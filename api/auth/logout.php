<?php

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../app/config/Database.php';
require_once __DIR__ . '/../../app/controllers/AuthController.php';

try {
    $database = new Database();
    $database->connect();
    
    $authController = new AuthController(null);
    $result = $authController->logout();
    
    echo json_encode($result);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan server'
    ]);
}
