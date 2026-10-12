<?php
require_once __DIR__ . '/functions.php';
requireLogin();
$user = loggedInUser();

$cards = getDashboardCards();
$chartData = getDashboardChartData();
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

        <section class="stats-grid">
            <div class="stat-card">
                <span>Stok awal hari ini</span>
                <strong><?= $cards['stok_awal'] ?? 0 ?></strong>
            </div>
            <div class="stat-card">
                <span>Total donat dibagikan</span>
                <strong><?= $cards['dibagikan'] ?? 0 ?></strong>
            </div>
            <div class="stat-card">
                <span>Stok tersedia</span>
                <strong><?= $cards['stok_tersedia'] ?? 0 ?></strong>
            </div>
            <div class="stat-card">
                <span>Donat di warung</span>
                <strong><?= $cards['di_warung'] ?? 0 ?></strong>
            </div>
            <div class="stat-card">
                <span>Donat terjual</span>
                <strong><?= $cards['terjual'] ?? 0 ?></strong>
            </div>
            <div class="stat-card">
                <span>Total BS</span>
                <strong><?= $cards['bs'] ?? 0 ?></strong>
            </div>
            <div class="stat-card">
                <span>Donat hilang/rusak</span>
                <strong><?= $cards['hilang'] ?? 0 ?></strong>
            </div>
            <div class="stat-card">
                <span>Hasil penjualan</span>
                <strong><?= formatRupiah($cards['penjualan'] ?? 0) ?></strong>
            </div>
            <div class="stat-card">
                <span>Setoran diterima</span>
                <strong><?= formatRupiah($cards['setoran'] ?? 0) ?></strong>
            </div>
            <div class="stat-card">
                <span>Belum disetorkan</span>
                <strong><?= formatRupiah($cards['belum_setor'] ?? 0) ?></strong>
            </div>
            <div class="stat-card">
                <span>Warung baru</span>
                <strong><?= $cards['warung_baru'] ?? 0 ?></strong>
            </div>
            <div class="stat-card">
                <span>Warung aktif</span>
                <strong><?= $cards['warung_aktif'] ?? 0 ?></strong>
            </div>
            <div class="stat-card">
                <span>Transaksi pending</span>
                <strong><?= $cards['transaksi_pending'] ?? 0 ?></strong>
            </div>
        </section>

        <section class="chart-grid">
            <div class="card chart-card">
                <h3>Grafik hasil penjualan</h3>
                <canvas id="salesChart"></canvas>
            </div>
            <div class="card chart-card">
                <h3>Grafik distribusi donat</h3>
                <canvas id="distributionChart"></canvas>
            </div>
            <div class="card chart-card">
                <h3>Komposisi hasil pemeriksaan</h3>
                <canvas id="compositionChart"></canvas>
            </div>
        </section>
    </main>

    <?php include __DIR__ . '/includes/footer.php'; ?>

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
                    borderColor: '#f7a900',
                    backgroundColor: 'rgba(247,169,0,0.15)',
                    tension: 0.3,
                    fill: true,
                }]
            },
            options: { responsive: true }
        });

        new Chart(document.getElementById('distributionChart'), {
            type: 'bar',
            data: {
                labels: distributionValues.map(item => item.date),
                datasets: [
                    { label: 'Dibagikan', data: distributionValues.map(item => item.dibagikan), backgroundColor: '#7a3b14' },
                    { label: 'Terjual', data: distributionValues.map(item => item.terjual), backgroundColor: '#f7a900' },
                    { label: 'BS', data: distributionValues.map(item => item.bs), backgroundColor: '#8d6e63' }
                ]
            },
            options: { responsive: true }
        });

        new Chart(document.getElementById('compositionChart'), {
            type: 'doughnut',
            data: {
                labels: ['Terjual', 'BS', 'Hilang / Rusak'],
                datasets: [{
                    data: [compositionValues.terjual ?? 0, compositionValues.bs ?? 0, compositionValues.hilang ?? 0],
                    backgroundColor: ['#f7a900', '#7a3b14', '#d9d9d9']
                }]
            },
            options: { responsive: true }
        });
    </script>
</body>
</html>
