<?php
// backend/public/index.php
// ------------------------------------------------------
// Modular API Front Controller (Router)
// Uses: helpers/Response.php, helpers/Validator.php, config/db_connect.php
// Route format: /backend/public/index.php?r=<resource>&a=<action>
// Example: ?r=health&a=ping, ?r=auth&a=login, ?r=menu&a=list
// ------------------------------------------------------

declare(strict_types=1);

// ---- Headers / CORS ----
require_once __DIR__ . '/../helpers/Response.php';
cors_headers(['*']); // adjust in production if needed

// JSON by default
header('Content-Type: application/json; charset=utf-8');

// Handle preflight quickly (also handled in cors_headers)
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
  http_response_code(204);
  exit;
}

// ---- Include DB + Helpers ----
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../helpers/Validator.php';

// ---- Small utilities ----
function q(string $key, ?string $default = null): ?string {
  return isset($_GET[$key]) ? (string)$_GET[$key] : $default;
}

/**
 * Resolve controller class + file by resource name.
 * auth -> controllers/AuthController.php, class AuthController
 * menu -> controllers/MenuController.php, class MenuController
 * reservations -> controllers/ReservationsController.php, class ReservationsController
 * orders -> controllers/OrdersController.php, class OrdersController
 * admin -> controllers/AdminController.php, class AdminController
 */
function resolve_controller(string $resource): array {
  $map = [
    'auth'         => 'AuthController',
    'menu'         => 'MenuController',
    'reservations' => 'ReservationsController',
    'orders'       => 'OrdersController',
    'admin'        => 'AdminController',  // ✅ added
  ];
  if (!isset($map[$resource])) {
    return [null, null];
  }
  $class = $map[$resource];
  $file  = __DIR__ . "/../controllers/{$class}.php";
  return [$class, $file];
}

/**
 * Dispatch to a controller method. The controller should be a class
 * with static public methods that accept ($conn) or use global $conn internally.
 */
function dispatch(string $resource, string $action): void {
  // Built-in "health" resource
  if ($resource === 'health') {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
      error_response('Method not allowed', 405);
    }
    respond([
      'status'  => 'ok',
      'message' => 'API alive',
      'time'    => date('c'),
      'router'  => 'modular',
    ]);
  }

  // Resolve controller file/class
  [$class, $file] = resolve_controller($resource);
  if ($class === null) {
    error_response('Unknown resource', 404, ['resource' => $resource]);
  }
  if (!file_exists($file)) {
    // Controller not implemented yet
    error_response('Not implemented (controller missing)', 501, [
      'resource' => $resource,
      'action'   => $action,
      'expect'   => basename($file),
      'hint'     => "Create backend/controllers/{$class}.php with static method {$action}()",
    ]);
  }

  require_once $file;

  if (!class_exists($class)) {
    error_response('Controller class not found', 500, ['class' => $class]);
  }
  if (!method_exists($class, $action)) {
    error_response('Action not found', 404, ['class' => $class, 'action' => $action]);
  }

  // Call: e.g., AuthController::login();
  try {
    global $conn;
    $ref = new ReflectionMethod($class, $action);
    if ($ref->getNumberOfParameters() >= 1) {
      $ref->invoke(null, $conn);
    } else {
      $ref->invoke(null);
    }
  } catch (Throwable $e) {
    error_response('Unhandled error', 500, ['message' => $e->getMessage()]);
  }
}

// ---- Read route ----
$resource = strtolower((string) q('r', 'health'));
$action   = strtolower((string) q('a', 'ping'));

// ---- Default route: empty -> health/ping ----
if ($resource === '' && $action === '') {
  $resource = 'health';
  $action   = 'ping';
}

// ---- Dispatch ----
dispatch($resource, $action);
