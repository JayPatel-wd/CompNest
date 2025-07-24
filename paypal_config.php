<?php
// PayPal Configuration File - Clean Version

// Environment setting
$paypal_environment = 'sandbox'; // Change to 'production' for live payments

// Sandbox credentials (replace with your actual credentials)
$paypal_sandbox_client_id = 'AW0h3uiTeBGbC7Abf4uU7sFJEVZZUZsfih9G6nOcaKjBEGLRJ0x2vCX9H0Pp65edkqbhSpf9XefO0V5f';
$paypal_sandbox_secret = 'ENONSVIDMDvy-eihAJh3cVdHv-ZylzS7hxrJkqEfWGVSOA0vK0JmPj8T7MeXnSKBVOKclkTn5HM5uZ5E';

// Production credentials (for when you go live)
$paypal_production_client_id = 'YOUR_PRODUCTION_CLIENT_ID_HERE';
$paypal_production_secret = 'YOUR_PRODUCTION_SECRET_HERE';

// Get PayPal credentials based on environment
function getPayPalCredentials() {
    global $paypal_environment, $paypal_sandbox_client_id, $paypal_sandbox_secret;
    global $paypal_production_client_id, $paypal_production_secret;
    
    if ($paypal_environment === 'production') {
        return [
            'client_id' => $paypal_production_client_id,
            'secret' => $paypal_production_secret,
            'base_url' => 'https://api.paypal.com'
        ];
    } else {
        return [
            'client_id' => $paypal_sandbox_client_id,
            'secret' => $paypal_sandbox_secret,
            'base_url' => 'https://api.sandbox.paypal.com'
        ];
    }
}

// Get PayPal SDK URL
function getPayPalSDKUrl() {
    $credentials = getPayPalCredentials();
    return "https://www.paypal.com/sdk/js?client-id=" . $credentials['client_id'] . "&currency=CAD&disable-funding=credit,card&enable-funding=paypal";
}

// Verify PayPal payment
function verifyPayPalPayment($payment_id) {
    $credentials = getPayPalCredentials();
    
    // Get access token
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $credentials['base_url'] . '/v1/oauth2/token');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_USERPWD, $credentials['client_id'] . ':' . $credentials['secret']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Accept-Language: en_US'
    ]);
    
    $token_response = curl_exec($ch);
    $token_data = json_decode($token_response, true);
    curl_close($ch);
    
    if (!isset($token_data['access_token'])) {
        error_log("PayPal Token Error: " . print_r($token_data, true));
        return false;
    }
    
    // Verify payment
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $credentials['base_url'] . '/v2/checkout/orders/' . $payment_id);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token_data['access_token'],
        'Accept: application/json'
    ]);
    
    $payment_response = curl_exec($ch);
    $payment_data = json_decode($payment_response, true);
    curl_close($ch);
    
    return $payment_data;
}

// Log PayPal transactions
function logPayPalTransaction($order_id, $payment_id, $status, $amount, $details = null) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO paypal_transactions (order_id, payment_id, status, amount, details, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $order_id,
            $payment_id,
            $status,
            $amount,
            json_encode($details)
        ]);
    } catch (Exception $e) {
        error_log("PayPal transaction logging failed: " . $e->getMessage());
    }
}

// Create PayPal transactions table
function createPayPalTransactionTable() {
    global $pdo;
    
    $sql = "
    CREATE TABLE IF NOT EXISTS paypal_transactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        payment_id VARCHAR(255) NOT NULL,
        status VARCHAR(50) NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        details TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_order_id (order_id),
        INDEX idx_payment_id (payment_id)
    )";
    
    try {
        $pdo->exec($sql);
        return true;
    } catch (Exception $e) {
        error_log("Failed to create PayPal transactions table: " . $e->getMessage());
        return false;
    }
}

// Initialize tables
createPayPalTransactionTable();
?>