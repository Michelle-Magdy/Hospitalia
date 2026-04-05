<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_login();

$pdo = db();
$flashMsg = $_SESSION['flash_msg'] ?? '';
$flashErr = $_SESSION['flash_err'] ?? '';
unset($_SESSION['flash_msg'], $_SESSION['flash_err']);
$action = $_GET['action'] ?? 'list';

$doctors = $pdo->query('SELECT id, name, specialty FROM hms_doctors ORDER BY name')->fetchAll();

if ($action === 'delete' && isset($_GET['id'])) {
    $st = $pdo->prepare('DELETE FROM hms_schedules WHERE id = ?');
    $st->execute([(int) $_GET['id']]);
    $_SESSION['flash_msg'] = 'Schedule removed.';
    header('Location: schedules.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $doctorId = (int) ($_POST['doctor_id'] ?? 0);
    $day = trim((string) ($_POST['day_of_week'] ?? ''));
    $start = trim((string) ($_POST['start_time'] ?? ''));
    $end = trim((string) ($_POST['end_time'] ?? ''));
    $notes = trim((string) ($_POST['notes'] ?? ''));
    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    if ($doctorId <= 0 || $day === '' || $start === '' || $end === '') {
        $flashErr = 'Doctor, day, start and end time are required.';
    } elseif (!is_admin() && count($doctors) === 0) {
        $flashErr = 'No doctors in the system yet. An admin must add doctors first.';
    } else {
        if ($id > 0) {
            $st = $pdo->prepare('UPDATE hms_schedules SET doctor_id=?, day_of_week=?, start_time=?, end_time=?, notes=? WHERE id=?');
            $st->execute([$doctorId, $day, $start, $end, $notes, $id]);
            $_SESSION['flash_msg'] = 'Schedule updated.';
        } else {
            $st = $pdo->prepare('INSERT INTO hms_schedules (doctor_id, day_of_week, start_time, end_time, notes) VALUES (?,?,?,?,?)');
            $st->execute([$doctorId, $day, $start, $end, $notes]);
            $_SESSION['flash_msg'] = 'Schedule added.';
        }
        header('Location: schedules.php');
        exit;
    }
}

$editRow = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $st = $pdo->prepare('SELECT * FROM hms_schedules WHERE id = ?');
    $st->execute([(int) $_GET['id']]);
    $editRow = $st->fetch();
    if (!$editRow) {
        header('Location: schedules.php');
        exit;
    }
}

$pageTitle = 'Schedules';
require __DIR__ . '/includes/header.php';

$sql = 'SELECT s.*, d.name AS doctor_name FROM hms_schedules s JOIN hms_doctors d ON d.id = s.doctor_id ORDER BY d.name, s.day_of_week, s.start_time';
$rows = $pdo->query($sql)->fetchAll();

$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
?>
<h1>Doctor schedules</h1>
<p style="color:#555;">Weekly availability slots for each doctor. Appointments can be linked to a schedule.</p>

<?php if ($flashMsg): ?>
    <div class="flash flash-ok"><?= h($flashMsg) ?></div>
<?php endif; ?>
<?php if ($flashErr): ?>
    <div class="flash flash-err"><?= h($flashErr) ?></div>
<?php endif; ?>

<?php if (count($doctors) === 0): ?>
    <div class="flash flash-err">There are no doctors yet. An admin must add doctors before schedules can be created.</div>
<?php endif; ?>

<?php if (($action === 'new' || $action === 'edit') && count($doctors) > 0): ?>
<div class="card">
    <h2 style="margin-top:0;"><?= $action === 'edit' ? 'Edit schedule' : 'New schedule' ?></h2>
    <form method="post" action="">
        <?php if ($editRow): ?>
            <input type="hidden" name="id" value="<?= (int) $editRow['id'] ?>">
        <?php endif; ?>
        <div class="form-row">
            <label for="doctor_id">Doctor *</label>
            <select id="doctor_id" name="doctor_id" required>
                <?php foreach ($doctors as $d): ?>
                    <option value="<?= (int) $d['id'] ?>" <?= ($editRow && (int)$editRow['doctor_id'] === (int)$d['id']) ? 'selected' : '' ?>>
                        <?= h($d['name']) ?><?= $d['specialty'] ? ' — ' . h($d['specialty']) : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-row">
            <label for="day_of_week">Day *</label>
            <select id="day_of_week" name="day_of_week" required>
                <?php foreach ($days as $d): ?>
                    <option value="<?= h($d) ?>" <?= ($editRow && ($editRow['day_of_week'] ?? '') === $d) ? 'selected' : '' ?>><?= h($d) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-row">
            <label for="start_time">Start time *</label>
            <input type="time" id="start_time" name="start_time" required value="<?= h($editRow['start_time'] ?? '') ?>">
        </div>
        <div class="form-row">
            <label for="end_time">End time *</label>
            <input type="time" id="end_time" name="end_time" required value="<?= h($editRow['end_time'] ?? '') ?>">
        </div>
        <div class="form-row">
            <label for="notes">Notes</label>
            <textarea id="notes" name="notes"><?= h($editRow['notes'] ?? '') ?></textarea>
        </div>
        <button type="submit" class="btn">Save</button>
        <a class="btn btn-secondary" href="schedules.php">Cancel</a>
    </form>
</div>
<?php elseif ($action === 'new' || $action === 'edit'): ?>
<div class="card">
    <p>No doctors in the system yet. An admin must add doctors first.</p>
    <a class="btn btn-secondary" href="schedules.php">Back to list</a>
</div>
<?php else: ?>
<div class="card">
    <?php if (count($doctors) > 0): ?>
        <p><a class="btn" href="schedules.php?action=new">Add schedule</a></p>
    <?php endif; ?>
    <table>
        <thead>
            <tr>
                <th>Doctor</th>
                <th>Day</th>
                <th>From</th>
                <th>To</th>
                <th>Notes</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!$rows): ?>
                <tr><td colspan="6">No schedules yet.</td></tr>
            <?php else: foreach ($rows as $r): ?>
                <tr>
                    <td><?= h($r['doctor_name']) ?></td>
                    <td><?= h($r['day_of_week']) ?></td>
                    <td><?= h($r['start_time']) ?></td>
                    <td><?= h($r['end_time']) ?></td>
                    <td><?= h($r['notes']) ?></td>
                    <td class="actions">
                        <a href="schedules.php?action=edit&id=<?= (int) $r['id'] ?>">Edit</a>
                        <a href="schedules.php?action=delete&id=<?= (int) $r['id'] ?>" class="btn btn-danger btn-small" onclick="return confirm('Delete?');">Delete</a>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
