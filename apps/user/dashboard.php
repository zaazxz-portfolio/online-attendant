<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'mahasiswa') {
    header("Location: /auth/login");
    exit();
}

require __DIR__ . "/../../utils/db.php";
require __DIR__ . "/../../utils/helpers.php";

$u = $_SESSION['user'];
$uid = $u['id'];

date_default_timezone_set('Asia/Jakarta');
$now = date("H:i");

$tanggalHariIni = date("Y-m-d");
$q = mysqli_query($conn, "SELECT * FROM attendance WHERE user_id='$uid' AND DATE(tanggal)='$tanggalHariIni'");
$today = $q && mysqli_num_rows($q) > 0 ? mysqli_fetch_assoc($q) : [];

$setting   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM settings LIMIT 1"));
$radius    = $setting['radius']    ?? 100;
$jamMasuk  = substr($setting['jam_masuk']  ?? '07:00', 0, 5);
$jamPulang = substr($setting['jam_pulang'] ?? '12:00', 0, 5);

$isBeforeMasuk = ($now < $jamMasuk);
$isInMasuk     = ($now >= $jamMasuk && $now < $jamPulang);
$isPastMasuk   = ($now >= $jamPulang);

$isBeforePulang = ($now < $jamPulang);
$isInPulang     = ($now >= $jamPulang);

$jamMasukDisplay  = !empty($today['jam_masuk'])  ? substr($today['jam_masuk'],  0, 5) : null;
$jamPulangDisplay = !empty($today['jam_pulang']) ? substr($today['jam_pulang'], 0, 5) : null;
$distance = isset($today['distance']) ? round($today['distance'], 2) : null;
$statusAbsen = $today['status'] ?? null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - ePresence</title>
    <!-- Bootstrap 5.3 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f4f6f9;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .navbar-custom {
            background-color: #ffffff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .navbar-brand-custom {
            font-weight: 700;
            color: #212529;
        }
        .navbar-brand-custom span {
            color: #FECA0A;
        }
        .profile-card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            background: #ffffff;
        }
        .profile-img {
            width: 160px;
            height: 160px;
            object-fit: cover;
            border: 4px solid #FECA0A;
            border-radius: 50%;
        }
        .status-card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            background: #ffffff;
        }
        .status-badge {
            font-size: 0.85rem;
            padding: 0.35rem 0.75rem;
            border-radius: 50px;
        }
        .btn-gold-big {
            background-color: #FECA0A;
            border: none;
            color: #212529;
            font-weight: 700;
            font-size: 1.1rem;
            padding: 1rem;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(254, 202, 10, 0.3);
            transition: all 0.2s;
            text-decoration: none;
            display: block;
            text-align: center;
        }
        .btn-gold-big:hover:not(.disabled) {
            background-color: #e5b609;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(254, 202, 10, 0.4);
            color: #212529;
        }
        .btn-gold-big.disabled {
            background-color: #e9ecef;
            color: #6c757d;
            box-shadow: none;
            cursor: not-allowed;
        }
        .attendance-preview {
            width: 100%;
            max-width: 200px;
            height: 150px;
            object-fit: cover;
            border-radius: 12px;
            border: 2px dashed #dee2e6;
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand navbar-light navbar-custom sticky-top mb-4">
    <div class="container py-1">
        <a class="navbar-brand navbar-brand-custom d-flex align-items-center" href="/">
            <img src="/public/assets/logo.png" alt="Logo" style="height: 30px; margin-right: 8px;">
            e<span>Presence</span>
        </a>
        
        <ul class="navbar-nav ms-auto align-items-center">
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle fw-semibold text-dark" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($u['nama']) ?>
                </a>
                <ul class="dropdown-menu dropdown-menu-end border-0 shadow-sm mt-2">
                    <li><a class="dropdown-item py-2" href="/user/lihat_presensi"><i class="bi bi-calendar-check me-2 text-secondary"></i>Lihat Presensi</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item py-2 text-danger" href="/auth/logout"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                </ul>
            </li>
        </ul>
    </div>
</nav>

<!-- Main Content -->
<div class="container pb-5">
    <?php if (!empty($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($_GET['error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <div class="row g-4 justify-content-center">
        <!-- Profile Column -->
        <div class="col-12 col-md-5 col-lg-4 text-center">
            <div class="card profile-card p-4">
                <div class="mb-3 d-flex justify-content-center">
                    <?php 
                    $fotoFileName = basename($u['foto'] ?? '');
                    $hasFoto = !empty($fotoFileName) && $fotoFileName !== 'default.png' && file_exists(__DIR__ . '/../../public/uploads/' . $fotoFileName);
                    if ($hasFoto): 
                    ?>
                        <img src="/public/uploads/<?= htmlspecialchars($fotoFileName) ?>" class="profile-img img-thumbnail" alt="User Photo">
                    <?php else: ?>
                        <div class="profile-img img-thumbnail d-flex align-items-center justify-content-center bg-light text-secondary">
                            <i class="bi bi-person-fill" style="font-size: 6rem;"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <h4 class="fw-bold mb-1"><?= htmlspecialchars($u['nama']) ?></h4>
                <p class="text-secondary small mb-3">NPM: <?= htmlspecialchars($u['username']) ?></p>
                
                <div class="border-top pt-3 text-start">
                    <div class="mb-2">
                        <span class="text-muted d-block small">Program Studi</span>
                        <span class="fw-semibold text-dark"><?= htmlspecialchars($u['fakultas']) ?></span>
                    </div>
                    <div>
                        <span class="text-muted d-block small">Tahun</span>
                        <span class="fw-semibold text-dark"><?= htmlspecialchars($u['tahun']) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Attendance Column -->
        <div class="col-12 col-md-7 col-lg-6">
            <!-- Status Card -->
            <div class="card status-card p-4 mb-4">
                <h5 class="fw-bold border-bottom pb-2 mb-4 text-center text-md-start">Status Presensi Hari Ini</h5>

                <div class="row align-items-start g-3 text-center">
                    <!-- Masuk -->
                    <div class="col-12 col-sm-6 d-flex flex-column align-items-center">
                        <span class="fw-semibold text-secondary d-block mb-2">📥 Absen Masuk</span>
                        <?php if (!empty($today['absen_masuk'])): ?>
                            <img src="<?= htmlspecialchars(fotoUrl($today['absen_masuk'])) ?>" class="attendance-preview mb-2" alt="Foto Masuk">
                        <?php else: ?>
                            <div class="attendance-preview d-flex align-items-center justify-content-center bg-light mb-2">
                                <i class="bi bi-camera text-muted fs-2"></i>
                            </div>
                        <?php endif; ?>
                        <?php if ($jamMasukDisplay): ?>
                            <span class="badge bg-success status-badge"><i class="bi bi-clock me-1"></i><?= $jamMasukDisplay ?> WIB</span>
                        <?php else: ?>
                            <span class="badge bg-danger status-badge"><i class="bi bi-x-circle me-1"></i>Belum Absen</span>
                        <?php endif; ?>
                    </div>

                    <!-- Pulang -->
                    <div class="col-12 col-sm-6 d-flex flex-column align-items-center">
                        <span class="fw-semibold text-secondary d-block mb-2">📤 Absen Pulang</span>
                        <?php if (!empty($today['absen_pulang'])): ?>
                            <img src="<?= htmlspecialchars(fotoUrl($today['absen_pulang'])) ?>" class="attendance-preview mb-2" alt="Foto Pulang">
                        <?php else: ?>
                            <div class="attendance-preview d-flex align-items-center justify-content-center bg-light mb-2">
                                <i class="bi bi-camera text-muted fs-2"></i>
                            </div>
                        <?php endif; ?>
                        <?php if ($jamPulangDisplay): ?>
                            <span class="badge bg-success status-badge"><i class="bi bi-clock me-1"></i><?= $jamPulangDisplay ?> WIB</span>
                        <?php else: ?>
                            <span class="badge bg-danger status-badge"><i class="bi bi-x-circle me-1"></i>Belum Absen</span>
                        <?php endif; ?>
                    </div>

                    <!-- Info jarak & status -->
                    <div class="col-12 border-top pt-3 text-start">
                        <div class="mb-2">
                            <span class="text-muted d-block small">Jarak dari Base</span>
                            <span class="fw-semibold text-dark">
                                <?= $distance !== null ? $distance . " meter" : "-" ?>
                            </span>
                            <span class="text-muted small d-block">(maksimum radius <?= $radius ?> meter)</span>
                        </div>
                        <div>
                            <span class="text-muted d-block small">Status Validasi</span>
                            <?php if ($statusAbsen === 'success'): ?>
                                <span class="badge bg-success status-badge fw-bold"><i class="bi bi-check-circle-fill me-1"></i>SUCCESS</span>
                            <?php elseif ($statusAbsen === 'failed'): ?>
                                <span class="badge bg-danger status-badge fw-bold"><i class="bi bi-exclamation-triangle-fill me-1"></i>FAILED (Luar Radius)</span>
                            <?php elseif ($statusAbsen === 'failed_time'): ?>
                                <span class="badge bg-warning text-dark status-badge fw-bold"><i class="bi bi-exclamation-circle-fill me-1"></i>Luar Jam Presensi</span>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tombol Absen -->
            <div class="d-grid gap-3">

                <!-- Presensi Masuk -->
                <?php if (!empty($today['absen_masuk'])): ?>
                    <!-- Sudah absen masuk -->
                    <div class="card border-0 shadow-sm rounded-3 p-3 text-center" style="background:#f0fdf4;">
                        <div class="mb-1"><i class="bi bi-check-circle-fill fs-4 text-success"></i></div>
                        <div class="fw-semibold text-success">Absen Masuk Tercatat</div>
                        <div class="small text-muted mt-1">Pukul <?= $jamMasukDisplay ?> WIB</div>
                    </div>
                <?php elseif ($isBeforeMasuk): ?>
                    <!-- Belum waktunya masuk -->
                    <div class="card border-0 shadow-sm rounded-3 p-3 text-center bg-light">
                        <div class="mb-1"><i class="bi bi-lock-fill fs-4 text-secondary"></i></div>
                        <div class="fw-semibold text-secondary">Absen Masuk Dikunci</div>
                        <div class="small text-muted mt-1">Dibuka mulai <strong><?= $jamMasuk ?> WIB</strong></div>
                    </div>
                <?php elseif ($isInMasuk): ?>
                    <!-- Sesi absen masuk sedang aktif -->
                    <a class="btn-gold-big" href="/user/absen?time=masuk">
                        <i class="bi bi-box-arrow-in-right me-2"></i>ABSEN MASUK
                        <small class="d-block fw-normal opacity-75" style="font-size:0.75rem">Sesi: <?= $jamMasuk ?> - <?= $jamPulang ?> WIB</small>
                    </a>
                <?php else: ?>
                    <!-- Sudah lewat jam masuk / ditutup -->
                    <div class="card border-0 shadow-sm rounded-3 p-3 text-center bg-light">
                        <div class="mb-1"><i class="bi bi-x-circle-fill fs-4 text-danger"></i></div>
                        <div class="fw-semibold text-danger">Absen Masuk Ditutup</div>
                        <div class="small text-muted mt-1">Batas sesi masuk sampai <strong><?= $jamPulang ?> WIB</strong></div>
                    </div>
                <?php endif; ?>

                <!-- Presensi Pulang -->
                <?php if (!empty($today['absen_pulang'])): ?>
                    <!-- Sudah absen pulang -->
                    <div class="card border-0 shadow-sm rounded-3 p-3 text-center" style="background:#f0fdf4;">
                        <div class="mb-1"><i class="bi bi-check-circle-fill fs-4 text-success"></i></div>
                        <div class="fw-semibold text-success">Absen Pulang Tercatat</div>
                        <div class="small text-muted mt-1">Pukul <?= $jamPulangDisplay ?> WIB</div>
                    </div>
                <?php elseif ($isBeforePulang): ?>
                    <!-- Belum waktunya pulang -->
                    <div class="card border-0 shadow-sm rounded-3 p-3 text-center bg-light">
                        <div class="mb-1"><i class="bi bi-lock-fill fs-4 text-secondary"></i></div>
                        <div class="fw-semibold text-secondary">Absen Pulang Dikunci</div>
                        <div class="small text-muted mt-1">Dibuka mulai <strong><?= $jamPulang ?> WIB</strong></div>
                    </div>
                <?php else: ?>
                    <!-- Sesi absen pulang sedang aktif -->
                    <a class="btn-gold-big" href="/user/absen?time=pulang">
                        <i class="bi bi-box-arrow-right me-2"></i>ABSEN PULANG
                        <small class="d-block fw-normal opacity-75" style="font-size:0.75rem">Dibuka mulai <?= $jamPulang ?> WIB</small>
                    </a>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
