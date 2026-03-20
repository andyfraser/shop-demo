<?php
// debug.php — delete this file after use
require_once __DIR__ . '/bootstrap.php';

session();
$user = current_user();

echo "<pre>";
echo "Session user: ";
var_dump($user);

// Test password hash
$hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
echo "\npassword_verify('password', hash): ";
var_dump(password_verify('password', $hash));

// Show all users in DB
echo "\nUsers in DB:\n";
$users = db()->query("SELECT id, name, email, role, password_hash FROM users")->fetchAll();
foreach ($users as $u) {
    echo "  [{$u['id']}] {$u['email']} ({$u['role']}) — verify 'password': ";
    var_dump(password_verify('password', $u['password_hash']));
}
echo "</pre>";
