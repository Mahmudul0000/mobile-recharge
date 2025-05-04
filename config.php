<?php
session_start();

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'your_db_username');
define('DB_PASS', 'your_db_password');
define('DB_NAME', 'mobile_recharge');

// App Configuration
define('APP_NAME', 'Mobile Recharge');
define('APP_URL', 'http://localhost/mobile-recharge');
define('UPLOAD_DIR', 'uploads/');

// Create database connection
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if(!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Helper Functions
function isAdmin() {
    global $conn;
    if(isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        $result = mysqli_query($conn, "SELECT is_admin FROM users WHERE id=$user_id");
        $user = mysqli_fetch_assoc($result);
        return ($user['is_admin'] == 1);
    }
    return false;
}

function getUserBalance($user_id) {
    global $conn;
    $result = mysqli_query($conn, "SELECT balance FROM users WHERE id=$user_id");
    $user = mysqli_fetch_assoc($result);
    return $user['balance'];
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function sanitize($data) {
    global $conn;
    return mysqli_real_escape_string($conn, htmlspecialchars(trim($data)));
}

// Create uploads directory if not exists
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0777, true);
}
?>