<?php
// Add Financial Transaction
require_once '../../config/config.php';
requireLogin();

$page_title = 'Add New Transaction';
$error_messages = [];
$success_message = '';

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
            // Prepare data for insertion
            $transaction_data = [
                'transaction_date' => $_POST['transaction_date'],
                'description' => sanitizeInput($_POST['description']),
                'amount' => floatval($_POST['amount']),
                'transaction_type' => $_POST['transaction_type'],
                'category_id' => !empty($_POST['category_id']) ? intval($_POST['category_id']) : null,
                'reference_number' => sanitizeInput($_POST['reference_number']),
                'created_by' => $_SESSION['admin_username']
            ];
            
            // Insert transaction
            $stmt = $pdo->prepare("
                INSERT INTO financial_transactions (
                    transaction_date, description, amount, transaction_type, 
                    category_id, reference_number, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute(array_values($transaction_data));
            $transaction_id = $pdo->lastInsertId();
            
            // Handle file uploads
            if (!empty($_FILES['documents']['name'][0])) {
                $upload_dir = '../../uploads/finance/';
                
                // Create directory if it doesn't exist
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                foreach ($_FILES['documents']['name'] as $key => $filename) {
                    if ($_FILES['documents']['error'][$key] === UPLOAD_ERR_OK) {
                        $file_errors = validateFileUpload([
                            'name' => $_FILES['documents']['name'][$key],
                            'size' => $_FILES['documents']['size'][$key],
                            'error' => $_FILES['documents']['error'][$key]
                        ]);
                        
                        if (empty($file_errors)) {
                            $unique_filename = generateUniqueFilename($filename);
                            $file_path = $upload_dir . $unique_filename;
                            
                            if (move_uploaded_file($_FILES['documents']['tmp_name'][$key], $file_path)) {
                                // Save file record to database
                                $stmt = $pdo->prepare("
                                    INSERT INTO financial_documents (transaction_id, document_name, file_path, file_type)
                                    VALUES (?, ?, ?, ?)
                                ");
                                
                                $file_type = pathinfo($filename, PATHINFO_EXTENSION);
                                $stmt->execute([$transaction_id, $filename, $file_path, $file_type]);
                            }
                        }
                    }
                }
            }
            
            // Log the action
            logAuditTrail('financial_transactions', $transaction_id, 'INSERT', null, $transaction_data);
            
            $success_message = 'Transaction added successfully!';
            
            // Redirect to transaction view
            header('Location: view_transaction.php?id=' . $transaction_id . '&added=1');
            exit;
        }
        
    } catch(PDOException $e) {
        error_log("Add transaction error: " . $e->getMessage());
        $error_messages[] = 'Database error occurred. Please try again.';
    }
}

// Get categories for dropdown
try {
    $categories = $pdo->query("SELECT * FROM finance_categories ORDER BY category_type, category_name")->fetchAll();
} catch(PDOException $e) {
    $categories = [];
}

include '../../includes/header.php';
?>

<div class="container">
    <!-- Page Header -->
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-plus-circle"></i> Add New Transaction</h2>
        </div>
        <div class="card-body">
            <div class="btn-group">
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Transactions
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
    <form method="POST" enctype="multipart/form-data" data-validate="true">
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
                               value="<?php echo htmlspecialchars($_POST['transaction_date'] ?? date('Y-m-d')); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="transaction_type" class="form-label">Transaction Type <span style="color: red;">*</span></label>
                        <select id="transaction_type" name="transaction_type" class="form-control" required>
                            <option value="">Select Type</option>
                            <option value="Income" <?php echo ($_POST['transaction_type'] ?? '') === 'Income' ? 'selected' : ''; ?>>Income</option>
                            <option value="Expense" <?php echo ($_POST['transaction_type'] ?? '') === 'Expense' ? 'selected' : ''; ?>>Expense</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="amount" class="form-label">Amount (₱) <span style="color: red;">*</span></label>
                        <input type="number" id="amount" name="amount" class="form-control" 
                               min="0.01" step="0.01" placeholder="0.00"
                               value="<?php echo htmlspecialchars($_POST['amount'] ?? ''); ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="description" class="form-label">Description <span style="color: red;">*</span></label>
                    <textarea id="description" name="description" class="form-control" rows="3" 
                              placeholder="Enter transaction description..." required><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
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
                                        <?php echo ($_POST['category_id'] ?? '') == $category['id'] ? 'selected' : ''; ?>>
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
                               value="<?php echo htmlspecialchars($_POST['reference_number'] ?? ''); ?>">
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Document Upload -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-file-upload"></i> Supporting Documents</h3>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label for="documents" class="form-label">Upload Documents</label>
                    <div class="file-upload-area">
                        <div class="file-upload-icon">
                            <i class="fas fa-cloud-upload-alt"></i>
                        </div>
                        <p>Upload receipts, invoices, or other supporting documents</p>
                        <input type="file" id="documents" name="documents[]" class="form-control" 
                               multiple accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif">
                        <small class="text-muted">
                            Allowed file types: PDF, DOC, DOCX, JPG, PNG, GIF (Max 5MB per file)
                        </small>
                    </div>
                    <div class="file-preview"></div>
                </div>
            </div>
        </div>
        
        <!-- Form Actions -->
        <div class="card">
            <div class="card-body">
                <div class="btn-group" style="justify-content: center; width: 100%;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Add Transaction
                    </button>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
.file-upload-area {
    border: 2px dashed #dee2e6;
    border-radius: 8px;
    padding: 2rem;
    text-align: center;
    background: #f8f9fa;
    transition: border-color 0.3s;
}

.file-upload-area:hover {
    border-color: #007bff;
}

.file-upload-icon {
    font-size: 3rem;
    color: #6c757d;
    margin-bottom: 1rem;
}

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
    
    // Reset category selection if it doesn't match the new type
    if (selectedType && categorySelect.value) {
        const selectedOption = categorySelect.options[categorySelect.selectedIndex];
        const selectedGroup = selectedOption.parentElement;
        if (selectedGroup.tagName === 'OPTGROUP' && selectedGroup.label !== selectedType) {
            categorySelect.value = '';
        }
    }
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

// File preview
document.getElementById('documents').addEventListener('change', function() {
    const preview = document.querySelector('.file-preview');
    preview.innerHTML = '';
    
    Array.from(this.files).forEach(file => {
        const fileItem = document.createElement('div');
        fileItem.className = 'file-item';
        fileItem.innerHTML = `
            <i class="fas fa-file"></i>
            <span>${file.name}</span>
            <small>(${(file.size / 1024).toFixed(1)} KB)</small>
        `;
        preview.appendChild(fileItem);
    });
});
</script>

<?php include '../../includes/footer.php'; ?>