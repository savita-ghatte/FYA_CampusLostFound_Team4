<?php
// profile.php - Edit Profile Page
session_start();
include "db.php";

// Redirect to login if not logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Prevent browser caching for security after logout
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

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
                        $_SESSION['name'] = $new_name;
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
                        $_SESSION['name'] = $new_name;
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
    <title>Edit Profile | CampusConnect - Zeal College</title>
    
    <!-- Font Awesome 6 Icons & Google Fonts -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/theme.css">
</head>
<body>

<div class="app-container">
    <!-- Fixed Left Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo-icon">
                <i class="fa-solid fa-building-columns"></i>
            </div>
            <div>
                <div class="sidebar-brand-name">CampusConnect</div>
                <div class="sidebar-brand-sub">Zeal College, Pune</div>
            </div>
        </div>

        <nav class="sidebar-menu">
            <div class="sidebar-section-label">Main Menu</div>
            <a href="index.php" class="nav-link">
                <i class="fa-solid fa-chart-pie"></i> Dashboard
            </a>
            <a href="report_lost.php" class="nav-link">
                <i class="fa-solid fa-circle-plus"></i> Report Lost
            </a>
            <a href="report_found.php" class="nav-link">
                <i class="fa-solid fa-hand-holding-hand"></i> Report Found
            </a>
            <a href="items.php" class="nav-link">
                <i class="fa-solid fa-boxes-stacked"></i> Browse Items
            </a>
            <a href="claims.php" class="nav-link">
                <i class="fa-solid fa-clipboard-check"></i> Claims
            </a>
            <a href="profile.php" class="nav-link active">
                <i class="fa-solid fa-user-gear"></i> Profile
            </a>
            <?php if (isset($_SESSION['username']) && $_SESSION['username'] === 'admin'): ?>
                <a href="admin.php" class="nav-link">
                    <i class="fa-solid fa-shield-halved"></i> Admin Portal
                </a>
            <?php endif; ?>
        </nav>

        <div class="sidebar-footer">
            <div class="user-sidebar-card">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($name ?: $username, 0, 1)); ?>
                </div>
                <div class="user-details">
                    <span class="user-name"><?php echo htmlspecialchars($name ?: $username); ?></span>
                    <span class="user-role"><?php echo htmlspecialchars($username); ?></span>
                </div>
            </div>
            <a href="logout.php" class="sidebar-btn sidebar-btn-danger">
                <i class="fa-solid fa-right-from-bracket"></i> Logout
            </a>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="main-content">
        <header class="top-bar">
            <div>
                <button class="mobile-toggle" onclick="toggleSidebar()">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <span class="top-bar-title">Manage Account Profile</span>
            </div>
            <div class="top-bar-subtitle">
                <i class="fa-solid fa-location-dot"></i> Zeal College of Engineering and Research, Narhe, Pune
            </div>
        </header>

        <div class="page-container">
            <div class="form-card-container">
                <div class="card">
                    <h2 class="card-title">
                        <i class="fa-solid fa-user-gear" style="color:var(--primary);"></i> Edit Student Profile
                    </h2>
                    <p class="card-subtitle">Update your full name or change your account password.</p>

                    <?php if (!empty($success_msg)): ?>
                        <div class="alert alert-success">
                            <i class="fa-solid fa-circle-check"></i>
                            <span><?php echo htmlspecialchars($success_msg); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($error_msg)): ?>
                        <div class="alert alert-danger">
                            <i class="fa-solid fa-circle-exclamation"></i>
                            <span><?php echo htmlspecialchars($error_msg); ?></span>
                        </div>
                    <?php endif; ?>

                    <form action="profile.php" method="POST">
                        <div class="form-group">
                            <label class="form-label">College ID / Username (Read-Only)</label>
                            <div class="input-icon-group">
                                <i class="fa-solid fa-id-card"></i>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($username); ?>" disabled style="background:#f1f5f9; cursor:not-allowed;">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="name" class="form-label">Full Name</label>
                            <div class="input-icon-group">
                                <i class="fa-solid fa-user"></i>
                                <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($name); ?>" required>
                            </div>
                        </div>

                        <hr style="border:none; border-top:1px solid var(--border); margin:24px 0;">
                        <h4 style="font-family:'Poppins',sans-serif; font-size:16px; font-weight:700; margin-bottom:16px; color:var(--text-dark);">
                            <i class="fa-solid fa-key"></i> Change Password (Optional)
                        </h4>

                        <div class="form-group">
                            <label for="password" class="form-label">New Password</label>
                            <div class="input-icon-group">
                                <i class="fa-solid fa-lock"></i>
                                <input type="password" id="password" name="password" class="form-control" placeholder="Leave blank to keep current password">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <div class="input-icon-group">
                                <i class="fa-solid fa-shield-halved"></i>
                                <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Re-enter new password">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block" style="padding:14px; margin-top: 10px;">
                            <i class="fa-solid fa-floppy-disk"></i> Save Profile Changes
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <footer class="footer-campus">
            <div class="footer-brand">CampusConnect Lost & Found</div>
            <div class="footer-college">Zeal College of Engineering and Research, Narhe, Pune</div>
            <div class="footer-credits">
                Developed by FYA Team 4 &bull; &copy; 2026 All Rights Reserved
            </div>
        </footer>
    </div>
</div>

<script>
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
}
</script>
</body>
</html>
<?php
// profile.php - Edit Profile Page
session_start();
include "db.php";

// Redirect to login if not logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Prevent browser caching for security after logout
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

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
                        $_SESSION['name'] = $new_name;
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
                        $_SESSION['name'] = $new_name;
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
    <title>Edit Profile | CampusConnect - Zeal College</title>
    
    <!-- Font Awesome 6 Icons & Google Fonts -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/theme.css">
</head>
<body>

<div class="app-container">
    <!-- Fixed Left Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo-icon">
                <i class="fa-solid fa-building-columns"></i>
            </div>
            <div>
                <div class="sidebar-brand-name">CampusConnect</div>
                <div class="sidebar-brand-sub">Zeal College, Pune</div>
            </div>
        </div>

        <nav class="sidebar-menu">
            <div class="sidebar-section-label">Main Menu</div>
            <a href="index.php" class="nav-link">
                <i class="fa-solid fa-chart-pie"></i> Dashboard
            </a>
            <a href="report_lost.php" class="nav-link">
                <i class="fa-solid fa-circle-plus"></i> Report Lost
            </a>
            <a href="report_found.php" class="nav-link">
                <i class="fa-solid fa-hand-holding-hand"></i> Report Found
            </a>
            <a href="items.php" class="nav-link">
                <i class="fa-solid fa-boxes-stacked"></i> Browse Items
            </a>
            <a href="claims.php" class="nav-link">
                <i class="fa-solid fa-clipboard-check"></i> Claims
            </a>
            <a href="profile.php" class="nav-link active">
                <i class="fa-solid fa-user-gear"></i> Profile
            </a>
            <?php if (isset($_SESSION['username']) && $_SESSION['username'] === 'admin'): ?>
                <a href="admin.php" class="nav-link">
                    <i class="fa-solid fa-shield-halved"></i> Admin Portal
                </a>
            <?php endif; ?>
        </nav>

        <div class="sidebar-footer">
            <div class="user-sidebar-card">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($name ?: $username, 0, 1)); ?>
                </div>
                <div class="user-details">
                    <span class="user-name"><?php echo htmlspecialchars($name ?: $username); ?></span>
                    <span class="user-role"><?php echo htmlspecialchars($username); ?></span>
                </div>
            </div>
            <a href="logout.php" class="sidebar-btn sidebar-btn-danger">
                <i class="fa-solid fa-right-from-bracket"></i> Logout
            </a>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="main-content">
        <header class="top-bar">
            <div>
                <button class="mobile-toggle" onclick="toggleSidebar()">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <span class="top-bar-title">Manage Account Profile</span>
            </div>
            <div class="top-bar-subtitle">
                <i class="fa-solid fa-location-dot"></i> Zeal College of Engineering and Research, Narhe, Pune
            </div>
        </header>

        <div class="page-container">
            <div class="form-card-container">
                <div class="card">
                    <h2 class="card-title">
                        <i class="fa-solid fa-user-gear" style="color:var(--primary);"></i> Edit Student Profile
                    </h2>
                    <p class="card-subtitle">Update your full name or change your account password.</p>

                    <?php if (!empty($success_msg)): ?>
                        <div class="alert alert-success">
                            <i class="fa-solid fa-circle-check"></i>
                            <span><?php echo htmlspecialchars($success_msg); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($error_msg)): ?>
                        <div class="alert alert-danger">
                            <i class="fa-solid fa-circle-exclamation"></i>
                            <span><?php echo htmlspecialchars($error_msg); ?></span>
                        </div>
                    <?php endif; ?>

                    <form action="profile.php" method="POST">
                        <div class="form-group">
                            <label class="form-label">College ID / Username (Read-Only)</label>
                            <div class="input-icon-group">
                                <i class="fa-solid fa-id-card"></i>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($username); ?>" disabled style="background:#f1f5f9; cursor:not-allowed;">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="name" class="form-label">Full Name</label>
                            <div class="input-icon-group">
                                <i class="fa-solid fa-user"></i>
                                <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($name); ?>" required>
                            </div>
                        </div>

                        <hr style="border:none; border-top:1px solid var(--border); margin:24px 0;">
                        <h4 style="font-family:'Poppins',sans-serif; font-size:16px; font-weight:700; margin-bottom:16px; color:var(--text-dark);">
                            <i class="fa-solid fa-key"></i> Change Password (Optional)
                        </h4>

                        <div class="form-group">
                            <label for="password" class="form-label">New Password</label>
                            <div class="input-icon-group">
                                <i class="fa-solid fa-lock"></i>
                                <input type="password" id="password" name="password" class="form-control" placeholder="Leave blank to keep current password">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <div class="input-icon-group">
                                <i class="fa-solid fa-shield-halved"></i>
                                <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Re-enter new password">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block" style="padding:14px; margin-top: 10px;">
                            <i class="fa-solid fa-floppy-disk"></i> Save Profile Changes
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <footer class="footer-campus">
            <div class="footer-brand">CampusConnect Lost & Found</div>
            <div class="footer-college">Zeal College of Engineering and Research, Narhe, Pune</div>
            <div class="footer-credits">
                Developed by FYA Team 4 &bull; &copy; 2026 All Rights Reserved
            </div>
        </footer>
    </div>
</div>

<script>
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
}
</script>
</body>
</html>
