<?php 
include('header.php');
include('config.php');

if(!isset($_SESSION['user_id'])) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id=$user_id"));

// Handle profile update
if(isset($_POST['update_profile'])) {
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    
    // Check if username or email already exists for other users
    $check = mysqli_query($conn, "SELECT * FROM users WHERE (username='$username' OR email='$email') AND id!=$user_id");
    if(mysqli_num_rows($check) > 0) {
        $_SESSION['message'] = "Username or Email already exists!";
        $_SESSION['message_type'] = "danger";
    } else {
        // Handle profile picture upload
        $profile_pic = $user['profile_pic'];
        if(isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $file_type = $_FILES['profile_pic']['type'];
            
            if(in_array($file_type, $allowed_types)) {
                // Delete old profile pic if not default
                if($profile_pic != 'default.jpg') {
                    @unlink(UPLOAD_DIR . $profile_pic);
                }
                
                $extension = pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION);
                $profile_pic = 'user_' . time() . '.' . $extension;
                move_uploaded_file($_FILES['profile_pic']['tmp_name'], UPLOAD_DIR . $profile_pic);
                $_SESSION['profile_pic'] = $profile_pic;
            }
        }
        
        $sql = "UPDATE users SET username='$username', email='$email', phone='$phone', profile_pic='$profile_pic' WHERE id=$user_id";
        if(mysqli_query($conn, $sql)) {
            $_SESSION['username'] = $username;
            $_SESSION['email'] = $email;
            $_SESSION['profile_pic'] = $profile_pic;
            
            $_SESSION['message'] = "Profile updated successfully!";
            $_SESSION['message_type'] = "success";
            redirect('profile.php');
        } else {
            $_SESSION['message'] = "Error updating profile: " . mysqli_error($conn);
            $_SESSION['message_type'] = "danger";
        }
    }
}

// Handle password change
if(isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if(password_verify($current_password, $user['password'])) {
        if($new_password === $confirm_password) {
            if(strlen($new_password) >= 6) {
                $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
                mysqli_query($conn, "UPDATE users SET password='$hashed_password' WHERE id=$user_id");
                
                $_SESSION['message'] = "Password changed successfully!";
                $_SESSION['message_type'] = "success";
                redirect('profile.php');
            } else {
                $_SESSION['message'] = "Password must be at least 6 characters!";
                $_SESSION['message_type'] = "danger";
            }
        } else {
            $_SESSION['message'] = "New passwords do not match!";
            $_SESSION['message_type'] = "danger";
        }
    } else {
        $_SESSION['message'] = "Current password is incorrect!";
        $_SESSION['message_type'] = "danger";
    }
}
?>

<div class="container">
    <div class="row">
        <div class="col-md-4">
            <div class="card shadow mb-4">
                <div class="card-body text-center">
                    <img src="<?php echo UPLOAD_DIR . $user['profile_pic']; ?>" alt="Profile Picture" class="rounded-circle mb-3" width="150">
                    <h4><?php echo $user['username']; ?></h4>
                    <p class="text-muted">Member since <?php echo date('M Y', strtotime($user['created_at'])); ?></p>
                    <div class="d-grid gap-2">
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#profileModal">
                            <i class="fas fa-edit"></i> Edit Profile
                        </button>
                        <button class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#passwordModal">
                            <i class="fas fa-key"></i> Change Password
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-wallet"></i> Balance</h5>
                </div>
                <div class="card-body">
                    <h2 class="text-center">৳<?php echo number_format($user['balance'], 2); ?></h2>
                    <div class="d-grid gap-2 mt-3">
                        <a href="deposit.php" class="btn btn-success"><i class="fas fa-plus-circle"></i> Add Money</a>
                        <a href="withdraw.php" class="btn btn-warning"><i class="fas fa-minus-circle"></i> Withdraw</a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-history"></i> Recent Transactions</h5>
                </div>
                <div class="card-body">
                    <?php
                    $transactions = mysqli_query($conn, "SELECT * FROM transactions WHERE user_id=$user_id ORDER BY created_at DESC LIMIT 5");
                    if(mysqli_num_rows($transactions) > 0): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Type</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($transaction = mysqli_fetch_assoc($transactions)): ?>
                                        <tr>
                                            <td><?php echo date('d M Y', strtotime($transaction['created_at'])); ?></td>
                                            <td><?php echo ucfirst($transaction['type']); ?></td>
                                            <td>৳<?php echo number_format($transaction['amount'], 2); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $transaction['status'] == 'completed' ? 'success' : 
                                                         ($transaction['status'] == 'pending' ? 'warning' : 'danger'); 
                                                ?>">
                                                    <?php echo ucfirst($transaction['status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="text-center mt-3">
                            <a href="history.php" class="btn btn-outline-primary">View All Transactions</a>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">No transactions found.</div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card shadow mt-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-gift"></i> Recent Purchases</h5>
                </div>
                <div class="card-body">
                    <?php
                    $purchases = mysqli_query($conn, "SELECT p.*, o.name as offer_name, o.price 
                                                     FROM purchases p 
                                                     JOIN offers o ON p.offer_id = o.id 
                                                     WHERE p.user_id=$user_id 
                                                     ORDER BY p.purchase_date DESC LIMIT 5");
                    if(mysqli_num_rows($purchases) > 0): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Offer</th>
                                        <th>Price</th>
                                        <th>Phone</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($purchase = mysqli_fetch_assoc($purchases)): ?>
                                        <tr>
                                            <td><?php echo date('d M Y', strtotime($purchase['purchase_date'])); ?></td>
                                            <td><?php echo $purchase['offer_name']; ?></td>
                                            <td>৳<?php echo number_format($purchase['price'], 2); ?></td>
                                            <td><?php echo $purchase['phone_number']; ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="text-center mt-3">
                            <a href="purchase_history.php" class="btn btn-outline-primary">View All Purchases</a>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">No purchases found.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Profile Edit Modal -->
<div class="modal fade" id="profileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Profile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" name="username" value="<?php echo $user['username']; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" value="<?php echo $user['email']; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" class="form-control" name="phone" value="<?php echo $user['phone']; ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Profile Picture</label>
                        <input type="file" class="form-control" name="profile_pic" accept="image/*">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="update_profile" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Password Change Modal -->
<div class="modal fade" id="passwordModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Change Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <input type="password" class="form-control" name="current_password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" class="form-control" name="new_password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" name="confirm_password" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include('footer.php'); ?>