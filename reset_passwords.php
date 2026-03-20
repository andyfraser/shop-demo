<?php
// reset_passwords.php — run once to fix password hashes, then delete
require_once __DIR__ . '/bootstrap.php';

$hash = password_hash('password', PASSWORD_DEFAULT);

db()->prepare("UPDATE users SET password_hash = ? WHERE email = 'admin@shop.local'")
   ->execute([$hash]);
db()->prepare("UPDATE users SET password_hash = ? WHERE email = 'jane@example.com'")
   ->execute([$hash]);

echo "<pre>";
echo "Passwords reset. Verifying...\n\n";
$users = db()->query("SELECT id, name, email, role, password_hash FROM users")->fetchAll();
foreach ($users as $u) {
    $ok = password_verify('password', $u['password_hash']) ? 'OK ✓' : 'FAIL ✗';
    echo "  [{$u['id']}] {$u['email']} ({$u['role']}) — {$ok}\n";
}
echo "\nDone. You can now delete this file and log in with password: password";
echo "</pre>";
