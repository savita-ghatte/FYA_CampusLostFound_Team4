<?php
// forgot_password.php - Forgot Password Page
session_start();
include "db.php";

// Redirect if already logged in
if (isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

$error_msg = null;
$success_msg = null;
$step = 1;
$email = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'verify_email') {
        $email = trim($_POST['email'] ?? '');
        if (empty($email)) {
            $error_msg = "Please enter your registered Email ID.";
        } else {
            // Check if user exists with matching email or username
            $stmt = $conn->prepare("SELECT username, email FROM users WHERE email = ? OR username = ?");
            if ($stmt) {
                $stmt->bind_param("ss", $email, $email);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result && $row = $result->fetch_assoc()) {
                    $username = $row['username'];
                    $email = $row['email'] ?? $email;
                    $step = 2; // Move to password reset step
                } else {
                    $error_msg = "No account found with this Email ID / College ID.";
                }
                $stmt->close();
            } else {
                $error_msg = "Database query error. Please try again.";
            }
        }
    } elseif ($action === 'reset_password') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $email = trim($_POST['email'] ?? '');
        
        if (empty($username) || empty($password) || empty($confirm_password)) {
            $error_msg = "Please fill in all fields.";
            $step = 2;
        } elseif (strlen($password) < 6) {
            $error_msg = "New password must contain at least 6 characters.";
            $step = 2;
        } elseif ($password !== $confirm_password) {
            $error_msg = "Passwords do not match.";
            $step = 2;
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt_up = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
            if ($stmt_up) {
                $stmt_up->bind_param("ss", $hashed_password, $username);
                if ($stmt_up->execute()) {
                    $success_msg = "Password reset successfully! You can now sign in with your new password.";
                    $step = 3; // Reset completed
                } else {
                    $error_msg = "Failed to update password. Please try again.";
                    $step = 2;
                }
                $stmt_up->close();
            } else {
                $error_msg = "Database query error.";
                $step = 2;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | CampusConnect - Zeal College</title>
    
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
                <i class="fa-solid fa-key"></i>
            </div>
            <h1 class="auth-title">Reset Password</h1>
            <p class="auth-sub">Zeal College of Engineering and Research, Narhe, Pune</p>
        </div>

        <?php if (!empty($success_msg)): ?>
            <div class="alert alert-success">
                <i class="fa-solid fa-circle-check"></i>
                <span><?php echo htmlspecialchars($success_msg); ?></span>
            </div>
            <a href="login.php" class="btn btn-primary btn-block" style="padding:14px;">
                <i class="fa-solid fa-right-to-bracket"></i> Return to Sign In
            </a>
        <?php else: ?>

            <?php if (!empty($error_msg)): ?>
                <div class="alert alert-danger">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span><?php echo htmlspecialchars($error_msg); ?></span>
                </div>
            <?php endif; ?>

            <?php if ($step === 1): ?>
                <form action="forgot_password.php" method="POST">
                    <input type="hidden" name="action" value="verify_email">
                    <div class="form-group">
                        <label for="email" class="form-label">Registered Email ID / College ID</label>
                        <div class="input-icon-group">
                            <i class="fa-solid fa-envelope"></i>
                            <input type="text" id="email" name="email" class="form-control" placeholder="Enter your email or College ID" value="<?php echo htmlspecialchars($email); ?>" required autofocus>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block" style="padding:14px;">
                        <i class="fa-solid fa-arrow-right"></i> Verify Account
                    </button>
                </form>
            <?php elseif ($step === 2): ?>
                <form action="forgot_password.php" method="POST">
                    <input type="hidden" name="action" value="reset_password">
                    <input type="hidden" name="username" value="<?php echo htmlspecialchars($username); ?>">
                    <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">

                    <div style="background:#eff6ff; padding:12px; border-radius:var(--radius-sm); font-size:13.5px; color:var(--primary); margin-bottom:20px;">
                        Account verified for: <strong><?php echo htmlspecialchars($username); ?></strong>
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label">New Password</label>
                        <div class="input-icon-group">
                            <i class="fa-solid fa-lock"></i>
                            <input type="password" id="password" name="password" class="form-control" placeholder="Min. 6 characters" required autofocus>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <div class="input-icon-group">
                            <i class="fa-solid fa-shield-halved"></i>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Re-enter new password" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block" style="padding:14px;">
                        <i class="fa-solid fa-check"></i> Reset Password Now
                    </button>
                </form>
            <?php endif; ?>

        <?php endif; ?>

        <div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--border); text-align:center; font-size: 14px; color: var(--text-muted);">
            Remembered your password? <a href="login.php" style="font-weight:700;">Sign In Here</a>
        </div>
    </div>
</div>

</body>
</html>
