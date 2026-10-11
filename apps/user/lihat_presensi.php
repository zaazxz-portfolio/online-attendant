<?php
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

$limit = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;

$totalQuery = mysqli_query($conn, "SELECT COUNT(*) AS total FROM attendance WHERE user_id='$uid'");
$totalRow = mysqli_fetch_assoc($totalQuery);
$total = $totalRow['total'];
$totalPages = ceil($total / $limit);

$q = mysqli_query($conn, "SELECT * FROM attendance WHERE user_id='$uid' ORDER BY tanggal DESC LIMIT $start,$limit");

$setting = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM settings LIMIT 1"));
$radius = $setting['radius'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Presensi - ePresence</title>
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
        .history-card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            background: #ffffff;
        }
        .table-hover tbody tr {
            cursor: pointer;
            transition: background-color 0.15s;
        }
        .table-hover tbody tr:hover {
            background-color: #fffbeb !important;
        }
        .btn-gold {
            background-color: #FECA0A;
            border: none;
            color: #212529;
            font-weight: 700;
            transition: all 0.2s;
        }
        .btn-gold:hover {
            background-color: #e5b609;
            color: #212529;
        }
        .modal-header-custom {
            background-color: #FECA0A;
            color: #212529;
        }
        .modal-body-img {
            width: 100%;
            max-height: 250px;
            object-fit: cover;
            border-radius: 12px;
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
                    <li><a class="dropdown-item py-2" href="/user/dashboard"><i class="bi bi-house-door me-2 text-secondary"></i>Dashboard</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item py-2 text-danger" href="/auth/logout"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                </ul>
            </li>
        </ul>
    </div>
</nav>

<!-- Main content -->
<div class="container pb-5">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <div class="card history-card p-4">
                <h4 class="fw-bold mb-4 text-center"><i class="bi bi-calendar-check text-warning me-2"></i>Riwayat Presensi</h4>
                
                <div class="table-responsive mb-4">
                    <table class="table table-hover align-middle border-0">
                        <thead class="table-light">
                            <tr>
                                <th class="border-0 rounded-start">No</th>
                                <th class="border-0">Tanggal</th>
                                <th class="border-0">Jam Masuk</th>
                                <th class="border-0">Jam Pulang</th>
                                <th class="border-0 rounded-end text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($q && mysqli_num_rows($q) > 0):
                                $no = $start + 1;
                                while ($row = mysqli_fetch_assoc($q)):
                                    $jamMasuk = !empty($row['jam_masuk']) ? substr($row['jam_masuk'], 0, 5) : '-';
                                    $jamPulang = !empty($row['jam_pulang']) ? substr($row['jam_pulang'], 0, 5) : '-';
                                    $distMasuk = isset($row['distance_masuk']) ? round($row['distance_masuk'], 1) : (isset($row['distance']) ? round($row['distance'], 1) : '-');
                                    $distPulang = isset($row['distance_pulang']) ? round($row['distance_pulang'], 1) : '-';
                                    $statMasuk = $row['status_masuk'] ?? ($row['status'] ?? '-');
                                    $statPulang = $row['status_pulang'] ?? '-';
                            ?>
                                    <tr class="row-data"
                                        data-tanggal="<?= tglIndoShort($row['tanggal']) ?>"
                                        data-jammasuk="<?= $jamMasuk ?>"
                                        data-jampulang="<?= $jamPulang ?>"
                                        data-masuk="<?= htmlspecialchars(fotoUrl($row['absen_masuk'] ?? '')) ?>"
                                        data-pulang="<?= htmlspecialchars(fotoUrl($row['absen_pulang'] ?? '')) ?>"
                                        data-distancemasuk="<?= $distMasuk ?>"
                                        data-distancepulang="<?= $distPulang ?>"
                                        data-statusmasuk="<?= htmlspecialchars($statMasuk) ?>"
                                        data-statuspulang="<?= htmlspecialchars($statPulang) ?>">
                                        <td><?= $no++ ?></td>
                                        <td class="fw-semibold text-dark"><?= tglIndoShort($row['tanggal']) ?></td>
                                        <td>
                                            <?php if (!empty($row['absen_masuk'])): ?>
                                                <span class="badge bg-success"><i class="bi bi-clock me-1"></i><?= $jamMasuk ?> WIB</span>
                                                <?php if ($statMasuk === 'success'): ?>
                                                    <span class="badge bg-success-subtle text-success ms-1">Valid</span>
                                                <?php elseif ($statMasuk === 'failed'): ?>
                                                    <span class="badge bg-danger-subtle text-danger ms-1">Luar Radius</span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="badge bg-secondary"><i class="bi bi-x-circle me-1"></i>Belum Absen</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($row['absen_pulang'])): ?>
                                                <span class="badge bg-success"><i class="bi bi-clock me-1"></i><?= $jamPulang ?> WIB</span>
                                                <?php if ($statPulang === 'success'): ?>
                                                    <span class="badge bg-success-subtle text-success ms-1">Valid</span>
                                                <?php elseif ($statPulang === 'failed'): ?>
                                                    <span class="badge bg-danger-subtle text-danger ms-1">Luar Radius</span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="badge bg-secondary"><i class="bi bi-x-circle me-1"></i>Belum Absen</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <button class="btn btn-outline-warning btn-sm fw-semibold rounded-pill px-3">Detail</button>
                                        </td>
                                    </tr>
                                <?php endwhile;
                            else: ?>
                                <tr>
                                    <td colspan="5" class="text-center text-secondary py-4">Belum ada presensi</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <nav aria-label="Page navigation" class="mb-4">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $page - 1 ?>" tabindex="-1">« Prev</a>
                            </li>
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?= $page == $i ? 'active' : '' ?>">
                                    <a class="page-link <?= $page == $i ? 'bg-warning border-warning text-dark fw-bold' : 'text-dark' ?>" href="?page=<?= $i ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $page + 1 ?>">Next »</a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>

                <!-- PDF Downloader & Kembali -->
                <div class="row g-2">
                    <div class="col-12 col-md-6">
                        <a href="/user/printRkp" target="_blank" class="btn btn-gold w-100 py-2.5">
                            <i class="bi bi-file-earmark-pdf-fill me-2"></i>UNDUH PDF REKAP
                        </a>
                    </div>
                    <div class="col-12 col-md-6">
                        <a href="/user/dashboard" class="btn btn-outline-secondary w-100 py-2.5">
                            <i class="bi bi-arrow-left me-2"></i>Kembali ke Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Details Modal -->
<div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header modal-header-custom border-0 py-3">
                <h5 class="modal-title fw-bold" id="detailModalLabel"><i class="bi bi-info-circle-fill me-2"></i>Detail Presensi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-dark">
                <div class="row g-3 text-center mb-3">
                    <div class="col-6">
                        <span class="small fw-semibold text-muted d-block mb-1">Foto Masuk</span>
                        <img id="modal-masuk" src="/public/uploads/default.png" class="modal-body-img shadow-sm w-100" style="height:140px;object-fit:cover;border-radius:10px;" alt="Foto Masuk">
                    </div>
                    <div class="col-6">
                        <span class="small fw-semibold text-muted d-block mb-1">Foto Pulang</span>
                        <img id="modal-pulang" src="/public/uploads/default.png" class="modal-body-img shadow-sm w-100" style="height:140px;object-fit:cover;border-radius:10px;" alt="Foto Pulang">
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-borderless m-0">
                        <tr>
                            <td class="text-secondary fw-semibold py-1">Tanggal</td>
                            <td class="fw-bold py-1" id="modal-tanggal"></td>
                        </tr>
                        <tr>
                            <td class="text-secondary fw-semibold py-1">Sesi Masuk</td>
                            <td class="py-1" id="modal-sesi-masuk"></td>
                        </tr>
                        <tr>
                            <td class="text-secondary fw-semibold py-1">Sesi Pulang</td>
                            <td class="py-1" id="modal-sesi-pulang"></td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="modal-footer border-0 bg-light py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const rows = document.querySelectorAll(".row-data");
    const detailModal = new bootstrap.Modal(document.getElementById('detailModal'));

    function renderSessionBadge(jam, dist, status) {
        if (!jam || jam === '-') return '<span class="badge bg-secondary">Belum Absen</span>';
        
        let statBadge = '';
        if (status === 'success') {
            statBadge = '<span class="badge bg-success ms-1"><i class="bi bi-check-circle-fill me-1"></i>SUCCESS</span>';
        } else if (status === 'failed') {
            statBadge = '<span class="badge bg-danger ms-1"><i class="bi bi-exclamation-triangle-fill me-1"></i>FAILED (Luar Radius)</span>';
        } else if (status === 'failed_time') {
            statBadge = '<span class="badge bg-warning text-dark ms-1"><i class="bi bi-clock me-1"></i>Luar Jam</span>';
        } else {
            statBadge = '<span class="badge bg-secondary ms-1">-</span>';
        }

        const distText = (dist && dist !== '-') ? `<span class="small text-muted d-block mt-0.5">Jarak: <strong>${dist} m</strong></span>` : '';
        return `<div><span class="badge bg-light text-dark border"><i class="bi bi-clock me-1"></i>${jam} WIB</span> ${statBadge} ${distText}</div>`;
    }

    rows.forEach(row => {
        row.addEventListener("click", () => {
            document.getElementById("modal-tanggal").innerText = row.dataset.tanggal;
            
            document.getElementById("modal-sesi-masuk").innerHTML = renderSessionBadge(
                row.dataset.jammasuk,
                row.dataset.distancemasuk,
                row.dataset.statusmasuk
            );
            
            document.getElementById("modal-sesi-pulang").innerHTML = renderSessionBadge(
                row.dataset.jampulang,
                row.dataset.distancepulang,
                row.dataset.statuspulang
            );

            document.getElementById("modal-masuk").src = row.dataset.masuk || "/public/uploads/default.png";
            document.getElementById("modal-pulang").src = row.dataset.pulang || "/public/uploads/default.png";

            detailModal.show();
        });
    });
</script>
</body>
</html>
