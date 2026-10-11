<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: /auth/login");
    exit();
}

require_once __DIR__ . "/../../utils/db.php";
require_once __DIR__ . "/../../utils/helpers.php";

$success = '';
$error = '';

$uploadDir = __DIR__ . "/../../public/assets/uploads/";
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
    chmod($uploadDir, 0777);
}

// ── Handle Action POST: Tambah & Edit Mahasiswa ───────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $id       = (int)($_POST['id'] ?? 0);
        $nama     = trim($_POST['nama'] ?? '');
        $username = trim($_POST['username'] ?? ''); // NPM
        $fakultas = trim($_POST['fakultas'] ?? '');
        $tahun    = trim($_POST['tahun'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (empty($nama) || empty($username)) {
            $error = "Nama dan NPM wajib diisi.";
        } else {
            // Cek duplikasi NPM
            $checkSql = "SELECT id FROM users WHERE username = ? AND id != ?";
            $chkStmt = $conn->prepare($checkSql);
            $chkStmt->bind_param("si", $username, $id);
            $chkStmt->execute();
            if ($chkStmt->get_result()->num_rows > 0) {
                $error = "NPM / Username '{$username}' sudah terdaftar pada mahasiswa lain.";
            } else {
                // Upload Foto jika ada
                $fotoFilename = null;
                if (!empty($_FILES['foto']['name']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                    $fileTmp  = $_FILES['foto']['tmp_name'];
                    $fileName = $_FILES['foto']['name'];
                    $fileExt  = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                    $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];

                    if (!in_array($fileExt, $allowedExts)) {
                        $error = "Format file foto tidak didukung (hanya JPG, JPEG, PNG, WEBP).";
                    } elseif ($_FILES['foto']['size'] > 5 * 1024 * 1024) {
                        $error = "Ukuran file foto maksimal 5 MB.";
                    } else {
                        $fotoFilename = "profil_" . $username . "_" . time() . "." . $fileExt;
                        $destPath = $uploadDir . $fotoFilename;
                        if (!move_uploaded_file($fileTmp, $destPath)) {
                            $error = "Gagal mengunggah foto ke folder /assets/uploads.";
                        }
                    }
                }

                if (empty($error)) {
                    if ($action === 'create') {
                        // Default password jika kosong: username + '*'
                        if (empty($password)) {
                            $password = $username . '*';
                        }
                        $stmt = $conn->prepare("INSERT INTO users (nama, username, password, fakultas, tahun, role, foto) VALUES (?, ?, ?, ?, ?, 'mahasiswa', ?)");
                        $stmt->bind_param("ssssss", $nama, $username, $password, $fakultas, $tahun, $fotoFilename);
                        if ($stmt->execute()) {
                            $success = "Mahasiswa '{$nama}' berhasil ditambahkan!";
                        } else {
                            $error = "Gagal menambah mahasiswa: " . $conn->error;
                        }
                    } elseif ($action === 'update') {
                        // Ambil foto lama jika tidak ganti foto
                        if ($fotoFilename === null) {
                            $oldQ = mysqli_query($conn, "SELECT foto FROM users WHERE id = $id");
                            $oldRow = mysqli_fetch_assoc($oldQ);
                            $fotoFilename = $oldRow['foto'] ?? null;
                        }

                        if (!empty($password)) {
                            $stmt = $conn->prepare("UPDATE users SET nama=?, username=?, password=?, fakultas=?, tahun=?, foto=? WHERE id=?");
                            $stmt->bind_param("ssssssi", $nama, $username, $password, $fakultas, $tahun, $fotoFilename, $id);
                        } else {
                            $stmt = $conn->prepare("UPDATE users SET nama=?, username=?, fakultas=?, tahun=?, foto=? WHERE id=?");
                            $stmt->bind_param("sssssi", $nama, $username, $fakultas, $tahun, $fotoFilename, $id);
                        }

                        if ($stmt->execute()) {
                            $success = "Data mahasiswa '{$nama}' berhasil diperbarui!";
                        } else {
                            $error = "Gagal memperbarui data: " . $conn->error;
                        }
                    }
                }
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            // Hapus presensi terkait
            $conn->query("DELETE FROM attendance WHERE user_id = $id");
            // Hapus user
            $delStmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'mahasiswa'");
            $delStmt->bind_param("i", $id);
            if ($delStmt->execute()) {
                $success = "Mahasiswa berhasil dihapus beserta riwayat presensinya.";
            } else {
                $error = "Gagal menghapus mahasiswa: " . $conn->error;
            }
        }
    }
}

// ── Search & Pagination ───────────────────────────────────────
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
    <title>Kelola Mahasiswa - ePresence Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background-color: #f4f6f9; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .navbar-custom { background-color: #ffffff; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .navbar-brand-custom { font-weight: 700; color: #212529; }
        .navbar-brand-custom span { color: #FECA0A; }
        .admin-card { border: none; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); background: #ffffff; }
        .avatar-sm { width: 44px; height: 44px; border-radius: 50%; object-fit: cover; border: 2px solid #FECA0A; }
        .btn-gold { background-color: #FECA0A; border: none; color: #212529; font-weight: 600; }
        .btn-gold:hover { background-color: #e5b609; color: #212529; }
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
        <div class="col-12 col-lg-11">
            <div class="card admin-card p-4 mb-4">
                
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show border-0 py-2 mb-3" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($success) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show border-0 py-2 mb-3" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 gap-3">
                    <div>
                        <h4 class="fw-bold m-0"><i class="bi bi-people-fill text-warning me-2"></i>Kelola Data Mahasiswa</h4>
                        <small class="text-muted">Total terdaftar: <strong><?= $total ?></strong> mahasiswa</small>
                    </div>

                    <div class="d-flex gap-2 align-items-center flex-wrap">
                        <!-- Search -->
                        <form method="GET" class="d-flex gap-2">
                            <div class="input-group input-group-sm" style="width: 220px;">
                                <input type="text" class="form-control" name="q" placeholder="Cari nama / NPM..." value="<?= htmlspecialchars($search) ?>">
                                <button class="btn btn-warning fw-semibold" type="submit"><i class="bi bi-search"></i></button>
                            </div>
                        </form>
                        <!-- Tombol Tambah Mahasiswa -->
                        <button type="button" class="btn btn-gold btn-sm px-3 rounded-pill" onclick="openAddModal()">
                            <i class="bi bi-person-plus-fill me-1"></i>Tambah Mahasiswa
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle border-0">
                        <thead class="table-light">
                            <tr>
                                <th class="border-0" style="width:50px;">No</th>
                                <th class="border-0">Foto</th>
                                <th class="border-0">Nama & NPM</th>
                                <th class="border-0 d-none d-lg-table-cell">Program Studi</th>
                                <th class="border-0 d-none d-md-table-cell">Tahun</th>
                                <th class="border-0 text-end" style="width:140px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $no = $offset + 1;
                            while ($row = $data->fetch_assoc()):
                                $fotoUrl = fotoProfilUrl($row['foto'] ?? '');
                                $hasCustomFoto = !empty($row['foto']) && $row['foto'] !== 'default.png';
                            ?>
                            <tr class="student-row"
                                data-id="<?= $row['id'] ?>"
                                data-nama="<?= htmlspecialchars($row['nama']) ?>"
                                data-npm="<?= htmlspecialchars($row['username']) ?>"
                                data-fakultas="<?= htmlspecialchars($row['fakultas'] ?? '') ?>"
                                data-tahun="<?= htmlspecialchars($row['tahun'] ?? '') ?>"
                                data-foto="<?= htmlspecialchars($fotoUrl) ?>">
                                <td><?= $no++ ?></td>
                                <td>
                                    <?php if ($hasCustomFoto): ?>
                                        <img src="<?= htmlspecialchars($fotoUrl) ?>" class="avatar-sm" alt="Foto">
                                    <?php else: ?>
                                        <div class="avatar-sm d-flex align-items-center justify-content-center bg-light" style="border-color: #dee2e6;">
                                            <i class="bi bi-person-fill fs-5 text-secondary"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark"><?= htmlspecialchars($row['nama']) ?></div>
                                    <small class="text-muted"><i class="bi bi-card-text me-1"></i><?= htmlspecialchars($row['username']) ?></small>
                                </td>
                                <td class="d-none d-lg-table-cell text-secondary"><?= htmlspecialchars($row['fakultas'] ?? '-') ?></td>
                                <td class="d-none d-md-table-cell text-secondary"><?= htmlspecialchars($row['tahun'] ?? '-') ?></td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-outline-primary btn-sm rounded-circle me-1" title="Edit Mahasiswa" onclick='openEditModal(<?= json_encode($row) ?>)'>
                                        <i class="bi bi-pencil-fill"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-danger btn-sm rounded-circle me-1" title="Hapus Mahasiswa" onclick='confirmDelete(<?= $row['id'] ?>, "<?= htmlspecialchars(addslashes($row['nama'])) ?>")'>
                                        <i class="bi bi-trash-fill"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-warning btn-sm rounded-circle" title="Lihat Profil" onclick='openDetailModal(<?= json_encode($row) ?>)'>
                                        <i class="bi bi-eye-fill"></i>
                                    </button>
                                </td>
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
                    <a class="btn btn-outline-secondary btn-sm px-3" href="/admin/dashboard">
                        <i class="bi bi-arrow-left me-1"></i>Kembali ke Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Form (Tambah & Edit) -->
<div class="modal fade" id="formModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius: 16px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="formModalTitle"><i class="bi bi-person-plus-fill text-warning me-2"></i>Tambah Mahasiswa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body p-4">
                    <input type="hidden" name="action" id="formAction" value="create">
                    <input type="hidden" name="id" id="formId" value="">

                    <!-- Preview Foto & Upload -->
                    <div class="text-center mb-3">
                        <img id="formFotoPreview" src="/public/assets/logo.png" class="rounded-circle shadow-sm mb-2" style="width: 90px; height: 90px; object-fit: cover; border: 3px solid #FECA0A;" alt="Preview Foto">
                        <div>
                            <label for="fotoInput" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                                <i class="bi bi-camera-fill me-1"></i>Pilih Foto Profil
                            </label>
                            <input type="file" name="foto" id="fotoInput" class="d-none" accept="image/png,image/jpeg,image/webp">
                        </div>
                        <small class="text-muted d-block mt-1" style="font-size: 0.75rem;">Format JPG, PNG, WEBP (Maks. 5 MB)</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">NPM / Username <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="username" id="inputUsername" required placeholder="Contoh: 140110230001">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="nama" id="inputNama" required placeholder="Nama lengkap mahasiswa">
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-7">
                            <label class="form-label small fw-semibold text-secondary">Program Studi</label>
                            <input type="text" class="form-control" name="fakultas" id="inputFakultas" placeholder="Contoh: Matematika">
                        </div>
                        <div class="col-5">
                            <label class="form-label small fw-semibold text-secondary">Tahun Angkatan</label>
                            <input type="text" class="form-control" name="tahun" id="inputTahun" placeholder="Contoh: 2026">
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label small fw-semibold text-secondary">Password</label>
                        <input type="password" class="form-control" name="password" id="inputPassword" placeholder="Kosongkan jika tidak ingin diubah">
                        <div class="form-text" id="passwordHelp" style="font-size: 0.75rem;">Default saat tambah baru: <code>NPM + *</code> (misal: 140110230001*)</div>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning btn-sm fw-semibold px-3" id="btnSubmitForm">Simpan Mahasiswa</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi Hapus -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow" style="border-radius: 16px;">
            <div class="modal-body text-center p-4">
                <i class="bi bi-exclamation-triangle-fill text-danger fs-1 mb-2"></i>
                <h5 class="fw-bold mb-2">Hapus Mahasiswa?</h5>
                <p class="text-secondary small mb-3">Yakin ingin menghapus <strong id="deleteNama" class="text-dark"></strong>? Seluruh data presensi terkait juga akan dihapus permanen.</p>
                <form method="POST">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="deleteId" value="">
                    <div class="d-flex justify-content-center gap-2">
                        <button type="button" class="btn btn-light btn-sm px-3" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger btn-sm px-3 fw-semibold">Ya, Hapus</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detail Mahasiswa -->
<div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius: 16px;">
            <div class="modal-header border-0 pb-0">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center pt-0 pb-4">
                <div class="mb-3 d-flex justify-content-center">
                    <img id="detailFoto" src="" alt="Foto Mahasiswa" class="shadow-sm" style="width: 130px; height: 130px; border-radius: 50%; object-fit: cover; border: 4px solid #FECA0A;">
                </div>
                <h4 class="fw-bold mb-1" id="detailNama">-</h4>
                <p class="text-secondary mb-3">NPM: <span id="detailNPM">-</span></p>
                
                <div class="border-top pt-3 text-start px-3">
                    <div class="row">
                        <div class="col-6 mb-2">
                            <span class="text-muted d-block small">Program Studi</span>
                            <span class="fw-semibold text-dark" id="detailFakultas">-</span>
                        </div>
                        <div class="col-6 mb-2">
                            <span class="text-muted d-block small">Tahun</span>
                            <span class="fw-semibold text-dark" id="detailTahun">-</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 bg-light py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
const formModal = new bootstrap.Modal(document.getElementById('formModal'));
const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
const detailModal = new bootstrap.Modal(document.getElementById('detailModal'));

const fotoInput = document.getElementById('fotoInput');
const formFotoPreview = document.getElementById('formFotoPreview');

// Live preview foto yang dipilih
fotoInput.addEventListener('change', function(){
    if (this.files && this.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e){
            formFotoPreview.src = e.target.result;
        };
        reader.readAsDataURL(this.files[0]);
    }
});

function openAddModal(){
    document.getElementById('formAction').value = 'create';
    document.getElementById('formId').value = '';
    document.getElementById('formModalTitle').innerHTML = '<i class="bi bi-person-plus-fill text-warning me-2"></i>Tambah Mahasiswa';
    document.getElementById('inputUsername').value = '';
    document.getElementById('inputUsername').readOnly = false;
    document.getElementById('inputNama').value = '';
    document.getElementById('inputFakultas').value = '';
    document.getElementById('inputTahun').value = '<?= date("Y") ?>';
    document.getElementById('inputPassword').value = '';
    document.getElementById('passwordHelp').textContent = 'Default saat tambah baru: NPM + * (contoh: 140110230001*)';
    formFotoPreview.src = '/public/assets/logo.png';
    fotoInput.value = '';
    document.getElementById('btnSubmitForm').textContent = 'Simpan Mahasiswa';
    formModal.show();
}

function openEditModal(data){
    document.getElementById('formAction').value = 'update';
    document.getElementById('formId').value = data.id;
    document.getElementById('formModalTitle').innerHTML = '<i class="bi bi-pencil-square text-warning me-2"></i>Edit Data Mahasiswa';
    document.getElementById('inputUsername').value = data.username;
    document.getElementById('inputUsername').readOnly = false;
    document.getElementById('inputNama').value = data.nama;
    document.getElementById('inputFakultas').value = data.fakultas || '';
    document.getElementById('inputTahun').value = data.tahun || '';
    document.getElementById('inputPassword').value = '';
    document.getElementById('passwordHelp').textContent = 'Kosongkan jika tidak ingin mengubah password';
    
    // Preview foto yang sudah ada
    let fotoUrl = '/public/assets/logo.png';
    if (data.foto && data.foto !== 'default.png') {
        if (data.foto.startsWith('http')) {
            fotoUrl = data.foto;
        } else {
            fotoUrl = '/public/assets/uploads/' + data.foto.replace(/^\/+(public\/+(assets\/+)?)?uploads\/+/, '');
        }
    }
    formFotoPreview.src = fotoUrl;
    fotoInput.value = '';
    document.getElementById('btnSubmitForm').textContent = 'Perbarui Data';
    formModal.show();
}

function confirmDelete(id, nama){
    document.getElementById('deleteId').value = id;
    document.getElementById('deleteNama').textContent = nama;
    deleteModal.show();
}

function openDetailModal(data){
    document.getElementById('detailNama').textContent = data.nama;
    document.getElementById('detailNPM').textContent = data.username;
    document.getElementById('detailFakultas').textContent = data.fakultas || '-';
    document.getElementById('detailTahun').textContent = data.tahun || '-';
    
    let fotoUrl = '/public/assets/logo.png';
    if (data.foto && data.foto !== 'default.png') {
        if (data.foto.startsWith('http')) {
            fotoUrl = data.foto;
        } else {
            fotoUrl = '/public/assets/uploads/' + data.foto.replace(/^\/+(public\/+(assets\/+)?)?uploads\/+/, '');
        }
    }
    document.getElementById('detailFoto').src = fotoUrl;
    detailModal.show();
}
</script>
</body>
</html>
