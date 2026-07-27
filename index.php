<?php
// index.php - Homepage & Dashboard for CampusConnect Lost & Found
session_start();
include "db.php";

// Set no-cache headers
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Fetch Dashboard & System Stats
$count_lost = 0;
$count_found = 0;
$count_pending_claims = 0;
$count_total_claims = 0;

$lost_res = $conn->query("SELECT COUNT(*) as count FROM lost_items");
if ($lost_res && $row = $lost_res->fetch_assoc()) {
    $count_lost = (int)$row['count'];
}

$found_res = $conn->query("SELECT COUNT(*) as count FROM found_items");
if ($found_res && $row = $found_res->fetch_assoc()) {
    $count_found = (int)$row['count'];
}

$pending_claims_res = $conn->query("SELECT COUNT(*) as count FROM claims WHERE claim_status='Pending'");
if ($pending_claims_res && $row = $pending_claims_res->fetch_assoc()) {
    $count_pending_claims = (int)$row['count'];
}

$total_claims_res = $conn->query("SELECT COUNT(*) as count FROM claims");
if ($total_claims_res && $row = $total_claims_res->fetch_assoc()) {
    $count_total_claims = (int)$row['count'];
}

// Fetch Recently Pinned Items (top 4)
$recent_items = [];
$recent_query = "(SELECT 'lost' as type, item_id, item_name, description, date_lost as date_reported, location, status, image FROM lost_items)
                 UNION ALL
                 (SELECT 'found' as type, item_id, item_name, description, date_found as date_reported, location, status, image FROM found_items)
                 ORDER BY date_reported DESC LIMIT 4";
$recent_res = $conn->query($recent_query);
if ($recent_res) {
    while ($row = $recent_res->fetch_assoc()) {
        $recent_items[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CampusConnect Lost & Found | Zeal College of Engineering and Research, Narhe, Pune</title>
    
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
            <?php if (isset($_SESSION['username'])): ?>
                <!-- Logged In Navigation Menu -->
                <div class="sidebar-section-label">Main Menu</div>
                <a href="index.php" class="nav-link active">
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
                <?php if ($_SESSION['username'] === 'admin'): ?>
                    <a href="admin.php" class="nav-link">
                        <i class="fa-solid fa-shield-halved"></i> Admin Portal
                    </a>
                <?php endif; ?>
            <?php else: ?>
                <!-- Logged Out First Page Sidebar Menu -->
                <div class="sidebar-section-label">Navigation</div>
                <a href="index.php" class="nav-link active">
                    <i class="fa-solid fa-house"></i> Home
                </a>
                <a href="#about" class="nav-link">
                    <i class="fa-solid fa-circle-info"></i> About
                </a>
                <a href="#contact" class="nav-link">
                    <i class="fa-solid fa-envelope"></i> Contact
                </a>
            <?php endif; ?>
        </nav>

        <div class="sidebar-footer">
            <?php if (isset($_SESSION['username'])): ?>
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
            <?php else: ?>
                <a href="login.php" class="sidebar-btn sidebar-btn-primary">
                    <i class="fa-solid fa-right-to-bracket"></i> Sign In
                </a>
                <a href="register.php" class="sidebar-btn sidebar-btn-outline">
                    <i class="fa-solid fa-user-plus"></i> Sign Up
                </a>
            <?php endif; ?>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="main-content">
        <!-- Top Bar -->
        <header class="top-bar">
            <div>
                <button class="mobile-toggle" id="mobileToggle" onclick="toggleSidebar()">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <span class="top-bar-title">
                    <?php echo isset($_SESSION['username']) ? 'CampusConnect Dashboard' : 'CampusConnect Lost & Found'; ?>
                </span>
            </div>
            <div class="top-bar-subtitle">
                <i class="fa-solid fa-location-dot"></i> Zeal College of Engineering and Research, Narhe, Pune
            </div>
        </header>

        <div class="page-container">
            <?php if (!isset($_SESSION['username'])): ?>
                <!-- FIRST PAGE / LANDING PAGE (LOGGED OUT) -->
                
                <!-- Hero Section -->
                <section class="hero-section" id="home">
                    <div class="hero-text">
                        <span class="hero-tag">
                            <i class="fa-solid fa-graduation-cap"></i> Zeal College of Engineering and Research, Narhe, Pune
                        </span>
                        <div style="font-size: 14px; font-weight: 700; color: var(--primary); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 6px;">CampusConnect Lost & Found</div>
                        <h1 class="hero-title">Campus Lost & Found Management System</h1>
                        <p class="hero-subtitle">
                            Helping students reconnect with their lost belongings through a secure, efficient, and user-friendly campus management system.
                        </p>
                        <div class="hero-buttons">
                            <a href="login.php" class="btn btn-primary">
                                <i class="fa-solid fa-right-to-bracket"></i> Sign In
                            </a>
                            <a href="register.php" class="btn btn-outline">
                                <i class="fa-solid fa-user-plus"></i> Sign Up
                            </a>
                        </div>
                    </div>

                    <div class="hero-image-wrap">
                        <img src="assets/zeal_college_campus.jpg" alt="Zeal College of Engineering and Research Campus, Narhe Pune" class="hero-image">
                        <div class="hero-image-badge">
                            <i class="fa-solid fa-building-columns" style="color:#60a5fa;"></i>
                            <span>Zeal College of Engineering and Research, Narhe, Pune</span>
                        </div>
                    </div>
                </section>

                <!-- About Section -->
                <section class="about-section" id="about">
                    <div class="section-header">
                        <h2 class="section-title">About CampusConnect</h2>
                        <p class="section-subtitle">A centralized digital platform engineered for Zeal Education Society students and faculty to record, track, and recover misplaced possessions safely.</p>
                    </div>

                    <div class="about-grid">
                        <div class="about-card">
                            <div class="about-icon"><i class="fa-solid fa-bullseye"></i></div>
                            <h3 class="about-card-title">Purpose of Project</h3>
                            <p class="about-card-desc">Streamlining the traditional lost & found process into a rapid, transparent online hub for Zeal campus.</p>
                        </div>
                        <div class="about-card">
                            <div class="about-icon"><i class="fa-solid fa-user-graduate"></i></div>
                            <h3 class="about-card-title">Benefits to Students</h3>
                            <p class="about-card-desc">Instant reporting, instant notifications, and proof-based item verification for quick recovery.</p>
                        </div>
                        <div class="about-card">
                            <div class="about-icon"><i class="fa-solid fa-lock"></i></div>
                            <h3 class="about-card-title">Secure Reporting</h3>
                            <p class="about-card-desc">Protected with student authentication, encrypted data, and office verification procedures.</p>
                        </div>
                        <div class="about-card">
                            <div class="about-icon"><i class="fa-solid fa-handshake"></i></div>
                            <h3 class="about-card-title">Easy Claim Process</h3>
                            <p class="about-card-desc">Submit distinguishing marks and verification details online to retrieve your item hassle-free.</p>
                        </div>
                    </div>
                </section>

                <!-- Features Section -->
                <section class="features-section">
                    <div class="section-header">
                        <h2 class="section-title">Platform Features</h2>
                        <p class="section-subtitle">Everything you need to locate, report, or claim items across the Narhe Pune campus.</p>
                    </div>

                    <div class="features-grid">
                        <div class="feature-card">
                            <div class="feature-icon-wrap"><i class="fa-solid fa-magnifying-glass-minus"></i></div>
                            <h3 class="feature-title">Report Lost Item</h3>
                            <p class="feature-desc">Submit comprehensive details of misplaced items including last known campus location and description.</p>
                            <a href="login.php" class="btn btn-outline btn-block">Sign In to Report</a>
                        </div>
                        <div class="feature-card">
                            <div class="feature-icon-wrap"><i class="fa-solid fa-box-archive"></i></div>
                            <h3 class="feature-title">Report Found Item</h3>
                            <p class="feature-desc">Found something on campus? Upload details and pictures to help return it to its rightful owner.</p>
                            <a href="login.php" class="btn btn-outline btn-block">Sign In to Report</a>
                        </div>
                        <div class="feature-card">
                            <div class="feature-icon-wrap"><i class="fa-solid fa-boxes-stacked"></i></div>
                            <h3 class="feature-title">Browse Items</h3>
                            <p class="feature-desc">Explore the live campus bulletin of reported lost & found items with location filters.</p>
                            <a href="items.php" class="btn btn-primary btn-block">Browse Gallery</a>
                        </div>
                        <div class="feature-card">
                            <div class="feature-icon-wrap"><i class="fa-solid fa-id-card-clip"></i></div>
                            <h3 class="feature-title">Claim Ownership</h3>
                            <p class="feature-desc">Provide proof of ownership with detailed descriptions to claim your lost belongings securely.</p>
                            <a href="login.php" class="btn btn-outline btn-block">Sign In to Claim</a>
                        </div>
                    </div>
                </section>

            <?php else: ?>
                <!-- LOGGED IN DASHBOARD (AFTER LOGIN) -->

                <!-- Welcome Banner -->
                <div class="welcome-banner">
                    <div>
                        <h1 class="welcome-title">Welcome to CampusConnect Lost & Found Portal</h1>
                        <p class="welcome-sub">Hello, <strong><?php echo htmlspecialchars($_SESSION['name'] ?? $_SESSION['username']); ?></strong>! Access your reports, browse items, or submit claims.</p>
                    </div>
                    <div>
                        <a href="report_lost.php" class="btn btn-primary">
                            <i class="fa-solid fa-plus"></i> Report Item
                        </a>
                    </div>
                </div>

                <!-- 4 Dashboard Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon stat-icon-blue">
                            <i class="fa-solid fa-folder-minus"></i>
                        </div>
                        <div>
                            <div class="stat-val"><?php echo $count_lost; ?></div>
                            <div class="stat-label">Lost Items</div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon stat-icon-green">
                            <i class="fa-solid fa-box-open"></i>
                        </div>
                        <div>
                            <div class="stat-val"><?php echo $count_found; ?></div>
                            <div class="stat-label">Found Items</div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon stat-icon-amber">
                            <i class="fa-solid fa-clock-rotate-left"></i>
                        </div>
                        <div>
                            <div class="stat-val"><?php echo $count_pending_claims; ?></div>
                            <div class="stat-label">Pending Claims</div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon stat-icon-purple">
                            <i class="fa-solid fa-clipboard-check"></i>
                        </div>
                        <div>
                            <div class="stat-val"><?php echo $count_total_claims; ?></div>
                            <div class="stat-label">Total Claims</div>
                        </div>
                    </div>
                </div>

                <!-- Recent Items Grid -->
                <div class="section-header" style="text-align:left; margin-bottom: 24px;">
                    <h2 class="section-title">Recently Reported Items</h2>
                    <p class="section-subtitle">Live bulletin of lost and found belongings across Zeal Campus.</p>
                </div>

                <div class="items-grid">
                    <?php if (empty($recent_items)): ?>
                        <div class="card" style="grid-column: 1/-1; text-align:center; padding: 40px;">
                            <i class="fa-solid fa-inbox" style="font-size:40px; color:#cbd5e1; margin-bottom:12px;"></i>
                            <p style="color:var(--text-muted);">No reports logged yet. Be the first to file a report!</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($recent_items as $item): ?>
                            <div class="item-card">
                                <div class="item-img-wrap">
                                    <?php if (!empty($item['image']) && file_exists("uploads/" . $item['image'])): ?>
                                        <img src="uploads/<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['item_name']); ?>" class="item-img">
                                    <?php else: ?>
                                        <div style="height:100%; display:flex; align-items:center; justify-content:center; background:#e2e8f0; color:#64748b; font-size:48px;">
                                            <i class="fa-solid <?php echo $item['type'] === 'lost' ? 'fa-magnifying-glass' : 'fa-box'; ?>"></i>
                                        </div>
                                    <?php endif; ?>
                                    <span class="badge <?php echo $item['type'] === 'lost' ? 'badge-lost' : 'badge-found'; ?>">
                                        <?php echo strtoupper($item['type']); ?>
                                    </span>
                                </div>
                                <div class="item-body">
                                    <h3 class="item-title"><?php echo htmlspecialchars($item['item_name']); ?></h3>
                                    <p class="item-desc"><?php echo htmlspecialchars($item['description'] ?? 'No description provided.'); ?></p>
                                    
                                    <div class="item-meta">
                                        <div class="item-meta-row">
                                            <i class="fa-solid fa-location-dot"></i>
                                            <span><?php echo htmlspecialchars($item['location']); ?></span>
                                        </div>
                                        <div class="item-meta-row">
                                            <i class="fa-solid fa-calendar-day"></i>
                                            <span><?php echo date('M d, Y', strtotime($item['date_reported'])); ?></span>
                                        </div>
                                    </div>

                                    <?php if ($item['type'] === 'found'): ?>
                                        <a href="claims.php?item_id=<?php echo $item['item_id']; ?>" class="btn btn-success btn-block">
                                            <i class="fa-solid fa-handshake"></i> Claim Ownership
                                        </a>
                                    <?php else: ?>
                                        <a href="items.php" class="btn btn-outline btn-block">
                                            <i class="fa-solid fa-eye"></i> View Details
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Campus Footer -->
        <footer class="footer-campus" id="contact">
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
    const sidebar = document.getElementById('sidebar');
    if (!sidebar) return;
    let overlay = document.querySelector('.sidebar-overlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.className = 'sidebar-overlay';
        document.body.appendChild(overlay);
        overlay.addEventListener('click', toggleSidebar);
    }
    const isOpen = sidebar.classList.toggle('open');
    if (isOpen) {
        overlay.classList.add('active');
    } else {
        overlay.classList.remove('active');
    }
}
</script>
</body>
</html>
