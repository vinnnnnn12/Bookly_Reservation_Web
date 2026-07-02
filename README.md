# Reservasi Ruangan - Panduan Deploy ke Vercel + Aiven MySQL

Project ini sudah dimodifikasi supaya bisa jalan di Vercel (hosting PHP serverless)
dengan database Aiven MySQL (managed database eksternal).

## Apa yang diubah dari kode aslinya?

1. **`database.php`** — sekarang baca kredensial dari Environment Variables
   (`DB_HOST`, `DB_PORT`, `DB_USER`, `DB_PASS`, `DB_NAME`, `DB_SSL_CA`), bukan
   hardcode `localhost/root`. Kalau env var tidak ada, otomatis fallback ke
   setting lokal (XAMPP) — jadi kode ini tetap bisa dipakai di laptop juga.
2. **`session_init.php` (baru)** — Vercel menjalankan PHP secara *serverless*
   (setiap request bisa "kontainer" berbeda), jadi session file biasa
   (`session_start()`) tidak reliable untuk fitur login. File ini menyimpan
   session ke tabel MySQL (`sessions`) supaya login tetap konsisten. Semua file
   yang tadinya pakai `session_start();` sudah diganti ke
   `require_once __DIR__ . '/session_init.php';`.
3. **`admin.php`** — proses upload gambar ruangan diperbaiki supaya kalau
   upload gagal (karena filesystem Vercel read-only), form tambah/edit
   ruangan tetap tersimpan (pakai gambar default), tidak lagi block total.
4. **`database.sql`** — baris `CREATE DATABASE`/`USE` dinonaktifkan (Aiven
   biasanya sudah kasih 1 database default), ditambah tabel `sessions`.
5. **`vercel.json` (baru)** — konfigurasi runtime PHP untuk Vercel.

## ⚠️ Batasan penting: upload gambar

Vercel **tidak** menyediakan penyimpanan file permanen di server (filesystem
read-only, kecuali folder `/tmp` yang sifatnya sementara dan hilang kapan
saja). Artinya:
- Gambar ruangan yang **sudah ada** di folder `uploads/` (yang di-deploy
  bersama kode) tetap tampil normal.
- Kalau admin **upload gambar baru** lewat form di dashboard admin, gambar
  itu **tidak akan tersimpan permanen** — akan otomatis fallback ke
  `default.jpg`. Ini cukup untuk keperluan tugas/demo.
- Untuk produksi sungguhan, perlu storage eksternal seperti **Vercel Blob**,
  **Cloudinary**, atau **Aiven Object Storage**. Bilang saja kalau mau saya
  bantu integrasikan salah satunya.

---

## Langkah 1 — Buat Database di Aiven

1. Daftar/login ke https://aiven.io lalu buat service baru: **MySQL**.
2. Pilih paket gratis (Free / Hobbyist) atau sesuai kebutuhan, pilih region
   terdekat, lalu **Create Service**.
3. Tunggu status service jadi **Running**.
4. Di halaman **Overview** service tersebut, catat:
   - `Host`
   - `Port`
   - `User` (biasanya `avnadmin`)
   - `Password`
   - `Default database name` (biasanya `defaultdb`)
5. Di bagian yang sama, **download file `ca.pem`** (SSL certificate) — wajib
   dipakai karena Aiven mewajibkan koneksi SSL.
6. Import struktur tabel: buka **Aiven Console > Query Editor** (atau pakai
   klien seperti DBeaver/TablePlus/MySQL Workbench dengan SSL aktif), lalu
   jalankan isi file `database.sql` dari project ini.

## Langkah 2 — Push Project ke GitHub

```bash
git init
git add .
git commit -m "Deploy reservasi ruangan ke Vercel + Aiven"
git branch -M main
git remote add origin <url-repo-github-kamu>
git push -u origin main
```

## Langkah 3 — Deploy ke Vercel

1. Buka https://vercel.com, login, klik **Add New > Project**.
2. Import repo GitHub yang tadi kamu push.
3. Saat konfigurasi project, **Framework Preset** pilih **Other** (Vercel akan
   otomatis mengenali `vercel.json`).
4. Sebelum klik Deploy, buka bagian **Environment Variables** dan tambahkan:

   | Key         | Value                                                        |
   |-------------|---------------------------------------------------------------|
   | `DB_HOST`   | host dari Aiven, misal `mysql-xxxx.aivencloud.com`             |
   | `DB_PORT`   | port dari Aiven, misal `12345`                                 |
   | `DB_USER`   | `avnadmin`                                                     |
   | `DB_PASS`   | password dari Aiven                                             |
   | `DB_NAME`   | `defaultdb` (atau nama database yang Aiven berikan)             |
   | `DB_SSL_CA` | **isi lengkap** file `ca.pem` yang kamu download tadi (paste teks-nya langsung, termasuk baris BEGIN/END CERTIFICATE) |

5. Klik **Deploy**. Tunggu sampai selesai.
6. Buka domain yang diberikan Vercel (contoh: `reservasi-app.vercel.app`).

## Langkah 4 — Test

- Buka halaman utama, pastikan daftar ruangan tampil (artinya koneksi DB
  sukses).
- Coba login admin: `admin@reservasi.com` / `admin123`.
- Coba register akun client baru, login, dan buat reservasi.

## Troubleshooting

- **"Koneksi gagal: ..."** → cek lagi 6 environment variable di atas, pastikan
  tidak ada spasi/salah ketik, dan `DB_SSL_CA` berisi teks certificate yang
  utuh (bukan path file).
- **Login selalu balik ke halaman login** → pastikan tabel `sessions` sudah
  ke-create (otomatis dibuat oleh `session_init.php` saat request pertama,
  tapi kalau user database tidak punya izin `CREATE TABLE`, minta akses itu
  di Aiven, atau jalankan manual query `CREATE TABLE` dari `database.sql`).
- **Ada perubahan `vercel.json` / rewrite rule tidak bekerja** → setelah edit
  `vercel.json`, redeploy ulang project dari tab **Deployments** di Vercel.
- **Gambar upload baru tidak muncul** → sudah dijelaskan di bagian "Batasan
  penting" di atas — ini memang keterbatasan Vercel, bukan bug.

## Menjalankan di lokal (XAMPP) — tetap bisa

Karena `database.php` fallback otomatis, kamu tetap bisa jalankan project ini
di XAMPP seperti biasa tanpa environment variable apapun (asal MySQL lokal
jalan dan database `reservasi_ruangan` sudah di-import dari `database.sql`).
