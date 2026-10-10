<?php
require_once __DIR__ . '/functions.php';
requireLogin();

$conn = dbConnect();
$rows = $conn->query('SELECT s.*, d.transaksi_id, w.nama AS warung_nama FROM setoran_sales s JOIN distribusi_donat d ON d.id = s.distribusi_id JOIN warung w ON w.id = d.warung_id ORDER BY s.tanggal_setoran DESC')->fetch_all(MYSQLI_ASSOC);
$conn->close();

$start = $_GET['start'] ?? date('Y-m-01');
$end = $_GET['end'] ?? date('Y-m-d');
$report = getReportData($start, $end);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Penjualan - Donat NS</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/includes/header.php'; ?>
    <main class="page-wrap">
        <div class="page-header">
            <div>
                <h1>Laporan Penjualan</h1>
                <p>Filter tanggal dan ringkasan transaksi</p>
            </div>
        </div>

        <div class="card">
            <form method="GET" class="filter-row">
                <div class="field">
                    <label>Mulai</label>
                    <input type="date" name="start" value="<?= htmlspecialchars($start) ?>">
                </div>
                <div class="field">
                    <label>Sampai</label>
                    <input type="date" name="end" value="<?= htmlspecialchars($end) ?>">
                </div>
                <div class="field-full">
                    <button type="submit" class="btn btn-primary">Tampilkan</button>
                </div>
            </form>
        </div>

        <div class="card table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Transaksi</th>
                        <th>Warung</th>
                        <th>Distribusi</th>
                        <th>Penjualan</th>
                        <th>BS</th>
                        <th>Setoran</th>
                        <th>Sisa</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($report)) : ?>
                        <tr><td colspan="7">Belum ada data laporan.</td></tr>
                    <?php else: ?>
                        <?php foreach ($report as $item) : ?>
                            <tr>
                                <td><?= htmlspecialchars($item['transaksi_id']) ?></td>
                                <td><?= htmlspecialchars($item['warung_nama']) ?></td>
                                <td><?= (int)$item['jumlah_donat'] ?></td>
                                <td><?= formatRupiah((float)($item['total_terjual'] ?? 0) * (int)$item['harga_jual']) ?></td>
                                <td><?= (int)($item['total_bs'] ?? 0) ?></td>
                                <td><?= formatRupiah((float)($item['total_setoran'] ?? 0)) ?></td>
                                <td><?= formatRupiah(((float)($item['total_terjual'] ?? 0) * (int)$item['harga_jual']) - (float)($item['total_setoran'] ?? 0)) ?></td>
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
