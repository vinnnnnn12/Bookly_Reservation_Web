<?php
// =============================================
// FILE: session_init.php
// Kenapa file ini ada?
// Di Vercel, tiap request PHP bisa dijalankan di "container" serverless
// yang berbeda-beda (stateless). Kalau pakai session_start() biasa,
// data session (login) bisa hilang / tidak konsisten antar request.
// Solusinya: simpan session ke tabel MySQL (Aiven), bukan ke file.
//
// Di lokal (XAMPP) file ini tetap otomatis fallback ke session file biasa,
// karena create table & session_set_save_handler tetap kompatibel.
// =============================================

require_once __DIR__ . '/database.php';

if (session_status() === PHP_SESSION_NONE) {

    class DBSessionHandler implements SessionHandlerInterface {
        private $conn;

        public function __construct($conn) {
            $this->conn = $conn;
            // Pastikan tabel sessions ada
            mysqli_query($this->conn, "CREATE TABLE IF NOT EXISTS sessions (
                id VARCHAR(191) PRIMARY KEY,
                data MEDIUMTEXT,
                last_activity INT NOT NULL
            )");
        }

        public function open($path, $name): bool {
            return true;
        }

        public function close(): bool {
            return true;
        }

        public function read($id): string {
            $id = mysqli_real_escape_string($this->conn, $id);
            $result = mysqli_query($this->conn, "SELECT data FROM sessions WHERE id = '$id'");
            if ($result && $row = mysqli_fetch_assoc($result)) {
                return $row['data'] ?? '';
            }
            return '';
        }

        public function write($id, $data): bool {
            $id = mysqli_real_escape_string($this->conn, $id);
            $data = mysqli_real_escape_string($this->conn, $data);
            $time = time();
            $sql = "INSERT INTO sessions (id, data, last_activity) VALUES ('$id', '$data', $time)
                    ON DUPLICATE KEY UPDATE data = '$data', last_activity = $time";
            return (bool) mysqli_query($this->conn, $sql);
        }

        public function destroy($id): bool {
            $id = mysqli_real_escape_string($this->conn, $id);
            mysqli_query($this->conn, "DELETE FROM sessions WHERE id = '$id'");
            return true;
        }

        public function gc($max_lifetime): int {
            $old = time() - $max_lifetime;
            mysqli_query($this->conn, "DELETE FROM sessions WHERE last_activity < $old");
            return mysqli_affected_rows($this->conn);
        }
    }

    $handler = new DBSessionHandler($conn);
    session_set_save_handler($handler, true);
    session_start();
}
?>
