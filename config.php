<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'ecommerce.db');
define('DB_TYPE', 'sqlite');

define('DB_CHARSET', 'utf8');
define('DB_USER', 'root');
define('DB_PASS', '');

//for mysql//

/*define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_HOST', 'localhost');
define('DB_NAME', 'ecommerce');
define('DB_TYPE', 'mysql');
define('DB_CHARSET', 'utf8');*/

//site config//
define('SITE_NAME', 'ShopEasy');
define('SITE_URL', 'http://localhost/ecommerce');
define('SECRET_KEY', 'your-secret-key-here-change-in-production');

//sesh config//
session_start();

//error report//
error_reporting(E_ALL);
ini_set('display_errors', 1);

//database connect function (returns PDO for sqlite and mysql)
function getDBConnection() {
    if (DB_TYPE === 'sqlite') {
        $dbPath = __DIR__ . DIRECTORY_SEPARATOR . DB_NAME;
        $dsn = 'sqlite:' . $dbPath;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        return new PDO($dsn, null, null, $options);
    } elseif (DB_TYPE === 'mysql') {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        return new PDO($dsn, DB_USER, DB_PASS, $options);
    }
}

//create tables//
function initializeDatabase() {
    try {
        $db = getDBConnection();

        // read and execute schema
        $schemaPath = __DIR__ . DIRECTORY_SEPARATOR . 'schema.sql';
        $schema = @file_get_contents($schemaPath);
        if ($schema !== false) {
            $db->exec($schema);
        }

        // seed sample data if products empty
        $count = 0;
        try {
            $stmt = $db->query("SELECT COUNT(*) as count FROM products");
            $count = $stmt ? (int)$stmt->fetchColumn() : 0;
        } catch (Exception $e) {
            $count = 0;
        }

        if ($count === 0) {
            $samplePath = __DIR__ . DIRECTORY_SEPARATOR . 'sample_data.sql';
            $sampleData = @file_get_contents($samplePath);
            if ($sampleData !== false) {
                $db->exec($sampleData);
            }
        }

        // ensure at least one user exists (create test user)
        $userCount = 0;
        try {
            $stmt = $db->query("SELECT COUNT(*) as count FROM users");
            $userCount = $stmt ? (int)$stmt->fetchColumn() : 0;
        } catch (Exception $e) {
            $userCount = 0;
        }

        if ($userCount === 0) {
            $passwordHash = password_hash('password123', PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (username, email, password, first_name, last_name, is_active, created_at) VALUES (:username, :email, :password, :first_name, :last_name, 1, datetime('now'))");
            $stmt->execute([
                ':username' => 'testuser',
                ':email' => 'test@example.com',
                ':password' => $passwordHash,
                ':first_name' => 'Test',
                ':last_name' => 'User'
            ]);
        }

        return true;
    } catch (Exception $e) {
        error_log("Database initialization failed: " . $e->getMessage());
        return false;
    }
}

initializeDatabase();
?>
