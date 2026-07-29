<?php
// admin.php - Administrative Dashboard
session_start();
include "db.php";

// 1. Authenticate Admin (only users logged in as 'admin')
if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Prevent browser caching for security after logout
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

// 2. Handle AJAX Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];

    if ($action === 'update_status') {
        $id = intval($_POST['id'] ?? 0);
        $type = $_POST['type'] ?? '';
        $status = $_POST['status'] ?? '';

        if ($id > 0 && ($type === 'lost' || $type === 'found') && in_array($status, ['Pending', 'Matched', 'Claimed', 'Returned'])) {
            $table = ($type === 'lost') ? 'lost_items' : 'found_items';
            $stmt = $conn->prepare("UPDATE $table SET status = ? WHERE item_id = ?");
            if ($stmt) {
                $stmt->bind_param("si", $status, $id);
                if ($stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => 'Status updated successfully.']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Database execution failed.']);
                }
                $stmt->close();
            } else {
                echo json_encode(['success' => false, 'message' => 'Statement preparation failed.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid parameters.']);
        }
        exit();
    }

    if ($action === 'resolve_claim') {
        $claim_id = intval($_POST['claim_id'] ?? 0);
        $status = $_POST['status'] ?? ''; // Approved or Rejected

        if ($claim_id > 0 && in_array($status, ['Approved', 'Rejected'])) {
            // Update claim status
            $stmt = $conn->prepare("UPDATE claims SET claim_status = ? WHERE claim_id = ?");
            if ($stmt) {
                $stmt->bind_param("si", $status, $claim_id);
                if ($stmt->execute()) {
                    // If approved, update associated found item status to 'Claimed' or 'Returned'
                    if ($status === 'Approved') {
                        $stmt_find = $conn->prepare("SELECT item_id FROM claims WHERE claim_id = ?");
                        if ($stmt_find) {
                            $stmt_find->bind_param("i", $claim_id);
                            $stmt_find->execute();
                            $res = $stmt_find->get_result();
                            if ($res && $row = $res->fetch_assoc()) {
                                $found_item_id = $row['item_id'];
                                $stmt_up = $conn->prepare("UPDATE found_items SET status = 'Claimed' WHERE item_id = ?");
                                if ($stmt_up) {
                                    $stmt_up->bind_param("i", $found_item_id);
                                    $stmt_up->execute();
                                    $stmt_up->close();
                                }
                            }
                            $stmt_find->close();
                        }
                    }
                    echo json_encode(['success' => true, 'message' => 'Claim status updated.']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Database execution failed.']);
                }
                $stmt->close();
            } else {
                echo json_encode(['success' => false, 'message' => 'Statement preparation failed.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid parameters.']);
        }
        exit();
    }
}

// 3. Fetch Statistics
$total_reports = 0;
$pending_reports = 0;
$returned_reports = 0;
$claims_to_verify = 0;

// Count Lost Reports
$lost_stats = $conn->query("SELECT 
    COUNT(*) as total, 
    SUM(CASE WHEN status='Pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status='Returned' THEN 1 ELSE 0 END) as returned 
    FROM lost_items");
if ($lost_stats && $lost_row = $lost_stats->fetch_assoc()) {
    $total_reports += (int)$lost_row['total'];
    $pending_reports += (int)$lost_row['pending'];
    $returned_reports += (int)$lost_row['returned'];
}

// Count Found Reports
$found_stats = $conn->query("SELECT 
    COUNT(*) as total, 
    SUM(CASE WHEN status='Pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status='Returned' THEN 1 ELSE 0 END) as returned 
    FROM found_items");
if ($found_stats && $found_row = $found_stats->fetch_assoc()) {
    $total_reports += (int)$found_row['total'];
    $pending_reports += (int)$found_row['pending'];
    $returned_reports += (int)$found_row['returned'];
}

// Count Claims to Verify
$claims_stat = $conn->query("SELECT COUNT(*) as count FROM claims WHERE claim_status='Pending'");
if ($claims_stat && $claim_row = $claims_stat->fetch_assoc()) {
    $claims_to_verify = (int)$claim_row['count'];
}

// 4. Fetch All Reports for the list table
$db_reports = [];

// Lost items
$lost_items_query = $conn->query("SELECT item_id, item_name, contact, location, date_lost, status FROM lost_items ORDER BY date_lost DESC");
if ($lost_items_query) {
    while ($row = $lost_items_query->fetch_assoc()) {
        $db_reports[] = [
            'id' => (int)$row['item_id'],
            'item' => $row['item_name'],
            'type' => 'lost',
            'by' => $row['contact'],
            'location' => $row['location'],
            'date' => date('M d', strtotime($row['date_lost'])),
            'status' => $row['status']
        ];
    }
}

// Found items
$found_items_query = $conn->query("SELECT item_id, item_name, contact, location, date_found, status FROM found_items ORDER BY date_found DESC");
if ($found_items_query) {
    while ($row = $found_items_query->fetch_assoc()) {
        $db_reports[] = [
            'id' => (int)$row['item_id'],
            'item' => $row['item_name'],
            'type' => 'found',
            'by' => $row['contact'],
            'location' => $row['location'],
            'date' => date('M d', strtotime($row['date_found'])),
            'status' => $row['status']
        ];
    }
}

// 5. Fetch Pending & All Claims for verification list
$db_claims = [];
$claims_query = $conn->query("SELECT c.claim_id, c.claimant, c.colour, c.distinguishing_marks, c.image, c.claim_status, f.item_name, f.contact FROM claims c JOIN found_items f ON c.item_id = f.item_id ORDER BY c.claim_id DESC");
if ($claims_query) {
    while ($row = $claims_query->fetch_assoc()) {
        $db_claims[] = [
            'id' => (int)$row['claim_id'],
            'item' => $row['item_name'],
            'by' => !empty($row['claimant']) ? $row['claimant'] : $row['contact'],
            'submitted' => 'Claim ' . $row['claim_id'],
            'color' => $row['colour'],
            'contents' => $row['distinguishing_marks'],
            'image' => $row['image'],
            'status' => $row['claim_status']
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | CampusConnect - Zeal College</title>
    
    <!-- Font Awesome 6 Icons & Google Fonts -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/theme.css">
    <style>
        .admin-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        .admin-table th, .admin-table td {
            padding: 14px 16px;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }
        .admin-table th {
            background-color: var(--bg-main);
            color: var(--text-dark);
            font-weight: 600;
        }
        .admin-table tr:hover {
            background-color: #f1f5f9;
        }
        .status-select {
            padding: 6px 12px;
            border-radius: 6px;
            border: 1px solid var(--border);
            font-size: 13px;
        }
    </style>
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
            <a href="profile.php" class="nav-link">
                <i class="fa-solid fa-user-gear"></i> Profile
            </a>
            <a href="admin.php" class="nav-link active">
                <i class="fa-solid fa-shield-halved"></i> Admin Portal
            </a>
        </nav>

        <div class="sidebar-footer">
            <div class="user-sidebar-card">
                <div class="user-avatar" style="background:#dc2626;">A</div>
                <div class="user-details">
                    <span class="user-name">Administrator</span>
                    <span class="user-role">admin</span>
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
                <span class="top-bar-title">Admin Management Portal</span>
            </div>
            <div class="top-bar-subtitle">
                <i class="fa-solid fa-location-dot"></i> Zeal College of Engineering and Research, Narhe, Pune
            </div>
        </header>

        <div class="page-container">
            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon stat-icon-blue"><i class="fa-solid fa-boxes-stacked"></i></div>
                    <div>
                        <div class="stat-val"><?php echo $total_reports; ?></div>
                        <div class="stat-label">Total Reports</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon stat-icon-amber"><i class="fa-solid fa-clock"></i></div>
                    <div>
                        <div class="stat-val"><?php echo $pending_reports; ?></div>
                        <div class="stat-label">Pending Review</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon stat-icon-green"><i class="fa-solid fa-circle-check"></i></div>
                    <div>
                        <div class="stat-val"><?php echo $returned_reports; ?></div>
                        <div class="stat-label">Items Returned</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon stat-icon-purple"><i class="fa-solid fa-id-card-clip"></i></div>
                    <div>
                        <div class="stat-val"><?php echo $claims_to_verify; ?></div>
                        <div class="stat-label">Claims to Verify</div>
                    </div>
                </div>
            </div>

            <!-- Manage Item Statuses -->
            <div class="card" style="margin-bottom: 32px;">
                <h3 class="card-title"><i class="fa-solid fa-list-check"></i> Manage Item Statuses</h3>
                <p class="card-subtitle">Review lost & found item reports and update their status.</p>

                <div style="overflow-x:auto;">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Item Name</th>
                                <th>Type</th>
                                <th>Reported By</th>
                                <th>Location</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($db_reports as $report): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($report['item']); ?></strong></td>
                                    <td><span class="badge <?php echo $report['type'] === 'lost' ? 'badge-lost' : 'badge-found'; ?>"><?php echo strtoupper($report['type']); ?></span></td>
                                    <td><?php echo htmlspecialchars($report['by']); ?></td>
                                    <td><?php echo htmlspecialchars($report['location']); ?></td>
                                    <td><?php echo $report['date']; ?></td>
                                    <td>
                                        <select class="status-select" onchange="updateItemStatus(<?php echo $report['id']; ?>, '<?php echo $report['type']; ?>', this.value)">
                                            <option value="Pending" <?php echo $report['status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="Matched" <?php echo $report['status'] === 'Matched' ? 'selected' : ''; ?>>Matched</option>
                                            <option value="Claimed" <?php echo $report['status'] === 'Claimed' ? 'selected' : ''; ?>>Claimed</option>
                                            <option value="Returned" <?php echo $report['status'] === 'Returned' ? 'selected' : ''; ?>>Returned</option>
                                        </select>
                                    </td>
                                    <td><span style="font-size:12px; color:var(--text-muted);">Auto-saved</span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Claims Verification -->
            <div class="card">
                <h3 class="card-title"><i class="fa-solid fa-file-signature"></i> Student Ownership Claims</h3>
                <p class="card-subtitle">Approve or reject verification claims submitted by students.</p>

                <div style="overflow-x:auto;">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Claim ID</th>
                                <th>Found Item</th>
                                <th>Claimant</th>
                                <th>Color Description</th>
                                <th>Distinguishing Marks</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($db_claims as $claim): ?>
                                <tr>
                                    <td>#<?php echo $claim['id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($claim['item']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($claim['by']); ?></td>
                                    <td><?php echo htmlspecialchars($claim['color']); ?></td>
                                    <td><?php echo htmlspecialchars($claim['contents']); ?></td>
                                    <td><span class="badge <?php echo $claim['status'] === 'Approved' ? 'badge-found' : ($claim['status'] === 'Rejected' ? 'badge-lost' : 'badge-returned'); ?>"><?php echo strtoupper($claim['status']); ?></span></td>
                                    <td>
                                        <?php if ($claim['status'] === 'Pending'): ?>
                                            <button class="btn btn-success" style="padding:6px 12px; font-size:12px;" onclick="resolveClaim(<?php echo $claim['id']; ?>, 'Approved')">Approve</button>
                                            <button class="btn btn-outline" style="padding:6px 12px; font-size:12px; border-color:var(--danger); color:var(--danger);" onclick="resolveClaim(<?php echo $claim['id']; ?>, 'Rejected')">Reject</button>
                                        <?php else: ?>
                                            <span style="font-size:13px; color:var(--text-muted);"><?php echo $claim['status']; ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
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
function updateItemStatus(id, type, status) {
    const formData = new FormData();
    formData.append('action', 'update_status');
    formData.append('id', id);
    formData.append('type', type);
    formData.append('status', status);

    fetch('admin.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Failed: ' + data.message);
        }
    });
}

function resolveClaim(claim_id, status) {
    const formData = new FormData();
    formData.append('action', 'resolve_claim');
    formData.append('claim_id', claim_id);
    formData.append('status', status);

    fetch('admin.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Failed: ' + data.message);
        }
    });
}

function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
}
</script>
</body>
</html>