<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/render.php';

require_admin();

$db          = db();
$action      = $_GET['action'] ?? 'list';
$category_id = (int)($_GET['id'] ?? 0);
$errors      = [];
$category    = [];

// ── Delete ────────────────────────────────────────────────────────────────────
if ($action === 'delete' && $category_id) {
    $db->prepare("UPDATE categories SET parent_id = NULL WHERE parent_id = ?")->execute([$category_id]);
    $db->prepare("DELETE FROM categories WHERE id = ?")->execute([$category_id]);
    flash('msg', 'Category deleted.');
    redirect('categories.php');
}

// ── Save ──────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    $category_id = (int)($_POST['id'] ?? 0);
    $name        = trim($_POST['name'] ?? '');
    $parent_id   = $_POST['parent_id'] ? (int)$_POST['parent_id'] : null;
    $description = trim($_POST['description'] ?? '');
    $icon        = trim($_POST['icon'] ?? '');

    if (!$name)                          $errors[] = 'Name is required.';
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
        redirect('categories.php');
    }

    // Re-populate for form re-render
    $category = [
        'id'          => $category_id,
        'name'        => $name,
        'parent_id'   => $parent_id,
        'description' => $description,
        'icon'        => $icon,
    ];
    $action = $category_id ? 'edit' : 'new';
}

// ── Load category for edit ────────────────────────────────────────────────────
if ($action === 'edit' && $category_id && !$category) {
    $stmt = $db->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$category_id]);
    $category = $stmt->fetch() ?: [];
}

$all_categories = $db->query("SELECT * FROM categories ORDER BY name")->fetchAll();

// ── Render ────────────────────────────────────────────────────────────────────
if ($action === 'list') {
    $categories = $db->query(
        "SELECT c.*, p.name as parent_name, COUNT(pr.id) as product_count
         FROM categories c
         LEFT JOIN categories p ON c.parent_id = p.id
         LEFT JOIN products pr ON pr.category_id = c.id
         GROUP BY c.id
         ORDER BY p.name, c.name"
    )->fetchAll();

    admin_render('categories_list', [
        'page_title' => 'Categories',
        'active'     => 'categories',
        'categories' => $categories,
        'flash_msg'  => flash('msg'),
    ]);
} else {
    admin_render('categories_form', [
        'page_title'     => ($action === 'new' ? 'Add' : 'Edit') . ' Category',
        'active'         => 'categories',
        'is_new'         => ($action === 'new' || !$category_id),
        'category'       => $category,
        'category_id'    => $category_id,
        'all_categories' => $all_categories,
        'errors'         => $errors,
    ]);
}
