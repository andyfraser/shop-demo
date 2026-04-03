<?php
namespace App\Controllers;

use App\Core\Database;
use App\Core\Renderer;
use App\Core\Validator;
use App\Services\SecurityService;

class AdminCategoriesController {
    public function list() {
        $db = Database::getConnection();
        $categories = $db->query(
            "SELECT c.*, p.name as parent_name, COUNT(pr.id) as product_count
             FROM categories c
             LEFT JOIN categories p ON c.parent_id = p.id
             LEFT JOIN products pr ON pr.category_id = c.id
             GROUP BY c.id
             ORDER BY p.name, c.name"
        )->fetchAll();

        Renderer::adminRender('categories_list', [
            'page_title' => 'Categories',
            'active'     => 'categories',
            'categories' => $categories,
            'flash_msg'  => flash('msg'),
        ]);
    }

    public function create() {
        Renderer::adminRender('categories_form', [
            'page_title'     => 'Add Category',
            'active'         => 'categories',
            'is_new'         => true,
            'category'       => [],
            'category_id'    => 0,
            'all_categories' => Database::getConnection()->query("SELECT * FROM categories ORDER BY name")->fetchAll(),
            'errors'         => [],
        ]);
    }

    public function edit() {
        $id = (int)($_GET['id'] ?? 0);
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        $category = $stmt->fetch() ?: [];

        Renderer::adminRender('categories_form', [
            'page_title'     => 'Edit Category',
            'active'         => 'categories',
            'is_new'         => !$id,
            'category'       => $category,
            'category_id'    => $id,
            'all_categories' => $db->query("SELECT * FROM categories ORDER BY name")->fetchAll(),
            'errors'         => [],
        ]);
    }

    public function save() {
        SecurityService::verifyCsrf();
        $db          = Database::getConnection();
        $category_id = (int)($_POST['id'] ?? 0);
        $name        = trim($_POST['name'] ?? '');
        $parent_id   = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
        $description = trim($_POST['description'] ?? '');
        $icon        = trim($_POST['icon'] ?? '');
        $errors = Validator::check($_POST, [
            'name' => 'required',
        ]);
        if ($parent_id && $parent_id === $category_id) $errors[] = 'A category cannot be its own parent.';

        if (!$errors) {
            $slug = slugify($name);

            if ($category_id) {
                $check = $db->prepare("SELECT id FROM categories WHERE slug = ? AND id != ?");
                $check->execute([$slug, $category_id]);
                if ($check->fetch()) $slug .= '-' . $category_id;

                $db->prepare("UPDATE categories SET name=?, slug=?, parent_id=?, description=?, icon=? WHERE id=?")
                   ->execute([$name, $slug, $parent_id, $description, $icon, $category_id]);
                flash('msg', 'Category updated.');
            } else {
                $check = $db->prepare("SELECT id FROM categories WHERE slug = ?");
                $check->execute([$slug]);
                if ($check->fetch()) $slug .= '-' . time();

                $db->prepare("INSERT INTO categories (name, slug, parent_id, description, icon) VALUES (?, ?, ?, ?, ?)")
                   ->execute([$name, $slug, $parent_id, $description, $icon]);
                flash('msg', 'Category created.');
            }
            redirect('/admin/categories');
        }

        Renderer::adminRender('categories_form', [
            'page_title'     => ($category_id ? 'Edit' : 'Add') . ' Category',
            'active'         => 'categories',
            'is_new'         => !$category_id,
            'category'       => [
                'id'          => $category_id,
                'name'        => $name,
                'parent_id'   => $parent_id,
                'description' => $description,
                'icon'        => $icon,
            ],
            'category_id'    => $category_id,
            'all_categories' => $db->query("SELECT * FROM categories ORDER BY name")->fetchAll(),
            'errors'         => $errors,
        ]);
    }

    public function delete() {
        $category_id = (int)($_GET['id'] ?? 0);
        if ($category_id) {
            $db = Database::getConnection();
            $db->prepare("UPDATE categories SET parent_id = NULL WHERE parent_id = ?")->execute([$category_id]);
            $db->prepare("DELETE FROM categories WHERE id = ?")->execute([$category_id]);
            flash('msg', 'Category deleted.');
        }
        redirect('/admin/categories');
    }
}
