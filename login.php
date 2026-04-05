<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

if (current_user()) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    if ($username === '' || $password === '') {
        $error = 'Please enter username and password.';
    } else {
        $st = db()->prepare('SELECT id, password_hash FROM hms_users WHERE username = ?');
        $st->execute([$username]);
        $row = $st->fetch();
        if ($row && password_verify($password, $row['password_hash'])) {
            $_SESSION['user_id'] = (int) $row['id'];
            header('Location: index.php');
            exit;
        }
        $error = 'Invalid login.';
    }
}

$pageTitle = 'Login';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<main class="container auth-box">
    <h1>Login</h1>
    <div class="card">
        <?php if ($error): ?>
            <div class="flash flash-err"><?= h($error) ?></div>
        <?php endif; ?>
        <form method="post" action="">
            <div class="form-row">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required autofocus>
            </div>
            <div class="form-row">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn">Sign in</button>
        </form>
        <p class="auth-links"><a href="register.php">Create an account</a></p>
        <p class="auth-links" style="margin-top:1rem;font-size:0.85rem;color:#555;">Fresh database: sign in as <strong>admin</strong> / <strong>admin123</strong>, then change it in production.</p>
    </div>
</main>
</body>
</html>
