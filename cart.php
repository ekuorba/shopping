<?php
require_once 'config.php';
require_once 'auth.php';

class CartManager {
    private $db;
    private $auth;
    
    public function __construct() {
        $this->db = getDBConnection();
        $this->auth = new Auth();
    }

    //add item to cart//
    public function addToCart($productId, $quantity = 1) {
        $user = $this->auth->isLoggedIn();
        
        if ($user) {
            // database cart for logged in users
            return $this->addToDBCart($user['id'], $productId, $quantity);
        } else {
            // cart for guest session
            return $this->addToSessionCart($productId, $quantity);
        }
    }

    private function addToDBCart($userId, $productId, $quantity) {
        //check if product exists//
        $product = $this->getProduct($productId);
        if (!$product) {
            return ['success' => false, 'error' => 'Product not found'];
        }

        // check if item already in cart
        $stmt = $this->db->prepare("SELECT * FROM cart WHERE user_id = :user_id AND product_id = :product_id");
        $stmt->execute([':user_id' => $userId, ':product_id' => $productId]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            $newQuantity = $existing['quantity'] + $quantity;
            $uStmt = $this->db->prepare("UPDATE cart SET quantity = :quantity WHERE id = :id");
            $uStmt->execute([':quantity' => $newQuantity, ':id' => $existing['id']]);
        } else {
            $iStmt = $this->db->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (:user_id, :product_id, :quantity)");
            $iStmt->execute([':user_id' => $userId, ':product_id' => $productId, ':quantity' => $quantity]);
        }

        return ['success' => true, 'cart_count' => $this->getCartCount($userId)];
}

private function addToSessionCart($productId, $quantity) {
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    if (isset($_SESSION['cart'][$productId])) {
        $_SESSION['cart'][$productId] += $quantity;
    } else {
        $_SESSION['cart'][$productId] = $quantity;
    }

    return ['success' => true, 'cart_count' => $this->getSessionCartCount()];
}

//get cart items//
public function getCart() {
    $user = $this->auth->isLoggedIn();

    if ($user) {
        return $this->getDBCart($user['id']);
    } else {
        return $this->getSessionCart();
    }
}

    private function getDBCart($userId) {
        $stmt = $this->db->prepare("SELECT c.*, p.name, p.price, p.image FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = :user_id");
        $stmt->execute([':user_id' => $userId]);

        $items = [];
        $total = 0;

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            $itemTotal = $row['price'] * $row['quantity'];
            $row['item_total'] = $itemTotal;
            $items[] = $row;
            $total += $itemTotal;
        }

        return ['items' => $items, 'total' => $total];
}

    private function getSessionCart() {
    if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
        return ['items' => [], 'total' => 0];
    }
        $productIds = array_keys($_SESSION['cart']);
        $placeholders = implode(',', array_fill(0, count($productIds), '?'));

        $stmt = $this->db->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
        $stmt->execute($productIds);

        $items = [];
        $total = 0;

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            $quantity = $_SESSION['cart'][$row['id']];
            $itemTotal = $row['price'] * $quantity;
            $row['quantity'] = $quantity;
            $row['item_total'] = $itemTotal;
            $items[] = $row;
            $total += $itemTotal;
        }

        return ['items' => $items, 'total' => $total];
    }

    //update cart quantity//
    public function updateCartQuantity($productId, $quantity) {
        $user = $this->auth->isLoggedIn();

        if ($user) {
            if ($quantity <= 0) {
                $stmt = $this->db->prepare("DELETE FROM cart WHERE user_id = :user_id AND product_id = :product_id");
                $stmt->execute([':user_id' => $user['id'], ':product_id' => $productId]);
            } else {
                $stmt = $this->db->prepare("UPDATE cart SET quantity = :quantity WHERE user_id = :user_id AND product_id = :product_id");
                $stmt->execute([':quantity' => $quantity, ':user_id' => $user['id'], ':product_id' => $productId]);
            }
        } else {
            if ($quantity <= 0) {
                unset($_SESSION['cart'] [$productId]);
            } else {
                $_SESSION['cart'] [$productId] = $quantity;
            }
        }

        return ['success' => true];
    }

    //clear cart//
    public function clearCart() {
        $user = $this->auth->isLoggedIn();

        if ($user) {
            $stmt = $this->db->prepare("DELETE FROM cart WHERE user_id = :user_id");
            $stmt->bindValue(':user_id', $user['id']);
            $stmt->execute();
        } else {
            $_SESSION['cart'] = [];
        }

        return ['success' => true];
    }

    //get cart count//
    private function getCartCount($userId) {
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM cart WHERE user_id = :user_id");
        $stmt->execute([':user_id' => $userId]);
        return (int)$stmt->fetchColumn();
    }
    
    private function getSessionCartCount() {
        if (!isset($_SESSION['cart'])) {
            return 0;
            }
            return array_sum($_SESSION['cart']);
        }

        private function getProduct($productId) {
            $stmt = $this->db->prepare("SELECT * FROM products WHERE id = :id");
            $stmt->execute([':id' => $productId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: false;
        }
    }
?>