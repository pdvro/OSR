<?php
// Audit log file path
$audit_log_file = dirname(__FILE__) . '/audit_log.json';

// Log audit
function logAudit($action, $details) {
    global $audit_log_file;
    
    // Validate audit log file path
    if (empty($audit_log_file) || !is_string($audit_log_file)) {
        error_log("Audit log file path is empty or invalid in audit.php");
        return;
    }

    // Resolve and verify path
    $resolved_path = realpath(dirname($audit_log_file)) ? realpath(dirname($audit_log_file)) . '/audit_log.json' : $audit_log_file;
    if (!file_exists(dirname($resolved_path))) {
        error_log("Audit log directory does not exist: " . dirname($resolved_path));
        return;
    }

    $log_entry = [
        'timestamp' => date('c'),
        'username' => $_SESSION['username'] ?? 'admin',
        'action' => $action,
        'details' => $details
    ];
    
    $logs = [];
    if (file_exists($resolved_path)) {
        $content = file_get_contents($resolved_path);
        $logs = $content !== false ? json_decode($content, true) : [];
        if (!is_array($logs)) {
            $logs = [];
        }
    }
    
    $logs[] = $log_entry;
    
    // Ensure file exists
    if (!file_exists($resolved_path)) {
        if (@file_put_contents($resolved_path, '') === false) {
            error_log("Failed to create audit_log.json at $resolved_path: Permission denied or path issue");
            return;
        }
    }
    
    // Write logs if writable
    if (is_writable($resolved_path)) {
        if (file_put_contents($resolved_path, json_encode($logs, JSON_PRETTY_PRINT)) === false) {
            error_log("Failed to write to audit_log.json at $resolved_path: Permission denied or path issue");
        }
    } else {
        error_log("audit_log.json at $resolved_path is not writable: Check permissions");
    }
}
?>