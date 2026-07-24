<?php
// profile.php - Edit Profile Page
session_start();
include "db.php";

// Redirect to login if not logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];
$error_msg = null;
$success_msg = null;

// Fetch current user details
$name = '';
$stmt = $conn->prepare("SELECT name FROM users WHERE username = ?");
if ($stmt) {
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $row = $result->fetch_assoc()) {
        $name = $row['name'];
    }
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_name = trim($_POST['name'] ?? '');
    $new_password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($new_name)) {
        $error_msg = "Name cannot be empty.";
    } elseif (strlen($new_name) < 2) {
        $error_msg = "Name must contain at least 2 characters.";
    } else {
        $update_password = false;
        if (!empty($new_password)) {
            if (strlen($new_password) < 6) {
                $error_msg = "New password must contain at least 6 characters.";
            } elseif ($new_password !== $confirm_password) {
                $error_msg = "Passwords do not match.";
            } else {
                $update_password = true;
            }
        }

        if (!$error_msg) {
            if ($update_password) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt_update = $conn->prepare("UPDATE users SET name = ?, password = ? WHERE username = ?");
                if ($stmt_update) {
                    $stmt_update->bind_param("sss", $new_name, $hashed_password, $username);
                    if ($stmt_update->execute()) {
                        $success_msg = "Profile and password updated successfully!";
                        $name = $new_name;
                    } else {
                        $error_msg = "Failed to update profile.";
                    }
                    $stmt_update->close();
                }
            } else {
                $stmt_update = $conn->prepare("UPDATE users SET name = ? WHERE username = ?");
                if ($stmt_update) {
                    $stmt_update->bind_param("ss", $new_name, $username);
                    if ($stmt_update->execute()) {
                        $success_msg = "Profile updated successfully!";
                        $name = $new_name;
                    } else {
                        $error_msg = "Failed to update profile.";
                    }
                    $stmt_update->close();
                }
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

<title>Campus Lost & Found — Edit Profile</title>

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

/* Profile Card */
.center {
    display:flex;
    justify-content:center;
}

.card {
    width:100%;
    max-width:440px;
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

.error-msg {
    display:none;
    color:var(--rust);
    font-size:12px;
}

.invalid input {
    border-color:var(--rust);
}

.invalid .error-msg {
    display:block;
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
    width: auto;
}

.toggle-password:hover {
    color: var(--gold-dark);
    background: none;
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
    display:none;
    margin-top:15px;
    padding:12px;
    border-radius:8px;
    background:#e5f1e8;
    color:var(--green);
}

.success-note.show {
    display:block;
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
    <?php if (isset($_SESSION['username'])): ?>
        <a href="report_lost.php">Report Lost</a>
        <a href="report_found.php">Report Found</a>
    <?php endif; ?>
    <a href="items.php">Browse Items</a>
    <?php if (isset($_SESSION['username'])): ?>
        <a href="claims.php">Claim</a>
        <a href="profile.php" class="active">Edit Profile</a>
        <?php if ($_SESSION['username'] === 'admin'): ?>
            <a href="admin.php">Admin</a>
        <?php endif; ?>
        <a href="logout.php">Sign Out (<?php echo htmlspecialchars($_SESSION['username']); ?>)</a>
    <?php else: ?>
        <a href="login.php">Sign In</a>
        <a href="register.php">Sign Up</a>
    <?php endif; ?>
</nav>

<header>
<h1>Edit Your Profile</h1>
<p>Update your details or set a new password.</p>
</header>

<div class="center">

<div class="card">
<div class="pin"></div>

<div class="badge-strip">
STUDENT PROFILE
</div>

<h2>Profile Settings</h2>
<p class="sub">
College ID: <strong><?php echo htmlspecialchars($username); ?></strong>
</p>

<?php if ($error_msg): ?>
    <div style="border:1px solid var(--rust); color:var(--rust); padding:12px; border-radius:8px; margin-bottom:20px; font-size:14px; font-weight:500;">
        ⚠ <?php echo htmlspecialchars($error_msg); ?>
    </div>
<?php endif; ?>

<?php if ($success_msg): ?>
    <div style="border:1px solid var(--green); color:var(--green); padding:12px; border-radius:8px; margin-bottom:20px; font-size:14px; font-weight:500; background:#e5f1e8;">
        ✓ <?php echo htmlspecialchars($success_msg); ?>
    </div>
<?php endif; ?>

<form id="profileForm" method="POST" action="profile.php">

<div class="field" id="nameBox">
<label>Full Name <span class="req">*</span></label>
<input 
type="text"
id="studentName"
name="name"
placeholder="Example: Anushka Pardesi"
value="<?php echo htmlspecialchars($name); ?>"
>
<p class="error-msg">Enter your full name.</p>
</div>

<div class="field" id="passwordBox">
<label>New Password (Leave blank to keep current)</label>
<div class="password-wrap">
    <input 
    type="password"
    id="password"
    name="password"
    placeholder="Minimum 6 characters"
    >
    <button type="button" class="toggle-password" onclick="togglePassword('password', this)" title="Show password" aria-label="Show password">
        <svg viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
    </button>
</div>
<p class="error-msg">Password must contain at least 6 characters.</p>
</div>

<div class="field" id="confirmBox">
<label>Confirm New Password</label>
<div class="password-wrap">
    <input 
    type="password"
    id="confirmPassword"
    name="confirm_password"
    placeholder="Re-enter new password"
    >
    <button type="button" class="toggle-password" onclick="togglePassword('confirmPassword', this)" title="Show password" aria-label="Show password">
        <svg viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
    </button>
</div>
<p class="error-msg">Passwords do not match.</p>
</div>

<button type="submit">
Save Profile Changes
</button>

</form>

</div>

</div>

<footer>
CAMPUS LOST & FOUND • COLLEGE OFFICE
</footer>

</div>

<script>
const form=document.getElementById("profileForm");
const studentName=document.getElementById("studentName");
const password=document.getElementById("password");
const confirmPassword=document.getElementById("confirmPassword");

const nameBox=document.getElementById("nameBox");
const passwordBox=document.getElementById("passwordBox");
const confirmBox=document.getElementById("confirmBox");

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

function validateName(){
    let ok = studentName.value.trim().length >= 2;
    nameBox.classList.toggle("invalid", !ok);
    nameBox.classList.toggle("valid", ok);
    return ok;
}

function validatePassword(){
    if (password.value.length === 0) {
        passwordBox.classList.remove("invalid", "valid");
        return true;
    }
    let ok=password.value.length>=6;
    passwordBox.classList.toggle("invalid",!ok);
    passwordBox.classList.toggle("valid",ok);
    return ok;
}

function validateConfirm(){
    if (password.value.length === 0 && confirmPassword.value.length === 0) {
        confirmBox.classList.remove("invalid", "valid");
        return true;
    }
    let ok = (confirmPassword.value === password.value) && confirmPassword.value.length >= 6;
    confirmBox.classList.toggle("invalid",!ok);
    confirmBox.classList.toggle("valid",ok);
    return ok;
}

studentName.addEventListener("blur", validateName);
password.addEventListener("blur",validatePassword);
confirmPassword.addEventListener("blur", validateConfirm);

form.addEventListener("submit",(e)=>{
    let valName = validateName();
    let valPass = validatePassword();
    let valConf = validateConfirm();

    if(!valName || !valPass || !valConf){
        e.preventDefault();
    }
});
</script>

</body>
</html>
