<?php
// Financial Reports
require_once '../../config/config.php';
requireLogin();

$page_title = 'Financial Reports';

// Get date range from form or default to current month
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to = $_GET['date_to'] ?? date('Y-m-t');

try {
    // Summary statistics for the period
    $stmt = $pdo->prepare("
        SELECT 
            transaction_type,
            COUNT(*) as transaction_count,
            SUM(amount) as total_amount
        FROM financial_transactions 
        WHERE transaction_date BETWEEN ? AND ?
        GROUP BY transaction_type
    ");
    $stmt->execute([$date_from, $date_to]);
    $summary = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $total_income = $summary['Income'] ?? 0;
    $total_expenses = $summary['Expense'] ?? 0;
    $net_balance = $total_income - $total_expenses;
    
    // Income by category
    $stmt = $pdo->prepare("
        SELECT fc.category_name, SUM(ft.amount) as total
        FROM financial_transactions ft
        LEFT JOIN finance_categories fc ON ft.category_id = fc.id
        WHERE ft.transaction_type = 'Income' 
        AND ft.transaction_date BETWEEN ? AND ?
        GROUP BY ft.category_id, fc.category_name
        ORDER BY total DESC
    ");
    $stmt->execute([$date_from, $date_to]);
    $income_by_category = $stmt->fetchAll();
    
    // Expenses by category
    $stmt = $pdo->prepare("
        SELECT fc.category_name, SUM(ft.amount) as total
        FROM financial_transactions ft
        LEFT JOIN finance_categories fc ON ft.category_id = fc.id
        WHERE ft.transaction_type = 'Expense' 
        AND ft.transaction_date BETWEEN ? AND ?
        GROUP BY ft.category_id, fc.category_name
        ORDER BY total DESC
    ");
    $stmt->execute([$date_from, $date_to]);
    $expenses_by_category = $stmt->fetchAll();
    
    // Monthly trend (last 12 months)
    $stmt = $pdo->prepare("
        SELECT 
            DATE_FORMAT(transaction_date, '%Y-%m') as month,
            transaction_type,
            SUM(amount) as total
        FROM financial_transactions 
        WHERE transaction_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(transaction_date, '%Y-%m'), transaction_type
        ORDER BY month DESC
    ");
    $stmt->execute();
    $monthly_data = $stmt->fetchAll();
    
    // Process monthly data for chart
    $months = [];
    $monthly_income = [];
    $monthly_expenses = [];
    
    $grouped_data = [];
    foreach ($monthly_data as $row) {
        $grouped_data[$row['month']][$row['transaction_type']] = $row['total'];
    }
    
    for ($i = 11; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        $months[] = date('M Y', strtotime($month . '-01'));
        $monthly_income[] = $grouped_data[$month]['Income'] ?? 0;
        $monthly_expenses[] = $grouped_data[$month]['Expense'] ?? 0;
    }
    
} catch(PDOException $e) {
    error_log("Reports error: " . $e->getMessage());
    $total_income = $total_expenses = $net_balance = 0;
    $income_by_category = $expenses_by_category = [];
    $months = $monthly_income = $monthly_expenses = [];
}

include '../../includes/header.php';
?>

<div class="container">
    <!-- Page Header -->
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-chart-bar"></i> Financial Reports</h2>
        </div>
        <div class="card-body">
            <div class="btn-group">
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Transactions
                </a>
                <button onclick="window.print()" class="btn btn-info">
                    <i class="fas fa-print"></i> Print Report
                </button>
            </div>
        </div>
    </div>
    
    <!-- Date Range Filter -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-calendar"></i> Report Period</h3>
        </div>
        <div class="card-body">
            <form method="GET">
                <div class="form-row">
                    <div class="form-group">
                        <label for="date_from">From Date:</label>
                        <input type="date" id="date_from" name="date_from" class="form-control" 
                               value="<?php echo htmlspecialchars($date_from); ?>">
                    </div>
                    <div class="form-group">
                        <label for="date_to">To Date:</label>
                        <input type="date" id="date_to" name="date_to" class="form-control" 
                               value="<?php echo htmlspecialchars($date_to); ?>">
                    </div>
                    <div class="form-group" style="display: flex; align-items: end;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Generate Report
                        </button>
                    </div>
                </div>
            </form>
            <p class="text-muted" style="margin-top: 0.5rem;">
                Report period: <?php echo formatDate($date_from); ?> to <?php echo formatDate($date_to); ?>
            </p>
        </div>
    </div>
    
    <!-- Summary Statistics -->
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
    
    <!-- Category Breakdown -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
        <!-- Income by Category -->
        <div class="card">
            <div class="card-header">
                <h3 class="text-success"><i class="fas fa-arrow-up"></i> Income by Category</h3>
            </div>
            <div class="card-body">
                <?php if (empty($income_by_category)): ?>
                    <p class="text-muted">No income transactions in this period.</p>
                <?php else: ?>
                    <div class="category-chart">
                        <?php foreach ($income_by_category as $category): ?>
                            <?php $percentage = $total_income > 0 ? ($category['total'] / $total_income) * 100 : 0; ?>
                            <div class="category-bar">
                                <div class="category-info">
                                    <span><?php echo htmlspecialchars($category['category_name'] ?? 'Uncategorized'); ?></span>
                                    <span><?php echo formatCurrency($category['total']); ?></span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill bg-success" style="width: <?php echo $percentage; ?>%"></div>
                                </div>
                                <small class="text-muted"><?php echo number_format($percentage, 1); ?>%</small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Expenses by Category -->
        <div class="card">
            <div class="card-header">
                <h3 class="text-danger"><i class="fas fa-arrow-down"></i> Expenses by Category</h3>
            </div>
            <div class="card-body">
                <?php if (empty($expenses_by_category)): ?>
                    <p class="text-muted">No expense transactions in this period.</p>
                <?php else: ?>
                    <div class="category-chart">
                        <?php foreach ($expenses_by_category as $category): ?>
                            <?php $percentage = $total_expenses > 0 ? ($category['total'] / $total_expenses) * 100 : 0; ?>
                            <div class="category-bar">
                                <div class="category-info">
                                    <span><?php echo htmlspecialchars($category['category_name'] ?? 'Uncategorized'); ?></span>
                                    <span><?php echo formatCurrency($category['total']); ?></span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill bg-danger" style="width: <?php echo $percentage; ?>%"></div>
                                </div>
                                <small class="text-muted"><?php echo number_format($percentage, 1); ?>%</small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Monthly Trend Chart -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-chart-line"></i> 12-Month Trend</h3>
        </div>
        <div class="card-body">
            <div class="chart-container">
                <canvas id="monthlyChart" width="400" height="200"></canvas>
            </div>
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

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr auto;
    gap: 1rem;
    align-items: end;
}

.category-chart {
    max-height: 400px;
    overflow-y: auto;
}

.category-bar {
    margin-bottom: 1rem;
}

.category-info {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.25rem;
    font-size: 0.9rem;
}

.progress-bar {
    height: 8px;
    background: #e9ecef;
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 0.25rem;
}

.progress-fill {
    height: 100%;
    transition: width 0.3s ease;
}

.bg-success { background-color: #28a745; }
.bg-danger { background-color: #dc3545; }

.text-success { color: #28a745 !important; }
.text-danger { color: #dc3545 !important; }
.text-muted { color: #6c757d !important; }

.chart-container {
    position: relative;
    height: 300px;
}

@media print {
    .btn, .form-row { display: none; }
    .card { box-shadow: none; border: 1px solid #ddd; }
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .container > div[style*="grid"] {
        grid-template-columns: 1fr !important;
    }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Monthly trend chart
const ctx = document.getElementById('monthlyChart').getContext('2d');
const monthlyChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($months); ?>,
        datasets: [{
            label: 'Income',
            data: <?php echo json_encode($monthly_income); ?>,
            borderColor: '#28a745',
            backgroundColor: 'rgba(40, 167, 69, 0.1)',
            tension: 0.4
        }, {
            label: 'Expenses',
            data: <?php echo json_encode($monthly_expenses); ?>,
            borderColor: '#dc3545',
            backgroundColor: 'rgba(220, 53, 69, 0.1)',
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '₱' + value.toLocaleString();
                    }
                }
            }
        },
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.dataset.label + ': ₱' + context.parsed.y.toLocaleString();
                    }
                }
            }
        }
    }
});
</script>

<?php include '../../includes/footer.php'; ?>