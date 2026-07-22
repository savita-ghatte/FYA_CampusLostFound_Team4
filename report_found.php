<?php
// report_found.php - Report a Found Item
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
    $date_found = $_POST['date_found'] ?? '';
    $location = trim($_POST['location'] ?? '');
    $contact = trim($_POST['contact'] ?? '');
    
    // Server-side validation
    if (empty($item_name) || empty($description) || empty($date_found) || empty($location) || empty($contact)) {
        $error_msg = "Please fill in all required fields.";
    } elseif (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        $error_msg = "Please upload a photo of the item.";
    } else {
        $file = $_FILES['image'];
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
            $filename = uniqid('found_', true) . '.' . $file_ext;
            $upload_path = 'uploads/' . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                // Save to database using prepared statements
                $stmt = $conn->prepare("INSERT INTO found_items (item_name, description, date_found, location, contact, image, status) VALUES (?, ?, ?, ?, ?, ?, 'Pending')");
                if ($stmt) {
                    $stmt->bind_param("ssssss", $item_name, $description, $date_found, $location, $contact, $filename);
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

<title>Campus Lost & Found - Report Found Item</title>

<style>

:root{
--ink:#1F2A38;
--paper:#FAF6EE;
--gold:#C99A2E;
--gold-dark:#A87D1E;
--green:#3E6B4F;
--rust:#B23A2E;
--board:#8C6A46;
--board-dark:#7A5A3A;
}

*{
box-sizing:border-box;
}

body{

margin:0;
font-family:Arial, sans-serif;
background:
linear-gradient(160deg,var(--board),var(--board-dark));

min-height:100vh;

color:var(--ink);

}


.container{

width:90%;
max-width:760px;

margin:auto;

padding:30px 0 70px;

}


nav{

display:flex;

justify-content:center;

gap:10px;

flex-wrap:wrap;

margin-bottom:40px;

}


nav a{

text-decoration:none;

color:white;

background:rgba(0,0,0,.3);

padding:10px 15px;

border-radius:20px;

font-size:13px;

}


nav a.active{

background:var(--gold);

color:black;

}


header{

text-align:center;

color:white;

margin-bottom:35px;

}


header h1{

font-size:40px;

margin-bottom:10px;

}


header p{

font-size:16px;

}


.card{

background:var(--paper);

padding:40px;

border-radius:15px;

box-shadow:0 15px 35px rgba(0,0,0,.3);

}


.pin{

width:22px;

height:22px;

background:var(--gold);

border-radius:50%;

margin:auto;

margin-top:-55px;

margin-bottom:20px;

}


.tag{

display:inline-block;

background:#dff0e3;

color:var(--green);

padding:5px 12px;

border-radius:5px;

font-size:12px;

}


h2{

font-size:28px;

}


.subtitle{

color:#555;

}


.field{

margin-bottom:20px;

}


label{

display:block;

font-weight:bold;

margin-bottom:7px;

}


.req{

color:red;

}


input,
textarea{

width:100%;

padding:12px;

border:1px solid #c9ae79;

border-radius:8px;

font-size:15px;

}


textarea{

height:100px;

resize:vertical;

}


.row{

display:grid;

grid-template-columns:1fr 1fr;

gap:15px;

}


@media(max-width:600px){

.row{

grid-template-columns:1fr;

}

}


.error{

display:none;

color:red;

font-size:13px;

margin-top:5px;

}


.invalid .error{

display:block;

}


.invalid input,
.invalid textarea{

border:2px solid red;

}


.valid input,
.valid textarea{

border:2px solid green;

}


.upload{

border:2px dashed #c9ae79;

padding:25px;

text-align:center;

cursor:pointer;

border-radius:10px;

}


.upload:hover{

background:#fff8e8;

}


.upload input{

display:none;

}


.preview{

display:none;

margin-top:20px;

width:180px;

background:white;

padding:10px;

box-shadow:0 5px 15px #aaa;

}


.preview.show{

display:block;

}


.preview img{

width:100%;

}


button{

width:100%;

padding:14px;

background:var(--green);

color:white;

border:none;

border-radius:8px;

font-size:16px;

cursor:pointer;

}


button:hover{

background:#2f553f;

}


.success{

display:none;

margin-top:20px;

padding:15px;

background:#dff0e3;

color:green;

border-radius:8px;

}


.success.show{

display:block;

}


footer{

text-align:center;

color:white;

margin-top:50px;

}

</style>

</head>

<body>


<div class="container">

<nav>
    <a href="index.php">Home</a>
    <?php if (isset($_SESSION['username'])): ?>
        <a href="report_lost.php">Report Lost</a>
        <a href="report_found.php" class="active">Report Found</a>
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

<h1>Report a Found Item</h1>

<p>Help return the item to its real owner by providing details.</p>

</header>


<div class="card">


<div class="pin"></div>

<span class="tag">TAG · FOUND ITEM</span>


<h2>Found Item Details</h2>

<p class="subtitle">
All fields marked * are required.
</p>

<?php if ($error_msg): ?>
    <div style="border:2px solid var(--rust); color:var(--rust); padding:12px; border-radius:8px; margin-bottom:20px; font-size:14px; font-weight:bold;">
        ⚠ <?php echo htmlspecialchars($error_msg); ?>
    </div>
<?php endif; ?>

<form id="foundForm" method="POST" action="report_found.php" enctype="multipart/form-data">


<div class="field" id="itemField">

<label>
Item Name <span class="req">*</span>
</label>

<input id="itemName" name="item_name" placeholder="Example: Blue water bottle" value="<?php echo htmlspecialchars($item_name ?? ''); ?>">

<p class="error">
Enter item name
</p>

</div>
<div class="field" id="descField">

<label>
Description <span class="req">*</span>
</label>

<textarea 
id="description"
name="description"
placeholder="Brand, colour, marks, stickers, details inside item"><?php echo htmlspecialchars($description ?? ''); ?></textarea>

<p class="error">
Enter description (minimum 10 characters)
</p>

</div>



<div class="row">


<div class="field" id="dateField">

<label>
Date Found <span class="req">*</span>
</label>

<input 
type="date"
id="dateFound"
name="date_found"
value="<?php echo htmlspecialchars($date_found ?? ''); ?>">

<p class="error">
Select a valid date
</p>

</div>



<div class="field" id="locationField">

<label>
Location Found <span class="req">*</span>
</label>

<input 
type="text"
id="location"
name="location"
placeholder="Example: Library, Ground Floor"
value="<?php echo htmlspecialchars($location ?? ''); ?>">

<p class="error">
Enter found location
</p>

</div>


</div>



<div class="field" id="contactField">

<label>
Contact Details <span class="req">*</span>
</label>


<input 
type="text"
id="contact"
name="contact"
placeholder="Phone number or college email"
value="<?php echo htmlspecialchars($contact ?? ''); ?>">


<p class="error">
Enter valid phone number or email
</p>


</div>




<div class="field" id="imageField">


<label>
Photo of Item <span class="req">*</span>
</label>



<div class="upload" id="uploadBox">


<input 
type="file"
id="imageInput"
name="image"
accept="image/png,image/jpeg">


<p>
📷<br>
<strong>Click to upload</strong>
<br>
or drag image here
<br>
JPG / PNG only (Max 5MB)
</p>


</div>



<p class="error">
Upload JPG or PNG image
</p>




<div class="preview" id="preview">


<img id="previewImage">


</div>


</div>




<button type="submit">
Submit Found Report
</button>



<div class="success <?php echo $success_msg ? 'show' : ''; ?>" id="success">

Found item report submitted successfully.

</div>


</form>


</div>



<footer>

CAMPUS LOST & FOUND · COLLEGE OFFICE

</footer>


</div>



<script>


const form=document.getElementById("foundForm");

const item=document.getElementById("itemName");

const desc=document.getElementById("description");

const date=document.getElementById("dateFound");

const locationInput=document.getElementById("location");

const contact=document.getElementById("contact");

const imageInput=document.getElementById("imageInput");

const uploadBox=document.getElementById("uploadBox");

const preview=document.getElementById("preview");

const previewImage=document.getElementById("previewImage");


let uploadedFile=null;



function invalid(field){

field.classList.add("invalid");

field.classList.remove("valid");

}



function valid(field){

field.classList.remove("invalid");

field.classList.add("valid");

}



function checkItem(){


let field=document.getElementById("itemField");


if(item.value.trim().length>=2){

valid(field);
return true;

}

invalid(field);
return false;

}



function checkDescription(){


let field=document.getElementById("descField");


if(desc.value.trim().length>=10){

valid(field);
return true;

}


invalid(field);
return false;


}



function checkDate(){


let field=document.getElementById("dateField");


if(date.value){

let selected=new Date(date.value);

let today=new Date();


if(selected<=today){

valid(field);
return true;

}

}


invalid(field);

return false;


}



function checkLocation(){


let field=document.getElementById("locationField");


if(locationInput.value.trim().length>=2){

valid(field);
return true;

}


invalid(field);

return false;


}




function checkContact(){


let field=document.getElementById("contactField");

let value=contact.value.trim();

let phone=/^[0-9+\-\s]{7,15}$/;

let email=/^[^\s@]+@[^\s@]+\.[^\s@]+$/;


if(phone.test(value)||email.test(value)){

valid(field);

return true;

}


invalid(field);

return false;


}




function checkImage(){


let field=document.getElementById("imageField");


if(uploadedFile || imageInput.value){

valid(field);

return true;

}


invalid(field);

return false;


}

// Image upload

uploadBox.addEventListener("click",function(){

imageInput.click();

});


imageInput.addEventListener("change",function(e){


let file=e.target.files[0];


if(!file){

return;

}



let validTypes=[
"image/jpeg",
"image/png",
"image/jpg"
];


if(!validTypes.includes(file.type) || file.size > 5*1024*1024){


uploadedFile=null;

invalid(document.getElementById("imageField"));

alert("Only JPG/PNG images under 5MB allowed");

return;


}



uploadedFile=file;

valid(document.getElementById("imageField"));



let reader=new FileReader();


reader.onload=function(event){

previewImage.src=event.target.result;

preview.classList.add("show");

};


reader.readAsDataURL(file);


});




// Drag and drop


uploadBox.addEventListener("dragover",function(e){

e.preventDefault();

uploadBox.style.background="#fff5dc";

});



uploadBox.addEventListener("dragleave",function(){

uploadBox.style.background="";

});



uploadBox.addEventListener("drop",function(e){

e.preventDefault();

uploadBox.style.background="";


let file=e.dataTransfer.files[0];


if(file){


imageInput.files=e.dataTransfer.files;


imageInput.dispatchEvent(new Event("change"));


}


});



form.addEventListener("submit",function(e){

e.preventDefault();

let result=[

checkItem(),

checkDescription(),

checkDate(),

checkLocation(),

checkContact(),

checkImage()

];

let successMsg=document.getElementById("success");

if(result.every(Boolean)){

successMsg.classList.add("show");

setTimeout(() => {
    form.submit();
}, 400);

}

else{

let first=document.querySelector(".invalid");

if(first){

first.scrollIntoView({

behavior:"smooth",

block:"center"

});

}

}

});

</script>


</body>
</html>