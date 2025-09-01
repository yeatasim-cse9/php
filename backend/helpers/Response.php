<?php
// backend/helpers/Response.php
// Common response + request helpers for API (modular use)

declare(strict_types=1);

/**
 * Send JSON response and exit.
 */
function respond(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Send standardized error payload.
 */
function error_response(string $message, int $code = 400, array $extra = []): void {
    $payload = array_merge(['error' => $message], $extra);
    respond($payload, $code);
}

/**
 * Read JSON body as associative array.
 * Returns [] if no/invalid JSON.
 */
function read_json(): array {
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

/**
 * Require specific fields in an array; returns first missing field name or null if OK.
 */
function require_fields(array $data, array $fields): ?string {
    foreach ($fields as $f) {
        if (!array_key_exists($f, $data) || $data[$f] === '' || $data[$f] === null) {
            return $f;
        }
    }
    return null;
}

/**
 * Simple string sanitizer (trim + cast).
 */
function s(?string $v): string {
    return trim((string) $v);
}

/**
 * Basic CORS helper (call at the top of public/index.php).
 */
function cors_headers(array $allowedOrigins = ['*']): void {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
    $allow  = in_array('*', $allowedOrigins, true) || in_array($origin, $allowedOrigins, true)
        ? $origin : $allowedOrigins[0];

    header('Access-Control-Allow-Origin: ' . $allow);
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}
