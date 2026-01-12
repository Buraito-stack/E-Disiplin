<?php

session_start();

require_once __DIR__ . '/../../app/config/Database.php';
require_once __DIR__ . '/../../app/controllers/AuthController.php';

$database = new Database();
$database->connect();

$authController = new AuthController(null);

if (!$authController->isLoggedIn()) {
    header('Location: login.php');
    exit;
}
