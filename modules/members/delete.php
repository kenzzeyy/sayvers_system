<?php
// Delete Member
require_once '../../config/config.php';
requireLogin();

$member_id = intval($_GET['id'] ?? 0);

if (!$member_id) {
    header('Location: index.php');
    exit;
}

try {
    // Get member details for confirmation and audit trail
    $stmt = $pdo->prepare("SELECT * FROM members WHERE id = ?");
    $stmt->execute([$member_id]);
    $member = $stmt->fetch();
    
    if (!$member) {
        header('Location: index.php?error=Member not found');
        exit;
    }
    
    // Handle deletion confirmation
    if ($_POST && isset($_POST['confirm_delete'])) {
        // Begin transaction
        $pdo->beginTransaction();
        
        try {
            // Delete member documents first (due to foreign key constraint)
            $stmt = $pdo->prepare("DELETE FROM member_documents WHERE member_id = ?");
            $stmt->execute([$member_id]);
            
            // Delete the member
            $stmt = $pdo->prepare("DELETE FROM members WHERE id = ?");
            $stmt->execute([$member_id]);
            
            // Log the action
            logAuditTrail('members', $member_id, 'DELETE', $member, null);
            
            // Commit transaction
            $pdo->commit();
            
            // Redirect with success message
            header('Location: index.php?deleted=1&name=' . urlencode($member['first_name'] . ' ' . $member['surname']));
            exit;
            
        } catch(PDOException $e) {
            // Rollback transaction on error
            $pdo->rollback();
            error_log("Delete member error: " . $e->getMessage());
            $error_message = 'Failed to delete member. Please try again.';
        }
    }
    
} catch(PDOException $e) {
    error_log("Delete member error: " . $e->getMessage());
    header('Location: index.php?error=Database error');
    exit;
}

$page_title = 'Delete Member - ' . $member['first_name'] . ' ' . $member['surname'];
include '../../includes/header.php';
?>

<div class="container">
    <!-- Page Header -->
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-user-times"></i> Delete Member</h2>
        </div>
        <div class="card-body">
            <div class="btn-group">
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Members
                </a>
                <a href="view.php?id=<?php echo $member['id']; ?>" class="btn btn-info">
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
                <p>You are about to permanently delete this member from the system. This action cannot be undone.</p>
                <p><strong>All associated data will be deleted, including:</strong></p>
                <ul>
                    <li>Member personal information</li>
                    <li>Uploaded documents</li>
                    <li>Historical records</li>
                </ul>
            </div>
            
            <!-- Member Information Summary -->
            <div class="member-summary">
                <h4>Member to be deleted:</h4>
                <table class="table">
                    <tr>
                        <td><strong>Name:</strong></td>
                        <td>
                            <?php echo htmlspecialchars($member['first_name'] . ' ' . ($member['middle_name'] ? $member['middle_name'] . ' ' : '') . $member['surname']); ?>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Position:</strong></td>
                        <td><?php echo htmlspecialchars($member['position'] ?? 'Member'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>District:</strong></td>
                        <td><?php echo htmlspecialchars($member['district'] ?? 'Not specified'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Status:</strong></td>
                        <td>
                            <span class="badge <?php echo $member['status'] === 'Active' ? 'bg-success' : 'bg-secondary'; ?>">
                                <?php echo $member['status']; ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Member Since:</strong></td>
                        <td><?php echo formatDate($member['created_at']); ?></td>
                    </tr>
                    <?php if ($member['email']): ?>
                    <tr>
                        <td><strong>Email:</strong></td>
                        <td><?php echo htmlspecialchars($member['email']); ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($member['contact_number']): ?>
                    <tr>
                        <td><strong>Contact:</strong></td>
                        <td><?php echo htmlspecialchars($member['contact_number']); ?></td>
                    </tr>
                    <?php endif; ?>
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
                        <i class="fas fa-trash"></i> Yes, Delete Member
                    </button>
                    <a href="view.php?id=<?php echo $member['id']; ?>" class="btn btn-secondary">
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
.bg-secondary { background-color: #6c757d; }

.member-summary {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 8px;
    margin: 1rem 0;
}

.member-summary h4 {
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
</style>

<script>
// Enable delete button only when checkbox is checked
document.getElementById('confirm_checkbox').addEventListener('change', function() {
    document.getElementById('delete_btn').disabled = !this.checked;
});

// Additional confirmation on form submit
document.querySelector('form').addEventListener('submit', function(e) {
    if (!confirm('Are you absolutely sure you want to delete this member? This action cannot be undone!')) {
        e.preventDefault();
    }
});
</script>

<?php include '../../includes/footer.php'; ?>