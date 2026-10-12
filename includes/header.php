<?php
require_once __DIR__ . '/../functions.php';

if (!isset($_SESSION['user_id'])) {
    redirect('index.php');
}
$user = loggedInUser();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donat NS</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header class="topbar">
        <div class="brand">Donat NS</div>
        <nav class="main-nav">
            <a href="dashboard.php">Dashboard</a>
            <a href="warung.php">Data Warung</a>
            <a href="stok.php">Stok Donat</a>
            <a href="distribusi.php">Distribusi Donat</a>
            <a href="pengambilan-bs.php">Pengambilan BS</a>
            <a href="setoran.php">Setoran Sales</a>
            <a href="laporan.php">Laporan Penjualan</a>
            <a href="logout.php">Logout</a>
        </nav>
        <div class="user-name"><?= htmlspecialchars($user['full_name']) ?></div>
    </header>
</body>
</html>
