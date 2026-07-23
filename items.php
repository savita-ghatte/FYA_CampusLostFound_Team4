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
            'icon' => '🎒',
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
            'icon' => '📦',
            'image' => $row['image'],
            'description' => $row['description']
        ];
    }
}

// Format items array to JSON for client side consumption
$json_items = json_encode($db_items);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Campus Lost&amp;Found — Browse Items</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet">
<style>
  :root{
    --ink:#1F2A38;
    --ink-soft:#3D4A5C;
    --paper:#FAF6EE;
    --tan-deep:#C9AE79;
    --gold:#C99A2E;
    --gold-deep:#A87D1E;
    --rust:#B23A2E;
    --green-ok:#3E6B4F;
    --board:#8C6A46;
    --board-deep:#7A5A3A;
    --radius:14px;
    --shadow-card: 0 18px 40px -14px rgba(31,42,56,0.35);
    --shadow-soft: 0 6px 16px -8px rgba(31,42,56,0.25);
  }

  *{ box-sizing:border-box; }

  body{
    margin:0;
    font-family:'Inter', sans-serif;
    color:var(--ink);
    min-height:100vh;
    background:
      radial-gradient(circle at 20% 15%, rgba(255,255,255,0.06) 0, transparent 45%),
      radial-gradient(circle at 80% 80%, rgba(0,0,0,0.08) 0, transparent 50%),
      repeating-linear-gradient(45deg, rgba(0,0,0,0.015) 0 2px, transparent 2px 6px),
      linear-gradient(160deg, var(--board) 0%, var(--board-deep) 100%);
  }

  .wrap{
    max-width:1180px;
    margin:0 auto;
    padding:32px 24px 80px;
  }

  /* ---- Nav ---- */
  nav{
    display:flex;
    justify-content:center;
    gap:10px;
    margin-bottom:44px;
    flex-wrap:wrap;
  }
  nav a{
    font-family:'JetBrains Mono', monospace;
    font-size:12px;
    letter-spacing:0.08em;
    text-transform:uppercase;
    color:var(--paper);
    text-decoration:none;
    background:rgba(31,42,56,0.35);
    border:1px solid rgba(250,246,238,0.35);
    padding:8px 16px;
    border-radius:999px;
    transition:background .15s ease;
  }
  nav a:hover{ background:rgba(31,42,56,0.55); }
  nav a.active{ background:var(--gold); color:var(--ink); border-color:var(--gold); }

  /* ---- Header ---- */
  header{ text-align:center; margin-bottom:32px; }
  header h1{
    font-family:'Fraunces', serif;
    font-weight:600;
    font-size:clamp(30px, 5vw, 42px);
    line-height:1.05;
    color:var(--paper);
    margin:0 0 10px;
    letter-spacing:-0.01em;
  }
  header p{
    color:rgba(250,246,238,0.82);
    font-size:15.5px;
    max-width:480px;
    margin:0 auto;
    line-height:1.55;
  }

  /* ---- Controls bar ---- */
  .controls{
    background:var(--paper);
    border-radius:var(--radius);
    box-shadow:var(--shadow-card);
    padding:20px 22px;
    margin-bottom:24px;
    display:flex;
    gap:14px;
    flex-wrap:wrap;
    align-items:center;
  }

  .search-box{
    flex:1;
    min-width:220px;
    position:relative;
    display:flex;
    align-items:center;
  }
  .search-box svg{
    position:absolute;
    left:13px;
    color:var(--ink-soft);
    pointer-events:none;
  }
  .search-box input{
    width:100%;
    font-family:'Inter', sans-serif;
    font-size:14.5px;
    color:var(--ink);
    background:#fff;
    border:1.5px solid var(--tan-deep);
    border-radius:9px;
    padding:11px 13px 11px 38px;
  }
  .search-box input::placeholder{ color:#9AA3AE; }
  .search-box input:focus{
    outline:none;
    border-color:var(--gold-deep);
    box-shadow:0 0 0 4px rgba(201,154,46,0.18);
  }

  .filter-tabs{ display:flex; gap:8px; flex-wrap:wrap; }
  .filter-tab{
    font-family:'JetBrains Mono', monospace;
    font-size:11.5px;
    letter-spacing:0.06em;
    text-transform:uppercase;
    background:#fff;
    border:1.5px solid var(--tan-deep);
    color:var(--ink-soft);
    padding:8px 14px;
    border-radius:999px;
    cursor:pointer;
    transition:all .15s ease;
  }
  .filter-tab.active{ background:var(--ink); color:var(--paper); border-color:var(--ink); }
  .filter-tab:hover:not(.active){ border-color:var(--gold-deep); color:var(--ink); }

  select.status-filter{
    font-family:'Inter', sans-serif;
    font-size:13.5px;
    color:var(--ink);
    background:#fff;
    border:1.5px solid var(--tan-deep);
    border-radius:9px;
    padding:10px 12px;
    cursor:pointer;
  }
  select.status-filter:focus{
    outline:none;
    border-color:var(--gold-deep);
    box-shadow:0 0 0 4px rgba(201,154,46,0.18);
  }

  .result-count{
    font-family:'JetBrains Mono', monospace;
    font-size:12px;
    color:rgba(250,246,238,0.75);
    margin:0 0 16px;
    letter-spacing:0.04em;
  }

  /* ---- Item grid ---- */
  .item-grid{
    display:grid;
    grid-template-columns:repeat(3, 1fr);
    gap:18px;
  }
  @media (max-width:900px){ .item-grid{ grid-template-columns:repeat(2, 1fr); } }
  @media (max-width:600px){ .item-grid{ grid-template-columns:1fr; } }

  .item-card{
    background:var(--paper);
    border-radius:var(--radius);
    box-shadow:var(--shadow-card);
    overflow:hidden;
    display:flex;
    flex-direction:column;
    transition:transform .15s ease, box-shadow .15s ease;
  }
  .item-card:hover{
    transform:translateY(-3px);
    box-shadow:0 22px 46px -14px rgba(31,42,56,0.45);
  }
  .item-thumb{
    height:160px;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:34px;
    background:repeating-linear-gradient(135deg, rgba(201,154,46,0.08) 0 10px, transparent 10px 20px);
    overflow:hidden;
  }
  .item-thumb img {
    width:100%;
    height:100%;
    object-fit:cover;
  }
  .item-body{ padding:16px 18px 18px; display:flex; flex-direction:column; flex:1; }
  .item-top{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:8px;
    margin-bottom:6px;
  }
  .item-title{
    font-family:'Fraunces', serif;
    font-size:17px;
    font-weight:600;
    margin:0;
  }
  .badge{
    font-family:'JetBrains Mono', monospace;
    font-size:10.5px;
    letter-spacing:0.06em;
    text-transform:uppercase;
    padding:3px 9px;
    border-radius:999px;
    white-space:nowrap;
    flex-shrink:0;
  }
  .badge-pending{ background:rgba(201,154,46,0.18); color:var(--gold-deep); }
  .badge-matched{ background:rgba(62,107,79,0.18); color:var(--green-ok); }
  .badge-claimed{ background:rgba(62,107,79,0.18); color:var(--green-ok); }
  .badge-returned{ background:rgba(62,107,79,0.18); color:var(--green-ok); }
  .badge-unclaimed{ background:rgba(178,58,46,0.15); color:var(--rust); }

  .item-meta{
    font-size:12.5px;
    color:var(--ink-soft);
    margin:0 0 10px;
  }

  .item-foot{
    display:flex;
    align-items:center;
    justify-content:space-between;
    margin-top:auto;
  }
  .item-type{
    display:inline-block;
    font-family:'JetBrains Mono', monospace;
    font-size:10px;
    letter-spacing:0.06em;
    text-transform:uppercase;
    color:var(--ink-soft);
    border:1px solid var(--tan-deep);
    border-radius:6px;
    padding:2px 7px;
  }
  .claim-link{
    font-family:'JetBrains Mono', monospace;
    font-size:11px;
    letter-spacing:0.05em;
    text-transform:uppercase;
    color:var(--gold-deep);
    text-decoration:none;
    font-weight:600;
  }
  .claim-link:hover{ text-decoration:underline; }

  /* ---- Empty state ---- */
  .no-results{
    display:none;
    text-align:center;
    padding:50px 20px;
    color:rgba(250,246,238,0.8);
    font-family:'JetBrains Mono', monospace;
    font-size:13px;
    letter-spacing:0.04em;
    background:rgba(250,246,238,0.06);
    border:1px dashed rgba(250,246,238,0.3);
    border-radius:var(--radius);
  }
  .no-results.show{ display:block; }

  footer{
    text-align:center;
    margin-top:56px;
    font-size:12.5px;
    color:rgba(250,246,238,0.55);
    font-family:'JetBrains Mono', monospace;
    letter-spacing:0.04em;
  }

  @media (prefers-reduced-motion: reduce){ *{ transition:none !important; } }
</style>
</head>
<body>
<div class="wrap">

  <nav>
    <a href="index.php">Home</a>
    <?php if (isset($_SESSION['username'])): ?>
        <a href="report_lost.php">Report Lost</a>
        <a href="report_found.php">Report Found</a>
    <?php endif; ?>
    <a href="items.php" class="active">Browse Items</a>
    <?php if (isset($_SESSION['username'])): ?>
        <a href="claims.php">Claim</a>
        <a href="profile.php">Edit Profile</a>
        <?php if ($_SESSION['username'] === 'admin'): ?>
            <a href="admin.php">Admin</a>
        <?php endif; ?>
        <a href="logout.php">Sign Out (<?php echo htmlspecialchars($_SESSION['username']); ?>)</a>
    <?php else: ?>
        <a href="login.php">Sign In</a>
        <a href="register.php">Sign Up</a>
    <?php endif; ?>
  </nav>

  <header>
    <h1>Browse the Board</h1>
    <p>Search reported items or filter by type and status.</p>
  </header>

  <div class="controls">
    <div class="search-box">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
      <input type="text" id="searchInput" placeholder="Search by item name or location…">
    </div>

    <div class="filter-tabs" id="typeTabs">
      <button class="filter-tab active" data-type="all">All</button>
      <button class="filter-tab" data-type="lost">Lost</button>
      <button class="filter-tab" data-type="found">Found</button>
    </div>

    <select class="status-filter" id="statusFilter">
      <option value="all">All statuses</option>
      <option value="pending">Pending</option>
      <option value="matched">Matched</option>
      <option value="claimed">Claimed</option>
      <option value="returned">Returned</option>
    </select>
  </div>

  <p class="result-count" id="resultCount">Showing 0 items</p>

  <div class="item-grid" id="itemGrid"></div>
  <p class="no-results" id="noResults">No items match your search. Try a different keyword or filter.</p>

  <footer>CAMPUS LOST&amp;FOUND · COLLEGE OFFICE</footer>
</div>

<script>
(function(){
  // Dynamic PHP variables rendered safely into JavaScript
  var items = <?php echo $json_items; ?>;

  var grid = document.getElementById('itemGrid');
  var noResults = document.getElementById('noResults');
  var resultCount = document.getElementById('resultCount');
  var searchInput = document.getElementById('searchInput');
  var statusFilter = document.getElementById('statusFilter');
  var typeTabs = document.querySelectorAll('.filter-tab');
  var activeType = 'all';

  var badgeClass = { 
    pending:'badge-pending', 
    matched:'badge-matched', 
    claimed:'badge-claimed', 
    returned:'badge-returned', 
    unclaimed:'badge-unclaimed' 
  };
  var badgeLabel = { 
    pending:'Pending', 
    matched:'Matched', 
    claimed:'Claimed', 
    returned:'Returned', 
    unclaimed:'Unclaimed' 
  };

  function escapeHtml(str){
    var div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
  }

  function render(){
    var query = searchInput.value.trim().toLowerCase();
    var status = statusFilter.value;

    var filtered = items.filter(function(item){
      var matchesQuery = !query ||
        item.name.toLowerCase().indexOf(query) !== -1 ||
        item.location.toLowerCase().indexOf(query) !== -1 ||
        item.description.toLowerCase().indexOf(query) !== -1;
      var matchesType = activeType === 'all' || item.type === activeType;
      var matchesStatus = status === 'all' || item.status === status;
      return matchesQuery && matchesType && matchesStatus;
    });

    grid.innerHTML = '';

    filtered.forEach(function(item){
      var card = document.createElement('div');
      card.className = 'item-card';

      var verb = item.type === 'lost' ? 'Lost near ' : 'Found at ';
      var typeLabel = item.type === 'lost' ? 'Lost' : 'Found';

      var thumbContent = item.icon;
      if (item.image && item.image !== '') {
        thumbContent = '<img src="uploads/' + encodeURIComponent(item.image) + '" alt="' + escapeHtml(item.name) + '">';
      }

      // Format status classes/labels safely
      var displayStatus = item.status;
      if (!badgeLabel[displayStatus]) {
        // Fallback to title case
        badgeLabel[displayStatus] = displayStatus.charAt(0).toUpperCase() + displayStatus.slice(1);
        badgeClass[displayStatus] = 'badge-pending';
      }

      card.innerHTML =
        '<div class="item-thumb">' + thumbContent + '</div>' +
        '<div class="item-body">' +
          '<div class="item-top">' +
            '<p class="item-title">' + escapeHtml(item.name) + '</p>' +
            '<span class="badge ' + badgeClass[item.status] + '">' + badgeLabel[item.status] + '</span>' +
          '</div>' +
          '<p class="item-meta">' + verb + escapeHtml(item.location) + ' · ' + item.days + '</p>' +
          '<div class="item-foot">' +
            '<span class="item-type">' + typeLabel + '</span>' +
            '<a class="claim-link" href="claims.php?item_id=' + item.id + '&item=' + encodeURIComponent(item.name) + '">Claim →</a>' +
          '</div>' +
        '</div>';

      grid.appendChild(card);
    });

    resultCount.textContent = 'Showing ' + filtered.length + ' item' + (filtered.length === 1 ? '' : 's');
    noResults.classList.toggle('show', filtered.length === 0);
  }

  searchInput.addEventListener('input', render);
  statusFilter.addEventListener('change', render);
  typeTabs.forEach(function(tab){
    tab.addEventListener('click', function(){
      typeTabs.forEach(function(t){ t.classList.remove('active'); });
      tab.classList.add('active');
      activeType = tab.getAttribute('data-type');
      render();
    });
  });

  render();
})();
</script>
</body>
</html>