<?php
require_once __DIR__ . '/functions.php';
requireLogin();

$conn = dbConnect();
$stockRows = $conn->query('SELECT * FROM stok_harian ORDER BY tanggal DESC LIMIT 30')->fetch_all(MYSQLI_ASSOC);
$conn->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_stock'])) {
    $tanggal = $_POST['tanggal'] ?? date('Y-m-d');
    $stokAwal = max(0, (int)($_POST['stok_awal'] ?? DEFAULT_STOK_AWAL));
    if ($tanggal === '') {
        $alert = 'Tanggal stok wajib diisi.';
    } else {
        $conn = dbConnect();
        $check = $conn->prepare('SELECT id FROM stok_harian WHERE tanggal = ? LIMIT 1');
        $check->bind_param('s', $tanggal);
        $check->execute();
        $hasRow = $check->get_result()->num_rows > 0;
        $check->close();

        if ($hasRow) {
            $stmt = $conn->prepare('UPDATE stok_harian SET stok_awal = ?, updated_at = NOW() WHERE tanggal = ?');
            $stmt->bind_param('is', $stokAwal, $tanggal);
            $stmt->execute();
            $stmt->close();
        } else {
            $stmt = $conn->prepare('INSERT INTO stok_harian (tanggal, stok_awal, total_dibagikan, stok_tersedia, donat_di_warung) VALUES (?, ?, 0, ?, 0)');
            $stmt->bind_param('si', $tanggal, $stokAwal, $stokAwal);
            $stmt->execute();
            $stmt->close();
        }
        $conn->close();
        redirect('stok.php?success=1');
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stok Donat - Donat NS</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/includes/header.php'; ?>
    <main class="page-wrap">
        <div class="page-header">
            <div>
                <h1>Stok Donat</h1>
                <p>Catat stok awal harian dan riwayat stok</p>
            </div>
        </div>

        <?php if (!empty($alert)) : ?>
            <div class="alert error"><?= htmlspecialchars($alert) ?></div>
        <?php endif; ?>

        <div class="card">
            <h3>Catat Stok Harian</h3>
            <form method="POST" class="two-col">
                <div class="field">
                    <label>Tanggal</label>
                    <input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="field">
                    <label>Stok awal</label>
                    <input type="number" name="stok_awal" value="400" min="0" required>
                </div>
                <div class="field-full">
                    <button type="submit" name="save_stock" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>

        <div class="card table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Stok Awal</th>
                        <th>Total Dibagikan</th>
                        <th>Stok Tersedia</th>
                        <th>Donat di Warung</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($stockRows)) : ?>
                        <tr><td colspan="5">Belum ada stok harian.</td></tr>
                    <?php else: ?>
                        <?php foreach ($stockRows as $row) : ?>
                            <tr>
                                <td><?= htmlspecialchars($row['tanggal']) ?></td>
                                <td><?= (int)$row['stok_awal'] ?></td>
                                <td><?= (int)$row['total_dibagikan'] ?></td>
                                <td><?= (int)$row['stok_tersedia'] ?></td>
                                <td><?= (int)$row['donat_di_warung'] ?></td>
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
