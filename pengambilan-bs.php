<?php
require_once __DIR__ . '/functions.php';
requireLogin();

$alert = '';
$distribusi = getActiveDistributions();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_bs'])) {
    $distribusiId = (int)($_POST['distribusi_id'] ?? 0);
    $tanggalPemeriksaan = trim($_POST['tanggal_pemeriksaan'] ?? date('Y-m-d'));
    $bs = max(0, (int)($_POST['bs'] ?? 0));
    $hilang = max(0, (int)($_POST['hilang_rusak'] ?? 0));
    $status = trim($_POST['status'] ?? 'selesai');

    if ($distribusiId <= 0) {
        $alert = 'Pilih transaksi distribusi.';
    } else {
        $conn = dbConnect();
        $stmt = $conn->prepare('SELECT jumlah_donat, harga_jual FROM distribusi_donat WHERE id = ? LIMIT 1');
        $stmt->bind_param('i', $distribusiId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) {
            $alert = 'Distribusi tidak ditemukan.';
        } else {
            $jumlahDibagikan = (int)$row['jumlah_donat'];
            if (($bs + $hilang) > $jumlahDibagikan) {
                $alert = 'BS + Hilang tidak boleh melebihi jumlah donat yang dibagikan.';
            } else {
                $donatTerjual = max(0, $jumlahDibagikan - $bs - $hilang);
                $totalPenjualan = $donatTerjual * (int)$row['harga_jual'];

                $insert = $conn->prepare('INSERT INTO hasil_penjualan (distribusi_id, tanggal_pemeriksaan, jumlah_dibagikan, bs, hilang_rusak, donat_terjual, total_penjualan, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
                $insert->bind_param('isiiiiis', $distribusiId, $tanggalPemeriksaan, $jumlahDibagikan, $bs, $hilang, $donatTerjual, $totalPenjualan, $status);
                if ($insert->execute()) {
                    $update = $conn->prepare('UPDATE distribusi_donat SET status = "selesai" WHERE id = ?');
                    $update->bind_param('i', $distribusiId);
                    $update->execute();
                    $update->close();
                    $insert->close();
                    $conn->close();
                    redirect('pengambilan-bs.php?success=1');
                } else {
                    $alert = 'Gagal menyimpan hasil: ' . $insert->error;
                }
                $insert->close();
            }
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
    <title>Pengambilan BS - Donat NS</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/includes/header.php'; ?>
    <main class="page-wrap">
        <div class="page-header">
            <div>
                <h1>Pengambilan BS dan Hasil Penjualan</h1>
                <p>Catat hasil pemeriksaan donat dari sales</p>
            </div>
        </div>

        <?php if (!empty($alert)) : ?>
            <div class="alert error"><?= htmlspecialchars($alert) ?></div>
        <?php endif; ?>

        <div class="card">
            <h3>Input Hasil Pemeriksaan</h3>
            <form method="POST" class="two-col">
                <div class="field">
                    <label>Pilih Transaksi Distribusi</label>
                    <select name="distribusi_id" required>
                        <option value="">-- Pilih transaksi --</option>
                        <?php foreach ($distribusi as $row) : ?>
                            <?php $umur = getUmurDonat($row['tanggal_distribusi']); ?>
                            <option value="<?= (int)$row['id'] ?>">
                                <?= htmlspecialchars($row['transaksi_id']) ?> - <?= htmlspecialchars($row['sales_nama']) ?> (<?= $umur ?> hari)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label>Tanggal Pemeriksaan</label>
                    <input type="date" name="tanggal_pemeriksaan" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="field">
                    <label>Donat BS (Dikembalikan)</label>
                    <input type="number" name="bs" min="0" value="0" required>
                </div>
                <div class="field">
                    <label>Hilang / Rusak</label>
                    <input type="number" name="hilang_rusak" min="0" value="0" required>
                </div>
                <div class="field">
                    <label>Status</label>
                    <select name="status">
                        <option value="selesai">Selesai</option>
                        <option value="pending">Pending</option>
                    </select>
                </div>
                <div class="field-full">
                    <button type="submit" name="save_bs" class="btn btn-primary">Simpan Hasil Pemeriksaan</button>
                </div>
            </form>
        </div>

        <div class="card" style="margin-top: 24px;">
            <h3>📋 Informasi Cara Penggunaan</h3>
            <div style="color: #6d5b6b; line-height: 1.6; font-size: 0.95rem;">
                <p><strong>Langkah-langkah pengambilan BS:</strong></p>
                <ol>
                    <li>Pilih transaksi distribusi dari sales</li>
                    <li>Masukkan tanggal pemeriksaan BS (biasanya hari ke-6 atau setelah batas BS)</li>
                    <li>Input jumlah donat BS yang dikembalikan</li>
                    <li>Input jumlah donat yang hilang atau rusak</li>
                    <li>Sistem otomatis menghitung: <strong>Donat Terjual = Jumlah Awal - BS - Hilang</strong></li>
                    <li>Total penjualan dihitung dari: <strong>Donat Terjual × Harga Jual</strong></li>
                </ol>
                <p style="background: #f8f3ff; padding: 12px; border-radius: 8px; border-left: 4px solid #7ec8ff;">
                    💡 <strong>Tips:</strong> Pastikan umur donat sudah mencapai batas sebelum melakukan pengambilan BS untuk akurasi laporan.
                </p>
            </div>
        </div>
    </main>
    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
