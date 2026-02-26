<?php

require_once __DIR__ . '/../app/config/bootstrap.php';
require_once __DIR__ . '/../app/config/Database.php';
require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/middleware/RoleMiddleware.php';

$database = new Database();
$conn = $database->connect();

$authController = new AuthController(null);

if (!$authController->isLoggedIn()) {
    header('Location: index.php');
    exit;
}

requireRoles(['admin', 'guru', 'bk']);

// the print page supports two modes:
//  * SP letter (default) - fetched from surat_orang_tua table via id
//  * Pernyataan letter (type=pernyataan) - data supplied directly via GET params

$type = $_GET['type'] ?? 'sp';

$data = null;
if ($type === 'pernyataan') {
    // copy any parameter we know from GET
    $keys = ['nama','nis','kelas','program','masalah',
             'nama_orang_tua','pekerjaan_orang_tua','alamat_orang_tua','kontak_orang_tua',
             'nama_guru_bk','nama_guru_wali','nomor_surat','tanggal_cetak'];
    foreach ($keys as $k) {
        if (isset($_GET[$k])) {
            $data[$k] = $_GET[$k];
        }
    }
    if (!$data) {
        echo 'Tidak ada data pernyataan yang diberikan.';
        exit;
    }
} else {
    $idSurat = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($idSurat > 0) {
        $stmt = $conn->prepare(
            "SELECT so.id_surat_orang_tua, so.tanggal_cetak, so.status_kirim, so.level_sp, so.nomor_surat,
                    p.tanggal AS tanggal_pelanggaran, p.keterangan,
                    s.nama, s.nis, s.kelas, s.alamat, s.nama_orang_tua, s.kontak_orang_tua, s.pekerjaan_orang_tua, s.alamat_orang_tua,
                    j.nama_jenis, j.poin
             FROM surat_orang_tua so
             JOIN pelanggaran p ON p.id_pelanggaran = so.id_pelanggaran
             JOIN siswa s ON s.id_siswa = p.id_siswa
             JOIN jenis_pelanggaran j ON j.id_jenis = p.id_jenis
             WHERE so.id_surat_orang_tua = ?");
        if ($stmt) {
            $stmt->bind_param('i', $idSurat);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result) {
                $data = $result->fetch_assoc();
            }
        }
    }
    if (!$data) {
        echo 'Data surat tidak ditemukan.';
        exit;
    }
}

require_once __DIR__ . '/app/templates/SuratTemplate.php';
if ($type === 'pernyataan') {
    echo getTemplateSuratPernyataan($data);
} else {
    echo getTemplateSuratSP($data);
}
?>