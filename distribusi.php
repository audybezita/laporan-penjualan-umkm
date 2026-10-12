<?php
require_once __DIR__ . '/functions.php';
requireLogin();

$salesList = getSalesList();
$alert = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_distribution'])) {
    $tanggal = trim($_POST['tanggal_distribusi'] ?? '');
    $salesId = (int)($_POST['sales_id'] ?? 0);
    $jumlah = max(0, (int)($_POST['jumlah_donat'] ?? 0));
    $harga = max(0, (int)($_POST['harga_jual'] ?? DEFAULT_HARGA_JUAL));
    $catatan = trim($_POST['catatan'] ?? '');

    if ($tanggal === '') {
        $alert = 'Tanggal distribusi wajib diisi.';
    } elseif ($salesId <= 0) {
        $alert = 'Pilih sales untuk distribusi.';
    } elseif ($jumlah <= 0) {
        $alert = 'Jumlah donat harus lebih dari 0.';
    } else {
        $conn = dbConnect();
        $stokHari = getDailyStockRow($tanggal);
        if (!$stokHari) {
            upsertDailyStock($tanggal, DEFAULT_STOK_AWAL);
            $stokHari = getDailyStockRow($tanggal);
        }

        $sum = $conn->query('SELECT COALESCE(SUM(jumlah_donat),0) AS total FROM distribusi_donat WHERE tanggal_distribusi = "' . $conn->real_escape_string($tanggal) . '" AND status != "dibatalkan"')->fetch_assoc()['total'] ?? 0;
        $stokTersedia = max(0, (int)$stokHari['stok_awal'] - (int)$sum);

        if ($jumlah > $stokTersedia) {
            $alert = 'Stok tidak cukup. Tersedia hanya ' . $stokTersedia . ' donat.';
        } else {
            $transaksiId = generateTransaksiId();
            $stmt = $conn->prepare('INSERT INTO distribusi_donat (transaksi_id, tanggal_distribusi, sales_id, jumlah_donat, harga_jual, catatan) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->bind_param('sssiss', $transaksiId, $tanggal, $salesId, $jumlah, $harga, $catatan);
            if ($stmt->execute()) {
                syncDailyStock($tanggal);
                redirect('distribusi.php?success=1');
            } else {
                $alert = 'Gagal menyimpan distribusi: ' . $stmt->error;
            }
            $stmt->close();
        }
        $conn->close();
    }
}

$distribusiList = getAllDistributions();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Distribusi Donat - Donat NS</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/includes/header.php'; ?>
    <main class="page-wrap">
        <div class="page-header">
            <div>
                <h1>Distribusi Donat</h1>
                <p>Catat distribusi donat kepada sales</p>
            </div>
        </div>

        <?php if (!empty($alert)) : ?>
            <div class="alert error"><?= htmlspecialchars($alert) ?></div>
        <?php endif; ?>

        <div class="card">
            <h3>Form Distribusi Baru</h3>
            <form method="POST" class="two-col">
                <div class="field">
                    <label>Tanggal Distribusi</label>
                    <input type="date" name="tanggal_distribusi" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="field">
                    <label>Pilih Sales</label>
                    <select name="sales_id" required>
                        <option value="0">-- Pilih sales --</option>
                        <?php foreach ($salesList as $item) : ?>
                            <option value="<?= (int)$item['id'] ?>"><?= htmlspecialchars($item['nama']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label>Jumlah Donat</label>
                    <input type="number" name="jumlah_donat" min="1" required>
                </div>
                <div class="field">
                    <label>Harga Jual / Biji</label>
                    <input type="number" name="harga_jual" min="1" value="1500" required>
                </div>
                <div class="field field-full">
                    <label>Catatan</label>
                    <textarea name="catatan" placeholder="Opsional"></textarea>
                </div>
                <div class="field-full">
                    <button type="submit" name="save_distribution" class="btn btn-primary">Simpan Distribusi</button>
                </div>
            </form>
        </div>

        <div class="card table-wrap">
            <h3>Riwayat Distribusi</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID Transaksi</th>
                        <th>Sales</th>
                        <th>Tanggal</th>
                        <th>Jumlah</th>
                        <th>Umur (Hari)</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($distribusiList)) : ?>
                        <tr><td colspan="6" style="text-align:center; padding:20px; color:#999;">Belum ada distribusi.</td></tr>
                    <?php else: ?>
                        <?php foreach ($distribusiList as $row) : ?>
                            <?php $umur = getUmurDonat($row['tanggal_distribusi']); ?>
                            <?php $statusUmur = getStatusUmurDonat($row['tanggal_distribusi']); ?>
                            <tr style="background: <?php if ($statusUmur === 'Kadaluarsa') echo '#ffe6e6'; elseif ($statusUmur === 'Critical') echo '#fff0e6'; elseif ($statusUmur === 'Warning') echo '#fffde6'; ?>">
                                <td><strong><?= htmlspecialchars($row['transaksi_id']) ?></strong></td>
                                <td><?= htmlspecialchars($row['sales_nama']) ?></td>
                                <td><?= formatTanggal($row['tanggal_distribusi']) ?></td>
                                <td><?= (int)$row['jumlah_donat'] ?></td>
                                <td><strong><?= $umur ?> hari</strong></td>
                                <td><span class="badge-status <?= strtolower($statusUmur) ?>"><?= $statusUmur ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
