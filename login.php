<?php
// login.php - User Sign In Page for Campus Lost & Found
session_start();
include "db.php";

// Redirect if already logged in
if (isset($_SESSION['username'])) {
    if ($_SESSION['username'] === 'admin') {
        header("Location: admin.php");
    } else {
        header("Location: index.php");
    }
    exit();
}

$error_msg = null;
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error_msg = "Please enter both College ID/Email and password.";
    } else {
        // Query database for user by username (College ID) or email
        $stmt = $conn->prepare("SELECT username, name, email, password FROM users WHERE username = ? OR email = ?");
        if ($stmt) {
            $stmt->bind_param("ss", $username, $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $user = $result->fetch_assoc()) {
                // Verify password (bcrypt hashed or fallback plain text match for seeded accounts)
                if (password_verify($password, $user['password']) || $password === $user['password']) {
                    // Set session variables
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['name'] = $user['name'];
                    if (!empty($user['email'])) {
                        $_SESSION['email'] = $user['email'];
                    }

                    // Redirect based on role/username
                    if ($user['username'] === 'admin') {
                        header("Location: admin.php");
                    } else {
                        header("Location: index.php");
                    }
                    exit();
                } else {
                    $error_msg = "Invalid password. Please check your credentials and try again.";
                }
            } else {
                $error_msg = "No account found matching this College ID or Email.";
            }
            $stmt->close();
        } else {
            $error_msg = "Database error. Please try again later.";
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
            <div class="alert alert-danger" style="margin-bottom: 20px;">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span><?php echo htmlspecialchars($error_msg); ?></span>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="form-group">
                <label for="username" class="form-label">College ID / Username / Email</label>
                <div class="input-icon-group">
                    <i class="fa-solid fa-id-card"></i>
                    <input type="text" id="username" name="username" class="form-control" placeholder="e.g. CS21B045 or admin" value="<?php echo htmlspecialchars($username); ?>" required autofocus>
                </div>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <div class="input-icon-group" style="position: relative;">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password" required>
                    <i class="fa-solid fa-eye" id="togglePassword" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; color: var(--text-muted); z-index: 10;"></i>
                </div>
            </div>

            <div class="form-check" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <label class="form-check-label" style="font-size: 13.5px; cursor: pointer;">
                    <input type="checkbox" name="remember" style="accent-color: var(--primary);"> Remember Me
                </label>
                <a href="forgot_password.php" style="font-size: 13.5px; font-weight: 600; color: var(--primary); text-decoration: none;">Forgot Password?</a>
            </div>

            <button type="submit" class="btn btn-primary btn-block" style="padding: 14px;">
                <i class="fa-solid fa-right-to-bracket"></i> Sign In to Portal
            </button>
        </form>

        <!-- Quick Demo Credentials Selector -->
        <div style="margin-top: 20px; padding: 12px; background: rgba(var(--primary-rgb, 79, 70, 229), 0.06); border: 1px dashed var(--primary); border-radius: 10px; font-size: 13px;">
            <div style="font-weight: 600; color: var(--primary); margin-bottom: 6px;">
                <i class="fa-solid fa-key"></i> Quick Demo Logins:
            </div>
            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                <button type="button" onclick="fillDemo('CS21B045', 'password123')" style="background: white; border: 1px solid #ccc; border-radius: 6px; padding: 4px 8px; font-size: 12px; cursor: pointer; color: #333;">
                    <i class="fa-solid fa-user-graduate"></i> Student (CS21B045)
                </button>
                <button type="button" onclick="fillDemo('admin', 'admin123')" style="background: white; border: 1px solid #ccc; border-radius: 6px; padding: 4px 8px; font-size: 12px; cursor: pointer; color: #333;">
                    <i class="fa-solid fa-user-shield"></i> Admin (admin)
                </button>
            </div>
        </div>

        <div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--border); text-align: center; font-size: 14px; color: var(--text-muted);">
            Don't have an account? <a href="register.php" style="font-weight: 700; color: var(--primary); text-decoration: none;">Sign Up Here</a>
        </div>
        <div style="margin-top: 10px; text-align: center;">
            <a href="index.php" style="font-size: 13px; color: var(--text-muted); text-decoration: none;">
                <i class="fa-solid fa-arrow-left"></i> Back to Homepage
            </a>
        </div>
    </div>
</div>

<script>
    // Toggle password visibility
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');

    if (togglePassword && passwordInput) {
        togglePassword.addEventListener('click', function () {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    }

    // Fill demo credentials
    function fillDemo(username, password) {
        document.getElementById('username').value = username;
        document.getElementById('password').value = password;
    }
</script>

</body>
</html>