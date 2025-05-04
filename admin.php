<?php 
include('header.php');
include('config.php');

// Only admin can access
if(!isset($_SESSION['user_id']) || !isAdmin()) {
    redirect('login.php');
}

// Handle deposit approval
if(isset($_POST['approve_deposit'])) {
    $transaction_id = intval($_POST['transaction_id']);
    $user_id = intval($_POST['user_id']);
    $amount = floatval($_POST['amount']);
    
    mysqli_begin_transaction($conn);
    
    try {
        // Update transaction status
        mysqli_query($conn, "UPDATE transactions SET status='completed' WHERE id=$transaction_id");
        
        // Add balance to user
        mysqli_query($conn, "UPDATE users SET balance = balance + $amount WHERE id=$user_id");
        
        mysqli_commit($conn);
        
        $_SESSION['message'] = "Deposit approved successfully!";
        $_SESSION['message_type'] = "success";
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $_SESSION['message'] = "Error approving deposit: " . $e->getMessage();
        $_SESSION['message_type'] = "danger";
    }
}

// Handle deposit rejection
if(isset($_POST['reject_deposit'])) {
    $transaction_id = intval($_POST['transaction_id']);
    
    mysqli_query($conn, "UPDATE transactions SET status='failed' WHERE id=$transaction_id");
    
    $_SESSION['message'] = "Deposit rejected!";
    $_SESSION['message_type'] = "success";
}

// Get pending deposits
$pending_deposits = mysqli_query($conn, "SELECT t.*, u.username 
                                       FROM transactions t 
                                       JOIN users u ON t.user_id = u.id 
                                       WHERE t.type='deposit' AND t.status='pending' 
                                       ORDER BY t.created_at DESC");

// Get all users
$users = mysqli_query($conn, "SELECT id, username, email, phone, balance, created_at FROM users ORDER BY id DESC");
?>

<div class="container">
    <h2 class="mb-4">Admin Dashboard</h2>
    
    <ul class="nav nav-tabs" id="adminTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="deposits-tab" data-bs-toggle="tab" data-bs-target="#deposits" type="button">Pending Deposits</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="users-tab" data-bs-toggle="tab" data-bs-target="#users" type="button">User Management</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="offers-tab" data-bs-toggle="tab" data-bs-target="#offers" type="button">Offer Management</button>
        </li>
    </ul>
    
    <div class="tab-content" id="adminTabsContent">
        <!-- Pending Deposits Tab -->
        <div class="tab-pane fade show active" id="deposits" role="tabpanel">
            <div class="card shadow mt-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Pending Deposit Requests</h5>
                </div>
                <div class="card-body">
                    <?php if(mysqli_num_rows($pending_deposits) > 0): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>User</th>
                                        <th>Amount</th>
                                        <th>Method</th>
                                        <th>Details</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($deposit = mysqli_fetch_assoc($pending_deposits)): ?>
                                        <tr>
                                            <td><?php echo date('d M Y h:i A', strtotime($deposit['created_at'])); ?></td>
                                            <td><?php echo $deposit['username']; ?></td>
                                            <td>৳<?php echo number_format($deposit['amount'], 2); ?></td>
                                            <td><?php echo $deposit['method']; ?></td>
                                            <td><?php echo $deposit['details']; ?></td>
                                            <td>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="transaction_id" value="<?php echo $deposit['id']; ?>">
                                                    <input type="hidden" name="user_id" value="<?php echo $deposit['user_id']; ?>">
                                                    <input type="hidden" name="amount" value="<?php echo $deposit['amount']; ?>">
                                                    <button type="submit" name="approve_deposit" class="btn btn-sm btn-success">Approve</button>
                                                </form>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="transaction_id" value="<?php echo $deposit['id']; ?>">
                                                    <button type="submit" name="reject_deposit" class="btn btn-sm btn-danger">Reject</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">No pending deposits found.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- User Management Tab -->
        <div class="tab-pane fade" id="users" role="tabpanel">
            <div class="card shadow mt-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">All Users</h5>
                </div>
                <div class="card-body">
                    <?php if(mysqli_num_rows($users) > 0): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Balance</th>
                                        <th>Joined</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($user = mysqli_fetch_assoc($users)): ?>
                                        <tr>
                                            <td><?php echo $user['id']; ?></td>
                                            <td><?php echo $user['username']; ?></td>
                                            <td><?php echo $user['email']; ?></td>
                                            <td><?php echo $user['phone']; ?></td>
                                            <td>৳<?php echo number_format($user['balance'], 2); ?></td>
                                            <td><?php echo date('d M Y', strtotime($user['created_at'])); ?></td>
                                            <td>
                                                <a href="admin_edit_user.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">No users found.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Offer Management Tab -->
        <div class="tab-pane fade" id="offers" role="tabpanel">
            <div class="card shadow mt-4">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Manage Offers</h5>
                        <a href="admin_add_offer.php" class="btn btn-light btn-sm">
                            <i class="fas fa-plus"></i> Add New Offer
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <?php
                    $operators = mysqli_query($conn, "SELECT * FROM operators");
                    while($operator = mysqli_fetch_assoc($operators)): 
                        $offers = mysqli_query($conn, "SELECT * FROM offers WHERE operator_id={$operator['id']} ORDER BY price");
                        if(mysqli_num_rows($offers) > 0): ?>
                            <h6 class="mt-4"><?php echo $operator['name']; ?></h6>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Price</th>
                                            <th>Validity</th>
                                            <th>Data</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($offer = mysqli_fetch_assoc($offers)): ?>
                                            <tr>
                                                <td><?php echo $offer['name']; ?></td>
                                                <td>৳<?php echo number_format($offer['price'], 2); ?></td>
                                                <td><?php echo $offer['validity_days']; ?> Days</td>
                                                <td><?php echo $offer['data_amount']; ?></td>
                                                <td>
                                                    <a href="admin_edit_offer.php?id=<?php echo $offer['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                                                    <a href="admin_delete_offer.php?id=<?php echo $offer['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('footer.php'); ?>