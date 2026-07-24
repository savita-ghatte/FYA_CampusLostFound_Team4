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
        // Fetch user from database using prepared statements
        $stmt = $conn->prepare("SELECT username, password FROM users WHERE username = ?");
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
            if ($result && $result->num_rows > 0) {
                $user = $result->fetch_assoc();
                
                // Verify password (supports bcrypt hash or fallback to direct comparison if not hashed,
                // but we hash it on registration/seeding)
                if (password_verify($password, $user['password']) || $password === $user['password']) {
                    // Start session
                    $_SESSION['username'] = $user['username'];
                    
                    // Redirect to index.php
                    header("Location: index.php");
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
            $error_msg = "Database error. Please try again later.";
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

<title>Campus Lost & Found — Sign In</title>

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

/* Navigation */

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

/* Header */

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

/* Login Card */

/* Login Card */

/* Login Card */
.center {
    display:flex;
    justify-content:center;
}

.card {
    width:100%;
    max-width:400px;
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

    width:100%;
    max-width:400px;

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

    width:100%;
    max-width:400px;
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
    margin-top:5px;
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

.label-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 7px;
}

.label-row label {
    margin-bottom: 0;
}

.forgot-link {
    font-size: 13px;
    color: var(--gold-dark);
    text-decoration: none;
    font-weight: 500;
}

.forgot-link:hover {
    text-decoration: underline;
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


<div class="field" id="passwordBox">
<div class="label-row">
    <label>
        Password <span class="req">*</span>
    </label>
    <a href="forgot_password.php" class="forgot-link">Forgot Password?</a>
</div>
<div class="password-wrap">
    <input 
    type="password"
    id="password"
    name="password"
    placeholder="Enter password"
    >
    <button type="button" class="toggle-password" onclick="togglePassword('password', this)" title="Show password" aria-label="Show password">
        <svg viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
    </button>
</div>
<p class="error-msg">
Password must contain at least 6 characters.
</p>
</div>
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


<body>


<div class="wrap">


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
        <a class="active" href="login.php">Sign In</a>
        <a href="register.php">Sign Up</a>
    <?php endif; ?>
</nav>

<header>
<h1>Sign In to Lost & Found</h1>
<p>Use your College ID and password to track your lost or found reports.</p>
</header>

<div class="center">

<div class="card">

<div class="pin"></div>



<header>

<h1>Sign In to Lost & Found</h1>

<p>Use your College ID and password to track your lost or found reports.</p>

</header>



<div class="center">


<div class="card">


<div class="pin"></div>


<header>
<h1>Sign In to Lost & Found</h1>
<p>Use your College ID and password to track your lost or found reports.</p>
</header>

<div class="center">

<div class="card">

<div class="pin"></div>

<div class="badge-strip">
COLLEGE ID ACCESS
</div>


<h2>Sign In</h2>

<p class="sub">
Same credentials as your student/staff portal.
</p>

<?php if ($error_msg): ?>
    <div style="border:1px solid var(--rust); color:var(--rust); padding:12px; border-radius:8px; margin-bottom:20px; font-size:14px; font-weight:500;">
    <div style="background:var(--paper); border:1px solid var(--rust); color:var(--rust); padding:12px; border-radius:8px; margin-bottom:20px; font-size:14px; font-weight:500;">
    <div style="border:1px solid var(--rust); color:var(--rust); padding:12px; border-radius:8px; margin-bottom:20px; font-size:14px; font-weight:500;">
        ⚠ <?php echo htmlspecialchars($error_msg); ?>
    </div>
<?php endif; ?>

<form id="loginForm" method="POST" action="login.php">

<div class="field" id="collegeBox">
<label>
College ID <span class="req">*</span>
</label>

<div class="field" id="collegeBox">

<label>
College ID <span class="req">*</span>
</label>


<div class="field" id="collegeBox">
<label>
College ID <span class="req">*</span>
</label>
<input 
type="text"
id="collegeId"
name="username"
placeholder="Example: CS21B045"
value="<?php echo htmlspecialchars($username); ?>"
>
<p class="error-msg">
Enter a valid College ID (minimum 5 letters/numbers).
</p>
</div>

<div class="field" id="passwordBox">
<label>
Password <span class="req">*</span>
</label>
value="<?php echo htmlspecialchars($username ?? ''); ?>"
>


<p class="error-msg">
Enter a valid College ID (minimum 5 letters/numbers).
</p>

</div>




<div class="field" id="passwordBox">


<label>
Password <span class="req">*</span>
</label>


value="<?php echo htmlspecialchars($username); ?>"
>
<p class="error-msg">
Enter a valid College ID (minimum 5 letters/numbers).
</p>
</div>

<div class="field" id="passwordBox">
<label>
Password <span class="req">*</span>
</label>
<input 
type="password"
id="password"
name="password"
placeholder="Enter password"
>
<p class="error-msg">
Password must contain at least 6 characters.
</p>
</div>



<p class="error-msg">
Password must contain at least 6 characters.
</p>


</div>




<p class="error-msg">
Password must contain at least 6 characters.
</p>
</div>

<button type="submit">
Sign In
</button>

<div class="success-note" id="success">
Signed in successfully — redirecting...


<div class="success-note" id="success">

Signed in successfully — redirecting...

<div class="success-note" id="success">
Signed in successfully — redirecting...
</div>

<p style="text-align:center; font-size:14px; margin-top:20px; color:#555;">
    Don't have an account? <a href="register.php" style="color:var(--gold-dark); text-decoration:none; font-weight:600;">Sign Up</a>
</p>

</form>

</div>

</div>

<footer>
CAMPUS LOST & FOUND • COLLEGE OFFICE
</footer>

</div>

<script>
const form=document.getElementById("loginForm");
const college=document.getElementById("collegeId");
const password=document.getElementById("password");
const collegeBox=document.getElementById("collegeBox");
const passwordBox=document.getElementById("passwordBox");
const success=document.getElementById("success");

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

college.addEventListener("blur",validateCollege);
password.addEventListener("blur",validatePassword);

form.addEventListener("submit",(e)=>{
    e.preventDefault();
    success.classList.remove("show");

    let valCol = validateCollege();
    let valPass = validatePassword();

    if(valCol && valPass){
        success.classList.add("show");
        setTimeout(() => {
            form.submit();
        }, 300);
    }
});
</script>

</body>
</html>

</div>


</div>



<footer>

CAMPUS LOST & FOUND • COLLEGE OFFICE

</footer>



</div>



<script>


const form=document.getElementById("loginForm");

const college=document.getElementById("collegeId");

const password=document.getElementById("password");

const collegeBox=document.getElementById("collegeBox");

const passwordBox=document.getElementById("passwordBox");

const success=document.getElementById("success");



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



college.addEventListener("blur",validateCollege);

password.addEventListener("blur",validatePassword);



form.addEventListener("submit",(e)=>{


e.preventDefault();


success.classList.remove("show");


if(validateCollege() && validatePassword()){


success.classList.add("show");

// Submit the form programmatically to let PHP handle it
setTimeout(() => {
    form.submit();
}, 400);

}


});


</script>


</body>
</html>
</html>
</div>

</div>

<footer>
CAMPUS LOST & FOUND • COLLEGE OFFICE
</footer>

</div>

<script>
const form=document.getElementById("loginForm");
const college=document.getElementById("collegeId");
const password=document.getElementById("password");
const collegeBox=document.getElementById("collegeBox");
const passwordBox=document.getElementById("passwordBox");
const success=document.getElementById("success");

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

college.addEventListener("blur",validateCollege);
password.addEventListener("blur",validatePassword);

form.addEventListener("submit",(e)=>{
    e.preventDefault();
    success.classList.remove("show");

    let valCol = validateCollege();
    let valPass = validatePassword();

    if(valCol && valPass){
        success.classList.add("show");
        setTimeout(() => {
            form.submit();
        }, 300);
    }
});
</script>

</body>
</html>
