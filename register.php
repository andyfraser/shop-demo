<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/render.php';

session();
if (current_user()) {
    redirect('index.php');
}

$errors = [];
$name   = '';
$email  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    check_rate_limit('register', $_SERVER['REMOTE_ADDR'], 10, 3600);

    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    $pass2 = $_POST['password2'] ?? '';

    if (!$name)                                      $errors[] = 'Name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))  $errors[] = 'Valid email required.';
    if (strlen($pass) < 6)                           $errors[] = 'Password must be at least 6 characters.';
    if ($pass !== $pass2)                            $errors[] = 'Passwords do not match.';

    if (!$errors) {
        $stmt = db()->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'This email is already registered.';
        } else {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            db()->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, 'customer')")
               ->execute([$name, $email, $hash]);

            $stmt = db()->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            session_regenerate_id(true);
            $_SESSION['user'] = $stmt->fetch();
            redirect('index.php');
        }
    }
    
    if ($errors) {
        record_rate_limit('register', $_SERVER['REMOTE_ADDR']);
    }
}

render('register', [
    'page_title' => 'Create Account',
    'errors'     => $errors,
    'name'       => $name,
    'email'      => $email,
]);
