<?php
require_once __DIR__ . '/functions.php';
requireLogin();
if (isset($_GET['logout'])) {
    session_destroy();
    redirect('index.php');
}
?>
