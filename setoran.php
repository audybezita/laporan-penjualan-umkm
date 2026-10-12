<?php
require_once __DIR__ . '/functions.php';
requireLogin();

$alert = '';
$conn = dbConnect();
$distribusiList = $conn->query('SELECT d.*, s.nama AS sales_nama, COALESCE((SELECT SUM(jumlah_setoran) FROM setoran_sales WHERE distribusi_id = d.id),0) AS total_setoran_existing FROM distribusi_donat d JOIN sales s ON s.id = d.sales_id WHERE d.status = "selesai" ORDER BY d.tanggal_distribusi DESC')->fetch_all(MYSQLI_ASSOC);
$conn->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_setoran'])) {
    $distribusiId = (int)($_POST['distribusi_id'] ?? 0);
    $tanggal = trim($_POST['tanggal_setoran'] ?? date('Y-m-d'));
    $jumlah = max(0, (int)($_POST['jumlah_setoran'] ?? 0));
    $metode = trim($_POST['metode_pembayaran'] ?? 'tunai');
    $catatan = trim($_POST['catatan'] ?? '');

    if ($distribusiId <= 0) {
        $alert = 'Pilih transaksi distribusi.';
    } elseif ($jumlah <= 0) {
        $alert = 'Jumlah setoran harus lebih dari 0.';
    } else {
        $conn = dbConnect();
        $stmt = $conn->prepare('SELECT COALESCE(SUM(hp.total_penjualan),0) AS total_penjualan FROM hasil_penjualan hp WHERE hp.distribusi_id = ?');
        $stmt->bind_param('i', $distribusiId);
        $stmt->execute();
        $totalPenjualan = $stmt->get_result()->fetch_assoc()['total_penjualan'] ?? 0;
        $stmt->close();

        $stmt = $conn->prepare('SELECT COALESCE(SUM(jumlah_setoran),0) AS total_setoran FROM setoran_sales WHERE distribusi_id = ?');
        $stmt->bind_param('i', $distribusiId);
        $stmt->execute();
        $totalSetoran = $stmt->get_result()->fetch_assoc()['total_setoran'] ?? 0;
        $stmt->close();

        $sisa = (float)$totalPenjualan - (float)$totalSetoran;
        if ((float)$jumlah > $sisa) {
            $alert = 'Setoran melebihi sisa kewajiban. Sisa yang harus disetorkan: ' . formatRupiah($sisa);
        } else {
            $insert = $conn->prepare('INSERT INTO setoran_sales (distribusi_id, tanggal_setoran, jumlah_setoran, metode_pembayaran, catatan) VALUES (?, ?, ?, ?, ?)');
            $insert->bind_param('isiss', $distribusiId, $tanggal, $jumlah, $metode, $catatan);
            if ($insert->execute()) {
                $insert->close();
                $conn->close();
                redirect('setoran.php?success=1');
            } else {
                $alert = 'Gagal menyimpan setoran: ' . $insert->error;
            }
            $insert->close();
        }
        $conn->close();
    }
}
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
                <p>Catat penerimaan uang dari hasil penjualan donat</p>
            </div>
        </div>

        <?php if (!empty($alert)) : ?>
            <div class="alert error"><?= htmlspecialchars($alert) ?></div>
        <?php endif; ?>

        <div class="card">
            <h3>Input Setoran Baru</h3>
            <form method="POST" class="two-col">
                <div class="field">
                    <label>Pilih Transaksi Penjualan</label>
                    <select name="distribusi_id" required>
                        <option value="">-- Pilih transaksi --</option>
                        <?php foreach ($distribusiList as $row) : ?>
                            <?php 
                                $penjualan = 0;
                                $conn = dbConnect();
                                $stmt = $conn->prepare('SELECT COALESCE(SUM(total_penjualan),0) AS total FROM hasil_penjualan WHERE distribusi_id = ?');
                                $stmt->bind_param('i', $row['id']);
                                $stmt->execute();
                                $penjualan = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
                                $stmt->close();
                                $conn->close();
                                $sisa = (float)$penjualan - (float)$row['total_setoran_existing'];
                            ?>
                            <option value="<?= (int)$row['id'] ?>">
                                <?= htmlspecialchars($row['sales_nama']) ?> - <?= formatRupiah($penjualan) ?> (Sisa: <?= formatRupiah($sisa) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label>Tanggal Setoran</label>
                    <input type="date" name="tanggal_setoran" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="field">
                    <label>Jumlah Setoran</label>
                    <input type="number" name="jumlah_setoran" min="1" value="0" required>
                </div>
                <div class="field">
                    <label>Metode Pembayaran</label>
                    <select name="metode_pembayaran">
                        <option value="tunai">Tunai</option>
                        <option value="transfer">Transfer Bank</option>
                        <option value="cek">Cek</option>
                    </select>
                </div>
                <div class="field field-full">
                    <label>Catatan</label>
                    <textarea name="catatan" placeholder="Opsional"></textarea>
                </div>
                <div class="field-full">
                    <button type="submit" name="save_setoran" class="btn btn-primary">Simpan Setoran</button>
                </div>
            </form>
        </div>

        <div class="card" style="margin-top: 24px;">
            <h3>📊 Ringkasan Setoran Hari Ini</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 16px;">
                <?php
                    $conn = dbConnect();
                    $summary = $conn->query('SELECT COALESCE(SUM(jumlah_setoran),0) AS total, COUNT(*) AS jumlah FROM setoran_sales WHERE DATE(tanggal_setoran) = CURDATE()')->fetch_assoc();
                    $conn->close();
                ?>
                <div style="background: linear-gradient(135deg, #7ec8ff, #a0d8ff); color: white; padding: 18px; border-radius: 12px; box-shadow: 0 8px 16px rgba(116, 189, 255, 0.2);">
                    <p style="margin: 0 0 8px; opacity: 0.9; font-size: 0.9rem;">Total Setoran Hari Ini</p>
                    <strong style="font-size: 1.8rem;"><?= formatRupiah($summary['total'] ?? 0) ?></strong>
                </div>
                <div style="background: linear-gradient(135deg, #ffb5c9, #ff9fd6); color: white; padding: 18px; border-radius: 12px; box-shadow: 0 8px 16px rgba(255, 107, 168, 0.2);">
                    <p style="margin: 0 0 8px; opacity: 0.9; font-size: 0.9rem;">Jumlah Transaksi</p>
                    <strong style="font-size: 1.8rem;"><?= (int)($summary['jumlah'] ?? 0) ?></strong>
                </div>
            </div>
        </div>
    </main>
    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
