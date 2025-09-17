<?php
// Finance Module - Main Index
require_once '../../config/config.php';
requireLogin();

$page_title = 'Financial Management';

// Handle search and filters
$search = $_GET['search'] ?? '';
$type_filter = $_GET['type'] ?? '';
$category_filter = $_GET['category'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Pagination
$page = max(1, intval($_GET['page'] ?? 1));
$records_per_page = 20;
$offset = ($page - 1) * $records_per_page;

try {
    // Build query with filters
    $where_conditions = [];
    $params = [];
    
    if (!empty($search)) {
        $where_conditions[] = "(ft.description LIKE ? OR ft.reference_number LIKE ?)";
        $search_param = '%' . $search . '%';
        $params = array_merge($params, [$search_param, $search_param]);
    }
    
    if (!empty($type_filter)) {
        $where_conditions[] = "ft.transaction_type = ?";
        $params[] = $type_filter;
    }
    
    if (!empty($category_filter)) {
        $where_conditions[] = "ft.category_id = ?";
        $params[] = $category_filter;
    }
    
    if (!empty($date_from)) {
        $where_conditions[] = "ft.transaction_date >= ?";
        $params[] = $date_from;
    }
    
    if (!empty($date_to)) {
        $where_conditions[] = "ft.transaction_date <= ?";
        $params[] = $date_to;
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    // Get total count for pagination
    $count_query = "
        SELECT COUNT(*) 
        FROM financial_transactions ft 
        LEFT JOIN finance_categories fc ON ft.category_id = fc.id 
        $where_clause
    ";
    $count_stmt = $pdo->prepare($count_query);
    $count_stmt->execute($params);
    $total_records = $count_stmt->fetchColumn();
    
    // Get transactions with pagination
    $query = "
        SELECT ft.*, fc.category_name
        FROM financial_transactions ft
        LEFT JOIN finance_categories fc ON ft.category_id = fc.id
        $where_clause
        ORDER BY ft.transaction_date DESC, ft.created_at DESC
        LIMIT $records_per_page OFFSET $offset
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $transactions = $stmt->fetchAll();
    
    // Get categories for filter
    $categories = $pdo->query("SELECT * FROM finance_categories ORDER BY category_name")->fetchAll();
    
    // Calculate summary statistics - Fixed the query
    $summary_query = "
        SELECT 
            transaction_type,
            SUM(amount) as total
        FROM financial_transactions ft
        $where_clause
        GROUP BY transaction_type
    ";
    $summary_stmt = $pdo->prepare($summary_query);
    $summary_stmt->execute($params);
    $summary_results = $summary_stmt->fetchAll();
    
    // Process summary results properly
    $total_income = 0;
    $total_expenses = 0;
    
    foreach ($summary_results as $row) {
        if ($row['transaction_type'] === 'Income') {
            $total_income = $row['total'];
        } elseif ($row['transaction_type'] === 'Expense') {
            $total_expenses = $row['total'];
        }
    }
    
    $net_balance = $total_income - $total_expenses;
    
    // Calculate pagination
    $total_pages = ceil($total_records / $records_per_page);
    
} catch(PDOException $e) {
    error_log("Finance index error: " . $e->getMessage());
    $transactions = [];
    $categories = [];
    $total_records = 0;
    $total_pages = 1;
    $total_income = $total_expenses = $net_balance = 0;
}

include '../../includes/header.php';
?>

<div class="container">
    <!-- Page Header -->
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-money-bill-wave"></i> Financial Management</h2>
        </div>
        <div class="card-body">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <p>Track income, expenses, and financial transactions for your organization.</p>
                <div class="btn-group">
                    <a href="add_transaction.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Transaction
                    </a>
                    <a href="reports.php" class="btn btn-info">
                        <i class="fas fa-chart-bar"></i> Reports
                    </a>
                    <a href="categories.php" class="btn btn-secondary">
                        <i class="fas fa-tags"></i> Categories
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Financial Summary -->
    <div class="stats-grid" style="grid-template-columns: repeat(3, 1fr); margin-bottom: 2rem;">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-arrow-up text-success"></i>
            </div>
            <div class="stat-number text-success"><?php echo formatCurrency($total_income); ?></div>
            <div class="stat-label">Total Income</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-arrow-down text-danger"></i>
            </div>
            <div class="stat-number text-danger"><?php echo formatCurrency($total_expenses); ?></div>
            <div class="stat-label">Total Expenses</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-balance-scale"></i>
            </div>
            <div class="stat-number <?php echo $net_balance >= 0 ? 'text-success' : 'text-danger'; ?>">
                <?php echo formatCurrency($net_balance); ?>
            </div>
            <div class="stat-label">Net Balance</div>
        </div>
    </div>
    
    <!-- Search and Filter Form -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-search"></i> Search & Filter</h3>
        </div>
        <div class="card-body">
            <form method="GET">
                <div class="form-row">
                    <div class="form-group">
                        <input type="text" 
                               name="search" 
                               class="form-control" 
                               placeholder="Search description or reference..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    
                    <div class="form-group">
                        <select name="type" class="form-control">
                            <option value="">All Types</option>
                            <option value="Income" <?php echo $type_filter === 'Income' ? 'selected' : ''; ?>>Income</option>
                            <option value="Expense" <?php echo $type_filter === 'Expense' ? 'selected' : ''; ?>>Expense</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <select name="category" class="form-control">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" 
                                        <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['category_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <input type="date" name="date_from" class="form-control" 
                               placeholder="From Date" value="<?php echo htmlspecialchars($date_from); ?>">
                    </div>
                    
                    <div class="form-group">
                        <input type="date" name="date_to" class="form-control" 
                               placeholder="To Date" value="<?php echo htmlspecialchars($date_to); ?>">
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-secondary">
                            <i class="fas fa-search"></i> Search
                        </button>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Transactions List -->
    <div class="card">
        <div class="card-header">
            <h3>
                <i class="fas fa-list"></i> Transactions 
                <span style="font-size: 0.8rem; font-weight: normal;">
                    (<?php echo number_format($total_records); ?> total)
                </span>
            </h3>
        </div>
        <div class="card-body">
            <?php if (empty($transactions)): ?>
                <div style="text-align: center; padding: 3rem;">
                    <i class="fas fa-money-bill-wave" style="font-size: 4rem; color: #ccc; margin-bottom: 1rem;"></i>
                    <h3 style="color: #666;">No transactions found</h3>
                    <p style="color: #999;">
                        <?php if (!empty($search) || !empty($type_filter) || !empty($category_filter)): ?>
                            Try adjusting your search criteria or <a href="index.php">clear filters</a>.
                        <?php else: ?>
                            Get started by adding your first transaction.
                        <?php endif; ?>
                    </p>
                    <a href="add_transaction.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Transaction
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table data-table" id="transactions-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Description</th>
                                <th>Category</th>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Reference</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $transaction): ?>
                                <tr>
                                    <td><?php echo formatDate($transaction['transaction_date']); ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($transaction['description']); ?></strong>
                                        <?php if ($transaction['created_by']): ?>
                                            <br><small class="text-muted">
                                                by <?php echo htmlspecialchars($transaction['created_by']); ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($transaction['category_name'] ?? 'Uncategorized'); ?></td>
                                    <td>
                                        <span class="badge <?php echo $transaction['transaction_type'] === 'Income' ? 'bg-success' : 'bg-danger'; ?>">
                                            <?php echo $transaction['transaction_type']; ?>
                                        </span>
                                    </td>
                                    <td class="<?php echo $transaction['transaction_type'] === 'Income' ? 'text-success' : 'text-danger'; ?>">
                                        <strong><?php echo formatCurrency($transaction['amount']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($transaction['reference_number'] ?? 'N/A'); ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="view_transaction.php?id=<?php echo $transaction['id']; ?>" 
                                               class="btn btn-sm btn-info" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="edit_transaction.php?id=<?php echo $transaction['id']; ?>" 
                                               class="btn btn-sm btn-warning" title="Edit Transaction">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="delete_transaction.php?id=<?php echo $transaction['id']; ?>" 
                                               class="btn btn-sm btn-danger" title="Delete Transaction"
                                               data-confirm="Are you sure you want to delete this transaction?">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Transactions pagination">
                        <ul class="pagination">
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
                
                <!-- Export Options -->
                <div class="text-center mt-4">
                    <button onclick="exportTableToCSV('transactions-table', 'transactions_export.csv')" 
                            class="btn btn-success">
                        <i class="fas fa-download"></i> Export to CSV
                    </button>
                    <button onclick="printPage()" class="btn btn-info">
                        <i class="fas fa-print"></i> Print List
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.stats-grid {
    display: grid;
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    text-align: center;
}

.stat-icon {
    font-size: 2rem;
    margin-bottom: 0.5rem;
}

.stat-number {
    font-size: 1.5rem;
    font-weight: bold;
    margin-bottom: 0.25rem;
}

.stat-label {
    color: #6c757d;
    font-size: 0.9rem;
}

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
    gap: 0.25rem;
}

.text-success { color: #28a745 !important; }
.text-danger { color: #dc3545 !important; }

@media print {
    .btn, .pagination, .card-header { display: none; }
    .card { box-shadow: none; border: none; }
}
</style>

<?php include '../../includes/footer.php'; ?>