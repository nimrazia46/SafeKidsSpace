<?php
// fix_admin.php
require_once __DIR__ . '/../includes/db.php';

$new_password = 'admin1234'; // Replace with your desired password
$hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
$admin_email = 'admin@safekids.com';

try {
    $stmt = $pdo->prepare("UPDATE users SET password = :password WHERE email = :email");
    $stmt->execute(['password' => $hashed_password, 'email' => $admin_email]);
    echo "Admin password updated successfully! Please DELETE this file (fix_admin.php) immediately for security.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>