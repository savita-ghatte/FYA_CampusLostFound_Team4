<?php
// claims.php - Submit a Claim for an Item
session_start();
include "db.php";

// Redirect to login if not authenticated
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

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
    
    // Server-side validation
    if ($item_id <= 0) {
        $error_msg = "Please select a valid item to claim.";
    } elseif (empty($colour) || empty($distinguishing_marks)) {
        $error_msg = "Please fill in all required fields.";
    } elseif (!isset($_FILES['proof']) || $_FILES['proof']['error'] !== UPLOAD_ERR_OK) {
        $error_msg = "Please upload photo proof.";
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
                // Save to claims table
                $stmt = $conn->prepare("INSERT INTO claims (item_id, colour, distinguishing_marks, image, claim_status) VALUES (?, ?, ?, ?, 'Pending')");
                if ($stmt) {
                    $stmt->bind_param("isss", $item_id, $colour, $distinguishing_marks, $filename);
                    if ($stmt->execute()) {
                        $success_msg = true;
                    } else {
                        $error_msg = "Database insert failed. Please try again.";
                    }
                    $stmt->close();
                } else {
                    $error_msg = "Database statement preparation failed.";
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
<title>Campus Lost&amp;Found — Claim an Item</title>
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
    max-width:640px;
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
    font-size:clamp(28px, 5vw, 38px);
    line-height:1.05;
    color:var(--paper);
    margin:0 0 10px;
    letter-spacing:-0.01em;
  }
  header p{
    color:rgba(250,246,238,0.82);
    font-size:15px;
    max-width:460px;
    margin:0 auto;
    line-height:1.55;
  }

  /* ---- Card ---- */
  .card{
    background:var(--paper);
    border-radius:var(--radius);
    box-shadow:var(--shadow-card);
    position:relative;
    padding:38px 32px 34px;
  }

  .item-strip{
    display:flex;
    align-items:center;
    gap:12px;
    background:rgba(201,154,46,0.1);
    border:1px solid rgba(201,154,46,0.3);
    border-radius:11px;
    padding:12px 16px;
    margin-bottom:28px;
  }
  .item-strip .icon{ font-size:24px; flex-shrink:0; }
  .item-strip .label{
    font-family:'JetBrains Mono', monospace;
    font-size:10.5px;
    letter-spacing:0.08em;
    text-transform:uppercase;
    color:var(--gold-deep);
    margin-bottom:2px;
  }
  .item-strip .name{
    font-family:'Fraunces', serif;
    font-size:17px;
    font-weight:600;
  }

  /* ---- Fields ---- */
  .field{ margin-bottom:22px; }
  .field-head{
    display:flex;
    justify-content:space-between;
    align-items:baseline;
    gap:8px;
    margin-bottom:7px;
  }
  .field label{
    display:block;
    font-size:12.5px;
    font-weight:600;
  }
  .field label .req{ color:var(--rust); margin-left:2px; }
  .field .hint{ font-size:12px; color:var(--ink-soft); margin:0 0 8px; }
  .char-count{
    font-family:'JetBrains Mono', monospace;
    font-size:10.5px;
    color:var(--ink-soft);
    white-space:nowrap;
  }

  .field input[type="text"],
  .field textarea{
    width:100%;
    font-family:'Inter', sans-serif;
    font-size:14.5px;
    color:var(--ink);
    background:#fff;
    border:1.5px solid var(--tan-deep);
    border-radius:9px;
    padding:11px 13px;
    transition:border-color .15s ease, box-shadow .15s ease;
  }
  .field textarea{ resize:vertical; min-height:70px; font-family:'Inter', sans-serif; }
  .field input:focus,
  .field textarea:focus{
    outline:none;
    border-color:var(--gold-deep);
    box-shadow:0 0 0 4px rgba(201,154,46,0.18);
  }
  .field input:focus-visible,
  .field textarea:focus-visible{
    outline:3px solid var(--gold-deep);
    outline-offset:2px;
  }

  .error-msg{
    display:none;
    font-size:12.5px;
    color:var(--rust);
    margin-top:6px;
    font-weight:500;
  }
  .field.invalid input,
  .field.invalid textarea{
    border-color:var(--rust);
    box-shadow:0 0 0 4px rgba(178,58,46,0.12);
  }
  .field.invalid .error-msg{ display:block; }
  .field.valid input,
  .field.valid textarea{ border-color:var(--green-ok); }

  /* ---- Upload zone ---- */
  .upload-zone{
    border:1.5px dashed var(--tan-deep);
    border-radius:11px;
    background:repeating-linear-gradient(135deg, rgba(201,154,46,0.04) 0 10px, transparent 10px 20px);
    padding:24px;
    text-align:center;
    cursor:pointer;
    transition:border-color .15s ease, background .15s ease;
  }
  .upload-zone:hover{ border-color:var(--gold-deep); }
  .upload-zone.dragover{
    border-color:var(--gold-deep);
    background:rgba(201,154,46,0.08);
  }
  .upload-zone:focus-visible{
    outline:3px solid var(--gold-deep);
    outline-offset:2px;
  }
  .upload-zone p{ margin:0; font-size:13.5px; color:var(--ink-soft); }
  .upload-zone .upload-icon{ font-size:26px; margin-bottom:8px; display:block; }
  .upload-zone input[type="file"]{ display:none; }

  .preview-polaroid{
    display:none;
    align-items:flex-start;
    gap:12px;
    margin-top:14px;
    background:#fff;
    padding:10px 10px 10px;
    border-radius:4px;
    box-shadow:var(--shadow-soft);
    max-width:100%;
  }
  .preview-polaroid.show{ display:flex; }
  .preview-polaroid .thumb{
    width:64px;
    height:64px;
    border-radius:6px;
    object-fit:cover;
    flex-shrink:0;
  }
  .preview-meta{ min-width:0; flex:1; }
  .preview-meta .cap{
    font-family:'JetBrains Mono', monospace;
    font-size:11.5px;
    color:var(--ink);
    font-weight:600;
    word-break:break-all;
  }
  .preview-meta .size{
    font-family:'JetBrains Mono', monospace;
    font-size:10.5px;
    color:var(--ink-soft);
    margin-top:2px;
  }
  .remove-file{
    font-family:'JetBrains Mono', monospace;
    font-size:11px;
    letter-spacing:0.04em;
    text-transform:uppercase;
    color:var(--rust);
    background:none;
    border:none;
    cursor:pointer;
    padding:0;
    width:auto;
    margin-top:6px;
    text-decoration:underline;
  }
  .remove-file:hover{ color:#8f2e24; }

  /* ---- Buttons ---- */
  button[type="submit"]{
    font-family:'Inter', sans-serif;
    font-weight:600;
    font-size:14.5px;
    border:none;
    border-radius:9px;
    padding:13px 22px;
    cursor:pointer;
    width:100%;
    background:var(--ink);
    color:var(--paper);
    transition:background .15s ease, box-shadow .12s ease, opacity .15s ease;
  }
  button[type="submit"]:hover{ background:var(--ink-soft); box-shadow:var(--shadow-soft); }
  button[type="submit"]:focus-visible{ outline:3px solid var(--gold-deep); outline-offset:2px; }
  button[type="submit"]:active{ transform:translateY(1px); }
  button[type="submit"]:disabled{
    opacity:0.6;
    cursor:not-allowed;
    transform:none;
  }

  .success-note{
    display:none;
    margin-top:16px;
    padding:12px 14px;
    background:rgba(62,107,79,0.1);
    border:1px solid rgba(62,107,79,0.4);
    color:var(--green-ok);
    border-radius:99px;
    font-size:13.5px;
    font-weight:500;
  }
  .success-note.show{ display:block; }

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
    <a href="items.php">Browse Items</a>
    <?php if (isset($_SESSION['username'])): ?>
        <a href="claims.php" class="active">Claim</a>
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
    <h1>Claim this item</h1>
    <p>Answer a couple of questions and attach a matching photo — only the real owner will know the details.</p>
  </header>

  <div class="card">
    <div class="item-strip">
      <span class="icon">🔎</span>
      <div>
        <div class="label">Claiming item</div>
        <div class="name" id="itemNameDisplay"><?php echo htmlspecialchars($item_name); ?></div>
      </div>
    </div>

    <?php if ($error_msg): ?>
        <div style="border:1.5px solid var(--rust); color:var(--rust); padding:12px; border-radius:9px; margin-bottom:20px; font-size:14px; font-weight:500; background:rgba(178,58,46,0.05);">
            ⚠ <?php echo htmlspecialchars($error_msg); ?>
        </div>
    <?php endif; ?>

    <form id="claimForm" method="POST" action="claims.php" enctype="multipart/form-data" novalidate>
      <div class="field" id="f-item">
        <label for="itemSelect">Select Item to Claim <span class="req">*</span></label>
        <select id="itemSelect" name="item_id" style="width:100%; padding:12px; border-radius:8px; border:1px solid var(--tan-deep); font-size:15px; background:white; font-family:'Inter',sans-serif; color:var(--ink);" onchange="var selectedText = this.options[this.selectedIndex].text; document.getElementById('itemNameDisplay').textContent = selectedText;">
          <?php if (empty($found_items_list)): ?>
            <option value="0">No found items currently available</option>
          <?php else: ?>
            <?php foreach ($found_items_list as $fitem): ?>
              <option value="<?php echo $fitem['item_id']; ?>" <?php echo ($item_id == $fitem['item_id']) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($fitem['item_name'] . ' (Found at ' . $fitem['location'] . ')'); ?>
              </option>
            <?php endforeach; ?>
          <?php endif; ?>
        </select>
        <p class="error-msg" id="err-item" role="alert">Please select an item to claim.</p>
      </div>

      <div class="field" id="f-color">
        <label for="colorAnswer">What colour was it? <span class="req">*</span></label>
        <input type="text" id="colorAnswer" name="colorAnswer" placeholder="e.g. Navy blue with a grey strap" aria-describedby="err-color" value="<?php echo htmlspecialchars($colour ?? ''); ?>">
        <p class="error-msg" id="err-color" role="alert">Please answer this question.</p>
      </div>

      <div class="field" id="f-contents">
        <div class="field-head">
          <label for="contentsAnswer">What's inside / any distinguishing marks? <span class="req">*</span></label>
          <span class="char-count" id="contentsCount">0 / 5</span>
        </div>
        <textarea id="contentsAnswer" name="contentsAnswer" placeholder="e.g. A notebook, a keychain, a small scratch on the side" aria-describedby="err-contents"><?php echo htmlspecialchars($distinguishing_marks ?? ''); ?></textarea>
        <p class="error-msg" id="err-contents" role="alert">Please answer this question (at least 5 characters).</p>
      </div>

      <div class="field" id="f-proof">
        <label>Photo proof from your phone <span class="req">*</span></label>
        <p class="hint">Upload another photo of the same item, taken before it was lost.</p>

        <div class="upload-zone" id="uploadZone" tabindex="0" role="button" aria-label="Upload proof photo">
          <span class="upload-icon">📎</span>
          <p><strong>Click to upload</strong> or drag a photo here<br>JPG or PNG, up to 5MB</p>
          <input type="file" id="imageInput" name="proof" accept="image/png, image/jpeg">
        </div>
        <p class="error-msg" id="err-proof" role="alert">Please attach a JPG or PNG under 5MB.</p>

        <div class="preview-polaroid" id="previewBox">
          <img class="thumb" id="previewImg" src="" alt="Preview of uploaded proof photo">
          <div class="preview-meta">
            <div class="cap" id="previewName"></div>
            <div class="size" id="previewSize"></div>
            <button type="button" class="remove-file" id="removeFile">Remove</button>
          </div>
        </div>
      </div>

      <button type="submit" id="submitBtn">Submit Claim for Verification</button>
      <div class="success-note <?php echo $success_msg ? 'show' : ''; ?>" id="claimSuccess" role="status" aria-live="polite">Claim submitted — the office will review your answers and photo before releasing the item.</div>
    </form>
  </div>

  <footer>CAMPUS LOST&amp;FOUND · COLLEGE OFFICE</footer>
</div>

<script>
(function(){
  function setInvalid(el, msg){
    el.classList.add('invalid');
    el.classList.remove('valid');
    var input = el.querySelector('input, textarea');
    if(input) input.setAttribute('aria-invalid', 'true');
    if(msg){ el.querySelector('.error-msg').textContent = msg; }
  }
  function setValid(el){
    el.classList.remove('invalid');
    el.classList.add('valid');
    var input = el.querySelector('input, textarea');
    if(input) input.setAttribute('aria-invalid', 'false');
  }
  function clearState(el){
    el.classList.remove('invalid', 'valid');
    var input = el.querySelector('input, textarea');
    if(input) input.removeAttribute('aria-invalid');
  }

  var fColor = document.getElementById('f-color');
  var fContents = document.getElementById('f-contents');
  var fProof = document.getElementById('f-proof');
  var claimForm = document.getElementById('claimForm');
  var claimSuccess = document.getElementById('claimSuccess');
  var submitBtn = document.getElementById('submitBtn');
  var contentsInput = document.getElementById('contentsAnswer');
  var contentsCount = document.getElementById('contentsCount');

  function validateColor(){
    var ok = document.getElementById('colorAnswer').value.trim().length >= 2;
    ok ? setValid(fColor) : setInvalid(fColor);
    return ok;
  }

  function validateContents(){
    var len = contentsInput.value.trim().length;
    var ok = len >= 5;
    ok ? setValid(fContents) : setInvalid(fContents);
    return ok;
  }

  function updateCharCount(){
    var len = contentsInput.value.trim().length;
    contentsCount.textContent = len + ' / 5';
    contentsCount.style.color = len >= 5 ? 'var(--green-ok)' : 'var(--ink-soft)';
  }

  document.getElementById('colorAnswer').addEventListener('blur', validateColor);
  contentsInput.addEventListener('blur', validateContents);
  contentsInput.addEventListener('input', updateCharCount);

  var uploadedFile = null;

  function formatSize(bytes){
    if(bytes < 1024) return bytes + ' B';
    if(bytes < 1024 * 1024) return (bytes / 1024).toFixed(0) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
  }

  function validateProof(){
    var hasFile = !!uploadedFile || document.getElementById('imageInput').value !== '';
    hasFile ? setValid(fProof) : setInvalid(fProof);
    return hasFile;
  }

  var uploadZone = document.getElementById('uploadZone');
  var imageInput = document.getElementById('imageInput');
  var previewBox = document.getElementById('previewBox');
  var previewImg = document.getElementById('previewImg');
  var previewName = document.getElementById('previewName');
  var previewSize = document.getElementById('previewSize');
  var removeFile = document.getElementById('removeFile');

  function handleFile(file){
    if(!file){ return; }
    var validTypes = ['image/jpeg', 'image/png', 'image/jpg'];
    var maxSize = 5 * 1024 * 1024;

    var fileExt = file.name.split('.').pop().toLowerCase();

    if((validTypes.indexOf(file.type) === -1 && ['jpg', 'jpeg', 'png'].indexOf(fileExt) === -1) || file.size > maxSize){
      uploadedFile = null;
      previewBox.classList.remove('show');
      setInvalid(fProof, 'Please attach a JPG or PNG under 5MB.');
      imageInput.value = '';
      return;
    }

    uploadedFile = file;
    setValid(fProof);

    var reader = new FileReader();
    reader.onload = function(e){
      previewImg.src = e.target.result;
      previewName.textContent = file.name;
      previewSize.textContent = formatSize(file.size);
      previewBox.classList.add('show');
    };
    reader.readAsDataURL(file);
  }

  uploadZone.addEventListener('click', function(){ imageInput.click(); });
  uploadZone.addEventListener('keydown', function(e){
    if(e.key === 'Enter' || e.key === ' '){
      e.preventDefault();
      imageInput.click();
    }
  });
  imageInput.addEventListener('change', function(e){ handleFile(e.target.files[0]); });

  ['dragover', 'dragenter'].forEach(function(evt){
    uploadZone.addEventListener(evt, function(e){
      e.preventDefault();
      uploadZone.classList.add('dragover');
    });
  });
  ['dragleave', 'drop'].forEach(function(evt){
    uploadZone.addEventListener(evt, function(e){
      e.preventDefault();
      uploadZone.classList.remove('dragover');
    });
  });
  uploadZone.addEventListener('drop', function(e){ handleFile(e.dataTransfer.files[0]); });

  removeFile.addEventListener('click', function(){
    uploadedFile = null;
    imageInput.value = '';
    previewBox.classList.remove('show');
    clearState(fProof);
  });

  function validateItem(){
    var sel = document.getElementById('itemSelect');
    var fItem = document.getElementById('f-item');
    if(!sel || !fItem) return true;
    var ok = parseInt(sel.value, 10) > 0;
    ok ? setValid(fItem) : setInvalid(fItem, 'Please select an item to claim.');
    return ok;
  }

  var itemSel = document.getElementById('itemSelect');
  if(itemSel) itemSel.addEventListener('change', validateItem);

  claimForm.addEventListener('submit', function(e){
    e.preventDefault();
    claimSuccess.classList.remove('show');

    var results = [validateItem(), validateColor(), validateContents(), validateProof()];
    var allValid = results.every(Boolean);

    if(allValid){
      submitBtn.disabled = true;
      submitBtn.textContent = 'Submitting…';

      setTimeout(function(){
        claimForm.submit();
      }, 400);
    } else {
      var firstInvalid = document.querySelector('.field.invalid');
      if(firstInvalid){
        firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
        var input = firstInvalid.querySelector('input, textarea');
        if(input) input.focus();
      }
    }
  });

  updateCharCount();
})();
</script>
</body>
</html>