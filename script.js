// =============================================
// FILTER RUANGAN BERDASARKAN LANTAI
// =============================================
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

// =============================================
// VALIDASI FORM RESERVASI
// =============================================
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

// =============================================
// KONFIRMASI HAPUS
// =============================================
function confirmDelete(message) {
    return confirm(message || 'Apakah Anda yakin ingin menghapus data ini?');
}

// =============================================
// AUTO HIDE ALERT
// =============================================
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