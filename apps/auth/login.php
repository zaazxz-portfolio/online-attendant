<?php
session_start();
require __DIR__ . "/../../utils/db.php";

if (isset($_SESSION['user'])) {
    if ($_SESSION['user']['role'] === 'admin') {
        header("Location: /admin/dashboard");
        exit();
    }
    header("Location: /user/dashboard");
    exit();
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    $stmt = $conn->prepare("SELECT * FROM users WHERE username=? AND password=?");
    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $u = $result->fetch_assoc();
        $_SESSION['user'] = $u;
        if ($u['role'] === 'admin') {
            header("Location: /admin/dashboard");
            exit();
        }
        header("Location: /user/dashboard");
        exit();
    } else {
        $error = "Username atau password salah";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ePresence</title>
    <!-- Bootstrap 5.3 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            background: #ffffff;
            width: 100%;
            max-width: 400px;
            padding: 2.5rem 2rem;
        }
        .btn-gold {
            background-color: #FECA0A;
            border-color: #FECA0A;
            color: #212529;
            font-weight: 600;
            padding: 0.75rem;
            border-radius: 8px;
            transition: all 0.2s;
        }
        .btn-gold:hover {
            background-color: #e5b609;
            border-color: #e5b609;
            color: #212529;
        }
        .form-control {
            border-radius: 8px;
            padding: 0.75rem 1rem;
        }
        .form-control:focus {
            border-color: #FECA0A;
            box-shadow: 0 0 0 0.25rem rgba(254, 202, 10, 0.25);
        }
        .logo-img {
            max-width: 90px;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>

<div class="container d-flex justify-content-center">
    <div class="login-card text-center">
        <img src="/public/assets/logo.png" class="logo-img img-fluid" alt="ePresence Logo">
        <h3 class="fw-bold mb-1">ePresence</h3>
        <p class="text-muted mb-4 small">KKNM Location Attendance</p>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show text-start py-2 px-3 mb-3" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
                <button type="submit" class="btn-close py-2.5 px-3" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <form method="POST" autocomplete="off" class="text-start">
            <div class="mb-3">
                <label for="username" class="form-label small fw-semibold text-secondary">NPM / Username</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-person text-secondary"></i></span>
                    <input type="text" class="form-control bg-light border-start-0" name="username" id="username" placeholder="Masukkan NPM Anda" required>
                </div>
            </div>

            <div class="mb-4">
                <label for="password" class="form-label small fw-semibold text-secondary">Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock text-secondary"></i></span>
                    <input type="password" class="form-control bg-light border-start-0 border-end-0" name="password" id="password" placeholder="Masukkan Password Anda" required>
                    <button class="btn btn-light border border-start-0 text-secondary bg-light" type="button" id="togglePassword">
                        <i class="bi bi-eye-slash" id="toggleIcon"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-gold w-100 mb-2">MASUK</button>
        </form>

        <div class="mt-4 pt-2 border-top">
            <small class="text-muted" style="font-size: 0.75rem;">
                Reserved by <a href="https://temanngoding.id" target="_blank" rel="noopener noreferrer" class="text-decoration-none fw-semibold text-secondary">temanngoding.id</a>, Copyright &copy; 2026
            </small>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const username = document.getElementById("username");
    const password = document.getElementById("password");

    username.oninvalid = function() {
        this.setCustomValidity("NPM tidak boleh kosong!");
    };
    username.oninput = function() {
        this.setCustomValidity("");
    };

    password.oninvalid = function() {
        this.setCustomValidity("Password wajib diisi!");
    };
    password.oninput = function() {
        this.setCustomValidity("");
    };

    const togglePassword = document.getElementById("togglePassword");
    const toggleIcon = document.getElementById("toggleIcon");

    togglePassword.addEventListener("click", function () {
        const type = password.getAttribute("type") === "password" ? "text" : "password";
        password.setAttribute("type", type);
        toggleIcon.className = type === "password" ? "bi bi-eye-slash" : "bi bi-eye";
    });
</script>
</body>
</html>
