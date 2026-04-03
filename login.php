<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/render.php';

session();
if (current_user()) {
    redirect('index.php');
}

$errors = [];
$email  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    check_rate_limit('login', $_SERVER['REMOTE_ADDR'], 5, 900);

    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';

    $stmt = db()->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($pass, $user['password_hash'])) {
        record_rate_limit('login', $_SERVER['REMOTE_ADDR']);
        $errors[] = 'Invalid email or password.';
    } else {
        clear_rate_limit('login', $_SERVER['REMOTE_ADDR']);
        session_regenerate_id(true);
        $_SESSION['user'] = $user;
        redirect($_SESSION['redirect_after_login'] ?? 'index.php');
    }
}

render('login', [
    'page_title' => 'Sign In',
    'errors'     => $errors,
    'email'      => $email,
]);
