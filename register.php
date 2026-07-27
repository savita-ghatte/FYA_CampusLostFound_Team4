<?php
// register.php - User Registration (Sign Up) Page
session_start();
include "db.php";

// Redirect if already logged in
if (isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

$error_msg = null;
$success_msg = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Server-side validation
    if (empty($name) || empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error_msg = "All fields are required.";
    } elseif (strlen($name) < 2) {
        $error_msg = "Name must contain at least 2 characters.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = "Please enter a valid Email ID.";
    } elseif (!preg_match('/^[A-Za-z0-9]{5,}$/', $username)) {
        $error_msg = "College ID must contain at least 5 alphanumeric characters.";
    } elseif (strlen($password) < 6) {
        $error_msg = "Password must contain at least 6 characters.";
    } elseif ($password !== $confirm_password) {
        $error_msg = "Passwords do not match.";
    } else {
        // Check if username already exists
        $stmt_check = $conn->prepare("SELECT username FROM users WHERE username = ? OR email = ?");
        if ($stmt_check) {
            $stmt_check->bind_param("ss", $username, $email);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();

            if ($result_check && $result_check->num_rows > 0) {
                $error_msg = "College ID or Email ID already registered. Try signing in.";
            } else {
                // Insert new user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt_insert = $conn->prepare("INSERT INTO users (username, name, email, password) VALUES (?, ?, ?, ?)");
                if ($stmt_insert) {
                    $stmt_insert->bind_param("ssss", $username, $name, $email, $hashed_password);
                    if ($stmt_insert->execute()) {
                        // Registration success - auto login and redirect
                        $_SESSION['username'] = $username;
                        $_SESSION['name'] = $name;
                        header("Location: index.php");
                        exit();
                    } else {
                        $error_msg = "Error registering user. Please try again.";
                    }
                    $stmt_insert->close();
                } else {
                    $error_msg = "Statement preparation error.";
                }
            }
            $stmt_check->close();
        } else {
            $error_msg = "Database connection error.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up | CampusConnect Lost & Found - Zeal College</title>
    
    <!-- Font Awesome 6 Icons & Google Fonts -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/theme.css">
</head>
<body>

<div class="auth-page-wrapper">
    <div class="glass-card" style="max-width: 500px;">
        <div class="auth-header">
            <div class="auth-logo">
                <i class="fa-solid fa-user-plus"></i>
            </div>
            <h1 class="auth-title">Create Account</h1>
            <p class="auth-sub">Zeal College of Engineering and Research, Narhe, Pune</p>
        </div>

        <?php if (!empty($error_msg)): ?>
            <div class="alert alert-danger">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span><?php echo htmlspecialchars($error_msg); ?></span>
            </div>
        <?php endif; ?>

        <form action="register.php" method="POST">
            <div class="form-group">
                <label for="name" class="form-label">Full Name</label>
                <div class="input-icon-group">
                    <i class="fa-solid fa-user"></i>
                    <input type="text" id="name" name="name" class="form-control" placeholder="e.g. Savita Ghatte" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required autofocus>
                </div>
            </div>

            <div class="form-group">
                <label for="email" class="form-label">Email ID</label>
                <div class="input-icon-group">
                    <i class="fa-solid fa-envelope"></i>
                    <input type="email" id="email" name="email" class="form-control" placeholder="e.g. student@zeal.edu.in" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label for="username" class="form-label">College Roll No. / ID</label>
                <div class="input-icon-group">
                    <i class="fa-solid fa-id-card"></i>
                    <input type="text" id="username" name="username" class="form-control" placeholder="e.g. CS21B045" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <div class="input-icon-group">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Min. 6 characters" required>
                </div>
            </div>

            <div class="form-group">
                <label for="confirm_password" class="form-label">Confirm Password</label>
                <div class="input-icon-group">
                    <i class="fa-solid fa-shield-halved"></i>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Re-enter password" required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block" style="padding:14px; margin-top: 10px;">
                <i class="fa-solid fa-user-plus"></i> Complete Sign Up
            </button>
        </form>

        <div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--border); text-align:center; font-size: 14px; color: var(--text-muted);">
            Already have an account? <a href="login.php" style="font-weight:700;">Sign In Here</a>
        </div>
    </div>
</div>

</body>
</html>
