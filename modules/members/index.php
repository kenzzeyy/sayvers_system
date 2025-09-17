<?php
// Members Module - Main Index
require_once '../../config/config.php';
requireLogin();

$page_title = 'Members Management';

// Handle search and filters
$search = $_GET['search'] ?? '';
$district_filter = $_GET['district'] ?? '';
$position_filter = $_GET['position'] ?? '';
$status_filter = $_GET['status'] ?? 'Active';

// Pagination
$page = max(1, intval($_GET['page'] ?? 1));
$records_per_page = 20;
$offset = ($page - 1) * $records_per_page;

try {
    // Build query with filters
    $where_conditions = [];
    $params = [];
    
    if (!empty($search)) {
        $where_conditions[] = "(first_name LIKE ? OR surname LIKE ? OR email LIKE ? OR contact_number LIKE ?)";
        $search_param = '%' . $search . '%';
        $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
    }
    
    if (!empty($district_filter)) {
        $where_conditions[] = "district = ?";
        $params[] = $district_filter;
    }
    
    if (!empty($position_filter)) {
        $where_conditions[] = "position = ?";
        $params[] = $position_filter;
    }
    
    if (!empty($status_filter)) {
        $where_conditions[] = "status = ?";
        $params[] = $status_filter;
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    // Get total count for pagination
    $count_query = "SELECT COUNT(*) FROM members $where_clause";
    $count_stmt = $pdo->prepare($count_query);
    $count_stmt->execute($params);
    $total_records = $count_stmt->fetchColumn();
    
    // Get members with pagination
    $query = "
        SELECT id, first_name, surname, middle_name, age, gender, position, district, 
               contact_number, email, status, created_at
        FROM members 
        $where_clause
        ORDER BY surname, first_name
        LIMIT $records_per_page OFFSET $offset
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $members = $stmt->fetchAll();
    
    // Get filter options
    $districts_query = "SELECT DISTINCT district FROM members WHERE district IS NOT NULL ORDER BY district";
    $districts = $pdo->query($districts_query)->fetchAll(PDO::FETCH_COLUMN);
    
    $positions_query = "SELECT DISTINCT position FROM members WHERE position IS NOT NULL ORDER BY position";
    $positions = $pdo->query($positions_query)->fetchAll(PDO::FETCH_COLUMN);
    
    // Calculate pagination
    $total_pages = ceil($total_records / $records_per_page);
    
} catch(PDOException $e) {
    error_log("Members index error: " . $e->getMessage());
    $members = [];
    $total_records = 0;
    $total_pages = 1;
    $districts = $positions = [];
}

include '../../includes/header.php';
?>

<div class="container">
    <!-- Page Header -->
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-users"></i> Members Management</h2>
        </div>
        <div class="card-body">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <p>Manage your organization members and their information.</p>
                <a href="add.php" class="btn btn-primary">
                    <i class="fas fa-user-plus"></i> Add New Member
                </a>
            </div>
            
            <!-- Search and Filter Form -->
            <form method="GET" class="mb-4">
                <div class="form-row">
                    <div class="form-group">
                        <input type="text" 
                               name="search" 
                               class="form-control" 
                               placeholder="Search by name, email, or phone..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    
                    <div class="form-group">
                        <select name="district" class="form-control">
                            <option value="">All Districts</option>
                            <?php foreach ($districts as $district): ?>
                                <option value="<?php echo htmlspecialchars($district); ?>" 
                                        <?php echo $district_filter === $district ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($district); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <select name="position" class="form-control">
                            <option value="">All Positions</option>
                            <?php foreach ($positions as $position): ?>
                                <option value="<?php echo htmlspecialchars($position); ?>" 
                                        <?php echo $position_filter === $position ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($position); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <select name="status" class="form-control">
                            <option value="">All Status</option>
                            <option value="Active" <?php echo $status_filter === 'Active' ? 'selected' : ''; ?>>Active</option>
                            <option value="Inactive" <?php echo $status_filter === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
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
    
    <!-- Results -->
    <div class="card">
        <div class="card-header">
            <h3>
                <i class="fas fa-list"></i> Members List 
                <span style="font-size: 0.8rem; font-weight: normal;">
                    (<?php echo number_format($total_records); ?> total)
                </span>
            </h3>
        </div>
        <div class="card-body">
            <?php if (empty($members)): ?>
                <div style="text-align: center; padding: 3rem;">
                    <i class="fas fa-users" style="font-size: 4rem; color: #ccc; margin-bottom: 1rem;"></i>
                    <h3 style="color: #666;">No members found</h3>
                    <p style="color: #999;">
                        <?php if (!empty($search) || !empty($district_filter) || !empty($position_filter)): ?>
                            Try adjusting your search criteria or <a href="index.php">clear filters</a>.
                        <?php else: ?>
                            Get started by adding your first member.
                        <?php endif; ?>
                    </p>
                    <a href="add.php" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> Add New Member
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table data-table" id="members-table">
                        <thead>
                            <tr>
                                <th data-sortable>Name</th>
                                <th data-sortable>Position</th>
                                <th data-sortable>District</th>
                                <th data-sortable>Contact</th>
                                <th data-sortable>Status</th>
                                <th data-sortable>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($members as $member): ?>
                                <tr>
                                    <td>
                                        <strong>
                                            <?php echo htmlspecialchars($member['first_name'] . ' ' . $member['surname']); ?>
                                        </strong>
                                        <?php if ($member['middle_name']): ?>
                                            <br><small class="text-muted">
                                                <?php echo htmlspecialchars($member['middle_name']); ?>
                                            </small>
                                        <?php endif; ?>
                                        <?php if ($member['age']): ?>
                                            <small class="text-muted">(<?php echo $member['age']; ?>)</small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($member['position'] ?? 'Member'); ?></td>
                                    <td><?php echo htmlspecialchars($member['district'] ?? 'N/A'); ?></td>
                                    <td>
                                        <?php if ($member['contact_number']): ?>
                                            <i class="fas fa-phone"></i> <?php echo htmlspecialchars($member['contact_number']); ?><br>
                                        <?php endif; ?>
                                        <?php if ($member['email']): ?>
                                            <i class="fas fa-envelope"></i> 
                                            <small><?php echo htmlspecialchars($member['email']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $member['status'] === 'Active' ? 'bg-success' : 'bg-secondary'; ?>">
                                            <?php echo $member['status']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo formatDate($member['created_at']); ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="view.php?id=<?php echo $member['id']; ?>" 
                                               class="btn btn-sm btn-info" 
                                               title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="edit.php?id=<?php echo $member['id']; ?>" 
                                               class="btn btn-sm btn-warning" 
                                               title="Edit Member">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="delete.php?id=<?php echo $member['id']; ?>" 
                                               class="btn btn-sm btn-danger" 
                                               title="Delete Member"
                                               data-confirm="Are you sure you want to delete this member? This action cannot be undone.">
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
                    <nav aria-label="Members pagination">
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
                    <button onclick="exportTableToCSV('members-table', 'members_export.csv')" 
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
.badge {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
    font-weight: 500;
    border-radius: 4px;
    color: white;
}

.bg-success { background-color: #28a745; }
.bg-secondary { background-color: #6c757d; }

.btn-group {
    display: flex;
    gap: 0.25rem;
}

.table th[data-sortable] {
    cursor: pointer;
    position: relative;
}

.table th[data-sortable]:hover {
    background-color: #e9ecef;
}

.table th.sort-asc::after {
    content: " ↑";
}

.table th.sort-desc::after {
    content: " ↓";
}

@media print {
    .btn, .pagination, .card-header { display: none; }
    .card { box-shadow: none; border: none; }
}
</style>

<?php include '../../includes/footer.php'; ?>