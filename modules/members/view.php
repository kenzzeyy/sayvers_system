<?php
// View Member Details
require_once '../../config/config.php';
requireLogin();

$member_id = intval($_GET['id'] ?? 0);
$added = isset($_GET['added']);

if (!$member_id) {
    header('Location: index.php');
    exit;
}

try {
    // Get member details
    $stmt = $pdo->prepare("
        SELECT * FROM members 
        WHERE id = ?
    ");
    $stmt->execute([$member_id]);
    $member = $stmt->fetch();
    
    if (!$member) {
        header('Location: index.php?error=Member not found');
        exit;
    }
    
    // Get member documents
    $stmt = $pdo->prepare("
        SELECT * FROM member_documents 
        WHERE member_id = ?
        ORDER BY created_at DESC
    ");
    $stmt->execute([$member_id]);
    $documents = $stmt->fetchAll();
    
} catch(PDOException $e) {
    error_log("View member error: " . $e->getMessage());
    header('Location: index.php?error=Database error');
    exit;
}

$page_title = 'View Member - ' . $member['first_name'] . ' ' . $member['surname'];
include '../../includes/header.php';
?>

<div class="container">
    <!-- Success Message -->
    <?php if ($added): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> Member has been successfully added to the system!
        </div>
    <?php endif; ?>
    
    <!-- Page Header -->
    <div class="card">
        <div class="card-header">
            <h2>
                <i class="fas fa-user"></i> 
                <?php echo htmlspecialchars($member['first_name'] . ' ' . $member['surname']); ?>
                <span class="badge <?php echo $member['status'] === 'Active' ? 'bg-success' : 'bg-secondary'; ?>">
                    <?php echo $member['status']; ?>
                </span>
            </h2>
        </div>
        <div class="card-body">
            <div class="btn-group">
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Members
                </a>
                <a href="edit.php?id=<?php echo $member['id']; ?>" class="btn btn-warning">
                    <i class="fas fa-edit"></i> Edit Member
                </a>
                <a href="delete.php?id=<?php echo $member['id']; ?>" class="btn btn-danger"
                   onclick="return confirm('Are you sure you want to delete this member?')">
                    <i class="fas fa-trash"></i> Delete Member
                </a>
            </div>
        </div>
    </div>
    
    <!-- Member Information Grid -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
        <!-- Personal Information -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-user"></i> Personal Information</h3>
            </div>
            <div class="card-body">
                <table class="table">
                    <tr>
                        <td><strong>Full Name:</strong></td>
                        <td>
                            <?php echo htmlspecialchars($member['first_name'] . ' ' . ($member['middle_name'] ? $member['middle_name'] . ' ' : '') . $member['surname']); ?>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Age:</strong></td>
                        <td><?php echo $member['age'] ? $member['age'] . ' years old' : 'Not specified'; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Date of Birth:</strong></td>
                        <td><?php echo $member['date_of_birth'] ? formatDate($member['date_of_birth']) : 'Not specified'; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Gender:</strong></td>
                        <td><?php echo htmlspecialchars($member['gender']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Address:</strong></td>
                        <td><?php echo $member['address'] ? nl2br(htmlspecialchars($member['address'])) : 'Not specified'; ?></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <!-- Contact Information -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-phone"></i> Contact Information</h3>
            </div>
            <div class="card-body">
                <table class="table">
                    <tr>
                        <td><strong>Phone Number:</strong></td>
                        <td>
                            <?php if ($member['contact_number']): ?>
                                <a href="tel:<?php echo htmlspecialchars($member['contact_number']); ?>">
                                    <i class="fas fa-phone"></i> <?php echo htmlspecialchars($member['contact_number']); ?>
                                </a>
                            <?php else: ?>
                                Not specified
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Email Address:</strong></td>
                        <td>
                            <?php if ($member['email']): ?>
                                <a href="mailto:<?php echo htmlspecialchars($member['email']); ?>">
                                    <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($member['email']); ?>
                                </a>
                            <?php else: ?>
                                Not specified
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Emergency Contact:</strong></td>
                        <td>
                            <?php if ($member['emergency_contact_name']): ?>
                                <strong><?php echo htmlspecialchars($member['emergency_contact_name']); ?></strong><br>
                                <?php if ($member['emergency_contact_number']): ?>
                                    <a href="tel:<?php echo htmlspecialchars($member['emergency_contact_number']); ?>">
                                        <i class="fas fa-phone"></i> <?php echo htmlspecialchars($member['emergency_contact_number']); ?>
                                    </a>
                                <?php endif; ?>
                            <?php else: ?>
                                Not specified
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Organizational Information -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-sitemap"></i> Organizational Information</h3>
        </div>
        <div class="card-body">
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 2rem;">
                <div>
                    <strong>District:</strong><br>
                    <?php echo htmlspecialchars($member['district'] ?? 'Not specified'); ?>
                </div>
                <div>
                    <strong>Position:</strong><br>
                    <?php echo htmlspecialchars($member['position'] ?? 'Member'); ?>
                </div>
                <div>
                    <strong>Status:</strong><br>
                    <span class="badge <?php echo $member['status'] === 'Active' ? 'bg-success' : 'bg-secondary'; ?>">
                        <?php echo $member['status']; ?>
                    </span>
                </div>
                <div>
                    <strong>Standing Committee:</strong><br>
                    <?php echo htmlspecialchars($member['standing_committee'] ?? 'Not specified'); ?>
                </div>
                <div>
                    <strong>Subcommittee:</strong><br>
                    <?php echo htmlspecialchars($member['subcommittee'] ?? 'Not specified'); ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Educational Information -->
    <?php if ($member['university_school'] || $member['course_strand'] || $member['year_grade']): ?>
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-graduation-cap"></i> Educational Information</h3>
        </div>
        <div class="card-body">
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 2rem;">
                <div>
                    <strong>University/School:</strong><br>
                    <?php echo htmlspecialchars($member['university_school'] ?? 'Not specified'); ?>
                </div>
                <div>
                    <strong>Course/Strand:</strong><br>
                    <?php echo htmlspecialchars($member['course_strand'] ?? 'Not specified'); ?>
                </div>
                <div>
                    <strong>Year/Grade:</strong><br>
                    <?php echo htmlspecialchars($member['year_grade'] ?? 'Not specified'); ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Additional Information -->
    <?php if ($member['medical_conditions'] || $member['skills_trainings']): ?>
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-info-circle"></i> Additional Information</h3>
        </div>
        <div class="card-body">
            <?php if ($member['medical_conditions']): ?>
                <div class="mb-3">
                    <strong>Medical Conditions/Allergies:</strong><br>
                    <?php echo nl2br(htmlspecialchars($member['medical_conditions'])); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($member['skills_trainings']): ?>
                <div>
                    <strong>Skills and Trainings:</strong><br>
                    <?php echo nl2br(htmlspecialchars($member['skills_trainings'])); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Documents -->
    <?php if (!empty($documents)): ?>
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-file-alt"></i> Documents</h3>
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
                                <td><?php echo formatDate($doc['created_at']); ?></td>
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
    
    <!-- Member History -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-history"></i> Member History</h3>
        </div>
        <div class="card-body">
            <table class="table">
                <tr>
                    <td><strong>Member Since:</strong></td>
                    <td><?php echo formatDate($member['created_at']); ?></td>
                </tr>
                <tr>
                    <td><strong>Last Updated:</strong></td>
                    <td><?php echo formatDate($member['updated_at']); ?></td>
                </tr>
            </table>
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
    gap: 0.5rem;
    flex-wrap: wrap;
}

.table td {
    padding: 0.5rem;
    border-bottom: 1px solid #dee2e6;
}

.table td:first-child {
    width: 30%;
    font-weight: 500;
}

@media (max-width: 768px) {
    .container > div[style*="grid"] {
        grid-template-columns: 1fr !important;
    }
    
    .btn-group {
        flex-direction: column;
    }
}
</style>

<?php include '../../includes/footer.php'; ?>