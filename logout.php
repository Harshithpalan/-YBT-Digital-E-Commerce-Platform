<?php
require_once 'config/config.php';
require_once 'classes/Auth.php';
$auth = new Auth($pdo);
$auth->logout();
header("Location: login.php");
exit;
?>
