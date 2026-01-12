<?php

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../app/config/Database.php';
require_once __DIR__ . '/../../app/models/User.php';
require_once __DIR__ . '/../../app/controllers/AuthController.php';

try {
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
