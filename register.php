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

    $name = trim($_POST["name"]);
    $username = strtoupper(trim($_POST["username"]));
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    $confirm = $_POST["confirm_password"];

    // Validation
    if (
        empty($name) ||
        empty($username) ||
        empty($email) ||
        empty($password) ||
        empty($confirm)
    ) {

        $error = "Please fill all fields.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Invalid email address.";

    } elseif (!preg_match('/^[A-Za-z0-9]{5,20}$/', $username)) {

        $error = "College ID must contain 5–20 letters or numbers.";

    } elseif (strlen($password) < 6) {

        $error = "Password must contain at least 6 characters.";

    } elseif ($password != $confirm) {

        $error = "Passwords do not match.";

    } else {

        $stmt = $conn->prepare(
            "SELECT username FROM users
             WHERE username=? OR email=?"
        );

        $stmt->bind_param(
            "ss",
            $username,
            $email
        );

        $stmt->execute();

        $stmt->store_result();

        if ($stmt->num_rows > 0) {

            $error = "College ID or Email already exists.";

        } else {

            $hash = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $insert = $conn->prepare(
                "INSERT INTO users
                (username,name,email,password)
                VALUES (?,?,?,?)"
            );

            $insert->bind_param(
                "ssss",
                $username,
                $name,
                $email,
                $hash
            );

            if ($insert->execute()) {

                $_SESSION["username"] = $username;
                $_SESSION["name"] = $name;

                header("Location:index.php");
                exit();

            } else {

                $error = "Registration failed.";

            }

            $insert->close();
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html>
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Register</title>

<link rel="stylesheet" href="style.css">

</head>

<body>

<div class="container">

<h2>Create Account</h2>

<?php if($error!=""){ ?>

<p style="color:red;">
<?= $error ?>
</p>

<?php } ?>

<form method="POST">

<input
type="text"
name="name"
placeholder="Full Name"
required>

<input
type="text"
name="username"
placeholder="College ID"
required>

<input
type="email"
name="email"
placeholder="Email"
required>

<input
type="password"
name="password"
placeholder="Password"
required>

<input
type="password"
name="confirm_password"
placeholder="Confirm Password"
required>

<button type="submit">
Register
</button>

</form>

<p>

Already have an account?

<a href="login.php">

Login

</a>

</p>

</div>

</body>
</html>