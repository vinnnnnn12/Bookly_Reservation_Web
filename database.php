<?php
// =============================================
// FILE: database.php - Koneksi Database
// Mendukung 2 mode:
// 1) Lokal (XAMPP)        -> pakai default di bawah
// 2) Vercel + Aiven MySQL -> pakai Environment Variables
// =============================================

// Ambil konfigurasi dari Environment Variables (di-set di Vercel),
// kalau tidak ada, fallback ke default untuk development lokal (XAMPP)
$host   = getenv('DB_HOST') ?: 'localhost';
$port   = getenv('DB_PORT') ?: '3306';
$user   = getenv('DB_USER') ?: 'root';
$pass   = getenv('DB_PASS') ?: '';
$db     = getenv('DB_NAME') ?: 'reservasi_ruangan';
$sslCa  = getenv('DB_SSL_CA') ?: ''; // isi file ca.pem dari Aiven (ISI-nya, bukan path)

$conn = mysqli_init();

if (!$conn) {
    die('Koneksi gagal: tidak bisa inisialisasi mysqli.');
}

if (!empty($sslCa)) {
    // ===== MODE AIVEN (butuh SSL) =====
    // Tulis isi certificate ke file sementara di /tmp (satu-satunya folder writable di Vercel)
    $caPath = '/tmp/aiven-ca.pem';
    if (!file_exists($caPath)) {
        file_put_contents($caPath, $sslCa);
    }

    mysqli_ssl_set($conn, null, null, $caPath, null, null);
    $ok = @mysqli_real_connect(
        $conn,
        $host,
        $user,
        $pass,
        $db,
        (int) $port,
        null,
        MYSQLI_CLIENT_SSL
    );
} else {
    // ===== MODE LOKAL (tanpa SSL, XAMPP) =====
    $ok = @mysqli_real_connect($conn, $host, $user, $pass, $db, (int) $port);
}

// Cek koneksi
if (!$ok) {
    die('Koneksi gagal: ' . mysqli_connect_error());
}

// Set charset
mysqli_set_charset($conn, 'utf8mb4');

// =============================================
// FUNGSI BANTUAN
// =============================================

// Fungsi untuk menjalankan query
function query($sql) {
    global $conn;
    return mysqli_query($conn, $sql);
}

// Fungsi untuk mengambil semua data
function fetchAll($result) {
    $data = [];
    while($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
    return $data;
}

// Fungsi untuk mengambil satu data
function fetchOne($result) {
    return mysqli_fetch_assoc($result);
}

// Fungsi untuk menghitung jumlah baris
function numRows($result) {
    return mysqli_num_rows($result);
}

// Fungsi untuk mengamankan input (anti SQL injection)
function escape($string) {
    global $conn;
    return mysqli_real_escape_string($conn, $string);
}

// Fungsi untuk test koneksi
function testConnection() {
    global $conn;
    return $conn ? true : false;
}
?>
