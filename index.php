<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/session_init.php';

// Wajib login dulu sebelum bisa lihat daftar ruangan
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if (!testConnection()) {
    die('<h1>Error Database</h1><p>Koneksi ke database gagal. Pastikan MySQL berjalan di XAMPP.</p>');
}

// Ambil semua ruangan
$query = "SELECT * FROM rooms ORDER BY lantai, id";
$rooms = query($query);

if (!$rooms) {
    die('<h1>Error Query</h1><p>Gagal mengambil data ruangan.</p>');
}

// Ambil semua reservasi aktif untuk hari ini dan ke depan
$today = date('Y-m-d');
$reservasiQuery = "SELECT room_id, tanggal, jam_mulai, jam_selesai, status 
                   FROM reservations 
                   WHERE status = 'booked' AND tanggal >= '$today'
                   ORDER BY tanggal, jam_mulai";
$reservasiResult = query($reservasiQuery);

// Kelompokkan reservasi berdasarkan room_id
$reservasiGroup = [];
while($res = fetchOne($reservasiResult)) {
    $roomId = $res['room_id'];
    if (!isset($reservasiGroup[$roomId])) {
        $reservasiGroup[$roomId] = [];
    }
    $reservasiGroup[$roomId][] = $res;
}
?>
<?php include 'header.php'; ?>

<div class="container">
    <h1>Daftar Ruangan Rapat</h1>
    <p style="color: #666; margin-bottom: 20px;">
        <?php if(isset($_SESSION['nama'])): ?>
            Selamat datang, <strong><?= $_SESSION['nama'] ?></strong>! 
            <?php if($_SESSION['role'] == 'admin'): ?>
                (Admin)
            <?php else: ?>
                (Client)
            <?php endif; ?>
        <?php else: ?>
            Silakan <a href="login.php">login</a> untuk melakukan reservasi.
        <?php endif; ?>
    </p>
    
    <!-- FILTER LANTAI -->
    <div class="filter">
        <button data-lantai="0" class="active" onclick="filterLantai(0)">Semua</button>
        <button data-lantai="1" onclick="filterLantai(1)">Lantai 1</button>
        <button data-lantai="2" onclick="filterLantai(2)">Lantai 2</button>
        <button data-lantai="3" onclick="filterLantai(3)">Lantai 3</button>
        <button data-lantai="4" onclick="filterLantai(4)">Lantai 4</button>
    </div>
    
    <div class="room-grid">
        <?php while($room = fetchOne($rooms)): 
            $roomId = $room['id'];
            $reservasiList = isset($reservasiGroup[$roomId]) ? $reservasiGroup[$roomId] : [];
            $isBooked = !empty($reservasiList);
            
            // Cek gambar
            $gambarPath = 'uploads/' . $room['gambar'];
            if (!file_exists($gambarPath)) {
                $gambarPath = 'uploads/default.jpg';
            }
        ?>
        <div class="card" data-lantai="<?= $room['lantai'] ?>">
            <div class="card-image" style="background: none; height: 200px; overflow: hidden; position: relative;">
                <img src="<?= $gambarPath ?>" alt="<?= htmlspecialchars($room['nama']) ?>" 
                     style="width: 100%; height: 100%; object-fit: cover; display: block;">
                <div style="position: absolute; top: 10px; left: 10px; background: rgba(0,0,0,0.7); color: white; padding: 4px 12px; border-radius: 15px; font-size: 0.7rem; font-weight: bold;">
                    Lantai <?= $room['lantai'] ?>
                </div>
            </div>
            <div class="card-body">
                <div style="display: flex; justify-content: space-between; align-items: start;">
                    <h3 style="margin: 0; font-size: 1rem;"><?= htmlspecialchars($room['nama']) ?></h3>
                    <?php if($isBooked): ?>
                        <span style="background: #dc3545; color: white; padding: 2px 10px; border-radius: 12px; font-size: 0.65rem; font-weight: bold; white-space: nowrap; margin-left: 10px;">
                            Booking
                        </span>
                    <?php else: ?>
                        <span style="background: #28a745; color: white; padding: 2px 10px; border-radius: 12px; font-size: 0.65rem; font-weight: bold; white-space: nowrap; margin-left: 10px;">
                            Tersedia
                        </span>
                    <?php endif; ?>
                </div>
                
                <p style="margin-top: 5px; font-size: 0.8rem; color: #666;">Kapasitas: <?= htmlspecialchars($room['kapasitas']) ?></p>
                <p style="font-size: 0.8rem; color: #666; margin-bottom: 5px;"><?= htmlspecialchars(substr($room['deskripsi'], 0, 40)) ?>...</p>
                
                <?php if($isBooked): ?>
                <div style="margin-top: 8px; padding: 8px; background: #fff3cd; border-radius: 6px; border-left: 3px solid #ffc107;">
                    <p style="font-size: 0.7rem; font-weight: bold; color: #856404; margin: 0 0 3px 0;">
                        Jadwal Booking:
                    </p>
                    <?php 
                    $showCount = 0;
                    foreach($reservasiList as $res):
                        if($showCount >= 2) break;
                        $showCount++;
                    ?>
                    <div style="display: flex; justify-content: space-between; font-size: 0.7rem; color: #666; padding: 2px 0; border-bottom: 1px dashed #e0c3a0;">
                        <span><?= date('d/m/Y', strtotime($res['tanggal'])) ?></span>
                        <span><?= substr($res['jam_mulai'], 0, 5) ?> - <?= substr($res['jam_selesai'], 0, 5) ?></span>
                    </div>
                    <?php endforeach; ?>
                    
                    <?php if(count($reservasiList) > 2): ?>
                    <p style="font-size: 0.6rem; color: #856404; margin: 3px 0 0 0; font-style: italic;">
                        + <?= count($reservasiList) - 2 ?> jadwal lainnya...
                    </p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <a href="detail.php?id=<?= $room['id'] ?>" class="btn" style="margin-top: 8px; padding: 6px 12px; font-size: 0.85rem;">Lihat Detail</a>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
</div>

<!-- ============================================= -->
<!-- JAVASCRIPT INLINE - DITARUH DI SINI            -->
<!-- ============================================= -->
<script>
function filterLantai(lantai) {
    var cards = document.querySelectorAll('.card');
    var buttons = document.querySelectorAll('.filter button');
    
    // Update active button
    buttons.forEach(function(btn) {
        btn.classList.remove('active');
        if (parseInt(btn.getAttribute('data-lantai')) === lantai) {
            btn.classList.add('active');
        }
    });
    
    // Filter cards
    cards.forEach(function(card) {
        var cardLantai = parseInt(card.getAttribute('data-lantai'));
        if (lantai === 0 || cardLantai === lantai) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

// Fungsi validasi reservasi
function validateReservasi(form) {
    var tanggal = form.tanggal.value;
    var jamMulai = form.jam_mulai.value;
    var jamSelesai = form.jam_selesai.value;
    
    if (!tanggal) {
        alert('Silakan pilih tanggal!');
        return false;
    }
    
    if (!jamMulai || !jamSelesai) {
        alert('Silakan pilih jam mulai dan jam selesai!');
        return false;
    }
    
    if (jamMulai >= jamSelesai) {
        alert('Jam mulai harus lebih awal dari jam selesai!');
        return false;
    }
    
    var mulai = jamMulai.split(':');
    var selesai = jamSelesai.split(':');
    var durasi = (parseInt(selesai[0]) * 60 + parseInt(selesai[1])) - 
                   (parseInt(mulai[0]) * 60 + parseInt(mulai[1]));
    
    if (durasi < 30) {
        alert('Durasi minimum reservasi adalah 30 menit!');
        return false;
    }
    
    return true;
}

// Fungsi konfirmasi hapus
function confirmDelete(message) {
    return confirm(message || 'Apakah Anda yakin ingin menghapus data ini?');
}

// Auto hide alert setelah 5 detik
document.addEventListener('DOMContentLoaded', function() {
    var alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(function() {
                alert.style.display = 'none';
            }, 500);
        }, 5000);
    });
});
</script>

</body>
</html>