<?php
// items.php - Browse Items Board
session_start();
include "db.php";

// Fetch all lost items
$db_items = [];

$lost_res = $conn->query("SELECT item_id, item_name, description, date_lost, location, contact, image, status FROM lost_items ORDER BY date_lost DESC");
if ($lost_res) {
    while ($row = $lost_res->fetch_assoc()) {
        $days_ago = round((time() - strtotime($row['date_lost'])) / (60 * 60 * 24));
        $days_label = ($days_ago <= 0) ? "Today" : (($days_ago == 1) ? "1 day ago" : "$days_ago days ago");
        
        $db_items[] = [
            'id' => (int)$row['item_id'],
            'name' => $row['item_name'],
            'location' => $row['location'],
            'type' => 'lost',
            'status' => strtolower($row['status']),
            'days' => $days_label,
            'date_raw' => date('M d, Y', strtotime($row['date_lost'])),
            'icon' => 'fa-magnifying-glass',
            'image' => $row['image'],
            'description' => $row['description']
        ];
    }
}

// Fetch all found items
$found_res = $conn->query("SELECT item_id, item_name, description, date_found, location, contact, image, status FROM found_items ORDER BY date_found DESC");
if ($found_res) {
    while ($row = $found_res->fetch_assoc()) {
        $days_ago = round((time() - strtotime($row['date_found'])) / (60 * 60 * 24));
        $days_label = ($days_ago <= 0) ? "Today" : (($days_ago == 1) ? "1 day ago" : "$days_ago days ago");
        
        $db_items[] = [
            'id' => (int)$row['item_id'],
            'name' => $row['item_name'],
            'location' => $row['location'],
            'type' => 'found',
            'status' => strtolower($row['status']),
            'days' => $days_label,
            'date_raw' => date('M d, Y', strtotime($row['date_found'])),
            'icon' => 'fa-box',
            'image' => $row['image'],
            'description' => $row['description']
        ];
    }
}

$json_items = json_encode($db_items);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Items | CampusConnect - Zeal College</title>
    
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
                <a href="items.php" class="nav-link active">
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
                <div class="sidebar-section-label">Navigation</div>
                <a href="index.php" class="nav-link">
                    <i class="fa-solid fa-house"></i> Home
                </a>
                <a href="index.php#about" class="nav-link">
                    <i class="fa-solid fa-circle-info"></i> About
                </a>
                <a href="index.php#contact" class="nav-link">
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
        <header class="top-bar">
            <div>
                <button class="mobile-toggle" onclick="toggleSidebar()">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <span class="top-bar-title">Browse Campus Items</span>
            </div>
            <div class="top-bar-subtitle">
                <i class="fa-solid fa-location-dot"></i> Zeal College of Engineering and Research, Narhe, Pune
            </div>
        </header>

        <div class="page-container">
            <!-- Search & Filter Controls -->
            <div class="card" style="margin-bottom: 32px; padding: 24px;">
                <div style="display: flex; gap: 16px; flex-wrap: wrap; align-items: center; justify-content: space-between;">
                    <div class="input-icon-group" style="flex: 1; min-width: 260px;">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" id="searchInput" class="form-control" placeholder="Search by item name, description, or campus location..." onkeyup="filterItems()">
                    </div>

                    <div style="display: flex; gap: 8px;">
                        <button id="filterAll" class="btn btn-primary" onclick="setFilter('all')">All Items</button>
                        <button id="filterLost" class="btn btn-outline" onclick="setFilter('lost')">Lost Items</button>
                        <button id="filterFound" class="btn btn-outline" onclick="setFilter('found')">Found Items</button>
                    </div>
                </div>
            </div>

            <!-- Items Cards Grid -->
            <div class="items-grid" id="itemsGrid">
                <!-- Injected dynamically via JS -->
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
const itemsData = <?php echo $json_items; ?>;
let activeFilter = 'all';

function setFilter(type) {
    activeFilter = type;
    document.getElementById('filterAll').className = type === 'all' ? 'btn btn-primary' : 'btn btn-outline';
    document.getElementById('filterLost').className = type === 'lost' ? 'btn btn-primary' : 'btn btn-outline';
    document.getElementById('filterFound').className = type === 'found' ? 'btn btn-primary' : 'btn btn-outline';
    filterItems();
}

function filterItems() {
    const query = document.getElementById('searchInput').value.toLowerCase();
    const grid = document.getElementById('itemsGrid');
    
    const filtered = itemsData.filter(item => {
        const matchesType = (activeFilter === 'all') || (item.type === activeFilter);
        const matchesQuery = item.name.toLowerCase().includes(query) || 
                             item.location.toLowerCase().includes(query) || 
                             (item.description && item.description.toLowerCase().includes(query));
        return matchesType && matchesQuery;
    });

    if (filtered.length === 0) {
        grid.innerHTML = `
            <div class="card" style="grid-column: 1/-1; text-align:center; padding: 48px;">
                <i class="fa-solid fa-folder-open" style="font-size:48px; color:#cbd5e1; margin-bottom:16px;"></i>
                <h3 style="color:var(--text-dark); margin-bottom:8px;">No items match your search</h3>
                <p style="color:var(--text-muted);">Try adjusting your search keywords or filter options.</p>
            </div>
        `;
        return;
    }

    grid.innerHTML = filtered.map(item => {
        const isFound = item.type === 'found';
        const badgeClass = item.status === 'returned' ? 'badge-returned' : (isFound ? 'badge-found' : 'badge-lost');
        const imageMarkup = item.image ? 
            `<img src="uploads/${item.image}" alt="${escapeHtml(item.name)}" class="item-img">` :
            `<div style="height:100%; display:flex; align-items:center; justify-content:center; background:#e2e8f0; color:#64748b; font-size:48px;">
                <i class="fa-solid ${isFound ? 'fa-box' : 'fa-magnifying-glass'}"></i>
             </div>`;

        return `
            <div class="item-card">
                <div class="item-img-wrap">
                    ${imageMarkup}
                    <span class="badge ${badgeClass}">${item.type.toUpperCase()} - ${item.status.toUpperCase()}</span>
                </div>
                <div class="item-body">
                    <h3 class="item-title">${escapeHtml(item.name)}</h3>
                    <p class="item-desc">${escapeHtml(item.description || 'No detailed description available.')}</p>
                    
                    <div class="item-meta">
                        <div class="item-meta-row">
                            <i class="fa-solid fa-location-dot"></i>
                            <span>${escapeHtml(item.location)}</span>
                        </div>
                        <div class="item-meta-row">
                            <i class="fa-solid fa-calendar-day"></i>
                            <span>${item.date_raw || item.days}</span>
                        </div>
                    </div>

                    ${isFound && item.status !== 'returned' ? `
                        <a href="claims.php?item_id=${item.id}" class="btn btn-success btn-block">
                            <i class="fa-solid fa-handshake"></i> Claim Ownership
                        </a>
                    ` : `
                        <div class="btn btn-outline btn-block" style="cursor:default; opacity:0.8;">
                            <i class="fa-solid fa-circle-info"></i> ${item.status === 'returned' ? 'Item Returned' : 'Reported Lost'}
                        </div>
                    `}
                </div>
            </div>
        `;
    }).join('');
}

function escapeHtml(text) {
    if (!text) return '';
    return text.replace(/[&<>"']/g, function(m) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m];
    });
}

function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
}

// Initial render
filterItems();
</script>
</body>
</html>