<?php
session_start();
require_once "db.php";

if (isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = strtoupper(trim($_POST["username"]));
    $password = $_POST["password"];

    if (empty($username) || empty($password)) {

        $error = "Please enter College ID and Password.";

    } else {

        $stmt = $conn->prepare(
            "SELECT username, name, password
             FROM users
             WHERE username=?"
        );

        $stmt->bind_param("s", $username);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows == 1) {

            $user = $result->fetch_assoc();

            if (password_verify($password, $user["password"])) {

                $_SESSION["username"] = $user["username"];
                $_SESSION["name"] = $user["name"];

                if ($user["username"] == "ADMIN") {
                    header("Location: admin.php");
                } else {
                    header("Location: index.php");
                }

                exit();

            } else {

                $error = "Incorrect password.";

            }

        } else {

            $error = "College ID not found.";

        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>Login</title>

<link rel="stylesheet" href="css/theme.css">

</head>

<body>

<div class="auth-page-wrapper">

<div class="glass-card">

<h2>Sign In</h2>

<?php if($error!=""){ ?>

<div style="color:red;margin-bottom:15px;">

<?= $error ?>

</div>

<?php } ?>

<form method="POST">

<input
type="text"
name="username"
placeholder="College ID"
required>

<br><br>

<input
type="password"
name="password"
placeholder="Password"
required>

<br><br>

<button type="submit">

Login

</button>

</form>

<br>

<a href="register.php">

Create Account

</a>

|

<a href="forgot_password.php">

Forgot Password

</a>

</div>

</div>

</body>

</html>