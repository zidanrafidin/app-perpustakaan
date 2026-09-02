# Sistem Perpustakaan Digital Kampus

Aplikasi manajemen perpustakaan kampus untuk mengelola buku, anggota, dan transaksi peminjaman.

## Cara Menjalankan Project

1. Clone repository: `git clone https://github.com/zidanrafidin/app-perpustakaan.git`
2. Masuk ke direktori: `cd app-perpustakaan`
3. Install dependensi: `composer install`
4. Copy environment: `cp .env.example .env`
5. Generate key: `php artisan key:generate`
6. Jalankan server: `php artisan serve`

## Pemahaman Konsep MVC

- **Model:** Mengelola representasi struktur data tabel database dan aturan bisnisnya.
- **View:** Mengelola antarmuka/tampilan HTML (Blade) yang berinteraksi langsung dengan pengguna.
- **Controller:** Logika penghubung yang memproses permintaan pengguna, mengambil data via Model, dan menampilkannya melalui View.
