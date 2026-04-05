<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_login();

$pdo = db();
$flashMsg = $_SESSION['flash_msg'] ?? '';
$flashErr = $_SESSION['flash_err'] ?? '';
unset($_SESSION['flash_msg'], $_SESSION['flash_err']);
$action = $_GET['action'] ?? 'list';

$patients = $pdo->query('SELECT id, name FROM hms_patients ORDER BY name')->fetchAll();
$appointments = $pdo->query("
    SELECT a.id, a.appt_datetime, p.name AS patient_name, d.name AS doctor_name
    FROM hms_appointments a
    JOIN hms_patients p ON p.id = a.patient_id
    JOIN hms_doctors d ON d.id = a.doctor_id
    ORDER BY a.appt_datetime DESC
")->fetchAll();

if ($action === 'delete' && isset($_GET['id'])) {
    $st = $pdo->prepare('DELETE FROM hms_bills WHERE id = ?');
    $st->execute([(int) $_GET['id']]);
    $_SESSION['flash_msg'] = 'Bill removed.';
    header('Location: bills.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patientId = (int) ($_POST['patient_id'] ?? 0);
    $appointmentId = (int) ($_POST['appointment_id'] ?? 0);
    $amount = (float) str_replace(',', '.', (string) ($_POST['amount'] ?? '0'));
    $description = trim((string) ($_POST['description'] ?? ''));
    $paid = isset($_POST['paid']) ? 1 : 0;
    $billDate = trim((string) ($_POST['bill_date'] ?? ''));
    if ($billDate === '') {
        $billDate = date('Y-m-d');
    }
    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    if ($patientId <= 0 || $amount < 0) {
        $flashErr = 'Patient and a valid amount are required.';
    } else {
        $apptVal = $appointmentId > 0 ? $appointmentId : null;
        if ($id > 0) {
            $st = $pdo->prepare('UPDATE hms_bills SET patient_id=?, appointment_id=?, amount=?, description=?, paid=?, bill_date=? WHERE id=?');
            $st->execute([$patientId, $apptVal, $amount, $description, $paid, $billDate, $id]);
            $_SESSION['flash_msg'] = 'Bill updated.';
        } else {
            $st = $pdo->prepare('INSERT INTO hms_bills (patient_id, appointment_id, amount, description, paid, bill_date) VALUES (?,?,?,?,?,?)');
            $st->execute([$patientId, $apptVal, $amount, $description, $paid, $billDate]);
            $_SESSION['flash_msg'] = 'Bill added.';
        }
        header('Location: bills.php');
        exit;
    }
}

$editRow = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $st = $pdo->prepare('SELECT * FROM hms_bills WHERE id = ?');
    $st->execute([(int) $_GET['id']]);
    $editRow = $st->fetch();
    if (!$editRow) {
        header('Location: bills.php');
        exit;
    }
}

$pageTitle = 'Bills';
require __DIR__ . '/includes/header.php';

$sql = "SELECT b.*, p.name AS patient_name,
        a.appt_datetime AS appt_dt
        FROM hms_bills b
        JOIN hms_patients p ON p.id = b.patient_id
        LEFT JOIN hms_appointments a ON a.id = b.appointment_id
        ORDER BY b.bill_date DESC, b.id DESC";
$rows = $pdo->query($sql)->fetchAll();
?>
<h1>Bills</h1>

<?php if ($flashMsg): ?>
    <div class="flash flash-ok"><?= h($flashMsg) ?></div>
<?php endif; ?>
<?php if ($flashErr): ?>
    <div class="flash flash-err"><?= h($flashErr) ?></div>
<?php endif; ?>

<?php if ($action === 'new' || $action === 'edit'): ?>
<div class="card">
    <h2 style="margin-top:0;"><?= $action === 'edit' ? 'Edit bill' : 'New bill' ?></h2>
    <?php if (!$patients): ?>
        <p>Add patients first: <a href="patients.php">Patients</a></p>
    <?php else: ?>
    <form method="post" action="">
        <?php if ($editRow): ?>
            <input type="hidden" name="id" value="<?= (int) $editRow['id'] ?>">
        <?php endif; ?>
        <div class="form-row">
            <label for="patient_id">Patient *</label>
            <select id="patient_id" name="patient_id" required>
                <?php foreach ($patients as $p): ?>
                    <option value="<?= (int) $p['id'] ?>" <?= ($editRow && (int)$editRow['patient_id'] === (int)$p['id']) ? 'selected' : '' ?>><?= h($p['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-row">
            <label for="appointment_id">Appointment (optional)</label>
            <select id="appointment_id" name="appointment_id">
                <option value="0">— None —</option>
                <?php foreach ($appointments as $a): ?>
                    <option value="<?= (int) $a['id'] ?>" <?= ($editRow && (int)($editRow['appointment_id'] ?? 0) === (int)$a['id']) ? 'selected' : '' ?>>
                        <?= h($a['appt_datetime']) ?> — <?= h($a['patient_name']) ?> / <?= h($a['doctor_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-row">
            <label for="amount">Amount *</label>
            <input type="number" step="0.01" min="0" id="amount" name="amount" required value="<?= h($editRow ? (string)$editRow['amount'] : '') ?>">
        </div>
        <div class="form-row">
            <label for="description">Description</label>
            <textarea id="description" name="description"><?= h($editRow['description'] ?? '') ?></textarea>
        </div>
        <div class="form-row">
            <label for="bill_date">Bill date</label>
            <input type="date" id="bill_date" name="bill_date" value="<?= h($editRow['bill_date'] ?? date('Y-m-d')) ?>">
        </div>
        <div class="form-row">
            <label><input type="checkbox" name="paid" value="1" <?= ($editRow && (int)$editRow['paid'] === 1) ? 'checked' : '' ?>> Paid</label>
        </div>
        <button type="submit" class="btn">Save</button>
        <a class="btn btn-secondary" href="bills.php">Cancel</a>
    </form>
    <?php endif; ?>
</div>
<?php else: ?>
<div class="card">
    <p><a class="btn" href="bills.php?action=new">Add bill</a></p>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Patient</th>
                <th>Amount</th>
                <th>Paid</th>
                <th>Appointment</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!$rows): ?>
                <tr><td colspan="6">No bills yet.</td></tr>
            <?php else: foreach ($rows as $r): ?>
                <tr>
                    <td><?= h($r['bill_date']) ?></td>
                    <td><?= h($r['patient_name']) ?></td>
                    <td><?= number_format((float)$r['amount'], 2) ?></td>
                    <td><?= (int)$r['paid'] === 1 ? 'Yes' : 'No' ?></td>
                    <td><?= $r['appt_dt'] ? h($r['appt_dt']) : '—' ?></td>
                    <td class="actions">
                        <a href="bills.php?action=edit&id=<?= (int) $r['id'] ?>">Edit</a>
                        <a href="bills.php?action=delete&id=<?= (int) $r['id'] ?>" class="btn btn-danger btn-small" onclick="return confirm('Delete?');">Delete</a>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
