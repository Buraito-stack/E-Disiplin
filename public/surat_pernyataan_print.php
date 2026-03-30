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

$fields = [
    'nama','nis','kelas','program','masalah',
    'nama_orang_tua','pekerjaan_orang_tua','alamat_orang_tua','kontak_orang_tua',
    'nama_guru_bk','nama_guru_wali','nomor_surat','tanggal_cetak'
];

$data = [];
foreach ($fields as $f) {
    if (isset($_GET[$f])) {
        $data[$f] = $_GET[$f];
    }
}

require_once __DIR__ . '/../app/templates/SuratTemplate.php';
echo getTemplateSuratPernyataan($data);
