<?php
// logout.php - User Sign Out
session_start();
session_unset();
session_destroy();
header("Location: login.php");
exit();
?>
