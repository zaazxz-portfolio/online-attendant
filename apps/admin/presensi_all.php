<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: /auth/login");
    exit();
}

require __DIR__ . "/../../utils/db.php";

$tanggal = $_GET['tanggal'] ?? date("Y-m-d");

$sql = "
    SELECT 
    u.id AS user_id,
    u.nama,
    u.username,
    u.fakultas,

    a.id AS absen_id,
    a.tanggal,
    a.jam_masuk,
    a.jam_pulang,
    a.absen_masuk,
    a.absen_pulang,
    a.distance,
    a.lat,
    a.lng,
    a.status,

    CASE 
        WHEN a.id IS NULL THEN 'belum_absen'
        ELSE a.status
    END AS status_final

FROM users u
LEFT JOIN attendance a 
    ON u.id = a.user_id 
    AND a.tanggal = ?

WHERE u.role = 'mahasiswa'
ORDER BY u.nama ASC;
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $tanggal);
$stmt->execute();
$data = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Presensi Harian - ePresence Admin</title>
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
        .modal-header-custom {
            background-color: #FECA0A;
            color: #212529;
        }
        .modal-body-img {
            width: 100%;
            max-height: 250px;
            object-fit: cover;
            border-radius: 12px;
            border: 1px solid #ddd;
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
                    <i class="bi bi-person-fill me-1"></i><?= htmlspecialchars($_SESSION['user']['nama']) ?>
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
    <div class="row justify-content-center">
        <div class="col-12 col-lg-9">
            <div class="card admin-card p-4 mb-4">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 gap-3">
                    <h4 class="fw-bold m-0 text-center text-md-start"><i class="bi bi-calendar3 text-warning me-2"></i>Rekap Presensi Harian</h4>
                    
                    <!-- Date Picker Form -->
                    <form method="GET" class="d-flex align-items-center gap-2 justify-content-center">
                        <label for="tanggal" class="small fw-semibold text-secondary text-nowrap m-0">Pilih Tanggal:</label>
                        <input type="date" class="form-control form-control-sm border-secondary-subtle" name="tanggal" id="tanggal" value="<?= $tanggal ?>" onchange="this.form.submit()">
                    </form>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle border-0">
                        <thead class="table-light">
                            <tr>
                                <th class="border-0 rounded-start" style="width: 50px;">No</th>
                                <th class="border-0">Nama Mahasiswa</th>
                                <th class="border-0">Jam Masuk</th>
                                <th class="border-0">Jam Pulang</th>
                                <th class="border-0">Status</th>
                                <th class="border-0 rounded-end text-end" style="width: 100px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            while ($row = $data->fetch_assoc()): 
                                $jamMasuk  = !empty($row['jam_masuk'])  ? substr($row['jam_masuk'], 0, 5)  : '-';
                                $jamPulang = !empty($row['jam_pulang']) ? substr($row['jam_pulang'], 0, 5) : '-';
                            ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td>
                                        <div class="fw-semibold text-dark"><?= htmlspecialchars($row['nama']) ?></div>
                                        <small class="text-secondary"><?= htmlspecialchars($row['username']) ?></small>
                                    </td>
                                    <td>
                                        <?php if (!empty($row['absen_masuk'])): ?>
                                            <span class="badge bg-success-subtle text-success fw-semibold"><i class="bi bi-clock me-1"></i><?= $jamMasuk ?> WIB</span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($row['absen_pulang'])): ?>
                                            <span class="badge bg-success-subtle text-success fw-semibold"><i class="bi bi-clock me-1"></i><?= $jamPulang ?> WIB</span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($row['status_final'] === 'success'): ?>
                                            <span class="badge bg-success"><i class="bi bi-check-circle-fill me-1"></i>SUCCESS</span>
                                        <?php elseif ($row['status_final'] === 'failed'): ?>
                                            <span class="badge bg-danger"><i class="bi bi-exclamation-triangle-fill me-1"></i>FAILED (Luar Radius)</span>
                                        <?php elseif ($row['status_final'] === 'failed_time'): ?>
                                            <span class="badge bg-warning text-dark"><i class="bi bi-exclamation-circle-fill me-1"></i>Luar Jam</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary"><i class="bi bi-slash-circle me-1"></i>Belum Absen</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <?php if ($row['status_final'] !== 'belum_absen'): ?>
                                            <button class="btn btn-outline-warning btn-sm fw-semibold rounded-pill px-3" onclick='showDetail(<?= json_encode($row) ?>)'>Lihat</button>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-light border text-muted rounded-pill px-3" disabled>Lihat</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <div class="text-start mt-4">
                    <a class="btn btn-outline-secondary py-2.5 px-4" href="/admin/dashboard">
                        <i class="bi bi-arrow-left me-2"></i>Kembali
                    </a>
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
            <div class="modal-body p-4 text-dark" id="detailBody">
                <!-- Content injected dynamically via JS -->
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
const detailModal = new bootstrap.Modal(document.getElementById('detailModal'));

function resolveFotoUrl(value){
    if(!value) return "/public/uploads/default.png";
    return value.startsWith("http") ? value : "/public/uploads/" + value;
}

function showDetail(data){
    const jamMasuk = data.jam_masuk ? data.jam_masuk.substring(0, 5) + " WIB" : "-";
    const jamPulang = data.jam_pulang ? data.jam_pulang.substring(0, 5) + " WIB" : "-";
    const status = data.status ?? "-";
    let badge = '';

    if(status === 'success'){
        badge = '<span class="badge bg-success">SUCCESS</span>';
    } else if(status === 'failed'){
        badge = '<span class="badge bg-danger">FAILED (Luar Radius)</span>';
    } else if(status === 'failed_time'){
        badge = '<span class="badge bg-warning text-dark">Luar Jam Presensi</span>';
    } else {
        badge = '<span class="badge bg-secondary">-</span>';
    }

    let html = `
        <div class="row g-3 text-center mb-3">
            <div class="col-6">
                <span class="small fw-semibold text-muted d-block mb-1">Foto Masuk</span>
                <img src="${resolveFotoUrl(data.absen_masuk)}" class="modal-body-img shadow-sm w-100" style="height:140px;object-fit:cover;border-radius:10px;" alt="Foto Masuk">
            </div>
            <div class="col-6">
                <span class="small fw-semibold text-muted d-block mb-1">Foto Pulang</span>
                <img src="${resolveFotoUrl(data.absen_pulang)}" class="modal-body-img shadow-sm w-100" style="height:140px;object-fit:cover;border-radius:10px;" alt="Foto Pulang">
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-borderless m-0">
                <tr>
                    <td class="text-secondary fw-semibold py-1" style="width: 120px;">Nama</td>
                    <td class="fw-bold py-1">${data.nama}</td>
                </tr>
                <tr>
                    <td class="text-secondary fw-semibold py-1">NPM</td>
                    <td class="py-1">${data.username}</td>
                </tr>
                <tr>
                    <td class="text-secondary fw-semibold py-1">Tanggal</td>
                    <td class="py-1">${data.tanggal ?? "-"}</td>
                </tr>
                <tr>
                    <td class="text-secondary fw-semibold py-1">Jam Masuk</td>
                    <td class="py-1">${data.absen_masuk ? `<span class="badge bg-success">${jamMasuk}</span>` : '<span class="text-muted">-</span>'}</td>
                </tr>
                <tr>
                    <td class="text-secondary fw-semibold py-1">Jam Pulang</td>
                    <td class="py-1">${data.absen_pulang ? `<span class="badge bg-success">${jamPulang}</span>` : '<span class="text-muted">-</span>'}</td>
                </tr>
                <tr>
                    <td class="text-secondary fw-semibold py-1">Jarak Radius</td>
                    <td class="py-1"><span class="fw-semibold">${data.distance ? Math.round(data.distance) : "-"}</span> meter</td>
                </tr>
                <tr>
                    <td class="text-secondary fw-semibold py-1">Lokasi GPS</td>
                    <td class="py-1 small text-dark">${data.lat ?? "-"}, ${data.lng ?? "-"}</td>
                </tr>
                <tr>
                    <td class="text-secondary fw-semibold py-1">Status</td>
                    <td class="py-1">${badge}</td>
                </tr>
            </table>
        </div>
    `;

    document.getElementById("detailBody").innerHTML = html;
    detailModal.show();
}
</script>
</body>
</html>
