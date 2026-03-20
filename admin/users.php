<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/render.php';

require_admin();

$db      = db();
$action  = $_GET['action'] ?? 'list';
$user_id = (int)($_GET['id'] ?? 0);
$errors  = [];
$user    = [];

// ── Delete ────────────────────────────────────────────────────────────────────
if ($action === 'delete' && $user_id) {
    if ($user_id === (int)current_user()['id']) {
        flash('err', 'You cannot delete your own account.');
    } else {
        $db->prepare("DELETE FROM users WHERE id = ?")->execute([$user_id]);
        flash('msg', 'User deleted.');
    }
    redirect('users.php');
}

// ── Save ──────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    $user_id = (int)($_POST['id'] ?? 0);
    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $role    = in_array($_POST['role'], ['admin', 'customer']) ? $_POST['role'] : 'customer';
    $pass    = $_POST['password'] ?? '';

    if (!$name)                                     $errors[] = 'Name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email required.';
    if (!$user_id && strlen($pass) < 6)             $errors[] = 'Password must be at least 6 characters.';
    if ($pass && strlen($pass) < 6)                 $errors[] = 'Password must be at least 6 characters.';

    if (!$errors) {
        $check = $user_id
            ? $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?")
            : $db->prepare("SELECT id FROM users WHERE email = ?");
        $user_id ? $check->execute([$email, $user_id]) : $check->execute([$email]);

        if ($check->fetch()) {
            $errors[] = 'Email already in use.';
        }
    }

    if (!$errors) {
        if ($user_id) {
            if ($pass) {
                $db->prepare("UPDATE users SET name=?, email=?, role=?, password_hash=? WHERE id=?")
                   ->execute([$name, $email, $role, password_hash($pass, PASSWORD_DEFAULT), $user_id]);
            } else {
                $db->prepare("UPDATE users SET name=?, email=?, role=? WHERE id=?")
                   ->execute([$name, $email, $role, $user_id]);
            }
            flash('msg', 'User updated.');
        } else {
            $db->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)")
               ->execute([$name, $email, password_hash($pass, PASSWORD_DEFAULT), $role]);
            flash('msg', 'User created.');
        }
        redirect('users.php');
    }

    // Re-populate for form re-render
    $user   = compact('name', 'email', 'role');
    $action = $user_id ? 'edit' : 'new';
}

// ── Load user for edit ────────────────────────────────────────────────────────
if ($action === 'edit' && $user_id && !$user) {
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch() ?: [];
}

// ── Render ────────────────────────────────────────────────────────────────────
if ($action === 'list') {
    $users = $db->query(
        "SELECT u.*, COUNT(o.id) as order_count
         FROM users u
         LEFT JOIN orders o ON o.user_id = u.id
         GROUP BY u.id
         ORDER BY u.id DESC"
    )->fetchAll();

    admin_render('users_list', [
        'page_title' => 'Users',
        'active'     => 'users',
        'users'      => $users,
        'flash_msg'  => flash('msg'),
        'flash_err'  => flash('err'),
    ]);
} else {
    admin_render('users_form', [
        'page_title' => ($action === 'new' ? 'Add' : 'Edit') . ' User',
        'active'     => 'users',
        'is_new'     => ($action === 'new' || !$user_id),
        'user'       => $user,
        'user_id'    => $user_id,
        'errors'     => $errors,
    ]);
}
