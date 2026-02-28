<?php
session_start();
ini_set('display_errors',1);
error_reporting(E_ALL);

if(!isset($_SESSION['username'])){
    header("Location: login.php");
    exit;
}

// Database
$conn = new mysqli("127.0.0.1","root","","instagram_clone");
if($conn->connect_error) die("Connection failed: ".$conn->connect_error);

// Current user
$session_user = $conn->real_escape_string($_SESSION['username']);
$session_res = $conn->query("SELECT * FROM users WHERE username='$session_user'");
$session_row = $session_res->fetch_assoc();
$session_id = $session_row['id'];

// Stats
$post_count = $conn->query("SELECT COUNT(*) as cnt FROM posts WHERE user_id=$session_id")->fetch_assoc()['cnt'];
$followers_count = $conn->query("SELECT COUNT(*) as cnt FROM followers WHERE follow_id=$session_id")->fetch_assoc()['cnt'];
$following_count = $conn->query("SELECT COUNT(*) as cnt FROM followers WHERE user_id=$session_id")->fetch_assoc()['cnt'];

// Like / Comment
if(isset($_POST['like_post'])){
    $post_id = intval($_POST['post_id']);
    $like_check = $conn->query("SELECT * FROM likes WHERE post_id=$post_id AND user_id=$session_id");
    if($like_check->num_rows>0){
        $conn->query("DELETE FROM likes WHERE post_id=$post_id AND user_id=$session_id");
    }else{
        $conn->query("INSERT INTO likes (post_id,user_id) VALUES($post_id,$session_id)");
    }
    $likes = $conn->query("SELECT COUNT(*) as cnt FROM likes WHERE post_id=$post_id")->fetch_assoc()['cnt'];
    echo $likes; exit;
}

if(isset($_POST['add_comment'])){
    $post_id = intval($_POST['post_id']);
    $comment_text = trim($_POST['comment_text']);
    if($comment_text != ''){
        $stmt = $conn->prepare("INSERT INTO comments(post_id,user_id,comment_text) VALUES(?,?,?)");
        $stmt->bind_param("iis",$post_id,$session_id,$comment_text);
        $stmt->execute();
        $stmt->close();
        $username = $session_row['username'];
        echo json_encode(['status'=>'success','comment'=>$comment_text,'username'=>$username]);
        exit;
    } else {
        echo json_encode(['status'=>'error','msg'=>'Comment cannot be empty.']);
        exit;
    }
}

// Posts
$all_posts_res = $conn->query("SELECT posts.id,posts.content,posts.image,posts.video,users.username,users.profile_pic FROM posts LEFT JOIN users ON posts.user_id=users.id ORDER BY posts.id ASC");
$my_posts_res = $conn->query("SELECT posts.id,posts.content,posts.image,users.username,users.profile_pic FROM posts LEFT JOIN users ON posts.user_id=users.id WHERE posts.user_id=$session_id ORDER BY posts.id ASC");

// Stories
$story_res = $conn->query("SELECT stories.*, users.username FROM stories JOIN users ON stories.user_id=users.id ORDER BY stories.id ASC");

// Reels
$reels_videos=['reel1.mp4','reel2.mp4','reel3.mp4','reel4.mp4','reel5.mp4'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Instagram Clone</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>
<link href="https://fonts.googleapis.com/css2?family=Pacifico&display=swap" rel="stylesheet">
<link rel="stylesheet" href="welcome.css">
<style>
body,.main-content{height:100vh;overflow-y:auto;background:#000;color:#fff;margin:0;}
#postsContainer,#profilePostsContainer{display:flex;flex-direction:column;gap:20px;}
#reelsContainer{display:none;height:100vh;overflow-y:scroll;scroll-snap-type:y mandatory;flex-direction:column;}
.reel{height:80vh;scroll-snap-align:start;display:flex;justify-content:center;align-items:center;position:relative;margin:0 auto;}
.reel video{max-height:100%;max-width:90%;border-radius:20px;}
.post{background:#111;padding:10px;border-radius:10px;position:relative;}
.post-header{display:flex;align-items:center;gap:10px;}
.post-header img{width:35px;height:35px;border-radius:50%;object-fit:cover;}
.post-actions{display:flex;justify-content:space-between;align-items:center;margin-top:5px;}
.post-actions-left button{background:none;border:none;color:#fff;cursor:pointer;margin-right:10px;font-size:24px;}
.post-image{width:100%;margin-top:5px;border-radius:10px;cursor:pointer;}
.likes-count{font-weight:700;margin-top:5px;}
.comment-box{display:none;flex-direction:row;margin-top:5px;gap:5px;}
.comment-box input{flex:1;padding:5px;border-radius:5px;border:none;}
.comment-box button{padding:5px 10px;border:none;border-radius:5px;cursor:pointer;background:#555;color:#fff;}
.comment-list div{font-size:14px;margin-top:3px;}

/* ==========================
   Instagram-Like Stories CSS
========================== */

.stories-container-wrapper {
    position: sticky;
    top: 1px;                 /* match Instagram's top spacing */
    background: #000;
    z-index: 999;
    padding: 5px 0;
    transition: transform 0.3s ease;
}

.stories-hidden {
    transform: translateY(-100%);
}

.stories-container {
    display: flex;
    overflow-x: auto;
    padding: 10px 12px;
    gap: 12px;
}

.stories-container::-webkit-scrollbar {
    display: none; /* hide scrollbar */
}

.story {
    display: flex;
    flex-direction: column;
    align-items: center;
    cursor: pointer;
}

.story-circle {
    width: 90px;               /* real Instagram story size */
    height: 90px;
    padding: 0px;              /* thinner gradient border like IG */
    border-radius: 50%;
    background: conic-gradient(
        #feda75 0deg,
        #fa7e1e 60deg,
        #d62976 120deg,
        #962fbf 180deg,
        #4f5bd5 240deg,
        #feda75 360deg
    );
    display: flex;
    align-items: center;
    justify-content: center;
    transition: transform 0.3s ease;
}

.story-circle img {
    width: 82px;               /* inner profile image */
    height: 82px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #000;    /* subtle inner border like IG */
}

.story-circle:hover {
    transform: scale(1.1);     /* slight zoom on hover */
}

.story-username {
    font-size: 11px;
    margin-top: 4px;
    text-align: center;
    color: #fff;
    max-width: 70px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* Top Bar */
.top-bar { display: flex; justify-content: space-between; align-items: center; padding: 35px 90px; gap: 0px;  background-color: #000; color: #fff; border-bottom: 1px solid #222; position: sticky; top: 0; z-index: 1000;gap: 130px; border-bottom: 1px solid rgba(255, 255, 255, 0.2); /* default faint line */
    transition: border-bottom 0.3s ease;}
/* --- PASTE STORIES CSS HERE --- */
#storiesWrapper {
    display: flex;
    overflow-x: auto;
    gap: 18px;
    padding: 1px 1px;
    background-color: #000;
}


.top-bar.scrolled {
    border-bottom: 1px solid rgba(255, 255, 255, 0.05); /* even fainter when scrolled */
}
.top-left, .top-center, .top-right { display: flex; align-items: center; }
.top-center h1 { font-family: 'Arial', sans-serif; font-size: 24px; font-weight: 600; margin: 0;}
.top-left button, .top-right a { background: none; border: none; color: #fff; font-size: 22px; cursor: pointer; padding: 1px; }
.top-left button:hover, .top-right a:hover { opacity: 0.9; }

/* Likes pop-up */
#likesTopLeft { display: none; position: fixed; top: 60px; left: 15px; background: #262626; padding: 8px 12px; border-radius: 8px; color: #fff; font-weight: 600; font-size: 14px; z-index: 1001; }
.heart-animation { position: absolute; top: 50%; left: 50%; transform: translate(-50%,-50%) scale(0); font-size: 80px; color: white; opacity: 0.9; pointer-events:none; transition: transform 0.3s ease; }
.heart-animation.show { transform: translate(-50%,-50%) scale(1); }

/* Reels buttons */
.reel-buttons{position:absolute; right:35px; bottom:70px; display:flex; flex-direction:column; gap:20px;   z-index: 10;
}
.reel-buttons button{background:none; border:none; color:#fff; font-size:28px; cursor:pointer;}

/* Responsive bottom-nav → side bar on large screens */
/* ===================== */
/* MOBILE BOTTOM NAV */
/* ===================== */

.bottom-nav {
    position: fixed;
    bottom: 0;
    width: 100%;
    height: 55px;                /* Instagram-like height */
    background: #000;
    display: flex;
    justify-content: ;  /* first icon flush left, last flush right */
    align-items: center;
    padding: 0;                     /* remove all padding */
    border-top: 1px solid #222;
    z-index: 1000;
}

.bottom-nav button {
    width: 15%;                      /* 4 buttons, take full width */
    display: flex;
    justify-content: center;         /* center icon inside its quarter */
    align-items: center;
    background: none;
    border: none;
    color: #fff;
    font-size: 22px;
    cursor: pointer;
}

.bottom-nav button.active {
    color: #fff;
}
/* ===================== */
/* DESKTOP MODE */
/* ===================== */
@media screen and (min-width: 992px){

    .bottom-nav{
        display:flex;
        flex-direction:column;   /* vertical */
        justify-content:flex-start;
        align-items:flex-start;
        gap:70px;                /* 🔥 THIS CONTROLS DESKTOP GAP */
        position:fixed;
        left:0;
        top:270px;
        height:100vh;
        width:90px;
        background:#000;
        padding:40px 20px;
        border-right: none !important;
    }

    .bottom-nav button{
        font-size:18px;
        text-align:left;
        width:100%;
    }

    .main-content{
        margin-left:30px;   /* push content right */
    }
}
.app-logo {
    font-family: 'Pacifico', cursive;
    font-size: 28px;
    color: #fff;
    margin: 0;
    letter-spacing: 1px;
}
/* Remove all borders */
*{
    border:none !important;
}

/* Hide HR */
hr{
    display:none;
}

/* Keep only top header line */
.header{
    border-bottom:1px solid #222 !important;
}

</style>
</head>
<body>
<div class="main-content">

<!-- Top Bar -->
<div class="top-bar">
    <div class="top-left">
        <button id="navLikes" title="Likes"><i class="fa-regular fa-heart"></i></button>
    </div>
    <div class="top-center">
        <h1 style="font-family: 'Pacifico', cursive; font-size:28px; margin:0;">SubediVibes</h1>
    </div>
    <div class="top-right">
        <a href="logout.php" title="Logout"><i class="fa-solid fa-right-from-bracket"></i></a>
    </div>
</div>
<div id="likesTopLeft">Likes Clicked</div>

<!-- Stories -->
<div class="stories-container-wrapper" id="storiesWrapper">
<div class="stories-container">
<?php
if($story_res && $story_res->num_rows>0){
    while($story=$story_res->fetch_assoc()){
        $story_img=$story['media']?$story['media']:'subedi.jpg';
        echo '<div class="story"><div class="story-circle"><img src="'.$story_img.'"></div><div class="story-username">'.$story['username'].'</div></div>';
    }
}
?>
</div></div>

<!-- Profile -->
<div id="profileContainer" style="display:none;">
    <div class="profile-header" style="display:flex;align-items:;padding:22px;gap:20px;border-bottom:1px solid #333;">

        <img src="subedi.jpg" alt="Profile" style="width:150px;height:150px;border-radius:50%;object-fit:cover;border:2px solid #fff;">
        <div class="user-info" style="display:flex;flex-direction:column;gap:10px;">
            <div style="font-weight:700;font-size:22px;">Er.Manish Subedi</div>
            <div style="display:flex;gap:25px;font-size:16px;">
                <div><span style="font-weight:700;font-size:18px;"><?= $post_count ?></span> posts</div>
                <div><span style="font-weight:700;font-size:18px;"><?= $followers_count ?></span> followers</div>
                <div><span style="font-weight:700;font-size:18px;"><?= $following_count ?></span> following</div>
            </div>
            <div class="bio" style="font-size:15px;line-height:1.2;">
                कर्मण्येवाधिकारस्ते मा फलेषु कदाचन<br>
                मा कर्मफलहेतुर्भूर्मा ते सङ्गोऽस्त्वकर्मणि:<br>
                कर्म गर फलको आशा नगर ❤️
            </div>
        </div>
    </div>
    <div id="profilePostsContainer" style="display:grid;grid-template-columns:repeat(3,1fr);gap:15px;background:#000; padding-bottom: 100% padding:5px">
    <?php
    if($my_posts_res && $my_posts_res->num_rows>0){
        while($post=$my_posts_res->fetch_assoc()){
            $img=$post['image']?$post['image']:'post1.jpg';
            $likes=$conn->query("SELECT COUNT(*) as cnt FROM likes WHERE post_id=".$post['id'])->fetch_assoc()['cnt'];
            echo '<div class="post" data-id="'.$post['id'].'">';
            echo '<div class="post-header"><img src="'.$post['profile_pic'].'" alt="Profile"><span>'.$post['username'].'</span></div>';
            echo '<img src="'.$img.'" class="post-image">';
            echo '<i class="fa-solid fa-heart heart-animation"></i>';
            echo '<div class="post-actions">';
            echo '<div class="post-actions-left">';
            echo '<button class="like-btn"><i class="fa-regular fa-heart"></i></button>';
            echo '<button class="comment-toggle-btn"><i class="fa-regular fa-comment"></i></button>';
            echo '<button><i class="fa-solid fa-paper-plane"></i></button>';
            echo '</div>';
            echo '<button><i class="fa-regular fa-bookmark"></i></button>';
            echo '</div>';
            echo '<div class="likes-count"><span>'.$likes.'</span> likes</div>';
            echo '<div class="comment-box"><input type="text" placeholder="Add a comment..."><button>➡</button></div>';
            echo '<div class="comment-list"></div>';
            echo '</div>';
        }
    }
    ?>
    </div>
</div>

<!-- Reels -->
<div id="reelsContainer">
<?php
foreach($reels_videos as $video){
    echo '<div class="reel" data-id="r'.$video.'">';
    echo '<video src="'.$video.'" muted playsinline class="reel-video"></video>';
    echo '<i class="fa-solid fa-heart heart-animation"></i>';
    echo '<div class="reel-buttons">';
    echo '<button class="reel-like-btn"><i class="fa-regular fa-heart"></i></button>';
    echo '<button class="reel-comment-btn"><i class="fa-regular fa-comment"></i></button>';
    echo '<button class="reel-share-btn"><i class="fa-solid fa-paper-plane"></i></button>';
    echo '</div>';
    echo '<div class="comment-box"><input type="text" placeholder="Add a comment..."><button>➡</button></div>';
    echo '<div class="comment-list"></div>';
    echo '</div>';
}
?>
</div>

<!-- Home posts -->
<div class="container" id="postsContainer">
<?php
if($all_posts_res && $all_posts_res->num_rows>0){
    while($row=$all_posts_res->fetch_assoc()){
        $profile_pic = $row['profile_pic'] ? $row['profile_pic'] : 'subedi.jpg';
        $post_image  = $row['image'] ? $row['image'] : 'post1.jpg';
        $likes       = $conn->query("SELECT COUNT(*) as cnt FROM likes WHERE post_id=".$row['id'])->fetch_assoc()['cnt'];

        echo '<div class="post" data-id="'.$row['id'].'">';

        // Post header with profile pic and username
        echo '<div class="post-header"><img src="'.$profile_pic.'" alt="Profile"><span>'.$row['username'].'</span></div>';

        // Caption (if any)
        if(!empty($row['content'])){
            echo '<div class="post-caption" style="color:#fff;font-size:14px;margin:5px 0;">';
            echo $row['content'];
            echo '</div>';
        }

        // Post image
        echo '<img src="'.$post_image.'" class="post-image">';

        // Heart animation and actions
        echo '<i class="fa-solid fa-heart heart-animation"></i>';
        echo '<div class="post-actions">';
        echo '<div class="post-actions-left">';
        echo '<button class="like-btn"><i class="fa-regular fa-heart"></i></button>';
        echo '<button class="comment-toggle-btn"><i class="fa-regular fa-comment"></i></button>';
        echo '<button><i class="fa-solid fa-paper-plane"></i></button>';
        echo '</div>';
        echo '<button><i class="fa-regular fa-bookmark"></i></button>';
        echo '</div>';

        // Likes count and comments
        echo '<div class="likes-count"><span>'.$likes.'</span> likes</div>';
        echo '<div class="comment-box"><input type="text" placeholder="Add a comment..."><button>➡</button></div>';
        echo '<div class="comment-list"></div>';

        echo '</div>'; // Close post
    }
}
$conn->close();
?>
</div>

<!-- Bottom nav -->
<div class="bottom-nav">
    <button id="navHome" class="active" title="Home"><i class="fa-solid fa-house"></i></button>
    <button id="navSearch" title="Search"><i class="fa-solid fa-magnifying-glass"></i></button>
    <button id="navReels" title="Reels"><i class="fa-solid fa-clapperboard"></i></button>
    <button id="navProfile" title="Profile"><i class="fa-solid fa-user"></i></button>
</div>

<script>
const postsContainer=document.getElementById('postsContainer');
const reelsContainer=document.getElementById('reelsContainer');
const profileContainer=document.getElementById('profileContainer');
const storiesWrapper=document.getElementById('storiesWrapper');

// Bottom nav
const bottomNavButtons=document.querySelectorAll('.bottom-nav button');
bottomNavButtons.forEach(btn=>{btn.addEventListener('click',()=>{
    bottomNavButtons.forEach(b=>b.classList.remove('active'));
    btn.classList.add('active');
});});
document.getElementById('navHome').addEventListener('click', ()=>{
    postsContainer.style.display='flex';
    reelsContainer.style.display='none';
    profileContainer.style.display='none';
    storiesWrapper.style.display='flex';
});
document.getElementById('navReels').addEventListener('click', ()=>{
    postsContainer.style.display='none';
    reelsContainer.style.display='flex';
    profileContainer.style.display='none';
    storiesWrapper.style.display='none';
});
document.getElementById('navProfile').addEventListener('click', ()=>{
    postsContainer.style.display='none';
    reelsContainer.style.display='none';
    profileContainer.style.display='block';
    storiesWrapper.style.display='none';
});
document.getElementById('navSearch').addEventListener('click', ()=>{ alert("Search Clicked"); });

// Likes top-left
const likesTopLeft=document.getElementById('likesTopLeft');
document.getElementById('navLikes').addEventListener('click',()=>{
    likesTopLeft.style.display='block';
    setTimeout(()=>{likesTopLeft.style.display='none';},2000);
});

// Story hide on scroll
let lastScrollTop=0;
window.addEventListener("scroll", function(){
    let scrollTop=window.pageYOffset||document.documentElement.scrollTop;
    if(scrollTop>lastScrollTop) storiesWrapper.classList.add("stories-hidden");
    else storiesWrapper.classList.remove("stories-hidden");
    lastScrollTop = scrollTop<=0?0:scrollTop;
});

// ===== LIKE FUNCTION =====
function likePost(postElem,isButton=false){
    const postId=postElem.dataset.id;
    const heart=postElem.querySelector(".heart-animation");
    const likeIcon=postElem.querySelector(".like-btn i, .reel-like-btn i");

    if(!isButton) heart.classList.add("show");
    setTimeout(()=>heart.classList.remove("show"),300);

    fetch("",{
        method:"POST",
        headers:{"Content-Type":"application/x-www-form-urlencoded"},
        body:"like_post=1&post_id="+postId
    }).then(res=>res.text()).then(data=>{
        postElem.querySelector(".likes-count span").innerText=data;

        if(parseInt(data)>0){
            likeIcon.classList.remove("fa-regular");
            likeIcon.classList.add("fa-solid");
            if(isButton) likeIcon.style.color="red";
        } else {
            likeIcon.classList.remove("fa-solid");
            likeIcon.classList.add("fa-regular");
            likeIcon.style.color="#fff";
        }
    });
}

// Posts like button
document.querySelectorAll(".like-btn").forEach(btn=>{
    btn.addEventListener("click",e=>{
        const postElem=e.target.closest(".post");
        likePost(postElem,true);
    });
});

// Posts double-tap
document.querySelectorAll(".post-image").forEach(img=>{
    let lastTap=0;
    img.addEventListener("click", function(){
        const currentTime=new Date().getTime();
        const tapLength=currentTime-lastTap;
        if(tapLength<300 && tapLength>0){
            const postElem=img.closest(".post");
            likePost(postElem,false);
        }
        lastTap=currentTime;
    });
});

// Comment toggle
document.querySelectorAll(".comment-toggle-btn").forEach(btn=>{
    btn.addEventListener("click",e=>{
        const postElem=e.target.closest(".post, .reel");
        const commentBox=postElem.querySelector(".comment-box");
        commentBox.style.display=(commentBox.style.display==="flex")?"none":"flex";
        commentBox.querySelector("input").focus();
    });
});

// Comment submit
document.querySelectorAll(".comment-box button").forEach(btn=>{
    btn.addEventListener("click", e=>{
        const postElem=e.target.closest(".post, .reel");
        const postId=postElem.dataset.id;
        const input=postElem.querySelector(".comment-box input");
        const text=input.value.trim();
        if(text==="") return;
        fetch("",{
            method:"POST",
            headers:{"Content-Type":"application/x-www-form-urlencoded"},
            body:"add_comment=1&post_id="+postId+"&comment_text="+encodeURIComponent(text)
        }).then(res=>res.json()).then(data=>{
            if(data.status==="success"){
                const div=document.createElement("div");
                div.innerText=data.username+": "+data.comment;
                postElem.querySelector(".comment-list").appendChild(div);
                input.value="";
            }
        });
    });
});
const reelsElems = document.querySelectorAll(".reel");

reelsElems.forEach(reel => {
    const video = reel.querySelector("video");
    const likeBtn = reel.querySelector(".reel-like-btn");
    const commentBtn = reel.querySelector(".reel-comment-btn");
    let lastTap = 0;

    // Play video always
    video.play();
    video.loop = true;

    // Double-tap to toggle like
    video.addEventListener("click", function() {
        const currentTime = new Date().getTime();
        const tapLength = currentTime - lastTap;
        if (tapLength < 300 && tapLength > 0) {
            toggleLike(reel);
        }
        lastTap = currentTime;
    });

    // Like button click toggle
    likeBtn.addEventListener("click", () => {
        toggleLike(reel);
    });

    // Comment button click
    commentBtn.addEventListener("click", () => {
        const commentBox = reel.querySelector(".comment-box");
        commentBox.style.display = commentBox.style.display === "flex" ? "none" : "flex";
        commentBox.querySelector("input").focus();
    });
});

// Toggle like function
function toggleLike(reelElem) {
    const heart = reelElem.querySelector(".heart-animation");
    const likeIcon = reelElem.querySelector(".reel-like-btn i");
    const postId = reelElem.dataset.id;

    // Show big heart animation only if liking (not unliking)
    const isLiked = likeIcon.classList.contains("fa-solid");
    if (!isLiked) {
        heart.classList.add("show");
        setTimeout(() => heart.classList.remove("show"), 500);
    }

    // Send request to server to toggle like
    fetch("", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "like_post=1&post_id=" + postId
    })
    .then(res => res.text())
    .then(data => {
        const likesCountElem = reelElem.querySelector(".likes-count span");
        if (likesCountElem) likesCountElem.innerText = data;

        // Toggle like button color
        if (likeIcon.classList.contains("fa-solid")) {
            likeIcon.classList.remove("fa-solid");
            likeIcon.classList.add("fa-regular");
            likeIcon.style.color = "#fff"; // now unliked
        } else {
            likeIcon.classList.remove("fa-regular");
            likeIcon.classList.add("fa-solid");
            likeIcon.style.color = "red"; // now liked
        }
    });
}
// ===== SCROLL EVENT =====
window.addEventListener("scroll", function() {
    const topBar = document.querySelector(".top-bar");
    if (window.scrollY > 50) {
        topBar.classList.add("scrolled");
    } else {
        topBar.classList.remove("scrolled");
    }
});

</script>
</body>
</html>