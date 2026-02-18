<?php
$host = 'localhost';
$db   = 'ybt_digital';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    3 => 2, // PDO::ATTR_ERR_MODE => PDO::ERRMODE_EXCEPTION
    19 => 2, // PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    20 => false, // PDO::ATTR_EMULATE_PREPARES => false
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
?>
