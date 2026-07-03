<?php
require __DIR__ . '/db.php';

// States and their local governments, loaded from the project JSON file.
$statesLga = [];
foreach (json_decode(file_get_contents(__DIR__ . '/states_lga.json'), true)['states'] ?? [] as $entry) {
    $statesLga[$entry['state']] = $entry['local'];
}
$states = array_keys($statesLga);

$errors = [];
$old = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = $_POST;

    // --- Validate required text fields ---
    $required = [
        'first_name'       => 'First name',
        'last_name'        => 'Last name',
        'email'            => 'Email',
        'date_of_birth'    => 'Date of birth',
        'gender'           => 'Gender',
        'phone_number'     => 'Phone number',
        'address'          => 'Address',
        'state_of_origin'  => 'State of origin',
        'local_government' => 'Local government',
        'next_of_kin'      => 'Next of kin',
        'jamb_score'       => 'JAMB score',
    ];

    foreach ($required as $field => $label) {
        if (trim($_POST[$field] ?? '') === '') {
            $errors[] = "$label is required.";
        }
    }

    if (!empty($_POST['email']) && !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    $jamb = filter_var($_POST['jamb_score'] ?? '', FILTER_VALIDATE_INT);
    if ($jamb === false || $jamb < 0 || $jamb > 400) {
        $errors[] = 'JAMB score must be a number between 0 and 400.';
    }

    if (!empty($_POST['state_of_origin'])) {
        if (!isset($statesLga[$_POST['state_of_origin']])) {
            $errors[] = 'Please select a valid state of origin.';
        } elseif (!empty($_POST['local_government'])
                  && !in_array($_POST['local_government'], $statesLga[$_POST['state_of_origin']], true)) {
            $errors[] = 'Please select a local government that belongs to the chosen state.';
        }
    }

    // --- Handle profile image upload (optional) ---
    $imagePath = null;
    if (!empty($_FILES['profile_image']['name'])) {
        $file = $_FILES['profile_image'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Profile image failed to upload. Please try again.';
        } else {
            $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
            $mime = mime_content_type($file['tmp_name']);

            if (!isset($allowed[$mime])) {
                $errors[] = 'Profile image must be a JPG, PNG, GIF or WEBP file.';
            } elseif ($file['size'] > 2 * 1024 * 1024) {
                $errors[] = 'Profile image must not be larger than 2 MB.';
            } else {
                if (!is_dir(__DIR__ . '/uploads')) {
                    mkdir(__DIR__ . '/uploads', 0777, true);
                }
                $imagePath = 'uploads/' . uniqid('student_') . '.' . $allowed[$mime];
                if (!move_uploaded_file($file['tmp_name'], __DIR__ . '/' . $imagePath)) {
                    $errors[] = 'Could not save the uploaded image.';
                    $imagePath = null;
                }
            }
        }
    }

    // --- Save to database ---
    if (!$errors) {
        $stmt = get_db()->prepare("
            INSERT INTO students
                (profile_image, first_name, middle_name, last_name, email,
                 date_of_birth, gender, phone_number, address,
                 state_of_origin, local_government, next_of_kin, jamb_score)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $imagePath,
            trim($_POST['first_name']),
            trim($_POST['middle_name'] ?? ''),
            trim($_POST['last_name']),
            trim($_POST['email']),
            $_POST['date_of_birth'],
            $_POST['gender'],
            trim($_POST['phone_number']),
            trim($_POST['address']),
            $_POST['state_of_origin'],
            trim($_POST['local_government']),
            trim($_POST['next_of_kin']),
            $jamb,
        ]);

        header('Location: form.php?submitted=1');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Portal — Registration Form</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header class="navbar">
    <div class="brand"><span class="logo">🎓</span> Student Portal</div>
    <nav>
        <a href="index.php">Home</a>
        <a href="form.php" class="active">Register</a>
        <a href="dashboard.php">Dashboard</a>
    </nav>
</header>

<div class="container">
    <div class="card">
        <h1 class="page-title">Student Registration Form</h1>
        <p class="page-subtitle">Fields marked <span style="color:#dc2626">*</span> are required.</p>

        <?php if (isset($_GET['submitted'])): ?>
            <div class="alert alert-success">
                ✅ Registration submitted successfully!
                <a href="dashboard.php">View it on the dashboard →</a>
            </div>
        <?php endif; ?>

        <?php if ($errors): ?>
            <div class="alert alert-error">
                <strong>Please fix the following:</strong>
                <ul style="margin: 0.3rem 0 0 1.2rem;">
                    <?php foreach ($errors as $err): ?>
                        <li><?= e($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data" class="form-grid">

            <div class="form-group full">
                <label for="profile_image">Profile Image (JPG/PNG/GIF/WEBP, max 2 MB)</label>
                <input type="file" id="profile_image" name="profile_image" accept="image/*">
            </div>

            <div class="form-group">
                <label for="first_name">First Name <span class="req">*</span></label>
                <input type="text" id="first_name" name="first_name" value="<?= e($old['first_name'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label for="middle_name">Middle Name</label>
                <input type="text" id="middle_name" name="middle_name" value="<?= e($old['middle_name'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="last_name">Last Name <span class="req">*</span></label>
                <input type="text" id="last_name" name="last_name" value="<?= e($old['last_name'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label for="email">Email <span class="req">*</span></label>
                <input type="email" id="email" name="email" value="<?= e($old['email'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label for="date_of_birth">Date of Birth <span class="req">*</span></label>
                <input type="date" id="date_of_birth" name="date_of_birth" value="<?= e($old['date_of_birth'] ?? '') ?>" max="<?= date('Y-m-d') ?>" required>
            </div>

            <div class="form-group">
                <label for="gender">Gender <span class="req">*</span></label>
                <select id="gender" name="gender" required>
                    <option value="">— Select gender —</option>
                    <?php foreach (['Male', 'Female'] as $g): ?>
                        <option value="<?= $g ?>" <?= ($old['gender'] ?? '') === $g ? 'selected' : '' ?>><?= $g ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="phone_number">Phone Number <span class="req">*</span></label>
                <input type="tel" id="phone_number" name="phone_number" value="<?= e($old['phone_number'] ?? '') ?>" placeholder="e.g. 08012345678" required>
            </div>

            <div class="form-group">
                <label for="jamb_score">JAMB Score (0 – 400) <span class="req">*</span></label>
                <input type="number" id="jamb_score" name="jamb_score" min="0" max="400" value="<?= e($old['jamb_score'] ?? '') ?>" required>
            </div>

            <div class="form-group full">
                <label for="address">Address <span class="req">*</span></label>
                <textarea id="address" name="address" rows="2" required><?= e($old['address'] ?? '') ?></textarea>
            </div>

            <div class="form-group">
                <label for="state_of_origin">State of Origin <span class="req">*</span></label>
                <select id="state_of_origin" name="state_of_origin" required>
                    <option value="">— Select state —</option>
                    <?php foreach ($states as $s): ?>
                        <option value="<?= $s ?>" <?= ($old['state_of_origin'] ?? '') === $s ? 'selected' : '' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="local_government">Local Government <span class="req">*</span></label>
                <select id="local_government" name="local_government" required>
                    <option value="">— Select state first —</option>
                </select>
            </div>

            <div class="form-group full">
                <label for="next_of_kin">Next of Kin <span class="req">*</span></label>
                <input type="text" id="next_of_kin" name="next_of_kin" value="<?= e($old['next_of_kin'] ?? '') ?>" required>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Submit Registration</button>
                <a href="index.php" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>

<footer class="footer">
    &copy; <?= date('Y') ?> Student Portal — One Million Coders Fullstack Project
</footer>

<script>
// Populate the Local Government dropdown based on the selected state.
const STATES_LGA = <?= json_encode($statesLga) ?>;
const stateSelect = document.getElementById('state_of_origin');
const lgaSelect = document.getElementById('local_government');
const previousLga = <?= json_encode($old['local_government'] ?? '') ?>;

function populateLgas() {
    const lgas = STATES_LGA[stateSelect.value] || [];
    lgaSelect.innerHTML = '';
    const placeholder = document.createElement('option');
    placeholder.value = '';
    placeholder.textContent = lgas.length ? '— Select local government —' : '— Select state first —';
    lgaSelect.appendChild(placeholder);
    for (const lga of lgas) {
        const opt = document.createElement('option');
        opt.value = lga;
        opt.textContent = lga;
        if (lga === previousLga) opt.selected = true;
        lgaSelect.appendChild(opt);
    }
}

stateSelect.addEventListener('change', populateLgas);
populateLgas();
</script>

</body>
</html>
