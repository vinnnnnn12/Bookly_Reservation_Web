<?php
require_once __DIR__ . '/session_init.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'client') {
    header('Location: login.php');
    exit();
}


$room_id = isset($_GET['room_id']) ? intval($_GET['room_id']) : 0;
$error = '';
$success = '';

$roomsQuery = "SELECT id, nama, lantai FROM rooms ORDER BY lantai, nama";
$rooms = query($roomsQuery);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $room_id = intval($_POST['room_id']);
    $nama_pemesan = escape($_POST['nama_pemesan']);
    $tanggal = escape($_POST['tanggal']);
    $jam_mulai = escape($_POST['jam_mulai']);
    $jam_selesai = escape($_POST['jam_selesai']);
    $keperluan = escape($_POST['keperluan']);
    
    $cek = "SELECT * FROM reservations 
            WHERE room_id = $room_id 
              AND tanggal = '$tanggal'
              AND status = 'booked'
              AND (
                (jam_mulai <= '$jam_mulai' AND jam_selesai > '$jam_mulai') OR
                (jam_mulai < '$jam_selesai' AND jam_selesai >= '$jam_selesai') OR
                (jam_mulai >= '$jam_mulai' AND jam_selesai <= '$jam_selesai')
              )";
    
    $cekResult = query($cek);
    
    if (numRows($cekResult) > 0) {
        $error = 'Maaf, ruangan sudah dibooking pada jam tersebut!';
    } else {
        $insert = "INSERT INTO reservations (room_id, user_id, nama_pemesan, tanggal, jam_mulai, jam_selesai, keperluan, status) 
                   VALUES ($room_id, {$_SESSION['user_id']}, '$nama_pemesan', '$tanggal', '$jam_mulai', '$jam_selesai', '$keperluan', 'booked')";
        
        if (query($insert)) {
            $success = 'Reservasi berhasil!';
            header('refresh:2; url=dashboard.php');
        } else {
            $error = 'Gagal melakukan reservasi: ' . mysqli_error($conn);
        }
    }
}

$selected_room = null;
if ($room_id > 0) {
    $roomQuery = "SELECT * FROM rooms WHERE id = $room_id";
    $roomResult = query($roomQuery);
    if (numRows($roomResult) > 0) {
        $selected_room = fetchOne($roomResult);
    }
}
?>
<?php include 'header.php'; ?>

<div class="form-container">
    <h2>Form Reservasi</h2>
    
    <?php if($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>
    
    <?php if($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>
    
    <form method="POST" action="" onsubmit="return validateReservasi(this)">
        <div class="form-group">
            <label for="room_id">Pilih Ruangan</label>
            <select id="room_id" name="room_id" required>
                <option value="">-- Pilih Ruangan --</option>
                <?php while($room = fetchOne($rooms)): ?>
                    <option value="<?= $room['id'] ?>" 
                        <?= ($selected_room && $selected_room['id'] == $room['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($room['nama']) ?> (Lantai <?= $room['lantai'] ?>)
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label for="nama_pemesan">Nama Pemesan</label>
            <input type="text" id="nama_pemesan" name="nama_pemesan" required 
                   value="<?= $_SESSION['nama'] ?>" readonly>
            <small style="color: #666;">Nama diambil dari akun Anda</small>
        </div>
        
        <div class="form-group">
            <label for="tanggal">Tanggal</label>
            <input type="date" id="tanggal" name="tanggal" required 
                   min="<?= date('Y-m-d') ?>">
        </div>
        
        <div class="form-group">
            <label for="jam_mulai">Jam Mulai</label>
            <input type="time" id="jam_mulai" name="jam_mulai" required>
        </div>
        
        <div class="form-group">
            <label for="jam_selesai">Jam Selesai</label>
            <input type="time" id="jam_selesai" name="jam_selesai" required>
        </div>
        
        <div class="form-group">
            <label for="keperluan">Keperluan</label>
            <textarea id="keperluan" name="keperluan" rows="3" 
                      placeholder="Jelaskan keperluan rapat..."></textarea>
        </div>
        
        <div class="form-group">
            <button type="submit" class="btn btn-success">Pesan Sekarang</button>
        </div>
    </form>
</div>

</body>
</html>