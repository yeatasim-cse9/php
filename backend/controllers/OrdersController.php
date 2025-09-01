<?php
// backend/controllers/OrdersController.php
// Handles online orders: create + list + (admin) update_status + items

declare(strict_types=1);

require_once __DIR__ . '/../helpers/Response.php';
require_once __DIR__ . '/../helpers/Validator.php';

class OrdersController
{
    /**
     * POST ?r=orders&a=create
     * Body:
     * {
     *   "user_id": 2,
     *   "items": [
     *     {"item_id": 1, "quantity": 2},
     *     {"item_id": 3, "quantity": 1}
     *   ],
     *   "delivery_type": "pickup" | "delivery",
     *   "delivery_address": "optional when pickup"
     * }
     *
     * Rules:
     *  - items must be non-empty, each with item_id>0 & quantity>0
     *  - menu_items.status must be 'available'
     *  - Calculates total_amount (= sum(price * qty)) using snapshot of menu price
     *  - Uses a DB transaction; rolls back on any error
     */
    public static function create(mysqli $conn): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            error_response('Method not allowed', 405);
        }

        $data = read_json();
        $missing = require_fields($data, ['user_id', 'items']);
        if ($missing) error_response("Missing field: $missing", 400);

        $user_id = (int)$data['user_id'];
        if (!is_positive_int($user_id)) {
            error_response('Invalid user_id', 400);
        }

        $items = sanitize_order_items($data['items']);
        if ($items === null) {
            error_response('items must be a non-empty array with valid item_id & quantity', 400);
        }

        $delivery_type = coerce_enum(
            s($data['delivery_type'] ?? 'pickup'),
            ['pickup','delivery'],
            'pickup',
            true
        );
        $delivery_address = nn($data['delivery_address'] ?? null);
        if ($delivery_type === 'delivery' && $delivery_address === null) {
            error_response('delivery_address is required for delivery', 400);
        }

        // Optional: ensure user exists
        $chkUser = $conn->prepare("SELECT user_id FROM users WHERE user_id=? LIMIT 1");
        $chkUser->bind_param('i', $user_id);
        $chkUser->execute();
        $userRes = $chkUser->get_result()->fetch_assoc();
        $chkUser->close();
        if (!$userRes) {
            error_response('User not found', 404);
        }

        // Start transaction
        $conn->begin_transaction();
        try {
            // Create order shell
            $stmtOrder = $conn->prepare(
                "INSERT INTO orders (user_id, status, delivery_type, delivery_address, total_amount)
                 VALUES (?, 'pending', ?, ?, 0.00)"
            );
            $stmtOrder->bind_param('iss', $user_id, $delivery_type, $delivery_address);
            if (!$stmtOrder->execute()) {
                throw new Exception('DB error creating order');
            }
            $order_id = $stmtOrder->insert_id;
            $stmtOrder->close();

            // Prepare helpers
            $getPrice = $conn->prepare("SELECT price FROM menu_items WHERE item_id=? AND status='available' LIMIT 1");
            $insItem  = $conn->prepare("INSERT INTO order_items (order_id, item_id, quantity, price) VALUES (?,?,?,?)");

            $subtotal = 0.00;
            foreach ($items as $row) {
                $iid = $row['item_id'];
                $qty = $row['quantity'];

                // Check availability & fetch price snapshot
                $getPrice->bind_param('i', $iid);
                $getPrice->execute();
                $priceRow = $getPrice->get_result()->fetch_assoc();
                if (!$priceRow) {
                    throw new Exception("Item not available: {$iid}");
                }
                $price = (float)$priceRow['price'];

                // Insert into order_items
                $insItem->bind_param('iiid', $order_id, $iid, $qty, $price);
                if (!$insItem->execute()) {
                    throw new Exception('DB error inserting order item');
                }

                $subtotal += $price * $qty;
            }
            $getPrice->close();
            $insItem->close();

            // Update total
            $upd = $conn->prepare("UPDATE orders SET total_amount=? WHERE order_id=?");
            $upd->bind_param('di', $subtotal, $order_id);
            if (!$upd->execute()) {
                throw new Exception('DB error updating order total');
            }
            $upd->close();

            // Commit
            $conn->commit();

            respond([
                'message' => 'Order created',
                'order' => [
                    'order_id'       => $order_id,
                    'user_id'        => $user_id,
                    'status'         => 'pending',
                    'delivery_type'  => $delivery_type,
                    'delivery_address'=> $delivery_address,
                    'total_amount'   => (float)$subtotal,
                    'items'          => $items
                ]
            ], 201);

        } catch (Throwable $e) {
            $conn->rollback();
            error_response($e->getMessage(), 400);
        }
    }

    /**
     * GET ?r=orders&a=list
     * Optional filters (query params):
     *  - user_id (int)
     *  - status: pending|preparing|ready|completed|cancelled
     *  - page, limit (pagination; default 1,20; max 100)
     *
     * Examples:
     *  /backend/public/index.php?r=orders&a=list
     *  /backend/public/index.php?r=orders&a=list&user_id=2
     *  /backend/public/index.php?r=orders&a=list&status=pending&page=1&limit=10
     */
    public static function list(mysqli $conn): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            error_response('Method not allowed', 405);
        }

        $user_id  = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
        $statusIn = isset($_GET['status']) ? s($_GET['status']) : null;

        $status = null;
        $allowed = ['pending','preparing','ready','completed','cancelled'];
        if ($statusIn !== null && is_valid_enum($statusIn, $allowed, true)) {
            $status = strtolower($statusIn);
        }

        [$page, $limit, $offset] = validate_pagination($_GET['page'] ?? null, $_GET['limit'] ?? null, 100);

        $where = [];
        $types = '';
        $params = [];

        if ($user_id !== null && $user_id > 0) {
            $where[] = 'o.user_id = ?';
            $types  .= 'i';
            $params[] = $user_id;
        }
        if ($status !== null) {
            $where[] = 'o.status = ?';
            $types  .= 's';
            $params[] = $status;
        }
        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $sql = "
          SELECT
            o.order_id, o.user_id, u.name AS customer_name, o.status,
            o.delivery_type, o.delivery_address, o.order_date, o.total_amount
          FROM orders o
          JOIN users u ON u.user_id = o.user_id
          $whereSql
          ORDER BY o.order_date DESC, o.order_id DESC
          LIMIT ? OFFSET ?
        ";

        $types .= 'ii';
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $conn->prepare($sql);
        if ($types !== '') {
            $stmt->bind_param($types, ...$params);
        }
        if (!$stmt->execute()) {
            error_response('DB error while fetching orders', 500);
        }
        $res = $stmt->get_result();

        $rows = [];
        while ($row = $res->fetch_assoc()) {
            $rows[] = [
                'order_id'        => (int)$row['order_id'],
                'user_id'         => (int)$row['user_id'],
                'customer_name'   => $row['customer_name'],
                'status'          => $row['status'],
                'delivery_type'   => $row['delivery_type'],
                'delivery_address'=> $row['delivery_address'],
                'order_date'      => $row['order_date'],
                'total_amount'    => (float)$row['total_amount']
            ];
        }
        $stmt->close();

        respond([
            'page'  => $page,
            'limit' => $limit,
            'count' => count($rows),
            'items' => $rows
        ]);
    }

    /**
     * POST ?r=orders&a=update_status
     * Body: { "order_id": 123, "status": "preparing|ready|completed|cancelled" }
     *
     * Rules:
     *  - order must exist
     *  - cannot move away from final states: completed/cancelled
     *  - status must be one of allowed list
     */
    public static function update_status(mysqli $conn): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            error_response('Method not allowed', 405);
        }

        $data = read_json();
        $missing = require_fields($data, ['order_id','status']);
        if ($missing) error_response("Missing field: $missing", 400);

        $order_id = (int)$data['order_id'];
        $newStatus = strtolower(s($data['status']));

        $allowed = ['pending','preparing','ready','completed','cancelled'];
        if (!is_valid_enum($newStatus, $allowed, true)) {
            error_response('Invalid status value', 400, ['allowed' => $allowed]);
        }

        // Fetch current
        $stmt = $conn->prepare("SELECT status FROM orders WHERE order_id=? LIMIT 1");
        $stmt->bind_param('i', $order_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();

        if (!$row) {
            error_response('Order not found', 404);
        }
        $current = $row['status'];

        // Guard: final states cannot be changed
        if (in_array($current, ['completed','cancelled'], true) && $current !== $newStatus) {
            error_response("Order already '{$current}', cannot change", 409);
        }

        // No-op
        if ($current === $newStatus) {
            respond(['message' => 'No change', 'order_id' => $order_id, 'status' => $current]);
        }

        // Update
        $upd = $conn->prepare("UPDATE orders SET status=? WHERE order_id=?");
        $upd->bind_param('si', $newStatus, $order_id);
        if (!$upd->execute()) {
            error_response('DB error while updating order status', 500);
        }
        $upd->close();

        respond([
            'message'   => 'Order status updated',
            'order_id'  => $order_id,
            'from'      => $current,
            'to'        => $newStatus
        ]);
    }

    /**
     * GET ?r=orders&a=items&order_id=123
     * Returns order items with names & line totals
     */
    public static function items(mysqli $conn): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            error_response('Method not allowed', 405);
        }

        $order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
        if (!is_positive_int($order_id)) {
            error_response('Invalid order_id', 400);
        }

        // Ensure order exists
        $chk = $conn->prepare("SELECT order_id, user_id, total_amount FROM orders WHERE order_id=? LIMIT 1");
        $chk->bind_param('i', $order_id);
        $chk->execute();
        $order = $chk->get_result()->fetch_assoc();
        $chk->close();
        if (!$order) error_response('Order not found', 404);

        $sql = "
          SELECT
            oi.order_item_id, oi.item_id, mi.name, oi.quantity, oi.price,
            (oi.quantity * oi.price) AS line_total
          FROM order_items oi
          JOIN menu_items mi ON mi.item_id = oi.item_id
          WHERE oi.order_id=?
          ORDER BY oi.order_item_id ASC
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $order_id);
        if (!$stmt->execute()) {
            error_response('DB error while fetching order items', 500);
        }
        $res = $stmt->get_result();

        $items = [];
        while ($r = $res->fetch_assoc()) {
            $items[] = [
                'order_item_id' => (int)$r['order_item_id'],
                'item_id'       => (int)$r['item_id'],
                'name'          => $r['name'],
                'quantity'      => (int)$r['quantity'],
                'price'         => (float)$r['price'],
                'line_total'    => (float)$r['line_total'],
            ];
        }
        $stmt->close();

        respond([
            'order' => [
                'order_id'     => (int)$order['order_id'],
                'user_id'      => (int)$order['user_id'],
                'total_amount' => (float)$order['total_amount']
            ],
            'items' => $items
        ]);
    }
}
