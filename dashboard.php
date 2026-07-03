<?php
require __DIR__ . '/db.php';

// ---- Read filter values from the query string ----
$filterName   = trim($_GET['name'] ?? '');
$filterStatus = $_GET['status'] ?? '';
$filterGender = $_GET['gender'] ?? '';
$filterScore  = trim($_GET['jamb_score'] ?? '');

// ---- Build the query dynamically based on active filters ----
$sql    = "SELECT * FROM students WHERE 1=1";
$params = [];

if ($filterName !== '') {
    $sql .= " AND (first_name LIKE :name OR middle_name LIKE :name OR last_name LIKE :name
              OR (first_name || ' ' || last_name) LIKE :name
              OR (first_name || ' ' || middle_name || ' ' || last_name) LIKE :name)";
    $params[':name'] = '%' . $filterName . '%';
}

if (in_array($filterStatus, ['Admitted', 'Undecided'], true)) {
    $sql .= " AND admission_status = :status";
    $params[':status'] = $filterStatus;
}

if (in_array($filterGender, ['Male', 'Female'], true)) {
    $sql .= " AND gender = :gender";
    $params[':gender'] = $filterGender;
}

if ($filterScore !== '' && is_numeric($filterScore)) {
    $sql .= " AND jamb_score >= :score";
    $params[':score'] = (int) $filterScore;
}

$sql .= " ORDER BY created_at DESC, id DESC";

$stmt = get_db()->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll();

$hasFilters = $filterName !== '' || $filterStatus !== '' || $filterGender !== '' || $filterScore !== '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Portal — Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header class="navbar">
    <div class="brand"><span class="logo">🎓</span> Student Portal</div>
    <nav>
        <a href="index.php">Home</a>
        <a href="form.php">Register</a>
        <a href="dashboard.php" class="active">Dashboard</a>
    </nav>
</header>

<div class="container">
    <h1 class="page-title">Student Records</h1>
    <p class="page-subtitle">
        <?= count($students) ?> record<?= count($students) === 1 ? '' : 's' ?>
        <?= $hasFilters ? 'matching your filters' : 'in total' ?>
    </p>

    <?php if (isset($_GET['updated'])): ?>
        <div class="alert alert-success">✅ Admission status updated.</div>
    <?php endif; ?>

    <!-- ==================== Filters ==================== -->
    <form method="get" class="filter-bar">
        <div class="form-group">
            <label for="name">Filter by Name</label>
            <input type="text" id="name" name="name" value="<?= e($filterName) ?>" placeholder="e.g. Adaeze">
        </div>

        <div class="form-group">
            <label for="status">Admission Status</label>
            <select id="status" name="status">
                <option value="">All statuses</option>
                <?php foreach (['Admitted', 'Undecided'] as $s): ?>
                    <option value="<?= $s ?>" <?= $filterStatus === $s ? 'selected' : '' ?>><?= $s ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="gender">Gender</label>
            <select id="gender" name="gender">
                <option value="">All genders</option>
                <?php foreach (['Male', 'Female'] as $g): ?>
                    <option value="<?= $g ?>" <?= $filterGender === $g ? 'selected' : '' ?>><?= $g ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="jamb_score">Min. JAMB Score</label>
            <input type="number" id="jamb_score" name="jamb_score" min="0" max="400" value="<?= e($filterScore) ?>" placeholder="e.g. 200">
        </div>

        <button type="submit" class="btn btn-primary btn-sm" style="margin-bottom: 2px;">Apply Filters</button>
        <?php if ($hasFilters): ?>
            <a href="dashboard.php" class="btn btn-outline btn-sm" style="margin-bottom: 2px;">Clear</a>
        <?php endif; ?>
    </form>

    <!-- ==================== Records table ==================== -->
    <?php if ($students): ?>
        <table class="records">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone Number</th>
                    <th>Gender</th>
                    <th>State of Origin</th>
                    <th>JAMB Score</th>
                    <th>Admission Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $s): ?>
                    <?php
                        $fullName = trim($s['first_name'] . ' ' . ($s['middle_name'] ? $s['middle_name'] . ' ' : '') . $s['last_name']);
                        $initials = strtoupper(substr($s['first_name'], 0, 1) . substr($s['last_name'], 0, 1));
                    ?>
                    <tr>
                        <td>
                            <?php if ($s['profile_image'] && file_exists(__DIR__ . '/' . $s['profile_image'])): ?>
                                <img class="avatar-sm" src="<?= e($s['profile_image']) ?>" alt="<?= e($fullName) ?>">
                            <?php else: ?>
                                <span class="avatar-placeholder"><?= e($initials) ?></span>
                            <?php endif; ?>
                            <?= e($fullName) ?>
                        </td>
                        <td><?= e($s['email']) ?></td>
                        <td><?= e($s['phone_number']) ?></td>
                        <td><?= e($s['gender']) ?></td>
                        <td><?= e($s['state_of_origin']) ?></td>
                        <td><?= e((string) $s['jamb_score']) ?></td>
                        <td>
                            <span class="badge <?= $s['admission_status'] === 'Admitted' ? 'badge-admitted' : 'badge-undecided' ?>">
                                <?= e($s['admission_status']) ?>
                            </span>
                        </td>
                        <td>
                            <a href="view.php?id=<?= (int) $s['id'] ?>" class="btn btn-primary btn-sm">View</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="card empty-state">
            <div class="icon">📭</div>
            <?php if ($hasFilters): ?>
                <p>No records match your filters. <a href="dashboard.php">Clear filters</a></p>
            <?php else: ?>
                <p>No student records yet. <a href="form.php">Register the first student →</a></p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<footer class="footer">
    &copy; <?= date('Y') ?> Student Portal — One Million Coders Fullstack Project
</footer>

</body>
</html>
