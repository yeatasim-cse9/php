<?php
// backend/helpers/Validator.php
// Common input validation utilities for the modular API.

declare(strict_types=1);

/**
 * Validate email address.
 */
function is_valid_email(string $email): bool {
    return (bool) filter_var(trim($email), FILTER_VALIDATE_EMAIL);
}

/**
 * Validate YYYY-MM-DD date.
 */
function is_valid_date(string $date): bool {
    $date = trim($date);
    $parts = explode('-', $date);
    if (count($parts) !== 3) return false;
    [$y, $m, $d] = array_map('intval', $parts);
    return checkdate($m, $d, $y);
}

/**
 * Validate HH:MM[:SS] 24-hour time.
 */
function is_valid_time(string $time): bool {
    $time = trim($time);
    return preg_match('/^(2[0-3]|[01]?\d):([0-5]\d)(:([0-5]\d))?$/', $time) === 1;
}

/**
 * Validate that a string equals one of allowed enum values.
 * @param string $value Incoming value
 * @param array<string> $allowed Allowed values (case-sensitive by default)
 * @param bool $caseInsensitive Compare case-insensitively if true
 */
function is_valid_enum(string $value, array $allowed, bool $caseInsensitive = false): bool {
    if ($caseInsensitive) {
        $value = mb_strtolower($value);
        $allowed = array_map('mb_strtolower', $allowed);
    }
    return in_array($value, $allowed, true);
}

/**
 * Validate positive integer (> 0).
 */
function is_positive_int($n): bool {
    if (is_int($n)) return $n > 0;
    if (is_string($n) && ctype_digit($n)) return (int)$n > 0;
    return false;
}

/**
 * Validate non-negative number (>= 0) for price/amount.
 */
function is_nonneg_number($v): bool {
    if (is_int($v) || is_float($v)) return $v >= 0;
    if (is_string($v) && is_numeric($v)) return (float)$v >= 0;
    return false;
}

/**
 * Trim + nullify empty strings. Helpful for optional fields.
 */
function nn(?string $s): ?string {
    $t = trim((string)$s);
    return $t === '' ? null : $t;
}

/**
 * Normalize phone (basic). Returns digits-only string or null if invalid length.
 * Accepts 10–14 digits to cover BD + others (e.g., 017..., +8801..., etc.).
 */
function normalize_phone(?string $phone): ?string {
    if ($phone === null) return null;
    $digits = preg_replace('/\D+/', '', $phone);
    if ($digits === null) return null;
    $len = strlen($digits);
    return ($len >= 10 && $len <= 14) ? $digits : null;
}

/**
 * Password strength check (min length, at least one letter & one digit).
 */
function is_strong_password(string $pass, int $minLen = 6): bool {
    if (strlen($pass) < $minLen) return false;
    $hasLetter = preg_match('/[A-Za-z]/', $pass) === 1;
    $hasDigit  = preg_match('/\d/', $pass) === 1;
    return $hasLetter && $hasDigit;
}

/**
 * Ensure date is within [today, today + $days] inclusive.
 * Returns true if $dateStr is valid and inside range; otherwise false.
 */
function is_date_in_next_days(string $dateStr, int $days): bool {
    if (!is_valid_date($dateStr)) return false;
    $date = DateTime::createFromFormat('Y-m-d', $dateStr);
    if (!$date) return false;

    $today = new DateTime('today');
    $max   = (clone $today)->modify('+' . $days . ' days');

    // Normalize time for comparison
    $date->setTime(0,0,0);
    $today->setTime(0,0,0);
    $max->setTime(0,0,0);

    return ($date >= $today && $date <= $max);
}

/**
 * Validate pagination inputs, returns [page, limit, offset].
 * - page >= 1, limit between 1..$maxLimit
 */
function validate_pagination($page, $limit, int $maxLimit = 100): array {
    $p = (is_numeric($page) && (int)$page > 0) ? (int)$page : 1;
    $l = (is_numeric($limit) && (int)$limit > 0) ? min((int)$limit, $maxLimit) : min(20, $maxLimit);
    $offset = ($p - 1) * $l;
    return [$p, $l, $offset];
}

/**
 * Coerce enum with default (case-insensitive optional).
 * If invalid, returns $default.
 */
function coerce_enum(string $value, array $allowed, string $default, bool $caseInsensitive = false): string {
    return is_valid_enum($value, $allowed, $caseInsensitive) ? $value : $default;
}

/**
 * Validate a list of items: each element must have integer item_id > 0 and quantity > 0.
 * Returns array with sanitized rows (int item_id, int quantity) or null on invalid.
 */
function sanitize_order_items($items): ?array {
    if (!is_array($items) || empty($items)) return null;
    $out = [];
    foreach ($items as $row) {
        $iid = isset($row['item_id']) ? (int)$row['item_id'] : 0;
        $qty = isset($row['quantity']) ? (int)$row['quantity'] : 0;
        if ($iid <= 0 || $qty <= 0) return null;
        $out[] = ['item_id' => $iid, 'quantity' => $qty];
    }
    return $out;
}
