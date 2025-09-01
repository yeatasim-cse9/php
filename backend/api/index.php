<?php
// backend/api/index.php
// Single-file API Router (PHP + mysqli)
// --------------------------------------------------
// Requires: ../config/db_connect.php  (already created)
// DB schema: restaurant_db.sql (already imported)
// --------------------------------------------------

declare(strict_types=1);

// ===== Bootstrap =====
header('Content-Type: application/json; charset=utf-8');
// Basic CORS (adjust origins for production)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(204);
  exit;
}

require_once __DIR__ . '/../config/db_connect.php';

// ===== Utilities =====
function respond(array $data, int $code = 200): void {
  http_response_code($code);
  echo json_encode($data, JSON_UNESCAPED_UNICODE);
  exit;
}

function read_json(): array {
  $raw = file_get_contents('php://input');
  if (!$raw) return [];
  $data = json_decode($raw, true);
  return is_array($data) ? $data : [];
}

function require_fields(array $data, array $fields): ?string {
  foreach ($fields as $f) {
    if (!isset($data[$f]) || $data[$f] === '') {
      return "Missing field: {$f}";
    }
  }
  return null;
}

function valid_date(string $d): bool {
  $parts = explode('-', $d);
  if (count($parts) !== 3) return false;
  return checkdate((int)$parts[1], (int)$parts[2], (int)$parts[0]);
}

function valid_time(string $t): bool {
  return preg_match('/^(2[0-3]|[01]?[0-9]):([0-5][0-9])(:[0-5][0-9])?$/', $t) === 1;
}

function sanitize_str(?string $s): string {
  return trim((string)$s);
}

// ===== Simple Router =====
// Use query param ?resource=...&action=...
$resource = strtolower($_GET['resource'] ?? $_GET['r'] ?? '');
$action   = strtolower($_GET['action']   ?? $_GET['a'] ?? '');
$method   = $_SERVER['REQUEST_METHOD'];

// Defaults: health check
if ($resource === '' && $action === '') {
  $resource = 'health';
  $action = 'ping';
}

// ===== Handlers =====
switch ($resource) {

  // ------------------------------------------------
  // Health
  // GET /api/index.php?resource=health&action=ping
  // ------------------------------------------------
  case 'health':
    if ($method !== 'GET') respond(['error' => 'Method not allowed'], 405);
    respond(['status' => 'ok', 'message' => 'API alive', 'time' => date('c')]);
    break;

  // ------------------------------------------------
  // Auth: register / login (simple, no JWT yet)
  // ------------------------------------------------
  case 'auth':
    if ($action === 'register') {
      if ($method !== 'POST') respond(['error' => 'Method not allowed'], 405);
      $data = read_json();
      $err = require_fields($data, ['name','email','password']);
      if ($err) respond(['error' => $err], 400);

      $name  = sanitize_str($data['name']);
      $email = strtolower(sanitize_str($data['email']));
      $phone = sanitize_str($data['phone'] ?? '');
      $pass  = (string)$data['password'];

      if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        respond(['error' => 'Invalid email'], 400);
      }
      if (strlen($pass) < 6) {
        respond(['error' => 'Password must be at least 6 chars'], 400);
      }

      // Check unique email
      global $conn;
      $stmt = $conn->prepare("SELECT user_id FROM users WHERE email=? LIMIT 1");
      $stmt->bind_param('s', $email);
      $stmt->execute();
      $stmt->store_result();
      if ($stmt->num_rows > 0) {
        respond(['error' => 'Email already exists'], 409);
      }
      $stmt->close();

      $hash = password_hash($pass, PASSWORD_DEFAULT);
      $stmt = $conn->prepare("INSERT INTO users (name,email,phone,password,role) VALUES (?,?,?,?, 'user')");
      $stmt->bind_param('ssss', $name, $email, $phone, $hash);
      if (!$stmt->execute()) {
        respond(['error' => 'DB error while creating user'], 500);
      }
      $user_id = $stmt->insert_id;
      $stmt->close();

      respond([
        'message' => 'Registered successfully',
        'user' => ['user_id'=>$user_id, 'name'=>$name, 'email'=>$email, 'phone'=>$phone, 'role'=>'user']
      ], 201);

    } elseif ($action === 'login') {
      if ($method !== 'POST') respond(['error' => 'Method not allowed'], 405);
      $data = read_json();
      $err = require_fields($data, ['email','password']);
      if ($err) respond(['error' => $err], 400);

      $email = strtolower(sanitize_str($data['email']));
      $pass  = (string)$data['password'];

      global $conn;
      $stmt = $conn->prepare("SELECT user_id, name, email, phone, password, role FROM users WHERE email=? LIMIT 1");
      $stmt->bind_param('s', $email);
      $stmt->execute();
      $res = $stmt->get_result();
      $user = $res->fetch_assoc();
      $stmt->close();

      if (!$user || !password_verify($pass, $user['password'])) {
        respond(['error' => 'Invalid credentials'], 401);
      }

      // NOTE: For production, issue a JWT/session. Here we return profile only.
      respond([
        'message' => 'Login success',
        'user' => [
          'user_id' => (int)$user['user_id'],
          'name'    => $user['name'],
          'email'   => $user['email'],
          'phone'   => $user['phone'],
          'role'    => $user['role']
        ]
      ]);
    } else {
      respond(['error' => 'Unknown auth action'], 400);
    }
    break;

  // ------------------------------------------------
  // Menu
  // GET  /api/index.php?resource=menu&action=list
  // POST /api/index.php?resource=menu&action=create (optional)
  // ------------------------------------------------
  case 'menu':
    if ($action === 'list') {
      if ($method !== 'GET') respond(['error' => 'Method not allowed'], 405);
      global $conn;
      $sql = "SELECT item_id, name, description, price, category, image, status, created_at
              FROM menu_items
              WHERE status='available'
              ORDER BY created_at DESC";
      $result = $conn->query($sql);
      $items = [];
      while ($row = $result->fetch_assoc()) {
        $row['item_id'] = (int)$row['item_id'];
        $row['price'] = (float)$row['price'];
        $items[] = $row;
      }
      respond(['items' => $items]);
    } elseif ($action === 'create') {
      // Basic create (admin guard not implemented yet)
      if ($method !== 'POST') respond(['error' => 'Method not allowed'], 405);
      $data = read_json();
      $err = require_fields($data, ['name','price']);
      if ($err) respond(['error' => $err], 400);

      $name = sanitize_str($data['name']);
      $desc = sanitize_str($data['description'] ?? '');
      $price = (float)$data['price'];
      $cat = sanitize_str($data['category'] ?? '');
      $img = sanitize_str($data['image'] ?? '');
      $status = in_array(($data['status'] ?? 'available'), ['available','unavailable'], true)
        ? $data['status'] : 'available';

      if ($price < 0) respond(['error'=>'Price must be >= 0'], 400);

      global $conn;
      $stmt = $conn->prepare("INSERT INTO menu_items (name,description,price,category,image,status) VALUES (?,?,?,?,?,?)");
      $stmt->bind_param('ssdcss', $name, $desc, $price, $cat, $img, $status);
      // NOTE: 'd' is double, but mysqli needs 'd' for float/decimal; binding as double is fine
      if (!$stmt->execute()) {
        respond(['error' => 'DB error while creating menu item'], 500);
      }
      $id = $stmt->insert_id;
      $stmt->close();

      respond(['message'=>'Menu item created','item_id'=>$id], 201);
    } else {
      respond(['error' => 'Unknown menu action'], 400);
    }
    break;

  // ------------------------------------------------
  // Reservations
  // POST /api/index.php?resource=reservations&action=create
  // body: user_id, reservation_date (YYYY-MM-DD), reservation_time (HH:MM), people_count, table_type, special_request?
  // Constraint: date must be today..+30 days (not past, not > +30)
  // ------------------------------------------------
  case 'reservations':
    if ($action === 'create') {
      if ($method !== 'POST') respond(['error' => 'Method not allowed'], 405);
      $data = read_json();
      $err = require_fields($data, ['user_id','reservation_date','reservation_time','people_count']);
      if ($err) respond(['error' => $err], 400);

      $user_id = (int)$data['user_id'];
      $rdate   = sanitize_str($data['reservation_date']);
      $rtime   = sanitize_str($data['reservation_time']);
      $count   = (int)$data['people_count'];
      $ttype   = sanitize_str($data['table_type'] ?? 'family');
      $sreq    = sanitize_str($data['special_request'] ?? '');

      if (!valid_date($rdate)) respond(['error'=>'Invalid reservation_date'], 400);
      if (!valid_time($rtime)) respond(['error'=>'Invalid reservation_time'], 400);
      if ($count <= 0) respond(['error'=>'people_count must be > 0'], 400);
      if (!in_array($ttype, ['family','couple','window'], true)) $ttype = 'family';

      // Date range: today .. today+30
      $today = new DateTime('today');
      $max   = (clone $today)->modify('+30 days');
      $given = DateTime::createFromFormat('Y-m-d', $rdate);
      if (!$given) respond(['error'=>'Invalid reservation_date format'], 400);
      if ($given < $today) respond(['error'=>'Reservation date cannot be in the past'], 400);
      if ($given > $max)   respond(['error'=>'Reservation date cannot be more than 30 days away'], 400);

      // Optional: Check overlapping reservations (simple check by same user/time)
      global $conn;
      $stmt = $conn->prepare("SELECT COUNT(*) c FROM reservations
                              WHERE user_id=? AND reservation_date=? AND reservation_time=? AND status IN ('pending','confirmed')");
      $stmt->bind_param('iss', $user_id, $rdate, $rtime);
      $stmt->execute();
      $res = $stmt->get_result()->fetch_assoc();
      $stmt->close();
      if ((int)$res['c'] > 0) {
        respond(['error'=>'You already have a reservation at this time'], 409);
      }

      $status = 'pending';
      $stmt = $conn->prepare("INSERT INTO reservations (user_id,reservation_date,reservation_time,people_count,table_type,status,special_request)
                              VALUES (?,?,?,?,?,?,?)");
      $stmt->bind_param('ississs', $user_id, $rdate, $rtime, $count, $ttype, $status, $sreq);
      if (!$stmt->execute()) {
        respond(['error'=>'DB error while creating reservation'], 500);
      }
      $rid = $stmt->insert_id;
      $stmt->close();

      respond([
        'message'=>'Reservation created',
        'reservation_id'=>$rid,
        'status'=>$status
      ], 201);
    } else {
      respond(['error' => 'Unknown reservations action'], 400);
    }
    break;

  // ------------------------------------------------
  // Orders
  // POST /api/index.php?resource=orders&action=create
  // body: user_id, items:[{item_id, quantity}], delivery_type('pickup'|'delivery'), delivery_address?
  // ------------------------------------------------
  case 'orders':
    if ($action === 'create') {
      if ($method !== 'POST') respond(['error' => 'Method not allowed'], 405);
      $data = read_json();
      $err = require_fields($data, ['user_id','items']);
      if ($err) respond(['error' => $err], 400);

      $user_id = (int)$data['user_id'];
      $items = $data['items'];
      if (!is_array($items) || count($items) === 0) {
        respond(['error'=>'items must be a non-empty array'], 400);
      }

      $delivery_type = in_array(($data['delivery_type'] ?? 'pickup'), ['pickup','delivery'], true)
        ? $data['delivery_type'] : 'pickup';
      $delivery_address = sanitize_str($data['delivery_address'] ?? null);

      global $conn;
      $conn->begin_transaction();

      try {
        // Create order
        $stmt = $conn->prepare("INSERT INTO orders (user_id, status, delivery_type, delivery_address, total_amount) VALUES (?, 'pending', ?, ?, 0.00)");
        $stmt->bind_param('iss', $user_id, $delivery_type, $delivery_address);
        if (!$stmt->execute()) {
          throw new Exception('DB error creating order');
        }
        $order_id = $stmt->insert_id;
        $stmt->close();

        // Insert items (price snapshot from menu_items)
        $getPrice = $conn->prepare("SELECT price FROM menu_items WHERE item_id=? AND status='available' LIMIT 1");
        $insItem  = $conn->prepare("INSERT INTO order_items (order_id, item_id, quantity, price) VALUES (?,?,?,?)");

        $subTotal = 0.00;
        foreach ($items as $it) {
          $iid = (int)($it['item_id'] ?? 0);
          $qty = (int)($it['quantity'] ?? 0);
          if ($iid <= 0 || $qty <= 0) throw new Exception('Invalid item payload');

          $getPrice->bind_param('i', $iid);
          $getPrice->execute();
          $res = $getPrice->get_result()->fetch_assoc();
          if (!$res) throw new Exception("Item not available: {$iid}");
          $price = (float)$res['price'];

          $insItem->bind_param('iiid', $order_id, $iid, $qty, $price);
          if (!$insItem->execute()) {
            throw new Exception('DB error inserting order item');
          }

          $subTotal += ($price * $qty);
        }
        $getPrice->close();
        $insItem->close();

        // Update total (trigger also exists, but we set explicitly)
        $up = $conn->prepare("UPDATE orders SET total_amount=? WHERE order_id=?");
        $up->bind_param('di', $subTotal, $order_id);
        if (!$up->execute()) {
          throw new Exception('DB error updating order total');
        }
        $up->close();

        $conn->commit();
        respond([
          'message'    => 'Order created',
          'order_id'   => $order_id,
          'total'      => (float)$subTotal,
          'status'     => 'pending',
          'items_count'=> count($items)
        ], 201);
      } catch (Exception $e) {
        $conn->rollback();
        respond(['error' => $e->getMessage()], 400);
      }
    } else {
      respond(['error' => 'Unknown orders action'], 400);
    }
    break;

  // ------------------------------------------------
  default:
    respond(['error' => 'Unknown resource'], 404);
}

// end switch
