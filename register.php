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
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Server-side validation
    if (empty($name) || empty($username) || empty($password) || empty($confirm_password)) {
        $error_msg = "All fields are required.";
    } elseif (strlen($name) < 2) {
        $error_msg = "Name must contain at least 2 characters.";
    } elseif (!preg_match('/^[A-Za-z0-9]{5,}$/', $username)) {
        $error_msg = "College ID must contain at least 5 alphanumeric characters.";
    } elseif (strlen($password) < 6) {
        $error_msg = "Password must contain at least 6 characters.";
    } elseif ($password !== $confirm_password) {
        $error_msg = "Passwords do not match.";
    } else {
        // Check if username already exists
        $stmt_check = $conn->prepare("SELECT username FROM users WHERE username = ?");
        if ($stmt_check) {
            $stmt_check->bind_param("s", $username);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();

            if ($result_check && $result_check->num_rows > 0) {
                $error_msg = "College ID already registered. Try signing in.";
            } else {
                // Insert new user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt_insert = $conn->prepare("INSERT INTO users (username, name, password) VALUES (?, ?, ?)");
                if ($stmt_insert) {
                    $stmt_insert->bind_param("sss", $username, $name, $hashed_password);
                    if ($stmt_insert->execute()) {
                        // Registration success - auto login and redirect
                        $_SESSION['username'] = $username;
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

<title>Campus Lost & Found — Sign Up</title>

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

/* Register Card */
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

.valid input {
    border-color:var(--green);
}

/* Button */
button {
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

button:hover {
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
        <a href="profile.php">Edit Profile</a>
        <?php if ($_SESSION['username'] === 'admin'): ?>
            <a href="admin.php">Admin</a>
        <?php endif; ?>
        <a href="logout.php">Sign Out (<?php echo htmlspecialchars($_SESSION['username']); ?>)</a>
    <?php else: ?>
        <a href="login.php">Sign In</a>
        <a class="active" href="register.php">Sign Up</a>
    <?php endif; ?>
</nav>

<header>
<h1>Register for Lost & Found</h1>
<p>Create an account to report items and verify ownership.</p>
</header>

<div class="center">

<div class="card">
<div class="pin"></div>

<div class="badge-strip">
STUDENT & STAFF PORTAL
</div>

<h2>Sign Up</h2>
<p class="sub">
All fields marked * are required.
</p>

<?php if ($error_msg): ?>
    <div style="border:1px solid var(--rust); color:var(--rust); padding:12px; border-radius:8px; margin-bottom:20px; font-size:14px; font-weight:500;">
        ⚠ <?php echo htmlspecialchars($error_msg); ?>
    </div>
<?php endif; ?>

<form id="registerForm" method="POST" action="register.php">

<div class="field" id="nameBox">
<label>Full Name <span class="req">*</span></label>
<input 
type="text"
id="studentName"
name="name"
placeholder="Example: Anushka Pardesi"
value="<?php echo htmlspecialchars($name ?? ''); ?>"
>
<p class="error-msg">Enter your full name.</p>
</div>

<div class="field" id="collegeBox">
<label>College ID / Username <span class="req">*</span></label>
<input 
type="text"
id="collegeId"
name="username"
placeholder="Example: CS21B045"
value="<?php echo htmlspecialchars($username ?? ''); ?>"
>
<p class="error-msg">Enter a valid College ID (minimum 5 letters/numbers).</p>
</div>

<div class="field" id="passwordBox">
<label>Password <span class="req">*</span></label>
<input 
type="password"
id="password"
name="password"
placeholder="Minimum 6 characters"
>
<p class="error-msg">Password must contain at least 6 characters.</p>
</div>

<div class="field" id="confirmBox">
<label>Confirm Password <span class="req">*</span></label>
<input 
type="password"
id="confirmPassword"
name="confirm_password"
placeholder="Re-enter password"
>
<p class="error-msg">Passwords do not match.</p>
</div>

<button type="submit">
Sign Up
</button>

<div class="success-note" id="success">
Account created successfully — redirecting...
</div>

<p style="text-align:center; font-size:14px; margin-top:20px; color:#555;">
    Already have an account? <a href="login.php" style="color:var(--gold-dark); text-decoration:none; font-weight:600;">Sign In</a>
</p>

</form>

</div>

</div>

<footer>
CAMPUS LOST & FOUND • COLLEGE OFFICE
</footer>

</div>

<script>
const form=document.getElementById("registerForm");
const studentName=document.getElementById("studentName");
const college=document.getElementById("collegeId");
const password=document.getElementById("password");
const confirmPassword=document.getElementById("confirmPassword");

const nameBox=document.getElementById("nameBox");
const collegeBox=document.getElementById("collegeBox");
const passwordBox=document.getElementById("passwordBox");
const confirmBox=document.getElementById("confirmBox");
const success=document.getElementById("success");

function validateName(){
    let ok = studentName.value.trim().length >= 2;
    nameBox.classList.toggle("invalid", !ok);
    nameBox.classList.toggle("valid", ok);
    return ok;
}

function validateCollege(){
    let ok=/^[A-Za-z0-9]{5,}$/.test(college.value.trim());
    collegeBox.classList.toggle("invalid",!ok);
    collegeBox.classList.toggle("valid",ok);
    return ok;
}

function validatePassword(){
    let ok=password.value.length>=6;
    passwordBox.classList.toggle("invalid",!ok);
    passwordBox.classList.toggle("valid",ok);
    return ok;
}

function validateConfirm(){
    let ok = (confirmPassword.value === password.value) && confirmPassword.value.length >= 6;
    confirmBox.classList.toggle("invalid",!ok);
    confirmBox.classList.toggle("valid",ok);
    return ok;
}

studentName.addEventListener("blur", validateName);
college.addEventListener("blur",validateCollege);
password.addEventListener("blur",validatePassword);
confirmPassword.addEventListener("blur", validateConfirm);

form.addEventListener("submit",(e)=>{
    e.preventDefault();
    success.classList.remove("show");

    let valName = validateName();
    let valCol = validateCollege();
    let valPass = validatePassword();
    let valConf = validateConfirm();

    if(valName && valCol && valPass && valConf){
        success.classList.add("show");
        setTimeout(() => {
            form.submit();
        }, 400);
    }
});
</script>

</body>
</html>
