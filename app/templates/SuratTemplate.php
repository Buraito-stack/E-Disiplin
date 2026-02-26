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

        // sanitize commonly used fields
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
                    @page { size: A4; margin: 0cm 2cm; }
                    body { font-family: "Times New Roman", serif; font-size: 12pt; line-height: 1.5; }
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
                    <!-- try to use provided kop image if available; place image at /images/kop_surat.png -->
                    <div style="text-align:center;">
                        <img src="/images/kop_surat.png" alt="Kop Surat" style="max-width:100%;height:auto;" onerror="this.style.display='none'">
                    </div>
                    <noscript>
                        <div style="text-align:center;">
                            <h3 style="margin-bottom:4px;">SMK TI BALI GLOBAL DENPASAR</h3>
                            <div style="font-size:11px;">Jl. Tukad Citarum No.44 Denpasar &nbsp;|&nbsp; Telp. (0361) 249434</div>
                        </div>
                    </noscript>
                </div>

                <!-- heading and metadata -->
                <div style="text-align:center; font-weight:bold; font-size:14px; margin-bottom:4px;">$judulLevel</div>
                <div style="text-align:right; font-size:12px; margin-bottom:12px;">
                    Nomor: <strong>$nomorSurat</strong><br>
                    Tanggal: $tanggalCetak
                </div>

                <!-- student identity table -->
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

/**
 * Surat Pernyataan Siswa
 *
 * Required data keys (all optional but recommended):
 *   'nama'               => siswa nama lengkap
 *   'nis'                => nomor induk siswa
 *   'kelas'              => kelas/tingkat
 *   'program'            => program keahlian (mis. Software Engineering)
 *   'masalah'            => uraian singkat masalah/pelanggaran
 *   'nama_orang_tua'     => nama orang tua/wali
 *   'pekerjaan_orang_tua'=> pekerjaan orang tua
 *   'alamat_orang_tua'   => alamat orang tua
 *   'kontak_orang_tua'   => nomor HP/telepon
 *   'nama_guru_bk'       => nama guru bimbingan konseling (jika ada)
 *   'nama_guru_wali'     => nama guru wali kelas (jika ada)
 *   'tanggal_cetak'      => tanggal surat (string parseable by strtotime)
 *   'nomor_surat'        => nomor surat (opsional)
 *
 * This template follows the alignment rules provided by the user:
 * header centered, title centered, identity table left‑aligned,
 * paragraphs justified, signature columns left/right, bottom center.
 */
function getTemplateSuratPernyataan($data) {
    $nama = htmlspecialchars($data['nama'] ?? '-');
    $nis = htmlspecialchars($data['nis'] ?? '-');
    $kelas = htmlspecialchars($data['kelas'] ?? '-');
    $program = htmlspecialchars($data['program'] ?? '');
    $masalah = htmlspecialchars($data['masalah'] ?? '-');
    $namaOrtu = htmlspecialchars($data['nama_orang_tua'] ?? '-');
    $pekerjaanOrtu = htmlspecialchars($data['pekerjaan_orang_tua'] ?? '');
    $alamatOrtu = htmlspecialchars($data['alamat_orang_tua'] ?? '');
    $kontakOrtu = htmlspecialchars($data['kontak_orang_tua'] ?? '');
    $guruBK = htmlspecialchars($data['nama_guru_bk'] ?? 'Ni Putu Chintya Pradnya Suari, S.Pd');
    $guruWali = htmlspecialchars($data['nama_guru_wali'] ?? '');
    $nomorSurat = $data['nomor_surat'] ?? '';
    $tanggal = isset($data['tanggal_cetak']) ? date('d F Y', strtotime($data['tanggal_cetak'])) : date('d F Y');

    $html = <<<HTML
    <!doctype html>
    <html lang="id">
        <head>
            <meta charset="utf-8" />
            <title>SURAT PERNYATAAN SISWA - $nama</title>
            <style>
                @page { size: A4; margin: 0cm 2cm; }
                body { font-family: "Times New Roman", serif; font-size:12pt; line-height:1.5; }
                p { margin:0 0 8px 0; text-align:justify; }
                .kop { text-align:center; margin-bottom:12px; }
                .identitas { margin:0 auto 12px; width:100%; }
                .identitas td { padding:2px 6px; }
                .identitas td:first-child { width:160px; text-align:left; }
                .ttd { width:100%; margin-top:24px; }
                .ttd td { vertical-align:top; width:50%; }
                .ttd .left { text-align:left; }
                .ttd .right { text-align:right; }
                .bottom-center { text-align:center; margin-top:18px; }
                @media print { body { -webkit-print-color-adjust: exact; } }
            </style>
        </head>
        <body>
            <div class="kop">
                <div style="text-align:center;">
                    <img src="/images/kop_surat.png" alt="Kop Surat" style="max-width:100%;height:auto;" onerror="this.style.display='none'">
                </div>
                <noscript>
                    <div style="text-align:center;">
                        <h3 style="margin-bottom:4px;">SMK TI BALI GLOBAL DENPASAR</h3>
                        <div style="font-size:11px;">Jl. Tukad Citarum No.44 Denpasar &nbsp;|&nbsp; Telp. (0361) 249434</div>
                    </div>
                </noscript>
            </div>

            <div style="text-align:center; font-weight:bold; display:block; margin-bottom:8px;">SURAT PERNYATAAN SISWA</div>
            <div style="text-align:center; margin-bottom:12px;">$nomorSurat</div>

            <table class="identitas">
                <tr><td>Nama</td><td>: $nama</td></tr>
                <tr><td>NIS</td><td>: $nis</td></tr>
                <tr><td>Kelas / Prodi</td><td>: $kelas / $program</td></tr>
                <tr><td>Orang Tua/Wali</td><td>: $namaOrtu</td></tr>
                <tr><td>Pekerjaan</td><td>: $pekerjaanOrtu</td></tr>
                <tr><td>Alamat</td><td>: $alamatOrtu</td></tr>
                <tr><td>Kontak</td><td>: $kontakOrtu</td></tr>
            </table>

            <p>Yang bertanda tangan di bawah ini, siswa dengan data di atas, menyatakan bahwa:</p>
            <p><em>$masalah</em></p>
            <p>Saya bersedia menerima konsekuensi dan mengikuti semua ketentuan yang ditetapkan oleh pihak sekolah. Apabila pernyataan ini tidak dipatuhi, saya siap ditindak sesuai aturan sekolah.</p>

            <table class="ttd">
                <tr>
                    <td class="left">
                        Mengetahui,<br>Orang Tua/Wali
                    </td>
                    <td class="right">
                        Denpasar, $tanggal<br>Siswa yang bersangkutan
                    </td>
                </tr>
                <tr><td colspan="2" style="height:60px;"></td></tr>
                <tr>
                    <td class="left">
                        <strong>$namaOrtu</strong>
                    </td>
                    <td class="right">
                        <strong>$nama</strong>
                    </td>
                </tr>
            </table>

            <div class="bottom-center">
                Guru BK<br><strong>$guruBK</strong><br><br>
                Guru Wali Kelas<br><strong>$guruWali</strong><br><br>
                Mengetahui Wakasek Kesiswaan
            </div>

            <script>window.print();</script>
        </body>
    </html>
    HTML;

    return $html;
}

/**
 * Surat Pemberhentian (termination) - autofill from same $data structure
 */
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
                    @page { size: A4; margin: 0cm 2cm; }
                    body { font-family: "Times New Roman", serif; font-size:12pt; line-height:1.5; }
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
                        <img src="/images/kop_surat.png" alt="Kop Surat" style="max-width:100%;height:auto;" onerror="this.style.display='none'">
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
