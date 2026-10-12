<?php
require_once __DIR__ . '/functions.php';
requireLogin();

$user = loggedInUser();
$cards = getDashboardCards();
$chartData = getDashboardChartData();
$distributionWarning = getDistributionWarning();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Donat NS</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include __DIR__ . '/includes/header.php'; ?>

    <main class="page-wrap">
        <div class="page-header">
            <div>
                <h1>Dashboard</h1>
                <p>Ringkasan penjualan dan distribusi Donat NS</p>
            </div>
            <div class="badge">Welcome, <?= htmlspecialchars($user['full_name']) ?></div>
        </div>

        <?php if (!empty($distributionWarning)): ?>
            <div class="alert-warning">
                <strong>⚠️ Peringatan: Donat Mendekati Batas Umur</strong>
                <p><?= count($distributionWarning) ?> transaksi donat akan kadaluarsa dalam 2 hari ke depan. Segera lakukan pengambilan BS.</p>
            </div>
        <?php endif; ?>

        <section class="stats-grid">
            <div class="stat-card">
                <span>Stok Awal Hari Ini</span>
                <strong><?= $cards['stok_awal'] ?? 0 ?></strong>
            </div>
            <div class="stat-card">
                <span>Donat Dibagikan</span>
                <strong><?= $cards['dibagikan'] ?? 0 ?></strong>
            </div>
            <div class="stat-card">
                <span>Stok Tersedia</span>
                <strong><?= $cards['stok_tersedia'] ?? 0 ?></strong>
            </div>
            <div class="stat-card">
                <span>Donat Terjual</span>
                <strong><?= $cards['terjual'] ?? 0 ?></strong>
            </div>
            <div class="stat-card">
                <span>BS Dikembalikan</span>
                <strong><?= $cards['bs'] ?? 0 ?></strong>
            </div>
            <div class="stat-card">
                <span>Total Penjualan</span>
                <strong><?= formatRupiah($cards['penjualan'] ?? 0) ?></strong>
            </div>
            <div class="stat-card">
                <span>Setoran Diterima</span>
                <strong><?= formatRupiah($cards['setoran'] ?? 0) ?></strong>
            </div>
            <div class="stat-card">
                <span>Sales Aktif</span>
                <strong><?= $cards['sales_aktif'] ?? 0 ?></strong>
            </div>
            <div class="stat-card">
                <span>Transaksi Aktif</span>
                <strong><?= $cards['transaksi_aktif'] ?? 0 ?></strong>
            </div>
        </section>

        <section class="chart-grid">
            <div class="card chart-card">
                <h3>Grafik Hasil Penjualan 7 Hari</h3>
                <canvas id="salesChart"></canvas>
            </div>
            <div class="card chart-card">
                <h3>Grafik Distribusi Donat</h3>
                <canvas id="distributionChart"></canvas>
            </div>
            <div class="card chart-card">
                <h3>Komposisi Hasil Pemeriksaan</h3>
                <canvas id="compositionChart"></canvas>
            </div>
        </section>
    </main>

    <?php include __DIR__ . '/includes/footer.php';

    <script>
        const salesValues = <?= json_encode($chartData['penjualan'] ?? []) ?>;
        const distributionValues = <?= json_encode($chartData['distribusi'] ?? []) ?>;
        const compositionValues = <?= json_encode($chartData['komposisi'] ?? []) ?>;

        new Chart(document.getElementById('salesChart'), {
            type: 'line',
            data: {
                labels: Array.from({length: salesValues.length}, (_, i) => {
                    const d = new Date();
                    d.setDate(d.getDate() - (salesValues.length - 1 - i));
                    return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short' });
                }),
                datasets: [{
                    label: 'Hasil penjualan',
                    data: salesValues,
                    borderColor: '#ff7eb3',
                    backgroundColor: 'rgba(255, 126, 179, 0.1)',
                    tension: 0.4,
                    fill: true,
                    borderWidth: 3
                }]
            },
            options: { responsive: true, maintainAspectRatio: true }
        });

        new Chart(document.getElementById('distributionChart'), {
            type: 'bar',
            data: {
                labels: distributionValues.map(item => item.date),
                datasets: [
                    { label: 'Dibagikan', data: distributionValues.map(item => item.dibagikan), backgroundColor: '#a0d8ff' },
                    { label: 'Terjual', data: distributionValues.map(item => item.terjual), backgroundColor: '#7ec8ff' },
                    { label: 'BS', data: distributionValues.map(item => item.bs), backgroundColor: '#ff9fd6' }
                ]
            },
            options: { responsive: true, maintainAspectRatio: true }
        });

        new Chart(document.getElementById('compositionChart'), {
            type: 'doughnut',
            data: {
                labels: ['Terjual', 'BS', 'Hilang / Rusak'],
                datasets: [{
                    data: [compositionValues.terjual ?? 0, compositionValues.bs ?? 0, compositionValues.hilang ?? 0],
                    backgroundColor: ['#7ec8ff', '#ff7eb3', '#ffb5c9']
                }]
            },
            options: { responsive: true, maintainAspectRatio: true }
        });
    </script>
</body>
</html>
