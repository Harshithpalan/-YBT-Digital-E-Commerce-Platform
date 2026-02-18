<?php
session_start();
require_once 'database.php';

function redirect($url) {
    header("Location: $url");
    exit();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function flash($name, $message = '', $class = 'alert alert-success alert-dismissible fade show') {
    if (!empty($name)) {
        if (!empty($message)) {
            $_SESSION[$name] = $message;
            $_SESSION[$name . '_class'] = $class;
        } elseif (empty($message) && !empty($_SESSION[$name])) {
            $class = !empty($_SESSION[$name . '_class']) ? $_SESSION[$name . '_class'] : '';
            echo '<div class="' . $class . '" role="alert">' . 
                 $_SESSION[$name] . 
                 '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
            unset($_SESSION[$name]);
            unset($_SESSION[$name . '_class']);
        }
    }
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function passwordHash($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function passwordVerify($password, $hash) {
    return password_verify($password, $hash);
}

function renderStars($rating) {
    $full = floor($rating);
    $half = ($rating - $full) >= 0.5 ? 1 : 0;
    $empty = 5 - ($full + $half);
    $out = '<div class="rating-stars">';
    for($i=0; $i<$full; $i++) $out .= '<i class="fas fa-star"></i>';
    if($half) $out .= '<i class="fas fa-star-half-alt"></i>';
    for($i=0; $i<$empty; $i++) $out .= '<i class="far fa-star"></i>';
    return $out . '</div>';
}
?>
