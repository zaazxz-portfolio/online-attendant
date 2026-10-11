<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'mahasiswa') {
    header("Location: /auth/login");
    exit();
}

$u   = $_SESSION['user'];
$uid = $u['id'];

$time = $_GET['time'] ?? 'masuk';
if (!in_array($time, ['masuk', 'pulang'])) $time = 'masuk';

require __DIR__ . "/../../utils/db.php";

date_default_timezone_set('Asia/Jakarta');
$now = date("H:i");

// Ambil jam presensi dari settings
$setting   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM settings LIMIT 1"));
$jamMasuk  = substr($setting['jam_masuk']  ?? '07:00', 0, 5);
$jamPulang = substr($setting['jam_pulang'] ?? '12:00', 0, 5);

// Cek apakah sudah absen hari ini
$tanggalHariIni = date("Y-m-d");
$q     = mysqli_query($conn, "SELECT absen_masuk, absen_pulang FROM attendance WHERE user_id='$uid' AND tanggal='$tanggalHariIni'");
$today = $q && mysqli_num_rows($q) > 0 ? mysqli_fetch_assoc($q) : [];

// Blokir berdasarkan sesi waktu
if ($time === 'masuk') {
    if ($now < $jamMasuk) {
        header("Location: /user/dashboard?error=" . urlencode("Absen masuk baru dibuka mulai $jamMasuk WIB"));
        exit();
    }
    if ($now >= $jamPulang) {
        header("Location: /user/dashboard?error=" . urlencode("Waktu absen masuk telah ditutup (batas $jamPulang WIB)"));
        exit();
    }
} elseif ($time === 'pulang') {
    if ($now < $jamPulang) {
        header("Location: /user/dashboard?error=" . urlencode("Absen pulang baru dibuka mulai $jamPulang WIB"));
        exit();
    }
}

// Blokir jika sudah absen
$fieldAbsen = $time === 'masuk' ? 'absen_masuk' : 'absen_pulang';
if (!empty($today[$fieldAbsen])) {
    header("Location: /user/dashboard?error=" . urlencode("Anda sudah melakukan absen $time hari ini"));
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ambil Foto Presensi</title>
    <!-- Bootstrap 5.3 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #121212;
            color: #ffffff;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .camera-container {
            position: relative;
            width: 100%;
            max-width: 480px;
            margin: auto;
            border-radius: 20px;
            overflow: hidden;
            border: 4px solid #FECA0A;
            background: #000;
        }
        video {
            width: 100%;
            height: auto;
            transform: scaleX(-1); /* mirror effect */
        }
        .btn-gold {
            background-color: #FECA0A;
            border: none;
            color: #212529;
            font-weight: 700;
            padding: 1rem;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(254, 202, 10, 0.3);
            transition: all 0.2s;
        }
        .btn-gold:hover:not(:disabled) {
            background-color: #e5b609;
            transform: scale(1.02);
        }
        .btn-gold:disabled {
            background-color: #495057;
            color: #adb5bd;
            box-shadow: none;
            cursor: not-allowed;
        }
    </style>
</head>
<body>

<!-- Header -->
<div class="bg-dark py-3 px-3 border-bottom border-secondary d-flex align-items-center justify-content-between">
    <a href="/user/dashboard" class="btn btn-outline-light btn-sm"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
    <h5 class="m-0 fw-bold"><i class="bi bi-camera me-2 text-warning"></i>Kamera Presensi</h5>
    <div style="width: 80px;"></div> <!-- spacer -->
</div>

<!-- Main Section -->
<div class="container my-auto py-4 text-center">
    <div class="camera-container shadow-lg mb-3">
        <video id="camera" autoplay playsinline></video>
    </div>
    <canvas id="canvas" style="display:none;"></canvas>

    <div class="mx-auto" style="max-width: 480px;">
        <div id="geoStatus" class="alert alert-secondary py-2 border-0 small mb-4">
            <span class="spinner-border spinner-border-sm text-warning me-2" role="status" aria-hidden="true"></span>
            📍 Mengambil lokasi GPS...
        </div>
        
        <button id="capture" class="btn btn-gold w-100 py-3 shadow" disabled>
            <i class="bi bi-camera-fill me-2 fs-5"></i><?= $time === 'masuk' ? 'Absen Masuk' : 'Absen Pulang' ?>
        </button>
    </div>
</div>

<form id="form" method="POST" action="/user/save_absen">
    <input type="hidden" name="img" id="img">
    <input type="hidden" name="time" value="<?= $time ?>">
    <input type="hidden" name="lat" id="lat">
    <input type="hidden" name="lng" id="lng">
</form>

<!-- Overlay loading -->
<div id="loadingOverlay" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.85);
    z-index:9999; flex-direction:column; align-items:center; justify-content:center; color:#fff;">
    <div class="spinner-border text-warning mb-3" style="width: 3.5rem; height: 3.5rem;" role="status">
        <span class="visually-hidden">Loading...</span>
    </div>
    <h5 class="fw-bold">Memproses presensi...</h5>
    <p class="text-secondary small">Mohon tunggu, foto sedang diunggah ke Google Drive</p>
</div>

<script>
let video = document.getElementById("camera");
let captureBtn = document.getElementById("capture");
let geoStatus = document.getElementById("geoStatus");
let hasLocation = false;
let isSubmitting = false;

// access front camera — enable capture button once camera is ready
navigator.mediaDevices.getUserMedia({ video: { facingMode: "user" } })
.then(stream => {
    video.srcObject = stream;
    captureBtn.disabled = false;
})
.catch(err => { alert("Gagal akses kamera: " + err.message); });

// Fetch geolocation secara presisi menggunakan watchPosition (terus memperbarui sampai akurasi terbaik)
let watchId = null;
let bestAccuracy = Infinity;

function startWatchingLocation(){
    if(!navigator.geolocation){
        geoStatus.className = "alert alert-warning py-2 border-0 small mb-4";
        geoStatus.innerHTML = "<i class='bi bi-exclamation-triangle me-1'></i> Browser tidak mendukung GPS. Foto tetap bisa diambil.";
        return;
    }
    geoStatus.className = "alert alert-secondary py-2 border-0 small mb-4";
    geoStatus.innerHTML = "<span class='spinner-border spinner-border-sm text-warning me-2' role='status' aria-hidden='true'></span> Mengunci sinyal GPS presisi tinggi...";

    const geoOptions = {
        enableHighAccuracy: true,
        timeout: 20000,
        maximumAge: 0 // Wajib 0 agar tidak mengambil koordinat basi/cache ISP
    };

    watchId = navigator.geolocation.watchPosition(
        pos => {
            const acc = Math.round(pos.coords.accuracy);
            
            // Simpan koordinat jika lebih akurat atau pembaruan pertama
            if (acc <= bestAccuracy || !hasLocation) {
                bestAccuracy = acc;
                document.getElementById("lat").value = pos.coords.latitude;
                document.getElementById("lng").value = pos.coords.longitude;
                hasLocation = true;
            }

            // Tampilkan indikator akurasi ke pengguna
            if (acc <= 20) {
                geoStatus.className = "alert alert-success py-2 border-0 small mb-4 text-success";
                geoStatus.innerHTML = `<i class='bi bi-check-circle-fill me-1'></i> GPS Sangat Akurat (Akurasi: ±${acc} meter)`;
            } else if (acc <= 100) {
                geoStatus.className = "alert alert-info py-2 border-0 small mb-4 text-primary";
                geoStatus.innerHTML = `<i class='bi bi-geo-alt-fill me-1'></i> Lokasi GPS Terkunci (Akurasi: ±${acc} meter)`;
            } else {
                geoStatus.className = "alert alert-warning py-2 border-0 small mb-4";
                geoStatus.innerHTML = `<i class='bi bi-exclamation-circle me-1'></i> Sinyal GPS Lemah (Akurasi: ±${acc} meter, sedang mencari sinyal satelit yang lebih kuat...)`;
            }
        },
        err => {
            geoStatus.className = "alert alert-warning py-2 border-0 small mb-4";
            geoStatus.innerHTML = `<i class='bi bi-exclamation-triangle me-1'></i> Gagal membaca GPS (${err.message}) — <a href='#' id='retryGeo' class='alert-link text-decoration-underline'>coba lagi</a>`;
            document.getElementById("retryGeo").onclick = function(e){
                e.preventDefault();
                startWatchingLocation();
            };
        },
        geoOptions
    );
}
startWatchingLocation();

document.getElementById("capture").onclick = function(){
    if(isSubmitting) return;

    isSubmitting = true;
    captureBtn.disabled = true;
    document.getElementById("loadingOverlay").style.display = "flex";

    let c = document.getElementById("canvas");
    c.width = video.videoWidth;
    c.height = video.videoHeight;
    c.getContext("2d").drawImage(video,0,0);

    let imgData = c.toDataURL("image/png");
    document.getElementById("img").value = imgData;
    document.getElementById("form").submit();
};
</script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
