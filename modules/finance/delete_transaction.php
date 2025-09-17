<?php
// Delete Financial Transaction
require_once '../../config/config.php';
requireLogin();

$transaction_id = intval($_GET['id'] ?? 0);

if (!$transaction_id) {
    header('Location: index.php');
    exit;
}

try {
    // Get transaction details for confirmation and audit trail
    $stmt = $pdo->prepare("
        SELECT ft.*, fc.category_name
        FROM financial_transactions ft
        LEFT JOIN finance_categories fc ON ft.category_id = fc.id
        WHERE ft.id = ?
    ");
    $stmt->execute([$transaction_id]);
    $transaction = $stmt->fetch();
    
    if (!$transaction) {
        header('Location: index.php?error=Transaction not found');
        exit;
    }
    
    // Handle deletion confirmation
    if ($_POST && isset($_POST['confirm_delete'])) {
        // Begin transaction
        $pdo->beginTransaction();
        
        try {
            // Delete transaction documents first (due to foreign key constraint)
            $stmt = $pdo->prepare("DELETE FROM financial_documents WHERE transaction_id = ?");
            $stmt->execute([$transaction_id]);
            
            // Delete the transaction
            $stmt = $pdo->prepare("DELETE FROM financial_transactions WHERE id = ?");
            $stmt->execute([$transaction_id]);
            
            // Log the action
            logAuditTrail('financial_transactions', $transaction_id, 'DELETE', $transaction, null);
            
            // Commit transaction
            $pdo->commit();
            
            // Redirect with success message
            header('Location: index.php?deleted=1&desc=' . urlencode($transaction['description']));
            exit;
            
        } catch(PDOException $e) {
            // Rollback transaction on error
            $pdo->rollback();
            error_log("Delete transaction error: " . $e->getMessage());
            $error_message = 'Failed to delete transaction. Please try again.';
        }
    }
    
} catch(PDOException $e) {
    error_log("Delete transaction error: " . $e->getMessage());
    header('Location: index.php?error=Database error');
    exit;
}

$page_title = 'Delete Transaction';
include '../../includes/header.php';
?>

<div class="container">
    <!-- Page Header -->
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-trash"></i> Delete Transaction</h2>
        </div>
        <div class="card-body">
            <div class="btn-group">
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Transactions
                </a>
                <a href="view_transaction.php?id=<?php echo $transaction['id']; ?>" class="btn btn-info">
                    <i class="fas fa-eye"></i> View Details
                </a>
            </div>
        </div>
    </div>
    
    <!-- Error Message -->
    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle"></i> <?php echo $error_message; ?>
        </div>
    <?php endif; ?>
    
    <!-- Confirmation Card -->
    <div class="card">
        <div class="card-header" style="background-color: #dc3545; color: white;">
            <h3><i class="fas fa-exclamation-triangle"></i> Confirm Deletion</h3>
        </div>
        <div class="card-body">
            <div class="alert alert-warning">
                <h4><i class="fas fa-warning"></i> Warning!</h4>
                <p>You are about to permanently delete this financial transaction. This action cannot be undone.</p>
                <p><strong>This will also delete:</strong></p>
                <ul>
                    <li>All associated documents</li>
                    <li>Historical records</li>
                    <li>Audit trail entries</li>
                </ul>
            </div>
            
            <!-- Transaction Information Summary -->
            <div class="transaction-summary">
                <h4>Transaction to be deleted:</h4>
                <table class="table">
                    <tr>
                        <td><strong>Date:</strong></td>
                        <td><?php echo formatDate($transaction['transaction_date']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Description:</strong></td>
                        <td><?php echo htmlspecialchars($transaction['description']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Type:</strong></td>
                        <td>
                            <span class="badge <?php echo $transaction['transaction_type'] === 'Income' ? 'bg-success' : 'bg-danger'; ?>">
                                <?php echo $transaction['transaction_type']; ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Amount:</strong></td>
                        <td class="<?php echo $transaction['transaction_type'] === 'Income' ? 'text-success' : 'text-danger'; ?>">
                            <strong><?php echo formatCurrency($transaction['amount']); ?></strong>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Category:</strong></td>
                        <td><?php echo htmlspecialchars($transaction['category_name'] ?? 'Uncategorized'); ?></td>
                    </tr>
                    <?php if ($transaction['reference_number']): ?>
                    <tr>
                        <td><strong>Reference:</strong></td>
                        <td><?php echo htmlspecialchars($transaction['reference_number']); ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <td><strong>Created:</strong></td>
                        <td><?php echo formatDateTime($transaction['created_at']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Created By:</strong></td>
                        <td><?php echo htmlspecialchars($transaction['created_by'] ?? 'System'); ?></td>
                    </tr>
                </table>
            </div>
            
            <!-- Confirmation Form -->
            <form method="POST" style="margin-top: 2rem;">
                <div class="form-group">
                    <label class="form-label">
                        <input type="checkbox" id="confirm_checkbox" required style="margin-right: 0.5rem;">
                        I understand that this action is permanent and cannot be undone
                    </label>
                </div>
                
                <div class="btn-group" style="justify-content: center; width: 100%; margin-top: 2rem;">
                    <button type="submit" name="confirm_delete" class="btn btn-danger" id="delete_btn" disabled>
                        <i class="fas fa-trash"></i> Yes, Delete Transaction
                    </button>
                    <a href="view_transaction.php?id=<?php echo $transaction['id']; ?>" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.badge {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
    font-weight: 500;
    border-radius: 4px;
    color: white;
}

.bg-success { background-color: #28a745; }
.bg-danger { background-color: #dc3545; }

.transaction-summary {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 8px;
    margin: 1rem 0;
}

.transaction-summary h4 {
    color: #495057;
    margin-bottom: 1rem;
}

.table td {
    padding: 0.5rem;
    border-bottom: 1px solid #dee2e6;
}

.table td:first-child {
    width: 30%;
    font-weight: 500;
}

.btn-group {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.alert-warning {
    border-left: 4px solid #ffc107;
}

.text-success { color: #28a745 !important; }
.text-danger { color: #dc3545 !important; }
</style>

<script>
// Enable delete button only when checkbox is checked
document.getElementById('confirm_checkbox').addEventListener('change', function() {
    document.getElementById('delete_btn').disabled = !this.checked;
});

// Additional confirmation on form submit
document.querySelector('form').addEventListener('submit', function(e) {
    if (!confirm('Are you absolutely sure you want to delete this transaction? This action cannot be undone!')) {
        e.preventDefault();
    }
});
</script>

<?php include '../../includes/footer.php'; ?>