<?php 
include('header.php');
include('config.php');

if(!isset($_SESSION['user_id'])) {
    redirect('login.php');
}

// Get all operators with their offers
$operators = mysqli_query($conn, "SELECT o.*, 
                                 (SELECT COUNT(*) FROM offers WHERE operator_id=o.id) as offer_count 
                                 FROM operators o 
                                 ORDER BY o.name");

// Handle offer purchase
if(isset($_POST['buy_offer'])) {
    $offer_id = intval($_POST['offer_id']);
    $phone_number = sanitize($_POST['phone_number']);
    
    // Get offer details
    $offer = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM offers WHERE id=$offer_id"));
    $user_balance = getUserBalance($_SESSION['user_id']);
    
    if($user_balance >= $offer['price']) {
        // Start transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Deduct balance
            mysqli_query($conn, "UPDATE users SET balance = balance - {$offer['price']} WHERE id={$_SESSION['user_id']}");
            
            // Record transaction
            $transaction_sql = "INSERT INTO transactions (user_id, type, amount, method, details, status) 
                              VALUES ({$_SESSION['user_id']}, 'purchase', {$offer['price']}, 'wallet', 
                              'Purchased {$offer['name']} for {$phone_number}', 'completed')";
            mysqli_query($conn, $transaction_sql);
            $transaction_id = mysqli_insert_id($conn);
            
            // Record purchase
            $purchase_sql = "INSERT INTO purchases (user_id, offer_id, transaction_id, phone_number) 
                           VALUES ({$_SESSION['user_id']}, $offer_id, $transaction_id, '$phone_number')";
            mysqli_query($conn, $purchase_sql);
            
            // Commit transaction
            mysqli_commit($conn);
            
            $_SESSION['message'] = "Offer purchased successfully for $phone_number!";
            $_SESSION['message_type'] = "success";
            redirect('profile.php');
        } catch (Exception $e) {
            // Rollback transaction on error
            mysqli_rollback($conn);
            $_SESSION['message'] = "Error processing purchase: " . $e->getMessage();
            $_SESSION['message_type'] = "danger";
        }
    } else {
        $_SESSION['message'] = "Insufficient balance! Please add money to your wallet.";
        $_SESSION['message_type'] = "danger";
    }
}
?>

<div class="container">
    <h2 class="mb-4">Available Offers</h2>
    
    <div class="row">
        <?php while($operator = mysqli_fetch_assoc($operators)): ?>
            <?php if($operator['offer_count'] > 0): ?>
                <div class="col-md-12 mb-4">
                    <div class="card shadow">
                        <div class="card-header bg-primary text-white">
                            <div class="d-flex align-items-center">
                                <?php if(!empty($operator['logo'])): ?>
                                    <img src="<?php echo UPLOAD_DIR . $operator['logo']; ?>" alt="<?php echo $operator['name']; ?>" width="40" class="me-3">
                                <?php endif; ?>
                                <h4 class="mb-0"><?php echo $operator['name']; ?> Offers</h4>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <?php 
                                $offers = mysqli_query($conn, "SELECT * FROM offers WHERE operator_id={$operator['id']} ORDER BY price");
                                while($offer = mysqli_fetch_assoc($offers)): ?>
                                    <div class="col-md-4 mb-3">
                                        <div class="card h-100">
                                            <div class="card-body">
                                                <h5 class="card-title"><?php echo $offer['name']; ?></h5>
                                                <p class="card-text"><?php echo $offer['description']; ?></p>
                                                <ul class="list-group list-group-flush mb-3">
                                                    <li class="list-group-item d-flex justify-content-between">
                                                        <span>Price:</span>
                                                        <span class="fw-bold">৳<?php echo number_format($offer['price'], 2); ?></span>
                                                    </li>
                                                    <li class="list-group-item d-flex justify-content-between">
                                                        <span>Validity:</span>
                                                        <span><?php echo $offer['validity_days']; ?> Days</span>
                                                    </li>
                                                    <li class="list-group-item d-flex justify-content-between">
                                                        <span>Data:</span>
                                                        <span><?php echo $offer['data_amount']; ?></span>
                                                    </li>
                                                </ul>
                                                <button class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#buyModal<?php echo $offer['id']; ?>">
                                                    <i class="fas fa-shopping-cart"></i> Buy Now
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Buy Modal -->
                                    <div class="modal fade" id="buyModal<?php echo $offer['id']; ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Buy <?php echo $offer['name']; ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <form method="POST">
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="form-label">Your <?php echo $operator['name']; ?> Number</label>
                                                            <input type="text" class="form-control" name="phone_number" placeholder="01XXXXXXXXX" required>
                                                        </div>
                                                        <div class="alert alert-info">
                                                            <h6>Offer Details</h6>
                                                            <ul class="mb-0">
                                                                <li>Price: ৳<?php echo number_format($offer['price'], 2); ?></li>
                                                                <li>Validity: <?php echo $offer['validity_days']; ?> Days</li>
                                                                <li>Data: <?php echo $offer['data_amount']; ?></li>
                                                            </ul>
                                                        </div>
                                                        <div class="alert alert-warning">
                                                            Your current balance: ৳<?php echo number_format(getUserBalance($_SESSION['user_id']), 2); ?>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <input type="hidden" name="offer_id" value="<?php echo $offer['id']; ?>">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                        <button type="submit" name="buy_offer" class="btn btn-primary">Confirm Purchase</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endwhile; ?>
    </div>
</div>

<?php include('footer.php'); ?>