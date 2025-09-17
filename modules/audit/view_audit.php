<?php
// View Audit Record Details (AJAX endpoint)
require_once '../../config/config.php';
requireLogin();

$record_id = intval($_GET['id'] ?? 0);

if (!$record_id) {
    echo '<p class="text-danger">Invalid record ID.</p>';
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM audit_trail WHERE id = ?");
    $stmt->execute([$record_id]);
    $record = $stmt->fetch();
    
    if (!$record) {
        echo '<p class="text-danger">Audit record not found.</p>';
        exit;
    }
    
} catch(PDOException $e) {
    echo '<p class="text-danger">Database error occurred.</p>';
    exit;
}

function formatJsonData($json_string) {
    if (empty($json_string)) {
        return '<em class="text-muted">No data</em>';
    }
    
    $data = json_decode($json_string, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return '<div class="json-viewer">' . htmlspecialchars($json_string) . '</div>';
    }
    
    $formatted = '';
    foreach ($data as $key => $value) {
        $formatted .= '<strong>' . htmlspecialchars($key) . ':</strong> ';
        if (is_array($value) || is_object($value)) {
            $formatted .= json_encode($value, JSON_PRETTY_PRINT);
        } else {
            $formatted .= htmlspecialchars($value);
        }
        $formatted .= "\n";
    }
    
    return '<div class="json-viewer">' . $formatted . '</div>';
}
?>

<div class="audit-details">
    <table class="table">
        <tr>
            <td><strong>Timestamp:</strong></td>
            <td><?php echo formatDateTime($record['created_at']); ?></td>
        </tr>
        <tr>
            <td><strong>User:</strong></td>
            <td><?php echo htmlspecialchars($record['user_id']); ?></td>
        </tr>
        <tr>
            <td><strong>Action:</strong></td>
            <td>
                <span class="badge action-badge action-<?php echo strtolower($record['action']); ?>">
                    <?php echo $record['action']; ?>
                </span>
            </td>
        </tr>
        <tr>
            <td><strong>Table:</strong></td>
            <td><?php echo ucfirst(str_replace('_', ' ', $record['table_name'])); ?></td>
        </tr>
        <tr>
            <td><strong>Record ID:</strong></td>
            <td><?php echo $record['record_id']; ?></td>
        </tr>
        <tr>
            <td><strong>IP Address:</strong></td>
            <td><?php echo htmlspecialchars($record['ip_address'] ?? 'N/A'); ?></td>
        </tr>
        <tr>
            <td><strong>User Agent:</strong></td>
            <td style="word-break: break-all;">
                <small><?php echo htmlspecialchars($record['user_agent'] ?? 'N/A'); ?></small>
            </td>
        </tr>
    </table>
    
    <?php if ($record['action'] === 'UPDATE'): ?>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 1rem;">
            <div>
                <h4>Old Values:</h4>
                <?php echo formatJsonData($record['old_values']); ?>
            </div>
            <div>
                <h4>New Values:</h4>
                <?php echo formatJsonData($record['new_values']); ?>
            </div>
        </div>
    <?php elseif ($record['action'] === 'INSERT'): ?>
        <div style="margin-top: 1rem;">
            <h4>New Record Data:</h4>
            <?php echo formatJsonData($record['new_values']); ?>
        </div>
    <?php elseif ($record['action'] === 'DELETE'): ?>
        <div style="margin-top: 1rem;">
            <h4>Deleted Record Data:</h4>
            <?php echo formatJsonData($record['old_values']); ?>
        </div>
    <?php endif; ?>
</div>

<style>
.audit-details .table td:first-child {
    width: 30%;
    font-weight: 500;
}

.json-viewer {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 4px;
    font-family: monospace;
    font-size: 0.85rem;
    white-space: pre-wrap;
    max-height: 300px;
    overflow-y: auto;
    border: 1px solid #dee2e6;
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

@media (max-width: 768px) {
    .audit-details > div[style*="grid"] {
        grid-template-columns: 1fr !important;
    }
}
</style>