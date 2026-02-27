<?php

/**
 * Security Helper - CSRF tokens, headers, input validation
 */
class SecurityHelper {
    
    /**
     * Generate CSRF token for session
     */
    public static function generateCSRFToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Verify CSRF token from POST/GET
     */
    public static function verifyCSRFToken($token = null) {
        $token = $token ?? $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
        
        if (empty($token) || empty($_SESSION['csrf_token'])) {
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Set security headers (call early in page)
     */
    public static function setSecurityHeaders() {
        // Prevent clickjacking
        header('X-Frame-Options: DENY');
        
        // Prevent MIME type sniffing
        header('X-Content-Type-Options: nosniff');
        
        // Enable XSS protection
        header('X-XSS-Protection: 1; mode=block');
        
        // Content Security Policy (basic)
        // allow Tailwind CDN and Google Fonts for styles/scripts
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.tailwindcss.com; img-src 'self' data:; font-src 'self' https://fonts.gstatic.com");
        
        // Referrer Policy
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // Permissions Policy
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    }
    
    /**
     * Secure session configuration
     */
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
    
    /**
     * Sanitize string input
     */
    public static function sanitizeInput($input) {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Validate email
     */
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
    
    /**
     * Validate integer
     */
    public static function validateInt($value, $min = null, $max = null) {
        $options = [];
        if ($min !== null) $options['min_range'] = $min;
        if ($max !== null) $options['max_range'] = $max;
        
        return filter_var($value, FILTER_VALIDATE_INT, ['options' => $options]);
    }
    
    /**
     * Log security event (untuk audit trail)
     */
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
     * Rate limiting check (basic in-memory, use Redis for production)
     */
    // identifier: usually IP address
    // action: optional string to differentiate throttles (e.g. "login")
    // maxAttempts: number of tries allowed in the window
    // window: length of time in seconds
    public static function checkRateLimit($identifier, $action = '', $maxAttempts = 10, $window = 3600) {
        $cacheKey = 'ratelimit_' . $identifier . ($action ? "_" . $action : '');
        
        // For production you'd use Redis; here we keep a simple file-based counter
        $limitFile = sys_get_temp_dir() . "/{$cacheKey}.json";
        
        $data = [];
        if (file_exists($limitFile)) {
            $data = json_decode(file_get_contents($limitFile), true) ?? [];
        }
        
        $now = time();
        $data['attempts'] = ($data['attempts'] ?? 0) + 1;
        $data['first_attempt'] = $data['first_attempt'] ?? $now;
        
        // Jika window sudah lewat, reset
        if (($now - $data['first_attempt']) > $window) {
            $data['attempts'] = 1;
            $data['first_attempt'] = $now;
        }
        
        file_put_contents($limitFile, json_encode($data));
        
        return $data['attempts'] <= $maxAttempts;
    }
}
?>
