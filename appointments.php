<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_admin();

$pdo = db();
$flashMsg = $_SESSION['flash_msg'] ?? '';
$flashErr = $_SESSION['flash_err'] ?? '';
unset($_SESSION['flash_msg'], $_SESSION['flash_err']);
$action = $_GET['action'] ?? 'list';

$doctors = $pdo->query('SELECT id, name FROM hms_doctors ORDER BY name')->fetchAll();
$patients = $pdo->query('SELECT id, name FROM hms_patients ORDER BY name')->fetchAll();
$schedules = $pdo->query('
    SELECT s.id, s.day_of_week, s.start_time, s.end_time, d.name AS doctor_name, s.doctor_id
    FROM hms_schedules s JOIN hms_doctors d ON d.id = s.doctor_id
    ORDER BY d.name, s.day_of_week, s.start_time
')->fetchAll();

if ($action === 'delete' && isset($_GET['id'])) {
    $st = $pdo->prepare('DELETE FROM hms_appointments WHERE id = ?');
    $st->execute([(int) $_GET['id']]);
    $_SESSION['flash_msg'] = 'Appointment removed.';
    header('Location: appointments.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $doctorId = (int) ($_POST['doctor_id'] ?? 0);
    $patientId = (int) ($_POST['patient_id'] ?? 0);
    $scheduleId = (int) ($_POST['schedule_id'] ?? 0);
    $apptDt = trim((string) ($_POST['appt_datetime'] ?? ''));
    $status = trim((string) ($_POST['status'] ?? 'scheduled'));
    $notes = trim((string) ($_POST['notes'] ?? ''));
    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    $allowedStatus = ['scheduled', 'completed', 'cancelled'];
    if (!in_array($status, $allowedStatus, true)) {
        $status = 'scheduled';
    }
    if ($doctorId <= 0 || $patientId <= 0 || $apptDt === '') {
        $flashErr = 'Doctor, patient and date/time are required.';
    } else {
        $apptDt = str_replace('T', ' ', $apptDt);
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $apptDt)) {
            $apptDt .= ':00';
        }
        $schedVal = $scheduleId > 0 ? $scheduleId : null;
        if ($id > 0) {
            $st = $pdo->prepare('UPDATE hms_appointments SET doctor_id=?, patient_id=?, schedule_id=?, appt_datetime=?, status=?, notes=? WHERE id=?');
            $st->execute([$doctorId, $patientId, $schedVal, $apptDt, $status, $notes, $id]);
            $_SESSION['flash_msg'] = 'Appointment updated.';
        } else {
            $st = $pdo->prepare('INSERT INTO hms_appointments (doctor_id, patient_id, schedule_id, appt_datetime, status, notes) VALUES (?,?,?,?,?,?)');
            $st->execute([$doctorId, $patientId, $schedVal, $apptDt, $status, $notes]);
            $_SESSION['flash_msg'] = 'Appointment added.';
        }
        header('Location: appointments.php');
        exit;
    }
}

$editRow = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $st = $pdo->prepare('SELECT * FROM hms_appointments WHERE id = ?');
    $st->execute([(int) $_GET['id']]);
    $editRow = $st->fetch();
    if (!$editRow) {
        header('Location: appointments.php');
        exit;
    }
}

$pageTitle = 'Appointments';
require __DIR__ . '/includes/header.php';

$sql = "SELECT a.*, d.name AS doctor_name, p.name AS patient_name,
        sch.day_of_week AS sch_day, sch.start_time AS sch_start
        FROM hms_appointments a
        JOIN hms_doctors d ON d.id = a.doctor_id
        JOIN hms_patients p ON p.id = a.patient_id
        LEFT JOIN hms_schedules sch ON sch.id = a.schedule_id
        ORDER BY a.appt_datetime DESC";
$rows = $pdo->query($sql)->fetchAll();
?>
<h1>Appointments</h1>

<?php if ($flashMsg): ?>
    <div class="flash flash-ok"><?= h($flashMsg) ?></div>
<?php endif; ?>
<?php if ($flashErr): ?>
    <div class="flash flash-err"><?= h($flashErr) ?></div>
<?php endif; ?>

<?php if ($action === 'new' || $action === 'edit'): ?>
<div class="card">
    <h2 style="margin-top:0;"><?= $action === 'edit' ? 'Edit appointment' : 'New appointment' ?></h2>
    <?php if (!$doctors || !$patients): ?>
        <p>You need at least one doctor and one patient. <a href="doctors.php">Doctors</a> · <a href="patients.php">Patients</a></p>
    <?php else: ?>
    <form method="post" action="">
        <?php if ($editRow): ?>
            <input type="hidden" name="id" value="<?= (int) $editRow['id'] ?>">
        <?php endif; ?>
        <div class="form-row">
            <label for="doctor_id">Doctor *</label>
            <select id="doctor_id" name="doctor_id" required>
                <?php foreach ($doctors as $d): ?>
                    <option value="<?= (int) $d['id'] ?>" <?= ($editRow && (int)$editRow['doctor_id'] === (int)$d['id']) ? 'selected' : '' ?>><?= h($d['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-row">
            <label for="patient_id">Patient *</label>
            <select id="patient_id" name="patient_id" required>
                <?php foreach ($patients as $p): ?>
                    <option value="<?= (int) $p['id'] ?>" <?= ($editRow && (int)$editRow['patient_id'] === (int)$p['id']) ? 'selected' : '' ?>><?= h($p['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-row">
            <label for="schedule_id">Schedule slot (optional)</label>
            <select id="schedule_id" name="schedule_id">
                <option value="0">— None —</option>
                <?php foreach ($schedules as $s): ?>
                    <option value="<?= (int) $s['id'] ?>" <?= ($editRow && (int)($editRow['schedule_id'] ?? 0) === (int)$s['id']) ? 'selected' : '' ?>>
                        <?= h($s['doctor_name']) ?>: <?= h($s['day_of_week']) ?> <?= h($s['start_time']) ?>–<?= h($s['end_time']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-row">
            <label for="appt_datetime">Date &amp; time *</label>
            <input type="datetime-local" id="appt_datetime" name="appt_datetime" required
                value="<?= h($editRow ? str_replace(' ', 'T', substr($editRow['appt_datetime'], 0, 16)) : '') ?>">
        </div>
        <div class="form-row">
            <label for="status">Status</label>
            <select id="status" name="status">
                <?php foreach (['scheduled', 'completed', 'cancelled'] as $st): ?>
                    <option value="<?= h($st) ?>" <?= ($editRow && ($editRow['status'] ?? '') === $st) ? 'selected' : '' ?>><?= h($st) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-row">
            <label for="notes">Notes</label>
            <textarea id="notes" name="notes"><?= h($editRow['notes'] ?? '') ?></textarea>
        </div>
        <button type="submit" class="btn">Save</button>
        <a class="btn btn-secondary" href="appointments.php">Cancel</a>
    </form>
    <?php endif; ?>
</div>
<?php else: ?>
<div class="card">
    <p><a class="btn" href="appointments.php?action=new">Add appointment</a></p>
    <table>
        <thead>
            <tr>
                <th>When</th>
                <th>Doctor</th>
                <th>Patient</th>
                <th>Schedule</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!$rows): ?>
                <tr><td colspan="6">No appointments yet.</td></tr>
            <?php else: foreach ($rows as $r): ?>
                <tr>
                    <td><?= h($r['appt_datetime']) ?></td>
                    <td><?= h($r['doctor_name']) ?></td>
                    <td><?= h($r['patient_name']) ?></td>
                    <td><?= $r['sch_day'] ? h($r['sch_day'] . ' ' . $r['sch_start']) : '—' ?></td>
                    <td><?= h($r['status']) ?></td>
                    <td class="actions">
                        <a href="appointments.php?action=edit&id=<?= (int) $r['id'] ?>">Edit</a>
                        <a href="appointments.php?action=delete&id=<?= (int) $r['id'] ?>" class="btn btn-danger btn-small" onclick="return confirm('Delete?');">Delete</a>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
