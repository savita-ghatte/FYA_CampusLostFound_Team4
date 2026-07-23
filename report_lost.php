<?php
// report_lost.php - Report a Lost Item
session_start();
include "db.php";

// Redirect to login if not authenticated
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

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
    } elseif (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        $error_msg = "Please upload a photo of the item.";
    } else {
        $file = $_FILES['photo'];
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
            $filename = uniqid('lost_', true) . '.' . $file_ext;
            $upload_dir = __DIR__ . '/uploads';
            if (!is_dir($upload_dir)) {
                @mkdir($upload_dir, 0777, true);
            }
            $upload_path = $upload_dir . '/' . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
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

<title>Campus Lost & Found — Report Lost Item</title>

<link rel="preconnect" href="https://fonts.googleapis.com">

<link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@600;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet">

<style>

:root{
--ink:#1F2A38;
--ink-soft:#3D4A5C;
--paper:#FAF6EE;
--tan:#C9AE79;
--gold:#C99A2E;
--gold-dark:#A87D1E;
--rust:#B23A2E;
--green:#3E6B4F;
--board:#8C6A46;
--board-dark:#7A5A3A;
--radius:14px;
}


*{
box-sizing:border-box;
}


body{

margin:0;
min-height:100vh;

font-family:'Inter',sans-serif;

background:
linear-gradient(
160deg,
var(--board),
var(--board-dark)
);

color:var(--ink);

}


.wrap{

max-width:760px;
margin:auto;

padding:32px 24px 80px;

}



nav{

display:flex;
justify-content:center;
gap:10px;
flex-wrap:wrap;
margin-bottom:40px;

}


nav a{

font-family:'JetBrains Mono',monospace;

font-size:12px;

text-decoration:none;

text-transform:uppercase;

color:white;

padding:8px 16px;

border-radius:50px;

background:rgba(31,42,56,.4);

border:1px solid rgba(255,255,255,.3);

}


nav a.active{

background:var(--gold);

color:var(--ink);

}



header{

text-align:center;

margin-bottom:40px;

}


h1{

font-family:'Fraunces',serif;

font-size:clamp(32px,5vw,44px);

color:var(--paper);

margin-bottom:12px;

}


header p{

color:rgba(250,246,238,.85);

line-height:1.6;

}



.card{

background:var(--paper);

padding:40px 36px;

border-radius:var(--radius);

box-shadow:0 18px 40px rgba(0,0,0,.25);

position:relative;

}



.pin{

position:absolute;

top:-12px;

left:50%;

transform:translateX(-50%);

height:22px;

width:22px;

background:var(--gold);

border-radius:50%;

}



.tag-id{

font-family:'JetBrains Mono',monospace;

font-size:12px;

color:var(--gold-dark);

}



.card h2{

font-family:'Fraunces',serif;

font-size:28px;

}



.sub{

color:var(--ink-soft);

}



.field{

margin-bottom:20px;

}


label{

display:block;

font-weight:600;

font-size:13px;

margin-bottom:7px;

}


.req{

color:var(--rust);

}


input,
textarea{

width:100%;

padding:12px;

font-size:15px;

border:1.5px solid var(--tan);

border-radius:9px;

font-family:'Inter',sans-serif;

}


textarea{

min-height:100px;

resize:vertical;

}


input:focus,
textarea:focus{

outline:none;

border-color:var(--gold-dark);

}



.row-2{

display:grid;

grid-template-columns:1fr 1fr;

gap:16px;

}


@media(max-width:550px){

.row-2{

grid-template-columns:1fr;

}

}


.error-msg{

display:none;

color:var(--rust);

font-size:12px;

margin-top:6px;

}


.field.invalid input,
.field.invalid textarea{

border-color:var(--rust);

}


.field.invalid .error-msg{

display:block;

}


.field.valid input,
.field.valid textarea{

border-color:var(--green);

}


.upload-zone{

border:2px dashed var(--tan);

padding:25px;

border-radius:12px;

text-align:center;

cursor:pointer;

}


.upload-zone:hover{

border-color:var(--gold-dark);

}


.upload-icon{

font-size:30px;

display:block;

margin-bottom:10px;

}


.upload-zone input{

display:none;

}


.preview-polaroid{

display:none;

margin-top:15px;

width:180px;

background:white;

padding:10px 10px 25px;

box-shadow:0 5px 15px rgba(0,0,0,.2);

}



.preview-polaroid.show{

display:block;

}


.preview-polaroid img{

width:100%;

height:150px;

object-fit:cover;

}


.cap{

font-family:'JetBrains Mono';

font-size:10px;

text-align:center;

margin-top:8px;

}

button{

width:100%;

padding:14px;

border:none;

border-radius:10px;

background:var(--gold);

color:var(--ink);

font-size:15px;

font-weight:700;

cursor:pointer;

}


button:hover{

background:var(--gold-dark);

color:white;

}



.success-note{

display:none;

margin-top:15px;

padding:12px;

border-radius:10px;

background:rgba(62,107,79,.12);

border:1px solid rgba(62,107,79,.4);

color:var(--green);

}


.success-note.show{

display:block;

}



footer{

text-align:center;

margin-top:50px;

color:white;

font-family:'JetBrains Mono',monospace;

font-size:12px;

}


</style>

</head>


<body>


<div class="wrap">


<nav>
    <a href="index.php">Home</a>
    <?php if (isset($_SESSION['username'])): ?>
        <a href="report_lost.php" class="active">Report Lost</a>
        <a href="report_found.php">Report Found</a>
    <?php endif; ?>
    <a href="items.php">Browse Items</a>
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

<h1>Report a Lost Item</h1>

<p>
Give as much detail as you can — specific details help the office confirm the real owner.
</p>

</header>




<div class="card">


<div class="pin"></div>


<span class="tag-id">
TAG · LOST-ITEM
</span>


<h2>Lost Item Details</h2>


<p class="sub">
All fields marked * are required.
</p>

<?php if ($error_msg): ?>
    <div style="border:1px solid var(--rust); color:var(--rust); padding:12px; border-radius:10px; margin-bottom:20px; font-size:14px; font-weight:500;">
        ⚠ <?php echo htmlspecialchars($error_msg); ?>
    </div>
<?php endif; ?>

<form action="report_lost.php" method="POST" enctype="multipart/form-data" id="lostForm">


<div class="field" id="f-itemName">

<label>
Item Name <span class="req">*</span>
</label>


<input
type="text"
id="itemName"
name="item_name"
placeholder="e.g. Blue water bottle"
value="<?php echo htmlspecialchars($item_name ?? ''); ?>">

<p class="error-msg">
Please enter item name.
</p>

</div>




<div class="field" id="f-description">

<label>
Description <span class="req">*</span>
</label>


<textarea
id="description"
name="description"
placeholder="Brand, colour, marks, stickers, etc."><?php echo htmlspecialchars($description ?? ''); ?></textarea>
<p class="error-msg">
Please add a description.
</p>


</div>






<div class="row-2">



<div class="field" id="f-dateLost">

<label>
Date Lost <span class="req">*</span>
</label>


<input
type="date"
id="dateLost"
name="date"
value="<?php echo htmlspecialchars($date_lost ?? ''); ?>">

<p class="error-msg">
Enter a valid date.
</p>


</div>




<div class="field" id="f-location">

<label>
Location Lost <span class="req">*</span>
</label>


<input
type="text"
id="location"
name="location"
placeholder="Library, Classroom"
value="<?php echo htmlspecialchars($location ?? ''); ?>">


<p class="error-msg">
Enter lost location.
</p>


</div>


</div>





<div class="field" id="f-contact">


<label>
Contact Details <span class="req">*</span>
</label>


<input
type="text"
id="contact"
name="contact"
placeholder="Phone or email"
value="<?php echo htmlspecialchars($contact ?? ''); ?>">

<p class="error-msg">
Enter valid contact details.
</p>


</div>






<div class="field" id="f-image">


<label>
Photo of Item <span class="req">*</span>
</label>



<div 
class="upload-zone"
id="uploadZone"
tabindex="0">


<span class="upload-icon">
📷
</span>


<p>

<strong>Click to upload</strong>
or drag photo here

<br>

JPG or PNG up to 5MB

</p>

<input
type="file"
id="imageInput"
name="photo"
accept="image/png,image/jpeg">


</div>



<p class="error-msg">
Upload JPG or PNG image.
</p>




<div 
class="preview-polaroid"
id="previewBox">


<img 
id="previewImg"
alt="Preview">


<div 
class="cap"
id="previewName">
</div>


</div>


</div>





<button type="submit" name="submit">
    Submit Lost Report
</button>

<div 
class="success-note <?php echo $success_msg ? 'show' : ''; ?>"
id="lostSuccess">

Report submitted successfully. Office will review it shortly.

</div>



</form>


</div>



<footer>

CAMPUS LOST & FOUND · COLLEGE OFFICE

</footer>


</div>



<script>
const form = document.getElementById("lostForm");

const itemName = document.getElementById("itemName");
const description = document.getElementById("description");
const dateLost = document.getElementById("dateLost");
const locationInput = document.getElementById("location");
const contact = document.getElementById("contact");
const imageInput = document.getElementById("imageInput");
const uploadZone = document.getElementById("uploadZone");
const previewBox = document.getElementById("previewBox");
const previewImg = document.getElementById("previewImg");
const previewName = document.getElementById("previewName");

const success = document.getElementById("lostSuccess");

let uploadedFile = null;



function invalid(field){

field.classList.add("invalid");
field.classList.remove("valid");

}



function valid(field){

field.classList.remove("invalid");
field.classList.add("valid");

}



function checkItem(){

let field=document.getElementById("f-itemName");

if(itemName.value.trim().length < 2){

invalid(field);
return false;

}

valid(field);
return true;

}



function checkDescription(){

let field=document.getElementById("f-description");

if(description.value.trim().length < 10){

invalid(field);
return false;

}

valid(field);
return true;

}



function checkDate(){

let field=document.getElementById("f-dateLost");

if(!dateLost.value){

invalid(field);
return false;

}


let selected=new Date(dateLost.value);
let today=new Date();


if(selected>today){

invalid(field);
return false;

}


valid(field);
return true;

}




function checkLocation(){

let field=document.getElementById("f-location");


if(locationInput.value.trim().length < 2){

invalid(field);
return false;

}


valid(field);
return true;

}





function checkContact(){

let field=document.getElementById("f-contact");


let value=contact.value.trim();


let email=/^[^\s@]+@[^\s@]+\.[^\s@]+$/;

let phone=/^[0-9+\-\s]{7,15}$/;


if(!email.test(value) && !phone.test(value)){


invalid(field);

return false;

}


valid(field);

return true;

}





function checkImage(){

let field=document.getElementById("f-image");

// If server-side indicates success or we already have file uploaded, or we check dynamically
// If we have a file or we are in edit and have image from PHP, otherwise invalid
// Wait, client side validation checks if the file input is selected. If not, it fails.
// But wait, if they submit, the file input should be filled.
if(!uploadedFile && !imageInput.value){

invalid(field);

return false;

}


valid(field);

return true;

}






uploadZone.addEventListener("click",()=>{

imageInput.click();

});





imageInput.addEventListener("change",(e)=>{

handleFile(e.target.files[0]);

});





function handleFile(file){


if(!file){

return;

}


let allowed=[
"image/jpeg",
"image/png",
"image/jpg"
];


if(!allowed.includes(file.type) || file.size>5*1024*1024){


uploadedFile=null;

previewBox.classList.remove("show");

invalid(document.getElementById("f-image"));

return;

}



uploadedFile=file;


valid(document.getElementById("f-image"));



let reader=new FileReader();



reader.onload=function(e){


previewImg.src=e.target.result;

previewName.textContent=file.name;

previewBox.classList.add("show");


};



reader.readAsDataURL(file);


}
uploadZone.addEventListener("dragover",(e)=>{

e.preventDefault();

uploadZone.style.borderColor="#A87D1E";

});



uploadZone.addEventListener("dragleave",()=>{

uploadZone.style.borderColor="";

});



uploadZone.addEventListener("drop",(e)=>{

e.preventDefault();

uploadZone.style.borderColor="";

handleFile(e.dataTransfer.files[0]);

});





form.addEventListener("submit", function(e){

    let result=[
        checkItem(),
        checkDescription(),
        checkDate(),
        checkLocation(),
        checkContact(),
        checkImage()
    ];

    if(!result.every(Boolean)){
        e.preventDefault();
    } else {
        // If JS validation passes, let the form submit natively to PHP
        success.classList.add("show");
    }

});

</script>

</body>

</html>