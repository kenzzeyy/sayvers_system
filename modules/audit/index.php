<?php
// Audit Trail Module
require_once '../../config/config.php';
requireLogin();

$page_title = 'Audit Trail';

// Handle search and filters
$search = $_GET['search'] ?? '';
$table_filter = $_GET['table'] ?? '';
$action_filter = $_GET['action'] ?? '';
$user_filter = $_GET['user'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Pagination
$page = max(1, intval($_GET['page'] ?? 1));
$records_per_page = 50;
$offset = ($page - 1) * $records_per_page;

try {
    // Build query with filters
    $where_conditions = [];
    $params = [];
    
    if (!empty($search)) {
        $where_conditions[] = "(old_values LIKE ? OR new_values LIKE ? OR user_id LIKE ?)";
        $search_param = '%' . $search . '%';
        $params = array_merge($params, [$search_param, $search_param, $search_param]);
    }
    
    if (!empty($table_filter)) {
        $where_conditions[] = "table_name = ?";
        $params[] = $table_filter;
    }
    
    if (!empty($action_filter)) {
        $where_conditions[] = "action = ?";
        $params[] = $action_filter;
    }
    
    if (!empty($user_filter)) {
        $where_conditions[] = "user_id = ?";
        $params[] = $user_filter;
    }
    
    if (!empty($date_from)) {
        $where_conditions[] = "DATE(created_at) >= ?";
        $params[] = $date_from;
    }
    
    if (!empty($date_to)) {
        $where_conditions[] = "DATE(created_at) <= ?";
        $params[] = $date_to;
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    // Get total count for pagination
    $count_query = "SELECT COUNT(*) FROM audit_trail $where_clause";
    $count_stmt = $pdo->prepare($count_query);
    $count_stmt->execute($params);
    $total_records = $count_stmt->fetchColumn();
    
    // Get audit records with pagination
    $query = "
        SELECT * FROM audit_trail 
        $where_clause
        ORDER BY created_at DESC
        LIMIT $records_per_page OFFSET $offset
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $audit_records = $stmt->fetchAll();
    
    // Get filter options
    $tables = $pdo->query("SELECT DISTINCT table_name FROM audit_trail ORDER BY table_name")->fetchAll(PDO::FETCH_COLUMN);
    $actions = $pdo->query("SELECT DISTINCT action FROM audit_trail ORDER BY action")->fetchAll(PDO::FETCH_COLUMN);
    $users = $pdo->query("SELECT DISTINCT user_id FROM audit_trail ORDER BY user_id")->fetchAll(PDO::FETCH_COLUMN);
    
    // Calculate pagination
    $total_pages = ceil($total_records / $records_per_page);
    
} catch(PDOException $e) {
    error_log("Audit trail error: " . $e->getMessage());
    $audit_records = [];
    $total_records = 0;
    $total_pages = 1;
    $tables = $actions = $users = [];
}

include '../../includes/header.php';
?>

<div class="container">
    <!-- Page Header -->
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-history"></i> Audit Trail</h2>
        </div>
        <div class="card-body">
            <p>Track all system activities and changes made by users.</p>
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
                               placeholder="Search in data..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    
                    <div class="form-group">
                        <select name="table" class="form-control">
                            <option value="">All Tables</option>
                            <?php foreach ($tables as $table): ?>
                                <option value="<?php echo htmlspecialchars($table); ?>" 
                                        <?php echo $table_filter === $table ? 'selected' : ''; ?>>
                                    <?php echo ucfirst(str_replace('_', ' ', $table)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <select name="action" class="form-control">
                            <option value="">All Actions</option>
                            <?php foreach ($actions as $action): ?>
                                <option value="<?php echo htmlspecialchars($action); ?>" 
                                        <?php echo $action_filter === $action ? 'selected' : ''; ?>>
                                    <?php echo $action; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <select name="user" class="form-control">
                            <option value="">All Users</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo htmlspecialchars($user); ?>" 
                                        <?php echo $user_filter === $user ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($user); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
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
    
    <!-- Audit Records -->
    <div class="card">
        <div class="card-header">
            <h3>
                <i class="fas fa-list"></i> Audit Records 
                <span style="font-size: 0.8rem; font-weight: normal;">
                    (<?php echo number_format($total_records); ?> total)
                </span>
            </h3>
        </div>
        <div class="card-body">
            <?php if (empty($audit_records)): ?>
                <div style="text-align: center; padding: 3rem;">
                    <i class="fas fa-history" style="font-size: 4rem; color: #ccc; margin-bottom: 1rem;"></i>
                    <h3 style="color: #666;">No audit records found</h3>
                    <p style="color: #999;">
                        <?php if (!empty($search) || !empty($table_filter) || !empty($action_filter)): ?>
                            Try adjusting your search criteria or <a href="index.php">clear filters</a>.
                        <?php else: ?>
                            System activities will appear here as they occur.
                        <?php endif; ?>
                    </p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table audit-table">
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>User</th>
                                <th>Action</th>
                                <th>Table</th>
                                <th>Record ID</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($audit_records as $record): ?>
                                <tr>
                                    <td>
                                        <small><?php echo formatDateTime($record['created_at']); ?></small>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($record['user_id']); ?></strong>
                                        <?php if ($record['ip_address']): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($record['ip_address']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge action-badge action-<?php echo strtolower($record['action']); ?>">
                                            <?php echo $record['action']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo ucfirst(str_replace('_', ' ', $record['table_name'])); ?></td>
                                    <td><?php echo $record['record_id']; ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-info" onclick="showAuditDetails(<?php echo $record['id']; ?>)">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Audit trail pagination">
                        <ul class="pagination">
                            <?php for ($i = 1; $i <= min($total_pages, 10); $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            <?php if ($total_pages > 10): ?>
                                <li class="page-item">
                                    <span class="page-link">...</span>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $total_pages])); ?>">
                                        <?php echo $total_pages; ?>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Audit Details Modal -->
<div id="auditModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Audit Record Details</h3>
            <span class="close" onclick="closeAuditModal()">&times;</span>
        </div>
        <div class="modal-body" id="auditModalBody">
            Loading...
        </div>
    </div>
</div>

<style>
.form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 1rem;
}

.audit-table {
    font-size: 0.9rem;
}

.audit-table td {
    vertical-align: middle;
}

.action-badge {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
    font-weight: 500;
    border-radius: 4px;
    color: white;
}

.action-insert { background-color: #28a745; }
.action-update { background-color: #ffc107; color: #212529; }
.action-delete { background-color: #dc3545; }
.action-login { background-color: #17a2b8; }
.action-logout { background-color: #6c757d; }

.modal {
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.modal-content {
    background-color: white;
    margin: 5% auto;
    padding: 0;
    border-radius: 8px;
    width: 80%;
    max-width: 800px;
    max-height: 80vh;
    overflow-y: auto;
}

.modal-header {
    padding: 1rem;
    border-bottom: 1px solid #dee2e6;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-body {
    padding: 1rem;
}

.close {
    font-size: 1.5rem;
    cursor: pointer;
    color: #aaa;
}

.close:hover {
    color: #000;
}

.json-viewer {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 4px;
    font-family: monospace;
    font-size: 0.9rem;
    white-space: pre-wrap;
    max-height: 300px;
    overflow-y: auto;
}

.text-muted { color: #6c757d !important; }

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .modal-content {
        width: 95%;
        margin: 10% auto;
    }
}
</style>

<script>
function showAuditDetails(recordId) {
    document.getElementById('auditModal').style.display = 'block';
    document.getElementById('auditModalBody').innerHTML = 'Loading...';
    
    fetch('view_audit.php?id=' + recordId)
        .then(response => response.text())
        .then(data => {
            document.getElementById('auditModalBody').innerHTML = data;
        })
        .catch(error => {
            document.getElementById('auditModalBody').innerHTML = 'Error loading audit details.';
        });
}

function closeAuditModal() {
    document.getElementById('auditModal').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('auditModal');
    if (event.target === modal) {
        modal.style.display = 'none';
    }
}
</script>

<?php include '../../includes/footer.php'; ?>