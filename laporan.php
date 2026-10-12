<?php
require_once __DIR__ . '/functions.php';
requireLogin();

$start = $_GET['start'] ?? date('Y-m-01');
$end = $_GET['end'] ?? date('Y-m-d');
$report = getReportData($start, $end);
$filterSales = $_GET['sales_id'] ?? '';

$conn = dbConnect();
$salesList = $conn->query('SELECT * FROM sales WHERE status = "aktif" ORDER BY nama ASC')->fetch_all(MYSQLI_ASSOC);
$conn->close();

if ($filterSales !== '') {
    $salesId = (int)$filterSales;
    $report = array_filter($report, function($item) use ($salesId) {
        return (int)$item['sales_id'] === $salesId;
    });
}
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
                <p>Analisis penjualan dan setoran per transaksi</p>
            </div>
        </div>

        <div class="card">
            <h3>Filter Laporan</h3>
            <form method="GET" class="filter-row">
                <div class="field">
                    <label>Dari Tanggal</label>
                    <input type="date" name="start" value="<?= htmlspecialchars($start) ?>">
                </div>
                <div class="field">
                    <label>Hingga Tanggal</label>
                    <input type="date" name="end" value="<?= htmlspecialchars($end) ?>">
                </div>
                <div class="field">
                    <label>Pilih Sales (Opsional)</label>
                    <select name="sales_id">
                        <option value="">-- Semua Sales --</option>
                        <?php foreach ($salesList as $item) : ?>
                            <option value="<?= (int)$item['id'] ?>" <?= $filterSales === (string)$item['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($item['nama']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field-full">
                    <button type="submit" class="btn btn-primary">Tampilkan Laporan</button>
                </div>
            </form>
        </div>

        <?php if (!empty($report)): ?>
            <div class="card" style="margin-top: 20px;">
                <h3>Ringkasan Periode <?= formatTanggal($start) ?> - <?= formatTanggal($end) ?></h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 20px;">
                    <?php
                        $totalPenjualan = 0;
                        $totalSetoran = 0;
                        $totalBS = 0;
                        $totalDonatTerjual = 0;
                        $totalTransaksi = count($report);

                        foreach ($report as $item) {
                            $totalPenjualan += (float)($item['total_terjual'] ?? 0) * (int)$item['harga_jual'];
                            $totalSetoran += (float)($item['total_setoran'] ?? 0);
                            $totalBS += (int)($item['total_bs'] ?? 0);
                            $totalDonatTerjual += (int)($item['total_terjual'] ?? 0);
                        }
                        $sisaSetoran = $totalPenjualan - $totalSetoran;
                    ?>
                    <div style="background: linear-gradient(135deg, #7ec8ff, #a0d8ff); color: white; padding: 16px; border-radius: 12px;">
                        <p style="margin: 0 0 6px; opacity: 0.9; font-size: 0.85rem;">Total Transaksi</p>
                        <strong style="font-size: 1.6rem;"><?= $totalTransaksi ?></strong>
                    </div>
                    <div style="background: linear-gradient(135deg, #ffb5c9, #ff9fd6); color: white; padding: 16px; border-radius: 12px;">
                        <p style="margin: 0 0 6px; opacity: 0.9; font-size: 0.85rem;">Total Penjualan</p>
                        <strong style="font-size: 1.4rem;"><?= formatRupiah($totalPenjualan) ?></strong>
                    </div>
                    <div style="background: linear-gradient(135deg, #fff5a9, #ffd76e); color: #553b00; padding: 16px; border-radius: 12px;">
                        <p style="margin: 0 0 6px; opacity: 0.9; font-size: 0.85rem;">Total Setoran</p>
                        <strong style="font-size: 1.4rem;"><?= formatRupiah($totalSetoran) ?></strong>
                    </div>
                    <div style="background: linear-gradient(135deg, #c8f7c8, #a0f0a0); color: #2d5c2d; padding: 16px; border-radius: 12px;">
                        <p style="margin: 0 0 6px; opacity: 0.9; font-size: 0.85rem;">Belum Disetorkan</p>
                        <strong style="font-size: 1.4rem;"><?= formatRupiah($sisaSetoran) ?></strong>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="card table-wrap" style="margin-top: 20px;">
            <h3>Detail Transaksi</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID Transaksi</th>
                        <th>Sales</th>
                        <th>Tanggal</th>
                        <th>Jumlah Donat</th>
                        <th>Terjual</th>
                        <th>BS</th>
                        <th>Total Penjualan</th>
                        <th>Setoran</th>
                        <th>Sisa</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($report)) : ?>
                        <tr><td colspan="9" style="text-align:center; padding:20px; color:#999;">Tidak ada laporan untuk periode ini.</td></tr>
                    <?php else: ?>
                        <?php foreach ($report as $item) : ?>
                            <?php 
                                $penjualan = (int)($item['total_terjual'] ?? 0) * (int)$item['harga_jual'];
                                $setoran = (float)($item['total_setoran'] ?? 0);
                                $sisa = $penjualan - $setoran;
                                $statusSisa = $sisa > 0 ? 'warning' : 'normal';
                            ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($item['transaksi_id']) ?></strong></td>
                                <td><?= htmlspecialchars($item['sales_nama']) ?></td>
                                <td><?= formatTanggal($item['tanggal_distribusi']) ?></td>
                                <td><?= (int)$item['jumlah_donat'] ?></td>
                                <td><?= (int)($item['total_terjual'] ?? 0) ?></td>
                                <td><?= (int)($item['total_bs'] ?? 0) ?></td>
                                <td><?= formatRupiah($penjualan) ?></td>
                                <td><?= formatRupiah($setoran) ?></td>
                                <td style="color: <?= $statusSisa === 'warning' ? '#d9534f' : '#5cb85c' ?>;"><strong><?= formatRupiah($sisa) ?></strong></td>
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
