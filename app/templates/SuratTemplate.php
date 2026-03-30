<?php
/**
 * Template Surat Pelanggaran Berjenjang
 * SP1, SP2, SP3, LE
 */

function getTemplateSuratSP($data) {
        $levelSp = $data['level_sp'] ?? 'SP1';
        $nomorSurat = $data['nomor_surat'] ?? date('d/m/Y');
        $tanggalCetak = isset($data['tanggal_cetak']) ? date('d F Y', strtotime($data['tanggal_cetak'])) : date('d F Y');

        $deskripsiLevel = [
                'SP1' => 'SURAT PELANGGARAN PERTAMA (SP1)',
                'SP2' => 'SURAT PELANGGARAN KEDUA (SP2)',
                'SP3' => 'SURAT PELANGGARAN KETIGA (SP3)',
                'LE'  => 'SURAT LEVEL EKSTENSIF (LE)'
        ];

        $judulLevel = $deskripsiLevel[$levelSp] ?? 'SURAT PELANGGARAN';
        $isiSurat = getIsiSuratPerLevel($levelSp, $data);

        $nama = htmlspecialchars($data['nama'] ?? '-');
        $nis = htmlspecialchars($data['nis'] ?? '-');
        $kelas = htmlspecialchars($data['kelas'] ?? '-');
        $namaOrtu = htmlspecialchars($data['nama_orang_tua'] ?? '-');

        $html = <<<HTML
        <!doctype html>
        <html lang="id">
            <head>
                <meta charset="UTF-8" />
                <title>$judulLevel</title>
                <style>
                    @page { size: A4; margin: 0; }
                    body { font-family: "Times New Roman", serif; font-size: 12pt; line-height: 1.5; padding: 0 2cm; }
                    table { border-collapse: collapse; }
                    p { margin: 0 0 8px 0; text-align: justify; }
                    /* header/kop area should always be centred */
                    .kop { text-align: center; margin-bottom: 12px; }
                    .header td { vertical-align: top; }
                    .header td:first-child { width: 80px; }
                    /* tables for identity/details with fixed label column */
                    .identitas{ margin-left: 8px; }
                    .identitas td { padding: 2px 6px; }
                    .identitas td:first-child { width: 160px; text-align:left; }
                    .detail { margin-top: 10px; }
                    .detail td { padding: 2px 6px; }
                    .detail td:first-child { width: 150px; text-align:left; }
                    .label { text-align:left; }
                    .value { text-align:left; }
                    .pembuka { margin-top: 12px; }
                    .keperluan { margin-top: 10px; }
                    .penutup { margin-top: 18px; }
                    .ttd { width: 100%; margin-top: 30px; }
                    .ttd td { width: 50%; vertical-align: top; }
                    .ttd .left { text-align:left; }
                    .ttd .right { text-align:right; }
                    .spasi-ttd { height: 70px; }
                    .data-box { margin: 12px 0 12px 0; padding: 10px; border-left: 6px solid #1976d2; background: #fbfcfd; }
                                        @media print { body { -webkit-print-color-adjust: exact; } }
                </style>
            </head>
            <body>
                <div class="kop">
                    <div style="text-align:center;">
                        <img src="/images/kop_surat.png" alt="Kop Surat" style="max-width:100%;max-height:3cm;height:auto;" onerror="this.style.display='none'">
                    </div>
                    <noscript>
                        <div style="text-align:center;">
                            <h3 style="margin-bottom:4px;">SMK TI BALI GLOBAL DENPASAR</h3>
                            <div style="font-size:11px;">Jl. Tukad Citarum No.44 Denpasar &nbsp;|&nbsp; Telp. (0361) 249434</div>
                        </div>
                    </noscript>
                </div>

                <div style="text-align:center; font-weight:bold; font-size:14px; margin-bottom:4px;">$judulLevel</div>
                <div style="text-align:right; font-size:12px; margin-bottom:12px;">
                    Nomor: <strong>$nomorSurat</strong><br>
                    Tanggal: $tanggalCetak
                </div>

                <table class="identitas" style="margin-bottom:12px; width:100%;">
                    <tr><td class="label">Nama</td><td class="value">: <strong>$nama</strong></td></tr>
                    <tr><td class="label">NIS</td><td class="value">: $nis</td></tr>
                    <tr><td class="label">Kelas</td><td class="value">: $kelas</td></tr>
                </table>

                <div class="pembuka">
                    $isiSurat
                </div>

                <div class="penutup">
                    <p>Demikian surat ini dibuat untuk dapat dipergunakan sebagaimana mestinya.</p>
                </div>

                <table class="ttd">
                    <tr>
                        <td class="left">
                            <div>Mengetahui,<br>Kepala Sekolah</div>
                            <div class="spasi-ttd"></div>
                            <div>______________________</div>
                        </td>
                        <td class="right">
                            <div>Orang Tua/Wali</div>
                            <div class="spasi-ttd"></div>
                            <div><strong>$namaOrtu</strong></div>
                        </td>
                    </tr>
                </table>

                <script>window.print();</script>
            </body>
        </html>
        HTML;

        return $html;
}

function getIsiSuratPerLevel($levelSp, $data) {
    $nama = htmlspecialchars($data['nama']);
    $nis = htmlspecialchars($data['nis']);
    $kelas = htmlspecialchars($data['kelas']);
    $namaJenis = htmlspecialchars($data['nama_jenis']);
    // poin kept for dashboard only; not shown in surat
    $tanggalPelanggaran = date('d F Y', strtotime($data['tanggal_pelanggaran']));
    $keterangan = htmlspecialchars($data['keterangan'] ?? '-');
    $namaOrangTua = htmlspecialchars($data['nama_orang_tua']);
    
    switch ($levelSp) {
        case 'SP1':
            return <<<ISI
            <p>Yang bertanda tangan dibawah ini Kepala Sekolah SMK TI BALI GLOBAL Denpasar menyatakan bahwa:</p>
            
            <table class="detail">
                <tr><td class="label">Nama Siswa</td><td class="value">: <strong>$nama</strong></td></tr>
                <tr><td class="label">NIS</td><td class="value">: $nis</td></tr>
                <tr><td class="label">Kelas</td><td class="value">: $kelas</td></tr>
                <tr><td class="label">Tanggal Pelanggaran</td><td class="value">: $tanggalPelanggaran</td></tr>
                <tr><td class="label">Jenis Pelanggaran</td><td class="value">: $namaJenis</td></tr>
            </table>
            
            <p>Siswa tersebut telah melakukan pelanggaran yang merupakan pelanggaran pertama. Sehubungan dengan hal tersebut, diharapkan kepada Orang Tua/Wali Siswa agar dapat melakukan pembinaan lebih lanjut.</p>
            
            <p>Demikian surat ini dibuat untuk dapat dipergunakan sebagaimana diperlukan.</p>
            ISI;
            
        case 'SP2':
            return <<<ISI
            <p>Yang bertanda tangan dibawah ini Kepala Sekolah SMK TI BALI GLOBAL Denpasar menyatakan bahwa:</p>
            
            <div class="data-siswa">
                <div class="row">
                    <div class="label">Nama Siswa</div>
                    <div class="value">: <strong>$nama</strong></div>
                </div>
                <div class="row">
                    <div class="label">NIS</div>
                    <div class="value">: $nis</div>
                </div>
                <div class="row">
                    <div class="label">Kelas</div>
                    <div class="value">: $kelas</div>
                </div>
                <div class="row">
                    <div class="label">Tanggal Pelanggaran</div>
                    <div class="value">: $tanggalPelanggaran</div>
                </div>
                <div class="row">
                    <div class="label">Jenis Pelanggaran</div>
                    <div class="value">: $namaJenis</div>
                </div>
            </div>
            
            <p><strong>Siswa tersebut telah mengulangi pelanggaran sebelumnya.</strong> Arahan dan pembinaan tidak diindahkan dan 
            siswa masih melakukan tindakan yang tidak sesuai dengan tata tertib sekolah.</p>
            
            <p>Sehubungan dengan hal tersebut, Orang Tua/Wali Siswa diminta untuk mengambil tindakan yang lebih tegas 
            dalam membina anak dan mengarahkan perilakunya sesuai dengan norma dan tata tertib yang berlaku.</p>
            
            <p>Demikian surat ini dibuat untuk dapat dipergunakan sebagaimana diperlukan.</p>
            ISI;
            
        case 'SP3':
            return <<<ISI
            <p>Yang bertanda tangan dibawah ini Kepala Sekolah SMK TI BALI GLOBAL Denpasar menyatakan bahwa:</p>
            
            <table class="detail">
                <tr><td class="label">Nama Siswa</td><td class="value">: <strong>$nama</strong></td></tr>
                <tr><td class="label">NIS</td><td class="value">: $nis</td></tr>
                <tr><td class="label">Kelas</td><td class="value">: $kelas</td></tr>
                <tr><td class="label">Tanggal Pelanggaran</td><td class="value">: $tanggalPelanggaran</td></tr>
                <tr><td class="label">Jenis Pelanggaran</td><td class="value">: $namaJenis</td></tr>
            </table>
            
            <p><strong>Siswa yang bersangkutan telah melakukan pelanggaran yang serius dan sudah menerima dua surat pelanggaran sebelumnya.</strong></p>
            
            <p>Pihak sekolah telah berusaha melakukan pembinaan tetapi siswa masih mengabaikan arahan dari sekolah. Dengan ini kami memberikan SP3 (Surat Pelanggaran Ketiga) dan meminta Orang Tua/Wali Siswa:</p>
            
            <ol style="margin: 15px 0 15px 30px;">
                <li>Mengambil tindakan yang lebih serius dalam membina anak</li>
                <li>Berkomitmen untuk memperbaiki perilaku anak segera</li>
                <li>Siap untuk tindakan lanjutan dari sekolah jika pelanggaran masih berlanjut</li>
            </ol>
            
            <p>Demikian surat ini dibuat untuk dapat dipergunakan sebagaimana diperlukan.</p>
            ISI;
            
        case 'LE':
        default:
            return <<<ISI
            <p>Yang bertanda tangan dibawah ini Kepala Sekolah SMK TI BALI GLOBAL Denpasar menyatakan bahwa:</p>
            
            <table class="detail" style="border-left-color: #d32f2f; background: #ffebee;">
                <tr><td class="label">Nama Siswa</td><td class="value">: <strong style="color: #d32f2f;">$nama</strong></td></tr>
                <tr><td class="label">NIS</td><td class="value">: $nis</td></tr>
                <tr><td class="label">Kelas</td><td class="value">: $kelas</td></tr>
                <tr><td class="label">Tanggal Pelanggaran</td><td class="value">: $tanggalPelanggaran</td></tr>
                <tr><td class="label">Jenis Pelanggaran</td><td class="value">: $namaJenis</td></tr>
            </table>
            
            <p style="color: #d32f2f; font-weight: bold;"><strong>⚠️ LEVEL EKSTENSIF - TINDAKAN FINAL</strong></p>
            
            <p>Siswa telah melalui tahapan SP1, SP2, dan SP3 namun masih tetap melakukan pelanggaran serius. Dengan ini Pihak Sekolah memberikan notifikasi akhir bahwa:</p>
            
            <ol style="margin: 15px 0 15px 30px;">
                <li>Siswa berada pada level ekstensif dengan tingkat pelanggaran tertinggi</li>
                <li>Orang Tua/Wali Siswa dan Siswa HARUS menghadiri rapat final dengan pihak sekolah</li>
                <li>Akan diputuskan tindakan selanjutnya termasuk kemungkinan pemberhentian studi</li>
                <li>Kedua belah pihak harus menandatangani kesepakatan tindakan lanjut</li>
            </ol>
            
            <p style="color: #d32f2f;"><strong>Demikian surat ini dibuat dengan serius untuk dapat dipergunakan sebagaimana diperlukan.</strong></p>
            ISI;
    }
}

function getTemplateSuratPernyataan($data) {
    $nama = htmlspecialchars($data['nama'] ?? '-');
    $nis = htmlspecialchars($data['nis'] ?? '-');
    $kelas = htmlspecialchars($data['kelas'] ?? '-');
    $program = htmlspecialchars($data['program'] ?? '');
    $masalah = nl2br(htmlspecialchars($data['masalah'] ?? '-'));
    $namaOrtu = htmlspecialchars($data['nama_orang_tua'] ?? '-');
    $pekerjaanOrtu = htmlspecialchars($data['pekerjaan_orang_tua'] ?? '');
    $alamatOrtu = htmlspecialchars($data['alamat_orang_tua'] ?? '');
    $kontakOrtu = htmlspecialchars($data['kontak_orang_tua'] ?? '');
    $guruBK = htmlspecialchars($data['nama_guru_bk'] ?? '');
    $guruWali = htmlspecialchars($data['nama_guru_wali'] ?? '');
    $wakasek = htmlspecialchars($data['nama_wakasek'] ?? '');
    $tanggal = isset($data['tanggal_cetak']) ? date('d F Y', strtotime($data['tanggal_cetak'])) : date('d F Y');

    $html = <<<HTML
    <!doctype html>
    <html lang="id">
        <head>
            <meta charset="utf-8" />
            <title>SURAT PERNYATAAN SISWA - $nama</title>
            <style>
                @page { size: A4; margin: 0; }
                body { font-family: "Times New Roman", serif; font-size:12pt; line-height:1.5; margin:0; padding: 0.5cm 2cm 1cm 2cm; }
                p { margin:0 0 6px 0; text-align:justify; }
                .kop { text-align:center; margin-bottom:4px; }
                .identitas { margin:4px 0 6px 0; width:100%; border-collapse:collapse; }
                .identitas td { padding:1px 4px; vertical-align:top; }
                .identitas td:first-child { width:155px; text-align:left; white-space:nowrap; }
                .identitas td:nth-child(2) { width:10px; }
                .masalah-line { border-bottom:1px dotted #333; min-height:1.4em; display:block; margin-bottom:4px; }
                .ttd-wrap { width:100%; border-collapse:collapse; margin-top:10px; page-break-inside:avoid; }
                .ttd-wrap td { vertical-align:top; padding:0 4px; }
                .ttd-center { text-align:center; }
                .spasi { height:55px; display:block; }
                .underline-name { border-top:1px solid #000; display:inline-block; min-width:160px; text-align:center; font-weight:bold; }
                @media print { body { -webkit-print-color-adjust: exact; } }
            </style>
        </head>
        <body>
            <div class="kop">
                <img src="/images/kop_surat.png" alt="Kop Surat" style="max-width:100%;max-height:3cm;height:auto;" onerror="this.style.display='none'">
            </div>

            <div style="text-align:center; font-weight:bold; font-size:13pt; margin-bottom:10px; text-decoration:underline;">SURAT PERNYATAAN SISWA</div>

            <p>Yang bertanda tangan di bawah ini :</p>

            <table class="identitas">
                <tr><td>Nama</td><td>:</td><td>$nama</td></tr>
                <tr><td>NIS</td><td>:</td><td>$nis</td></tr>
                <tr><td>Kelas</td><td>:</td><td>$kelas</td></tr>
                <tr><td>Program Keahlian</td><td>:</td><td>$program</td></tr>
                <tr>
                    <td style="vertical-align:top;">Masalah</td>
                    <td style="vertical-align:top;">:</td>
                    <td>
                        <div style="min-height:3.5em;">$masalah</div>
                    </td>
                </tr>
                <tr><td>Nama Orang Tua</td><td>:</td><td>$namaOrtu</td></tr>
                <tr><td>Pekerjaan</td><td>:</td><td>$pekerjaanOrtu</td></tr>
                <tr><td>Alamat Rumah</td><td>:</td><td>$alamatOrtu</td></tr>
                <tr><td>No. Hp./Telp.</td><td>:</td><td>$kontakOrtu</td></tr>
            </table>

            <p>Menyatakan dan berjanji akan bersungguh-sungguh berubah dan bersedia mentaati aturan dan tata tertib sekolah. Apabila selama masa pembinaan tidak mengalami perubahan, maka siswa yang bersangkutan dikembalikan kepada orang tua/wali.</p>
            <p>Demikian surat pernyataan ini saya buat dengan sesungguhnya tanpa ada tekanan dari siapapun.</p>

            <table class="ttd-wrap">
                <tr>
                    <td style="width:50%;">Mengetahui,<br>Orang Tua/Wali siswa</td>
                    <td style="width:50%; text-align:right;">Denpasar, $tanggal<br>Siswa yang bersangkutan</td>
                </tr>
                <tr>
                    <td><span class="spasi"></span></td>
                    <td><span class="spasi"></span></td>
                </tr>
                <tr>
                    <td><span class="underline-name">$namaOrtu</span></td>
                    <td style="text-align:right;"><span class="underline-name">$nama</span></td>
                </tr>
                <tr><td colspan="2" style="height:10px;"></td></tr>
                <tr>
                    <td>Guru Bimbingan Konseling</td>
                    <td style="text-align:right;">Guru Wali Kelas</td>
                </tr>
                <tr>
                    <td><span class="spasi"></span></td>
                    <td><span class="spasi"></span></td>
                </tr>
                <tr>
                    <td><span class="underline-name">$guruBK</span></td>
                    <td style="text-align:right;"><span class="underline-name">$guruWali</span></td>
                </tr>
                <tr><td colspan="2" style="height:10px;"></td></tr>
                <tr>
                    <td colspan="2" style="text-align:center;">Mengetahui<br>Wakasek Kesiswaan</td>
                </tr>
                <tr><td colspan="2"><span class="spasi"></span></td></tr>
                <tr>
                    <td colspan="2" style="text-align:center;"><span class="underline-name">$wakasek</span></td>
                </tr>
            </table>

            <script>window.print();</script>
        </body>
    </html>
    HTML;

    return $html;
}

function getTemplateSuratPindah($data) {
    $nama = htmlspecialchars($data['nama'] ?? '-');
    $nis = htmlspecialchars($data['nis'] ?? '-');
    $kelas = htmlspecialchars($data['kelas'] ?? '-');
    $alamat = htmlspecialchars($data['alamat'] ?? '-');
    $namaOrtu = htmlspecialchars($data['nama_orang_tua'] ?? '-');
    $alamatOrtu = htmlspecialchars($data['alamat_orang_tua'] ?? '');
    $alasan = htmlspecialchars($data['alasan_pindah'] ?? '-');
    $sekolahTujuan = htmlspecialchars($data['sekolah_tujuan'] ?? '');
    $nomorSurat = htmlspecialchars($data['nomor_surat'] ?? '-');
    $kepalaSekolah = htmlspecialchars($data['kepala_sekolah'] ?? '');
    $tanggal = isset($data['tanggal_pindah']) ? date('d F Y', strtotime($data['tanggal_pindah'])) : date('d F Y');

    $sekolahLine = $sekolahTujuan
        ? "ke <strong>{$sekolahTujuan}</strong>, dengan alasan {$alasan}"
        : "ke sekolah lain, dengan alasan {$alasan}";

    $html = <<<HTML
    <!doctype html>
    <html lang="id">
        <head>
            <meta charset="utf-8" />
            <title>KETERANGAN PINDAH SEKOLAH - $nama</title>
            <style>
                @page { size: A4; margin: 0; }
                body { font-family: "Times New Roman", serif; font-size:12pt; line-height:1.6; margin:0; padding: 2cm; }
                p { margin:0 0 10px 0; text-align:justify; }
                .kop { text-align:center; margin-bottom:12px; }
                .identitas { margin:6px 0 12px 20px; width:auto; border-collapse:collapse; }
                .identitas td { padding:2px 6px; vertical-align:top; }
                .identitas td:first-child { width:160px; text-align:left; white-space:nowrap; }
                .ttd { width:100%; border-collapse:collapse; margin-top:40px; }
                .spasi-ttd { height:80px; display:block; }
                .underline-name { border-top:1px solid #000; display:inline-block; min-width:180px; text-align:center; font-weight:bold; }
                @media print { body { -webkit-print-color-adjust: exact; } }
            </style>
        </head>
        <body>
            <div class="kop">
                <img src="/images/kop_surat.png" alt="Kop Surat" style="max-width:100%;max-height:3cm;height:auto;" onerror="this.style.display='none'">
            </div>

            <div style="text-align:center; font-weight:bold; font-size:13pt; margin-bottom:2px; text-decoration:underline;">KETERANGAN PINDAH SEKOLAH</div>
            <div style="text-align:center; margin-bottom:18px; font-size:11pt;">No : $nomorSurat</div>

            <p>Yang bertanda tangan dibawah ini Kepala SMK TI BALI GLOBAL Denpasar, kecamatan Denpasar Selatan, Kota Denpasar, Provinsi Bali, Menerangkan bahwa :</p>

            <table class="identitas">
                <tr><td>Nama Siswa</td><td>:</td><td><strong>$nama</strong></td></tr>
                <tr><td>Kelas/Program</td><td>:</td><td>$kelas</td></tr>
                <tr><td>NIS</td><td>:</td><td>$nis</td></tr>
                <tr><td>Alamat</td><td>:</td><td>$alamat</td></tr>
            </table>

            <p>Sesuai dengan surat permohonan pindah sekolah dari Orang tua/ Wali siswa</p>

            <table class="identitas">
                <tr><td>Nama</td><td>:</td><td>$namaOrtu</td></tr>
                <tr><td>Alamat</td><td>:</td><td>$alamatOrtu</td></tr>
            </table>

            <p>Telah mengajukan surat permohonan pindah $sekolahLine dan untuk kelengkapan administrasi sudah diselesaikan.</p>

            <p>Demikian surat pindah ini dibuat untuk dipergunakan sebagaimana mestinya.</p>

            <table class="ttd">
                <tr>
                    <td style="width:50%;"></td>
                    <td style="width:50%; text-align:center;">
                        Denpasar, $tanggal<br>
                        Kepala SMK TI Bali Global Denpasar
                        <span class="spasi-ttd"></span>
                        <span class="underline-name">$kepalaSekolah</span>
                    </td>
                </tr>
            </table>

            <script>window.print();</script>
        </body>
    </html>
    HTML;

    return $html;
}

function getTemplateDaftarPelanggaran($data) {
    $rows = $data['rows'] ?? [];
    $filterKelas = htmlspecialchars($data['filter_kelas'] ?? 'Semua Kelas');
    $filterDari = $data['filter_dari'] ?? '';
    $filterSampai = $data['filter_sampai'] ?? '';
    $tanggalCetak = isset($data['tanggal_cetak']) ? date('d F Y', strtotime($data['tanggal_cetak'])) : date('d F Y');

    $mapKategori = function(string $namaJenis): string {
        $lower = strtolower($namaJenis);
        if (strpos($lower, 'seragam') !== false)                                      return 'SS';
        if (strpos($lower, 'hadir') !== false || strpos($lower, 'absen') !== false)   return 'KS';
        if (strpos($lower, 'belajar') !== false || strpos($lower, 'mengajar') !== false || strpos($lower, 'pbm') !== false) return 'PBM';
        if (strpos($lower, 'norma') !== false)                                        return 'PNN';
        if (strpos($lower, 'berat') !== false)                                        return 'PB';
        if (strpos($lower, 'kendaraan') !== false || strpos($lower, 'berkendara') !== false) return 'KB';
        if (strpos($lower, 'upacara') !== false || strpos($lower, 'bendera') !== false) return 'UB';
        return '';
    };

    $cats = ['SS', 'KS', 'PBM', 'PNN', 'PB', 'KB', 'UB'];

    $rowsHtml = '';
    $no = 1;
    foreach ($rows as $row) {
        $tanggal    = htmlspecialchars($row['tanggal']    ?? '-');
        $namaSiswa  = htmlspecialchars($row['nama']       ?? '-');
        $kelas      = htmlspecialchars($row['kelas']      ?? '-');
        $namaJenis  = $row['nama_jenis'] ?? '';
        $ket        = htmlspecialchars($row['keterangan'] ?? '');
        $cat        = $mapKategori($namaJenis);

        if ($cat === '' && $namaJenis !== '') {
            $ket = trim(htmlspecialchars($namaJenis) . ($ket ? (' – ' . $ket) : ''));
        }

        $catCols = '';
        foreach ($cats as $c) {
            $tick = ($c === $cat) ? '&#10003;' : '';
            $catCols .= "<td style=\"border:1px solid #555; text-align:center; padding:3px 2px;\">{$tick}</td>";
        }

        $bg = ($no % 2 === 0) ? ' background:#f5f5f5;' : '';
        $rowsHtml .= "<tr style=\"{$bg}\">
            <td style=\"border:1px solid #555; text-align:center; padding:3px 4px;\">{$tanggal}</td>
            <td style=\"border:1px solid #555; padding:3px 5px;\">{$namaSiswa}</td>
            <td style=\"border:1px solid #555; padding:3px 4px; text-align:center;\">{$kelas}</td>
            {$catCols}
            <td style=\"border:1px solid #555; padding:3px 5px;\">{$ket}</td>
        </tr>";
        $no++;
    }

    if (!$rowsHtml) {
        $rowsHtml = '<tr><td colspan="11" style="padding:10px; text-align:center; border:1px solid #555; color:#999;">Tidak ada data pelanggaran.</td></tr>';
    }

    $filterInfo = $filterKelas;
    if ($filterDari || $filterSampai) {
        $filterInfo .= ' | ' . ($filterDari ?: '...') . ' s/d ' . ($filterSampai ?: '...');
    }

    $html = <<<HTML
    <!doctype html>
    <html lang="id">
        <head>
            <meta charset="utf-8" />
            <title>Daftar Pelanggaran Siswa</title>
            <style>
                @page { size: A4 landscape; margin: 0; }
                body { font-family: "Times New Roman", serif; font-size:10.5pt; line-height:1.4; margin:0; padding: 1.5cm; }
                .kop { text-align:center; margin-bottom:8px; }
                table { border-collapse:collapse; width:100%; }
                .legend { margin-top:12px; font-size:9.5pt; }
                .legend span { margin-right:18px; }
                @media print { body { -webkit-print-color-adjust: exact; } }
            </style>
        </head>
        <body>
            <div class="kop">
                <img src="/images/kop_surat.png" alt="Kop Surat" style="max-width:100%;max-height:3cm;height:auto;" onerror="this.style.display='none'">
            </div>

            <div style="text-align:center; font-weight:bold; font-size:12pt; margin-bottom:10px; text-decoration:underline;">DAFTAR PELANGGARAN SISWA</div>

            <table>
                <thead>
                    <tr>
                        <th rowspan="2" style="border:1px solid #555; padding:4px 6px; width:9%;">TGL</th>
                        <th rowspan="2" style="border:1px solid #555; padding:4px 6px; width:18%;">NAMA</th>
                        <th rowspan="2" style="border:1px solid #555; padding:4px 6px; width:8%;">KELAS</th>
                        <th colspan="7" style="border:1px solid #555; padding:4px 6px;">JENIS PELANGGARAN</th>
                        <th rowspan="2" style="border:1px solid #555; padding:4px 6px;">KETERANGAN</th>
                    </tr>
                    <tr>
                        <th style="border:1px solid #555; padding:3px 4px; width:5%;">SS</th>
                        <th style="border:1px solid #555; padding:3px 4px; width:5%;">KS</th>
                        <th style="border:1px solid #555; padding:3px 4px; width:5%;">PBM</th>
                        <th style="border:1px solid #555; padding:3px 4px; width:5%;">PNN</th>
                        <th style="border:1px solid #555; padding:3px 4px; width:5%;">PB</th>
                        <th style="border:1px solid #555; padding:3px 4px; width:5%;">KB</th>
                        <th style="border:1px solid #555; padding:3px 4px; width:5%;">UB</th>
                    </tr>
                </thead>
                <tbody>
                    $rowsHtml
                </tbody>
            </table>

            <div class="legend">
                <span>SS : Seragam Sekolah</span>
                <span>KS : Kehadiran Di Sekolah</span>
                <span>PBM : Proses Belajar Mengajar</span>
                <span>PNN : Pelanggaran Norma Norma</span>
                <span>PB : Pelanggaran Berat</span>
                <span>KB : Kesopanan Berkendaraan</span>
                <span>UB : Upacara Bendera</span>
            </div>

            <script>window.print();</script>
        </body>
    </html>
    HTML;

    return $html;
}

function getTemplateSuratPemberhentian($data) {
        $nomorSurat = $data['nomor_surat'] ?? '-';
        $tanggal = isset($data['tanggal_cetak']) ? date('d F Y', strtotime($data['tanggal_cetak'])) : date('d F Y');
        $nama = htmlspecialchars($data['nama'] ?? '-');
        $nis = htmlspecialchars($data['nis'] ?? '-');
        $kelas = htmlspecialchars($data['kelas'] ?? '-');
        $namaOrtu = htmlspecialchars($data['nama_orang_tua'] ?? '-');
        $alasan = htmlspecialchars($data['keterangan'] ?? 'Melanggar tata tertib sekolah secara berulang dan berat.');

        $html = <<<HTML
        <!doctype html>
        <html lang="id">
            <head>
                <meta charset="utf-8" />
                <title>SURAT PEMBERHENTIAN - $nama</title>
                <style>
                    @page { size: A4; margin: 0; }
                    body { font-family: "Times New Roman", serif; font-size:12pt; line-height:1.5; padding: 0 2cm; }
                    p { margin:0 0 8px 0; text-align:justify; }
                    .kop { text-align:center; margin-bottom:12px; }
                    .content { margin-top:12px; }
                    .data-box { margin:12px 0; padding:10px; border-left:6px solid #d32f2f; background:#fff0f0; }
                    .ttd { width:100%; margin-top:28px; }
                    .ttd td { width:50%; vertical-align:top; }
                    .ttd .left { text-align:left; }
                    .ttd .right { text-align:right; }
                </style>
            </head>
            <body>
                <div class="kop">
                    <div style="text-align:center;">
                        <img src="/images/kop_surat.png" alt="Kop Surat" style="max-width:100%;max-height:3cm;height:auto;" onerror="this.style.display='none'">
                    </div>
                    <noscript>
                        <div style="text-align:center;">
                            <h3 style="margin-bottom:4px;">SMK TI BALI GLOBAL DENPASAR</h3>
                            <div style="font-size:11px;">Jl. Tukad Citarum No.44 Denpasar &nbsp;|&nbsp; Telp. (0361) 249434</div>
                        </div>
                    </noscript>
                </div>

                <div style="text-align:center; font-weight:bold; margin-top:6px;">SURAT PEMBERHENTIAN</div>
                <div style="text-align:center; margin-top:6px;">Nomor: <strong>$nomorSurat</strong></div>
                <div style="text-align:center; margin-top:6px;">Tanggal: $tanggal</div>

                <div class="content">
                    <p>Berdasarkan hasil rapat dan pertimbangan dari pihak sekolah, serta setelah melalui serangkaian pembinaan, dengan sangat menyesal diumumkan bahwa:</p>

                    <div class="data-box">
                        <p>Nama: <strong>$nama</strong></p>
                        <p>NIS: $nis</p>
                        <p>Kelas: $kelas</p>
                        <p>Orang Tua/Wali: $namaOrtu</p>
                        <p>Alasan: $alasan</p>
                    </div>

                    <p>Dengan ini dinyatakan bahwa siswa tersebut <strong>DIHENTIKAN DARI PENDIDIKAN</strong> di SMK TI BALI GLOBAL DENPASAR terhitung sejak tanggal surat ini dikeluarkan atau sesuai keputusan lebih lanjut.</p>

                    <p>Keputusan ini diambil setelah mempertimbangkan aspek keselamatan, ketertiban, dan keberlangsungan proses belajar mengajar bagi siswa lain. Hak administrasi, pembayaran, dan mekanisme banding tercantum pada catatan sekolah.</p>

                    <p>Demikian pemberitahuan ini disampaikan agar dapat dipergunakan sebagaimana mestinya.</p>
                </div>

                <table class="ttd">
                    <tr>
                        <td style="text-align:left;">
                            <div>Kepala Sekolah</div>
                            <div style="height:70px;"></div>
                            <div>______________________</div>
                        </td>
                        <td style="text-align:right;">
                            <div>Orang Tua/Wali</div>
                            <div style="height:70px;"></div>
                            <div><strong>$namaOrtu</strong></div>
                        </td>
                    </tr>
                </table>

                <script>window.print();</script>
            </body>
        </html>
        HTML;

        return $html;
}
