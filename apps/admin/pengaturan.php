<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: /auth/login");
    exit();
}

require __DIR__ . "/../../utils/db.php";

$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $baseLat = floatval($_POST['base_lat'] ?? 0);
    $baseLng = floatval($_POST['base_lng'] ?? 0);
    $radius  = intval($_POST['radius'] ?? 100);

    if ($baseLat == 0 || $baseLng == 0) {
        $error = "Koordinat latitude dan longitude harus diisi.";
    } elseif ($radius < 10 || $radius > 5000) {
        $error = "Radius harus antara 10 - 5000 meter.";
    } else {
        $check = mysqli_query($conn, "SELECT id FROM settings LIMIT 1");
        if (mysqli_num_rows($check) > 0) {
            $id = mysqli_fetch_assoc($check)['id'];
            $stmt = $conn->prepare("UPDATE settings SET base_lat=?, base_lng=?, radius=? WHERE id=?");
            $stmt->bind_param("ddii", $baseLat, $baseLng, $radius, $id);
        } else {
            $stmt = $conn->prepare("INSERT INTO settings (base_lat, base_lng, radius) VALUES (?, ?, ?)");
            $stmt->bind_param("ddi", $baseLat, $baseLng, $radius);
        }
        if ($stmt->execute()) {
            $success = "Pengaturan berhasil disimpan!";
        } else {
            $error = "Gagal menyimpan: " . $conn->error;
        }
    }
}

// Get current settings
$setting = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM settings LIMIT 1"));
$baseLat = $setting['base_lat'] ?? -6.8936;
$baseLng = $setting['base_lng'] ?? 107.6104;
$radius  = $setting['radius'] ?? 100;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan - ePresence Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background-color: #f4f6f9; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .navbar-custom { background-color: #ffffff; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .navbar-brand-custom { font-weight: 700; color: #212529; }
        .navbar-brand-custom span { color: #FECA0A; }
        .admin-card { border: none; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); background: #ffffff; }
        .map-container { width: 100%; height: 300px; border-radius: 12px; overflow: hidden; border: 2px solid #dee2e6; background: #e9ecef; }
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
            <div class="card admin-card p-4 mb-4">
                <h4 class="fw-bold mb-4"><i class="bi bi-gear-fill text-warning me-2"></i>Pengaturan Lokasi & Radius</h4>

                <?php if ($success): ?>
                    <div class="alert alert-success border-0 py-2"><i class="bi bi-check-circle-fill me-2"></i><?= $success ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger border-0 py-2"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= $error ?></div>
                <?php endif; ?>

                <!-- Map Preview -->
                <div class="mb-4">
                    <label class="form-label fw-semibold text-secondary small">Pratinjau Lokasi</label>
                    <div class="map-container" id="mapContainer">
                        <iframe
                            id="mapFrame"
                            width="100%" height="100%"
                            style="border:0;"
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"
                            src="https://maps.google.com/maps?q=<?= $baseLat ?>,<?= $baseLng ?>&z=17&output=embed">
                        </iframe>
                    </div>
                </div>

                <form method="POST">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label for="base_lat" class="form-label fw-semibold text-secondary small">Base Latitude</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-geo-alt text-warning"></i></span>
                                <input type="text" class="form-control" name="base_lat" id="base_lat" value="<?= $baseLat ?>" required>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <label for="base_lng" class="form-label fw-semibold text-secondary small">Base Longitude</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-geo-alt-fill text-warning"></i></span>
                                <input type="text" class="form-control" name="base_lng" id="base_lng" value="<?= $baseLng ?>" required>
                            </div>
                        </div>
                        <div class="col-12">
                            <label for="radius" class="form-label fw-semibold text-secondary small">Radius Presensi (meter)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-bullseye text-warning"></i></span>
                                <input type="number" class="form-control" name="radius" id="radius" value="<?= $radius ?>" min="10" max="5000" required>
                                <span class="input-group-text bg-light">meter</span>
                            </div>
                            <div class="form-text">Jarak maksimum dari titik base agar presensi dianggap valid.</div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-4 flex-wrap gap-2">
                        <a class="btn btn-outline-secondary px-3" href="/admin/dashboard">
                            <i class="bi bi-arrow-left me-1"></i>Kembali
                        </a>
                        <button type="submit" class="btn btn-warning fw-semibold px-4">
                            <i class="bi bi-check-lg me-1"></i>Simpan Pengaturan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Update map preview when lat/lng changes
const latInput = document.getElementById('base_lat');
const lngInput = document.getElementById('base_lng');
const mapFrame = document.getElementById('mapFrame');

function updateMap(){
    const lat = latInput.value;
    const lng = lngInput.value;
    if(lat && lng){
        mapFrame.src = `https://maps.google.com/maps?q=${lat},${lng}&z=17&output=embed`;
    }
}

latInput.addEventListener('change', updateMap);
lngInput.addEventListener('change', updateMap);
</script>
</body>
</html>
