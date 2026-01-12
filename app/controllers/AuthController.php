<?php

class AuthController
{
    private $userModel;

    public function __construct($userModel)
    {
        $this->userModel = $userModel;
    }

    public function login()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $username = trim($_POST['username'] ?? '');
            $password = trim($_POST['password'] ?? '');

            if (empty($username) || empty($password)) {
                return [
                    'success' => false,
                    'message' => 'Username dan password harus diisi'
                ];
            }

            $user = $this->userModel->findByUsername($username);

            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'Akun tidak ditemukan'
                ];
            }

            if (!$this->userModel->verifyPassword($password, $user['password'])) {
                return [
                    'success' => false,
                    'message' => 'Password yang Anda masukkan tidak sesuai'
                ];
            }

            if ($user['is_active'] != 1) {
                return [
                    'success' => false,
                    'message' => 'Akun Anda tidak aktif'
                ];
            }

            // Set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role'];

            return [
                'success' => true,
                'message' => 'Login berhasil',
                'redirect' => '/dashboard.php'
            ];
        }

        return ['success' => false];
    }

    public function logout()
    {
        session_destroy();
        return [
            'success' => true,
            'redirect' => 'index.php'
        ];
    }

    public function isLoggedIn()
    {
        return isset($_SESSION['user_id']);
    }

    public function getUser()
    {
        return $_SESSION ?? null;
    }
}
