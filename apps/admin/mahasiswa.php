<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: /auth/login");
    exit();
}

require __DIR__ . "/../../utils/db.php";

$search = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

$where = "WHERE role = 'mahasiswa'";
$params = [];
$types = "";

if ($search !== '') {
    $where .= " AND (nama LIKE ? OR username LIKE ? OR fakultas LIKE ?)";
    $like = "%{$search}%";
    $params = [$like, $like, $like];
    $types = "sss";
}

// Count total
$countSql = "SELECT COUNT(*) AS total FROM users $where";
$countStmt = $conn->prepare($countSql);
if ($types) $countStmt->bind_param($types, ...$params);
$countStmt->execute();
$total = $countStmt->get_result()->fetch_assoc()['total'];
$totalPages = max(1, ceil($total / $limit));

// Fetch data
$sql = "SELECT * FROM users $where ORDER BY nama ASC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
if ($types) {
    $stmt->bind_param($types . "ii", ...[...$params, $limit, $offset]);
} else {
    $stmt->bind_param("ii", $limit, $offset);
}
$stmt->execute();
$data = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Mahasiswa - ePresence Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background-color: #f4f6f9; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .navbar-custom { background-color: #ffffff; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .navbar-brand-custom { font-weight: 700; color: #212529; }
        .navbar-brand-custom span { color: #FECA0A; }
        .admin-card { border: none; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); background: #ffffff; }
        .avatar-sm { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid #FECA0A; }
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
        <div class="col-12 col-lg-10">
            <div class="card admin-card p-4 mb-4">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 gap-3">
                    <h4 class="fw-bold m-0"><i class="bi bi-people-fill text-warning me-2"></i>Data Mahasiswa</h4>

                    <!-- Search -->
                    <form method="GET" class="d-flex gap-2" style="max-width: 320px; width: 100%;">
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control" name="q" placeholder="Cari nama / NPM..." value="<?= htmlspecialchars($search) ?>">
                            <button class="btn btn-warning fw-semibold" type="submit"><i class="bi bi-search"></i></button>
                        </div>
                    </form>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle border-0">
                        <thead class="table-light">
                            <tr>
                                <th class="border-0" style="width:50px;">No</th>
                                <th class="border-0">Foto</th>
                                <th class="border-0">Nama</th>
                                <th class="border-0 d-none d-md-table-cell">NPM</th>
                                <th class="border-0 d-none d-lg-table-cell">Program Studi</th>
                                <th class="border-0 d-none d-md-table-cell">Tahun</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $no = $offset + 1;
                            while ($row = $data->fetch_assoc()):
                            ?>
                            <?php
                            $fotoFileName = basename($row['foto'] ?? '');
                            $hasFoto = !empty($fotoFileName) && $fotoFileName !== 'default.png' && file_exists(__DIR__ . '/../../public/uploads/' . $fotoFileName);
                            ?>
                            <tr style="cursor: pointer;" class="student-row" 
                                data-nama="<?= htmlspecialchars($row['nama']) ?>"
                                data-npm="<?= htmlspecialchars($row['username']) ?>"
                                data-fakultas="<?= htmlspecialchars($row['fakultas'] ?? '-') ?>"
                                data-tahun="<?= htmlspecialchars($row['tahun'] ?? '-') ?>"
                                data-foto="<?= $hasFoto ? '/public/uploads/' . htmlspecialchars($fotoFileName) : '' ?>">
                                <td><?= $no++ ?></td>
                                <td>
                                    <?php if ($hasFoto): ?>
                                        <img src="/public/uploads/<?= htmlspecialchars($fotoFileName) ?>" class="avatar-sm" alt="Foto">
                                    <?php else: ?>
                                        <div class="avatar-sm d-flex align-items-center justify-content-center bg-light" style="border-color: #dee2e6;">
                                            <i class="bi bi-person-fill fs-4 text-secondary"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-semibold"><?= htmlspecialchars($row['nama']) ?></div>
                                    <small class="text-muted d-md-none"><?= htmlspecialchars($row['username']) ?></small>
                                </td>
                                <td class="d-none d-md-table-cell text-secondary"><?= htmlspecialchars($row['username']) ?></td>
                                <td class="d-none d-lg-table-cell"><?= htmlspecialchars($row['fakultas'] ?? '-') ?></td>
                                <td class="d-none d-md-table-cell"><?= htmlspecialchars($row['tahun'] ?? '-') ?></td>
                            </tr>
                            <?php endwhile; ?>
                            <?php if ($total === 0): ?>
                            <tr><td colspan="6" class="text-center text-muted py-4">Tidak ada data mahasiswa</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <nav class="mt-3">
                    <ul class="pagination pagination-sm justify-content-center mb-0">
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="?q=<?= urlencode($search) ?>&page=<?= $page - 1 ?>">«</a>
                        </li>
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= $page == $i ? 'active' : '' ?>">
                            <a class="page-link <?= $page == $i ? 'bg-warning border-warning text-dark fw-bold' : 'text-dark' ?>" href="?q=<?= urlencode($search) ?>&page=<?= $i ?>"><?= $i ?></a>
                        </li>
                        <?php endfor; ?>
                        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                            <a class="page-link" href="?q=<?= urlencode($search) ?>&page=<?= $page + 1 ?>">»</a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>

                <div class="d-flex justify-content-between align-items-center mt-4">
                    <small class="text-muted">Total: <strong><?= $total ?></strong> mahasiswa</small>
                    <a class="btn btn-outline-secondary btn-sm px-3" href="/admin/dashboard">
                        <i class="bi bi-arrow-left me-1"></i>Kembali
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detail Mahasiswa -->
<div class="modal fade" id="studentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius: 16px;">
            <div class="modal-header border-0 pb-0">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center pt-0 pb-4">
                <div class="mb-3 d-flex justify-content-center">
                    <img id="modal-foto" src="" alt="Foto Mahasiswa" class="d-none" style="width: 140px; height: 140px; border-radius: 50%; object-fit: cover; border: 4px solid #FECA0A;">
                    <div id="modal-icon" class="align-items-center justify-content-center bg-light text-secondary d-none" style="width: 140px; height: 140px; border-radius: 50%; border: 4px solid #dee2e6;">
                        <i class="bi bi-person-fill" style="font-size: 5rem;"></i>
                    </div>
                </div>
                <h4 class="fw-bold mb-1" id="modal-nama">Nama Mahasiswa</h4>
                <p class="text-secondary mb-3">NPM: <span id="modal-npm">12345</span></p>
                
                <div class="border-top pt-3 text-start px-3">
                    <div class="row">
                        <div class="col-6 mb-2">
                            <span class="text-muted d-block small">Program Studi</span>
                            <span class="fw-semibold text-dark" id="modal-fakultas">-</span>
                        </div>
                        <div class="col-6 mb-2">
                            <span class="text-muted d-block small">Tahun</span>
                            <span class="fw-semibold text-dark" id="modal-tahun">-</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const rows = document.querySelectorAll(".student-row");
    const modal = new bootstrap.Modal(document.getElementById("studentModal"));
    
    rows.forEach(row => {
        row.addEventListener("click", function() {
            document.getElementById("modal-nama").textContent = this.dataset.nama;
            document.getElementById("modal-npm").textContent = this.dataset.npm;
            document.getElementById("modal-fakultas").textContent = this.dataset.fakultas;
            document.getElementById("modal-tahun").textContent = this.dataset.tahun;
            
            const fotoUrl = this.dataset.foto;
            const imgEl = document.getElementById("modal-foto");
            const iconEl = document.getElementById("modal-icon");
            
            if (fotoUrl) {
                imgEl.src = fotoUrl;
                imgEl.classList.remove("d-none");
                imgEl.classList.add("d-block");
                iconEl.classList.remove("d-flex");
                iconEl.classList.add("d-none");
            } else {
                imgEl.classList.remove("d-block");
                imgEl.classList.add("d-none");
                iconEl.classList.remove("d-none");
                iconEl.classList.add("d-flex");
            }
            
            modal.show();
        });
    });
});
</script>
</body>
</html>
