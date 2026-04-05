<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_admin();

$pdo = db();
$flashMsg = $_SESSION['flash_msg'] ?? '';
$flashErr = $_SESSION['flash_err'] ?? '';
unset($_SESSION['flash_msg'], $_SESSION['flash_err']);
$action = $_GET['action'] ?? 'list';

if ($action === 'delete' && isset($_GET['id'])) {
    $st = $pdo->prepare('DELETE FROM hms_doctors WHERE id = ?');
    $st->execute([(int) $_GET['id']]);
    $_SESSION['flash_msg'] = 'Doctor removed.';
    header('Location: doctors.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string) ($_POST['name'] ?? ''));
    $specialty = trim((string) ($_POST['specialty'] ?? ''));
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    if ($name === '') {
        $flashErr = 'Name is required.';
    } elseif ($id > 0) {
        $st = $pdo->prepare('UPDATE hms_doctors SET name=?, specialty=?, phone=? WHERE id=?');
        $st->execute([$name, $specialty, $phone, $id]);
        $_SESSION['flash_msg'] = 'Doctor updated.';
        header('Location: doctors.php');
        exit;
    } else {
        $st = $pdo->prepare('INSERT INTO hms_doctors (name, specialty, phone) VALUES (?,?,?)');
        $st->execute([$name, $specialty, $phone]);
        $_SESSION['flash_msg'] = 'Doctor added.';
        header('Location: doctors.php');
        exit;
    }
}

$editRow = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $st = $pdo->prepare('SELECT * FROM hms_doctors WHERE id = ?');
    $st->execute([(int) $_GET['id']]);
    $editRow = $st->fetch();
    if (!$editRow) {
        header('Location: doctors.php');
        exit;
    }
}

$pageTitle = 'Doctors';
require __DIR__ . '/includes/header.php';

$rows = $pdo->query('SELECT * FROM hms_doctors ORDER BY name')->fetchAll();
?>
<h1>Doctors</h1>

<?php if ($flashMsg): ?>
    <div class="flash flash-ok"><?= h($flashMsg) ?></div>
<?php endif; ?>
<?php if ($flashErr): ?>
    <div class="flash flash-err"><?= h($flashErr) ?></div>
<?php endif; ?>

<?php if ($action === 'new' || $action === 'edit'): ?>
<div class="card">
    <h2 style="margin-top:0;"><?= $action === 'edit' ? 'Edit doctor' : 'New doctor' ?></h2>
    <form method="post" action="">
        <?php if ($editRow): ?>
            <input type="hidden" name="id" value="<?= (int) $editRow['id'] ?>">
        <?php endif; ?>
        <div class="form-row">
            <label for="name">Name *</label>
            <input type="text" id="name" name="name" required value="<?= h($editRow['name'] ?? '') ?>">
        </div>
        <div class="form-row">
            <label for="specialty">Specialty</label>
            <input type="text" id="specialty" name="specialty" value="<?= h($editRow['specialty'] ?? '') ?>">
        </div>
        <div class="form-row">
            <label for="phone">Phone</label>
            <input type="text" id="phone" name="phone" value="<?= h($editRow['phone'] ?? '') ?>">
        </div>
        <button type="submit" class="btn">Save</button>
        <a class="btn btn-secondary" href="doctors.php">Cancel</a>
    </form>
</div>
<?php else: ?>
<div class="card">
    <p><a class="btn" href="doctors.php?action=new">Add doctor</a></p>
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Specialty</th>
                <th>Phone</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!$rows): ?>
                <tr><td colspan="4">No doctors yet.</td></tr>
            <?php else: foreach ($rows as $r): ?>
                <tr>
                    <td><?= h($r['name']) ?></td>
                    <td><?= h($r['specialty']) ?></td>
                    <td><?= h($r['phone']) ?></td>
                    <td class="actions">
                        <a href="doctors.php?action=edit&id=<?= (int) $r['id'] ?>">Edit</a>
                        <a href="doctors.php?action=delete&id=<?= (int) $r['id'] ?>" class="btn btn-danger btn-small" onclick="return confirm('Delete? Related schedules may be removed.');">Delete</a>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
