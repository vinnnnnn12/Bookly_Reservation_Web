<?php
require_once __DIR__ . '/session_init.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit();
}


// =============================================
// CRUD RUANGAN
// =============================================

// Tambah Ruangan
if (isset($_POST['add_room'])) {
    $nama = escape($_POST['nama']);
    $lantai = intval($_POST['lantai']);
    $kapasitas = escape($_POST['kapasitas']);
    $fasilitas = escape($_POST['fasilitas']);
    $deskripsi = escape($_POST['deskripsi']);
    
    // Proses upload gambar
    // CATATAN: Di Vercel, filesystem project bersifat read-only. Upload gambar
    // baru tidak akan tersimpan permanen. Kalau upload gagal, tetap lanjutkan
    // simpan data ruangan dengan gambar default supaya fitur lain tidak rusak.
    $gambar = 'default.jpg';
    $uploadWarning = '';
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
        $target_dir = "uploads/";
        $check = @getimagesize($_FILES['gambar']['tmp_name']);
        if ($check !== false) {
            if (!file_exists($target_dir)) {
                @mkdir($target_dir, 0777, true);
            }
            $file_extension = pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION);
            $gambar_baru = time() . '_' . uniqid() . '.' . $file_extension;
            $target_file = $target_dir . $gambar_baru;

            if (@move_uploaded_file($_FILES['gambar']['tmp_name'], $target_file)) {
                $gambar = $gambar_baru;
            } else {
                $uploadWarning = ' (Gambar tidak tersimpan permanen di server Vercel, memakai gambar default.)';
            }
        } else {
            $uploadWarning = ' (File yang diupload bukan gambar, memakai gambar default.)';
        }
    }

    $insert = "INSERT INTO rooms (nama, lantai, kapasitas, fasilitas, deskripsi, gambar) 
               VALUES ('$nama', $lantai, '$kapasitas', '$fasilitas', '$deskripsi', '$gambar')";
    if (query($insert)) {
        $success = 'Ruangan berhasil ditambahkan!' . $uploadWarning;
    } else {
        $error = 'Gagal menambahkan ruangan: ' . mysqli_error($conn);
    }
}

// Edit Ruangan
if (isset($_POST['edit_room'])) {
    $id = intval($_POST['id']);
    $nama = escape($_POST['nama']);
    $lantai = intval($_POST['lantai']);
    $kapasitas = escape($_POST['kapasitas']);
    $fasilitas = escape($_POST['fasilitas']);
    $deskripsi = escape($_POST['deskripsi']);
    
    $queryGambar = "SELECT gambar FROM rooms WHERE id = $id";
    $resultGambar = query($queryGambar);
    $dataGambar = fetchOne($resultGambar);
    $gambar = $dataGambar['gambar'];
    
    // CATATAN: sama seperti tambah ruangan, upload gambar baru tidak permanen
    // di Vercel (filesystem read-only). Kalau gagal, gambar lama tetap dipakai.
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
        $target_dir = "uploads/";
        $check = @getimagesize($_FILES['gambar']['tmp_name']);
        if ($check !== false) {
            if (!file_exists($target_dir)) {
                @mkdir($target_dir, 0777, true);
            }
            $file_extension = pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION);
            $gambar_baru = time() . '_' . uniqid() . '.' . $file_extension;
            $target_file = $target_dir . $gambar_baru;

            if (@move_uploaded_file($_FILES['gambar']['tmp_name'], $target_file)) {
                if ($gambar != 'default.jpg' && file_exists($target_dir . $gambar)) {
                    @unlink($target_dir . $gambar);
                }
                $gambar = $gambar_baru;
            }
        }
    }
    
    $update = "UPDATE rooms SET 
               nama = '$nama', 
               lantai = $lantai, 
               kapasitas = '$kapasitas', 
               fasilitas = '$fasilitas', 
               deskripsi = '$deskripsi',
               gambar = '$gambar'
               WHERE id = $id";
    if (query($update)) {
        $success = 'Ruangan berhasil diupdate!';
    } else {
        $error = 'Gagal mengupdate ruangan: ' . mysqli_error($conn);
    }
}

// Hapus Ruangan
if (isset($_GET['delete_room']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    $queryGambar = "SELECT gambar FROM rooms WHERE id = $id";
    $resultGambar = query($queryGambar);
    $dataGambar = fetchOne($resultGambar);
    
    $delete = "DELETE FROM rooms WHERE id = $id";
    if (query($delete)) {
        if ($dataGambar['gambar'] != 'default.jpg' && file_exists('uploads/' . $dataGambar['gambar'])) {
            unlink('uploads/' . $dataGambar['gambar']);
        }
        $success = 'Ruangan berhasil dihapus!';
    } else {
        $error = 'Gagal menghapus ruangan: ' . mysqli_error($conn);
    }
}

// =============================================
// ADMIN BATALKAN RESERVASI
// =============================================
if (isset($_GET['cancel_reservation']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $update = "UPDATE reservations SET status = 'canceled' WHERE id = $id";
    if (query($update)) {
        $success = 'Reservasi berhasil dibatalkan oleh Admin!';
    } else {
        $error = 'Gagal membatalkan reservasi: ' . mysqli_error($conn);
    }
}

// =============================================
// ADMIN UBAH STATUS RESERVASI JADI SELESAI
// =============================================
if (isset($_GET['done_reservation']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $update = "UPDATE reservations SET status = 'done' WHERE id = $id";
    if (query($update)) {
        $success = 'Reservasi ditandai selesai!';
    } else {
        $error = 'Gagal mengupdate status: ' . mysqli_error($conn);
    }
}

// =============================================
// ADMIN EDIT RESERVASI
// =============================================
if (isset($_POST['edit_reservation'])) {
    $id = intval($_POST['id']);
    $tanggal = escape($_POST['tanggal']);
    $jam_mulai = escape($_POST['jam_mulai']);
    $jam_selesai = escape($_POST['jam_selesai']);
    $keperluan = escape($_POST['keperluan']);
    
    $cek = "SELECT * FROM reservations 
            WHERE room_id = (SELECT room_id FROM reservations WHERE id = $id)
              AND id != $id
              AND tanggal = '$tanggal'
              AND status = 'booked'
              AND (
                (jam_mulai <= '$jam_mulai' AND jam_selesai > '$jam_mulai') OR
                (jam_mulai < '$jam_selesai' AND jam_selesai >= '$jam_selesai') OR
                (jam_mulai >= '$jam_mulai' AND jam_selesai <= '$jam_selesai')
              )";
    
    $cekResult = query($cek);
    
    if (numRows($cekResult) > 0) {
        $error = 'Jadwal bentrok dengan reservasi lain!';
    } else {
        $update = "UPDATE reservations SET 
                   tanggal = '$tanggal',
                   jam_mulai = '$jam_mulai',
                   jam_selesai = '$jam_selesai',
                   keperluan = '$keperluan'
                   WHERE id = $id";
        if (query($update)) {
            $success = 'Reservasi berhasil diupdate!';
        } else {
            $error = 'Gagal mengupdate reservasi: ' . mysqli_error($conn);
        }
    }
}

// =============================================
// AMBIL DATA
// =============================================

$roomsQuery = "SELECT * FROM rooms ORDER BY lantai, id";
$rooms = query($roomsQuery);

$reservationsQuery = "SELECT r.*, rm.nama as ruangan, rm.lantai, rm.id as room_id, u.nama as user_nama, u.email as user_email
                      FROM reservations r 
                      JOIN rooms rm ON r.room_id = rm.id 
                      JOIN users u ON r.user_id = u.id 
                      ORDER BY r.tanggal DESC, r.jam_mulai DESC";
$reservations = query($reservationsQuery);

$statsQuery = "SELECT 
                (SELECT COUNT(*) FROM rooms) as total_rooms,
                (SELECT COUNT(*) FROM reservations WHERE status = 'booked' AND tanggal >= CURDATE()) as reservasi_aktif,
                (SELECT COUNT(*) FROM reservations WHERE status = 'booked') as total_reservasi,
                (SELECT COUNT(*) FROM users WHERE role = 'client') as total_client";
$stats = fetchOne(query($statsQuery));

$edit_room = null;
if (isset($_GET['edit_room']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $editQuery = "SELECT * FROM rooms WHERE id = $id";
    $editResult = query($editQuery);
    if (numRows($editResult) > 0) {
        $edit_room = fetchOne($editResult);
    }
}

$detail_reservation = null;
if (isset($_GET['detail']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $detailQuery = "SELECT r.*, rm.nama as ruangan, rm.lantai, rm.fasilitas as ruangan_fasilitas, 
                           u.nama as user_nama, u.email as user_email
                    FROM reservations r 
                    JOIN rooms rm ON r.room_id = rm.id 
                    JOIN users u ON r.user_id = u.id 
                    WHERE r.id = $id";
    $detailResult = query($detailQuery);
    if (numRows($detailResult) > 0) {
        $detail_reservation = fetchOne($detailResult);
    }
}

$edit_reservation = null;
if (isset($_GET['edit_res']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $editResQuery = "SELECT r.*, rm.nama as ruangan, rm.lantai, u.nama as user_nama
                     FROM reservations r 
                     JOIN rooms rm ON r.room_id = rm.id 
                     JOIN users u ON r.user_id = u.id 
                     WHERE r.id = $id";
    $editResResult = query($editResQuery);
    if (numRows($editResResult) > 0) {
        $edit_reservation = fetchOne($editResResult);
    }
}

$show_add_form = isset($_GET['show_add']) ? true : false;
?>
<?php include 'header.php'; ?>

<div class="container">
    <h1>Dashboard Admin</h1>
    <p style="color: #666; margin-bottom: 20px;">
        Selamat datang, <strong><?= $_SESSION['nama'] ?></strong>!
    </p>
    
    <div class="stats-grid">
        <div class="stat-card">
            <h3><?= $stats['total_rooms'] ?? 0 ?></h3>
            <p>Total Ruangan</p>
        </div>
        <div class="stat-card" style="border-left: 4px solid #28a745;">
            <h3><?= $stats['reservasi_aktif'] ?? 0 ?></h3>
            <p>Reservasi Aktif</p>
        </div>
        <div class="stat-card" style="border-left: 4px solid #0d47a1;">
            <h3><?= $stats['total_reservasi'] ?? 0 ?></h3>
            <p>Total Reservasi</p>
        </div>
        <div class="stat-card" style="border-left: 4px solid #ffc107;">
            <h3><?= $stats['total_client'] ?? 0 ?></h3>
            <p>Total Client</p>
        </div>
    </div>
    
    <?php if(isset($error) && $error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>
    
    <?php if(isset($success) && $success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>
    
    <!-- =============================================
    MANAJEMEN RUANGAN
    ============================================= -->
    <div class="section-box" id="section-ruangan">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
            <h2 style="margin: 0;">Manajemen Ruangan</h2>
            <a href="?show_add=1#section-ruangan" class="btn btn-success" id="btnTambahRuangan">
                + Tambah Ruangan
            </a>
        </div>
        
        <!-- Form Tambah Ruangan -->
        <?php if($show_add_form && !$edit_room): ?>
        <div id="formTambahRuangan" style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 20px; border: 2px solid #28a745;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h3 style="margin: 0; color: #28a745;">Tambah Ruangan Baru</h3>
                <a href="admin.php#section-ruangan" style="color: #dc3545; text-decoration: none; font-weight: bold; font-size: 1.2rem;">X</a>
            </div>
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="grid-2">
                    <div class="form-group">
                        <label>Nama Ruangan</label>
                        <input type="text" name="nama" required placeholder="Contoh: Ruang Rapat VIP">
                    </div>
                    <div class="form-group">
                        <label>Lantai</label>
                        <input type="number" name="lantai" required placeholder="1-4">
                    </div>
                    <div class="form-group">
                        <label>Kapasitas</label>
                        <input type="text" name="kapasitas" required placeholder="10-15 orang">
                    </div>
                    <div class="form-group">
                        <label>Fasilitas</label>
                        <input type="text" name="fasilitas" required placeholder="TV, Proyektor, dll">
                    </div>
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label>Deskripsi</label>
                        <textarea name="deskripsi" rows="2" placeholder="Deskripsi ruangan..."></textarea>
                    </div>
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label>Gambar Ruangan</label>
                        <input type="file" name="gambar" accept="image/*">
                        <small style="color: #666;">Format: JPG, PNG, JPEG. Maks: 2MB</small>
                    </div>
                </div>
                <button type="submit" name="add_room" class="btn btn-success">Tambah Ruangan</button>
                <a href="admin.php#section-ruangan" class="btn" style="background: #6c757d;">Batal</a>
            </form>
        </div>
        <?php endif; ?>
        
        <!-- Form Edit Ruangan -->
        <?php if($edit_room): ?>
        <div style="background: #fff3cd; padding: 20px; border-radius: 10px; margin-bottom: 20px; border: 2px solid #ffc107;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h3 style="margin: 0; color: #856404;">Edit Ruangan</h3>
                <a href="admin.php#section-ruangan" style="color: #dc3545; text-decoration: none; font-weight: bold; font-size: 1.2rem;">X</a>
            </div>
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?= $edit_room['id'] ?>">
                <div class="grid-2">
                    <div class="form-group">
                        <label>Nama Ruangan</label>
                        <input type="text" name="nama" required value="<?= htmlspecialchars($edit_room['nama']) ?>">
                    </div>
                    <div class="form-group">
                        <label>Lantai</label>
                        <input type="number" name="lantai" required value="<?= $edit_room['lantai'] ?>">
                    </div>
                    <div class="form-group">
                        <label>Kapasitas</label>
                        <input type="text" name="kapasitas" required value="<?= htmlspecialchars($edit_room['kapasitas']) ?>">
                    </div>
                    <div class="form-group">
                        <label>Fasilitas</label>
                        <input type="text" name="fasilitas" required value="<?= htmlspecialchars($edit_room['fasilitas']) ?>">
                    </div>
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label>Deskripsi</label>
                        <textarea name="deskripsi" rows="2"><?= htmlspecialchars($edit_room['deskripsi']) ?></textarea>
                    </div>
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label>Gambar Ruangan</label>
                        <?php if($edit_room['gambar'] != 'default.jpg' && file_exists('uploads/' . $edit_room['gambar'])): ?>
                            <div style="margin-bottom: 10px;">
                                <img src="uploads/<?= $edit_room['gambar'] ?>" alt="Gambar Ruangan" style="max-width: 200px; max-height: 150px; border-radius: 8px; border: 1px solid #ddd;">
                            </div>
                        <?php endif; ?>
                        <input type="file" name="gambar" accept="image/*">
                        <small style="color: #666;">Upload gambar baru untuk mengganti (kosongkan jika tidak ingin mengubah)</small>
                    </div>
                </div>
                <button type="submit" name="edit_room" class="btn btn-success">Update Ruangan</button>
                <a href="admin.php#section-ruangan" class="btn" style="background: #6c757d;">Batal</a>
            </form>
        </div>
        <?php endif; ?>
        
        <!-- Daftar Ruangan -->
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Gambar</th>
                        <th>Nama</th>
                        <th>Lantai</th>
                        <th>Kapasitas</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    while($room = fetchOne($rooms)): 
                    ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td>
                            <?php if($room['gambar'] != 'default.jpg' && file_exists('uploads/' . $room['gambar'])): ?>
                                <img src="uploads/<?= $room['gambar'] ?>" alt="<?= htmlspecialchars($room['nama']) ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;">
                            <?php else: ?>
                                <span style="color: #999; font-size: 0.8rem;">No Image</span>
                            <?php endif; ?>
                        </td>
                        <td><strong><?= htmlspecialchars($room['nama']) ?></strong></td>
                        <td>Lantai <?= $room['lantai'] ?></td>
                        <td><?= htmlspecialchars($room['kapasitas']) ?></td>
                        <td>
                            <a href="?edit_room=1&id=<?= $room['id'] ?>#section-ruangan" class="btn btn-warning btn-sm">Edit</a>
                            <a href="?delete_room=1&id=<?= $room['id'] ?>#section-ruangan" class="btn btn-danger btn-sm" onclick="return confirmDelete('Yakin ingin menghapus ruangan ini?')">Hapus</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- =============================================
    DAFTAR RESERVASI (DENGAN ANCHOR ID)
    ============================================= -->
    <div class="section-box" id="section-reservasi">
        <h2>Daftar Reservasi</h2>
        
        <?php if(numRows($reservations) > 0): ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Ruangan</th>
                            <th>Pemesan</th>
                            <th>Tanggal</th>
                            <th>Jam</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        while($res = fetchOne($reservations)): 
                        ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><strong><?= htmlspecialchars($res['ruangan']) ?></strong><br>
                                <small>Lantai <?= $res['lantai'] ?></small>
                            </td>
                            <td><?= htmlspecialchars($res['user_nama']) ?></td>
                            <td><?= date('d/m/Y', strtotime($res['tanggal'])) ?></td>
                            <td><?= substr($res['jam_mulai'], 0, 5) ?> - <?= substr($res['jam_selesai'], 0, 5) ?></td>
                            <td>
                                <span class="status-<?= $res['status'] ?>">
                                    <?php
                                    $statusLabel = [
                                        'booked' => 'Aktif',
                                        'canceled' => 'Dibatalkan',
                                        'done' => 'Selesai'
                                    ];
                                    echo $statusLabel[$res['status']] ?? $res['status'];
                                    ?>
                                </span>
                            </td>
                            <td>
                                <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                                    <!-- Tambahkan #section-reservasi di setiap link -->
                                    <a href="?detail=1&id=<?= $res['id'] ?>#section-reservasi" class="btn btn-info btn-sm">Detail</a>
                                    
                                    <?php if($res['status'] == 'booked'): ?>
                                        <a href="?edit_res=1&id=<?= $res['id'] ?>#section-reservasi" class="btn btn-warning btn-sm">Edit</a>
                                        <a href="?cancel_reservation=1&id=<?= $res['id'] ?>#section-reservasi" 
                                           class="btn btn-danger btn-sm" 
                                           onclick="return confirmDelete('Yakin ingin membatalkan reservasi ini?')">
                                            Batalkan
                                        </a>
                                        <a href="?done_reservation=1&id=<?= $res['id'] ?>#section-reservasi" 
                                           class="btn btn-success btn-sm" 
                                           onclick="return confirmDelete('Tandai reservasi ini selesai?')">
                                            Selesai
                                        </a>
                                    <?php elseif($res['status'] == 'done'): ?>
                                        <span style="color: #6c757d; font-size: 0.8rem;">Selesai</span>
                                    <?php elseif($res['status'] == 'canceled'): ?>
                                        <span style="color: #dc3545; font-size: 0.8rem;">Dibatalkan</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p style="text-align: center; padding: 20px; color: #666;">Belum ada reservasi.</p>
        <?php endif; ?>
    </div>
</div>

<!-- =============================================
MODAL DETAIL RESERVASI
============================================= -->
<?php if($detail_reservation): ?>
<div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 9999; padding: 20px;">
    <div style="background: white; border-radius: 15px; padding: 30px; max-width: 600px; width: 100%; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,0.3);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 2px solid #1a237e; padding-bottom: 15px;">
            <h2 style="margin: 0; color: #1a237e;">Detail Reservasi</h2>
            <a href="admin.php#section-reservasi" style="color: #dc3545; text-decoration: none; font-size: 1.5rem; font-weight: bold;">X</a>
        </div>
        
        <div style="display: grid; gap: 12px;">
            <div style="display: grid; grid-template-columns: 120px 1fr; gap: 10px; padding: 8px 0; border-bottom: 1px solid #eee;">
                <strong>ID Reservasi</strong>
                <span>#<?= $detail_reservation['id'] ?></span>
            </div>
            <div style="display: grid; grid-template-columns: 120px 1fr; gap: 10px; padding: 8px 0; border-bottom: 1px solid #eee;">
                <strong>Ruangan</strong>
                <span><strong><?= htmlspecialchars($detail_reservation['ruangan']) ?></strong> (Lantai <?= $detail_reservation['lantai'] ?>)</span>
            </div>
            <div style="display: grid; grid-template-columns: 120px 1fr; gap: 10px; padding: 8px 0; border-bottom: 1px solid #eee;">
                <strong>Pemesan</strong>
                <span><?= htmlspecialchars($detail_reservation['user_nama']) ?></span>
            </div>
            <div style="display: grid; grid-template-columns: 120px 1fr; gap: 10px; padding: 8px 0; border-bottom: 1px solid #eee;">
                <strong>Email</strong>
                <span><?= htmlspecialchars($detail_reservation['user_email']) ?></span>
            </div>
            <div style="display: grid; grid-template-columns: 120px 1fr; gap: 10px; padding: 8px 0; border-bottom: 1px solid #eee;">
                <strong>Tanggal</strong>
                <span><?= date('d F Y', strtotime($detail_reservation['tanggal'])) ?></span>
            </div>
            <div style="display: grid; grid-template-columns: 120px 1fr; gap: 10px; padding: 8px 0; border-bottom: 1px solid #eee;">
                <strong>Jam</strong>
                <span><?= substr($detail_reservation['jam_mulai'], 0, 5) ?> - <?= substr($detail_reservation['jam_selesai'], 0, 5) ?></span>
            </div>
            <div style="display: grid; grid-template-columns: 120px 1fr; gap: 10px; padding: 8px 0; border-bottom: 1px solid #eee;">
                <strong>Keperluan</strong>
                <span><?= htmlspecialchars($detail_reservation['keperluan'] ?: '-') ?></span>
            </div>
            <div style="display: grid; grid-template-columns: 120px 1fr; gap: 10px; padding: 8px 0; border-bottom: 1px solid #eee;">
                <strong>Status</strong>
                <span class="status-<?= $detail_reservation['status'] ?>">
                    <?php
                    $statusLabel = [
                        'booked' => 'Aktif',
                        'canceled' => 'Dibatalkan',
                        'done' => 'Selesai'
                    ];
                    echo $statusLabel[$detail_reservation['status']] ?? $detail_reservation['status'];
                    ?>
                </span>
            </div>
            <div style="display: grid; grid-template-columns: 120px 1fr; gap: 10px; padding: 8px 0;">
                <strong>Dibuat</strong>
                <span><?= date('d/m/Y H:i', strtotime($detail_reservation['created_at'])) ?></span>
            </div>
        </div>
        
        <div style="margin-top: 20px; padding-top: 20px; border-top: 2px solid #eee; text-align: center;">
            <a href="admin.php#section-reservasi" class="btn" style="background: #6c757d;">Tutup</a>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- =============================================
MODAL EDIT RESERVASI
============================================= -->
<?php if($edit_reservation): ?>
<div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 9999; padding: 20px;">
    <div style="background: white; border-radius: 15px; padding: 30px; max-width: 500px; width: 100%; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,0.3);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 2px solid #ffc107; padding-bottom: 15px;">
            <h2 style="margin: 0; color: #856404;">Edit Reservasi</h2>
            <a href="admin.php#section-reservasi" style="color: #dc3545; text-decoration: none; font-size: 1.5rem; font-weight: bold;">X</a>
        </div>
        
        <form method="POST" action="">
            <input type="hidden" name="id" value="<?= $edit_reservation['id'] ?>">
            
            <div style="background: #f8f9fa; padding: 12px; border-radius: 8px; margin-bottom: 15px;">
                <p style="margin: 0; color: #666;"><strong>Ruangan:</strong> <?= htmlspecialchars($edit_reservation['ruangan']) ?></p>
                <p style="margin: 0; color: #666;"><strong>Pemesan:</strong> <?= htmlspecialchars($edit_reservation['user_nama']) ?></p>
            </div>
            
            <div class="form-group">
                <label for="edit_tanggal">Tanggal</label>
                <input type="date" id="edit_tanggal" name="tanggal" required 
                       value="<?= $edit_reservation['tanggal'] ?>"
                       min="<?= date('Y-m-d') ?>">
            </div>
            
            <div class="form-group">
                <label for="edit_jam_mulai">Jam Mulai</label>
                <input type="time" id="edit_jam_mulai" name="jam_mulai" required 
                       value="<?= $edit_reservation['jam_mulai'] ?>">
            </div>
            
            <div class="form-group">
                <label for="edit_jam_selesai">Jam Selesai</label>
                <input type="time" id="edit_jam_selesai" name="jam_selesai" required 
                       value="<?= $edit_reservation['jam_selesai'] ?>">
            </div>
            
            <div class="form-group">
                <label for="edit_keperluan">Keperluan</label>
                <textarea id="edit_keperluan" name="keperluan" rows="3"><?= htmlspecialchars($edit_reservation['keperluan']) ?></textarea>
            </div>
            
            <div style="display: flex; gap: 10px;">
                <button type="submit" name="edit_reservation" class="btn btn-success">Update Reservasi</button>
                <a href="admin.php#section-reservasi" class="btn" style="background: #6c757d;">Batal</a>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

</body>
</html>