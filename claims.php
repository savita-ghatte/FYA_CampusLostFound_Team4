<?php
// claims.php - Submit a Claim for an Item
session_start();
include "db.php";

if (isset($_SESSION['username']) && !isset($_SESSION['role'])) {
    $_SESSION['role'] = ($_SESSION['username'] === 'admin') ? 'admin' : 'user';
}

// Redirect to login if not authenticated
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Restrict access to admin only
if (empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
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
$item_id   = intval($_GET['item_id'] ?? $_POST['item_id'] ?? 0);
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
    $item_id   = intval($found_items_list[0]['item_id']);
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
    <title>Claim Item Ownership | CampusConnect - Zeal College</title>

    <!-- Font Awesome 6 Icons & Google Fonts -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/theme.css">

    <!-- Page-specific styles for the claim form's field states, upload zone and preview card.
         Scoped to this page so they work even if css/theme.css doesn't define them. -->
    <style>
        .field .error-msg { display: none; color: var(--danger, #c0392b); font-size: 13px; margin-top: 6px; }
        .field.invalid .error-msg { display: block; }
        .field.invalid .form-control,
        .field.invalid .upload-zone { border-color: var(--danger, #c0392b) !important; }
        .field.valid .form-control,
        .field.valid .upload-zone { border-color: var(--green-ok, var(--success, #3E6B4F)) !important; }

        .char-count { display: block; text-align: right; font-size: 12px; color: var(--ink-soft, #6b7280); margin-top: 4px; }

        .upload-zone {
            position: relative;
            border: 2px dashed rgba(0,0,0,0.18);
            border-radius: 12px;
            padding: 28px 16px;
            text-align: center;
            cursor: pointer;
            transition: border-color .15s ease, background .15s ease;
            background: rgba(0,0,0,0.015);
        }
        .upload-zone:hover,
        .upload-zone.dragover { border-color: var(--gold, #C99A2E); background: rgba(201,154,46,0.06); }
        .upload-zone input[type="file"] {
            position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%;
        }
        .upload-zone .upload-icon { font-size: 26px; display: block; margin-bottom: 8px; }
        .upload-zone p { margin: 0; font-size: 13px; color: var(--ink-soft, #6b7280); }

        .preview-polaroid {
            display: none;
            align-items: center;
            gap: 14px;
            margin-top: 14px;
            padding: 10px;
            border: 1px solid rgba(0,0,0,0.1);
            border-radius: 10px;
            background: #fff;
        }
        .preview-polaroid.show { display: flex; }
        .preview-polaroid .thumb { width: 64px; height: 64px; object-fit: cover; border-radius: 6px; }
        .preview-polaroid .preview-meta { flex: 1; min-width: 0; }
        .preview-polaroid .cap { font-size: 13px; font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .preview-polaroid .size { font-size: 12px; color: var(--ink-soft, #6b7280); }
        .remove-file {
            border: none; background: none; color: var(--danger, #c0392b);
            font-size: 12px; cursor: pointer; padding: 0; margin-top: 4px;
        }
        .remove-file:hover { text-decoration: underline; }
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
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <a href="items.php" class="nav-link">
                    <i class="fa-solid fa-boxes-stacked"></i> Browse Items
                </a>
                <a href="claims.php" class="nav-link active">
                    <i class="fa-solid fa-clipboard-check"></i> Claims
                </a>
            <?php endif; ?>
            <a href="profile.php" class="nav-link">
                <i class="fa-solid fa-user-gear"></i> Profile
            </a>
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
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

                    <form action="claims.php" method="POST" enctype="multipart/form-data" id="claimForm" novalidate>

                        <div class="form-group field" id="f-item">
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
                            <span class="error-msg" role="alert">Please select an item to claim.</span>
                        </div>

                        <div class="form-group field" id="f-color">
                            <label for="colorAnswer" class="form-label">Specific Color / Shade</label>
                            <div class="input-icon-group">
                                <i class="fa-solid fa-palette"></i>
                                <input type="text" id="colorAnswer" name="colorAnswer" class="form-control" placeholder="e.g. Metallic sky-blue cap or Navy blue" required>
                            </div>
                            <span class="error-msg" role="alert">Please describe the color in a bit more detail.</span>
                        </div>

                        <div class="form-group field" id="f-contents">
                            <label for="contentsAnswer" class="form-label">Distinguishing Marks, Serial Numbers, or Contents</label>
                            <div class="input-icon-group">
                                <i class="fa-solid fa-list-check" style="top:18px;"></i>
                                <textarea id="contentsAnswer" name="contentsAnswer" class="form-control" placeholder="Mention stickers, keychains, scratch marks, room numbers, or contents inside..." required></textarea>
                            </div>
                            <span class="char-count" id="contentsCount">0 / 5</span>
                            <span class="error-msg" role="alert">Please add a few more details (at least 5 characters).</span>
                        </div>

                        <div class="form-group field" id="f-proof">
                            <label for="imageInput" class="form-label">Upload Proof Photo / Purchase Receipt (Required)</label>

                            <div class="upload-zone" id="uploadZone" tabindex="0" role="button" aria-label="Upload proof photo">
                                <span class="upload-icon"><i class="fa-solid fa-file-shield"></i></span>
                                <p><strong>Click to upload</strong> or drag a photo here<br>JPG or PNG, up to 5MB</p>
                                <input type="file" id="imageInput" name="proof" accept="image/jpeg,image/png,image/jpg" required>
                            </div>
                            <span class="error-msg" role="alert">Please attach a JPG or PNG under 5MB.</span>

                            <div class="preview-polaroid" id="previewBox">
                                <img class="thumb" id="previewImg" src="" alt="Preview of uploaded proof photo">
                                <div class="preview-meta">
                                    <div class="cap" id="previewName"></div>
                                    <div class="size" id="previewSize"></div>
                                    <button type="button" class="remove-file" id="removeFile">Remove</button>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-success btn-block" id="submitBtn" style="padding:14px; margin-top: 10px;">
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

(function () {
    function setInvalid(el, msg) {
        el.classList.add('invalid');
        el.classList.remove('valid');
        var input = el.querySelector('input, select, textarea');
        if (input) input.setAttribute('aria-invalid', 'true');
        if (msg) {
            var em = el.querySelector('.error-msg');
            if (em) em.textContent = msg;
        }
    }
    function setValid(el) {
        el.classList.remove('invalid');
        el.classList.add('valid');
        var input = el.querySelector('input, select, textarea');
        if (input) input.setAttribute('aria-invalid', 'false');
    }
    function clearState(el) {
        el.classList.remove('invalid', 'valid');
        var input = el.querySelector('input, select, textarea');
        if (input) input.removeAttribute('aria-invalid');
    }

    var fItem = document.getElementById('f-item');
    var fColor = document.getElementById('f-color');
    var fContents = document.getElementById('f-contents');
    var fProof = document.getElementById('f-proof');
    var claimForm = document.getElementById('claimForm');
    var submitBtn = document.getElementById('submitBtn');
    var contentsInput = document.getElementById('contentsAnswer');
    var contentsCount = document.getElementById('contentsCount');
    var itemSelect = document.getElementById('item_id');

    function validateItem() {
        var ok = parseInt(itemSelect.value, 10) > 0;
        ok ? setValid(fItem) : setInvalid(fItem);
        return ok;
    }

    function validateColor() {
        var ok = document.getElementById('colorAnswer').value.trim().length >= 2;
        ok ? setValid(fColor) : setInvalid(fColor);
        return ok;
    }

    function validateContents() {
        var len = contentsInput.value.trim().length;
        var ok = len >= 5;
        ok ? setValid(fContents) : setInvalid(fContents);
        return ok;
    }

    function updateCharCount() {
        var len = contentsInput.value.trim().length;
        contentsCount.textContent = len + ' / 5';
        contentsCount.style.color = len >= 5 ? 'var(--green-ok, #3E6B4F)' : 'var(--ink-soft, #6b7280)';
    }

    itemSelect.addEventListener('change', validateItem);
    document.getElementById('colorAnswer').addEventListener('blur', validateColor);
    contentsInput.addEventListener('blur', validateContents);
    contentsInput.addEventListener('input', updateCharCount);

    var uploadedFile = null;

    function formatSize(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(0) + ' KB';
        return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    }

    function validateProof() {
        var ok = !!uploadedFile;
        ok ? setValid(fProof) : setInvalid(fProof);
        return ok;
    }

    var uploadZone = document.getElementById('uploadZone');
    var imageInput = document.getElementById('imageInput');
    var previewBox = document.getElementById('previewBox');
    var previewImg = document.getElementById('previewImg');
    var previewName = document.getElementById('previewName');
    var previewSize = document.getElementById('previewSize');
    var removeFile = document.getElementById('removeFile');

    function handleFile(file) {
        if (!file) return;
        var validTypes = ['image/jpeg', 'image/png', 'image/jpg'];
        var maxSize = 5 * 1024 * 1024;
        var fileExt = file.name.split('.').pop().toLowerCase();

        if ((validTypes.indexOf(file.type) === -1 && ['jpg', 'jpeg', 'png'].indexOf(fileExt) === -1) || file.size > maxSize) {
            uploadedFile = null;
            previewBox.classList.remove('show');
            setInvalid(fProof, 'Please attach a JPG or PNG under 5MB.');
            imageInput.value = '';
            return;
        }

        uploadedFile = file;
        setValid(fProof);

        var reader = new FileReader();
        reader.onload = function (e) {
            previewImg.src = e.target.result;
            previewName.textContent = file.name;
            previewSize.textContent = formatSize(file.size);
            previewBox.classList.add('show');
        };
        reader.readAsDataURL(file);
    }

    uploadZone.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            imageInput.click();
        }
    });
    imageInput.addEventListener('change', function (e) { handleFile(e.target.files[0]); });

    ['dragover', 'dragenter'].forEach(function (evt) {
        uploadZone.addEventListener(evt, function (e) {
            e.preventDefault();
            uploadZone.classList.add('dragover');
        });
    });
    ['dragleave', 'drop'].forEach(function (evt) {
        uploadZone.addEventListener(evt, function (e) {
            e.preventDefault();
            uploadZone.classList.remove('dragover');
        });
    });
    uploadZone.addEventListener('drop', function (e) { handleFile(e.dataTransfer.files[0]); });

    removeFile.addEventListener('click', function (e) {
        e.stopPropagation();
        uploadedFile = null;
        imageInput.value = '';
        previewBox.classList.remove('show');
        clearState(fProof);
    });

    claimForm.addEventListener('submit', function (e) {
        e.preventDefault();

        var results = [validateItem(), validateColor(), validateContents(), validateProof()];
        var allValid = results.every(Boolean);

        if (allValid) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Submitting…';
            setTimeout(function () {
                claimForm.submit();
            }, 300);
        } else {
            var firstInvalid = document.querySelector('.field.invalid');
            if (firstInvalid) {
                firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                var input = firstInvalid.querySelector('input, select, textarea');
                if (input) input.focus();
            }
        }
    });

    updateCharCount();
})();
</script>
</body>
</html>