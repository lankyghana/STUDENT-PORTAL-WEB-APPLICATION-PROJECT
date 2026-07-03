<?php
require __DIR__ . '/db.php';

$id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT);
if ($id === false) {
    header('Location: dashboard.php');
    exit;
}

$db = get_db();

// ---- Handle admission status update (checkbox submit) ----
$justUpdated = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newStatus = isset($_POST['admitted']) ? 'Admitted' : 'Undecided';
    $stmt = $db->prepare("UPDATE students SET admission_status = ? WHERE id = ?");
    $stmt->execute([$newStatus, $id]);
    $justUpdated = true;
}

// ---- Fetch the student ----
$stmt = $db->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$id]);
$s = $stmt->fetch();

if (!$s) {
    header('Location: dashboard.php');
    exit;
}

$fullName = trim($s['first_name'] . ' ' . ($s['middle_name'] ? $s['middle_name'] . ' ' : '') . $s['last_name']);
$initials = strtoupper(substr($s['first_name'], 0, 1) . substr($s['last_name'], 0, 1));
$isAdmitted = $s['admission_status'] === 'Admitted';

$details = [
    'First Name'        => $s['first_name'],
    'Middle Name'       => $s['middle_name'] ?: '—',
    'Last Name'         => $s['last_name'],
    'Email'             => $s['email'],
    'Date of Birth'     => $s['date_of_birth'],
    'Gender'            => $s['gender'],
    'Phone Number'      => $s['phone_number'],
    'Address'           => $s['address'],
    'State of Origin'   => $s['state_of_origin'],
    'Local Government'  => $s['local_government'],
    'Next of Kin'       => $s['next_of_kin'],
    'JAMB Score'        => $s['jamb_score'] . ' / 400',
    'Registered On'     => $s['created_at'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Portal — <?= e($fullName) ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header class="navbar">
    <div class="brand"><span class="logo">🎓</span> Student Portal</div>
    <nav>
        <a href="index.php">Home</a>
        <a href="form.php">Register</a>
        <a href="dashboard.php">Dashboard</a>
    </nav>
</header>

<div class="container">
    <p style="margin-bottom: 1rem;"><a href="dashboard.php">← Back to Dashboard</a></p>

    <?php if ($justUpdated): ?>
        <div class="alert alert-success">✅ Admission status updated to <strong><?= e($s['admission_status']) ?></strong>.</div>
    <?php endif; ?>

    <div class="card">
        <div class="profile-header">
            <?php if ($s['profile_image'] && file_exists(__DIR__ . '/' . $s['profile_image'])): ?>
                <img src="<?= e($s['profile_image']) ?>" alt="<?= e($fullName) ?>">
            <?php else: ?>
                <div class="avatar-lg"><?= e($initials) ?></div>
            <?php endif; ?>
            <div>
                <h2><?= e($fullName) ?></h2>
                <div class="meta"><?= e($s['email']) ?> &nbsp;·&nbsp; <?= e($s['phone_number']) ?></div>
                <div style="margin-top: 0.4rem;">
                    <span class="badge <?= $isAdmitted ? 'badge-admitted' : 'badge-undecided' ?>">
                        <?= e($s['admission_status']) ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="detail-grid">
            <?php foreach ($details as $label => $value): ?>
                <div class="detail-item">
                    <div class="label"><?= e($label) ?></div>
                    <div class="value"><?= e((string) $value) ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <form method="post" class="admission-box">
            <label>
                <input type="checkbox" name="admitted" value="1" <?= $isAdmitted ? 'checked' : '' ?>>
                Admitted &nbsp;<span style="color: var(--muted); font-weight: 400;">(unchecked = Undecided)</span>
            </label>
            <button type="submit" class="btn btn-primary btn-sm">Save Admission Status</button>
        </form>
    </div>
</div>

<footer class="footer">
    &copy; <?= date('Y') ?> Student Portal — One Million Coders Fullstack Project
</footer>

</body>
</html>
