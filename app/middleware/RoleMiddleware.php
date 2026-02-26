<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function requireRoles(array $roles): void
{
    $role = $_SESSION['role'] ?? null;
    if (!$role || !in_array($role, $roles, true)) {
        header('Location: /dashboard.php');
        exit;
    }
}
