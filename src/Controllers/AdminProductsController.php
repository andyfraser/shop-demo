<?php
namespace App\Controllers;

use App\Core\Database;
use App\Core\Renderer;
use App\Core\Validator;
use App\Services\SecurityService;
use RuntimeException;

class AdminProductsController {
    
    private function getUploadDir() {
        return __DIR__ . '/../../public/images/';
    }
    
    public function list() {
        $db     = Database::getConnection();
        $search = trim($_GET['search'] ?? '');

        if ($search !== '') {
            $stmt = $db->prepare(
                "SELECT p.*, c.name as cat_name
                 FROM products p
                 LEFT JOIN categories c ON p.category_id = c.id
                 WHERE p.name LIKE ?
                 ORDER BY p.name ASC"
            );
            $stmt->execute(['%' . $search . '%']);
            $products = $stmt->fetchAll();
        } else {
            $products = $db->query(
                "SELECT p.*, c.name as cat_name
                 FROM products p
                 LEFT JOIN categories c ON p.category_id = c.id
                 ORDER BY p.name ASC"
            )->fetchAll();
        }

        Renderer::adminRender('products_list', [
            'page_title' => 'Products',
            'active'     => 'products',
            'products'   => $products,
            'search'     => $search,
            'flash_msg'  => flash('msg'),
        ]);
    }

    public function create() {
        Renderer::adminRender('products_form', [
            'page_title' => 'Add Product',
            'active'     => 'products',
            'is_new'     => true,
            'product'    => [
                'vat_rate' => \App\Services\SettingsService::get('default_vat_rate')
            ],
            'product_id' => 0,
            'categories' => get_category_flat(),
            'errors'     => [],
        ]);
    }

    public function edit() {
        $db = Database::getConnection();
        $product_id = (int)($_GET['id'] ?? 0);

        $stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch() ?: [];

        Renderer::adminRender('products_form', [
            'page_title' => 'Edit Product',
            'active'     => 'products',
            'is_new'     => !$product_id,
            'product'    => $product,
            'product_id' => $product_id,
            'categories' => get_category_flat(),
            'errors'     => [],
        ]);
    }

    private function handleUpload(): ?string {
        $file = $_FILES['image'] ?? null;
        if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) return null;
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Upload error code: ' . $file['error']);
        }
        if ($file['size'] > 5 * 1024 * 1024) {
            throw new RuntimeException('Image must be under 5MB.');
        }
        $mime = mime_content_type($file['tmp_name']);
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'])) {
            throw new RuntimeException('Only JPEG, PNG, GIF and WebP images are allowed.');
        }
        $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('img_', true) . '.' . strtolower($ext);
        if (!move_uploaded_file($file['tmp_name'], $this->getUploadDir() . $filename)) {
            throw new RuntimeException('Failed to save uploaded file.');
        }
        return $filename;
    }

    public function save() {
        SecurityService::verifyCsrf();
        $db = Database::getConnection();
        
        $product = [
            'name'        => trim($_POST['name'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'price'       => (float)($_POST['price'] ?? 0),
            'vat_rate'    => (float)($_POST['vat_rate'] ?? 0),
            'stock'       => (int)($_POST['stock'] ?? 0),
            'category_id' => !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null,
            'image'       => $_POST['existing_image'] ?? null,
            'active'      => isset($_POST['active']) ? 1 : 0,
            'featured'    => isset($_POST['featured']) ? 1 : 0,
        ];
        $product_id = (int)($_POST['id'] ?? 0);

        $errors = Validator::check($_POST, [
            'name'  => 'required',
            'price' => 'required|positive',
        ]);

        if (!$errors) {
            try {
                $uploaded = $this->handleUpload();
                if ($uploaded) {
                    $old = $product['image'];
                    if ($old && file_exists($this->getUploadDir() . $old)) {
                        unlink($this->getUploadDir() . $old);
                    }
                    $product['image'] = $uploaded;
                } elseif (isset($_POST['remove_image'])) {
                    $old = $product['image'];
                    if ($old && file_exists($this->getUploadDir() . $old)) {
                        unlink($this->getUploadDir() . $old);
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
                     SET name=?, slug=?, description=?, price=?, vat_rate=?, stock=?, category_id=?, image=?, active=?, featured=?
                     WHERE id=?"
                )->execute([
                    $product['name'], $slug, $product['description'], $product['price'], $product['vat_rate'],
                    $product['stock'], $product['category_id'], $product['image'],
                    $product['active'], $product['featured'], $product_id,
                ]);
                flash('msg', 'Product updated.');
            } else {
                $check = $db->prepare("SELECT id FROM products WHERE slug = ?");
                $check->execute([$slug]);
                if ($check->fetch()) $slug .= '-' . time();

                $db->prepare(
                    "INSERT INTO products (name, slug, description, price, vat_rate, stock, category_id, image, active, featured)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                )->execute([
                    $product['name'], $slug, $product['description'], $product['price'], $product['vat_rate'],
                    $product['stock'], $product['category_id'], $product['image'],
                    $product['active'], $product['featured'],
                ]);
                flash('msg', 'Product created.');
            }
            redirect('/admin/products');
        }

        Renderer::adminRender('products_form', [
            'page_title' => ($product_id ? 'Edit' : 'Add') . ' Product',
            'active'     => 'products',
            'is_new'     => !$product_id,
            'product'    => $product,
            'product_id' => $product_id,
            'categories' => get_category_flat(),
            'errors'     => $errors,
        ]);
    }

    public function delete() {
        $product_id = (int)($_GET['id'] ?? 0);
        if ($product_id) {
            Database::getConnection()->prepare("UPDATE products SET active = 0 WHERE id = ?")->execute([$product_id]);
            flash('msg', 'Product deactivated.');
        }
        redirect('/admin/products');
    }
}
