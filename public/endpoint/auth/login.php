<?php

require_once __DIR__ . '/../../../app/config/bootstrap.php';
header('Content-Type: application/json');

require_once __DIR__ . '/../../../app/config/Database.php';
require_once __DIR__ . '/../../../app/models/User.php';
require_once __DIR__ . '/../../../app/controllers/AuthController.php';

try {
    // CSRF Token verification (CRITICAL SECURITY CHECK)
    if (!SecurityHelper::verifyCSRFToken()) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Akses ditolak: token tidak valid'
        ]);
        exit;
    }
    
    // Rate Limiting (BRUTE-FORCE PROTECTION) - DISABLED
    // $rateMax = (getenv('APP_ENV') === 'production') ? 5 : 50;
    // if (!SecurityHelper::checkRateLimit($_SERVER['REMOTE_ADDR'], 'login', $rateMax, 900)) {
    //     http_response_code(429);
    //     echo json_encode([
    //         'success' => false,
    //         'message' => 'Terlalu banyak percobaan login. Coba lagi dalam beberapa menit.'
    //     ]);
    //     exit;
    // }

    $database = new Database();
    $conn = $database->connect();
    
    $userModel = new User($conn);
    $authController = new AuthController($userModel);
    
    $result = $authController->login();
    
    echo json_encode($result);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan server'
    ]);
}
