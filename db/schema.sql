CREATE DATABASE IF NOT EXISTS donat_ns CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE donat_ns;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(150) NOT NULL,
    role ENUM('admin','sales') DEFAULT 'admin',
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS warung (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(150) NOT NULL,
    alamat VARCHAR(255) DEFAULT NULL,
    pemilik VARCHAR(150) DEFAULT NULL,
    telepon VARCHAR(50) DEFAULT NULL,
    catatan TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('aktif','nonaktif') DEFAULT 'aktif'
);

CREATE TABLE IF NOT EXISTS stok_harian (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tanggal DATE NOT NULL,
    stok_awal INT NOT NULL DEFAULT 0,
    total_dibagikan INT NOT NULL DEFAULT 0,
    stok_tersedia INT NOT NULL DEFAULT 0,
    donat_di_warung INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_tanggal (tanggal)
);

CREATE TABLE IF NOT EXISTS distribusi_donat (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaksi_id VARCHAR(50) NOT NULL UNIQUE,
    tanggal_distribusi DATE NOT NULL,
    warung_id INT NOT NULL,
    sales_name VARCHAR(150) DEFAULT NULL,
    jumlah_donat INT NOT NULL,
    harga_jual INT NOT NULL DEFAULT 1500,
    tanggal_batas_bs DATE NOT NULL,
    catatan TEXT DEFAULT NULL,
    status ENUM('aktif','selesai','dibatalkan') DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_distribusi_tanggal (tanggal_distribusi),
    FOREIGN KEY (warung_id) REFERENCES warung(id)
);

CREATE TABLE IF NOT EXISTS hasil_penjualan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    distribusi_id INT NOT NULL,
    tanggal_pemeriksaan DATE NOT NULL,
    jumlah_dibagikan INT NOT NULL,
    bs INT NOT NULL DEFAULT 0,
    hilang_rusak INT NOT NULL DEFAULT 0,
    donat_terjual INT NOT NULL,
    total_penjualan BIGINT NOT NULL,
    status ENUM('selesai','pending') DEFAULT 'selesai',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_hasil_distribusi (distribusi_id),
    FOREIGN KEY (distribusi_id) REFERENCES distribusi_donat(id)
);

CREATE TABLE IF NOT EXISTS setoran_sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    distribusi_id INT NOT NULL,
    tanggal_setoran DATE NOT NULL,
    nama_sales VARCHAR(150) DEFAULT NULL,
    jumlah_setoran BIGINT NOT NULL DEFAULT 0,
    metode_pembayaran VARCHAR(100) DEFAULT 'tunai',
    catatan TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_setoran_distribusi (distribusi_id),
    FOREIGN KEY (distribusi_id) REFERENCES distribusi_donat(id)
);

INSERT INTO users (username, password_hash, full_name, role)
SELECT 'admin', '$2y$10$wV7R2SO2dVfJ0gKQx79Ylu4Jwfc0sG0klD6F4ZxEZ72YJx2q0mBrG', 'Pemilik Donat NS', 'admin'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'admin');
