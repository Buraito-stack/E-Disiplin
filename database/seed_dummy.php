<?php
/**
 * Dummy Data Seeder
 * Run once via CLI: php database/seed_dummy.php
 * Or via browser: http://localhost/E-Disiplin/database/seed_dummy.php
 *   (remove the die() guard below for browser access)
 */

// Uncomment the line below to block browser access:
// if (php_sapi_name() !== 'cli') { die('CLI only.'); }

$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (strpos($line, '=') !== false && $line[0] !== '#') {
            [$k, $v] = explode('=', $line, 2);
            putenv(trim($k) . '=' . trim($v));
        }
    }
}

$host = getenv('DB_HOST') ?: 'localhost';
$db   = getenv('DB_NAME') ?: 'e_disiplin';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error . "\n");
}
$conn->set_charset('utf8mb4');

echo "=== E-Disiplin Dummy Data Seeder ===\n\n";

// -----------------------------------------------------------------------
// 1. Migrate columns (MySQL-compatible, INFORMATION_SCHEMA)
// -----------------------------------------------------------------------
function addColIfMissing(mysqli $conn, string $table, string $column, string $def): void {
    $db  = $conn->query("SELECT DATABASE()")->fetch_row()[0];
    $res = $conn->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                         WHERE TABLE_SCHEMA='{$db}' AND TABLE_NAME='{$table}' AND COLUMN_NAME='{$column}'");
    if ($res && $res->fetch_row()[0] == 0) {
        $conn->query("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$def}");
        echo "  + Added column `{$table}`.`{$column}`\n";
    }
}

addColIfMissing($conn, 'surat_perjanjian', 'nomor_surat',      'VARCHAR(100) DEFAULT NULL');
addColIfMissing($conn, 'surat_perjanjian', 'nama_guru_bk',     'VARCHAR(150) DEFAULT NULL');
addColIfMissing($conn, 'surat_perjanjian', 'nama_guru_wali',   'VARCHAR(150) DEFAULT NULL');
addColIfMissing($conn, 'surat_perjanjian', 'nama_wakasek',     'VARCHAR(150) DEFAULT NULL');
addColIfMissing($conn, 'surat_perjanjian', 'program_keahlian', 'VARCHAR(100) DEFAULT NULL');
addColIfMissing($conn, 'surat_pindah',     'nomor_surat',      'VARCHAR(100) DEFAULT NULL');
addColIfMissing($conn, 'surat_pindah',     'sekolah_tujuan',   'VARCHAR(200) DEFAULT NULL');
addColIfMissing($conn, 'surat_pindah',     'kepala_sekolah',   'VARCHAR(150) DEFAULT NULL');
echo "[OK] Migrasi kolom selesai.\n";

// -----------------------------------------------------------------------
// 2. Update existing surat_perjanjian records (IDs 1-5)
// -----------------------------------------------------------------------
$pernyataanUpdates = [
    1 => [
        'nomor_surat'    => '001/SMK-TIG/SP/1/2026',
        'nama_guru_bk'   => 'I Gusti Ayu Rinjani, M.Pd',
        'nama_guru_wali' => 'Ni Luh Putu Sari, S.Pd',
        'nama_wakasek'   => 'Bagus Putu Eka Wijaya, S.Kom',
        'program_keahlian' => 'Teknik Komputer Jaringan',
    ],
    2 => [
        'nomor_surat'    => '002/SMK-TIG/SP/1/2026',
        'nama_guru_bk'   => 'I Gusti Ayu Rinjani, M.Pd',
        'nama_guru_wali' => 'I Made Sudana, S.Pd',
        'nama_wakasek'   => 'Bagus Putu Eka Wijaya, S.Kom',
        'program_keahlian' => 'Rekayasa Perangkat Lunak',
    ],
    3 => [
        'nomor_surat'    => '003/SMK-TIG/SP/1/2026',
        'nama_guru_bk'   => 'I Gusti Ayu Rinjani, M.Pd',
        'nama_guru_wali' => 'Ni Nyoman Dewi, S.Pd',
        'nama_wakasek'   => 'Bagus Putu Eka Wijaya, S.Kom',
        'program_keahlian' => 'Teknik Komputer Jaringan',
    ],
    4 => [
        'nomor_surat'    => '004/SMK-TIG/SP/1/2026',
        'nama_guru_bk'   => 'I Gusti Ayu Rinjani, M.Pd',
        'nama_guru_wali' => 'I Wayan Gede, S.Pd',
        'nama_wakasek'   => 'Bagus Putu Eka Wijaya, S.Kom',
        'program_keahlian' => 'Multimedia',
    ],
    5 => [
        'nomor_surat'    => '005/SMK-TIG/SP/1/2026',
        'nama_guru_bk'   => 'I Gusti Ayu Rinjani, M.Pd',
        'nama_guru_wali' => 'Ni Ketut Ayu Sari, S.Pd',
        'nama_wakasek'   => 'Bagus Putu Eka Wijaya, S.Kom',
        'program_keahlian' => 'Rekayasa Perangkat Lunak',
    ],
];

$stmtUpP = $conn->prepare(
    "UPDATE surat_perjanjian
     SET nomor_surat=?, nama_guru_bk=?, nama_guru_wali=?, nama_wakasek=?, program_keahlian=?
     WHERE id_perjanjian=? AND (nomor_surat IS NULL OR nomor_surat='')"
);

foreach ($pernyataanUpdates as $id => $d) {
    $stmtUpP->bind_param('sssssi',
        $d['nomor_surat'], $d['nama_guru_bk'], $d['nama_guru_wali'],
        $d['nama_wakasek'], $d['program_keahlian'], $id
    );
    $stmtUpP->execute();
}
echo "[OK] Update surat_perjanjian (5 record lama).\n";

// -----------------------------------------------------------------------
// 3. Insert new surat_perjanjian records
// -----------------------------------------------------------------------
$newPernyataan = [
    [
        'id_siswa' => 6,
        'tanggal'  => '2026-02-10',
        'masalah'  => 'Siswa terbukti merokok di area sekolah pada jam pelajaran. Dengan ini siswa berjanji untuk tidak mengulangi perbuatan tersebut dan bersedia menerima sanksi lebih berat jika dilanggar.',
        'nomor'    => '006/SMK-TIG/SP/2/2026',
        'guru_bk'  => 'I Gusti Ayu Rinjani, M.Pd',
        'guru_wali'=> 'Ni Luh Putu Sari, S.Pd',
        'wakasek'  => 'Bagus Putu Eka Wijaya, S.Kom',
        'program'  => 'Teknik Komputer Jaringan',
    ],
    [
        'id_siswa' => 7,
        'tanggal'  => '2026-02-15',
        'masalah'  => 'Siswa sering tidak hadir tanpa keterangan (alpa) selama 7 hari dalam sebulan. Siswa berkomitmen untuk hadir tepat waktu dan memberikan surat izin jika berhalangan.',
        'nomor'    => '007/SMK-TIG/SP/2/2026',
        'guru_bk'  => 'I Gusti Ayu Rinjani, M.Pd',
        'guru_wali'=> 'I Made Sudana, S.Pd',
        'wakasek'  => 'Bagus Putu Eka Wijaya, S.Kom',
        'program'  => 'Rekayasa Perangkat Lunak',
    ],
    [
        'id_siswa' => 8,
        'tanggal'  => '2026-02-20',
        'masalah'  => 'Siswa terlibat dalam kasus perundungan (bullying) terhadap teman sekelas. Siswa menyatakan penyesalannya dan berjanji tidak akan mengulangi tindakan tersebut.',
        'nomor'    => '008/SMK-TIG/SP/2/2026',
        'guru_bk'  => 'I Gusti Ayu Rinjani, M.Pd',
        'guru_wali'=> 'Ni Nyoman Dewi, S.Pd',
        'wakasek'  => 'Bagus Putu Eka Wijaya, S.Kom',
        'program'  => 'Multimedia',
    ],
    [
        'id_siswa' => 9,
        'tanggal'  => '2026-03-01',
        'masalah'  => 'Siswa kedapatan membawa dan menggunakan handphone saat ujian berlangsung. Siswa berjanji tidak membawa perangkat elektronik ke ruang ujian.',
        'nomor'    => '009/SMK-TIG/SP/3/2026',
        'guru_bk'  => 'I Gusti Ayu Rinjani, M.Pd',
        'guru_wali'=> 'I Wayan Gede, S.Pd',
        'wakasek'  => 'Bagus Putu Eka Wijaya, S.Kom',
        'program'  => 'Teknik Komputer Jaringan',
    ],
    [
        'id_siswa' => 10,
        'tanggal'  => '2026-03-03',
        'masalah'  => 'Siswa merusak fasilitas sekolah (kursi kelas) secara sengaja. Siswa bersedia mengganti fasilitas yang rusak dan berjanji menjaga fasilitas sekolah.',
        'nomor'    => '010/SMK-TIG/SP/3/2026',
        'guru_bk'  => 'I Gusti Ayu Rinjani, M.Pd',
        'guru_wali'=> 'Ni Ketut Ayu Sari, S.Pd',
        'wakasek'  => 'Bagus Putu Eka Wijaya, S.Kom',
        'program'  => 'Rekayasa Perangkat Lunak',
    ],
];

$stmtInsP = $conn->prepare(
    "INSERT INTO surat_perjanjian
     (id_siswa, tanggal_perjanjian, isi_perjanjian, nomor_surat, nama_guru_bk, nama_guru_wali, nama_wakasek, program_keahlian)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
);

$inserted = 0;
foreach ($newPernyataan as $d) {
    // check if this siswa already has a record on that date to avoid dupes on re-run
    $chk = $conn->prepare("SELECT id_perjanjian FROM surat_perjanjian WHERE id_siswa=? AND tanggal_perjanjian=? LIMIT 1");
    $chk->bind_param('is', $d['id_siswa'], $d['tanggal']);
    $chk->execute();
    $chk->store_result();
    if ($chk->num_rows > 0) { $chk->close(); continue; }
    $chk->close();

    $stmtInsP->bind_param('isssssss',
        $d['id_siswa'], $d['tanggal'], $d['masalah'], $d['nomor'],
        $d['guru_bk'], $d['guru_wali'], $d['wakasek'], $d['program']
    );
    $stmtInsP->execute();
    $inserted++;
}
echo "[OK] Insert surat_perjanjian baru: {$inserted} record.\n";

// -----------------------------------------------------------------------
// 4. Update existing surat_pindah records (IDs 1-5)
// -----------------------------------------------------------------------
$pindahUpdates = [
    1 => [
        'nomor_surat'    => '001/SMKTIG/KPK/2/2026',
        'sekolah_tujuan' => 'SMK Negeri 1 Denpasar',
        'kepala_sekolah' => 'Drs. I Wayan Sukadana, M.Pd',
    ],
    2 => [
        'nomor_surat'    => '002/SMKTIG/KPK/2/2026',
        'sekolah_tujuan' => 'SMA Negeri 4 Denpasar',
        'kepala_sekolah' => 'Drs. I Wayan Sukadana, M.Pd',
    ],
    3 => [
        'nomor_surat'    => '003/SMKTIG/KPK/2/2026',
        'sekolah_tujuan' => 'SMK Negeri 3 Denpasar',
        'kepala_sekolah' => 'Drs. I Wayan Sukadana, M.Pd',
    ],
    4 => [
        'nomor_surat'    => '004/SMKTIG/KPK/2/2026',
        'sekolah_tujuan' => '',
        'kepala_sekolah' => 'Drs. I Wayan Sukadana, M.Pd',
    ],
    5 => [
        'nomor_surat'    => '005/SMKTIG/KPK/2/2026',
        'sekolah_tujuan' => 'SMK Kesehatan Bali',
        'kepala_sekolah' => 'Drs. I Wayan Sukadana, M.Pd',
    ],
];

$stmtUpPi = $conn->prepare(
    "UPDATE surat_pindah
     SET nomor_surat=?, sekolah_tujuan=?, kepala_sekolah=?
     WHERE id_surat_pindah=? AND (nomor_surat IS NULL OR nomor_surat='')"
);

foreach ($pindahUpdates as $id => $d) {
    $stmtUpPi->bind_param('sssi', $d['nomor_surat'], $d['sekolah_tujuan'], $d['kepala_sekolah'], $id);
    $stmtUpPi->execute();
}
echo "[OK] Update surat_pindah (5 record lama).\n";

// -----------------------------------------------------------------------
// 5. Insert new surat_pindah records
// -----------------------------------------------------------------------
$newPindah = [
    [
        'id_siswa'       => 11,
        'tanggal'        => '2026-02-12',
        'alasan'         => 'Orang tua siswa pindah kerja ke luar kota sehingga siswa harus mengikuti dan melanjutkan pendidikan di daerah baru.',
        'nomor'          => '006/SMKTIG/KPK/2/2026',
        'sekolah_tujuan' => 'SMK Negeri 1 Surabaya',
        'kepala_sekolah' => 'Drs. I Wayan Sukadana, M.Pd',
    ],
    [
        'id_siswa'       => 12,
        'tanggal'        => '2026-02-18',
        'alasan'         => 'Siswa mengalami kondisi kesehatan yang memerlukan perawatan khusus dan harus pindah ke sekolah yang lebih dekat dengan rumah sakit rujukan.',
        'nomor'          => '007/SMKTIG/KPK/2/2026',
        'sekolah_tujuan' => 'SMK Kesehatan Denpasar',
        'kepala_sekolah' => 'Drs. I Wayan Sukadana, M.Pd',
    ],
    [
        'id_siswa'       => 13,
        'tanggal'        => '2026-03-01',
        'alasan'         => 'Atas permintaan orang tua yang menginginkan siswa melanjutkan pendidikan di sekolah yang memiliki program keahlian berbeda sesuai minat siswa.',
        'nomor'          => '008/SMKTIG/KPK/3/2026',
        'sekolah_tujuan' => 'SMK Pariwisata Bali',
        'kepala_sekolah' => 'Drs. I Wayan Sukadana, M.Pd',
    ],
];

$stmtInsPi = $conn->prepare(
    "INSERT INTO surat_pindah
     (id_siswa, tanggal_pindah, alasan_pindah, nomor_surat, sekolah_tujuan, kepala_sekolah)
     VALUES (?, ?, ?, ?, ?, ?)"
);

$inserted2 = 0;
foreach ($newPindah as $d) {
    $chk = $conn->prepare("SELECT id_surat_pindah FROM surat_pindah WHERE id_siswa=? AND tanggal_pindah=? LIMIT 1");
    $chk->bind_param('is', $d['id_siswa'], $d['tanggal']);
    $chk->execute();
    $chk->store_result();
    if ($chk->num_rows > 0) { $chk->close(); continue; }
    $chk->close();

    $stmtInsPi->bind_param('isssss',
        $d['id_siswa'], $d['tanggal'], $d['alasan'],
        $d['nomor'], $d['sekolah_tujuan'], $d['kepala_sekolah']
    );
    $stmtInsPi->execute();
    $inserted2++;
}
echo "[OK] Insert surat_pindah baru: {$inserted2} record.\n";

// -----------------------------------------------------------------------
// 6. Add more pelanggaran dummy data (Feb–Mar 2026)
// -----------------------------------------------------------------------
$newPelanggaran = [
    ['id_siswa' => 6,  'id_jenis' => 1, 'tanggal' => '2026-02-17', 'ket' => 'Terlambat 15 menit'],
    ['id_siswa' => 7,  'id_jenis' => 3, 'tanggal' => '2026-02-17', 'ket' => 'Kedapatan merokok di kantin'],
    ['id_siswa' => 8,  'id_jenis' => 5, 'tanggal' => '2026-02-18', 'ket' => 'Mengejek teman di media sosial'],
    ['id_siswa' => 9,  'id_jenis' => 6, 'tanggal' => '2026-02-18', 'ket' => 'Tidak memakai dasi seragam'],
    ['id_siswa' => 10, 'id_jenis' => 2, 'tanggal' => '2026-02-19', 'ket' => 'Bolos jam pelajaran ke-3 dan ke-4'],
    ['id_siswa' => 11, 'id_jenis' => 7, 'tanggal' => '2026-02-19', 'ket' => 'Rambut diwarnai tidak sesuai aturan'],
    ['id_siswa' => 12, 'id_jenis' => 8, 'tanggal' => '2026-02-20', 'ket' => 'Tidak mengumpulkan tugas PKK'],
    ['id_siswa' => 13, 'id_jenis' => 1, 'tanggal' => '2026-02-21', 'ket' => 'Terlambat upacara bendera'],
    ['id_siswa' => 14, 'id_jenis' => 4, 'tanggal' => '2026-02-24', 'ket' => 'Mencoret meja ruang kelas'],
    ['id_siswa' => 15, 'id_jenis' => 2, 'tanggal' => '2026-02-24', 'ket' => 'Tidak hadir tanpa izin 2 hari'],
    ['id_siswa' => 6,  'id_jenis' => 6, 'tanggal' => '2026-02-25', 'ket' => 'Seragam tidak dimasukkan'],
    ['id_siswa' => 7,  'id_jenis' => 8, 'tanggal' => '2026-02-25', 'ket' => 'Tugas matematika tidak dikerjakan'],
    ['id_siswa' => 8,  'id_jenis' => 1, 'tanggal' => '2026-02-26', 'ket' => 'Terlambat masuk setelah istirahat'],
    ['id_siswa' => 9,  'id_jenis' => 3, 'tanggal' => '2026-02-27', 'ket' => 'Merokok di belakang laboratorium'],
    ['id_siswa' => 10, 'id_jenis' => 7, 'tanggal' => '2026-02-28', 'ket' => 'Rambut melewati batas aturan'],
    ['id_siswa' => 11, 'id_jenis' => 5, 'tanggal' => '2026-03-01', 'ket' => 'Mengintimidasi adik kelas'],
    ['id_siswa' => 12, 'id_jenis' => 1, 'tanggal' => '2026-03-02', 'ket' => 'Terlambat 20 menit'],
    ['id_siswa' => 13, 'id_jenis' => 4, 'tanggal' => '2026-03-03', 'ket' => 'Merusak kursi kelas'],
    ['id_siswa' => 14, 'id_jenis' => 6, 'tanggal' => '2026-03-03', 'ket' => 'Tidak memakai sepatu hitam'],
    ['id_siswa' => 15, 'id_jenis' => 2, 'tanggal' => '2026-03-04', 'ket' => 'Bolos pada jam praktikum'],
];

$stmtInsPel = $conn->prepare(
    "INSERT INTO pelanggaran (id_siswa, id_jenis, tanggal, keterangan) VALUES (?, ?, ?, ?)"
);

$insertedPel = 0;
foreach ($newPelanggaran as $p) {
    $chk = $conn->prepare("SELECT id_pelanggaran FROM pelanggaran WHERE id_siswa=? AND id_jenis=? AND tanggal=? LIMIT 1");
    $chk->bind_param('iis', $p['id_siswa'], $p['id_jenis'], $p['tanggal']);
    $chk->execute();
    $chk->store_result();
    if ($chk->num_rows > 0) { $chk->close(); continue; }
    $chk->close();

    $stmtInsPel->bind_param('iiss', $p['id_siswa'], $p['id_jenis'], $p['tanggal'], $p['ket']);
    $stmtInsPel->execute();
    $insertedPel++;
}
echo "[OK] Insert pelanggaran baru: {$insertedPel} record.\n";

$conn->close();

echo "\n=== Seeder selesai! ===\n";
echo "Buka: http://localhost/E-Disiplin/public/pelanggaran.php\n";
