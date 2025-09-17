<?php
// View Transaction Details
require_once '../../config/config.php';
requireLogin();

$transaction_id = intval($_GET['id'] ?? 0);
$added = isset($_GET['added']);

if (!$transaction_id) {
    header('Location: index.php');
    exit;
}

try {
    // Get transaction details
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
    
    // Get transaction documents
    $stmt = $pdo->prepare("
        SELECT * FROM financial_documents 
        WHERE transaction_id = ?
        ORDER BY uploaded_at DESC
    ");
    $stmt->execute([$transaction_id]);
    $documents = $stmt->fetchAll();
    
} catch(PDOException $e) {
    error_log("View transaction error: " . $e->getMessage());
    header('Location: index.php?error=Database error');
    exit;
}

$page_title = 'Transaction Details';
include '../../includes/header.php';
?>

<div class="container">
    <!-- Success Message -->
    <?php if ($added): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> Transaction has been successfully added to the system!
        </div>
    <?php endif; ?>
    
    <!-- Page Header -->
    <div class="card">
        <div class="card-header">
            <h2>
                <i class="fas fa-receipt"></i> Transaction Details
                <span class="badge <?php echo $transaction['transaction_type'] === 'Income' ? 'bg-success' : 'bg-danger'; ?>">
                    <?php echo $transaction['transaction_type']; ?>
                </span>
            </h2>
        </div>
        <div class="card-body">
            <div class="btn-group">
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Transactions
                </a>
                <a href="edit_transaction.php?id=<?php echo $transaction['id']; ?>" class="btn btn-warning">
                    <i class="fas fa-edit"></i> Edit Transaction
                </a>
                <a href="delete_transaction.php?id=<?php echo $transaction['id']; ?>" class="btn btn-danger"
                   onclick="return confirm('Are you sure you want to delete this transaction?')">
                    <i class="fas fa-trash"></i> Delete Transaction
                </a>
            </div>
        </div>
    </div>
    
    <!-- Transaction Information -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-info-circle"></i> Transaction Information</h3>
        </div>
        <div class="card-body">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                <div>
                    <table class="table">
                        <tr>
                            <td><strong>Transaction Date:</strong></td>
                            <td><?php echo formatDate($transaction['transaction_date']); ?></td>
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
                                <strong style="font-size: 1.2rem;"><?php echo formatCurrency($transaction['amount']); ?></strong>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Category:</strong></td>
                            <td><?php echo htmlspecialchars($transaction['category_name'] ?? 'Uncategorized'); ?></td>
                        </tr>
                    </table>
                </div>
                
                <div>
                    <table class="table">
                        <tr>
                            <td><strong>Reference Number:</strong></td>
                            <td><?php echo htmlspecialchars($transaction['reference_number'] ?? 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Created By:</strong></td>
                            <td><?php echo htmlspecialchars($transaction['created_by'] ?? 'System'); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Created On:</strong></td>
                            <td><?php echo formatDateTime($transaction['created_at']); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Last Updated:</strong></td>
                            <td><?php echo formatDateTime($transaction['updated_at']); ?></td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <div style="margin-top: 1.5rem;">
                <strong>Description:</strong>
                <div style="background: #f8f9fa; padding: 1rem; border-radius: 4px; margin-top: 0.5rem;">
                    <?php echo nl2br(htmlspecialchars($transaction['description'])); ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Documents -->
    <?php if (!empty($documents)): ?>
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-file-alt"></i> Supporting Documents</h3>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Document Name</th>
                            <th>File Type</th>
                            <th>Upload Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($documents as $doc): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($doc['document_name']); ?></td>
                                <td><?php echo strtoupper($doc['file_type']); ?></td>
                                <td><?php echo formatDateTime($doc['uploaded_at']); ?></td>
                                <td>
                                    <a href="<?php echo htmlspecialchars($doc['file_path']); ?>" 
                                       class="btn btn-sm btn-primary" 
                                       target="_blank">
                                        <i class="fas fa-download"></i> Download
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
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

.btn-group {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.table td {
    padding: 0.5rem;
    border-bottom: 1px solid #dee2e6;
}

.table td:first-child {
    width: 40%;
    font-weight: 500;
}

.text-success { color: #28a745 !important; }
.text-danger { color: #dc3545 !important; }

@media (max-width: 768px) {
    .card-body > div[style*="grid"] {
        grid-template-columns: 1fr !important;
    }
    
    .btn-group {
        flex-direction: column;
    }
}
</style>

<?php include '../../includes/footer.php'; ?>