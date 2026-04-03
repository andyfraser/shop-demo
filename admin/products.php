<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/render.php';

require_admin();

$db         = db();
$action     = $_GET['action'] ?? 'list';
$product_id = (int)($_GET['id'] ?? 0);
$errors     = [];
$product    = [];

define('UPLOAD_DIR', __DIR__ . '/../public/images/');
define('ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('MAX_SIZE', 5 * 1024 * 1024); // 5MB

// ── Delete ────────────────────────────────────────────────────────────────────
if ($action === 'delete' && $product_id) {
    $db->prepare("UPDATE products SET active = 0 WHERE id = ?")->execute([$product_id]);
    flash('msg', 'Product deactivated.');
    redirect('products.php');
}

// ── Handle image upload ───────────────────────────────────────────────────────
function handle_upload(): ?string {
    $file = $_FILES['image'] ?? null;
    if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) return null;
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload error code: ' . $file['error']);
    }
    if ($file['size'] > MAX_SIZE) {
        throw new RuntimeException('Image must be under 5MB.');
    }
    $mime = mime_content_type($file['tmp_name']);
    if (!in_array($mime, ALLOWED_TYPES)) {
        throw new RuntimeException('Only JPEG, PNG, GIF and WebP images are allowed.');
    }
    $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('img_', true) . '.' . strtolower($ext);
    if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $filename)) {
        throw new RuntimeException('Failed to save uploaded file.');
    }
    return $filename;
}

// ── Save (create or update) ───────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    verify_csrf();
    $product = [
        'name'        => trim($_POST['name'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'price'       => (float)($_POST['price'] ?? 0),
        'stock'       => (int)($_POST['stock'] ?? 0),
        'category_id' => $_POST['category_id'] ? (int)$_POST['category_id'] : null,
        'image'       => $_POST['existing_image'] ?? null,
        'active'      => isset($_POST['active']) ? 1 : 0,
    ];
    $product_id = (int)($_POST['id'] ?? 0);

    if (!$product['name'])      $errors[] = 'Name is required.';
    if ($product['price'] <= 0) $errors[] = 'Price must be positive.';

    if (!$errors) {
        try {
            $uploaded = handle_upload();
            if ($uploaded) {
                $old = $product['image'];
                if ($old && file_exists(UPLOAD_DIR . $old)) {
                    unlink(UPLOAD_DIR . $old);
                }
                $product['image'] = $uploaded;
            } elseif (isset($_POST['remove_image'])) {
                $old = $product['image'];
                if ($old && file_exists(UPLOAD_DIR . $old)) {
                    unlink(UPLOAD_DIR . $old);
                }
                $product['image'] = null;
            }
        } catch (RuntimeException $e) {
            $errors[] = $e->getMessage();
        }
    }

    if (!$errors) {
        $slug = slugify($product['name']);

        if ($product_id) {
            $check = $db->prepare("SELECT id FROM products WHERE slug = ? AND id != ?");
            $check->execute([$slug, $product_id]);
            if ($check->fetch()) $slug .= '-' . $product_id;

            $db->prepare(
                "UPDATE products
                 SET name=?, slug=?, description=?, price=?, stock=?, category_id=?, image=?, active=?
                 WHERE id=?"
            )->execute([
                $product['name'], $slug, $product['description'], $product['price'],
                $product['stock'], $product['category_id'], $product['image'],
                $product['active'], $product_id,
            ]);
            flash('msg', 'Product updated.');
        } else {
            $check = $db->prepare("SELECT id FROM products WHERE slug = ?");
            $check->execute([$slug]);
            if ($check->fetch()) $slug .= '-' . time();

            $db->prepare(
                "INSERT INTO products (name, slug, description, price, stock, category_id, image, active)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            )->execute([
                $product['name'], $slug, $product['description'], $product['price'],
                $product['stock'], $product['category_id'], $product['image'],
                $product['active'],
            ]);
            flash('msg', 'Product created.');
        }
        redirect('products.php');
    }
    $action = $product_id ? 'edit' : 'new';
}

// ── Load product for edit ─────────────────────────────────────────────────────
if (($action === 'edit') && $product_id && !$product) {
    $stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch() ?: [];
}

$categories = get_category_flat();

// ── Render ────────────────────────────────────────────────────────────────────
if ($action === 'list') {
    $products = $db->query(
        "SELECT p.*, c.name as cat_name
         FROM products p
         LEFT JOIN categories c ON p.category_id = c.id
         ORDER BY p.id DESC"
    )->fetchAll();

    admin_render('products_list', [
        'page_title' => 'Products',
        'active'     => 'products',
        'products'   => $products,
        'flash_msg'  => flash('msg'),
    ]);
} else {
    admin_render('products_form', [
        'page_title' => ($action === 'new' ? 'Add' : 'Edit') . ' Product',
        'active'     => 'products',
        'is_new'     => ($action === 'new' || !$product_id),
        'product'    => $product,
        'product_id' => $product_id,
        'categories' => $categories,
        'errors'     => $errors,
    ]);
}
