<?php
require_once __DIR__ . '/functions.php';
requireLogin();

$conn = dbConnect();
$keyword = trim($_GET['q'] ?? '');
$searchSql = $keyword === '' ? 'SELECT * FROM warung ORDER BY created_at DESC' : 'SELECT * FROM warung WHERE nama LIKE ? ORDER BY created_at DESC';
$stmt = $conn->prepare($searchSql);
if ($keyword !== '') {
    $like = '%' . $keyword . '%';
    $stmt->bind_param('s', $like);
}
$stmt->execute();
$warung = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_warung'])) {
    $nama = trim($_POST['nama'] ?? '');
    if ($nama === '') {
        $alert = 'Nama warung wajib diisi.';
    } else {
        $conn = dbConnect();
        $stmt = $conn->prepare('INSERT INTO warung (nama, alamat, pemilik, telepon, catatan, status) VALUES (?, ?, ?, ?, ?, "aktif")');
        $stmt->bind_param('sssss', $nama, trim($_POST['alamat'] ?? ''), trim($_POST['pemilik'] ?? ''), trim($_POST['telepon'] ?? ''), trim($_POST['catatan'] ?? ''));
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
                <h1>Data Warung</h1>
                <p>Kelola warung aktif dan riwayat distribusi</p>
            </div>
            <a href="#form-warung" class="btn btn-primary">Tambah Warung</a>
        </div>

        <?php if (!empty($alert)) : ?>
            <div class="alert error"><?= htmlspecialchars($alert) ?></div>
        <?php endif; ?>

        <div class="actions-bar">
            <form method="GET" class="search-form">
                <input type="text" name="q" value="<?= htmlspecialchars($keyword) ?>" placeholder="Cari nama warung...">
                <button type="submit" class="btn btn-secondary">Cari</button>
            </form>
        </div>

        <div class="card" id="form-warung">
            <h3>Tambah Warung Baru</h3>
            <form method="POST" class="two-col">
                <div class="field">
                    <label>Nama warung</label>
                    <input type="text" name="nama" required>
                </div>
                <div class="field">
                    <label>Alamat</label>
                    <input type="text" name="alamat">
                </div>
                <div class="field">
                    <label>Nama pemilik</label>
                    <input type="text" name="pemilik">
                </div>
                <div class="field">
                    <label>Telepon</label>
                    <input type="text" name="telepon">
                </div>
                <div class="field field-full">
                    <label>Catatan</label>
                    <textarea name="catatan"></textarea>
                </div>
                <div class="field-full">
                    <button type="submit" name="save_warung" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>

        <div class="card table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Alamat</th>
                        <th>Pemilik</th>
                        <th>Telepon</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($warung)) : ?>
                        <tr><td colspan="5">Belum ada data warung.</td></tr>
                    <?php else: ?>
                        <?php foreach ($warung as $item) : ?>
                            <tr>
                                <td><?= htmlspecialchars($item['nama']) ?></td>
                                <td><?= htmlspecialchars($item['alamat'] ?: '-') ?></td>
                                <td><?= htmlspecialchars($item['pemilik'] ?: '-') ?></td>
                                <td><?= htmlspecialchars($item['telepon'] ?: '-') ?></td>
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
