<?php
require_once __DIR__ . '/functions.php';
requireLogin();

$warung = getWarungList();
$alert = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_distribution'])) {
    $tanggal = trim($_POST['tanggal_distribusi'] ?? '');
    $warungId = (int)($_POST['warung_id'] ?? 0);
    $namaBaru = trim($_POST['nama_warung_baru'] ?? '');
    $salesName = trim($_POST['sales_name'] ?? '');
    $jumlah = max(0, (int)($_POST['jumlah_donat'] ?? 0));
    $harga = max(0, (int)($_POST['harga_jual'] ?? DEFAULT_HARGA_JUAL));
    $batas = trim($_POST['tanggal_batas_bs'] ?? $tanggal);
    $catatan = trim($_POST['catatan'] ?? '');

    if ($tanggal === '') {
        $alert = 'Tanggal distribusi wajib diisi.';
    } elseif ($warungId <= 0 && $namaBaru === '') {
        $alert = 'Pilih warung lama atau isi nama warung baru.';
    } elseif ($jumlah <= 0) {
        $alert = 'Jumlah donat harus lebih dari 0.';
    } else {
        $conn = dbConnect();
        if ($warungId <= 0) {
            $stmt = $conn->prepare('INSERT INTO warung (nama, alamat, pemilik, telepon, catatan, status) VALUES (?, "", "", "", ?, "aktif")');
            $stmt->bind_param('ss', $namaBaru, $catatan);
            $stmt->execute();
            $warungId = $conn->insert_id;
            $stmt->close();
        }

        $stokHari = getDailyStockRow($tanggal);
        if (!$stokHari) {
            upsertDailyStock($tanggal, DEFAULT_STOK_AWAL);
            $stokHari = getDailyStockRow($tanggal);
        }

        $conn2 = dbConnect();
        $sum = $conn2->query('SELECT COALESCE(SUM(jumlah_donat),0) AS total FROM distribusi_donat WHERE tanggal_distribusi = "' . $tanggal . '" AND status != "dibatalkan"')->fetch_assoc()['total'] ?? 0;
        $stokTersedia = max(0, (int)$stokHari['stok_awal'] - (int)$sum);

        if ($jumlah > $stokTersedia) {
            $alert = 'Stok tidak cukup. Distribusi melebihi stok tersedia.';
        } else {
            $transaksiId = generateTransaksiId();
            $stmt = $conn2->prepare('INSERT INTO distribusi_donat (transaksi_id, tanggal_distribusi, warung_id, sales_name, jumlah_donat, harga_jual, tanggal_batas_bs, catatan) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->bind_param('ssississ', $transaksiId, $tanggal, $warungId, $salesName, $jumlah, $harga, $batas, $catatan);
            if ($stmt->execute()) {
                syncDailyStock($tanggal);
                redirect('distribusi.php?success=1');
            } else {
                $alert = 'Gagal menyimpan distribusi: ' . $stmt->error;
            }
            $stmt->close();
        }
        $conn2->close();
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
                <p>Catat distribusi ke warung lama maupun baru</p>
            </div>
        </div>

        <?php if (!empty($alert)) : ?>
            <div class="alert error"><?= htmlspecialchars($alert) ?></div>
        <?php endif; ?>

        <div class="card">
            <h3>Form Distribusi</h3>
            <form method="POST" class="two-col">
                <div class="field">
                    <label>Tanggal distribusi</label>
                    <input type="date" name="tanggal_distribusi" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="field">
                    <label>Nama sales</label>
                    <input type="text" name="sales_name" placeholder="Opsional">
                </div>
                <div class="field">
                    <label>Pilih warung lama</label>
                    <select name="warung_id">
                        <option value="0">-- Pilih warung --</option>
                        <?php foreach ($warung as $item) : ?>
                            <option value="<?= (int)$item['id'] ?>"><?= htmlspecialchars($item['nama']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label>Warung baru (opsional)</label>
                    <input type="text" name="nama_warung_baru" placeholder="Isi jika warung belum ada">
                </div>
                <div class="field">
                    <label>Jumlah donat</label>
                    <input type="number" name="jumlah_donat" min="1" required>
                </div>
                <div class="field">
                    <label>Harga jual / biji</label>
                    <input type="number" name="harga_jual" min="1" value="1500" required>
                </div>
                <div class="field">
                    <label>Batas BS</label>
                    <input type="date" name="tanggal_batas_bs" value="<?= date('Y-m-d', strtotime('+6 days')) ?>" required>
                </div>
                <div class="field field-full">
                    <label>Catatan</label>
                    <textarea name="catatan"></textarea>
                </div>
                <div class="field-full">
                    <button type="submit" name="save_distribution" class="btn btn-primary">Simpan Distribusi</button>
                </div>
            </form>
        </div>

        <div class="card table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Warung</th>
                        <th>Tanggal</th>
                        <th>Jumlah</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($distribusiList as $row) : ?>
                        <tr>
                            <td><?= htmlspecialchars($row['transaksi_id']) ?></td>
                            <td><?= htmlspecialchars($row['warung_nama']) ?></td>
                            <td><?= formatTanggal($row['tanggal_distribusi']) ?></td>
                            <td><?= (int)$row['jumlah_donat'] ?></td>
                            <td><?= htmlspecialchars($row['status']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
    <?php include __DIR__ . '/includes/header.php'; ?>
</body>
</html>
