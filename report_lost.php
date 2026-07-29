<?php
// report_lost.php - Report a Lost Item
session_start();
include "db.php";

// Redirect to login if not authenticated
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Prevent browser caching for security after logout
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

$success_msg = false;
$error_msg = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_name = trim($_POST['item_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $date_lost = $_POST['date'] ?? '';
    $location = trim($_POST['location'] ?? '');
    $contact = trim($_POST['contact'] ?? '');
    
    // Server-side validation
    if (empty($item_name) || empty($description) || empty($date_lost) || empty($location) || empty($contact)) {
        $error_msg = "Please fill in all required fields.";
    } else {
        $filename = NULL;
        $file_valid = true;

        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['photo'];
            $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
            $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed_exts = ['jpg', 'jpeg', 'png'];

            // Validate image type and size (max 5MB)
            if (!in_array($file['type'], $allowed_types) && !in_array($file_ext, $allowed_exts)) {
                $error_msg = "Only JPG, JPEG, and PNG images are allowed.";
                $file_valid = false;
            } elseif ($file['size'] > 5 * 1024 * 1024) {
                $error_msg = "The image size must be under 5MB.";
                $file_valid = false;
            } else {
                // Generate a unique filename to prevent overwrite
                $filename = uniqid('lost_', true) . '.' . $file_ext;
                $upload_dir = __DIR__ . '/uploads';
                if (!is_dir($upload_dir)) {
                    @mkdir($upload_dir, 0777, true);
                }
                $upload_path = $upload_dir . '/' . $filename;

                if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
                    $error_msg = "Failed to upload image. Please check directory permissions.";
                    $file_valid = false;
                }
            }
        }

        if ($file_valid) {
            // Save to database using prepared statements
            $stmt = $conn->prepare("INSERT INTO lost_items (item_name, description, date_lost, location, contact, image, status) VALUES (?, ?, ?, ?, ?, ?, 'Pending')");
            if ($stmt) {
                $stmt->bind_param("ssssss", $item_name, $description, $date_lost, $location, $contact, $filename);
                if ($stmt->execute()) {
                    $success_msg = true;
                } else {
                    $error_msg = "Database query failed. Please try again.";
                }
                $stmt->close();
            } else {
                $error_msg = "Database statement preparation failed: " . $conn->error;
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
    <title>Report Lost Item | CampusConnect - Zeal College</title>
    
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
            <a href="report_lost.php" class="nav-link active">
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
            <a href="profile.php" class="nav-link">
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
                    <?php echo strtoupper(substr($_SESSION['name'] ?? $_SESSION['username'], 0, 1)); ?>
                </div>
                <div class="user-details">
                    <span class="user-name"><?php echo htmlspecialchars($_SESSION['name'] ?? $_SESSION['username']); ?></span>
                    <span class="user-role"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                </div>
            </div>
            <a href="logout.php" class="sidebar-btn sidebar-btn-danger">
                <i class="fa-solid fa-right-from-bracket"></i> Logout
            </a>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="main-content">
        <!-- Top Bar -->
        <header class="top-bar">
            <div>
                <button class="mobile-toggle" onclick="toggleSidebar()">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <span class="top-bar-title">Report Lost Item</span>
            </div>
            <div class="top-bar-subtitle">
                <i class="fa-solid fa-location-dot"></i> Zeal College of Engineering and Research, Narhe, Pune
            </div>
        </header>

        <div class="page-container">
            <div class="form-card-container">
                <div class="card">
                    <h2 class="card-title">
                        <i class="fa-solid fa-circle-plus" style="color:var(--primary);"></i> Report Misplaced Item
                    </h2>
                    <p class="card-subtitle">Provide details about the item you lost on Zeal campus to alert the community and office.</p>

                    <?php if ($success_msg): ?>
                        <div class="alert alert-success">
                            <i class="fa-solid fa-circle-check"></i>
                            <span>Your lost item report has been filed successfully! <a href="items.php">Browse items board</a> to track updates.</span>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($error_msg)): ?>
                        <div class="alert alert-danger">
                            <i class="fa-solid fa-circle-exclamation"></i>
                            <span><?php echo htmlspecialchars($error_msg); ?></span>
                        </div>
                    <?php endif; ?>

                    <form action="report_lost.php" method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="item_name" class="form-label">Item Name</label>
                            <div class="input-icon-group">
                                <i class="fa-solid fa-tag"></i>
                                <input type="text" id="item_name" name="item_name" class="form-control" placeholder="e.g. Grey Backpack or Casio Calculator" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="description" class="form-label">Detailed Description</label>
                            <div class="input-icon-group">
                                <i class="fa-solid fa-align-left" style="top:18px;"></i>
                                <textarea id="description" name="description" class="form-control" placeholder="Describe color, brand, stickers, unique marks, or contents..." required></textarea>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="date" class="form-label">Date Lost</label>
                            <div class="input-icon-group">
                                <i class="fa-solid fa-calendar-days"></i>
                                <input type="date" id="date" name="date" class="form-control" required value="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="location" class="form-label">Location Lost on Campus</label>
                            <div class="input-icon-group">
                                <i class="fa-solid fa-location-dot"></i>
                                <input type="text" id="location" name="location" class="form-control" placeholder="e.g. Central Library, Main Gate, Canteen, CS Lab" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="photo" class="form-label">Upload Image (Optional)</label>
                            <div class="input-icon-group">
                                <i class="fa-solid fa-cloud-arrow-up"></i>
                                <input type="file" id="photo" name="photo" class="form-control" accept="image/jpeg,image/png,image/jpg" style="padding-top:10px;">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="contact" class="form-label">Contact Email / Phone Number</label>
                            <div class="input-icon-group">
                                <i class="fa-solid fa-phone"></i>
                                <input type="text" id="contact" name="contact" class="form-control" placeholder="e.g. cs21b045@zeal.edu.in or 9876543210" value="<?php echo htmlspecialchars($_SESSION['email'] ?? $_SESSION['username'] ?? ''); ?>" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block" style="padding:14px; margin-top: 10px;">
                            <i class="fa-solid fa-paper-plane"></i> Submit Lost Report
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