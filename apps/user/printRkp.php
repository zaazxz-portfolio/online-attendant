<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: /auth/login");
    exit();
}

require __DIR__ . "/../../utils/db.php";
require __DIR__ . "/../../vendor/autoload.php";

use Dompdf\Dompdf;
use Dompdf\Options;

$u = $_SESSION['user'];
$uid = $u['id'];

$user_data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id='$uid'"));
$query = mysqli_query($conn, "SELECT * FROM attendance WHERE user_id='$uid' ORDER BY tanggal ASC");

// Function to encode local image to base64
function getImageBase64($path)
{
    if (file_exists($path)) {
        $type = pathinfo($path, PATHINFO_EXTENSION);
        $data = file_get_contents($path);
        return 'data:image/' . $type . ';base64,' . base64_encode($data);
    }
    return '';
}

$logoPath = __DIR__ . '/../../public/assets/logo.png';
$ttdPath = __DIR__ . '/../../public/assets/ttd.png';

$logoBase64 = getImageBase64($logoPath);
$ttdBase64 = getImageBase64($ttdPath);

// Resolve student photo
$photoValue = $user_data['foto'] ?? '';
$photoSrc = '';
if (empty($photoValue)) {
    $photoSrc = getImageBase64(__DIR__ . '/../../public/uploads/default.png');
} elseif (str_starts_with($photoValue, 'http')) {
    $photoSrc = $photoValue; // Direct Google Drive URL
} else {
    $photoSrc = getImageBase64(__DIR__ . '/../../public/uploads/' . $photoValue);
}

$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 25px 30px;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #333;
            line-height: 1.3;
        }
        .judul {
            text-align: center;
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 15px;
            line-height: 1.4;
        }
        .header-logo {
            text-align: center;
            margin-bottom: 10px;
        }
        .header-logo img {
            width: 55px;
            height: auto;
        }
        .info-section {
            width: 100%;
            margin-bottom: 20px;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
        }
        .photo-box {
            width: 90px;
            text-align: center;
            vertical-align: top;
        }
        .photo-img {
            width: 80px;
            height: 100px;
            object-fit: cover;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .details-box {
            vertical-align: bottom;
            padding-left: 20px;
        }
        .details-table {
            width: 100%;
        }
        .details-table td {
            padding: 3px 0;
        }
        .main-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .main-table th, .main-table td {
            border: 1px solid #dee2e6;
            padding: 8px 10px;
            text-align: center;
        }
        .main-table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .footer-section {
            width: 100%;
            margin-top: 20px;
        }
        .signature-box {
            float: right;
            width: 200px;
            text-align: center;
        }
    </style>
</head>
<body>

    <div class="header-logo">
        <img src="' . $logoBase64 . '" alt="Logo UNPAD">
    </div>
    
    <div class="judul">
        KKNM UNIVERSITAS PADJADJARAN<br>
        SEMESTER GANJIL TAHUN AKADEMIK 2025/2026<br>
        PERIODE JANUARI - FEBRUARI 2026<br><br>
        REKAPITULASI PRESENSI MAHASISWA
    </div>

    <div class="info-section">
        <table class="info-table">
            <tr>
                <td class="photo-box">
                    <img src="' . $photoSrc . '" class="photo-img" alt="Foto Mahasiswa">
                </td>
                <td class="details-box">
                    <table class="details-table">
                        <tr>
                            <td style="width: 100px; color: #666;">NPM</td>
                            <td>: <strong>' . ($user_data['nim'] ?? $user_data['username']) . '</strong></td>
                        </tr>
                        <tr>
                            <td style="color: #666;">Nama Lengkap</td>
                            <td>: <strong>' . $user_data['nama'] . '</strong></td>
                        </tr>
                        <tr>
                            <td style="color: #666;">Program Studi</td>
                            <td>: ' . ($user_data['fakultas'] ?? '-') . '</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    <table class="main-table">
        <thead>
            <tr>
                <th style="width: 40px;">NO</th>
                <th>TANGGAL</th>
                <th>JAM MASUK</th>
                <th>JAM PULANG</th>
                <th>JARAK PRESENSI</th>
            </tr>
        </thead>
        <tbody>';

$no = 1;
while ($row = mysqli_fetch_assoc($query)) {
    $jarak = isset($row['distance']) ? round($row['distance']) . " m" : "-";
    $jamMasuk  = !empty($row['jam_masuk'])  ? substr($row['jam_masuk'], 0, 5) . " WIB"  : "-";
    $jamPulang = !empty($row['jam_pulang']) ? substr($row['jam_pulang'], 0, 5) . " WIB" : "-";

    $html .= '
            <tr>
                <td>' . $no++ . '</td>
                <td>' . date('d-m-Y', strtotime($row['tanggal'])) . '</td>
                <td>' . $jamMasuk . '</td>
                <td>' . $jamPulang . '</td>
                <td>' . $jarak . '</td>
            </tr>';
}

$html .= '
        </tbody>
    </table>

    <div class="footer-section">
        <div class="signature-box">
            Bandung, ' . date('d F Y') . '<br>
            Dosen Pembimbing Lapangan<br><br>
            <img src="' . $ttdBase64 . '" alt="Tanda Tangan" style="width: 90px; height: auto;"><br><br>
            <strong>Dr. Diyan Herdiyantoro, S.P., M.Si.</strong><br>
            NIP. 19771024 200604 1 002
        </div>
    </div>

</body>
</html>';

$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('isHtml5ParserEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$filename = "Rekap_Presence_" . str_replace(' ', '_', $u['nama']) . ".pdf";
$dompdf->stream($filename, ["Attachment" => 1]);
exit();
?>
