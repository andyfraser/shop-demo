<?php
namespace App\Controllers;

use App\Core\Database;
use App\Core\Renderer;
use App\Core\Validator;
use App\Services\SecurityService;
use App\Services\SettingsService;
use RuntimeException;

class AdminProductsController {
    private \PDO $db;
    private Renderer $renderer;
    private Validator $validator;
    private SecurityService $security;
    private SettingsService $settings;

    public function __construct(\PDO $db, Renderer $renderer, Validator $validator, SecurityService $security, SettingsService $settings) {
        $this->db = $db;
        $this->renderer = $renderer;
        $this->validator = $validator;
        $this->security = $security;
        $this->settings = $settings;
    }
    
    private function getUploadDir() {
        return __DIR__ . '/../../public/images/';
    }
    
    public function list() {
        $search = trim($_GET['search'] ?? '');

        if ($search !== '') {
            $stmt = $this->db->prepare(
                "SELECT p.*, c.name as cat_name
                 FROM products p
                 LEFT JOIN categories c ON p.category_id = c.id
                 WHERE p.name LIKE ?
                 ORDER BY p.name ASC"
            );
            $stmt->execute(['%' . $search . '%']);
            $products = $stmt->fetchAll();
        } else {
            $products = $this->db->query(
                "SELECT p.*, c.name as cat_name
                 FROM products p
                 LEFT JOIN categories c ON p.category_id = c.id
                 ORDER BY p.name ASC"
            )->fetchAll();
        }

        $this->renderer->adminRender('products_list', [
            'page_title' => 'Products',
            'active'     => 'products',
            'products'   => $products,
            'search'     => $search,
            'flash_msg'  => flash('msg'),
        ]);
    }

    public function create() {
        $this->renderer->adminRender('products_form', [
            'page_title' => 'Add Product',
            'active'     => 'products',
            'is_new'     => true,
            'product'    => [
                'vat_rate' => $this->settings->get('default_vat_rate')
            ],
            'product_id' => 0,
            'categories' => get_category_flat(),
            'errors'     => [],
        ]);
    }

    public function edit() {
        $product_id = (int)($_GET['id'] ?? 0);

        $stmt = $this->db->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch() ?: [];

        $this->renderer->adminRender('products_form', [
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
        $this->security->verifyCsrf();
        
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

        $errors = $this->validator->check($_POST, [
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
                $check = $this->db->prepare("SELECT id FROM products WHERE slug = ? AND id != ?");
                $check->execute([$slug, $product_id]);
                if ($check->fetch()) $slug .= '-' . $product_id;

                $this->db->prepare(
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
                $check = $this->db->prepare("SELECT id FROM products WHERE slug = ?");
                $check->execute([$slug]);
                if ($check->fetch()) $slug .= '-' . time();

                $this->db->prepare(
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

        $this->renderer->adminRender('products_form', [
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
            $this->db->prepare("UPDATE products SET active = 0 WHERE id = ?")->execute([$product_id]);
            flash('msg', 'Product deactivated.');
        }
        redirect('/admin/products');
    }
}
