<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Get form data
    $item_name   = trim($_POST['itemName']);
    $description = trim($_POST['description']);
    $date_lost   = $_POST['dateLost'];
    $location    = trim($_POST['location']);
    $contact     = trim($_POST['contact']);

    // Validation
    if (
        empty($item_name) ||
        empty($description) ||
        empty($date_lost) ||
        empty($location) ||
        empty($contact)
    ) {
        die('All fields are required.');
    }

    // Upload folder
    $uploadDir = 'uploads/';

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $imagePath = '';

    // Handle image upload
    if (isset($_FILES['imageInput']) && $_FILES['imageInput']['error'] == 0) {

        $fileName = time() . '_' . basename($_FILES['imageInput']['name']);
        $targetFile = $uploadDir . $fileName;

        $allowed = ['image/jpeg', 'image/png'];

        if (!in_array($_FILES['imageInput']['type'], $allowed)) {
            die('Only JPG and PNG images are allowed.');
        }

        if ($_FILES['imageInput']['size'] > 5 * 1024 * 1024) {
            die('Image size must be less than 5MB.');
        }

        if (move_uploaded_file($_FILES['imageInput']['tmp_name'], $targetFile)) {
            $imagePath = $targetFile;
        } else {
            die('Failed to upload image.');
        }
    }

    // Insert into database
    $sql = 'INSERT INTO lost_items
            (item_name, description, date_lost, location_lost, contact, image)
            VALUES (?, ?, ?, ?, ?, ?)';

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param(
        $stmt,
        'ssssss',
        $item_name,
        $description,
        $date_lost,
        $location,
        $contact,
        $imagePath
    );

    if (mysqli_stmt_execute($stmt)) {
        echo '
        <script>
            alert("Lost item report submitted successfully!");
            window.location.href = "items.php";
        </script>';
    } else {
        echo 'Database Error: ' . mysqli_error($conn);
    }

    mysqli_stmt_close($stmt);
    mysqli_close($conn);
}
?>