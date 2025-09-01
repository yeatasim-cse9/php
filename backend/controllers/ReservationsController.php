<?php
// backend/controllers/ReservationsController.php
// Reservations: create + list + (admin) update_status

declare(strict_types=1);

require_once __DIR__ . '/../helpers/Response.php';
require_once __DIR__ . '/../helpers/Validator.php';

class ReservationsController
{
    /**
     * POST ?r=reservations&a=create
     * Body: {
     *   "user_id": 2,
     *   "reservation_date": "YYYY-MM-DD",
     *   "reservation_time": "HH:MM" | "HH:MM:SS",
     *   "people_count": 4,
     *   "table_type": "family|couple|window",
     *   "special_request": "optional"
     * }
     * Rules:
     *  - Date must be today .. today+30 days
     *  - Time HH:MM (24h)
     *  - people_count > 0
     *  - Same user cannot double-book exact same date+time if status in (pending, confirmed)
     */
    public static function create(mysqli $conn): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            error_response('Method not allowed', 405);
        }

        $data = read_json();
        $missing = require_fields($data, ['user_id','reservation_date','reservation_time','people_count']);
        if ($missing) error_response("Missing field: $missing", 400);

        $user_id = (int)$data['user_id'];
        $rdate   = s($data['reservation_date']);
        $rtime   = s($data['reservation_time']);
        $count   = (int)$data['people_count'];
        $ttype   = s($data['table_type'] ?? 'family');
        $sreq    = s($data['special_request'] ?? '');

        if (!is_positive_int($user_id)) {
            error_response('Invalid user_id', 400);
        }
        if (!is_valid_date($rdate) || !is_date_in_next_days($rdate, 30)) {
            error_response('reservation_date must be within the next 30 days (including today)', 400);
        }
        if (!is_valid_time($rtime)) {
            error_response('Invalid reservation_time (HH:MM or HH:MM:SS 24h)', 400);
        }
        if (!is_positive_int($count)) {
            error_response('people_count must be > 0', 400);
        }
        if (!is_valid_enum($ttype, ['family','couple','window'], true)) {
            $ttype = 'family';
        }

        // Check duplicate for same user/date/time (pending|confirmed)
        $stmt = $conn->prepare(
            "SELECT COUNT(*) AS c
             FROM reservations
             WHERE user_id=? AND reservation_date=? AND reservation_time=? AND status IN ('pending','confirmed')"
        );
        $stmt->bind_param('iss', $user_id, $rdate, $rtime);
        $stmt->execute();
        $dup = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ((int)$dup['c'] > 0) {
            error_response('You already have a reservation at this time', 409);
        }

        // Create
        $status = 'pending';
        $stmt = $conn->prepare(
            "INSERT INTO reservations (user_id, reservation_date, reservation_time, people_count, table_type, status, special_request)
             VALUES (?,?,?,?,?,?,?)"
        );
        $stmt->bind_param('ississs', $user_id, $rdate, $rtime, $count, $ttype, $status, $sreq);
        if (!$stmt->execute()) {
            error_response('DB error while creating reservation', 500);
        }
        $rid = $stmt->insert_id;
        $stmt->close();

        respond([
            'message' => 'Reservation created',
            'reservation' => [
                'reservation_id'  => $rid,
                'user_id'         => $user_id,
                'reservation_date'=> $rdate,
                'reservation_time'=> $rtime,
                'people_count'    => $count,
                'table_type'      => $ttype,
                'status'          => $status,
                'special_request' => $sreq
            ]
        ], 201);
    }

    /**
     * GET ?r=reservations&a=list
     * Optional filters (query params):
     *  - user_id (int)
     *  - status: pending|confirmed|cancelled
     *  - date_from: YYYY-MM-DD
     *  - date_to:   YYYY-MM-DD
     *  - page, limit (default 1,20; max 100)
     */
    public static function list(mysqli $conn): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            error_response('Method not allowed', 405);
        }

        $user_id   = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
        $statusRaw = isset($_GET['status']) ? s($_GET['status']) : null;
        $date_from = isset($_GET['date_from']) ? s($_GET['date_from']) : null;
        $date_to   = isset($_GET['date_to']) ? s($_GET['date_to']) : null;

        $status = null;
        if ($statusRaw !== null && is_valid_enum($statusRaw, ['pending','confirmed','cancelled'], true)) {
            $status = strtolower($statusRaw);
        }

        if ($date_from !== null && !is_valid_date($date_from)) $date_from = null;
        if ($date_to   !== null && !is_valid_date($date_to))   $date_to   = null;

        [$page, $limit, $offset] = validate_pagination($_GET['page'] ?? null, $_GET['limit'] ?? null, 100);

        $where = [];
        $params = [];
        $types  = '';

        if ($user_id !== null && $user_id > 0) {
            $where[] = 'r.user_id = ?';
            $types  .= 'i';
            $params[] = $user_id;
        }
        if ($status !== null) {
            $where[] = 'r.status = ?';
            $types  .= 's';
            $params[] = $status;
        }
        if ($date_from !== null) {
            $where[] = 'r.reservation_date >= ?';
            $types  .= 's';
            $params[] = $date_from;
        }
        if ($date_to !== null) {
            $where[] = 'r.reservation_date <= ?';
            $types  .= 's';
            $params[] = $date_to;
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
        $sql = "
            SELECT
              r.reservation_id, r.user_id, u.name AS customer_name,
              r.reservation_date, r.reservation_time, r.people_count,
              r.table_type, r.status, r.special_request, r.created_at
            FROM reservations r
            JOIN users u ON u.user_id = r.user_id
            $whereSql
            ORDER BY r.reservation_date ASC, r.reservation_time ASC
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
            error_response('DB error while fetching reservations', 500);
        }
        $res = $stmt->get_result();

        $rows = [];
        while ($row = $res->fetch_assoc()) {
            $rows[] = [
                'reservation_id'   => (int)$row['reservation_id'],
                'user_id'          => (int)$row['user_id'],
                'customer_name'    => $row['customer_name'],
                'reservation_date' => $row['reservation_date'],
                'reservation_time' => $row['reservation_time'],
                'people_count'     => (int)$row['people_count'],
                'table_type'       => $row['table_type'],
                'status'           => $row['status'],
                'special_request'  => $row['special_request'],
                'created_at'       => $row['created_at'],
            ];
        }
        $stmt->close();

        respond([
            'page'   => $page,
            'limit'  => $limit,
            'count'  => count($rows),
            'items'  => $rows
        ]);
    }

    /**
     * POST ?r=reservations&a=update_status
     * Body: { "reservation_id": 123, "status": "pending|confirmed|cancelled" }
     *
     * Rules:
     *  - reservation must exist
     *  - cannot change once cancelled (user-cancel safeguard per spec)
     *  - allowed statuses: pending, confirmed, cancelled
     */
    public static function update_status(mysqli $conn): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            error_response('Method not allowed', 405);
        }

        $data = read_json();
        $missing = require_fields($data, ['reservation_id','status']);
        if ($missing) error_response("Missing field: $missing", 400);

        $rid = (int)$data['reservation_id'];
        $to  = strtolower(s($data['status']));

        $allowed = ['pending','confirmed','cancelled'];
        if (!is_valid_enum($to, $allowed, true)) {
            error_response('Invalid status value', 400, ['allowed' => $allowed]);
        }

        // fetch current
        $stmt = $conn->prepare("SELECT status FROM reservations WHERE reservation_id=? LIMIT 1");
        $stmt->bind_param('i', $rid);
        $stmt->execute();
        $rs = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$rs) {
            error_response('Reservation not found', 404);
        }
        $current = $rs['status'];

        // safeguard: once cancelled, do not allow changes (as per requirement)
        if ($current === 'cancelled' && $to !== 'cancelled') {
            error_response("Reservation already cancelled, cannot change", 409);
        }

        // no-op
        if ($current === $to) {
            respond(['message'=>'No change','reservation_id'=>$rid,'status'=>$current]);
        }

        $upd = $conn->prepare("UPDATE reservations SET status=? WHERE reservation_id=?");
        $upd->bind_param('si', $to, $rid);
        if (!$upd->execute()) {
            error_response('DB error while updating reservation status', 500);
        }
        $upd->close();

        respond([
            'message' => 'Reservation status updated',
            'reservation_id' => $rid,
            'from' => $current,
            'to'   => $to
        ]);
    }
}
