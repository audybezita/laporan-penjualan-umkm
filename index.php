<?php
require_once __DIR__ . '/functions.php';

try {
    ensureDatabase();
    if (isset($_POST['login'])) {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        $user = authenticateUser($username, $password);
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            redirect('dashboard.php');
        }

        $error = 'Username atau password salah.';
    }
} catch (Throwable $e) {
    $error = $e->getMessage();
}

$bodyClass = 'auth-page';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Donat NS</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="<?= $bodyClass ?>">
    <div class="auth-wrap">
        <div class="auth-box">
            <div class="brand-box">
                <h1>Donat NS</h1>
                <p>Sistem Informasi Laporan Penjualan & Distribusi</p>
            </div>

            <?php if (!empty($error)) : ?>
                <div class="alert error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" class="auth-form">
                <div class="field">
                    <label>Username</label>
                    <input type="text" name="username" required value="admin">
                </div>
                <div class="field">
                    <label>Password</label>
                    <input type="password" name="password" required value="admin123">
                </div>
                <button type="submit" name="login" class="btn btn-primary">Masuk</button>
            </form>

            <div class="auth-meta">
                <small>Default login: admin / admin123</small>
            </div>
        </div>
    </div>
</body>
</html>
