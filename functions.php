<?php
require_once __DIR__ . '/config.php';

function dbConnect()
{
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        throw new RuntimeException('Koneksi database gagal: ' . $conn->connect_error);
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}

function redirect(string $location): void
{
    header('Location: ' . $location);
    exit;
}

function formatRupiah($value): string
{
    return 'Rp' . number_format((float)$value, 0, ',', '.');
}

function formatTanggal($date): string
{
    if (empty($date)) {
        return '-';
    }
    return date('d M Y', strtotime($date));
}

function ensureDatabase(): void
{
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS);
    if ($conn->connect_error) {
        throw new RuntimeException('Koneksi database server gagal: ' . $conn->connect_error);
    }

    $conn->query('CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci');
    $conn->select_db(DB_NAME);

    $queries = [
        "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(100) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            full_name VARCHAR(150) NOT NULL,
            role ENUM('admin','sales') DEFAULT 'admin',
            active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS warung (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nama VARCHAR(150) NOT NULL,
            alamat VARCHAR(255) DEFAULT NULL,
            pemilik VARCHAR(150) DEFAULT NULL,
            telepon VARCHAR(50) DEFAULT NULL,
            catatan TEXT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            status ENUM('aktif','nonaktif') DEFAULT 'aktif'
        )",
        "CREATE TABLE IF NOT EXISTS stok_harian (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tanggal DATE NOT NULL,
            stok_awal INT NOT NULL DEFAULT 0,
            total_dibagikan INT NOT NULL DEFAULT 0,
            stok_tersedia INT NOT NULL DEFAULT 0,
            donat_di_warung INT NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_tanggal (tanggal)
        )",
        "CREATE TABLE IF NOT EXISTS distribusi_donat (
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
            FOREIGN KEY (warung_id) REFERENCES warung(id)
        )",
        "CREATE TABLE IF NOT EXISTS hasil_penjualan (
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
            FOREIGN KEY (distribusi_id) REFERENCES distribusi_donat(id)
        )",
        "CREATE TABLE IF NOT EXISTS setoran_sales (
            id INT AUTO_INCREMENT PRIMARY KEY,
            distribusi_id INT NOT NULL,
            tanggal_setoran DATE NOT NULL,
            nama_sales VARCHAR(150) DEFAULT NULL,
            jumlah_setoran BIGINT NOT NULL DEFAULT 0,
            metode_pembayaran VARCHAR(100) DEFAULT 'tunai',
            catatan TEXT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (distribusi_id) REFERENCES distribusi_donat(id)
        )"
    ];

    foreach ($queries as $sql) {
        if (!$conn->query($sql)) {
            throw new RuntimeException('Gagal membuat tabel: ' . $conn->error);
        }
    }

    $passwordHash = password_hash('admin123', PASSWORD_DEFAULT);
    $checkUser = $conn->query("SELECT id FROM users WHERE username = 'admin' LIMIT 1");
    if ($checkUser->num_rows === 0) {
        $conn->query("INSERT INTO users (username, password_hash, full_name, role) VALUES ('admin', '$passwordHash', 'Pemilik Donat NS', 'admin')");
    }

    $conn->close();
}

function loggedInUser(): ?array
{
    if (!isset($_SESSION['user_id'])) {
        return null;
    }

    $conn = dbConnect();
    $stmt = $conn->prepare('SELECT id, username, full_name, role FROM users WHERE id = ? AND active = 1 LIMIT 1');
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    $conn->close();

    return $user ?: null;
}

function requireLogin(): void
{
    if (!loggedInUser()) {
        redirect('index.php');
    }
}

function authenticateUser(string $username, string $password): ?array
{
    $conn = dbConnect();
    $stmt = $conn->prepare('SELECT id, username, password_hash, full_name, role FROM users WHERE username = ? AND active = 1 LIMIT 1');
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    $conn->close();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return null;
    }

    return $user;
}

function generateTransaksiId(): string
{
    return 'DN-' . date('YmdHis') . '-' . random_int(100, 999);
}

function getDailyStockRow(string $tanggal): ?array
{
    $conn = dbConnect();
    $stmt = $conn->prepare('SELECT * FROM stok_harian WHERE tanggal = ? LIMIT 1');
    $stmt->bind_param('s', $tanggal);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();
    $conn->close();
    return $data;
}

function upsertDailyStock(string $tanggal, int $stokAwal = DEFAULT_STOK_AWAL): void
{
    $conn = dbConnect();
    $existing = getDailyStockRow($tanggal);

    if ($existing) {
        $newStok = max(0, (int)$stokAwal);
        $stmt = $conn->prepare('UPDATE stok_harian SET stok_awal = ?, stok_tersedia = ?, updated_at = NOW() WHERE tanggal = ?');
        $stmt->bind_param('iis', $newStok, $newStok, $tanggal);
        $stmt->execute();
        $stmt->close();
    } else {
        $stmt = $conn->prepare('INSERT INTO stok_harian (tanggal, stok_awal, total_dibagikan, stok_tersedia, donat_di_warung) VALUES (?, ?, 0, ?, 0)');
        $stmt->bind_param('sii', $tanggal, $stokAwal, $stokAwal);
        $stmt->execute();
        $stmt->close();
    }

    $conn->close();
}

function syncDailyStock(string $tanggal): void
{
    $conn = dbConnect();
    $stmt = $conn->prepare('SELECT COALESCE(SUM(jumlah_donat),0) AS total_dibagikan FROM distribusi_donat WHERE tanggal_distribusi = ? AND status != "dibatalkan"');
    $stmt->bind_param('s', $tanggal);
    $stmt->execute();
    $dibagikan = $stmt->get_result()->fetch_assoc()['total_dibagikan'] ?? 0;
    $stmt->close();

    $stmt = $conn->prepare('SELECT COALESCE(SUM(donat_terjual),0) AS total_terjual, COALESCE(SUM(bs),0) AS total_bs FROM hasil_penjualan hp JOIN distribusi_donat d ON d.id = hp.distribusi_id WHERE DATE(d.tanggal_distribusi) = ?');
    $stmt->bind_param('s', $tanggal);
    $stmt->execute();
    $penjualan = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $totalTerjual = (int)($penjualan['total_terjual'] ?? 0);
    $totalBs = (int)($penjualan['total_bs'] ?? 0);

    $row = getDailyStockRow($tanggal);
    $stokAwal = $row['stok_awal'] ?? DEFAULT_STOK_AWAL;
    $stokTersedia = max(0, $stokAwal - (int)$dibagikan);
    $diWarung = max(0, (int)$dibagikan - $totalTerjual - $totalBs);

    $stmt = $conn->prepare('UPDATE stok_harian SET total_dibagikan = ?, stok_tersedia = ?, donat_di_warung = ?, updated_at = NOW() WHERE tanggal = ?');
    $stmt->bind_param('iiis', $dibagikan, $stokTersedia, $diWarung, $tanggal);
    $stmt->execute();
    $stmt->close();
    $conn->close();
}

function getDashboardCards(): array
{
    $conn = dbConnect();
    $today = date('Y-m-d');

    $queries = [
        'warung_baru' => "SELECT COUNT(*) AS total FROM warung WHERE DATE(created_at) = CURDATE()",
        'warung_aktif' => "SELECT COUNT(*) AS total FROM warung WHERE status = 'aktif'",
        'stok_awal' => "SELECT COALESCE(stok_awal, 0) AS total FROM stok_harian WHERE tanggal = CURDATE() LIMIT 1",
        'dibagikan' => "SELECT COALESCE(SUM(jumlah_donat),0) AS total FROM distribusi_donat WHERE DATE(tanggal_distribusi) = CURDATE() AND status != 'dibatalkan'",
        'terjual' => "SELECT COALESCE(SUM(hp.donat_terjual),0) AS total FROM hasil_penjualan hp JOIN distribusi_donat d ON d.id = hp.distribusi_id WHERE DATE(d.tanggal_distribusi) = CURDATE()",
        'bs' => "SELECT COALESCE(SUM(hp.bs),0) AS total FROM hasil_penjualan hp JOIN distribusi_donat d ON d.id = hp.distribusi_id WHERE DATE(d.tanggal_distribusi) = CURDATE()",
        'hilang' => "SELECT COALESCE(SUM(hp.hilang_rusak),0) AS total FROM hasil_penjualan hp JOIN distribusi_donat d ON d.id = hp.distribusi_id WHERE DATE(d.tanggal_distribusi) = CURDATE()",
        'penjualan' => "SELECT COALESCE(SUM(hp.total_penjualan),0) AS total FROM hasil_penjualan hp JOIN distribusi_donat d ON d.id = hp.distribusi_id WHERE DATE(d.tanggal_distribusi) = CURDATE()",
        'setoran' => "SELECT COALESCE(SUM(s.jumlah_setoran),0) AS total FROM setoran_sales s WHERE DATE(s.tanggal_setoran) = CURDATE()",
        'belum_setor' => "SELECT COALESCE(SUM(hp.total_penjualan),0) - COALESCE(SUM(ss.jumlah_setoran),0) AS total FROM hasil_penjualan hp LEFT JOIN setoran_sales ss ON ss.distribusi_id = hp.distribusi_id WHERE DATE(d.tanggal_distribusi) = CURDATE()",
        'transaksi_pending' => "SELECT COUNT(*) AS total FROM distribusi_donat WHERE status = 'aktif'"
    ];

    $result = [];
    foreach ($queries as $key => $sql) {
        $query = $conn->query($sql);
        $result[$key] = (int)($query->fetch_assoc()['total'] ?? 0);
    }

    $row = getDailyStockRow($today);
    $result['stok_tersedia'] = (int)($row['stok_tersedia'] ?? DEFAULT_STOK_AWAL);
    $result['di_warung'] = (int)($row['donat_di_warung'] ?? 0);
    $conn->close();

    return $result;
}

function getDashboardChartData(): array
{
    $conn = dbConnect();
    $result = ['penjualan' => [], 'distribusi' => [], 'komposisi' => []];

    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime('-' . $i . ' days'));

        $stmt = $conn->prepare('SELECT COALESCE(SUM(hp.total_penjualan),0) AS total FROM hasil_penjualan hp JOIN distribusi_donat d ON d.id = hp.distribusi_id WHERE DATE(d.tanggal_distribusi) = ?');
        $stmt->bind_param('s', $date);
        $stmt->execute();
        $val = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
        $result['penjualan'][] = (float)$val;
        $stmt->close();

        $stmt = $conn->prepare('SELECT COALESCE(SUM(d.jumlah_donat),0) AS dibagikan, COALESCE(SUM(hp.donat_terjual),0) AS terjual, COALESCE(SUM(hp.bs),0) AS bs FROM distribusi_donat d LEFT JOIN hasil_penjualan hp ON hp.distribusi_id = d.id WHERE DATE(d.tanggal_distribusi) = ?');
        $stmt->bind_param('s', $date);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $result['distribusi'][] = [
            'date' => date('d M', strtotime($date)),
            'dibagikan' => (int)($row['dibagikan'] ?? 0),
            'terjual' => (int)($row['terjual'] ?? 0),
            'bs' => (int)($row['bs'] ?? 0),
        ];
        $stmt->close();
    }

    $stmt = $conn->prepare('SELECT COALESCE(SUM(donat_terjual),0) AS terjual, COALESCE(SUM(bs),0) AS bs, COALESCE(SUM(hilang_rusak),0) AS hilang FROM hasil_penjualan');
    $stmt->execute();
    $komposisi = $stmt->get_result()->fetch_assoc();
    $result['komposisi'] = [
        'terjual' => (int)($komposisi['terjual'] ?? 0),
        'bs' => (int)($komposisi['bs'] ?? 0),
        'hilang' => (int)($komposisi['hilang'] ?? 0)
    ];
    $stmt->close();
    $conn->close();

    return $result;
}

function getWarungBySearch(string $keyword = ''): array
{
    $conn = dbConnect();
    $condition = '';
    if ($keyword !== '') {
        $condition = ' WHERE nama LIKE ? ';
    }

    $sql = 'SELECT * FROM warung ' . $condition . ' ORDER BY created_at DESC';
    $stmt = $conn->prepare($sql);
    if ($keyword !== '') {
        $like = '%' . $keyword . '%';
        $stmt->bind_param('s', $like);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $conn->close();
    return $rows;
}

function getWarungList(): array
{
    return getWarungBySearch();
}

function getActiveDistributionList(): array
{
    $conn = dbConnect();
    $sql = 'SELECT d.*, w.nama AS warung_nama FROM distribusi_donat d JOIN warung w ON w.id = d.warung_id WHERE d.status = "aktif" ORDER BY d.tanggal_distribusi DESC';
    $result = $conn->query($sql);
    $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $conn->close();
    return $rows;
}

function getAllDistributions(): array
{
    $conn = dbConnect();
    $sql = 'SELECT d.*, w.nama AS warung_nama FROM distribusi_donat d JOIN warung w ON w.id = d.warung_id ORDER BY d.tanggal_distribusi DESC';
    $result = $conn->query($sql);
    $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $conn->close();
    return $rows;
}

function getSetoranSummaryForDistribusi(int $distribusiId): array
{
    $conn = dbConnect();
    $sql = 'SELECT COALESCE(SUM(jumlah_setoran),0) AS total_setoran FROM setoran_sales WHERE distribusi_id = ?';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $distribusiId);
    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()['total_setoran'] ?? 0;
    $stmt->close();
    $conn->close();
    return ['total_setoran' => (int)$total];
}

function getReportData(string $startDate, string $endDate): array
{
    $conn = dbConnect();
    $sql = 'SELECT d.*, w.nama AS warung_nama,
            COALESCE((SELECT SUM(s.jumlah_setoran) FROM setoran_sales s WHERE s.distribusi_id = d.id),0) AS total_setoran,
            COALESCE((SELECT SUM(hp.donat_terjual) FROM hasil_penjualan hp WHERE hp.distribusi_id = d.id),0) AS total_terjual,
            COALESCE((SELECT SUM(hp.bs) FROM hasil_penjualan hp WHERE hp.distribusi_id = d.id),0) AS total_bs,
            COALESCE((SELECT SUM(hp.hilang_rusak) FROM hasil_penjualan hp WHERE hp.distribusi_id = d.id),0) AS total_hilang
            FROM distribusi_donat d
            JOIN warung w ON w.id = d.warung_id
            WHERE d.tanggal_distribusi BETWEEN ? AND ?
            ORDER BY d.tanggal_distribusi DESC';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $startDate, $endDate);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $conn->close();
    return $rows;
}
