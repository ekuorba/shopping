<?php
require_once 'config.php';

class Auth {
    private $db;

    public function __construct()
{
        $this->db = getDBConnection();
    }

    //user reg//
    public function register($userData) {
        $errors = [];

        if (empty($userData['username'])) {
            $errors[] = "Username is required";
        }
        if (empty($userData['email'])) {
            $errors[] = "Email is required";
        } elseif (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format";
        }

        if (empty($userData['password'])) {
            $errors[] = "Password is required";
        } elseif (strlen($userData['password']) < 8) {
            $errors[] = "Password must be at least 8 characters";
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        // check existing user
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = :email OR username = :username");
        $stmt->execute([':email' => $userData['email'], ':username' => $userData['username']]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            return ['success' => false, 'errors' => ['User already exists']];
        }

        $hashedPassword = password_hash($userData['password'], PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("INSERT INTO users (username, email, password, first_name, last_name, is_active, created_at) VALUES (:username, :email, :password, :first_name, :last_name, 1, datetime('now'))");
        $ok = $stmt->execute([
            ':username' => $userData['username'],
            ':email' => $userData['email'],
            ':password' => $hashedPassword,
            ':first_name' => $userData['first_name'] ?? '',
            ':last_name' => $userData['last_name'] ?? ''
        ]);

        if ($ok) {
            $userId = $this->db->lastInsertId();
            $this->createSession($userId);
            return ['success' => true, 'user_id' => $userId];
        }

        return ['success' => false, 'errors' => ['Registration failed']];
    }

    //login//
    public function login($email, $password) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email AND is_active = 1");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !isset($user['password'])) {
            return ['success' => false, 'error' => 'Invalid email or password'];
        }

        // standard hashed password
        if (password_verify($password, $user['password'])) {
            $uStmt = $this->db->prepare("UPDATE users SET updated_at = datetime('now') WHERE id = :id");
            $uStmt->execute([':id' => $user['id']]);
            $this->createSession($user['id']);
            return ['success' => true, 'user' => $user];
        }

        // fallback for plaintext-stored password (test/legacy) — re-hash on first use
        if ($user['password'] === $password) {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $uStmt = $this->db->prepare("UPDATE users SET password = :password, updated_at = datetime('now') WHERE id = :id");
            $uStmt->execute([':password' => $newHash, ':id' => $user['id']]);
            $this->createSession($user['id']);
            return ['success' => true, 'user' => $user];
        }

        return ['success' => false, 'error' => 'Invalid email or password'];
    }

    //user sesh//
    private function createSession($userId) {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+30 days'));

        $stmt = $this->db->prepare("INSERT INTO user_sessions (user_id, session_token, expires_at, created_at) VALUES (:user_id, :token, :expires, datetime('now'))");
        $stmt->execute([':user_id' => $userId, ':token' => $token, ':expires' => $expires]);

        setcookie('session_token', $token, time() + (30 * 24 * 60 * 60), '/');
        $_SESSION['user_id'] = $userId;

        return $token;
    }

    //to check if user is logged in//
    public function isLoggedIn() {
        if (isset($_SESSION['user_id'])) {
            return $this->getUser($_SESSION['user_id']);
        }

        if (isset($_COOKIE['session_token'])) {
            return $this->validateSession($_COOKIE['session_token']);
        }

        return false;
    }

    //session token validation//
    private function validateSession($token) {
        $stmt = $this->db->prepare("SELECT u.* FROM user_sessions s JOIN users u ON s.user_id = u.id WHERE s.session_token = :token AND s.expires_at > datetime('now') AND u.is_active = 1");
        $stmt->execute([':token' => $token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            return $user;
        }
        return false;
    }

    //get user by ID//
    public function getUser($userId) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = :id AND is_active = 1");
        $stmt->execute([':id' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    //logout//
    public function logout() {
        if (isset($_COOKIE['session_token'])) {
            $stmt = $this->db->prepare("DELETE FROM user_sessions WHERE session_token = :token");
            $stmt->execute([':token' => $_COOKIE['session_token']]);
            setcookie('session_token', '', time() - 3600, '/');
        }

        session_destroy();
        return ['success' => true];
    }

    //profile update//
    public function updateProfile($userId, $data) {
        $allowedFields = ['first_name', 'last_name', 'phone', 'address', 'city', 'region', 'zip_code', 'country'];
        $updates = [];
        $params = [':id' => $userId];

        foreach ($data as $key => $value) {
            // normalize key (replace hyphens with underscores)
            $k = str_replace('-', '_', $key);
            if (in_array($k, $allowedFields)) {
                $updates[] = "$k = :$k";
                $params[":$k"] = $value;
            }
        }

        if (!empty($updates)) {
            $updates[] = "updated_at = datetime('now')";
            $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
        }

        return false;
    }

    //password change//
    public function changePassword($userId, $currentPassword, $newPassword) {
        $user = $this->getUser($userId);

        if ($user && password_verify($currentPassword, $user['password'])) {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $this->db->prepare("UPDATE users SET password = :password WHERE id = :id");
            $stmt->bindValue(':password', $hashedPassword);
            $stmt->bindValue(':id', $userId);
            return $stmt->execute();
        }

        return false;
    }
}
?>

        