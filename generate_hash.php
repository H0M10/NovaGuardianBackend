<?php
/**
 * Generar hash para password Admin123!
 */

$password = 'Admin123!';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "Password: $password\n";
echo "Hash: $hash\n";
echo "\nSQL para actualizar:\n";
echo "UPDATE administradores SET password = '$hash' WHERE email = 'admin@novaguardian.com';\n";
?>
