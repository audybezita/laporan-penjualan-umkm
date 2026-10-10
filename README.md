# Donat NS - Sistem Informasi Laporan Penjualan dan Distribusi

Project ini adalah basis awal aplikasi web PHP/MySQL untuk sistem informasi laporan penjualan dan distribusi Donat NS.

## Fitur yang tersedia
- Sistem login admin
- Manajemen warung
- Stok harian
- Distribusi donat ke warung
- Pengambilan BS dan hasil penjualan
- Setoran sales
- Dashboard ringkasan
- Laporan harian/mingguan/bulanan
- Grafik Chart.js
- Script SQL untuk database MySQL

## Struktur utama
- `config.php` : konfigurasi database dan aplikasi
- `functions.php` : helper fungsi, koneksi, validasi, kalkulasi
- `install.php` : membuat tabel dan user default
- `index.php` : halaman login
- `dashboard.php` : dashboard ringkasan dan grafik
- `warung.php` : daftar dan tambah warung
- `stok.php` : stok harian
- `distribusi.php` : pencatatan distribusi
- `pengambilan-bs.php` : hasil pemeriksaan BS
- `setoran.php` : pencatatan setoran sales
- `laporan.php` : laporan dan filter
- `logout.php` : logout
- `db/schema.sql` : skema database MySQL
- `assets/css/style.css` : styling
- `assets/js/app.js` : script UI dan chart helper

## Langkah awal
1. Pastikan XAMPP aktif dengan Apache dan MySQL.
2. Buka `http://localhost/laporan-penjualan-umkm/install.php`.
3. Jika berhasil, login dengan:
   - username: `admin`
   - password: `admin123`
4. Lanjutkan ke `http://localhost/laporan-penjualan-umkm/dashboard.php`

## Catatan penting
- Database default: `donat_ns`
- User DB default: `root` / password kosong
- Jika ingin disesuaikan, edit `config.php`.
- Script ini dirancang untuk kebutuhan awal aplikasi lokal dan dapat dikembangkan lebih lanjut sesuai kebutuhan usaha Donat NS.
