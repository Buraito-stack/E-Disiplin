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

$idSurat = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($idSurat <= 0) {
    echo 'ID surat tidak valid.';
    exit;
}

$stmt = $conn->prepare(
    "SELECT sp.id_surat_pindah, sp.tanggal_pindah, sp.alasan_pindah, sp.nomor_surat,
            sp.sekolah_tujuan, sp.kepala_sekolah,
            s.nama, s.nis, s.kelas, s.alamat, s.nama_orang_tua, s.alamat_orang_tua
     FROM surat_pindah sp
     JOIN siswa s ON s.id_siswa = sp.id_siswa
     WHERE sp.id_surat_pindah = ?"
);

if (!$stmt) {
    echo 'Gagal memuat surat.';
    exit;
}

$stmt->bind_param('i', $idSurat);
$stmt->execute();
$result = $stmt->get_result();
$data = $result ? $result->fetch_assoc() : null;

if (!$data) {
    echo 'Data surat tidak ditemukan.';
    exit;
}

if (empty($data['nomor_surat'])) {
    $bulan = date('n', strtotime($data['tanggal_pindah']));
    $tahun = date('Y', strtotime($data['tanggal_pindah']));
    $data['nomor_surat'] = sprintf('%03d/SMKTIG/KPK/%s/%s', $data['id_surat_pindah'], $bulan, $tahun);
}

require_once __DIR__ . '/../app/templates/SuratTemplate.php';
echo getTemplateSuratPindah($data);
?>
