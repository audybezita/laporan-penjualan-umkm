<?php
require_once __DIR__ . '/functions.php';
requireLogin();

$alert = '';
$distribusi = getAllDistributions();

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
        $stmt = $conn->prepare('SELECT jumlah_donat, harga_jual, tanggal_distribusi, warung_id FROM distribusi_donat WHERE id = ? LIMIT 1');
        $stmt->bind_param('i', $distribusiId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) {
            $alert = 'Distribusi tidak ditemukan.';
        } else {
            $jumlahDibagikan = (int)$row['jumlah_donat'];
            if (($bs + $hilang) > $jumlahDibagikan) {
                $alert = 'BS dan donat hilang/rusak tidak boleh melebihi jumlah donat yang dibagikan.';
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
                    redirect('pengambilan-bs.php?success=1');
                } else {
                    $alert = 'Gagal menyimpan hasil pemeriksaan: ' . $insert->error;
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
                <h1>Pengambilan BS</h1>
                <p>Catat hasil pemeriksaan, BS, dan donat terjual</p>
            </div>
        </div>

        <?php if (!empty($alert)) : ?>
            <div class="alert error"><?= htmlspecialchars($alert) ?></div>
        <?php endif; ?>

        <div class="card">
            <h3>Form Hasil Pemeriksaan</h3>
            <form method="POST" class="two-col">
                <div class="field">
                    <label>Transaksi distribusi</label>
                    <select name="distribusi_id" required>
                        <option value="">-- Pilih transaksi --</option>
                        <?php foreach ($distribusi as $row) : ?>
                            <option value="<?= (int)$row['id'] ?>"><?= htmlspecialchars($row['transaksi_id']) ?> - <?= htmlspecialchars($row['warung_id']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label>Tanggal pemeriksaan</label>
                    <input type="date" name="tanggal_pemeriksaan" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="field">
                    <label>BS dikembalikan</label>
                    <input type="number" name="bs" min="0" value="0" required>
                </div>
                <div class="field">
                    <label>Hilangan / rusak</label>
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
                    <button type="submit" name="save_bs" class="btn btn-primary">Simpan Hasil</button>
                </div>
            </form>
        </div>
    </main>
    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
