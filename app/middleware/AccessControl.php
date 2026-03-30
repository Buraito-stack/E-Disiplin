<?php

/**
 * Access Control Middleware - Verify data ownership and permissions
 */
class AccessControl {
    
    private $conn;
    private $userId;
    private $userRole;
    
    public function __construct($db) {
        $this->conn = $db;
        $this->userId = $_SESSION['user_id'] ?? null;
        $this->userRole = $_SESSION['role'] ?? null;
    }
    
    public function verifySiswaOwnership($siswaId) {
        if (!$this->userId || $this->userRole !== 'orangtua') {
            return false;
        }
        
        $stmt = $this->conn->prepare(
            "SELECT s.* FROM siswa s 
             JOIN users u ON (s.id_orang_tua = u.id OR s.kontak_orang_tua = u.email)
             WHERE s.id_siswa = ? AND u.id = ?"
        );
        
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param('ii', $siswaId, $this->userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
    
    public function verifyGuruStudentAccess($siswaId) {
        if (!$this->userId) {
            return false;
        }

        $staffRoles = ['admin', 'guru', 'bk', 'guru_mapel'];
        if (!in_array($this->userRole, $staffRoles, true)) {
            return false;
        }

        $stmt = $this->conn->prepare(
            "SELECT s.* FROM siswa s
             WHERE s.id_siswa = ?"
        );

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('i', $siswaId);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result->fetch_assoc();
    }

    public function verifyStaffAccess($siswaId, $minRole = 'bk') {
        if (!$this->userId) {
            return false;
        }

        $allowedRoles = ['admin', 'bk', 'wakasek', 'guru', 'guru_mapel'];
        if (!in_array($this->userRole, $allowedRoles, true)) {
            return false;
        }
        
        $stmt = $this->conn->prepare("SELECT * FROM siswa WHERE id_siswa = ?");
        if (!$stmt) return false;
        
        $stmt->bind_param('i', $siswaId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
    
    public function getAuthorizedSiswaList() {
        if ($this->userRole === 'orangtua') {
            $stmt = $this->conn->prepare(
                "SELECT s.* FROM siswa s 
                 JOIN users u ON (s.id_orang_tua = u.id OR s.kontak_orang_tua = u.email)
                 WHERE u.id = ? 
                 ORDER BY s.nama ASC"
            );
            $stmt->bind_param('i', $this->userId);
        } elseif ($this->userRole === 'siswa') {
            $stmt = $this->conn->prepare(
                "SELECT s.* FROM siswa s 
                 JOIN users u ON s.email = u.email
                 WHERE u.id = ?"
            );
            $stmt->bind_param('i', $this->userId);
        } elseif (in_array($this->userRole, ['admin', 'bk', 'wakasek', 'guru', 'guru_mapel'], true)) {
            $stmt = $this->conn->prepare("SELECT * FROM siswa ORDER BY nama ASC");
        } else {
            return [];
        }
        
        if (!$stmt) return [];
        
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    public function verifySuratAccess($suratId) {
        if (!$this->userId) {
            return false;
        }
        
        if ($this->userRole === 'orangtua') {
            $stmt = $this->conn->prepare(
                "SELECT so.* FROM surat_orang_tua so
                 JOIN pelanggaran p ON p.id_pelanggaran = so.id_pelanggaran
                 JOIN siswa s ON s.id_siswa = p.id_siswa
                 JOIN users u ON (s.id_orang_tua = u.id OR s.kontak_orang_tua = u.email)
                 WHERE so.id_surat_orang_tua = ? AND u.id = ?"
            );
            $stmt->bind_param('ii', $suratId, $this->userId);
        } else {
            $stmt = $this->conn->prepare(
                "SELECT * FROM surat_orang_tua WHERE id_surat_orang_tua = ?"
            );
            $stmt->bind_param('i', $suratId);
        }
        
        if (!$stmt) return false;
        
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
}

?>
