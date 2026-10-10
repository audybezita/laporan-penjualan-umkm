<?php
if (!isset($_SESSION['user_id'])) {
    redirect('index.php');
}
$user = loggedInUser();
?>
<footer class="footer">
    <p>© <?= date('Y') ?> Donat NS - Sistem Informasi Laporan Penjualan</p>
</footer>
<script src="assets/js/app.js"></script>
