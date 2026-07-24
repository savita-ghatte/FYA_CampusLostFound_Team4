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
        
        if (empty($username)) {
            $error_msg = "Invalid request. Please try again.";
            $step = 1;
        } elseif (empty($password) || empty($confirm_password)) {
            $error_msg = "Please fill in all password fields.";
            $step = 2;
        } elseif (strlen($password) < 6) {
            $error_msg = "Password must contain at least 6 characters.";
            $step = 2;
        } elseif ($password !== $confirm_password) {
            $error_msg = "Passwords do not match.";
            $step = 2;
        } else {
            // Update user password in database
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt_update = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
            if ($stmt_update) {
                $stmt_update->bind_param("ss", $hashed_password, $username);
                if ($stmt_update->execute()) {
                    $success_msg = "Password reset successfully! Redirecting to Sign In page...";
                    $step = 3; // Success state
                } else {
                    $error_msg = "Failed to update password. Please try again.";
                    $step = 2;
                }
                $stmt_update->close();
            } else {
                $error_msg = "Database statement error.";
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

<title>Campus Lost & Found — Forgot Password</title>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Fraunces:wght@600;700&display=swap" rel="stylesheet">
<style>
:root {
    --ink:#1F2A38;
    --paper:#FAF6EE;
    --gold:#C99A2E;
    --gold-dark:#A87D1E;
    --rust:#B23A2E;
    --green:#3E6B4F;
    --board:#8C6A46;
    --board-dark:#7A5A3A;
}

* {
    box-sizing:border-box;
}

body {
    margin:0;
    min-height:100vh;
    font-family:'Inter',sans-serif;
    background:
    linear-gradient(160deg,#8C6A46,#7A5A3A);
    color:var(--ink);
}

.wrap {
    max-width:1100px;
    margin:auto;
    padding:30px 20px;
}

/* Navigation */
nav {
    display:flex;
    justify-content:center;
    gap:10px;
    flex-wrap:wrap;
    margin-bottom:40px;
}

nav a {
    text-decoration:none;
    color:white;
    padding:8px 16px;
    border-radius:20px;
    background:rgba(0,0,0,.3);
    font-size:13px;
}

nav a.active {
    background:var(--gold);
    color:#222;
}

/* Header */
header {
    text-align:center;
    color:white;
    margin-bottom:40px;
}

h1 {
    font-family:'Fraunces',serif;
    font-size:45px;
    margin-bottom:10px;
}

header p {
    opacity:.85;
}

/* Card */
.center {
    display:flex;
    justify-content:center;
}

.card {
    width:100%;
    max-width:420px;
    background:var(--paper);
    padding:35px;
    border-radius:15px;
    box-shadow:0 20px 40px rgba(0,0,0,.35);
    position:relative;
}

.pin {
    width:22px;
    height:22px;
    background:var(--gold);
    border-radius:50%;
    position:absolute;
    top:-12px;
    left:50%;
    transform:translateX(-50%);
}

.badge-strip {
    color:var(--gold-dark);
    font-size:12px;
    letter-spacing:2px;
    border-bottom:2px dashed #C9AE79;
    padding-bottom:12px;
    margin-bottom:20px;
}

h2 {
    font-family:'Fraunces',serif;
    margin:0;
}

.sub {
    font-size:14px;
    color:#555;
    margin-bottom:25px;
}

/* Form */
.field {
    margin-bottom:20px;
}

label {
    display:block;
    font-weight:600;
    font-size:14px;
    margin-bottom:7px;
}

.req {
    color:var(--rust);
}

input {
    width:100%;
    padding:12px;
    border-radius:8px;
    border:1px solid #C9AE79;
    font-size:15px;
}

input:focus {
    outline:none;
    border-color:var(--gold-dark);
}

.password-wrap {
    position: relative;
    display: flex;
    align-items: center;
}

.password-wrap input {
    padding-right: 42px;
}

.toggle-password {
    position: absolute;
    right: 10px;
    background: none;
    border: none;
    cursor: pointer;
    padding: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #666;
    transition: color 0.2s ease;
}

.toggle-password:hover {
    color: var(--gold-dark);
}

.toggle-password svg {
    width: 20px;
    height: 20px;
    stroke: currentColor;
    fill: none;
    stroke-width: 2;
    stroke-linecap: round;
    stroke-linejoin: round;
}

.error-msg {
    display:none;
    color:var(--rust);
    font-size:12px;
    margin-top:5px;
}

.invalid input {
    border-color:var(--rust);
}

.invalid .error-msg {
    display:block;
}

.valid input {
    border-color:var(--green);
}

/* Button */
button[type="submit"] {
    width:100%;
    padding:13px;
    border:none;
    border-radius:8px;
    background:var(--ink);
    color:white;
    font-size:15px;
    font-weight:600;
    cursor:pointer;
}

button[type="submit"]:hover {
    background:#3D4A5C;
}

.success-note {
    margin-top:15px;
    padding:14px;
    border-radius:8px;
    background:#e5f1e8;
    color:var(--green);
    font-size:14px;
    font-weight:500;
    text-align:center;
}

/* Footer */
footer {
    text-align:center;
    color:white;
    opacity:.6;
    margin-top:50px;
    font-size:13px;
}
</style>

</head>

<body>

<div class="wrap">

<nav>
    <a href="index.php">Home</a>
    <a href="items.php">Browse Items</a>
    <a class="active" href="login.php">Sign In</a>
    <a href="register.php">Sign Up</a>
</nav>

<header>
<h1>Forgot Password</h1>
<p>Reset your account password using your registered Email ID.</p>
</header>

<div class="center">

<div class="card">

<div class="pin"></div>

<div class="badge-strip">
ACCOUNT RECOVERY
</div>

<?php if ($step === 1): ?>
    <h2>Identify Account</h2>
    <p class="sub">
        Enter your registered Email ID (or College ID) below.
    </p>

    <?php if ($error_msg): ?>
        <div style="border:1px solid var(--rust); color:var(--rust); padding:12px; border-radius:8px; margin-bottom:20px; font-size:14px; font-weight:500;">
            ⚠ <?php echo htmlspecialchars($error_msg); ?>
        </div>
    <?php endif; ?>

    <form id="emailForm" method="POST" action="forgot_password.php">
        <input type="hidden" name="action" value="verify_email">

        <div class="field" id="emailBox">
            <label>
                Email ID / College ID <span class="req">*</span>
            </label>
            <input 
                type="text"
                id="emailInput"
                name="email"
                placeholder="e.g. cs21b045@college.edu or CS21B045"
                value="<?php echo htmlspecialchars($email); ?>"
                required
            >
            <p class="error-msg">Please enter a valid Email ID or College ID.</p>
        </div>

        <button type="submit">
            Continue to Reset Password
        </button>

        <p style="text-align:center; font-size:14px; margin-top:20px; color:#555;">
            Remembered your password? <a href="login.php" style="color:var(--gold-dark); text-decoration:none; font-weight:600;">Sign In</a>
        </p>
    </form>

<?php elseif ($step === 2): ?>
    <h2>Set New Password</h2>
    <p class="sub">
        Account: <strong><?php echo htmlspecialchars($username); ?></strong>
    </p>

    <?php if ($error_msg): ?>
        <div style="border:1px solid var(--rust); color:var(--rust); padding:12px; border-radius:8px; margin-bottom:20px; font-size:14px; font-weight:500;">
            ⚠ <?php echo htmlspecialchars($error_msg); ?>
        </div>
    <?php endif; ?>

    <form id="resetForm" method="POST" action="forgot_password.php">
        <input type="hidden" name="action" value="reset_password">
        <input type="hidden" name="username" value="<?php echo htmlspecialchars($username); ?>">
        <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">

        <div class="field" id="passwordBox">
            <label>New Password <span class="req">*</span></label>
            <div class="password-wrap">
                <input 
                    type="password"
                    id="newPassword"
                    name="password"
                    placeholder="Minimum 6 characters"
                    required
                >
                <button type="button" class="toggle-password" onclick="togglePassword('newPassword', this)" title="Show password" aria-label="Show password">
                    <svg viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                </button>
            </div>
            <p class="error-msg">Password must contain at least 6 characters.</p>
        </div>

        <div class="field" id="confirmBox">
            <label>Confirm New Password <span class="req">*</span></label>
            <div class="password-wrap">
                <input 
                    type="password"
                    id="confirmPassword"
                    name="confirm_password"
                    placeholder="Re-enter new password"
                    required
                >
                <button type="button" class="toggle-password" onclick="togglePassword('confirmPassword', this)" title="Show password" aria-label="Show password">
                    <svg viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                </button>
            </div>
            <p class="error-msg">Passwords do not match.</p>
        </div>

        <button type="submit">
            Save New Password
        </button>
    </form>

<?php elseif ($step === 3): ?>
    <h2>Success!</h2>
    <div class="success-note">
        ✓ <?php echo htmlspecialchars($success_msg); ?>
    </div>
    <script>
        setTimeout(() => {
            window.location.href = "login.php";
        }, 2000);
    </script>
    <p style="text-align:center; font-size:14px; margin-top:20px;">
        <a href="login.php" style="color:var(--gold-dark); text-decoration:none; font-weight:600;">Click here if not redirected automatically</a>
    </p>
<?php endif; ?>

</div>

</div>

<footer>
CAMPUS LOST & FOUND • COLLEGE OFFICE
</footer>

</div>

<script>
function togglePassword(inputId, btn) {
    const input = document.getElementById(inputId);
    if (!input) return;
    if (input.type === "password") {
        input.type = "text";
        btn.innerHTML = '<svg viewBox="0 0 24 24"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>';
        btn.setAttribute("title", "Hide password");
        btn.setAttribute("aria-label", "Hide password");
    } else {
        input.type = "password";
        btn.innerHTML = '<svg viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>';
        btn.setAttribute("title", "Show password");
        btn.setAttribute("aria-label", "Show password");
    }
}
</script>

</body>
</html>
