<?php
// backend/controllers/AdminController.php
// Admin dashboard summary: today's KPIs + recent lists + top items

declare(strict_types=1);

require_once __DIR__ . '/../helpers/Response.php';
require_once __DIR__ . '/../helpers/Validator.php';

class AdminController
{
    /**
     * GET ?r=admin&a=dashboard
     * Optional query:
     *   recent_limit   -> integer (how many recent orders/reservations to include), default 5, max 20
     *   top_days       -> integer (lookback window for top items), default 7, max 60
     *
     * Response:
     * {
     *   "date": "YYYY-MM-DD",
     *   "reservations_today": { total, pending, confirmed, cancelled, upcoming: [...] },
     *   "orders_today": {
     *      total, pending, preparing, ready, completed, cancelled,
     *      total_sales_completed, recent: [...]
     *   },
     *   "top_menu_items": [{item_id,name,qty,total_amount}]
     * }
     */
    public static function dashboard(mysqli $conn): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
            error_response('Method not allowed', 405);
        }

        // sanitize inputs
        $recent_limit = isset($_GET['recent_limit']) ? (int)$_GET['recent_limit'] : 5;
        $top_days     = isset($_GET['top_days'])     ? (int)$_GET['top_days']     : 7;

        if ($recent_limit < 1) $recent_limit = 5;
        if ($recent_limit > 20) $recent_limit = 20;

        if ($top_days < 1) $top_days = 7;
        if ($top_days > 60) $top_days = 60;

        // ---------- Today's Reservations KPIs ----------
        $sqlResKpi = "
          SELECT
            COUNT(*)                                        AS total,
            SUM(CASE WHEN status='pending'   THEN 1 ELSE 0 END) AS pending,
            SUM(CASE WHEN status='confirmed' THEN 1 ELSE 0 END) AS confirmed,
            SUM(CASE WHEN status='cancelled' THEN 1 ELSE 0 END) AS cancelled
          FROM reservations
          WHERE reservation_date = CURDATE()
        ";
        $resKpi = $conn->query($sqlResKpi);
        if (!$resKpi) error_response('DB error (reservations KPI)', 500);
        $kpiRes = $resKpi->fetch_assoc() ?: ['total'=>0,'pending'=>0,'confirmed'=>0,'cancelled'=>0];

        // Upcoming list for today (next slots first)
        $sqlResUpcoming = "
          SELECT r.reservation_id, r.user_id, u.name AS customer_name,
                 r.reservation_time, r.people_count, r.table_type, r.status
          FROM reservations r
          JOIN users u ON u.user_id = r.user_id
          WHERE r.reservation_date = CURDATE()
          ORDER BY r.reservation_time ASC
          LIMIT ?
        ";
        $stmtResUp = $conn->prepare($sqlResUpcoming);
        $stmtResUp->bind_param('i', $recent_limit);
        $stmtResUp->execute();
        $upListRes = $stmtResUp->get_result();
        $upcoming = [];
        while ($row = $upListRes->fetch_assoc()) {
            $upcoming[] = [
                'reservation_id'  => (int)$row['reservation_id'],
                'user_id'         => (int)$row['user_id'],
                'customer_name'   => $row['customer_name'],
                'reservation_time'=> $row['reservation_time'],
                'people_count'    => (int)$row['people_count'],
                'table_type'      => $row['table_type'],
                'status'          => $row['status']
            ];
        }
        $stmtResUp->close();

        // ---------- Today's Orders KPIs ----------
        $sqlOrdKpi = "
          SELECT
            COUNT(*) AS total,
            SUM(CASE WHEN status='pending'   THEN 1 ELSE 0 END) AS pending,
            SUM(CASE WHEN status='preparing' THEN 1 ELSE 0 END) AS preparing,
            SUM(CASE WHEN status='ready'     THEN 1 ELSE 0 END) AS ready,
            SUM(CASE WHEN status='completed' THEN 1 ELSE 0 END) AS completed,
            SUM(CASE WHEN status='cancelled' THEN 1 ELSE 0 END) AS cancelled,
            SUM(CASE WHEN status='completed' THEN total_amount ELSE 0 END) AS total_sales_completed
          FROM orders
          WHERE DATE(order_date) = CURDATE()
        ";
        $ordKpi = $conn->query($sqlOrdKpi);
        if (!$ordKpi) error_response('DB error (orders KPI)', 500);
        $kpiOrd = $ordKpi->fetch_assoc() ?: [
            'total'=>0,'pending'=>0,'preparing'=>0,'ready'=>0,'completed'=>0,'cancelled'=>0,'total_sales_completed'=>0
        ];

        // Recent orders today (latest first)
        $sqlRecentOrders = "
          SELECT o.order_id, o.user_id, u.name AS customer_name, o.status,
                 o.delivery_type, o.total_amount, o.order_date
          FROM orders o
          JOIN users u ON u.user_id = o.user_id
          WHERE DATE(o.order_date) = CURDATE()
          ORDER BY o.order_date DESC, o.order_id DESC
          LIMIT ?
        ";
        $stmtRecent = $conn->prepare($sqlRecentOrders);
        $stmtRecent->bind_param('i', $recent_limit);
        $stmtRecent->execute();
        $recRes = $stmtRecent->get_result();
        $recentOrders = [];
        while ($row = $recRes->fetch_assoc()) {
            $recentOrders[] = [
                'order_id'       => (int)$row['order_id'],
                'user_id'        => (int)$row['user_id'],
                'customer_name'  => $row['customer_name'],
                'status'         => $row['status'],
                'delivery_type'  => $row['delivery_type'],
                'total_amount'   => (float)$row['total_amount'],
                'order_date'     => $row['order_date']
            ];
        }
        $stmtRecent->close();

        // ---------- Top menu items (last N days) ----------
        $sqlTopItems = "
          SELECT
            oi.item_id,
            mi.name,
            SUM(oi.quantity)              AS qty,
            SUM(oi.quantity * oi.price)   AS amount
          FROM order_items oi
          JOIN orders o  ON o.order_id = oi.order_id
          JOIN menu_items mi ON mi.item_id = oi.item_id
          WHERE o.order_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
          GROUP BY oi.item_id, mi.name
          ORDER BY qty DESC, amount DESC
          LIMIT 10
        ";
        $stmtTop = $conn->prepare($sqlTopItems);
        $stmtTop->bind_param('i', $top_days);
        $stmtTop->execute();
        $topRes = $stmtTop->get_result();
        $topItems = [];
        while ($row = $topRes->fetch_assoc()) {
            $topItems[] = [
                'item_id'      => (int)$row['item_id'],
                'name'         => $row['name'],
                'qty'          => (int)$row['qty'],
                'total_amount' => (float)$row['amount']
            ];
        }
        $stmtTop->close();

        respond([
            'date' => date('Y-m-d'),
            'reservations_today' => [
                'total'     => (int)$kpiRes['total'],
                'pending'   => (int)$kpiRes['pending'],
                'confirmed' => (int)$kpiRes['confirmed'],
                'cancelled' => (int)$kpiRes['cancelled'],
                'upcoming'  => $upcoming
            ],
            'orders_today' => [
                'total'                 => (int)$kpiOrd['total'],
                'pending'               => (int)$kpiOrd['pending'],
                'preparing'             => (int)$kpiOrd['preparing'],
                'ready'                 => (int)$kpiOrd['ready'],
                'completed'             => (int)$kpiOrd['completed'],
                'cancelled'             => (int)$kpiOrd['cancelled'],
                'total_sales_completed' => (float)$kpiOrd['total_sales_completed'],
                'recent'                => $recentOrders
            ],
            'top_menu_items' => $topItems,
            'params' => [
                'recent_limit' => $recent_limit,
                'top_days'     => $top_days
            ]
        ]);
    }
}
