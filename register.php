<?php
require_once __DIR__ . '/session_init.php';

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'admin') {
        header('Location: admin.php');
    } else {
        header('Location: index.php');
    }
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = escape($_POST['nama']);
    $email = escape($_POST['email']);
    $password = md5($_POST['password']);
    $confirm_password = md5($_POST['confirm_password']);
    
    if (empty($nama) || empty($email) || empty($password)) {
        $error = 'Semua field harus diisi!';
    } elseif ($password != $confirm_password) {
        $error = 'Password dan konfirmasi password tidak sama!';
    } else {
        $check = "SELECT id FROM users WHERE email = '$email'";
        $result = query($check);
        
        if (numRows($result) > 0) {
            $error = 'Email sudah terdaftar!';
        } else {
            $insert = "INSERT INTO users (nama, email, password, role) 
                       VALUES ('$nama', '$email', '$password', 'client')";
            
            if (query($insert)) {
                $success = 'Registrasi berhasil! Silakan login.';
                header('refresh:2; url=login_client.php');
            } else {
                $error = 'Gagal registrasi: ' . mysqli_error($conn);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Reservasi Ruangan</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="form-container">
        <h2>Daftar Akun Client</h2>
        <p style="text-align: center; color: #666; margin-bottom: 20px;">
            Daftar untuk melakukan reservasi ruangan
        </p>
        
        <?php if($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>
        
        <?php if($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="nama">Nama Lengkap</label>
                <input type="text" id="nama" name="nama" required 
                       placeholder="Masukkan nama lengkap">
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required 
                       placeholder="Masukkan email">
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required 
                       placeholder="Masukkan password (min 6 karakter)">
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Konfirmasi Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required 
                       placeholder="Ulangi password">
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-success">Daftar</button>
            </div>
        </form>
        
        <p style="text-align: center; margin-top: 20px;">
            Sudah punya akun? <a href="login_client.php">Login di sini</a>
        </p>
        
        <p style="text-align: center; margin-top: 10px;">
            <a href="login.php">Kembali ke pilihan login</a>
        </p>
    </div>
</body>
</html>