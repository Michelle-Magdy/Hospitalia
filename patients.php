<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_login();

$pdo = db();
$flashMsg = $_SESSION['flash_msg'] ?? '';
$flashErr = $_SESSION['flash_err'] ?? '';
unset($_SESSION['flash_msg'], $_SESSION['flash_err']);
$action = $_GET['action'] ?? 'list';

if ($action === 'delete' && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    $st = $pdo->prepare('DELETE FROM hms_patients WHERE id = ?');
    $st->execute([$id]);
    $_SESSION['flash_msg'] = 'Patient removed.';
    header('Location: patients.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string) ($_POST['name'] ?? ''));
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $notes = trim((string) ($_POST['notes'] ?? ''));
    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    if ($name === '') {
        $flashErr = 'Name is required.';
    } elseif ($id > 0) {
        $st = $pdo->prepare('UPDATE hms_patients SET name=?, phone=?, email=?, notes=? WHERE id=?');
        $st->execute([$name, $phone, $email, $notes, $id]);
        $_SESSION['flash_msg'] = 'Patient updated.';
        header('Location: patients.php');
        exit;
    } else {
        $st = $pdo->prepare('INSERT INTO hms_patients (name, phone, email, notes) VALUES (?,?,?,?)');
        $st->execute([$name, $phone, $email, $notes]);
        $_SESSION['flash_msg'] = 'Patient added.';
        header('Location: patients.php');
        exit;
    }
}

$editRow = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $st = $pdo->prepare('SELECT * FROM hms_patients WHERE id = ?');
    $st->execute([(int) $_GET['id']]);
    $editRow = $st->fetch();
    if (!$editRow) {
        header('Location: patients.php');
        exit;
    }
}

$pageTitle = 'Patients';
require __DIR__ . '/includes/header.php';

$rows = $pdo->query('SELECT * FROM hms_patients ORDER BY name')->fetchAll();
?>
<h1>Patients</h1>

<?php if ($flashMsg): ?>
    <div class="flash flash-ok"><?= h($flashMsg) ?></div>
<?php endif; ?>
<?php if ($flashErr): ?>
    <div class="flash flash-err"><?= h($flashErr) ?></div>
<?php endif; ?>

<?php if ($action === 'new' || $action === 'edit'): ?>
<div class="card">
    <h2 style="margin-top:0;"><?= $action === 'edit' ? 'Edit patient' : 'New patient' ?></h2>
    <form method="post" action="">
        <?php if ($editRow): ?>
            <input type="hidden" name="id" value="<?= (int) $editRow['id'] ?>">
        <?php endif; ?>
        <div class="form-row">
            <label for="name">Name *</label>
            <input type="text" id="name" name="name" required value="<?= h($editRow['name'] ?? '') ?>">
        </div>
        <div class="form-row">
            <label for="phone">Phone</label>
            <input type="text" id="phone" name="phone" value="<?= h($editRow['phone'] ?? '') ?>">
        </div>
        <div class="form-row">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= h($editRow['email'] ?? '') ?>">
        </div>
        <div class="form-row">
            <label for="notes">Notes</label>
            <textarea id="notes" name="notes"><?= h($editRow['notes'] ?? '') ?></textarea>
        </div>
        <button type="submit" class="btn">Save</button>
        <a class="btn btn-secondary" href="patients.php">Cancel</a>
    </form>
</div>
<?php else: ?>
<div class="card">
    <p><a class="btn" href="patients.php?action=new">Add patient</a></p>
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Phone</th>
                <th>Email</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!$rows): ?>
                <tr><td colspan="4">No patients yet.</td></tr>
            <?php else: foreach ($rows as $r): ?>
                <tr>
                    <td><?= h($r['name']) ?></td>
                    <td><?= h($r['phone']) ?></td>
                    <td><?= h($r['email']) ?></td>
                    <td class="actions">
                        <a href="patients.php?action=edit&id=<?= (int) $r['id'] ?>">Edit</a>
                        <a href="patients.php?action=delete&id=<?= (int) $r['id'] ?>" class="btn btn-danger btn-small" onclick="return confirm('Delete this patient?');">Delete</a>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
