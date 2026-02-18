<?php
require_once '../config/config.php';
require_once '../classes/AdminAuth.php';

$auth = new AdminAuth($pdo);
$auth->logout();
header("Location: login.php");
exit();
?>
