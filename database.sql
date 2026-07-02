-- =============================================
-- DATABASE: reservasi_ruangan
-- =============================================
-- CATATAN PENTING UNTUK AIVEN:
-- Aiven MySQL biasanya sudah menyediakan 1 database (misalnya "defaultdb")
-- dan user Anda TIDAK punya izin CREATE DATABASE tambahan di paket gratis.
-- Jadi baris "CREATE DATABASE" & "USE" di bawah ini SENGAJA di-nonaktifkan.
-- Cukup jalankan sisa script ini langsung di database yang sudah Aiven berikan.
-- (Kalau jalan di lokal/XAMPP, boleh un-comment 2 baris di bawah)

-- CREATE DATABASE IF NOT EXISTS reservasi_ruangan;
-- USE reservasi_ruangan;

-- =============================================
-- TABEL 1: users
-- =============================================
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'client') DEFAULT 'client',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =============================================
-- TABEL 2: rooms
-- =============================================
CREATE TABLE IF NOT EXISTS rooms (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nama VARCHAR(100) NOT NULL,
    lantai INT NOT NULL,
    kapasitas VARCHAR(50) NOT NULL,
    fasilitas TEXT,
    deskripsi TEXT,
    gambar VARCHAR(255) DEFAULT 'default.jpg',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =============================================
-- TABEL 3: reservations
-- =============================================
CREATE TABLE IF NOT EXISTS reservations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    room_id INT NOT NULL,
    user_id INT NOT NULL,
    nama_pemesan VARCHAR(100) NOT NULL,
    tanggal DATE NOT NULL,
    jam_mulai TIME NOT NULL,
    jam_selesai TIME NOT NULL,
    keperluan TEXT,
    status ENUM('booked', 'canceled', 'done') DEFAULT 'booked',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =============================================
-- TABEL 4: sessions (dipakai session_init.php agar login tetap
-- bekerja di lingkungan serverless Vercel)
-- =============================================
CREATE TABLE IF NOT EXISTS sessions (
    id VARCHAR(191) PRIMARY KEY,
    data MEDIUMTEXT,
    last_activity INT NOT NULL
);

-- =============================================
-- SEED DATA: Admin & Ruangan
-- =============================================

-- Insert Admin (email: admin@reservasi.com, password: admin123)
INSERT INTO users (nama, email, password, role) VALUES
('Administrator', 'admin@reservasi.com', MD5('admin123'), 'admin');

-- Insert 8 Ruangan
INSERT INTO rooms (nama, lantai, kapasitas, fasilitas, deskripsi) VALUES
('Ruang Rapat Kecil', 1, '2-4 orang', 'TV 45", Whiteboard kaca, Meja kecil, 4 kursi', 'Cocok untuk diskusi tim kecil'),
('Ruang Rapat Standar', 1, '6-8 orang', 'TV 60", Proyektor, Whiteboard, Meja oval', 'Ruang rapat standar untuk meeting tim'),
('Ruang Rapat Utama', 2, '10-14 orang', 'TV Ganda, Kamera PTZ, Meja premium, Dispenser', 'Ruang rapat utama dengan fasilitas lengkap'),
('Ruang Rapat Besar', 2, '15-20 orang', 'Proyektor laser, Sound system, Mini bar', 'Untuk rapat besar atau presentasi'),
('Ruang Rapat Memanjang', 3, '4-6 orang', 'TV 50", Sliding whiteboard, Meja ramping', 'Ruang rapat hemat tempat'),
('Ruang Rapat Persegi', 3, '8-10 orang', 'TV 65", Kamera 360, Whiteboard 2 sisi', 'Ruang rapat dengan kamera 360 derajat'),
('Ruang Rapat Fleksibel', 4, '12-15 orang', 'TV, Whiteboard portabel, Meja lipat', 'Ruang rapat fleksibel yang bisa diatur'),
('Aula Mini', 4, '40 orang', 'LED Wall, Podium, AC sentral, Kursi lecture', 'Untuk acara besar atau seminar');
