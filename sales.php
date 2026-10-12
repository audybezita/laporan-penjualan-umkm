<?php
require_once __DIR__ . '/functions.php';
requireLogin();

$alert = '';
$conn = dbConnect();
$salesList = $conn->query('SELECT * FROM sales ORDER BY nama ASC')->fetch_all(MYSQLI_ASSOC);
$conn->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_sales'])) {
    $nama = trim($_POST['nama'] ?? '');
    $telepon = trim($_POST['telepon'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');
    $catatan = trim($_POST['catatan'] ?? '');

    if ($nama === '') {
        $alert = 'Nama sales wajib diisi.';
    } else {
        $conn = dbConnect();
        $stmt = $conn->prepare('INSERT INTO sales (nama, telepon, alamat, catatan, status) VALUES (?, ?, ?, ?, "aktif")');
        $stmt->bind_param('ssss', $nama, $telepon, $alamat, $catatan);
        if ($stmt->execute()) {
            $stmt->close();
            $conn->close();
            redirect('sales.php?success=1');
        } else {
            $alert = 'Gagal menambahkan sales: ' . $stmt->error;
            $stmt->close();
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
    <title>Data Sales - Donat NS</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/includes/header.php'; ?>
    <main class="page-wrap">
        <div class="page-header">
            <div>
                <h1>Data Sales</h1>
                <p>Kelola data sales dan penjual Donat NS</p>
            </div>
            <a href="#form-sales" class="btn btn-primary">+ Tambah Sales</a>
        </div>

        <?php if (!empty($alert)) : ?>
            <div class="alert error"><?= htmlspecialchars($alert) ?></div>
        <?php endif; ?>

        <div class="card" id="form-sales">
            <h3>Tambah Sales Baru</h3>
            <form method="POST" class="two-col">
                <div class="field">
                    <label>Nama Sales</label>
                    <input type="text" name="nama" required>
                </div>
                <div class="field">
                    <label>Nomor Telepon</label>
                    <input type="text" name="telepon" placeholder="Opsional">
                </div>
                <div class="field field-full">
                    <label>Alamat</label>
                    <input type="text" name="alamat" placeholder="Opsional">
                </div>
                <div class="field field-full">
                    <label>Catatan</label>
                    <textarea name="catatan" placeholder="Opsional"></textarea>
                </div>
                <div class="field-full">
                    <button type="submit" name="save_sales" class="btn btn-primary">Simpan Sales</button>
                </div>
            </form>
        </div>

        <div class="card table-wrap">
            <h3>Daftar Sales</h3>
            <table>
                <thead>
                    <tr>
                        <th>Nama Sales</th>
                        <th>Telepon</th>
                        <th>Alamat</th>
                        <th>Status</th>
                        <th>Bergabung</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($salesList)) : ?>
                        <tr><td colspan="5" style="text-align:center; padding:20px; color:#999;">Belum ada data sales.</td></tr>
                    <?php else: ?>
                        <?php foreach ($salesList as $item) : ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($item['nama']) ?></strong></td>
                                <td><?= htmlspecialchars($item['telepon'] ?: '-') ?></td>
                                <td><?= htmlspecialchars($item['alamat'] ?: '-') ?></td>
                                <td>
                                    <span class="badge-status <?= strtolower($item['status']) ?>">
                                        <?= $item['status'] === 'aktif' ? '✓ Aktif' : '✗ Nonaktif' ?>
                                    </span>
                                </td>
                                <td><?= formatTanggal($item['created_at']) ?></td>
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
