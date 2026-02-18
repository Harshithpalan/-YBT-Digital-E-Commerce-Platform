<?php
class Cart {
    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
    }

    public function add($product) {
        $id = $product['id'];
        if (isset($_SESSION['cart'][$id])) {
            $_SESSION['cart'][$id]['qty']++;
        } else {
            $_SESSION['cart'][$id] = [
                'id' => $product['id'],
                'title' => $product['title'],
                'price' => $product['price'],
                'image' => $product['image'],
                'qty' => 1
            ];
        }
        return count($_SESSION['cart']);
    }

    public function remove($id) {
        if (isset($_SESSION['cart'][$id])) {
            unset($_SESSION['cart'][$id]);
        }
    }

    public function clear() {
        $_SESSION['cart'] = [];
        unset($_SESSION['discount']);
        unset($_SESSION['coupon_code']);
    }

    public function getItems() {
        return $_SESSION['cart'];
    }

    public function getTotal() {
        $total = 0;
        foreach ($_SESSION['cart'] as $item) {
            $total += $item['price'] * $item['qty'];
        }
        return $total;
    }
}
?>
