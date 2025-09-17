<?php
// Edit Member
require_once '../../config/config.php';
requireLogin();

$member_id = intval($_GET['id'] ?? 0);
$error_messages = [];
$success_message = '';

if (!$member_id) {
    header('Location: index.php');
    exit;
}

try {
    // Get member details
    $stmt = $pdo->prepare("SELECT * FROM members WHERE id = ?");
    $stmt->execute([$member_id]);
    $member = $stmt->fetch();
    
    if (!$member) {
        header('Location: index.php?error=Member not found');
        exit;
    }
    
} catch(PDOException $e) {
    error_log("Edit member error: " . $e->getMessage());
    header('Location: index.php?error=Database error');
    exit;
}

// Handle form submission
if ($_POST) {
    try {
        // Validate required fields
        $required_fields = ['surname', 'first_name', 'gender'];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                $error_messages[] = ucfirst(str_replace('_', ' ', $field)) . ' is required.';
            }
        }
        
        // Validate email if provided
        if (!empty($_POST['email']) && !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            $error_messages[] = 'Please enter a valid email address.';
        }
        
        // Validate age if provided
        if (!empty($_POST['age']) && ($_POST['age'] < 1 || $_POST['age'] > 120)) {
            $error_messages[] = 'Please enter a valid age (1-120).';
        }
        
        // Check if email already exists (excluding current member)
        if (!empty($_POST['email'])) {
            $stmt = $pdo->prepare("SELECT id FROM members WHERE email = ? AND id != ?");
            $stmt->execute([$_POST['email'], $member_id]);
            if ($stmt->fetchColumn()) {
                $error_messages[] = 'This email address is already registered.';
            }
        }
        
        if (empty($error_messages)) {
            // Store old values for audit trail
            $old_values = $member;
            
            // Prepare data for update
            $member_data = [
                'surname' => sanitizeInput($_POST['surname']),
                'first_name' => sanitizeInput($_POST['first_name']),
                'middle_name' => sanitizeInput($_POST['middle_name']),
                'age' => !empty($_POST['age']) ? intval($_POST['age']) : null,
                'date_of_birth' => !empty($_POST['date_of_birth']) ? $_POST['date_of_birth'] : null,
                'gender' => $_POST['gender'],
                'address' => sanitizeInput($_POST['address']),
                'contact_number' => sanitizeInput($_POST['contact_number']),
                'email' => !empty($_POST['email']) ? strtolower(sanitizeInput($_POST['email'])) : null,
                'district' => sanitizeInput($_POST['district']),
                'position' => sanitizeInput($_POST['position']),
                'standing_committee' => sanitizeInput($_POST['standing_committee']),
                'subcommittee' => sanitizeInput($_POST['subcommittee']),
                'university_school' => sanitizeInput($_POST['university_school']),
                'course_strand' => sanitizeInput($_POST['course_strand']),
                'year_grade' => sanitizeInput($_POST['year_grade']),
                'emergency_contact_name' => sanitizeInput($_POST['emergency_contact_name']),
                'emergency_contact_number' => sanitizeInput($_POST['emergency_contact_number']),
                'medical_conditions' => sanitizeInput($_POST['medical_conditions']),
                'skills_trainings' => sanitizeInput($_POST['skills_trainings']),
                'status' => $_POST['status'] ?? 'Active'
            ];
            
            // Update member
            $stmt = $pdo->prepare("
                UPDATE members SET 
                    surname = ?, first_name = ?, middle_name = ?, age = ?, date_of_birth = ?, 
                    gender = ?, address = ?, contact_number = ?, email = ?, district = ?, 
                    position = ?, standing_committee = ?, subcommittee = ?, university_school = ?, 
                    course_strand = ?, year_grade = ?, emergency_contact_name = ?, 
                    emergency_contact_number = ?, medical_conditions = ?, skills_trainings = ?, 
                    status = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            
            $params = array_values($member_data);
            $params[] = $member_id;
            $stmt->execute($params);
            
            // Log the action
            logAuditTrail('members', $member_id, 'UPDATE', $old_values, $member_data);
            
            $success_message = 'Member updated successfully!';
            
            // Refresh member data
            $stmt = $pdo->prepare("SELECT * FROM members WHERE id = ?");
            $stmt->execute([$member_id]);
            $member = $stmt->fetch();
        }
        
    } catch(PDOException $e) {
        error_log("Update member error: " . $e->getMessage());
        $error_messages[] = 'Database error occurred. Please try again.';
    }
}

$page_title = 'Edit Member - ' . $member['first_name'] . ' ' . $member['surname'];
include '../../includes/header.php';
?>

<div class="container">
    <!-- Page Header -->
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-user-edit"></i> Edit Member</h2>
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
    
    <!-- Member Form -->
    <form method="POST" data-validate="true">
        <!-- Personal Information -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-user"></i> Personal Information</h3>
            </div>
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group">
                        <label for="surname" class="form-label">Surname <span style="color: red;">*</span></label>
                        <input type="text" id="surname" name="surname" class="form-control" 
                               value="<?php echo htmlspecialchars($member['surname']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="first_name" class="form-label">First Name <span style="color: red;">*</span></label>
                        <input type="text" id="first_name" name="first_name" class="form-control" 
                               value="<?php echo htmlspecialchars($member['first_name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="middle_name" class="form-label">Middle Name</label>
                        <input type="text" id="middle_name" name="middle_name" class="form-control" 
                               value="<?php echo htmlspecialchars($member['middle_name'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="age" class="form-label">Age</label>
                        <input type="number" id="age" name="age" class="form-control" min="1" max="120"
                               value="<?php echo htmlspecialchars($member['age'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="date_of_birth" class="form-label">Date of Birth</label>
                        <input type="date" id="date_of_birth" name="date_of_birth" class="form-control"
                               value="<?php echo htmlspecialchars($member['date_of_birth'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="gender" class="form-label">Gender <span style="color: red;">*</span></label>
                        <select id="gender" name="gender" class="form-control" required>
                            <option value="">Select Gender</option>
                            <option value="Male" <?php echo $member['gender'] === 'Male' ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo $member['gender'] === 'Female' ? 'selected' : ''; ?>>Female</option>
                            <option value="Other" <?php echo $member['gender'] === 'Other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="address" class="form-label">Address</label>
                    <textarea id="address" name="address" class="form-control" rows="3"><?php echo htmlspecialchars($member['address'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="contact_number" class="form-label">Contact Number</label>
                        <input type="tel" id="contact_number" name="contact_number" class="form-control"
                               value="<?php echo htmlspecialchars($member['contact_number'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" id="email" name="email" class="form-control"
                               value="<?php echo htmlspecialchars($member['email'] ?? ''); ?>">
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Organizational Information -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-sitemap"></i> Organizational Information</h3>
            </div>
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group">
                        <label for="district" class="form-label">District</label>
                        <input type="text" id="district" name="district" class="form-control"
                               value="<?php echo htmlspecialchars($member['district'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="position" class="form-label">Position</label>
                        <input type="text" id="position" name="position" class="form-control"
                               value="<?php echo htmlspecialchars($member['position'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="standing_committee" class="form-label">Standing Committee</label>
                        <input type="text" id="standing_committee" name="standing_committee" class="form-control"
                               value="<?php echo htmlspecialchars($member['standing_committee'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="subcommittee" class="form-label">Subcommittee</label>
                        <input type="text" id="subcommittee" name="subcommittee" class="form-control"
                               value="<?php echo htmlspecialchars($member['subcommittee'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="status" class="form-label">Status</label>
                    <select id="status" name="status" class="form-control">
                        <option value="Active" <?php echo $member['status'] === 'Active' ? 'selected' : ''; ?>>Active</option>
                        <option value="Inactive" <?php echo $member['status'] === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
            </div>
        </div>
        
        <!-- Educational Information -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-graduation-cap"></i> Educational Information</h3>
            </div>
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group">
                        <label for="university_school" class="form-label">University/School</label>
                        <input type="text" id="university_school" name="university_school" class="form-control"
                               value="<?php echo htmlspecialchars($member['university_school'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="course_strand" class="form-label">Course/Strand</label>
                        <input type="text" id="course_strand" name="course_strand" class="form-control"
                               value="<?php echo htmlspecialchars($member['course_strand'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="year_grade" class="form-label">Year/Grade</label>
                        <input type="text" id="year_grade" name="year_grade" class="form-control"
                               value="<?php echo htmlspecialchars($member['year_grade'] ?? ''); ?>">
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Emergency Contact -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-phone-alt"></i> Emergency Contact</h3>
            </div>
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group">
                        <label for="emergency_contact_name" class="form-label">Emergency Contact Name</label>
                        <input type="text" id="emergency_contact_name" name="emergency_contact_name" class="form-control"
                               value="<?php echo htmlspecialchars($member['emergency_contact_name'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="emergency_contact_number" class="form-label">Emergency Contact Number</label>
                        <input type="tel" id="emergency_contact_number" name="emergency_contact_number" class="form-control"
                               value="<?php echo htmlspecialchars($member['emergency_contact_number'] ?? ''); ?>">
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Additional Information -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-info-circle"></i> Additional Information</h3>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label for="medical_conditions" class="form-label">Medical Conditions or Allergies</label>
                    <textarea id="medical_conditions" name="medical_conditions" class="form-control" rows="3" 
                              placeholder="List any medical conditions, allergies, or health considerations..."><?php echo htmlspecialchars($member['medical_conditions'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="skills_trainings" class="form-label">Skills and Trainings</label>
                    <textarea id="skills_trainings" name="skills_trainings" class="form-control" rows="3"
                              placeholder="List skills, certifications, trainings, or special qualifications..."><?php echo htmlspecialchars($member['skills_trainings'] ?? ''); ?></textarea>
                </div>
            </div>
        </div>
        
        <!-- Form Actions -->
        <div class="card">
            <div class="card-body">
                <div class="btn-group" style="justify-content: center; width: 100%;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Member
                    </button>
                    <a href="view.php?id=<?php echo $member['id']; ?>" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
// Auto-calculate age from date of birth
document.getElementById('date_of_birth').addEventListener('change', function() {
    if (this.value) {
        const birthDate = new Date(this.value);
        const today = new Date();
        let age = today.getFullYear() - birthDate.getFullYear();
        const monthDiff = today.getMonth() - birthDate.getMonth();
        
        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
            age--;
        }
        
        if (age >= 0 && age <= 120) {
            document.getElementById('age').value = age;
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
</script>

<?php include '../../includes/footer.php'; ?>