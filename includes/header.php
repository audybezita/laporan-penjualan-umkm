<?php
require_once __DIR__ . '/../functions.php';

if (!isset($_SESSION['user_id'])) {
    redirect('index.php');
}

$user = loggedInUser();
?>
<header class="topbar">
    <div class="brand-wrap">
        <div class="brand-mark">D</div>
        <div class="brand-text">Donat NS</div>
    </div>

    <nav class="main-nav" aria-label="Main navigation">
        <a href="dashboard.php">Dashboard</a>
        <a href="warung.php">Data Warung</a>
        <a href="stok.php">Stok Donat</a>
        <a href="distribusi.php">Distribusi Donat</a>
        <a href="pengambilan-bs.php">Pengambilan BS</a>
        <a href="setoran.php">Setoran Sales</a>
        <a href="laporan.php">Laporan</a>
        <a href="logout.php" class="logout-link">Logout</a>
    </nav>

    <div class="user-name"><?= htmlspecialchars($user['full_name']) ?></div>
</header>
