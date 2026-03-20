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
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';

    $stmt = db()->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($pass, $user['password_hash'])) {
        $errors[] = 'Invalid email or password.';
    } else {
        $_SESSION['user'] = $user;
        redirect($_SESSION['redirect_after_login'] ?? 'index.php');
    }
}

render('login', [
    'page_title' => 'Sign In',
    'errors'     => $errors,
    'email'      => $email,
]);
