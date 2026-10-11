<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: /auth/login");
    exit();
}
require_once __DIR__ . "/../../utils/helpers.php";
$u = $_SESSION['user'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - ePresence</title>
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
        .admin-card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            background: #ffffff;
        }
        .admin-img {
            width: 140px;
            height: 140px;
            object-fit: cover;
            border: 4px solid #FECA0A;
            border-radius: 50%;
        }
        .menu-card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.04);
            background: #ffffff;
            transition: all 0.25s;
            text-decoration: none;
            color: inherit;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 2rem 1rem;
        }
        .menu-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(254, 202, 10, 0.25);
            border-color: #FECA0A;
        }
        .menu-icon-wrapper {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background-color: #fffbeb;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            color: #FECA0A;
            font-size: 1.75rem;
            transition: all 0.2s;
        }
        .menu-card:hover .menu-icon-wrapper {
            background-color: #FECA0A;
            color: #212529;
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand navbar-light navbar-custom sticky-top mb-4">
    <div class="container py-1">
        <a class="navbar-brand navbar-brand-custom d-flex align-items-center" href="/">
            <img src="/public/assets/logo.png" alt="Logo" style="height: 30px; margin-right: 8px;">
            e<span>Presence Admin</span>
        </a>
        
        <ul class="navbar-nav ms-auto align-items-center">
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle fw-semibold text-dark" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-person-fill me-1"></i><?= htmlspecialchars($u['nama']) ?>
                </a>
                <ul class="dropdown-menu dropdown-menu-end border-0 shadow-sm mt-2">
                    <li><a class="dropdown-item py-2 text-danger" href="/auth/logout"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                </ul>
            </li>
        </ul>
    </div>
</nav>

<!-- Main Container -->
<div class="container pb-5">
    <!-- Admin Info Header -->
    <div class="row justify-content-center mb-4">
        <div class="col-12 col-md-8 col-lg-6">
            <div class="card admin-card p-4 text-center">
                <div class="mb-3 d-flex justify-content-center">
                    <?php if (!empty($u['foto']) && $u['foto'] !== 'default.png'): ?>
                        <img src="<?= htmlspecialchars(fotoProfilUrl($u['foto'])) ?>" class="admin-img" alt="Admin Photo">
                    <?php else: ?>
                        <div class="admin-img d-flex align-items-center justify-content-center bg-light text-secondary">
                            <i class="bi bi-person-fill" style="font-size: 5rem;"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <h4 class="fw-bold mb-1">Halo, <?= htmlspecialchars($u['nama']) ?></h4>
                <span class="badge bg-warning text-dark px-3 py-2 fw-semibold rounded-pill">Administrator</span>
            </div>
        </div>
    </div>

    <!-- Menu Grid -->
    <div class="row justify-content-center g-3">
        <div class="col-6 col-md-3 col-lg-2.5">
            <a href="/admin/presensi_all" class="card menu-card h-100">
                <div class="menu-icon-wrapper">
                    <i class="bi bi-calendar-check-fill"></i>
                </div>
                <h6 class="fw-bold m-0 text-center">Presensi</h6>
                <small class="text-muted text-center mt-1">Rekap Harian</small>
            </a>
        </div>

        <div class="col-6 col-md-3 col-lg-2.5">
            <a href="/admin/mahasiswa" class="card menu-card h-100">
                <div class="menu-icon-wrapper">
                    <i class="bi bi-people-fill"></i>
                </div>
                <h6 class="fw-bold m-0 text-center">Mahasiswa</h6>
                <small class="text-muted text-center mt-1">Data Mahasiswa</small>
            </a>
        </div>

        <div class="col-6 col-md-3 col-lg-2.5">
            <a href="/admin/pengaturan" class="card menu-card h-100">
                <div class="menu-icon-wrapper">
                    <i class="bi bi-gear-fill"></i>
                </div>
                <h6 class="fw-bold m-0 text-center">Pengaturan</h6>
                <small class="text-muted text-center mt-1">Lokasi & Radius</small>
            </a>
        </div>

        <div class="col-6 col-md-3 col-lg-2.5">
            <a href="/admin/reset" class="card menu-card h-100">
                <div class="menu-icon-wrapper">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </div>
                <h6 class="fw-bold m-0 text-center">Reset</h6>
                <small class="text-muted text-center mt-1">Reset Sistem</small>
            </a>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
