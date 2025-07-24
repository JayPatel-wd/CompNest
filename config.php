<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'computer_store');

// Create connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Start session
session_start();

// Helper functions
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function formatPrice($price) {
    return '$' . number_format($price, 2);
}

// Database update function for PayPal integration
function updateDatabaseForPayPal() {
    global $pdo;
    
    try {
        // Check if columns already exist
        $stmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'payment_method'");
        if ($stmt->rowCount() == 0) {
            // Add PayPal-related columns to orders table
            $pdo->exec("ALTER TABLE orders 
                       ADD COLUMN payment_method VARCHAR(50) DEFAULT 'paypal',
                       ADD COLUMN payment_id VARCHAR(255),
                       ADD COLUMN billing_name VARCHAR(255),
                       ADD COLUMN billing_email VARCHAR(255)");
        }
        
        // Create PayPal transactions table if it doesn't exist
        $pdo->exec("CREATE TABLE IF NOT EXISTS paypal_transactions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            payment_id VARCHAR(255) NOT NULL,
            status VARCHAR(50) NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            details TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_order_id (order_id),
            INDEX idx_payment_id (payment_id)
        )");
        
        return true;
    } catch (Exception $e) {
        error_log("Database update failed: " . $e->getMessage());
        return false;
    }
}

// Run database updates (only runs once)
if (!defined('DATABASE_UPDATED')) {
    updateDatabaseForPayPal();
    define('DATABASE_UPDATED', true);
}