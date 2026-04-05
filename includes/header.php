<?php
declare(strict_types=1);
$u = current_user();
$pageTitle = $pageTitle ?? 'Hospitalia';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($pageTitle) ?></title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<?php if ($u): ?>
<nav class="navbar">
    <a class="brand" href="index.php">Hospitalia</a>
    <div class="nav-links">
        <a href="index.php">Home</a>
        <a href="patients.php">Patients</a>
        <a href="schedules.php">Schedules</a>
        <a href="bills.php">Bills</a>
        <?php if (is_admin()): ?>
            <a href="doctors.php">Doctors</a>
            <a href="appointments.php">Appointments</a>
        <?php endif; ?>
        <span class="nav-user"><?= h($u['username']) ?> (<?= h($u['role']) ?>)</span>
        <a href="logout.php">Logout</a>
    </div>
</nav>
<?php endif; ?>
<main class="container">
