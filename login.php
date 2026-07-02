<?php
require_once __DIR__ . '/session_init.php';

// Jika sudah login, redirect
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'admin') {
        header('Location: admin.php');
    } else {
        header('Location: index.php');
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Reservasi Ruangan</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="login-choice-container">
        <div class="login-choice-box">
            <div class="login-choice-header">
                <h2>Pilih Login</h2>
                <p>Silakan pilih peran Anda untuk melanjutkan</p>
            </div>
            
            <div class="login-choice-buttons">
                <a href="login_admin.php" class="login-choice-btn admin-btn">
                    <span class="text">Login sebagai Admin</span>
                    <span class="desc">Kelola ruangan dan reservasi</span>
                </a>
                <a href="login_client.php" class="login-choice-btn client-btn">
                    <span class="text">Login sebagai Client</span>
                    <span class="desc">Lihat dan pesan ruangan</span>
                </a>
            </div>
            
            <div class="login-choice-footer">
                <p>Belum punya akun? <a href="register.php">Daftar di sini</a></p>
            </div>
        </div>
    </div>
</body>
</html>