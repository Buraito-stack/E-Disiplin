<?php

require_once __DIR__ . '/../app/config/bootstrap.php';
require_once __DIR__ . '/../app/config/Database.php';
require_once __DIR__ . '/../app/controllers/AuthController.php';

$database = new Database();
$database->connect();

$authController = new AuthController(null);

// Jika sudah login, redirect ke dashboard
if ($authController->isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

// Load login view
include __DIR__ . '/../app/views/login.php';
