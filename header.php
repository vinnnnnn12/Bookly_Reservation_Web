<?php
require_once __DIR__ . '/session_init.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservasi Ruangan Rapat</title>
    <link rel="stylesheet" href="style.css">
    <!-- Google Font untuk Bookly -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@600&display=swap" rel="stylesheet">
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="nav-container">
                <div class="nav-brand">
                    <a href="index.php" style="font-family: 'Playfair Display', serif; font-size: 2.2rem; font-weight: 700; letter-spacing: 2px; text-shadow: 2px 2px 4px rgba(0,0,0,0.3);">
                        Bookly
                    </a>
                </div>
                <ul class="nav-menu">
                    <li><a href="index.php">Beranda</a></li>
                    
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <?php if($_SESSION['role'] == 'admin'): ?>
                            <li><a href="admin.php">Dashboard Admin</a></li>
                        <?php else: ?>
                            <li><a href="dashboard.php">Dashboard Saya</a></li>
                        <?php endif; ?>
                        <li>
                            <span style="color: white; font-weight: bold; margin-right: 10px;">
                                <?php if($_SESSION['role'] == 'admin'): ?>
                                    ADMIN
                                <?php else: ?>
                                    <?= $_SESSION['nama'] ?>
                                <?php endif; ?>
                            </span>
                        </li>
                        <li><a href="logout.php" class="btn-logout">Logout</a></li>
                    <?php else: ?>
                        <li><a href="login.php">Login</a></li>
                        <li><a href="register.php">Register</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </nav>
    </header>
    <main>