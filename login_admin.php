<?php
require_once __DIR__ . '/session_init.php';

if (isset($_SESSION['user_id']) && $_SESSION['role'] == 'admin') {
    header('Location: admin.php');
    exit();
}

if (isset($_SESSION['user_id']) && $_SESSION['role'] == 'client') {
    header('Location: index.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = escape($_POST['email']);
    $password = md5($_POST['password']);
    
    $query = "SELECT * FROM users WHERE email = '$email' AND password = '$password' AND role = 'admin'";
    $result = query($query);
    
    if (numRows($result) == 1) {
        $user = fetchOne($result);
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['nama'] = $user['nama'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        
        header('Location: admin.php');
        exit();
    } else {
        $error = 'Email atau password salah! Pastikan Anda login sebagai Admin.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - Reservasi Ruangan</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="form-container">
        <h2>Login Admin</h2>
        <p style="text-align: center; color: #666; margin-bottom: 25px; font-size: 0.95rem;">
            Hanya untuk Administrator
        </p>
        
        <?php if($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required 
                       placeholder="Masukkan email admin">
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required 
                       placeholder="Masukkan password">
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn" style="width: 100%;">Login sebagai Admin</button>
            </div>
        </form>
        
        <div style="text-align: center; margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee;">
            <a href="login.php" style="color: #1a237e; text-decoration: none;">Kembali ke pilihan login</a>
        </div>
    </div>
</body>
</html>