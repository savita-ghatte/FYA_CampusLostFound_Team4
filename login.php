<?php
// login.php - User Sign In Page
session_start();
include "db.php";

// Redirect if already logged in
if (isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

$error_msg = null;
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error_msg = "Please enter both College ID and password.";
    } else {
        // Query database for user
        $stmt = $conn->prepare("SELECT username, name, password FROM users WHERE username = ?");
        if ($stmt) {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $row = $result->fetch_assoc()) {
                // Verify password (hashed or plain fallback for seeded accounts)
                if (password_verify($password, $row['password']) || $password === $row['password']) {
                    $_SESSION['username'] = $row['username'];
                    $_SESSION['name'] = $row['name'];

                    if ($row['username'] === 'admin') {
                        header("Location: admin.php");
                    } else {
                        header("Location: index.php");
                    }
                    exit();
                } else {
                    $error_msg = "Invalid College ID or password.";
                }
            } else {
                $error_msg = "Invalid College ID or password.";
            }
            $stmt->close();
        } else {
            $error_msg = "Database query error. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In | CampusConnect Lost & Found - Zeal College</title>
    
    <!-- Font Awesome 6 Icons & Google Fonts -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/theme.css">
</head>
<body>

<div class="auth-page-wrapper">
    <div class="glass-card">
        <div class="auth-header">
            <div class="auth-logo">
                <i class="fa-solid fa-building-columns"></i>
            </div>
            <h1 class="auth-title">CampusConnect</h1>
            <p class="auth-sub">Zeal College of Engineering and Research, Narhe, Pune</p>
        </div>

        <?php if (!empty($error_msg)): ?>
            <div class="alert alert-danger">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span><?php echo htmlspecialchars($error_msg); ?></span>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="form-group">
                <label for="username" class="form-label">College ID / Username</label>
                <div class="input-icon-group">
                    <i class="fa-solid fa-id-card"></i>
                    <input type="text" id="username" name="username" class="form-control" placeholder="e.g. CS21B045 or admin" value="<?php echo htmlspecialchars($username); ?>" required autofocus>
                </div>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <div class="input-icon-group">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password" required>
                </div>
            </div>

            <div class="form-check">
                <label class="form-check-label">
                    <input type="checkbox" name="remember" style="accent-color: var(--primary);"> Remember Me
                </label>
                <a href="forgot_password.php" style="font-size:13.5px; font-weight:600;">Forgot Password?</a>
            </div>

            <button type="submit" class="btn btn-primary btn-block" style="padding:14px;">
                <i class="fa-solid fa-right-to-bracket"></i> Sign In to Portal
            </button>
        </form>

        <div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--border); text-align:center; font-size: 14px; color: var(--text-muted);">
            Don't have an account? <a href="register.php" style="font-weight:700;">Sign Up Here</a>
        </div>

        <div style="margin-top: 20px; background: rgba(37,99,235,0.06); padding: 12px; border-radius: var(--radius-sm); font-size: 12.5px; color: var(--text-muted); text-align: center;">
            <i class="fa-solid fa-lightbulb" style="color:var(--primary);"></i> Demo Login: <strong>CS21B045</strong> &bull; Password: <strong>password123</strong>
        </div>
    </div>
</div>

</body>
</html>
