<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Portal — Home</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header class="navbar">
    <div class="brand"><span class="logo">🎓</span> Student Portal</div>
    <nav>
        <a href="index.php" class="active">Home</a>
        <a href="form.php">Register</a>
        <a href="dashboard.php">Dashboard</a>
    </nav>
</header>

<section class="hero">
    <div>
        <h1>Welcome to the <span>Student Portal</span></h1>
        <p>
            Register as a student, submit your details, and track your
            admission status — all in one simple portal. Click the button
            below to fill out the student registration form.
        </p>
        <a href="form.php" class="btn btn-primary">Fill Registration Form →</a>
        &nbsp;
        <a href="dashboard.php" class="btn btn-outline">View Records</a>
    </div>
    <div class="hero-art">🎓</div>
</section>

<section class="features">
    <div class="feature">
        <div class="icon">📝</div>
        <h3>Easy Registration</h3>
        <p>Fill a simple form with your personal details, profile photo, and JAMB score.</p>
    </div>
    <div class="feature">
        <div class="icon">📊</div>
        <h3>Records Dashboard</h3>
        <p>All submitted records displayed in a table you can filter by name, status, gender and score.</p>
    </div>
    <div class="feature">
        <div class="icon">✅</div>
        <h3>Admission Status</h3>
        <p>View full student details and set admission status to Admitted or Undecided.</p>
    </div>
</section>

<footer class="footer">
    &copy; <?= date('Y') ?> Student Portal — One Million Coders Fullstack Project
</footer>

</body>
</html>
