<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_login();

$pageTitle = 'Dashboard';
require __DIR__ . '/includes/header.php';

$pdo = db();
$stats = [
    'patients' => (int) $pdo->query('SELECT COUNT(*) FROM hms_patients')->fetchColumn(),
    'schedules' => (int) $pdo->query('SELECT COUNT(*) FROM hms_schedules')->fetchColumn(),
    'bills' => (int) $pdo->query('SELECT COUNT(*) FROM hms_bills')->fetchColumn(),
];
if (is_admin()) {
    $stats['doctors'] = (int) $pdo->query('SELECT COUNT(*) FROM hms_doctors')->fetchColumn();
    $stats['appointments'] = (int) $pdo->query('SELECT COUNT(*) FROM hms_appointments')->fetchColumn();
}
?>
<h1>Dashboard</h1>
<p>Welcome, <strong><?= h(current_user()['username']) ?></strong>.</p>

<div class="card">
    <h2 style="margin-top:0;">Quick counts</h2>
    <ul>
        <li>Patients: <?= $stats['patients'] ?></li>
        <li>Schedules: <?= $stats['schedules'] ?></li>
        <li>Bills: <?= $stats['bills'] ?></li>
        <?php if (is_admin()): ?>
            <li>Doctors: <?= $stats['doctors'] ?></li>
            <li>Appointments: <?= $stats['appointments'] ?></li>
        <?php endif; ?>
    </ul>
</div>

<div class="card">
    <h2 style="margin-top:0;">Shortcuts</h2>
    <p>
        <a class="btn" href="patients.php">Patients</a>
        <a class="btn btn-secondary" href="schedules.php">Schedules</a>
        <a class="btn btn-secondary" href="bills.php">Bills</a>
        <?php if (is_admin()): ?>
            <a class="btn btn-secondary" href="doctors.php">Doctors</a>
            <a class="btn btn-secondary" href="appointments.php">Appointments</a>
        <?php endif; ?>
    </p>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
