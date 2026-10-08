<?php
session_start();
if (isset($_SESSION['user'])) {
    if ($_SESSION['user']['role'] === 'admin') {
        header("Location: /admin/dashboard");
    } else {
        header("Location: /user/dashboard");
    }
} else {
    header("Location: /auth/login");
}
exit();
?>
