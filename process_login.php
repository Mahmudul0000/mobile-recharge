<?php
include('config.php');

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    
    // Check if user exists
    $sql = "SELECT * FROM users WHERE username='$username' OR email='$username'";
    $result = mysqli_query($conn, $sql);
    
    if(mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        
        if(password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['profile_pic'] = $user['profile_pic'];
            $_SESSION['is_admin'] = $user['is_admin'];
            
            // Set remember me cookie if checked
            if(isset($_POST['remember'])) {
                $cookie_value = base64_encode($user['id'] . ':' . $user['username']);
                setcookie('remember_me', $cookie_value, time() + (86400 * 30), "/"); // 30 days
            }
            
            $_SESSION['message'] = "Login successful!";
            $_SESSION['message_type'] = "success";
            
            if($user['is_admin'] == 1) {
                redirect('admin.php');
            } else {
                redirect('profile.php');
            }
        } else {
            $_SESSION['message'] = "Invalid password!";
            $_SESSION['message_type'] = "danger";
            redirect('login.php');
        }
    } else {
        $_SESSION['message'] = "User not found!";
        $_SESSION['message_type'] = "danger";
        redirect('login.php');
    }
} else {
    redirect('login.php');
}
?>