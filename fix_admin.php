<?php
require 'config/database.php';
$db = getDBConnection();

$newHash = password_hash('admin123', PASSWORD_BCRYPT);

$stmt = $db->prepare("UPDATE users SET password = :hash WHERE email = 'admin@ptoda.local'");
$stmt->execute([':hash' => $newHash]);

echo "Rows updated: " . $stmt->rowCount() . "\n";
echo "New hash: " . $newHash . "\n";
echo "Verify: ";
var_dump(password_verify('admin123', $newHash));
