<?php
    $servername = "localhost";  	// Server name or IP address
    $username = "root";     		// MySQL username
    $password = "";     			// MySQL password
    $dbname = "computer_store";	  	// Database name

    // Create connection
    $conn = mysqli_connect($servername, $username, $password, $dbname);

    // Check connection
    if (!$conn) {
        die("Connection failed:" . mysqli_connect_error());
    }
session_start();


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
?>