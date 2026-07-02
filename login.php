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

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = escape($_POST['email']);
    $password = md5($_POST['password']);
    
    // Cek di database
    $query = "SELECT * FROM users WHERE email = '$email' AND password = '$password'";
    $result = query($query);
    
    if (numRows($result) == 1) {
        $user = fetchOne($result);
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['nama'] = $user['nama'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        
        if ($user['role'] == 'admin') {
            header('Location: admin.php');
        } else {
            header('Location: index.php');
        }
        exit();
    } else {
        $error = 'Email atau password salah!';
    }
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
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <h1>Bookly</h1>
                <p>Reservasi Ruangan Rapat</p>
            </div>
            
            <?php if($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required 
                           placeholder="Masukkan email">
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required 
                           placeholder="Masukkan password">
                </div>
                
                <button type="submit" class="btn-login">Masuk</button>
            </form>
            
            <div class="login-footer">
                <p>Belum punya akun? <a href="register.php">Daftar di sini</a></p>
            </div>
            
            <div class="login-copyright">
                <p>&copy; <?= date('Y') ?> Bookly - Reservasi Ruangan</p>
            </div>
        </div>
    </div>
</body>
</html>