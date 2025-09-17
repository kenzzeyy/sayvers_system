<?php
// Main Dashboard for SAYVERS Management System
require_once 'config/config.php';

// Require login
requireLogin();

// Get dashboard statistics
try {
    // Total members
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM members WHERE status = 'Active'");
    $stmt->execute();
    $total_members = $stmt->fetchColumn();
    
    // Total transactions this month
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM financial_transactions 
        WHERE MONTH(transaction_date) = MONTH(CURRENT_DATE) 
        AND YEAR(transaction_date) = YEAR(CURRENT_DATE)
    ");
    $stmt->execute();
    $monthly_transactions = $stmt->fetchColumn();
    
    // Total income this month
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(amount), 0) FROM financial_transactions 
        WHERE transaction_type = 'Income' 
        AND MONTH(transaction_date) = MONTH(CURRENT_DATE) 
        AND YEAR(transaction_date) = YEAR(CURRENT_DATE)
    ");
    $stmt->execute();
    $monthly_income = $stmt->fetchColumn();
    
    // Total expenses this month
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(amount), 0) FROM financial_transactions 
        WHERE transaction_type = 'Expense' 
        AND MONTH(transaction_date) = MONTH(CURRENT_DATE) 
        AND YEAR(transaction_date) = YEAR(CURRENT_DATE)
    ");
    $stmt->execute();
    $monthly_expenses = $stmt->fetchColumn();
    
    // Current balance (all time)
    $stmt = $pdo->prepare("
        SELECT COALESCE(
            (SELECT SUM(amount) FROM financial_transactions WHERE transaction_type = 'Income') - 
            (SELECT SUM(amount) FROM financial_transactions WHERE transaction_type = 'Expense'), 0
        ) as balance
    ");
    $stmt->execute();
    $current_balance = $stmt->fetchColumn();
    
    // Recent members (last 5)
    $stmt = $pdo->prepare("
        SELECT id, first_name, surname, position, created_at 
        FROM members 
        WHERE status = 'Active' 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $stmt->execute();
    $recent_members = $stmt->fetchAll();
    
    // Recent transactions (last 5)
    $stmt = $pdo->prepare("
        SELECT ft.*, fc.category_name 
        FROM financial_transactions ft
        LEFT JOIN finance_categories fc ON ft.category_id = fc.id
        ORDER BY ft.created_at DESC 
        LIMIT 5
    ");
    $stmt->execute();
    $recent_transactions = $stmt->fetchAll();

} catch(PDOException $e) {
    error_log("Dashboard error: " . $e->getMessage());
    $total_members = $monthly_transactions = $monthly_income = $monthly_expenses = $current_balance = 0;
    $recent_members = $recent_transactions = [];
}

$page_title = 'Dashboard';
include 'includes/header.php';
?>

<div class="container">
    <!-- Welcome Section -->
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-tachometer-alt"></i> Dashboard Overview</h2>
        </div>
        <div class="card-body">
            <p>Welcome to the <?php echo ORGANIZATION_NAME; ?> Management System. Here's a quick overview of your organization's current status.</p>
        </div>
    </div>
    
    <!-- Statistics Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-number"><?php echo number_format($total_members); ?></div>
            <div class="stat-label">Active Members</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-exchange-alt"></i>
            </div>
            <div class="stat-number"><?php echo number_format($monthly_transactions); ?></div>
            <div class="stat-label">Transactions This Month</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-arrow-up text-success"></i>
            </div>
            <div class="stat-number"><?php echo formatCurrency($monthly_income); ?></div>
            <div class="stat-label">Monthly Income</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-arrow-down text-danger"></i>
            </div>
            <div class="stat-number"><?php echo formatCurrency($monthly_expenses); ?></div>
            <div class="stat-label">Monthly Expenses</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-wallet"></i>
            </div>
            <div class="stat-number <?php echo $current_balance >= 0 ? 'text-success' : 'text-danger'; ?>">
                <?php echo formatCurrency($current_balance); ?>
            </div>
            <div class="stat-label">Current Balance</div>
        </div>
    </div>
    
    <!-- Recent Activity Section -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
        <!-- Recent Members -->
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-user-plus"></i> Recent Members</h2>
            </div>
            <div class="card-body">
                <?php if (empty($recent_members)): ?>
                    <p style="text-align: center; color: #666; margin: 2rem 0;">
                        <i class="fas fa-info-circle"></i><br>
                        No members registered yet.
                    </p>
                    <div class="text-center">
                        <a href="modules/members/add.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add First Member
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <tbody>
                                <?php foreach ($recent_members as $member): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($member['first_name'] . ' ' . $member['surname']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($member['position'] ?? 'Member'); ?></small>
                                        </td>
                                        <td class="text-right">
                                            <small class="text-muted"><?php echo formatDate($member['created_at']); ?></small>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-center mt-3">
                        <a href="modules/members/index.php" class="btn btn-secondary">
                            <i class="fas fa-list"></i> View All Members
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Recent Transactions -->
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-credit-card"></i> Recent Transactions</h2>
            </div>
            <div class="card-body">
                <?php if (empty($recent_transactions)): ?>
                    <p style="text-align: center; color: #666; margin: 2rem 0;">
                        <i class="fas fa-info-circle"></i><br>
                        No transactions recorded yet.
                    </p>
                    <div class="text-center">
                        <a href="modules/finance/add_transaction.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add First Transaction
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <tbody>
                                <?php foreach ($recent_transactions as $transaction): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($transaction['description']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($transaction['category_name'] ?? 'Uncategorized'); ?></small>
                                        </td>
                                        <td class="text-right">
                                            <span class="<?php echo $transaction['transaction_type'] === 'Income' ? 'text-success' : 'text-danger'; ?>">
                                                <?php echo $transaction['transaction_type'] === 'Income' ? '+' : '-'; ?>
                                                <?php echo formatCurrency($transaction['amount']); ?>
                                            </span><br>
                                            <small class="text-muted"><?php echo formatDate($transaction['transaction_date']); ?></small>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-center mt-3">
                        <a href="modules/finance/index.php" class="btn btn-secondary">
                            <i class="fas fa-list"></i> View All Transactions
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-bolt"></i> Quick Actions</h2>
        </div>
        <div class="card-body">
            <div class="btn-group" style="display: flex; gap: 1rem; flex-wrap: wrap; justify-content: center;">
                <a href="modules/members/add.php" class="btn btn-primary">
                    <i class="fas fa-user-plus"></i> Add New Member
                </a>
                <a href="modules/finance/add_transaction.php" class="btn btn-success">
                    <i class="fas fa-plus-circle"></i> Add Transaction
                </a>
                <a href="modules/finance/reports.php" class="btn btn-info">
                    <i class="fas fa-chart-bar"></i> Financial Reports
                </a>
                <a href="modules/audit/index.php" class="btn btn-warning">
                    <i class="fas fa-history"></i> Audit Trail
                </a>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-refresh dashboard every 5 minutes
setTimeout(function() {
    location.reload();
}, 300000);

// Show welcome message for new login
<?php if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) < 60): ?>
showAlert('Welcome back, <?php echo htmlspecialchars($_SESSION['admin_username']); ?>!', 'success');
<?php endif; ?>
</script>

<?php include 'includes/footer.php'; ?>