<?php
// backend/controllers/InvoiceController.php
// Printable Invoice/Receipt for an order (HTML; use browser Print -> Save as PDF)

declare(strict_types=1);

require_once __DIR__ . '/../helpers/Response.php';
require_once __DIR__ . '/../helpers/Validator.php';

class InvoiceController
{
    /**
     * GET ?r=invoice&a=receipt&order_id=123
     * Renders a printable HTML invoice for the given order.
     * Notes:
     * - Overrides JSON header to text/html (safe)
     * - Uses Bootstrap-like minimal styles (no external CSS required)
     */
    public static function receipt(mysqli $conn): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
            error_response('Method not allowed', 405);
        }

        $order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
        if (!is_positive_int($order_id)) {
            error_response('Invalid order_id', 400);
        }

        // Fetch order + user
        $sqlOrder = "
          SELECT
            o.order_id, o.user_id, o.order_date, o.status, o.total_amount,
            o.delivery_type, o.delivery_address,
            u.name AS customer_name, u.email AS customer_email, u.phone AS customer_phone
          FROM orders o
          JOIN users u ON u.user_id = o.user_id
          WHERE o.order_id = ?
          LIMIT 1
        ";
        $st = $conn->prepare($sqlOrder);
        $st->bind_param('i', $order_id);
        $st->execute();
        $order = $st->get_result()->fetch_assoc();
        $st->close();

        if (!$order) {
            error_response('Order not found', 404);
        }

        // Fetch order items
        $sqlItems = "
          SELECT oi.order_item_id, oi.item_id, mi.name, oi.quantity, oi.price,
                 (oi.quantity * oi.price) AS line_total
          FROM order_items oi
          JOIN menu_items mi ON mi.item_id = oi.item_id
          WHERE oi.order_id = ?
          ORDER BY oi.order_item_id ASC
        ";
        $st2 = $conn->prepare($sqlItems);
        $st2->bind_param('i', $order_id);
        $st2->execute();
        $res = $st2->get_result();
        $items = [];
        while ($r = $res->fetch_assoc()) {
            $items[] = [
                'name'       => $r['name'],
                'quantity'   => (int)$r['quantity'],
                'price'      => (float)$r['price'],
                'line_total' => (float)$r['line_total'],
            ];
        }
        $st2->close();

        // Currency helper (৳)
        $fmt = function($num) { return '৳' . number_format((float)$num, 2); };

        // Business info (Cafe Rio — Gulshan)
        $biz = [
            'name' => 'The Cafe Rio — Gulshan',
            'slogan' => 'Best Buffet in Town',
            'address' => 'Jabbar Tower, 7th Floor, Gulshan-1, Dhaka 1212',
            'phone' => '01799-437172',
            'website' => 'http://localhost/restaurant-app/',
        ];

        // Totals (already computed total_amount; still recompute to show breakdown safety)
        $subtotal = 0.0;
        foreach ($items as $it) $subtotal += $it['line_total'];
        $grandTotal = (float)$order['total_amount']; // source of truth from DB

        // Output printable HTML
        header('Content-Type: text/html; charset=utf-8');

        $created = htmlspecialchars(date('d M Y, h:i A', strtotime((string)$order['order_date'])));
        $oid = (int)$order['order_id'];
        $delType = htmlspecialchars($order['delivery_type']);
        $delAddr = $order['delivery_address'] ? nl2br(htmlspecialchars($order['delivery_address'])) : '—';
        $status  = htmlspecialchars($order['status']);

        $custName  = htmlspecialchars($order['customer_name'] ?? '—');
        $custEmail = htmlspecialchars($order['customer_email'] ?? '—');
        $custPhone = htmlspecialchars($order['customer_phone'] ?? '—');

        $bizName   = htmlspecialchars($biz['name']);
        $bizAddr   = htmlspecialchars($biz['address']);
        $bizPhone  = htmlspecialchars($biz['phone']);
        $bizSite   = htmlspecialchars($biz['website']);
        $bizSlogan = htmlspecialchars($biz['slogan']);

        echo "<!DOCTYPE html>
<html lang='bn'>
<head>
<meta charset='utf-8'>
<meta name='viewport' content='width=device-width, initial-scale=1'>
<title>Invoice #{$oid} | {$bizName}</title>
<style>
  :root{ --ink:#222; --muted:#6c757d; --line:#e9ecef; --brand:#dc3545 }
  *{ box-sizing:border-box }
  body{ font-family: system-ui, -apple-system, Segoe UI, Roboto, 'Helvetica Neue', Arial, 'Noto Sans', 'Liberation Sans', sans-serif;
        color:var(--ink); margin:0; background:#f8f9fa }
  .sheet{ max-width:900px; margin:32px auto; background:#fff; border:1px solid var(--line); border-radius:14px; box-shadow:0 10px 26px rgba(0,0,0,.06) }
  .pad{ padding:28px }
  .topbar{ display:flex; align-items:center; justify-content:space-between; gap:16px; border-bottom:1px solid var(--line); }
  .brand{ display:flex; align-items:center; gap:14px }
  .logo{ width:46px; height:46px; border-radius:10px; background:var(--brand); color:#fff; display:grid; place-items:center; font-weight:700 }
  h1{ margin:0; font-size:1.25rem }
  .muted{ color:var(--muted) }
  .grid{ display:grid; grid-template-columns: 1fr 1fr; gap:18px; padding-top:18px }
  .card{ border:1px solid var(--line); border-radius:12px; padding:16px }
  .title{ font-weight:700; margin:0 0 4px 0 }
  table{ width:100%; border-collapse:collapse; margin-top:8px }
  th, td{ text-align:left; padding:10px 8px }
  thead th{ border-bottom:2px solid var(--line); font-size:.9rem; white-space:nowrap }
  tbody td{ border-bottom:1px dashed var(--line) }
  .num{ text-align:right; white-space:nowrap }
  .tag{ display:inline-block; padding:.22rem .5rem; border-radius:12px; border:1px solid var(--line); font-size:.8rem }
  .totals{ margin-top:12px; display:grid; grid-template-columns: 1fr auto; gap:6px; }
  .actions{ display:flex; gap:8px; justify-content:flex-end; padding:18px; border-top:1px solid var(--line) }
  .btn{ display:inline-flex; align-items:center; gap:8px; border:1px solid var(--line); background:#fff; border-radius:10px; padding:10px 14px; cursor:pointer; text-decoration:none; color:inherit }
  .btn-primary{ background:var(--brand); color:#fff; border-color:var(--brand) }
  @media print{
    body{ background:#fff }
    .actions{ display:none }
    .sheet{ box-shadow:none; border:0; margin:0 }
  }
</style>
</head>
<body>
  <div class='sheet'>
    <div class='pad topbar'>
      <div class='brand'>
        <div class='logo'>CR</div>
        <div>
          <h1>{$bizName}</h1>
          <div class='muted' style='font-size:.9rem'>{$bizSlogan}</div>
        </div>
      </div>
      <div class='muted' style='text-align:right'>
        <div><strong>Invoice #</strong> {$oid}</div>
        <div><strong>Date</strong> {$created}</div>
        <div><strong>Status</strong> <span class='tag'>".ucfirst($status)."</span></div>
      </div>
    </div>

    <div class='pad grid'>
      <div class='card'>
        <div class='title'>Billed To</div>
        <div>{$custName}</div>
        <div class='muted'>Email: {$custEmail}</div>
        <div class='muted'>Phone: {$custPhone}</div>
      </div>
      <div class='card'>
        <div class='title'>From</div>
        <div>{$bizName}</div>
        <div class='muted'>{$bizAddr}</div>
        <div class='muted'>Phone: {$bizPhone}</div>
        <div class='muted'>Website: {$bizSite}</div>
      </div>
    </div>

    <div class='pad'>
      <div class='card' style='padding:0'>
        <div style='display:flex; justify-content:space-between; align-items:center; padding:14px 16px; border-bottom:1px solid var(--line)'>
          <div><strong>Order Details</strong></div>
          <div class='muted'>Type: <span class='tag'>".ucfirst($delType)."</span></div>
        </div>
        <div style='padding:0 16px 12px 16px'>
          <table>
            <thead>
              <tr>
                <th style='width:52%'>Item</th>
                <th class='num' style='width:12%'>Qty</th>
                <th class='num' style='width:18%'>Price</th>
                <th class='num' style='width:18%'>Line Total</th>
              </tr>
            </thead>
            <tbody>";
              foreach ($items as $it) {
                  $name = htmlspecialchars($it['name']);
                  $qty  = (int)$it['quantity'];
                  $pr   = $fmt($it['price']);
                  $ln   = $fmt($it['line_total']);
                  echo "<tr>
                         <td>{$name}</td>
                         <td class='num'>{$qty}</td>
                         <td class='num'>{$pr}</td>
                         <td class='num'><strong>{$ln}</strong></td>
                       </tr>";
              }
        echo "  </tbody>
          </table>

          <div class='totals'>
            <div class='muted'>Subtotal</div>
            <div class='num'>".$fmt($subtotal)."</div>

            <div class='muted'>Tax / Service</div>
            <div class='num'>".$fmt(0)."</div>

            <div><strong>Grand Total</strong></div>
            <div class='num'><strong>".$fmt($grandTotal)."</strong></div>
          </div>

          <div style='margin-top:12px'>
            <div class='muted'><strong>Delivery Address:</strong></div>
            <div>". $delAddr ."</div>
          </div>
        </div>
      </div>
    </div>

    <div class='actions'>
      <button class='btn' onclick='window.history.back()'>&larr; Back</button>
      <button class='btn btn-primary' onclick='window.print()'>Print / Save as PDF</button>
    </div>
  </div>
</body>
</html>";
        // End echo
        exit;
    }

    /**
     * Optional JSON endpoint if you need raw data for custom clients later.
     * GET ?r=invoice&a=data&order_id=123
     */
    public static function data(mysqli $conn): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
            error_response('Method not allowed', 405);
        }
        $order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
        if (!is_positive_int($order_id)) {
            error_response('Invalid order_id', 400);
        }

        // Order
        $q = $conn->prepare("
          SELECT o.*, u.name AS customer_name, u.email AS customer_email, u.phone AS customer_phone
          FROM orders o
          JOIN users u ON u.user_id=o.user_id
          WHERE o.order_id=? LIMIT 1
        ");
        $q->bind_param('i', $order_id);
        $q->execute();
        $order = $q->get_result()->fetch_assoc();
        $q->close();
        if (!$order) error_response('Order not found', 404);

        // Items
        $q2 = $conn->prepare("
          SELECT oi.order_item_id, oi.item_id, mi.name, oi.quantity, oi.price,
                 (oi.quantity*oi.price) AS line_total
          FROM order_items oi JOIN menu_items mi ON mi.item_id=oi.item_id
          WHERE oi.order_id=?
          ORDER BY oi.order_item_id ASC
        ");
        $q2->bind_param('i', $order_id);
        $q2->execute();
        $rs = $q2->get_result();
        $items = [];
        while ($r = $rs->fetch_assoc()) {
            $items[] = [
                'order_item_id' => (int)$r['order_item_id'],
                'item_id'       => (int)$r['item_id'],
                'name'          => $r['name'],
                'quantity'      => (int)$r['quantity'],
                'price'         => (float)$r['price'],
                'line_total'    => (float)$r['line_total'],
            ];
        }
        $q2->close();

        respond([
            'order' => [
                'order_id'        => (int)$order['order_id'],
                'user_id'         => (int)$order['user_id'],
                'order_date'      => $order['order_date'],
                'status'          => $order['status'],
                'total_amount'    => (float)$order['total_amount'],
                'delivery_type'   => $order['delivery_type'],
                'delivery_address'=> $order['delivery_address'],
                'customer'        => [
                    'name'  => $order['customer_name'],
                    'email' => $order['customer_email'],
                    'phone' => $order['customer_phone'],
                ]
            ],
            'items' => $items
        ]);
    }
}
