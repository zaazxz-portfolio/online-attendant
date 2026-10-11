<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'mahasiswa') {
    header("Location: /auth/login");
    exit();
}

require __DIR__ . "/../../utils/db.php";
require __DIR__ . "/../../utils/helpers.php";

$u = $_SESSION['user'];
$uid = $u['id'];

$img = $_POST['img'] ?? '';
$time = $_POST['time'] ?? '';
$latUser = floatval($_POST['lat'] ?? 0);
$lngUser = floatval($_POST['lng'] ?? 0);

if (empty($img) || !in_array($time, ['masuk', 'pulang'])) {
    die("Data tidak valid");
}

date_default_timezone_set('Asia/Jakarta');

$setting   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM settings LIMIT 1"));
$jamMasuk  = substr($setting['jam_masuk']  ?? '07:00', 0, 5);
$jamPulang = substr($setting['jam_pulang'] ?? '12:00', 0, 5);

$now = date("H:i");

if ($time === 'masuk') {
    if ($now < $jamMasuk || $now >= $jamPulang) {
        $status = 'failed_time';
    } else {
        $status = 'success';
    }
} elseif ($time === 'pulang') {
    if ($now < $jamPulang) {
        $status = 'failed_time';
    } else {
        $status = 'success';
    }
} else {
    $status = 'failed_time';
}

$latBase = $setting['base_lat'];
$lngBase = $setting['base_lng'];
$radius  = $setting['radius'];

// Compute distance using the helper function
$dist = distance($latUser, $lngUser, $latBase, $lngBase);

if ($status === 'success' && $dist > $radius) {
    $status = 'failed';
}

$imgName   = "foto_" . $uid . "_" . $time . "_" . time() . ".png";

$imgBase64Only = preg_replace('#^data:image/\w+;base64,#i', '', $img);

if (empty($imgBase64Only) || base64_decode($imgBase64Only, true) === false) {
    die("Base64 tidak valid");
}

$uploadDir = __DIR__ . "/../../public/uploads/";

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$saved = file_put_contents($uploadDir . $imgName, base64_decode($imgBase64Only));

if ($saved === false) {
    die("Gagal menyimpan foto ke server.");
}

$tanggalHariIni = date("Y-m-d");
$jamSekarang = date("H:i:s");

$q = mysqli_query($conn, "SELECT * FROM attendance WHERE user_id='$uid' AND tanggal='$tanggalHariIni'");
$attendance = mysqli_fetch_assoc($q);

$fieldImg  = $time == 'masuk' ? 'absen_masuk'    : 'absen_pulang';
$fieldJam  = $time == 'masuk' ? 'jam_masuk'      : 'jam_pulang';
$fieldDist = $time == 'masuk' ? 'distance_masuk' : 'distance_pulang';
$fieldStat = $time == 'masuk' ? 'status_masuk'   : 'status_pulang';

if (!$attendance) {
    mysqli_query($conn, "INSERT INTO attendance(
        user_id, tanggal, $fieldImg, $fieldJam, $fieldDist, $fieldStat, lat, lng, distance, status
    ) VALUES(
        '$uid', '$tanggalHariIni', '$imgName', '$jamSekarang', '$dist', '$status', '$latUser', '$lngUser', '$dist', '$status'
    )");
} else {
    mysqli_query($conn, "UPDATE attendance SET
        $fieldImg='$imgName',
        $fieldJam='$jamSekarang',
        $fieldDist='$dist',
        $fieldStat='$status',
        lat='$latUser',
        lng='$lngUser',
        distance='$dist',
        status='$status'
    WHERE id={$attendance['id']}");
}

if ($status === 'success') {
    echo "<script>alert('Absensi $time berhasil');window.location='/user/dashboard';</script>";
} elseif ($status === 'failed') {
    echo "<script>alert('Gagal, Anda berada di luar radius (" . round($dist, 1) . " m)');window.location='/user/dashboard';</script>";
} else {
    $labelJam = $time === 'masuk' ? $jamMasuk : $jamPulang;
    echo "<script>alert('Gagal, absen $time baru dibuka mulai $labelJam WIB');window.location='/user/dashboard';</script>";
}
?>
