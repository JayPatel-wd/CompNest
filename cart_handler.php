<?php
require_once 'config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$action = isset($_POST['action']) ? $_POST['action'] : '';

switch ($action) {
    case 'add_to_cart':
        $product_id = (int)$_POST['product_id'];
        $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
        
        if ($product_id <= 0 || $quantity <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid product or quantity']);
            exit;
        }
        
        // Check if product exists and has stock
        $stmt = $pdo->prepare("SELECT stock FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();
        
        if (!$product) {
            echo json_encode(['success' => false, 'message' => 'Product not found']);
            exit;
        }
        
        if ($product['stock'] < $quantity) {
            echo json_encode(['success' => false, 'message' => 'Not enough stock available']);
            exit;
        }
        
        // Check if item already in cart
        $stmt = $pdo->prepare("SELECT quantity FROM cart WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$_SESSION['user_id'], $product_id]);
        $existing = $stmt->fetch();
        
        try {
            if ($existing) {
                $new_quantity = $existing['quantity'] + $quantity;
                if ($new_quantity > $product['stock']) {
                    echo json_encode(['success' => false, 'message' => 'Total quantity would exceed available stock']);
                    exit;
                }
                
                $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
                $stmt->execute([$new_quantity, $_SESSION['user_id'], $product_id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
                $stmt->execute([$_SESSION['user_id'], $product_id, $quantity]);
            }
            
            echo json_encode(['success' => true, 'message' => 'Product added to cart successfully']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error adding product to cart']);
        }
        break;
        
    case 'update_quantity':
        $cart_id = (int)$_POST['cart_id'];
        $quantity = (int)$_POST['quantity'];
        
        if ($cart_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid cart item']);
            exit;
        }
        
        if ($quantity <= 0) {
            // Remove item if quantity is 0 or negative
            $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
            $stmt->execute([$cart_id, $_SESSION['user_id']]);
            echo json_encode(['success' => true, 'message' => 'Item removed from cart']);
            exit;
        }
        
        // Check stock availability
        $stmt = $pdo->prepare("
            SELECT p.stock 
            FROM cart c 
            JOIN products p ON c.product_id = p.id 
            WHERE c.id = ? AND c.user_id = ?
        ");
        $stmt->execute([$cart_id, $_SESSION['user_id']]);
        $stock = $stmt->fetchColumn();
        
        if (!$stock) {
            echo json_encode(['success' => false, 'message' => 'Cart item not found']);
            exit;
        }
        
        if ($quantity > $stock) {
            echo json_encode(['success' => false, 'message' => 'Not enough stock available']);
            exit;
        }
        
        try {
            $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$quantity, $cart_id, $_SESSION['user_id']]);
            echo json_encode(['success' => true, 'message' => 'Cart updated successfully']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error updating cart']);
        }
        break;
        
    case 'remove_item':
        $cart_id = (int)$_POST['cart_id'];
        
        if ($cart_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid cart item']);
            exit;
        }
        
        try {
            $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
            $stmt->execute([$cart_id, $_SESSION['user_id']]);
            echo json_encode(['success' => true, 'message' => 'Item removed from cart']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error removing item from cart']);
        }
        break;
        
    case 'clear_cart':
        try {
            $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            echo json_encode(['success' => true, 'message' => 'Cart cleared successfully']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error clearing cart']);
        }
        break;
        
    case 'get_cart_total':
        try {
            $stmt = $pdo->prepare("
                SELECT SUM(c.quantity * p.price) as total
                FROM cart c
                JOIN products p ON c.product_id = p.id
                WHERE c.user_id = ?
            ");
            $stmt->execute([$_SESSION['user_id']]);
            $total = $stmt->fetchColumn() ?: 0;
            
            echo json_encode(['success' => true, 'total' => $total, 'formatted_total' => formatPrice($total)]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error calculating cart total']);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}
?>