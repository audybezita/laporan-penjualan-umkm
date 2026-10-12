<?php
require_once __DIR__ . '/functions.php';
requireLogin();

$conn = dbConnect();
$warung = $conn->query('SELECT * FROM sales ORDER BY nama ASC')->fetch_all(MYSQLI_ASSOC);
$conn->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_warung'])) {
    $nama = trim($_POST['nama'] ?? '');
    if ($nama === '') {
        $alert = 'Nama warung wajib diisi.';
    } else {
        $conn = dbConnect();
        $stmt = $conn->prepare('INSERT INTO sales (nama, telepon, alamat, catatan, status) VALUES (?, ?, ?, ?, "aktif")');
        $stmt->bind_param('ssss', $nama, trim($_POST['telepon'] ?? ''), trim($_POST['alamat'] ?? ''), trim($_POST['catatan'] ?? ''));
        $stmt->execute();
        $stmt->close();
        $conn->close();
        redirect('warung.php?success=1');
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Warung - Donat NS</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/includes/header.php'; ?>
    <main class="page-wrap">
        <div class="page-header">
            <div>
                <h1>Data Warung (Sales)</h1>
                <p>Kelola warung dan data penjual Donat NS</p>
            </div>
            <a href="sales.php" class="btn btn-primary">Buka Manajemen Sales</a>
        </div>

        <?php if (!empty($alert)) : ?>
            <div class="alert error"><?= htmlspecialchars($alert) ?></div>
        <?php endif; ?>

        <div class="card table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Telepon</th>
                        <th>Alamat</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($warung)) : ?>
                        <tr><td colspan="4" style="text-align:center; padding:20px;">Belum ada data warung.</td></tr>
                    <?php else: ?>
                        <?php foreach ($warung as $item) : ?>
                            <tr>
                                <td><?= htmlspecialchars($item['nama']) ?></td>
                                <td><?= htmlspecialchars($item['telepon'] ?: '-') ?></td>
                                <td><?= htmlspecialchars($item['alamat'] ?: '-') ?></td>
                                <td><?= htmlspecialchars($item['status']) ?></td>
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
