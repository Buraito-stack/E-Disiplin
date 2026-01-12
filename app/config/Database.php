<?php

class Database
{
    private $host = 'localhost';
    private $db = 'e_disiplin';
    private $user = 'root';
    private $pass = '';
    private $conn;

    public function connect()
    {
        $this->conn = new mysqli(
            $this->host,
            $this->user,
            $this->pass,
            $this->db
        );

        if ($this->conn->connect_error) {
            die('Connection Error: ' . $this->conn->connect_error);
        }

        $this->conn->set_charset('utf8mb4');
        return $this->conn;
    }

    public function getConnection()
    {
        return $this->conn;
    }
}
