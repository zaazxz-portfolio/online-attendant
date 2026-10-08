<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'mahasiswa') {
    header("Location: /auth/login");
    exit();
}

require __DIR__ . "/../../utils/db.php";
require __DIR__ . "/../../utils/drive.php";
require __DIR__ . "/../../utils/helpers.php";

$u = $_SESSION['user'];
$uid = $u['id'];

$img = $_POST['img'] ?? '';
$time = $_POST['time'] ?? '';
$latUser = floatval($_POST['lat'] ?? 0);
$lngUser = floatval($_POST['lng'] ?? 0);

if (empty($img) || !in_array($time, ['pagi', 'sore'])) {
    die("Data tidak valid");
}

date_default_timezone_set('Asia/Jakarta');

$now = date("H:i");
$timeRange = [
    "pagi" => ["01:00", "23:59"],
    "sore" => ["17:00", "23:59"]
];

if ($now < $timeRange[$time][0] || $now > $timeRange[$time][1]) {
    $status = 'failed_time';
} else {
    $status = 'success';
}

$setting = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM settings LIMIT 1"));
$latBase = $setting['base_lat'];
$lngBase = $setting['base_lng'];
$radius = $setting['radius'];

// Compute distance using the helper function
$dist = distance($latUser, $lngUser, $latBase, $lngBase);

if ($status === 'success' && $dist > $radius) {
    $status = 'failed';
}

$imgName = "foto_" . $uid . "_" . $time . "_" . time() . ".png";

$imgBase64Only = preg_replace('#^data:image/\w+;base64,#i', '', $img);

if (empty($imgBase64Only) || base64_decode($imgBase64Only, true) === false) {
    die("Base64 tidak valid");
}

$driveResult = uploadToDrive($imgBase64Only, $imgName, "image/png");

if (empty($driveResult['success'])) {
    die("Gagal upload foto ke Google Drive: " . ($driveResult['message'] ?? 'unknown error'));
}

$imgName = $driveResult['url'];

$tanggalHariIni = date("Y-m-d");
$jamSekarang = date("H:i:s");

$q = mysqli_query($conn, "SELECT * FROM attendance WHERE user_id='$uid' AND tanggal='$tanggalHariIni'");
$attendance = mysqli_fetch_assoc($q);

$fieldImg = $time == 'pagi' ? 'absen_pagi' : 'absen_sore';
$fieldJam = $time == 'pagi' ? 'jam_pagi' : 'jam_sore';

if (!$attendance) {
    mysqli_query($conn, "INSERT INTO attendance(
        user_id, tanggal, $fieldImg, $fieldJam, lat, lng, distance, status
    ) VALUES(
        '$uid', '$tanggalHariIni', '$imgName', '$jamSekarang', '$latUser', '$lngUser', '$dist', '$status'
    )");
} else {
    mysqli_query($conn, "UPDATE attendance SET
        $fieldImg='$imgName',
        $fieldJam='$jamSekarang',
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
    echo "<script>alert('Gagal, di luar jam presensi');window.location='/user/dashboard';</script>";
}
?>
