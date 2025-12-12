<?php
session_start(); // Start session to access logged-in user

// Redirect to login page if user is not logged in
if(!isset($_SESSION['username'])){
    header("Location: login.php");
    exit;
}

// Connect to MySQL database
$conn = new mysqli("127.0.0.1","root","","instagram_clone");
if($conn->connect_error) die("Connection failed: ".$conn->connect_error);

// Get logged-in user's username safely
$session_user = $conn->real_escape_string($_SESSION['username']);

// Fetch user details from database
$user_res = $conn->query("SELECT * FROM users WHERE username='$session_user'");
$user = $user_res->fetch_assoc();
$user_id = $user['id']; // store user ID for later queries

// Count number of posts by the user
$post_count_res = $conn->query("SELECT COUNT(*) as cnt FROM posts WHERE user_id=$user_id");
$post_count = $post_count_res->fetch_assoc()['cnt'];

// Count number of followers (people who follow this user)
$followers_count_res = $conn->query("SELECT COUNT(*) as cnt FROM followers WHERE follow_id=$user_id");
$followers_count = $followers_count_res->fetch_assoc()['cnt'];

// Count number of following (people this user follows)
$following_count_res = $conn->query("SELECT COUNT(*) as cnt FROM followers WHERE user_id=$user_id");
$following_count = $following_count_res->fetch_assoc()['cnt'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Profile</title>

<!-- Link external CSS -->
<link rel="stylesheet" href="welcome.css">

<style>
/* ===== BODY LAYOUT ===== */
body {
    background:#000; /* black background */
    color:#fff; /* white text */
    font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif; /* modern font */
    margin:0; /* remove default margin */
    display:flex; 
    justify-content:center; /* center profile horizontally */
}

/* ===== PROFILE CONTAINER ===== */
.profile-container {
    width:700px; /* fixed container width */
    margin:50px auto; /* center with vertical margin */
    display:flex; /* horizontal layout */
    gap:30px; /* space between picture and right info */
    align-items:flex-start; /* top align items */
}

/* ===== PROFILE PICTURE ===== */
.profile-pic {
    width:180px; /* width of profile picture */
    height:180px; /* height */
    border-radius:50%; /* circular picture */
    object-fit:cover; /* maintain aspect ratio */
    border:3px solid rgba(255,255,255,0.3); /* slightly dim white border */
    cursor:pointer; /* pointer on hover */
}

/* ===== RIGHT SIDE INFO ===== */
.profile-right {
    flex:1; /* take remaining horizontal space */
}

/* ===== PROFILE STATS ===== */
.profile-stats {
    display:flex; /* horizontal layout */
    gap:30px; /* space between stats */
    margin-top:10px; /* small top margin */
}

.profile-stats div {
    text-align:center; /* center each stat */
}

.profile-stats div span {
    font-weight:600; /* bold numbers */
    display:block; /* number on top */
    font-size:18px; /* size of numbers */
}

/* ===== DISPLAY NAME ===== */
.display-name {
    font-weight:700; /* bold name */
    font-size:22px; /* slightly bigger text */
    margin-top:15px; /* margin between stats and name */
}

/* ===== BIO ===== */
.bio {
    margin-top:0px; /* reduce gap between name and bio */
    font-size:15px; /* readable font size */
    line-height:1.2; /* tighter line spacing */
    white-space:pre-line; /* maintain line breaks */
}
</style>
</head>
<body>

<div class="profile-container">
    <!-- PROFILE PICTURE ON LEFT -->
    <img src="<?= $user['profile_pic'] ? $user['profile_pic'] : 'default.jpg'; ?>" 
         class="profile-pic" 
         alt="Profile Picture" 
         title="Change profile photo">

    <!-- RIGHT SIDE INFO -->
    <div class="profile-right">

        <!-- STATS: Posts / Followers / Following -->
        <div class="profile-stats">
            <div>
                <span><?= $post_count ?></span> <!-- number of posts -->
                Posts
            </div>
            <div>
                <span><?= $followers_count ?></span> <!-- number of followers -->
                Followers
            </div>
            <div>
                <span><?= $following_count ?></span> <!-- number of following -->
                Following
            </div>
        </div>

        <!-- DISPLAY NAME -->
        <div class="display-name">Er.Subedi Manish</div> <!-- updated name as requested -->

        <!-- BIO / CAPTION -->
        <div class="bio">
            कर्मण्येवाधिकारस्ते मा फलेषु कदाचन
            मा कर्मफलहेतुर्भूर्मा ते सङ्गोऽस्त्वकर्मणि:
            
            कर्म गर फलको आशा नगर ❤️
        </div>

    </div>
</div>

</body>
</html>
