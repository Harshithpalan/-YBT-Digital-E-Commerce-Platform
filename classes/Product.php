<?php
class Product {
    private $db;

    public function __construct($pdo) {
        $this->db = $pdo;
    }

    public function getAllProducts($status = 'active') {
        $stmt = $this->db->prepare("
            SELECT p.*, c.name as category_name,
            (SELECT COALESCE(AVG(rating), 0) FROM product_reviews WHERE product_id = p.id AND status = 'approved') as avg_rating,
            (SELECT COUNT(*) FROM product_reviews WHERE product_id = p.id AND status = 'approved') as review_count
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE p.status = ? 
            ORDER BY p.created_at DESC
        ");
        $stmt->execute([$status]);
        return $stmt->fetchAll();
    }

    public function getProductById($id) {
        $stmt = $this->db->prepare("
            SELECT p.*, c.name as category_name,
            (SELECT COALESCE(AVG(rating), 0) FROM product_reviews WHERE product_id = p.id AND status = 'approved') as avg_rating,
            (SELECT COUNT(*) FROM product_reviews WHERE product_id = p.id AND status = 'approved') as review_count
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE p.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function getFeaturedProducts($limit = 3) {
        $stmt = $this->db->prepare("
            SELECT p.*, c.name as category_name,
            (SELECT COALESCE(AVG(rating), 0) FROM product_reviews WHERE product_id = p.id AND status = 'approved') as avg_rating,
            (SELECT COUNT(*) FROM product_reviews WHERE product_id = p.id AND status = 'approved') as review_count
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE p.status = 'active' 
            ORDER BY p.created_at DESC 
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
?>
