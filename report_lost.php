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

}button{

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

<a href="index.html">Home</a>

<a href="login.html">Sign In</a>

<a href="report lost.html" class="active">Report Lost</a>

<a href="report found.html">Report Found</a>

<a href="items.html">Browse Items</a>

<a href="admin.html">Admin</a>

<a href="claim.html">Claim</a>

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




<form id="lostForm"
action="report lost.php"
method="POST"
enctype="multipart/form-data"
novalidate>



<div class="field" id="f-itemName">

<label>
Item Name <span class="req">*</span>
</label>


<input 
type="text"
id="itemName"
name="itemName"
placeholder="e.g. Blue water bottle">


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
placeholder="Brand, colour, marks, stickers, etc.">
</textarea>


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
name="dateLost">


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
placeholder="Library, Classroom">


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
placeholder="Phone or email">


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
name="imageInput"
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





<button type="submit">

Submit Lost Report

</button>



<div 
class="success-note"
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


if(!uploadedFile){

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
"image/png"
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





form.addEventListener("submit",(e)=>{


e.preventDefault();


success.classList.remove("show");



let result=[

checkItem(),

checkDescription(),

checkDate(),

checkLocation(),

checkContact(),

checkImage()

];



if(result.every(Boolean)){


success.classList.add("show");


form.reset();


previewBox.classList.remove("show");


uploadedFile=null;



document.querySelectorAll(".field").forEach((field)=>{

field.classList.remove("valid");

});


}


});



</script>

</body>

</html>