<?php
// backend/controllers/MenuController.php
// Menu items: list + (admin) create/update/delete/toggle_status + upload_image

declare(strict_types=1);

require_once __DIR__ . '/../helpers/Response.php';
require_once __DIR__ . '/../helpers/Validator.php';

class MenuController
{
    /**
     * GET ?r=menu&a=list
     * Optional filters:
     *  - status=available|unavailable
     *  - category=string
     *  - q=search (name/description LIKE)
     *  - page, limit (default 1,20; max 100)
     */
    public static function list(mysqli $conn): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
            error_response('Method not allowed', 405);
        }

        $status   = isset($_GET['status']) ? strtolower(s($_GET['status'])) : null;
        $category = isset($_GET['category']) ? s($_GET['category']) : null;
        $q        = isset($_GET['q']) ? s($_GET['q']) : null;

        [$page, $limit, $offset] = validate_pagination($_GET['page'] ?? null, $_GET['limit'] ?? null, 100);

        $where = [];
        $types = '';
        $params = [];

        if ($status !== null && is_valid_enum($status, ['available','unavailable'], true)) {
            $where[] = 'status = ?';
            $types  .= 's';
            $params[] = $status;
        }
        if ($category) {
            $where[] = 'category = ?';
            $types  .= 's';
            $params[] = $category;
        }
        if ($q) {
            $like = '%' . $q . '%';
            $where[] = '(name LIKE ? OR description LIKE ?)';
            $types  .= 'ss';
            $params[] = $like;
            $params[] = $like;
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
        $sql = "
          SELECT item_id, name, description, price, category, image, status, created_at
          FROM menu_items
          $whereSql
          ORDER BY created_at DESC, item_id DESC
          LIMIT ? OFFSET ?
        ";
        $types .= 'ii';
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $conn->prepare($sql);
        if ($types !== '') $stmt->bind_param($types, ...$params);
        if (!$stmt->execute()) error_response('DB error while fetching menu', 500);
        $res = $stmt->get_result();

        $rows = [];
        while ($r = $res->fetch_assoc()) {
            $rows[] = [
                'item_id'     => (int)$r['item_id'],
                'name'        => $r['name'],
                'description' => $r['description'],
                'price'       => (float)$r['price'],
                'category'    => $r['category'],
                'image'       => $r['image'],
                'status'      => $r['status'],
                'created_at'  => $r['created_at'],
            ];
        }
        $stmt->close();

        respond(['page'=>$page,'limit'=>$limit,'count'=>count($rows),'items'=>$rows]);
    }

    /**
     * POST ?r=menu&a=create
     * Body: { "name": "...", "description":"...", "price": 500.00, "category":"Main Course", "image":"filename.jpg", "status":"available|unavailable" }
     * Note: image optional (use upload_image to get filename first)
     */
    public static function create(mysqli $conn): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') error_response('Method not allowed', 405);
        $d = read_json();
        $missing = require_fields($d, ['name','price']);
        if ($missing) error_response("Missing field: $missing", 400);

        $name = trim((string)$d['name']);
        $description = s($d['description'] ?? '');
        $price = (float)$d['price'];
        $category = s($d['category'] ?? '');
        $image = s($d['image'] ?? '');
        $status = strtolower(s($d['status'] ?? 'available'));
        if (!is_valid_enum($status, ['available','unavailable'], true)) $status = 'available';
        if ($name === '' || $price <= 0) error_response('Invalid name/price', 400);

        $sql = "INSERT INTO menu_items (name, description, price, category, image, status) VALUES (?,?,?,?,?,?)";
        $st = $conn->prepare($sql);
        $st->bind_param('ssdsss', $name, $description, $price, $category, $image, $status);
        if (!$st->execute()) error_response('DB error while creating item', 500);
        $id = $st->insert_id;
        $st->close();

        respond(['message'=>'Menu item created','item_id'=>$id], 201);
    }

    /**
     * POST ?r=menu&a=update
     * Body: { "item_id":1, "name":"..", "description":"..", "price": 450, "category":"..", "image":"new.jpg", "status":"available" }
     */
    public static function update(mysqli $conn): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') error_response('Method not allowed', 405);
        $d = read_json();
        $missing = require_fields($d, ['item_id']);
        if ($missing) error_response("Missing field: $missing", 400);

        $id = (int)$d['item_id'];
        if (!is_positive_int($id)) error_response('Invalid item_id', 400);

        // fetch existing
        $chk = $conn->prepare("SELECT * FROM menu_items WHERE item_id=? LIMIT 1");
        $chk->bind_param('i', $id);
        $chk->execute();
        $old = $chk->get_result()->fetch_assoc();
        $chk->close();
        if (!$old) error_response('Item not found', 404);

        $name = s($d['name'] ?? $old['name']);
        $description = s($d['description'] ?? $old['description']);
        $price = $d['price'] !== null ? (float)$d['price'] : (float)$old['price'];
        $category = s($d['category'] ?? $old['category']);
        $image = s($d['image'] ?? $old['image']);
        $status = strtolower(s($d['status'] ?? $old['status']));
        if (!is_valid_enum($status, ['available','unavailable'], true)) $status = $old['status'];

        if ($name === '' || $price <= 0) error_response('Invalid name/price', 400);

        $sql = "UPDATE menu_items SET name=?, description=?, price=?, category=?, image=?, status=? WHERE item_id=?";
        $st = $conn->prepare($sql);
        $st->bind_param('ssdsssi', $name, $description, $price, $category, $image, $status, $id);
        if (!$st->execute()) error_response('DB error while updating item', 500);
        $st->close();

        respond(['message'=>'Menu item updated','item_id'=>$id]);
    }

    /**
     * POST ?r=menu&a=delete
     * Body: { "item_id": 1 }
     */
    public static function delete(mysqli $conn): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') error_response('Method not allowed', 405);
        $d = read_json();
        $missing = require_fields($d, ['item_id']);
        if ($missing) error_response("Missing field: $missing", 400);

        $id = (int)$d['item_id'];
        if (!is_positive_int($id)) error_response('Invalid item_id', 400);

        // Guard: check existence
        $chk = $conn->prepare("SELECT item_id FROM menu_items WHERE item_id=? LIMIT 1");
        $chk->bind_param('i', $id);
        $chk->execute();
        $found = $chk->get_result()->fetch_assoc();
        $chk->close();
        if (!$found) error_response('Item not found', 404);

        // Delete (this will cascade on order_items via FK if defined as ON DELETE CASCADE; here it is CASCADE for order_items.item_id)
        $del = $conn->prepare("DELETE FROM menu_items WHERE item_id=?");
        $del->bind_param('i', $id);
        if (!$del->execute()) error_response('DB error while deleting item', 500);
        $del->close();

        respond(['message'=>'Menu item deleted','item_id'=>$id]);
    }

    /**
     * POST ?r=menu&a=toggle_status
     * Body: { "item_id":1, "status":"available|unavailable" }
     */
    public static function toggle_status(mysqli $conn): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') error_response('Method not allowed', 405);
        $d = read_json();
        $missing = require_fields($d, ['item_id','status']);
        if ($missing) error_response("Missing field: $missing", 400);

        $id = (int)$d['item_id'];
        $status = strtolower(s($d['status']));
        if (!is_positive_int($id)) error_response('Invalid item_id', 400);
        if (!is_valid_enum($status, ['available','unavailable'], true)) error_response('Invalid status', 400);

        $upd = $conn->prepare("UPDATE menu_items SET status=? WHERE item_id=?");
        $upd->bind_param('si', $status, $id);
        if (!$upd->execute()) error_response('DB error while updating status', 500);
        $upd->close();

        respond(['message'=>'Status updated','item_id'=>$id,'status'=>$status]);
    }

    /**
     * POST (multipart/form-data) ?r=menu&a=upload_image
     * Field: uploadfile
     * Saves to /frontend/assets/images/
     * Returns: { filename: "saved-name.jpg", url: "/restaurant-app/frontend/assets/images/saved-name.jpg" }
     */
    public static function upload_image(mysqli $conn): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') error_response('Method not allowed', 405);

        if (!isset($_FILES['uploadfile'])) {
            error_response('No file uploaded (field: uploadfile)', 400);
        }

        $file = $_FILES['uploadfile'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            error_response('Upload error code: '.$file['error'], 400);
        }

        // Validate size (<=5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            error_response('File too large (max 5MB)', 400);
        }

        // Validate extension & mime
        $allowedExt = ['jpg','jpeg','png','gif','webp'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExt, true)) {
            error_response('Only JPG, JPEG, PNG, GIF, WEBP allowed', 400);
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        $allowedMime = ['image/jpeg','image/png','image/gif','image/webp'];
        if (!in_array($mime, $allowedMime, true)) {
            error_response('Invalid image mime type', 400);
        }

        // Destination
        $root = realpath(__DIR__ . '/../../');
        $destDir = $root . '/frontend/assets/images';
        if (!is_dir($destDir)) {
            @mkdir($destDir, 0777, true);
        }

        // Unique filename
        $base = preg_replace('/[^a-zA-Z0-9_\-]+/', '-', pathinfo($file['name'], PATHINFO_FILENAME));
        $fname = $base . '-' . date('YmdHis') . '-' . substr(sha1(uniqid('', true)), 0, 6) . '.' . $ext;
        $dest = $destDir . '/' . $fname;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            error_response('Failed to save file', 500);
        }

        // (Optional) Insert into uploadedimage table
        $sql = "INSERT INTO uploadedimage (imagename) VALUES (?)";
        $st = $conn->prepare($sql);
        $st->bind_param('s', $fname);
        $st->execute();
        $st->close();

        $url = '/restaurant-app/frontend/assets/images/' . $fname;
        respond(['message'=>'Image uploaded','filename'=>$fname,'url'=>$url], 201);
    }
}
