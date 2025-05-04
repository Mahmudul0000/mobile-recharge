<?php
include('config.php');

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize inputs
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate inputs
    $errors = [];
    
    if($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    if(strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters";
    }
    
    // Check if username or email already exists
    $check_user = mysqli_query($conn, "SELECT * FROM users WHERE username='$username' OR email='$email'");
    if(mysqli_num_rows($check_user) > 0) {
        $errors[] = "Username or Email already exists";
    }
    
    if(empty($errors)) {
        // Handle profile picture upload
        $profile_pic = 'default.jpg';
        if(isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $file_type = $_FILES['profile_pic']['type'];
            
            if(in_array($file_type, $allowed_types)) {
                $extension = pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION);
                $profile_pic = 'user_' . time() . '.' . $extension;
                move_uploaded_file($_FILES['profile_pic']['tmp_name'], UPLOAD_DIR . $profile_pic);
            }
        }
        
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        
        // Insert user into database
        $sql = "INSERT INTO users (username, email, phone, password, profile_pic) 
                VALUES ('$username', '$email', '$phone', '$hashed_password', '$profile_pic')";
        
        if(mysqli_query($conn, $sql)) {
            $_SESSION['message'] = "Registration successful! Please login.";
            $_SESSION['message_type'] = "success";
            redirect('login.php');
        } else {
            $_SESSION['message'] = "Error: " . mysqli_error($conn);
            $_SESSION['message_type'] = "danger";
            redirect('register.php');
        }
    } else {
        $_SESSION['message'] = implode("<br>", $errors);
        $_SESSION['message_type'] = "danger";
        redirect('register.php');
    }
} else {
    redirect('register.php');
}
?>