<?php 
include('header.php');
include('config.php');

if(!isset($_SESSION['user_id'])) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];

// Handle deposit request
if(isset($_POST['submit_deposit'])) {
    $amount = floatval($_POST['amount']);
    $method = sanitize($_POST['method']);
    $transaction_id = sanitize($_POST['transaction_id']);
    
    if($amount > 0) {
        // Insert transaction
        $sql = "INSERT INTO transactions (user_id, type, amount, method, details, status) 
                VALUES ($user_id, 'deposit', $amount, '$method', 'Deposit via $method. Transaction ID: $transaction_id', 'pending')";
        
        if(mysqli_query($conn, $sql)) {
            $_SESSION['message'] = "Deposit request submitted successfully! It will be processed shortly.";
            $_SESSION['message_type'] = "success";
            redirect('profile.php');
        } else {
            $_SESSION['message'] = "Error: " . mysqli_error($conn);
            $_SESSION['message_type'] = "danger";
        }
    } else {
        $_SESSION['message'] = "Amount must be greater than 0!";
        $_SESSION['message_type'] = "danger";
    }
}
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-money-bill-wave"></i> Add Money to Wallet</h4>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Amount (BDT)</label>
                            <input type="number" class="form-control" name="amount" min="10" step="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Payment Method</label>
                            <select class="form-select" name="method" required>
                                <option value="">Select Method</option>
                                <option value="bKash">bKash</option>
                                <option value="Nagad">Nagad</option>
                                <option value="Rocket">Rocket</option>
                                <option value="Bank">Bank Transfer</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Transaction ID</label>
                            <input type="text" class="form-control" name="transaction_id" required>
                            <small class="text-muted">The transaction ID you received after payment</small>
                        </div>
                        <button type="submit" name="submit_deposit" class="btn btn-primary w-100">
                            <i class="fas fa-paper-plane"></i> Submit Deposit Request
                        </button>
                    </form>
                    
                    <hr>
                    
                    <h5 class="mt-4">Payment Instructions</h5>
                    <div class="accordion" id="paymentMethods">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#bKash">
                                    bKash Payment
                                </button>
                            </h2>
                            <div id="bKash" class="accordion-collapse collapse show" data-bs-parent="#paymentMethods">
                                <div class="accordion-body">
                                    <ol>
                                        <li>Go to your bKash Mobile Menu by dialing *247#</li>
                                        <li>Choose "Payment" option</li>
                                        <li>Enter our bKash Account Number: 017XXXXXXXX</li>
                                        <li>Enter the amount you want to deposit</li>
                                        <li>Enter your bKash Mobile Menu PIN to confirm</li>
                                        <li>Copy the Transaction ID (TrxID) and paste above</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#nagad">
                                    Nagad Payment
                                </button>
                            </h2>
                            <div id="nagad" class="accordion-collapse collapse" data-bs-parent="#paymentMethods">
                                <div class="accordion-body">
                                    <ol>
                                        <li>Go to your Nagad Mobile Menu by dialing *167#</li>
                                        <li>Choose "Send Money" option</li>
                                        <li>Enter our Nagad Account Number: 017XXXXXXXX</li>
                                        <li>Enter the amount you want to deposit</li>
                                        <li>Enter your Nagad PIN to confirm</li>
                                        <li>Copy the Transaction ID (TrxID) and paste above</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('footer.php'); ?>