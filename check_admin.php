<?php
require 'config/database.php';
$db = getDBConnection();
$user = $db->query("SELECT id, email, password, role, status FROM users WHERE email = 'admin@ptoda.local'")->fetch();
echo "DB record:\n";
var_dump($user);

echo "\n--- Password verify tests ---\n";
if ($user) {
    echo "password_verify('admin123'): ";
    var_dump(password_verify('admin123', $user['password']));
    echo "password_verify('password'): ";
    var_dump(password_verify('password', $user['password']));
    echo "Stored hash: " . $user['password'] . "\n";
}
