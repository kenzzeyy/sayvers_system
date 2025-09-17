<?php
// SAYVERS Management System - Main Configuration File
session_start();

// System Configuration
define('SYSTEM_NAME', 'SAYVERS Management System');
define('ORGANIZATION_NAME', 'Sta. Cruz Alliance of Young Volunteers for Emergencies (SAYVERS)');
define('ORGANIZATION_SHORT', 'SAYVERS');
define('SYSTEM_VERSION', '1.0');
define('BASE_URL', '/sayvers_system/');

// Include database configuration
require_once 'database.php';

// Security Functions
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . 'modules/auth/login.php');
        exit;
    }
}

function isLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

// Input Sanitization
function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Currency Formatting
function formatCurrency($amount) {
    return '₱' . number_format((float)$amount, 2);
}

// Date Formatting
function formatDate($date) {
    if (empty($date)) return 'N/A';
    return date('M d, Y', strtotime($date));
}

function formatDateTime($datetime) {
    if (empty($datetime)) return 'N/A';
    return date('M d, Y g:i A', strtotime($datetime));
}

// File Upload Validation
function validateFileUpload($file) {
    $errors = [];
    $allowedTypes = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'gif'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'File upload error occurred.';
        return $errors;
    }
    
    $fileName = strtolower($file['name']);
    $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
    
    if (!in_array($fileExtension, $allowedTypes)) {
        $errors[] = 'Invalid file type. Allowed types: ' . implode(', ', $allowedTypes);
    }
    
    if ($file['size'] > $maxSize) {
        $errors[] = 'File size exceeds 5MB limit.';
    }
    
    return $errors;
}

// Generate Unique Filename
function generateUniqueFilename($originalName) {
    $extension = pathinfo($originalName, PATHINFO_EXTENSION);
    $baseName = pathinfo($originalName, PATHINFO_FILENAME);
    $timestamp = time();
    $random = mt_rand(1000, 9999);
    
    return $baseName . '_' . $timestamp . '_' . $random . '.' . $extension;
}

// Audit Trail Logging
function logAuditTrail($tableName, $recordId, $action, $oldValues = null, $newValues = null) {
    global $pdo;
    
    try {
        $userId = $_SESSION['admin_username'] ?? 'system';
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        $stmt = $pdo->prepare("
            INSERT INTO audit_trail (table_name, record_id, action, old_values, new_values, user_id, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $tableName,
            $recordId,
            $action,
            $oldValues ? json_encode($oldValues) : null,
            $newValues ? json_encode($newValues) : null,
            $userId,
            $ipAddress,
            $userAgent
        ]);
        
    } catch(PDOException $e) {
        error_log("Audit trail error: " . $e->getMessage());
    }
}

// Error Handling
function handleError($message, $redirect = null) {
    error_log($message);
    if ($redirect) {
        header('Location: ' . $redirect . '?error=' . urlencode('An error occurred. Please try again.'));
        exit;
    }
}

// Success Message Handling
function setSuccessMessage($message) {
    $_SESSION['success_message'] = $message;
}

function getSuccessMessage() {
    if (isset($_SESSION['success_message'])) {
        $message = $_SESSION['success_message'];
        unset($_SESSION['success_message']);
        return $message;
    }
    return null;
}

// Database Helper Functions
function executeQuery($query, $params = []) {
    global $pdo;
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt;
    } catch(PDOException $e) {
        error_log("Database query error: " . $e->getMessage());
        throw $e;
    }
}

function fetchOne($query, $params = []) {
    $stmt = executeQuery($query, $params);
    return $stmt->fetch();
}

function fetchAll($query, $params = []) {
    $stmt = executeQuery($query, $params);
    return $stmt->fetchAll();
}

function getLastInsertId() {
    global $pdo;
    return $pdo->lastInsertId();
}

// System Settings
function getSystemSetting($key, $default = null) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetchColumn();
        return $result !== false ? $result : $default;
    } catch(PDOException $e) {
        return $default;
    }
}

function setSystemSetting($key, $value, $description = '') {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            INSERT INTO system_settings (setting_key, setting_value, description) 
            VALUES (?, ?, ?) 
            ON DUPLICATE KEY UPDATE setting_value = ?, description = ?
        ");
        $stmt->execute([$key, $value, $description, $value, $description]);
        return true;
    } catch(PDOException $e) {
        error_log("System setting error: " . $e->getMessage());
        return false;
    }
}

// Check if system is properly configured
function checkSystemHealth() {
    global $pdo;
    $issues = [];
    
    // Check database connection
    try {
        $pdo->query("SELECT 1");
    } catch(PDOException $e) {
        $issues[] = "Database connection failed";
    }
    
    // Check required directories
    $requiredDirs = [
        __DIR__ . '/../uploads/',
        __DIR__ . '/../uploads/members/',
        __DIR__ . '/../uploads/finance/'
    ];
    
    foreach ($requiredDirs as $dir) {
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true)) {
                $issues[] = "Cannot create directory: $dir";
            }
        } elseif (!is_writable($dir)) {
            $issues[] = "Directory not writable: $dir";
        }
    }
    
    return $issues;
}

// Initialize system
$systemIssues = checkSystemHealth();
if (!empty($systemIssues)) {
    error_log("System health issues: " . implode(', ', $systemIssues));
}
?>