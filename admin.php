<?php
// admin.php - Administrative Dashboard
session_start();
include "db.php";

// 1. Authenticate Admin (only users logged in as 'admin')
if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'admin') {
    header("Location: login.php");
    exit();
}

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
$claims_query = $conn->query("SELECT c.claim_id, c.colour, c.distinguishing_marks, c.image, c.claim_status, f.item_name, f.contact FROM claims c JOIN found_items f ON c.item_id = f.item_id ORDER BY c.claim_id DESC");
if ($claims_query) {
    while ($row = $claims_query->fetch_assoc()) {
        $db_claims[] = [
            'id' => (int)$row['claim_id'],
            'item' => $row['item_name'],
            'by' => $row['contact'],
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
<title>Campus Lost &amp; Found — Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet">
<style>
  :root{
    --ink:#1F2A38; --ink-soft:#3D4A5C; --paper:#FAF6EE;
    --tan-deep:#C9AE79; --gold:#C99A2E; --gold-deep:#A87D1E;
    --rust:#B23A2E; --green-ok:#3E6B4F;
    --board:#8C6A46; --board-deep:#7A5A3A;
    --radius:14px;
    --shadow-card: 0 18px 40px -14px rgba(31,42,56,0.35);
    --shadow-soft: 0 6px 16px -8px rgba(31,42,56,0.25);
  }
  *{ box-sizing:border-box; }
  body{
    margin:0; font-family:'Inter', sans-serif; color:var(--ink); min-height:100vh;
    background:
      radial-gradient(circle at 20% 15%, rgba(255,255,255,0.06) 0, transparent 45%),
      radial-gradient(circle at 80% 80%, rgba(0,0,0,0.08) 0, transparent 50%),
      repeating-linear-gradient(45deg, rgba(0,0,0,0.015) 0 2px, transparent 2px 6px),
      linear-gradient(160deg, var(--board) 0%, var(--board-deep) 100%);
  }
  .wrap{ max-width:1180px; margin:0 auto; padding:32px 24px 80px; }

  nav{ display:flex; justify-content:center; gap:10px; margin-bottom:44px; flex-wrap:wrap; }
  nav a{
    font-family:'JetBrains Mono', monospace; font-size:12px; letter-spacing:0.08em;
    text-transform:uppercase; color:var(--paper); text-decoration:none;
    background:rgba(31,42,56,0.35); border:1px solid rgba(250,246,238,0.35);
    padding:8px 16px; border-radius:999px; transition:background .15s ease;
  }
  nav a:hover{ background:rgba(31,42,56,0.55); }
  nav a.active{ background:var(--gold); color:var(--ink); border-color:var(--gold); }

  header{ text-align:center; margin-bottom:32px; }
  h1{
    font-family:'Fraunces', serif; font-weight:600; font-size:clamp(30px, 5vw, 42px);
    line-height:1.05; color:var(--paper); margin:0 0 10px; letter-spacing:-0.01em;
  }
  header p{ color:rgba(250,246,238,0.82); font-size:15.5px; max-width:480px; margin:0 auto; line-height:1.55; }

  .stats{ display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin-bottom:36px; }
  @media (max-width:760px){ .stats{ grid-template-columns:repeat(2,1fr); } }
  .stat-card{ background:var(--paper); border-radius:var(--radius); box-shadow:var(--shadow-card); padding:18px 16px; text-align:center; }
  .stat-num{ font-family:'Fraunces', serif; font-size:28px; font-weight:600; }
  .stat-label{ font-family:'JetBrains Mono', monospace; font-size:10.5px; letter-spacing:0.06em; text-transform:uppercase; color:var(--ink-soft); margin-top:4px; }

  .panel{
    background:var(--paper); border-radius:var(--radius); box-shadow:var(--shadow-card);
    padding:26px 24px 30px; margin-bottom:32px;
  }
  .panel-head{ display:flex; justify-content:space-between; align-items:baseline; margin-bottom:18px; flex-wrap:wrap; gap:8px; }
  .panel-head h2{ font-family:'Fraunces', serif; font-size:21px; margin:0; }
  .panel-head span{ font-family:'JetBrains Mono', monospace; font-size:11px; color:var(--ink-soft); letter-spacing:0.05em; }

  table{ width:100%; border-collapse:collapse; }
  thead th{
    text-align:left; font-family:'JetBrains Mono', monospace; font-size:10.5px; letter-spacing:0.06em;
    text-transform:uppercase; color:var(--ink-soft); padding:0 10px 10px; border-bottom:2px solid var(--tan-deep);
  }
  tbody td{ padding:12px 10px; border-bottom:1px solid rgba(201,174,121,0.35); font-size:13.5px; vertical-align:middle; }
  tbody tr:last-child td{ border-bottom:none; }

  .type-pill{
    font-family:'JetBrains Mono', monospace; font-size:10px; letter-spacing:0.05em; text-transform:uppercase;
    border-radius:999px; padding:3px 9px;
  }
  .type-lost{ background:rgba(201,154,46,0.15); color:var(--gold-deep); }
  .type-found{ background:rgba(62,107,79,0.15); color:var(--green-ok); }

  select.status-select{
    font-family:'Inter', sans-serif; font-size:12.5px; color:var(--ink);
    background:#fff; border:1.5px solid var(--tan-deep); border-radius:7px; padding:6px 8px;
  }
  select.status-select:focus{ outline:none; border-color:var(--gold-deep); box-shadow:0 0 0 3px rgba(201,154,46,0.18); }

  /* claims panel */
  .claim-row{
    display:flex; align-items:center; justify-content:space-between; gap:14px; flex-wrap:wrap;
    padding:14px 4px; border-bottom:1px solid rgba(201,174,121,0.35);
  }
  .claim-row:last-child{ border-bottom:none; }
  .claim-info{ flex:1; min-width:200px; }
  .claim-info .name{ font-family:'Fraunces', serif; font-size:16px; font-weight:600; margin:0 0 3px; }
  .claim-info .meta{ font-size:12.5px; color:var(--ink-soft); margin:0; }
  .claim-answers{
    font-size:12.5px; color:var(--ink-soft); margin-top:6px; background:rgba(201,154,46,0.06);
    border-left:2px solid var(--gold); padding:6px 10px; border-radius:4px;
  }
  .claim-actions{ display:flex; gap:8px; }
  .action-btn{
    font-family:'Inter', sans-serif; font-weight:600; font-size:12.5px; border:none;
    border-radius:8px; padding:9px 16px; cursor:pointer; transition:opacity .15s ease;
  }
  .action-btn:hover{ opacity:0.85; }
  .approve-btn{ background:var(--green-ok); color:var(--paper); }
  .reject-btn{ background:transparent; color:var(--rust); border:1.5px solid var(--rust); }
  .claim-row.resolved{ opacity:0.55; }
  .resolved-tag{
    font-family:'JetBrains Mono', monospace; font-size:11px; letter-spacing:0.05em; text-transform:uppercase;
  }

  footer{
    text-align:center; margin-top:56px; font-size:12.5px; color:rgba(250,246,238,0.55);
    font-family:'JetBrains Mono', monospace; letter-spacing:0.04em;
  }

  @media (max-width:760px){
    table thead{ display:none; }
    table, tbody, tr, td{ display:block; width:100%; }
    tbody tr{ margin-bottom:14px; border:1px solid var(--tan-deep); border-radius:10px; padding:8px 4px; }
    tbody td{ display:flex; justify-content:space-between; border-bottom:none; padding:6px 10px; }
    tbody td::before{ content:attr(data-label); font-family:'JetBrains Mono', monospace; font-size:10px; color:var(--ink-soft); text-transform:uppercase; }
  }
</style>
</head>
<body>
<div class="wrap">

  <nav>
    <a href="index.php">HOME</a>
    <?php if (isset($_SESSION['username'])): ?>
        <a href="report_lost.php">REPORT LOST</a>
        <a href="report_found.php">REPORT FOUND</a>
    <?php endif; ?>
    <a href="items.php">BROWSE ITEM</a>
    <?php if (isset($_SESSION['username'])): ?>
        <a href="claims.php">Claim</a>
        <a href="profile.php">Edit Profile</a>
        <?php if ($_SESSION['username'] === 'admin'): ?>
            <a href="admin.php" class="active">ADMIN</a>
        <?php endif; ?>
        <a href="logout.php">SIGN OUT (<?php echo htmlspecialchars($_SESSION['username']); ?>)</a>
    <?php else: ?>
        <a href="login.php">SIGN IN</a>
        <a href="register.php">SIGN UP</a>
    <?php endif; ?>
  </nav>

  <header>
    <h1>Admin Dashboard</h1>
    <p>Review reports, update item status, and verify ownership claims.</p>
  </header>

  <div class="stats">
    <div class="stat-card"><div class="stat-num"><?php echo $total_reports; ?></div><div class="stat-label">Total Reports</div></div>
    <div class="stat-card"><div class="stat-num"><?php echo $pending_reports; ?></div><div class="stat-label">Pending</div></div>
    <div class="stat-card"><div class="stat-num"><?php echo $returned_reports; ?></div><div class="stat-label">Returned</div></div>
    <div class="stat-card"><div class="stat-num"><?php echo $claims_to_verify; ?></div><div class="stat-label">Claims to Verify</div></div>
  </div>

  <div class="panel">
    <div class="panel-head">
      <h2>Item Reports</h2>
      <span>UPDATE STATUS DIRECTLY FROM THE TABLE</span>
    </div>
    <table>
      <thead>
        <tr><th>Item</th><th>Type</th><th>Reported by</th><th>Location</th><th>Date</th><th>Status</th></tr>
      </thead>
      <tbody id="reportsBody"></tbody>
    </table>
  </div>

  <div class="panel">
    <div class="panel-head">
      <h2>Claims Awaiting Verification</h2>
      <span>APPROVE ONLY IF ANSWERS &amp; PHOTO MATCH</span>
    </div>
    <div id="claimsList"></div>
  </div>

  <footer>CAMPUS LOST &amp; FOUND · COLLEGE OFFICE</footer>
</div>

<script>
(function(){
  var reports = <?php echo json_encode($db_reports); ?>;

  var reportsBody = document.getElementById('reportsBody');
  reportsBody.innerHTML = '';
  
  reports.forEach(function(r){
    var tr = document.createElement('tr');
    
    // Build options based on item type
    var optionsHtml = '';
    if (r.type === 'lost') {
      optionsHtml = 
        '<option value="Pending"' + (r.status === 'Pending' ? ' selected' : '') + '>Pending</option>' +
        '<option value="Matched"' + (r.status === 'Matched' ? ' selected' : '') + '>Matched</option>' +
        '<option value="Returned"' + (r.status === 'Returned' ? ' selected' : '') + '>Returned</option>';
    } else {
      optionsHtml = 
        '<option value="Pending"' + (r.status === 'Pending' ? ' selected' : '') + '>Pending</option>' +
        '<option value="Claimed"' + (r.status === 'Claimed' ? ' selected' : '') + '>Claimed</option>' +
        '<option value="Returned"' + (r.status === 'Returned' ? ' selected' : '') + '>Returned</option>';
    }

    tr.innerHTML =
      '<td data-label="Item"><strong>' + escapeHtml(r.item) + '</strong></td>' +
      '<td data-label="Type"><span class="type-pill type-' + r.type + '">' + (r.type === 'lost' ? 'Lost' : 'Found') + '</span></td>' +
      '<td data-label="Reported by">' + escapeHtml(r.by) + '</td>' +
      '<td data-label="Location">' + escapeHtml(r.location) + '</td>' +
      '<td data-label="Date">' + escapeHtml(r.date) + '</td>' +
      '<td data-label="Status">' +
        '<select class="status-select" data-id="' + r.id + '" data-type="' + r.type + '">' +
          optionsHtml +
        '</select>' +
      '</td>';
    reportsBody.appendChild(tr);
  });

  // Handle reports status change via AJAX
  reportsBody.querySelectorAll('.status-select').forEach(function(select) {
    select.addEventListener('change', function() {
      var id = this.getAttribute('data-id');
      var type = this.getAttribute('data-type');
      var status = this.value;

      var xhr = new XMLHttpRequest();
      xhr.open('POST', 'admin.php', true);
      xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
      xhr.onload = function() {
        if (xhr.status === 200) {
          try {
            var res = JSON.parse(xhr.responseText);
            if (!res.success) {
              alert('Error: ' + res.message);
            }
          } catch(e) {
            console.error('Invalid response', xhr.responseText);
          }
        }
      };
      xhr.send('action=update_status&id=' + id + '&type=' + type + '&status=' + status);
    });
  });

  // Render Claims list
  var claims = <?php echo json_encode($db_claims); ?>;
  var claimsList = document.getElementById('claimsList');

  function renderClaims(){
    claimsList.innerHTML = '';
    if(claims.length === 0){
      claimsList.innerHTML = '<p style="font-size:13.5px;color:var(--ink-soft);padding:10px 4px;">No claims waiting for review.</p>';
      return;
    }
    claims.forEach(function(c, idx){
      var row = document.createElement('div');
      row.className = 'claim-row';
      if (c.status !== 'Pending') {
          row.classList.add('resolved');
      }

      var actionHtml = '';
      if (c.status === 'Pending') {
          actionHtml = 
            '<div class="claim-actions">' +
              '<button class="action-btn approve-btn" data-idx="' + idx + '" data-claim-id="' + c.id + '">Approve</button>' +
              '<button class="action-btn reject-btn" data-idx="' + idx + '" data-claim-id="' + c.id + '">Reject</button>' +
            '</div>';
      } else {
          var approved = c.status === 'Approved';
          actionHtml = 
            '<span class="resolved-tag" style="color:' + (approved ? 'var(--green-ok)' : 'var(--rust)') + '">' +
            (approved ? '✓ Approved' : '✕ Rejected') + '</span>';
      }

      var imageHtml = '';
      if (c.image && c.image !== '') {
          imageHtml = '<br><a href="uploads/' + encodeURIComponent(c.image) + '" target="_blank">' +
            '<img src="uploads/' + encodeURIComponent(c.image) + '" style="width:120px; height:120px; object-fit:cover; border-radius:6px; margin-top:8px; display:block;" alt="Proof Image" />' +
            '</a>';
      }

      row.innerHTML =
        '<div class="claim-info">' +
          '<p class="name">' + escapeHtml(c.item) + '</p>' +
          '<p class="meta">Claimed by ' + escapeHtml(c.by) + ' · ' + escapeHtml(c.submitted) + '</p>' +
          '<div class="claim-answers">Colour: "' + escapeHtml(c.color) + '"<br>Distinguishing Marks: "' + escapeHtml(c.contents) + '"' + imageHtml + '</div>' +
        '</div>' +
        actionHtml;
      claimsList.appendChild(row);
    });

    claimsList.querySelectorAll('.action-btn').forEach(function(btn){
      btn.addEventListener('click', function(){
        var row = btn.closest('.claim-row');
        var claimId = btn.getAttribute('data-claim-id');
        var approved = btn.classList.contains('approve-btn');
        var status = approved ? 'Approved' : 'Rejected';

        // Submit via AJAX
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'admin.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
          if (xhr.status === 200) {
            try {
              var res = JSON.parse(xhr.responseText);
              if (res.success) {
                row.classList.add('resolved');
                row.querySelector('.claim-actions').outerHTML =
                  '<span class="resolved-tag" style="color:' + (approved ? 'var(--green-ok)' : 'var(--rust)') + '">' +
                  (approved ? '✓ Approved' : '✕ Rejected') + '</span>';
              } else {
                alert('Error: ' + res.message);
              }
            } catch(e) {
              console.error(e);
            }
          }
        };
        xhr.send('action=resolve_claim&claim_id=' + claimId + '&status=' + status);
      });
    });
  }

  function escapeHtml(str){
    var div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
  }

  renderClaims();
})();
</script>
</body>
</html>