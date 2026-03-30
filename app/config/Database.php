<?php

class Database
{
    private $host;
    private $db;
    private $user;
    private $pass;
    private $conn;

    public function __construct()
    {
        $this->host = getenv('DB_HOST') ?: 'localhost';
        $this->db = getenv('DB_NAME') ?: 'e_disiplin';
        $this->user = getenv('DB_USER') ?: 'root';
        $this->pass = getenv('DB_PASS') ?: '';
    }

    public function connect()
    {
        $this->conn = new mysqli(
            $this->host,
            $this->user,
            $this->pass,
            $this->db
        );

        if ($this->conn->connect_error) {
            error_log('Database connection failed: ' . $this->conn->connect_error);
            die('Sistem tidak dapat terhubung ke database. Silakan hubungi administrator.');
        }

        $this->conn->set_charset(getenv('DB_CHARSET') ?: 'utf8mb4');
        return $this->conn;
    }

    public function getConnection()
    {
        return $this->conn;
    }

    public function closeConnection()
    {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}
