<?php 
include('header.php');
include('config.php');

if(!isset($_SESSION['user_id'])) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];

// Get all transactions
$transactions = mysqli_query($conn, "SELECT * FROM transactions WHERE user_id=$user_id ORDER BY created_at DESC");
?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Transaction History</h2>
        <a href="profile.php" class="btn btn-outline-primary"><i class="fas fa-arrow-left"></i> Back to Profile</a>
    </div>
    
    <div class="card shadow">
        <div class="card-body">
            <?php if(mysqli_num_rows($transactions) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Details</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($transaction = mysqli_fetch_assoc($transactions)): ?>
                                <tr>
                                    <td><?php echo date('d M Y h:i A', strtotime($transaction['created_at'])); ?></td>
                                    <td><?php echo ucfirst($transaction['type']); ?></td>
                                    <td>৳<?php echo number_format($transaction['amount'], 2); ?></td>
                                    <td><?php echo $transaction['method']; ?></td>
                                    <td><?php echo $transaction['details']; ?></td>
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
            <?php else: ?>
                <div class="alert alert-info">No transactions found.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include('footer.php'); ?>