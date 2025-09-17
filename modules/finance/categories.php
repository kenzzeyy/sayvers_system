<?php
// Finance Categories Management
require_once '../../config/config.php';
requireLogin();

$page_title = 'Finance Categories';
$error_messages = [];
$success_message = '';

// Handle form submissions
if ($_POST) {
    if (isset($_POST['add_category'])) {
        // Add new category
        try {
            $category_name = sanitizeInput($_POST['category_name']);
            $category_type = $_POST['category_type'];
            
            if (empty($category_name) || empty($category_type)) {
                $error_messages[] = 'Category name and type are required.';
            } else {
                // Check if category already exists
                $stmt = $pdo->prepare("SELECT id FROM finance_categories WHERE category_name = ? AND category_type = ?");
                $stmt->execute([$category_name, $category_type]);
                
                if ($stmt->fetchColumn()) {
                    $error_messages[] = 'This category already exists.';
                } else {
                    $stmt = $pdo->prepare("INSERT INTO finance_categories (category_name, category_type) VALUES (?, ?)");
                    $stmt->execute([$category_name, $category_type]);
                    
                    logAuditTrail('finance_categories', $pdo->lastInsertId(), 'INSERT', null, [
                        'category_name' => $category_name,
                        'category_type' => $category_type
                    ]);
                    
                    $success_message = 'Category added successfully!';
                }
            }
        } catch(PDOException $e) {
            error_log("Add category error: " . $e->getMessage());
            $error_messages[] = 'Database error occurred. Please try again.';
        }
    }
    
    if (isset($_POST['delete_category'])) {
        // Delete category
        try {
            $category_id = intval($_POST['category_id']);
            
            // Check if category is in use
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM financial_transactions WHERE category_id = ?");
            $stmt->execute([$category_id]);
            $usage_count = $stmt->fetchColumn();
            
            if ($usage_count > 0) {
                $error_messages[] = "Cannot delete category. It is used in $usage_count transaction(s).";
            } else {
                // Get category data for audit trail
                $stmt = $pdo->prepare("SELECT * FROM finance_categories WHERE id = ?");
                $stmt->execute([$category_id]);
                $category_data = $stmt->fetch();
                
                if ($category_data) {
                    $stmt = $pdo->prepare("DELETE FROM finance_categories WHERE id = ?");
                    $stmt->execute([$category_id]);
                    
                    logAuditTrail('finance_categories', $category_id, 'DELETE', $category_data, null);
                    
                    $success_message = 'Category deleted successfully!';
                }
            }
        } catch(PDOException $e) {
            error_log("Delete category error: " . $e->getMessage());
            $error_messages[] = 'Database error occurred. Please try again.';
        }
    }
}

// Get all categories with usage count
try {
    $categories = $pdo->query("
        SELECT fc.*, 
               COUNT(ft.id) as usage_count
        FROM finance_categories fc
        LEFT JOIN financial_transactions ft ON fc.id = ft.category_id
        GROUP BY fc.id
        ORDER BY fc.category_type, fc.category_name
    ")->fetchAll();
} catch(PDOException $e) {
    error_log("Categories fetch error: " . $e->getMessage());
    $categories = [];
}

include '../../includes/header.php';
?>

<div class="container">
    <!-- Page Header -->
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-tags"></i> Finance Categories</h2>
        </div>
        <div class="card-body">
            <div class="btn-group">
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Transactions
                </a>
            </div>
            <p style="margin-top: 1rem;">Manage income and expense categories for better financial tracking.</p>
        </div>
    </div>
    
    <!-- Error Messages -->
    <?php if (!empty($error_messages)): ?>
        <div class="alert alert-danger">
            <h4><i class="fas fa-exclamation-triangle"></i> Error:</h4>
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
    
    <!-- Add New Category -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-plus"></i> Add New Category</h3>
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label for="category_name" class="form-label">Category Name</label>
                        <input type="text" id="category_name" name="category_name" class="form-control" 
                               placeholder="Enter category name..." required>
                    </div>
                    
                    <div class="form-group">
                        <label for="category_type" class="form-label">Category Type</label>
                        <select id="category_type" name="category_type" class="form-control" required>
                            <option value="">Select Type</option>
                            <option value="Income">Income</option>
                            <option value="Expense">Expense</option>
                        </select>
                    </div>
                    
                    <div class="form-group" style="display: flex; align-items: end;">
                        <button type="submit" name="add_category" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add Category
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Categories List -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-list"></i> Existing Categories</h3>
        </div>
        <div class="card-body">
            <?php if (empty($categories)): ?>
                <div style="text-align: center; padding: 2rem;">
                    <i class="fas fa-tags" style="font-size: 3rem; color: #ccc; margin-bottom: 1rem;"></i>
                    <h4 style="color: #666;">No categories found</h4>
                    <p style="color: #999;">Add your first category using the form above.</p>
                </div>
            <?php else: ?>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                    <!-- Income Categories -->
                    <div>
                        <h4 class="text-success"><i class="fas fa-arrow-up"></i> Income Categories</h4>
                        <div class="category-list">
                            <?php 
                            $income_categories = array_filter($categories, function($cat) { return $cat['category_type'] === 'Income'; });
                            if (empty($income_categories)): 
                            ?>
                                <p class="text-muted">No income categories yet.</p>
                            <?php else: ?>
                                <?php foreach ($income_categories as $category): ?>
                                    <div class="category-item">
                                        <div class="category-info">
                                            <strong><?php echo htmlspecialchars($category['category_name']); ?></strong>
                                            <small class="text-muted">
                                                (Used in <?php echo $category['usage_count']; ?> transaction<?php echo $category['usage_count'] != 1 ? 's' : ''; ?>)
                                            </small>
                                        </div>
                                        <?php if ($category['usage_count'] == 0): ?>
                                            <form method="POST" style="display: inline;" 
                                                  onsubmit="return confirm('Are you sure you want to delete this category?')">
                                                <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                                <button type="submit" name="delete_category" class="btn btn-sm btn-danger">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Expense Categories -->
                    <div>
                        <h4 class="text-danger"><i class="fas fa-arrow-down"></i> Expense Categories</h4>
                        <div class="category-list">
                            <?php 
                            $expense_categories = array_filter($categories, function($cat) { return $cat['category_type'] === 'Expense'; });
                            if (empty($expense_categories)): 
                            ?>
                                <p class="text-muted">No expense categories yet.</p>
                            <?php else: ?>
                                <?php foreach ($expense_categories as $category): ?>
                                    <div class="category-item">
                                        <div class="category-info">
                                            <strong><?php echo htmlspecialchars($category['category_name']); ?></strong>
                                            <small class="text-muted">
                                                (Used in <?php echo $category['usage_count']; ?> transaction<?php echo $category['usage_count'] != 1 ? 's' : ''; ?>)
                                            </small>
                                        </div>
                                        <?php if ($category['usage_count'] == 0): ?>
                                            <form method="POST" style="display: inline;" 
                                                  onsubmit="return confirm('Are you sure you want to delete this category?')">
                                                <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                                <button type="submit" name="delete_category" class="btn btn-sm btn-danger">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr auto;
    gap: 1rem;
    align-items: end;
}

.category-list {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1rem;
    min-height: 200px;
}

.category-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    margin-bottom: 0.5rem;
    background: white;
    border-radius: 4px;
    border-left: 4px solid #dee2e6;
}

.category-item:last-child {
    margin-bottom: 0;
}

.category-info {
    flex: 1;
}

.category-info strong {
    display: block;
    margin-bottom: 0.25rem;
}

.text-success { color: #28a745 !important; }
.text-danger { color: #dc3545 !important; }
.text-muted { color: #6c757d !important; }

.btn-group {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .card-body > div[style*="grid"] {
        grid-template-columns: 1fr !important;
    }
}
</style>

<?php include '../../includes/footer.php'; ?>