<?php
require_once __DIR__ . '/session_init.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'client') {
    header('Location: login.php');
    exit();
}


$user_id = $_SESSION['user_id'];

$query = "SELECT r.*, rm.nama as ruangan, rm.lantai 
          FROM reservations r 
          JOIN rooms rm ON r.room_id = rm.id 
          WHERE r.user_id = $user_id 
          ORDER BY r.tanggal DESC, r.jam_mulai DESC";
$reservations = query($query);

$statsQuery = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'booked' AND tanggal >= CURDATE() THEN 1 ELSE 0 END) as aktif,
                SUM(CASE WHEN status = 'done' THEN 1 ELSE 0 END) as selesai,
                SUM(CASE WHEN status = 'canceled' THEN 1 ELSE 0 END) as dibatalkan
               FROM reservations WHERE user_id = $user_id";
$stats = fetchOne(query($statsQuery));

if (isset($_GET['cancel']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    $cek = "SELECT tanggal FROM reservations WHERE id = $id AND user_id = $user_id AND status = 'booked'";
    $cekResult = query($cek);
    if (numRows($cekResult) > 0) {
        $data = fetchOne($cekResult);
        $tanggalReservasi = strtotime($data['tanggal']);
        $sekarang = strtotime(date('Y-m-d'));
        $selisihHari = ($tanggalReservasi - $sekarang) / (60 * 60 * 24);
        
        if ($selisihHari >= 1) {
            $update = "UPDATE reservations SET status = 'canceled' WHERE id = $id";
            if (query($update)) {
                $success = 'Reservasi berhasil dibatalkan.';
                header('refresh:2; url=dashboard.php');
            }
        } else {
            $error = 'Reservasi hanya bisa dibatalkan H-1 (minimal 1 hari sebelumnya).';
        }
    }
}
?>
<?php include 'header.php'; ?>

<div class="container">
    <h1>Dashboard Saya</h1>
    <p style="color: #666; margin-bottom: 20px;">
        Selamat datang, <strong><?= $_SESSION['nama'] ?></strong>!
    </p>
    
    <div class="stats-grid">
        <div class="stat-card">
            <h3><?= $stats['total'] ?? 0 ?></h3>
            <p>Total Reservasi</p>
        </div>
        <div class="stat-card" style="border-left: 4px solid #28a745;">
            <h3><?= $stats['aktif'] ?? 0 ?></h3>
            <p>Aktif</p>
        </div>
        <div class="stat-card" style="border-left: 4px solid #6c757d;">
            <h3><?= $stats['selesai'] ?? 0 ?></h3>
            <p>Selesai</p>
        </div>
        <div class="stat-card" style="border-left: 4px solid #dc3545;">
            <h3><?= $stats['dibatalkan'] ?? 0 ?></h3>
            <p>Dibatalkan</p>
        </div>
    </div>
    
    <?php if(isset($error) && $error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>
    
    <?php if(isset($success) && $success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>
    
    <h2>Riwayat Reservasi</h2>
    
    <?php if(numRows($reservations) > 0): ?>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Ruangan</th>
                        <th>Tanggal</th>
                        <th>Jam</th>
                        <th>Keperluan</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = fetchOne($reservations)): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($row['ruangan']) ?></strong><br>
                            <small>Lantai <?= $row['lantai'] ?></small>
                        </td>
                        <td><?= date('d/m/Y', strtotime($row['tanggal'])) ?></td>
                        <td><?= substr($row['jam_mulai'], 0, 5) ?> - <?= substr($row['jam_selesai'], 0, 5) ?></td>
                        <td><?= htmlspecialchars($row['keperluan'] ?: '-') ?></td>
                        <td>
                            <span class="status-<?= $row['status'] ?>">
                                <?php
                                $statusLabel = [
                                    'booked' => 'Aktif',
                                    'canceled' => 'Dibatalkan',
                                    'done' => 'Selesai'
                                ];
                                echo $statusLabel[$row['status']] ?? $row['status'];
                                ?>
                            </span>
                        </td>
                        <td>
                            <?php if($row['status'] == 'booked'): ?>
                                <?php
                                $tanggalReservasi = strtotime($row['tanggal']);
                                $sekarang = strtotime(date('Y-m-d'));
                                $selisihHari = ($tanggalReservasi - $sekarang) / (60 * 60 * 24);
                                if ($selisihHari >= 1):
                                ?>
                                    <a href="?cancel=1&id=<?= $row['id'] ?>" 
                                       class="btn btn-danger btn-sm" 
                                       onclick="return confirmDelete('Yakin ingin membatalkan reservasi ini?')">
                                        Batalkan
                                    </a>
                                <?php else: ?>
                                    <small style="color: #dc3545;">(H-1)</small>
                                <?php endif; ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div style="background: white; padding: 40px; text-align: center; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <p style="font-size: 1.2rem; color: #666;">Belum ada reservasi.</p>
            <a href="index.php" class="btn">Lihat Ruangan</a>
        </div>
    <?php endif; ?>
</div>

</body>
</html>