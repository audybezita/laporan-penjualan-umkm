<?php
require_once __DIR__ . '/functions.php';
requireLogin();

$alert = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_setoran'])) {
    $distribusiId = (int)($_POST['distribusi_id'] ?? 0);
    $tanggal = trim($_POST['tanggal_setoran'] ?? date('Y-m-d'));
    $jumlah = max(0, (int)($_POST['jumlah_setoran'] ?? 0));
    $meta = trim($_POST['metode_pembayaran'] ?? 'tunai');
    $sales = trim($_POST['nama_sales'] ?? '');
    $catatan = trim($_POST['catatan'] ?? '');

    if ($distribusiId <= 0) {
        $alert = 'Pilih transaksi distribusi.';
    } elseif ($jumlah <= 0) {
        $alert = 'Jumlah setoran harus lebih dari 0.';
    } else {
        $conn = dbConnect();
        $stmt = $conn->prepare('SELECT COALESCE(SUM(hp.total_penjualan),0) AS total_penjualan, COALESCE(SUM(ss.jumlah_setoran),0) AS total_setoran FROM hasil_penjualan hp LEFT JOIN setoran_sales ss ON ss.distribusi_id = hp.distribusi_id WHERE hp.distribusi_id = ?');
        $stmt->bind_param('i', $distribusiId);
        $stmt->execute();
        $summary = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $sisa = (int)$summary['total_penjualan'] - (int)$summary['total_setoran'];
        if ($jumlah > $sisa) {
            $alert = 'Setoran tidak boleh melebihi sisa kewajiban transaksi.';
        } else {
            $insert = $conn->prepare('INSERT INTO setoran_sales (distribusi_id, tanggal_setoran, nama_sales, jumlah_setoran, metode_pembayaran, catatan) VALUES (?, ?, ?, ?, ?, ?)');
            $insert->bind_param('isisss', $distribusiId, $tanggal, $sales, $jumlah, $meta, $catatan);
            if ($insert->execute()) {
                redirect('setoran.php?success=1');
            } else {
                $alert = 'Gagal menyimpan setoran.';
            }
            $insert->close();
        }
        $conn->close();
    }
}

$conn = dbConnect();
$rows = $conn->query('SELECT d.id, d.transaksi_id, w.nama AS warung_nama, d.tanggal_distribusi, COALESCE((SELECT SUM(total_penjualan) FROM hasil_penjualan WHERE distribusi_id = d.id),0) AS total_penjualan FROM distribusi_donat d JOIN warung w ON w.id = d.warung_id ORDER BY d.tanggal_distribusi DESC')->fetch_all(MYSQLI_ASSOC);
$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setoran Sales - Donat NS</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/includes/header.php'; ?>
    <main class="page-wrap">
        <div class="page-header">
            <div>
                <h1>Setoran Sales</h1>
                <p>Catat penerimaan uang hasil penjualan</p>
            </div>
        </div>

        <?php if (!empty($alert)) : ?>
            <div class="alert error"><?= htmlspecialchars($alert) ?></div>
        <?php endif; ?>

        <div class="card">
            <h3>Catat Setoran</h3>
            <form method="POST" class="two-col">
                <div class="field">
                    <label>Transaksi penjualan</label>
                    <select name="distribusi_id" required>
                        <option value="">-- Pilih transaksi --</option>
                        <?php foreach ($rows as $row) : ?>
                            <option value="<?= (int)$row['id'] ?>"><?= htmlspecialchars($row['transaksi_id']) ?> - <?= htmlspecialchars($row['warung_nama']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label>Tanggal setoran</label>
                    <input type="date" name="tanggal_setoran" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="field">
                    <label>Nama sales</label>
                    <input type="text" name="nama_sales" placeholder="Opsional">
                </div>
                <div class="field">
                    <label>Jumlah setoran</label>
                    <input type="number" name="jumlah_setoran" min="1" value="0" required>
                </div>
                <div class="field">
                    <label>Metode pembayaran</label>
                    <select name="metode_pembayaran">
                        <option value="tunai">Tunai</option>
                        <option value="transfer">Transfer</option>
                    </select>
                </div>
                <div class="field field-full">
                    <label>Catatan</label>
                    <textarea name="catatan"></textarea>
                </div>
                <div class="field-full">
                    <button type="submit" name="save_setoran" class="btn btn-primary">Simpan Setoran</button>
                </div>
            </form>
        </div>
    </main>
    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
