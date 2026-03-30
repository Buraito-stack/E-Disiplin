<?php

/**
 * Security Helper - CSRF tokens, headers, input validation
 */
class SecurityHelper {
    
    public static function generateCSRFToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    public static function verifyCSRFToken($token = null) {
        $token = $token ?? $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
        
        if (empty($token) || empty($_SESSION['csrf_token'])) {
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    public static function setSecurityHeaders() {
        header('X-Frame-Options: DENY');
        header('X-Content-Type-Options: nosniff');
        header('X-XSS-Protection: 1; mode=block');
        // Allow Tailwind CDN and Google Fonts
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.tailwindcss.com; img-src 'self' data:; font-src 'self' https://fonts.gstatic.com");
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    }
    
    public static function configureSecureSession() {
        $options = [
            'lifetime' => 3600,
            'path' => '/',
            'domain' => '',
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
            'httponly' => true,
            'samesite' => 'Strict'
        ];
        
        session_set_cookie_params($options);
    }
    
    public static function sanitizeInput($input) {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
    
    public static function validateInt($value, $min = null, $max = null) {
        $options = [];
        if ($min !== null) $options['min_range'] = $min;
        if ($max !== null) $options['max_range'] = $max;
        
        return filter_var($value, FILTER_VALIDATE_INT, ['options' => $options]);
    }
    
    public static function logSecurityEvent($event, $userId = null, $details = '') {
        $logDir = __DIR__ . '/../../logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $userIdStr = $userId ?? 'UNKNOWN';
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
        $logMsg = "[$timestamp] Event: $event | User: $userIdStr | IP: $ip | $details\n";
        
        @error_log($logMsg, 3, "$logDir/security.log");
    }
    
    /**
     * Audit log for CRUD operations - writes to database table audit_log.
     */
    public static function auditLog($conn, $action, $tableName, $recordId = null, $details = '') {
        static $tableChecked = false;
        if (!$tableChecked) {
            $conn->query("CREATE TABLE IF NOT EXISTS audit_log (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT,
                username VARCHAR(100),
                action VARCHAR(50) NOT NULL,
                table_name VARCHAR(100),
                record_id INT,
                details TEXT,
                ip_address VARCHAR(45),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $tableChecked = true;
        }

        $userId = $_SESSION['user_id'] ?? null;
        $username = $_SESSION['username'] ?? 'system';
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';

        $stmt = $conn->prepare("INSERT INTO audit_log (user_id, username, action, table_name, record_id, details, ip_address) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param('isssis', $userId, $username, $action, $tableName, $recordId, $details, $ip);
            $stmt->execute();
        }
    }

    /**
     * Basic file-based rate limiting; use Redis for production.
     */
    public static function checkRateLimit($identifier, $action = '', $maxAttempts = 10, $window = 3600) {
        $cacheKey = 'ratelimit_' . $identifier . ($action ? "_" . $action : '');
        
        $limitFile = sys_get_temp_dir() . "/{$cacheKey}.json";
        
        $data = [];
        if (file_exists($limitFile)) {
            $data = json_decode(file_get_contents($limitFile), true) ?? [];
        }
        
        $now = time();
        $data['attempts'] = ($data['attempts'] ?? 0) + 1;
        $data['first_attempt'] = $data['first_attempt'] ?? $now;
        
        if (($now - $data['first_attempt']) > $window) {
            $data['attempts'] = 1;
            $data['first_attempt'] = $now;
        }
        
        file_put_contents($limitFile, json_encode($data));
        
        return $data['attempts'] <= $maxAttempts;
    }
}
?>
