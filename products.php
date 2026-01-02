<?php
require_once 'config.php';

class ProductManager {
    private $db;

    public function __construct() {
        $this->db = getDBConnection();
    }

    //fetch all products//
    public function getAllProducts($filters) {
        $sql = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE 1=1";
        $params = [];

        if (!empty($filters['category'])) {
            $sql .= " AND c.slug = :category";
            $params[':category'] = $filters['category'];
        }

        if (!empty($filters['min_price'])) {
            $sql .= " AND p.price >= :min_price";
            $params[':min_price'] = $filters['min_price'];
        }

        if (!empty($filters['max_price'])) {
            $sql .= " AND p.price <= :max_price";
            $params[':max_price'] = $filters['max_price'];
        }

        //sorting//
        $sortOptions = [
            'name' => 'p.name ASC',
            'price-low' => 'p.price ASC',
            'price-high' => 'p.price DESC',
            'newest' => 'p.created_at DESC',
            'rating' => 'p.rating DESC'
        ];

        $sort = $filters['sort'] ?? 'newest';
        $sql .= " ORDER BY " . ($sortOptions[$sort] ?? 'p.created_at DESC');

        //pagination//
        $limit = isset($filters['limit']) ? (int)$filters['limit'] : 20;
        $page = isset($filters['page']) ? (int)$filters['page'] : 1;
        $offset = ($page - 1) * $limit;
        $sql .= " LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

//get single product//
    public function getProduct($productId) {
        $stmt = $this->db->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.id = :id");
        $stmt->execute([':id' => $productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        $rStmt = $this->db->prepare("SELECT r.*, u.username, u.profile_image
            FROM reviews r
            JOIN users u ON r.user_id = u.id
            WHERE r.product_id = :product_id
            ORDER BY r.created_at DESC");
        $rStmt->execute([':product_id' => $productId]);
        $reviews = $rStmt->fetchAll(PDO::FETCH_ASSOC);

        return ['product' => $product, 'reviews' => $reviews];
    }

    //add review//
    public function addReview($userId, $productId, $rating, $comment) {
        $stmt = $this->db->prepare("INSERT INTO reviews (user_id, product_id, rating, comment, created_at) VALUES (:user_id, :product_id, :rating, :comment, datetime('now'))");
        $ok = $stmt->execute([
            ':user_id' => $userId,
            ':product_id' => $productId,
            ':rating' => $rating,
            ':comment' => $comment
        ]);

        if ($ok) {
            $this->updateProductRating($productId);
            return true;
        }
        return false;
    }
    
    private function updateProductRating($productId) {
        $stmt = $this->db->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as count FROM reviews WHERE product_id = :product_id");
        $stmt->execute([':product_id' => $productId]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);

        $updateStmt = $this->db->prepare("UPDATE products SET rating = :rating, review_count = :count WHERE id = :id");
        $updateStmt->execute([
            ':rating' => $stats['avg_rating'] ?? 0,
            ':count' => $stats['count'] ?? 0,
            ':id' => $productId
        ]);
    }
}   
    ?>