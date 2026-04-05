<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

if (current_user()) {
    header('Location: index.php');
    exit;
}

$error = '';
$ok = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $password2 = (string) ($_POST['password2'] ?? '');
    if (strlen($username) < 3) {
        $error = 'Username must be at least 3 characters.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $password2) {
        $error = 'Passwords do not match.';
    } else {
        try {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $st = db()->prepare("INSERT INTO hms_users (username, password_hash, role) VALUES (?, ?, 'user')");
            $st->execute([$username, $hash]);
            $ok = true;
        } catch (PDOException $e) {
            $sqlState = $e->errorInfo[0] ?? '';
            $msg = strtolower($e->getMessage());
            if ($sqlState === '23505' || str_contains($msg, 'unique')) {
                $error = 'That username is already taken.';
            } else {
                $error = 'Could not register. Try again.';
            }
        }
    }
}

$pageTitle = 'Register';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<main class="container auth-box">
    <h1>Register</h1>
    <div class="card">
        <?php if ($ok): ?>
            <div class="flash flash-ok">Account created. You can <a href="login.php">log in</a> now.</div>
        <?php else: ?>
            <?php if ($error): ?>
                <div class="flash flash-err"><?= h($error) ?></div>
            <?php endif; ?>
            <form method="post" action="">
                <div class="form-row">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required minlength="3" autofocus>
                </div>
                <div class="form-row">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required minlength="6">
                </div>
                <div class="form-row">
                    <label for="password2">Confirm password</label>
                    <input type="password" id="password2" name="password2" required minlength="6">
                </div>
                <button type="submit" class="btn">Register</button>
            </form>
        <?php endif; ?>
        <p class="auth-links"><a href="login.php">Back to login</a></p>
    </div>
</main>
</body>
</html>
