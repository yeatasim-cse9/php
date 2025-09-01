<?php
// backend/controllers/ReviewsController.php
// Reviews & Ratings: create + list + remove + summary

declare(strict_types=1);

require_once __DIR__ . '/../helpers/Response.php';
require_once __DIR__ . '/../helpers/Validator.php';

class ReviewsController
{
    /**
     * POST ?r=reviews&a=create
     * Body:
     * {
     *   "user_id": 2,
     *   "rating": 1..5,
     *   "item_id": 10,            // optional; null => restaurant-level review
     *   "comment": "text here"    // optional
     * }
     *
     * Rules:
     * - rating 1..5
     * - user must exist
     * - if item_id provided -> item must exist & be available or unavailable (any status ok)
     */
    public static function create(mysqli $conn): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            error_response('Method not allowed', 405);
        }

        $data = read_json();

        $missing = require_fields($data, ['user_id', 'rating']);
        if ($missing) error_response("Missing field: $missing", 400);

        $user_id = (int)$data['user_id'];
        $rating  = (int)$data['rating'];
        $item_id = isset($data['item_id']) && $data['item_id'] !== '' ? (int)$data['item_id'] : null;
        $comment = s($data['comment'] ?? '');

        if (!is_positive_int($user_id)) error_response('Invalid user_id', 400);
        if ($rating < 1 || $rating > 5) error_response('rating must be 1..5', 400);

        // Ensure user exists
        $stmtU = $conn->prepare("SELECT user_id, name, role FROM users WHERE user_id=? LIMIT 1");
        $stmtU->bind_param('i', $user_id);
        $stmtU->execute();
        $u = $stmtU->get_result()->fetch_assoc();
        $stmtU->close();
        if (!$u) error_response('User not found', 404);

        // If item_id given, ensure item exists
        if ($item_id !== null) {
            $stmtI = $conn->prepare("SELECT item_id, name FROM menu_items WHERE item_id=? LIMIT 1");
            $stmtI->bind_param('i', $item_id);
            $stmtI->execute();
            $it = $stmtI->get_result()->fetch_assoc();
            $stmtI->close();
            if (!$it) error_response('Menu item not found', 404);
        }

        // Insert review
        $sql = "INSERT INTO reviews (user_id, item_id, rating, comment) VALUES (?,?,?,?)";
        $stmt = $conn->prepare($sql);
        // item_id may be null -> use i or null type binding workaround
        if ($item_id === null) {
            $null = null;
            $stmt->bind_param('iiis', $user_id, $null, $rating, $comment);
        } else {
            $stmt->bind_param('iiis', $user_id, $item_id, $rating, $comment);
        }
        if (!$stmt->execute()) {
            error_response('DB error while creating review', 500);
        }
        $rid = $stmt->insert_id;
        $stmt->close();

        respond([
            'message' => 'Review created',
            'review' => [
                'review_id' => $rid,
                'user' => [
                    'user_id' => (int)$u['user_id'],
                    'name'    => $u['name'],
                    'role'    => $u['role'],
                ],
                'item_id' => $item_id,
                'rating'  => $rating,
                'comment' => $comment,
                'created_at' => date('Y-m-d H:i:s')
            ]
        ], 201);
    }

    /**
     * GET ?r=reviews&a=list
     * Optional query:
     *   item_id   -> int (filter by item)
     *   user_id   -> int (filter by user)
     *   page,limit -> pagination (default 1,20; max 100)
     *
     * Response: { page, limit, count, items:[{...}] }
     * Each item includes: review_id, rating, comment, created_at,
     *   user: {user_id, name}, item: {item_id, name|null}
     */
    public static function list(mysqli $conn): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
            error_response('Method not allowed', 405);
        }

        $item_id = isset($_GET['item_id']) ? (int)$_GET['item_id'] : null;
        $user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;

        [$page, $limit, $offset] = validate_pagination($_GET['page'] ?? null, $_GET['limit'] ?? null, 100);

        $where = [];
        $types = '';
        $params = [];

        if ($item_id !== null && $item_id > 0) {
            $where[] = 'r.item_id = ?';
            $types  .= 'i';
            $params[] = $item_id;
        }
        if ($user_id !== null && $user_id > 0) {
            $where[] = 'r.user_id = ?';
            $types  .= 'i';
            $params[] = $user_id;
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "
          SELECT
            r.review_id, r.user_id, r.item_id, r.rating, r.comment, r.created_at,
            u.name AS user_name,
            mi.name AS item_name
          FROM reviews r
          JOIN users u ON u.user_id = r.user_id
          LEFT JOIN menu_items mi ON mi.item_id = r.item_id
          $whereSql
          ORDER BY r.created_at DESC, r.review_id DESC
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
            error_response('DB error while fetching reviews', 500);
        }
        $res = $stmt->get_result();

        $items = [];
        while ($row = $res->fetch_assoc()) {
            $items[] = [
                'review_id'  => (int)$row['review_id'],
                'rating'     => (int)$row['rating'],
                'comment'    => $row['comment'],
                'created_at' => $row['created_at'],
                'user' => [
                    'user_id' => (int)$row['user_id'],
                    'name'    => $row['user_name']
                ],
                'item' => [
                    'item_id' => $row['item_id'] !== null ? (int)$row['item_id'] : null,
                    'name'    => $row['item_name'] ?? null
                ]
            ];
        }
        $stmt->close();

        respond([
            'page'  => $page,
            'limit' => $limit,
            'count' => count($items),
            'items' => $items
        ]);
    }

    /**
     * POST ?r=reviews&a=remove
     * Body: { "review_id": 123 }
     * Deletes a review (for admin moderation).
     */
    public static function remove(mysqli $conn): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            error_response('Method not allowed', 405);
        }

        $data = read_json();
        $missing = require_fields($data, ['review_id']);
        if ($missing) error_response("Missing field: $missing", 400);

        $rid = (int)$data['review_id'];
        if (!is_positive_int($rid)) error_response('Invalid review_id', 400);

        // Check exists
        $chk = $conn->prepare("SELECT review_id FROM reviews WHERE review_id=? LIMIT 1");
        $chk->bind_param('i', $rid);
        $chk->execute();
        $found = $chk->get_result()->fetch_assoc();
        $chk->close();
        if (!$found) error_response('Review not found', 404);

        $del = $conn->prepare("DELETE FROM reviews WHERE review_id=?");
        $del->bind_param('i', $rid);
        if (!$del->execute()) {
            error_response('DB error while deleting review', 500);
        }
        $del->close();

        respond(['message'=>'Review removed','review_id'=>$rid]);
    }

    /**
     * GET ?r=reviews&a=summary
     * Query:
     *   item_id (optional)
     *
     * If item_id provided: returns summary for that item.
     * If not provided: returns global summary (including restaurant-level reviews where item_id IS NULL).
     *
     * Response:
     * {
     *   "scope": "item"|"global",
     *   "item": { "item_id": 10, "name": "BBQ Beef", "total": 12, "avg": 4.5, "stars": {1:0,2:1,3:2,4:3,5:6} }
     *   // OR
     *   "global": { "total": ..., "avg": ..., "stars": {...} }
     * }
     */
    public static function summary(mysqli $conn): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
            error_response('Method not allowed', 405);
        }

        $item_id = isset($_GET['item_id']) ? (int)$_GET['item_id'] : null;

        if ($item_id !== null && $item_id > 0) {
            // Ensure item exists (optional but helpful)
            $stmtI = $conn->prepare("SELECT item_id, name FROM menu_items WHERE item_id=? LIMIT 1");
            $stmtI->bind_param('i', $item_id);
            $stmtI->execute();
            $it = $stmtI->get_result()->fetch_assoc();
            $stmtI->close();
            if (!$it) error_response('Menu item not found', 404);

            $sql = "
              SELECT
                COUNT(*) AS total,
                AVG(rating) AS avg_rating,
                SUM(CASE WHEN rating=1 THEN 1 ELSE 0 END) AS s1,
                SUM(CASE WHEN rating=2 THEN 1 ELSE 0 END) AS s2,
                SUM(CASE WHEN rating=3 THEN 1 ELSE 0 END) AS s3,
                SUM(CASE WHEN rating=4 THEN 1 ELSE 0 END) AS s4,
                SUM(CASE WHEN rating=5 THEN 1 ELSE 0 END) AS s5
              FROM reviews
              WHERE item_id=?
            ";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $item_id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            $total = (int)($row['total'] ?? 0);
            $avg   = $total > 0 ? round((float)$row['avg_rating'], 2) : 0.0;

            respond([
                'scope' => 'item',
                'item' => [
                    'item_id' => (int)$it['item_id'],
                    'name'    => $it['name'],
                    'total'   => $total,
                    'avg'     => $avg,
                    'stars'   => [
                        1 => (int)($row['s1'] ?? 0),
                        2 => (int)($row['s2'] ?? 0),
                        3 => (int)($row['s3'] ?? 0),
                        4 => (int)($row['s4'] ?? 0),
                        5 => (int)($row['s5'] ?? 0),
                    ]
                ]
            ]);
        } else {
            // Global summary (all reviews, including restaurant-level: item_id IS NULL)
            $sql = "
              SELECT
                COUNT(*) AS total,
                AVG(rating) AS avg_rating,
                SUM(CASE WHEN rating=1 THEN 1 ELSE 0 END) AS s1,
                SUM(CASE WHEN rating=2 THEN 1 ELSE 0 END) AS s2,
                SUM(CASE WHEN rating=3 THEN 1 ELSE 0 END) AS s3,
                SUM(CASE WHEN rating=4 THEN 1 ELSE 0 END) AS s4,
                SUM(CASE WHEN rating=5 THEN 1 ELSE 0 END) AS s5
              FROM reviews
            ";
            $res = $conn->query($sql);
            if (!$res) error_response('DB error while computing global summary', 500);
            $row = $res->fetch_assoc() ?: [];

            $total = (int)($row['total'] ?? 0);
            $avg   = $total > 0 ? round((float)$row['avg_rating'], 2) : 0.0;

            respond([
                'scope'  => 'global',
                'global' => [
                    'total' => $total,
                    'avg'   => $avg,
                    'stars' => [
                        1 => (int)($row['s1'] ?? 0),
                        2 => (int)($row['s2'] ?? 0),
                        3 => (int)($row['s3'] ?? 0),
                        4 => (int)($row['s4'] ?? 0),
                        5 => (int)($row['s5'] ?? 0),
                    ]
                ]
            ]);
        }
    }
}
