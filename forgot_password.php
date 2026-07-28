<?php
session_start();
require_once "db.php";

if (isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = strtoupper(trim($_POST["username"]));
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    $confirm = $_POST["confirm_password"];

    if (
        empty($username) ||
        empty($email) ||
        empty($password) ||
        empty($confirm)
    ) {

        $error = "Please fill all fields.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Invalid email address.";

    } elseif ($password != $confirm) {

        $error = "Passwords do not match.";

    } elseif (strlen($password) < 6) {

        $error = "Password must be at least 6 characters.";

    } else {

        $stmt = $conn->prepare(
            "SELECT username
             FROM users
             WHERE username=? AND email=?"
        );

        $stmt->bind_param(
            "ss",
            $username,
            $email
        );

        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows == 1) {

            $hash = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $update = $conn->prepare(
                "UPDATE users
                 SET password=?
                 WHERE username=?"
            );

            $update->bind_param(
                "ss",
                $hash,
                $username
            );

            if ($update->execute()) {

                $success = "Password updated successfully.";

            } else {

                $error = "Unable to update password.";

            }

            $update->close();

        } else {

            $error = "Invalid College ID or Email.";

        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html>

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1">

<title>Forgot Password</title>

<link rel="stylesheet" href="css/theme.css">

</head>

<body>

<div class="auth-page-wrapper">

<div class="glass-card">

<h2>Reset Password</h2>

<?php
if($error!=""){
echo "<div style='color:red;'>$error</div>";
}
?>

<?php
if($success!=""){
echo "<div style='color:green;'>$success</div>";
}
?>

<form method="POST">

<input
type="text"
name="username"
placeholder="College ID"
required>

<br><br>

<input
type="email"
name="email"
placeholder="Registered Email"
required>

<br><br>

<input
type="password"
name="password"
placeholder="New Password"
required>

<br><br>

<input
type="password"
name="confirm_password"
placeholder="Confirm Password"
required>

<br><br>

<button type="submit">

Reset Password

</button>

</form>

<br>

<a href="login.php">

Back to Login

</a>

</div>

</div>

</body>

</html>