<?php
// Edit Financial Transaction
require_once '../../config/config.php';
requireLogin();

$transaction_id = intval($_GET['id'] ?? 0);
$error_messages = [];
$success_message = '';

if (!$transaction_id) {
    header('Location: index.php');
    exit;
}

try {
    // Get transaction details
    $stmt = $pdo->prepare("SELECT * FROM financial_transactions WHERE id = ?");
    $stmt->execute([$transaction_id]);
    $transaction = $stmt->fetch();
    
    if (!$transaction) {
        header('Location: index.php?error=Transaction not found');
        exit;
    }
    
} catch(PDOException $e) {
    error_log("Edit transaction error: " . $e->getMessage());
    header('Location: index.php?error=Database error');
    exit;
}

// Handle form submission
if ($_POST) {
    try {
        // Validate required fields
        $required_fields = ['transaction_date', 'description', 'amount', 'transaction_type'];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                $error_messages[] = ucfirst(str_replace('_', ' ', $field)) . ' is required.';
            }
        }
        
        // Validate amount
        if (!empty($_POST['amount']) && (!is_numeric($_POST['amount']) || $_POST['amount'] <= 0)) {
            $error_messages[] = 'Please enter a valid amount greater than 0.';
        }
        
        // Validate date
        if (!empty($_POST['transaction_date']) && !strtotime($_POST['transaction_date'])) {
            $error_messages[] = 'Please enter a valid transaction date.';
        }
        
        if (empty($error_messages)) {
            // Store old values for audit trail
            $old_values = $transaction;
            
            // Prepare data for update
            $transaction_data = [
                'transaction_date' => $_POST['transaction_date'],
                'description' => sanitizeInput($_POST['description']),
                'amount' => floatval($_POST['amount']),
                'transaction_type' => $_POST['transaction_type'],
                'category_id' => !empty($_POST['category_id']) ? intval($_POST['category_id']) : null,
                'reference_number' => sanitizeInput($_POST['reference_number'])
            ];
            
            // Update transaction
            $stmt = $pdo->prepare("
                UPDATE financial_transactions SET 
                    transaction_date = ?, description = ?, amount = ?, transaction_type = ?, 
                    category_id = ?, reference_number = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            
            $params = array_values($transaction_data);
            $params[] = $transaction_id;
            $stmt->execute($params);
            
            // Log the action
            logAuditTrail('financial_transactions', $transaction_id, 'UPDATE', $old_values, $transaction_data);
            
            $success_message = 'Transaction updated successfully!';
            
            // Refresh transaction data
            $stmt = $pdo->prepare("SELECT * FROM financial_transactions WHERE id = ?");
            $stmt->execute([$transaction_id]);
            $transaction = $stmt->fetch();
        }
        
    } catch(PDOException $e) {
        error_log("Update transaction error: " . $e->getMessage());
        $error_messages[] = 'Database error occurred. Please try again.';
    }
}

// Get categories for dropdown
try {
    $categories = $pdo->query("SELECT * FROM finance_categories ORDER BY category_type, category_name")->fetchAll();
} catch(PDOException $e) {
    $categories = [];
}

$page_title = 'Edit Transaction';
include '../../includes/header.php';
?>

<div class="container">
    <!-- Page Header -->
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-edit"></i> Edit Transaction</h2>
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
    
    <!-- Error Messages -->
    <?php if (!empty($error_messages)): ?>
        <div class="alert alert-danger">
            <h4><i class="fas fa-exclamation-triangle"></i> Please fix the following errors:</h4>
            <ul style="margin-bottom: 0;">
                <?php foreach ($error_messages as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <!-- Success Message -->
    <?php if ($success_message): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
        </div>
    <?php endif; ?>
    
    <!-- Transaction Form -->
    <form method="POST" data-validate="true">
        <!-- Basic Information -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-info-circle"></i> Transaction Details</h3>
            </div>
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group">
                        <label for="transaction_date" class="form-label">Transaction Date <span style="color: red;">*</span></label>
                        <input type="date" id="transaction_date" name="transaction_date" class="form-control" 
                               value="<?php echo htmlspecialchars($transaction['transaction_date']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="transaction_type" class="form-label">Transaction Type <span style="color: red;">*</span></label>
                        <select id="transaction_type" name="transaction_type" class="form-control" required>
                            <option value="">Select Type</option>
                            <option value="Income" <?php echo $transaction['transaction_type'] === 'Income' ? 'selected' : ''; ?>>Income</option>
                            <option value="Expense" <?php echo $transaction['transaction_type'] === 'Expense' ? 'selected' : ''; ?>>Expense</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="amount" class="form-label">Amount (₱) <span style="color: red;">*</span></label>
                        <input type="number" id="amount" name="amount" class="form-control" 
                               min="0.01" step="0.01" placeholder="0.00"
                               value="<?php echo htmlspecialchars($transaction['amount']); ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="description" class="form-label">Description <span style="color: red;">*</span></label>
                    <textarea id="description" name="description" class="form-control" rows="3" 
                              placeholder="Enter transaction description..." required><?php echo htmlspecialchars($transaction['description']); ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="category_id" class="form-label">Category</label>
                        <select id="category_id" name="category_id" class="form-control">
                            <option value="">Select Category</option>
                            <?php 
                            $current_type = '';
                            foreach ($categories as $category): 
                                if ($current_type !== $category['category_type']):
                                    if ($current_type !== '') echo '</optgroup>';
                                    echo '<optgroup label="' . htmlspecialchars($category['category_type']) . '">';
                                    $current_type = $category['category_type'];
                                endif;
                            ?>
                                <option value="<?php echo $category['id']; ?>" 
                                        <?php echo $transaction['category_id'] == $category['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['category_name']); ?>
                                </option>
                            <?php endforeach; ?>
                            <?php if ($current_type !== '') echo '</optgroup>'; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="reference_number" class="form-label">Reference Number</label>
                        <input type="text" id="reference_number" name="reference_number" class="form-control"
                               placeholder="Receipt #, Check #, etc."
                               value="<?php echo htmlspecialchars($transaction['reference_number'] ?? ''); ?>">
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Form Actions -->
        <div class="card">
            <div class="card-body">
                <div class="btn-group" style="justify-content: center; width: 100%;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Transaction
                    </button>
                    <a href="view_transaction.php?id=<?php echo $transaction['id']; ?>" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
.form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
}

.form-group {
    margin-bottom: 1rem;
}

.form-label {
    font-weight: 500;
    margin-bottom: 0.5rem;
    display: block;
}

.form-control {
    width: 100%;
    padding: 0.5rem;
    border: 1px solid #ced4da;
    border-radius: 4px;
}

.btn-group {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}
</style>

<script>
// Filter categories based on transaction type
document.getElementById('transaction_type').addEventListener('change', function() {
    const categorySelect = document.getElementById('category_id');
    const selectedType = this.value;
    
    // Show/hide options based on transaction type
    Array.from(categorySelect.options).forEach(option => {
        if (option.value === '') return; // Keep "Select Category" option
        
        const optgroup = option.parentElement;
        if (optgroup.tagName === 'OPTGROUP') {
            const groupType = optgroup.label;
            option.style.display = (!selectedType || groupType === selectedType) ? 'block' : 'none';
        }
    });
});

// Form validation
document.querySelector('form').addEventListener('submit', function(e) {
    const requiredFields = this.querySelectorAll('[required]');
    let hasErrors = false;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('is-invalid');
            hasErrors = true;
        } else {
            field.classList.remove('is-invalid');
        }
    });
    
    if (hasErrors) {
        e.preventDefault();
        showAlert('Please fill in all required fields.', 'danger');
        document.querySelector('.is-invalid').scrollIntoView();
    }
});
</script>

<?php include '../../includes/footer.php'; ?>