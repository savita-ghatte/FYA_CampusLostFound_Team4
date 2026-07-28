<?php
// claims.php - Submit a Claim for an Item
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

// Get item details
$item_id = intval($_GET['item_id'] ?? $_POST['item_id'] ?? 0);
$item_name = $_GET['item'] ?? '';

// Fetch available found items for claim selection
$found_items_list = [];
$found_res = $conn->query("SELECT item_id, item_name, location FROM found_items WHERE status != 'Returned' ORDER BY item_id DESC");
if ($found_res) {
    while ($f_row = $found_res->fetch_assoc()) {
        $found_items_list[] = $f_row;
    }
}

// Pre-select first item if item_id not provided in URL
if ($item_id <= 0 && !empty($found_items_list)) {
    $item_id = intval($found_items_list[0]['item_id']);
    $item_name = $found_items_list[0]['item_name'];
}

if ($item_id > 0) {
    $stmt = $conn->prepare("SELECT item_name FROM found_items WHERE item_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $item_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $row = $result->fetch_assoc()) {
            $item_name = $row['item_name'];
        }
        $stmt->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_id = intval($_POST['item_id'] ?? 0);
    $colour = trim($_POST['colorAnswer'] ?? '');
    $distinguishing_marks = trim($_POST['contentsAnswer'] ?? '');
    $claimant = $_SESSION['name'] ?? $_SESSION['username'];
    
    // Server-side validation
    if ($item_id <= 0) {
        $error_msg = "Please select a valid item to claim.";
    } elseif (empty($colour) || empty($distinguishing_marks)) {
        $error_msg = "Please fill in all required verification fields.";
    } elseif (!isset($_FILES['proof']) || $_FILES['proof']['error'] !== UPLOAD_ERR_OK) {
        $error_msg = "Please upload photo proof / purchase receipt.";
    } else {
        $file = $_FILES['proof'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed_exts = ['jpg', 'jpeg', 'png'];
        
        // Validate image type and size (max 5MB)
        if (!in_array($file['type'], $allowed_types) && !in_array($file_ext, $allowed_exts)) {
            $error_msg = "Only JPG, JPEG, and PNG images are allowed.";
        } elseif ($file['size'] > 5 * 1024 * 1024) {
            $error_msg = "The image size must be under 5MB.";
        } else {
            // Generate a unique filename to prevent overwrite
            $filename = uniqid('claim_', true) . '.' . $file_ext;
            $upload_dir = __DIR__ . '/uploads';
            if (!is_dir($upload_dir)) {
                @mkdir($upload_dir, 0777, true);
            }
            $upload_path = $upload_dir . '/' . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                // Save to claims table with claimant info
                $stmt = $conn->prepare("INSERT INTO claims (item_id, claimant, colour, distinguishing_marks, image, claim_status) VALUES (?, ?, ?, ?, ?, 'Pending')");
                if ($stmt) {
                    $stmt->bind_param("issss", $item_id, $claimant, $colour, $distinguishing_marks, $filename);
                    if ($stmt->execute()) {
                        $success_msg = true;
                    } else {
                        $error_msg = "Database insert failed: " . $conn->error;
                    }
                    $stmt->close();
                } else {
                    $error_msg = "Database statement preparation failed: " . $conn->error;
                }
            } else {
                $error_msg = "Failed to upload image. Please check directory permissions.";
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
    <title>Claim Item Ownership | CampusConnect - Zeal College</title>
    
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
            <a href="claims.php" class="nav-link active">
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
        <header class="top-bar">
            <div>
                <button class="mobile-toggle" onclick="toggleSidebar()">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <span class="top-bar-title">Claim Item Ownership</span>
            </div>
            <div class="top-bar-subtitle">
                <i class="fa-solid fa-location-dot"></i> Zeal College of Engineering and Research, Narhe, Pune
            </div>
        </header>

        <div class="page-container">
            <div class="form-card-container">
                <div class="card">
                    <h2 class="card-title">
                        <i class="fa-solid fa-clipboard-check" style="color:var(--success);"></i> Submit Ownership Claim
                    </h2>
                    <p class="card-subtitle">Provide verification details to verify your ownership of the found item before collecting from Zeal office.</p>

                    <?php if ($success_msg): ?>
                        <div class="alert alert-success">
                            <i class="fa-solid fa-circle-check"></i>
                            <span>Claim submitted successfully! The Zeal office will verify your details and notify you once approved.</span>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($error_msg)): ?>
                        <div class="alert alert-danger">
                            <i class="fa-solid fa-circle-exclamation"></i>
                            <span><?php echo htmlspecialchars($error_msg); ?></span>
                        </div>
                    <?php endif; ?>

                    <form action="claims.php" method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="item_id" class="form-label">Select Found Item</label>
                            <div class="input-icon-group">
                                <i class="fa-solid fa-box-open"></i>
                                <select id="item_id" name="item_id" class="form-control" required style="padding-left:42px;">
                                    <?php if (empty($found_items_list)): ?>
                                        <option value="">No found items currently open for claims</option>
                                    <?php else: ?>
                                        <?php foreach ($found_items_list as $f_item): ?>
                                            <option value="<?php echo $f_item['item_id']; ?>" <?php echo ($item_id == $f_item['item_id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($f_item['item_name']); ?> (Found at: <?php echo htmlspecialchars($f_item['location']); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="colorAnswer" class="form-label">Specific Color / Shade</label>
                            <div class="input-icon-group">
                                <i class="fa-solid fa-palette"></i>
                                <input type="text" id="colorAnswer" name="colorAnswer" class="form-control" placeholder="e.g. Metallic sky-blue cap or Navy blue" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="contentsAnswer" class="form-label">Distinguishing Marks, Serial Numbers, or Contents</label>
                            <div class="input-icon-group">
                                <i class="fa-solid fa-list-check" style="top:18px;"></i>
                                <textarea id="contentsAnswer" name="contentsAnswer" class="form-control" placeholder="Mention stickers, keychains, scratch marks, room numbers, or contents inside..." required></textarea>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="proof" class="form-label">Upload Proof Photo / Purchase Receipt (Required)</label>
                            <div class="input-icon-group">
                                <i class="fa-solid fa-file-shield"></i>
                                <input type="file" id="proof" name="proof" class="form-control" accept="image/jpeg,image/png,image/jpg" style="padding-top:10px;" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-success btn-block" style="padding:14px; margin-top: 10px;">
                            <i class="fa-solid fa-paper-plane"></i> Submit Verification Claim
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