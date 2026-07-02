<?php
require_once __DIR__ . '/session_init.php';

// Wajib login dulu sebelum bisa lihat detail ruangan
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    header('Location: index.php');
    exit();
}

$query = "SELECT * FROM rooms WHERE id = $id";
$result = query($query);

if (numRows($result) == 0) {
    header('Location: index.php');
    exit();
}

$room = fetchOne($result);

// Cek gambar
$gambarPath = 'uploads/' . $room['gambar'];
if (!file_exists($gambarPath)) {
    $gambarPath = 'uploads/default.jpg';
}

// Ambil jadwal reservasi yang aktif
$jadwal = "SELECT r.*, u.nama as user_nama 
           FROM reservations r 
           JOIN users u ON r.user_id = u.id
           WHERE r.room_id = $id AND r.status = 'booked' 
           AND r.tanggal >= CURDATE()
           ORDER BY r.tanggal, r.jam_mulai";
$jadwalResult = query($jadwal);

$isBooked = numRows($jadwalResult) > 0;
?>
<?php include 'header.php'; ?>

<div class="detail-container">
    <!-- Tombol Kembali ke Beranda -->
    <div style="padding: 15px 30px; background: #f8f9fa; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
        <a href="index.php" class="btn" style="background: #6c757d; padding: 8px 20px; font-size: 0.9rem;">
            ← Kembali ke Beranda
        </a>
        <?php if(isset($_SESSION['user_id']) && $_SESSION['role'] == 'client'): ?>
            <?php if(!$isBooked): ?>
                <a href="reservasi.php?room_id=<?= $room['id'] ?>" class="btn btn-success" style="font-size: 0.9rem; padding: 8px 20px;">
                    Pesan Sekarang
                </a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <div class="detail-image" style="background: none; height: 400px; overflow: hidden;">
        <img src="<?= $gambarPath ?>" alt="<?= htmlspecialchars($room['nama']) ?>" 
             style="width: 100%; height: 100%; object-fit: cover; display: block;">
    </div>
    
    <div class="detail-content">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
            <h1 style="margin: 0;"><?= htmlspecialchars($room['nama']) ?></h1>
            <?php if($isBooked): ?>
                <span style="background: #dc3545; color: white; padding: 5px 20px; border-radius: 20px; font-size: 0.9rem; font-weight: bold;">
                    Sedang Booking
                </span>
            <?php else: ?>
                <span style="background: #28a745; color: white; padding: 5px 20px; border-radius: 20px; font-size: 0.9rem; font-weight: bold;">
                    Tersedia
                </span>
            <?php endif; ?>
        </div>
        
        <div class="info">
            <div class="info-item">
                <strong>Lantai</strong>
                Lantai <?= $room['lantai'] ?>
            </div>
            <div class="info-item">
                <strong>Kapasitas</strong>
                <?= htmlspecialchars($room['kapasitas']) ?>
            </div>
            <div class="info-item">
                <strong>Fasilitas</strong>
                <?= htmlspecialchars($room['fasilitas']) ?>
            </div>
            <div class="info-item" style="grid-column: 1 / -1;">
                <strong>Status</strong>
                <?php if($isBooked): ?>
                    <span style="color: #dc3545; font-weight: bold;">Tidak Tersedia (Sudah dibooking)</span>
                <?php else: ?>
                    <span style="color: #28a745; font-weight: bold;">Tersedia</span>
                <?php endif; ?>
            </div>
        </div>
        
        <div style="margin: 20px 0;">
            <h3>Deskripsi</h3>
            <p><?= htmlspecialchars($room['deskripsi']) ?></p>
        </div>
        
        <?php if(isset($_SESSION['user_id']) && $_SESSION['role'] == 'client'): ?>
            <?php if($isBooked): ?>
                <div style="background: #f8d7da; padding: 15px; border-radius: 8px; margin: 15px 0; border: 1px solid #f5c6cb;">
                    <p style="color: #721c24; margin: 0; font-weight: bold;">
                        Maaf, ruangan ini sedang tidak tersedia untuk tanggal-tanggal berikut:
                    </p>
                </div>
            <?php else: ?>
                <a href="reservasi.php?room_id=<?= $room['id'] ?>" class="btn btn-success" style="font-size: 1.1rem;">
                    Pesan Sekarang
                </a>
            <?php endif; ?>
        <?php elseif(!isset($_SESSION['user_id'])): ?>
            <p style="color: #666;">
                <a href="login.php">Login</a> untuk melakukan reservasi.
            </p>
        <?php endif; ?>
        
        <!-- Jadwal Lengkap -->
        <div style="margin-top: 30px;">
            <h3>Jadwal Reservasi</h3>
            
            <?php if(numRows($jadwalResult) > 0): ?>
                <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                    <p style="margin: 0; color: #856404; font-size: 0.9rem;">
                        <strong>Total Booking:</strong> <?= numRows($jadwalResult) ?> jadwal
                    </p>
                </div>
                
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Jam</th>
                                <th>Pemesan</th>
                                <th>Keperluan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($jadwal = fetchOne($jadwalResult)): ?>
                            <tr>
                                <td><?= date('d/m/Y', strtotime($jadwal['tanggal'])) ?></td>
                                <td><?= substr($jadwal['jam_mulai'], 0, 5) ?> - <?= substr($jadwal['jam_selesai'], 0, 5) ?></td>
                                <td><?= htmlspecialchars($jadwal['user_nama']) ?></td>
                                <td><?= htmlspecialchars($jadwal['keperluan'] ?: '-') ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div style="background: #d4edda; padding: 20px; text-align: center; border-radius: 8px; border: 1px solid #c3e6cb;">
                    <p style="color: #155724; margin: 0; font-size: 1rem;">
                        Belum ada jadwal reservasi. Ruangan tersedia!
                    </p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Tombol Kembali ke Beranda di Bawah -->
        <div style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #eee; text-align: center;">
            <a href="index.php" class="btn" style="background: #6c757d; padding: 10px 30px;">
                ← Kembali ke Beranda
            </a>
        </div>
    </div>
</div>

</body>
</html>