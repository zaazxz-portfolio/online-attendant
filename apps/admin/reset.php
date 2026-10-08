<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: /auth/login");
    exit();
}

require __DIR__ . "/../../utils/db.php";

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'reset_attendance') {
        $confirm = $_POST['confirm_text'] ?? '';
        if ($confirm !== 'RESET') {
            $error = "Ketik RESET untuk mengkonfirmasi.";
        } else {
            $conn->query("DELETE FROM attendance");
            $success = "Seluruh data presensi berhasil direset!";
        }
    } elseif ($action === 'reset_attendance_date') {
        $date = $_POST['reset_date'] ?? '';
        if (empty($date)) {
            $error = "Pilih tanggal yang ingin direset.";
        } else {
            $stmt = $conn->prepare("DELETE FROM attendance WHERE tanggal = ?");
            $stmt->bind_param("s", $date);
            $stmt->execute();
            $affected = $stmt->affected_rows;
            $success = "Data presensi tanggal $date berhasil dihapus ($affected record).";
        }
    }
}

// Stats
$totalMhs = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM users WHERE role='mahasiswa'"))['c'];
$totalAbsen = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM attendance"))['c'];
$oldestDate = mysqli_fetch_assoc(mysqli_query($conn, "SELECT MIN(tanggal) AS d FROM attendance"))['d'] ?? '-';
$newestDate = mysqli_fetch_assoc(mysqli_query($conn, "SELECT MAX(tanggal) AS d FROM attendance"))['d'] ?? '-';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Sistem - ePresence Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background-color: #f4f6f9; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .navbar-custom { background-color: #ffffff; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .navbar-brand-custom { font-weight: 700; color: #212529; }
        .navbar-brand-custom span { color: #FECA0A; }
        .admin-card { border: none; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); background: #ffffff; }
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
                <a class="nav-link dropdown-toggle fw-semibold text-dark" href="#" role="button" data-bs-toggle="dropdown">
                    <i class="bi bi-person-fill me-1"></i><?= htmlspecialchars($_SESSION['user']['nama']) ?>
                </a>
                <ul class="dropdown-menu dropdown-menu-end border-0 shadow-sm mt-2">
                    <li><a class="dropdown-item py-2 text-danger" href="/auth/logout"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                </ul>
            </li>
        </ul>
    </div>
</nav>

<div class="container pb-5">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-7">

            <?php if ($success): ?>
                <div class="alert alert-success border-0 shadow-sm mb-4"><i class="bi bi-check-circle-fill me-2"></i><?= $success ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger border-0 shadow-sm mb-4"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= $error ?></div>
            <?php endif; ?>

            <!-- Stats Overview -->
            <div class="card admin-card p-4 mb-4">
                <h4 class="fw-bold mb-4"><i class="bi bi-bar-chart-fill text-warning me-2"></i>Statistik Data</h4>
                <div class="row g-3 text-center">
                    <div class="col-6 col-md-3">
                        <div class="bg-light rounded-3 p-3">
                            <div class="fs-3 fw-bold text-warning"><?= $totalMhs ?></div>
                            <small class="text-secondary">Mahasiswa</small>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="bg-light rounded-3 p-3">
                            <div class="fs-3 fw-bold text-primary"><?= $totalAbsen ?></div>
                            <small class="text-secondary">Total Presensi</small>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="bg-light rounded-3 p-3">
                            <div class="fs-6 fw-bold text-dark"><?= $oldestDate !== '-' ? date('d/m/Y', strtotime($oldestDate)) : '-' ?></div>
                            <small class="text-secondary">Presensi Awal</small>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="bg-light rounded-3 p-3">
                            <div class="fs-6 fw-bold text-dark"><?= $newestDate !== '-' ? date('d/m/Y', strtotime($newestDate)) : '-' ?></div>
                            <small class="text-secondary">Presensi Akhir</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Reset by Date -->
            <div class="card admin-card p-4 mb-4">
                <h5 class="fw-bold mb-3"><i class="bi bi-calendar-x text-secondary me-2"></i>Hapus Presensi per Tanggal</h5>
                <p class="text-muted small mb-3">Menghapus seluruh record presensi pada tanggal tertentu saja.</p>
                <form method="POST">
                    <input type="hidden" name="action" value="reset_attendance_date">
                    <div class="row g-2 align-items-end">
                        <div class="col">
                            <label class="form-label small fw-semibold text-secondary">Pilih Tanggal</label>
                            <input type="date" class="form-control" name="reset_date" required>
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-outline-warning fw-semibold px-3" onclick="return confirm('Yakin hapus presensi pada tanggal ini?')">
                                <i class="bi bi-trash me-1"></i>Hapus
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Full Reset -->
            <div class="card admin-card p-4 mb-4 border border-danger border-opacity-25">
                <h5 class="fw-bold mb-2 text-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i>Reset Seluruh Presensi</h5>
                <p class="text-muted small mb-3">Menghapus <strong>SELURUH</strong> data presensi dari database. Aksi ini tidak bisa dibatalkan!</p>
                <form method="POST" id="resetAllForm">
                    <input type="hidden" name="action" value="reset_attendance">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-danger">Ketik <code>RESET</code> untuk mengkonfirmasi:</label>
                        <input type="text" class="form-control border-danger" name="confirm_text" id="confirmInput" placeholder="Ketik RESET" autocomplete="off" required>
                    </div>
                    <button type="submit" class="btn btn-danger fw-semibold px-4" id="resetAllBtn" disabled>
                        <i class="bi bi-trash3-fill me-1"></i>Reset Semua Data Presensi
                    </button>
                </form>
            </div>

            <div class="text-start">
                <a class="btn btn-outline-secondary px-3" href="/admin/dashboard">
                    <i class="bi bi-arrow-left me-1"></i>Kembali ke Dashboard
                </a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
const confirmInput = document.getElementById('confirmInput');
const resetAllBtn = document.getElementById('resetAllBtn');

confirmInput.addEventListener('input', function(){
    resetAllBtn.disabled = this.value !== 'RESET';
});

document.getElementById('resetAllForm').addEventListener('submit', function(e){
    if(confirmInput.value !== 'RESET'){
        e.preventDefault();
        return;
    }
    if(!confirm('PERINGATAN TERAKHIR! Seluruh data presensi akan dihapus permanen. Lanjutkan?')){
        e.preventDefault();
    }
});
</script>
</body>
</html>
