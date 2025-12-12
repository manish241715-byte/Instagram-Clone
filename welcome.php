<?php
session_start(); // Start the session for logged in user
if(!isset($_SESSION['username'])){
    header("Location: login.php"); // Redirect to login if not logged in
    exit;
}

// Connect to database
$conn = new mysqli("127.0.0.1", "root", "", "instagram_clone");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$session_user = $conn->real_escape_string($_SESSION['username']); // Get current username
$session_res = $conn->query("SELECT id FROM users WHERE username='$session_user'"); 
$session_row = $session_res->fetch_assoc();
$session_id = $session_row['id']; // Get current user ID

// Handle like/unlike for posts
if(isset($_POST['like_post'])){
    $post_id = intval($_POST['post_id']);
    $like_check = $conn->query("SELECT * FROM likes WHERE post_id=$post_id AND user_id=$session_id");
    if($like_check->num_rows > 0){
        $conn->query("DELETE FROM likes WHERE post_id=$post_id AND user_id=$session_id"); // Unlike
    } else {
        $conn->query("INSERT INTO likes (post_id, user_id) VALUES ($post_id, $session_id)"); // Like
    }
    echo "success";
    exit;
}

// Handle comment submission
if(isset($_POST['add_comment'])){
    $post_id = intval($_POST['post_id']);
    $comment_text = trim($_POST['comment_text']);
    if($comment_text != '') {
        $check_post = $conn->query("SELECT id FROM posts WHERE id=$post_id");
        if($check_post->num_rows > 0){
            $stmt = $conn->prepare("INSERT INTO comments (post_id, user_id, comment) VALUES (?, ?, ?)");
            $stmt->bind_param("iis", $post_id, $session_id, $comment_text);
            $stmt->execute();
            if($stmt->affected_rows > 0){
                echo "success"; // Comment added successfully
            } else {
                echo "Failed to add comment.";
            }
            $stmt->close();
        } else {
            echo "Post does not exist.";
        }
    } else {
        echo "Comment cannot be empty.";
    }
    exit;
}

// Fetch posts from database
$sql = "SELECT posts.id, posts.content, posts.image, users.username, users.profile_pic
        FROM posts
        LEFT JOIN users ON posts.user_id = users.id
        ORDER BY posts.id DESC";
$result = $conn->query($sql);

// Fetch stories from database
$story_res = $conn->query("SELECT stories.*, users.username FROM stories JOIN users ON stories.user_id = users.id ORDER BY stories.id ASC");

// Define some sample reels videos
$reels_videos = ['reel1.mp4','reel2.mp4','reel3.mp4','reel4.mp4','reel5.mp4'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Instagram Clone</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>
<link rel="stylesheet" href="welcome.css"> <!-- external CSS linked -->
</head>
<body>

<!-- ===== LEFT NAVBAR ===== -->
<div class="sidebar">
    <button class="active" id="homeBtn" title="Home"><i class="fa-solid fa-house"></i></button>
    <button id="searchBtn" title="Search"><i class="fa-solid fa-magnifying-glass"></i></button>
    <button id="reelsBtnSidebar" title="Reels"><i class="fa-solid fa-clapperboard"></i></button>
    <button id="messagesBtn" title="Messages"><i class="fa-solid fa-paper-plane"></i></button>
    <button id="profileBtn" title="Profile"><i class="fa-solid fa-user"></i></button>
</div>

<div class="main-content">
<div class="header">
    <h1>Instagram</h1>
    <a href="logout.php" class="logout-btn">Logout</a>
</div>

<!-- ===== STORIES ===== -->
<div class="stories-container-wrapper" id="storiesWrapper">
<div class="stories-container">
<?php
if($story_res->num_rows > 0){
    while($story = $story_res->fetch_assoc()){
        $story_img = $story['media'] ? $story['media'] : 'default.jpg';
        echo '<div class="story"><div class="story-circle"><img src="'.$story_img.'"></div><div class="story-username">'.$story['username'].'</div></div>';
    }
}
?>
</div>
</div>

<!-- ===== REELS ===== -->
<div id="reelsContainer">
<?php
foreach($reels_videos as $video){
    echo '<div class="reel">';
    echo '<video src="'.$video.'" muted loop></video>'; // Reel video
    echo '<div class="reel-buttons">'; // Buttons near video
    echo '<button class="reel-like-btn">♡</button>'; // Like button
    echo '<button class="reel-comment-btn"><i class="fa-regular fa-comment"></i></button>'; // Comment button
    echo '<button class="reel-share-btn"><i class="fa-solid fa-paper-plane"></i></button>'; // Share button
    echo '</div></div>';
}
?>
</div>

<!-- ===== POSTS ===== -->
<div class="container" id="postsContainer">
<?php
if($result && $result->num_rows > 0){
    while($row = $result->fetch_assoc()){
        $profile_pic = $row['profile_pic'] ? $row['profile_pic'] : 'default.jpg';
        $post_image = $row['image'] ? $row['image'] : '';
        $likes_res = $conn->query("SELECT COUNT(*) as cnt FROM likes WHERE post_id=".$row['id']);
        $likes = $likes_res->fetch_assoc()['cnt'];
?>
<div class="post" data-postid="<?= $row['id'] ?>">
    <div class="post-header">
        <img src="<?= $profile_pic ?>" alt="Profile">
        <span><?= $row['username'] ?></span>
    </div>
    <div class="post-content">
        <p><?= htmlspecialchars($row['content']) ?></p>
        <?php if($post_image) echo '<img src="'.$post_image.'" class="post-image">'; ?>
        <i class="fa-solid fa-heart heart-overlay"></i>
    </div>
    <div class="post-actions">
        <div class="post-actions-left">
            <button class="like-btn"><i class="fa-regular fa-heart"></i></button>
            <button><i class="fa-regular fa-comment comment-toggle"></i></button>
            <button><i class="fa-solid fa-paper-plane"></i></button>
        </div>
        <button><i class="fa-regular fa-bookmark"></i></button>
    </div>
    <div class="likes-count"><span class="like-count"><?= $likes ?></span> likes</div>
    
    <div class="comment-box" style="display:none;">
        <input type="text" class="comment-input" placeholder="Add a comment...">
        <button class="submit-comment">&#10148;</button>
    </div>
    <div class="comment-list">
        <?php
            $c_res = $conn->query("SELECT comments.comment, users.username FROM comments JOIN users ON comments.user_id = users.id WHERE post_id=".$row['id']." ORDER BY comments.id ASC");
            while($c = $c_res->fetch_assoc()){
                echo "<div><b>{$c['username']}</b>: {$c['comment']}</div>";
            }
        ?>
    </div>
</div>
<?php
    }
}
$conn->close();
?>
</div>

<script>
// ===== POSTS LIKE/COMMENT =====
document.querySelectorAll('.like-btn').forEach(btn=>{
    btn.addEventListener('click', ()=>{
        const post = btn.closest('.post');
        const icon = btn.querySelector('i');
        const countSpan = post.querySelector('.like-count');
        let count = parseInt(countSpan.textContent);
        const postId = post.getAttribute('data-postid');
        fetch(window.location.href, {
            method:'POST',
            headers:{'Content-Type':'application/x-www-form-urlencoded'},
            body:'like_post=1&post_id='+postId
        }).then(()=>{
            if(icon.classList.contains('fa-regular')){
                icon.classList.remove('fa-regular');
                icon.classList.add('fa-solid');
                icon.style.color = 'red';
                countSpan.textContent = count + 1;
            } else {
                icon.classList.remove('fa-solid');
                icon.classList.add('fa-regular');
                icon.style.color = '#fff';
                countSpan.textContent = count - 1;
            }
        });
    });
});

// Double click image to like
document.querySelectorAll('.post-content img').forEach(img=>{
    img.addEventListener('dblclick', ()=>{
        const post = img.closest('.post');
        const heart = post.querySelector('.heart-overlay');
        const icon = post.querySelector('.like-btn i');
        heart.style.display='block';
        setTimeout(()=>heart.style.display='none',600);
        if(icon.classList.contains('fa-regular')){
            icon.classList.remove('fa-regular');
            icon.classList.add('fa-solid');
            icon.style.color='red';
            const postId = post.getAttribute('data-postid');
            fetch(window.location.href, {
                method:'POST',
                headers:{'Content-Type':'application/x-www-form-urlencoded'},
                body:'like_post=1&post_id='+postId
            });
            let countSpan = post.querySelector('.like-count');
            countSpan.textContent = parseInt(countSpan.textContent) + 1;
        }
    });
});

// Toggle comment box
document.querySelectorAll('.comment-toggle').forEach(btn=>{
    btn.addEventListener('click', ()=>{
        const post = btn.closest('.post');
        const box = post.querySelector('.comment-box');
        box.style.display = box.style.display==='flex'?'none':'flex';
        box.querySelector('input').focus();
    });
});

// Submit comment
document.querySelectorAll('.submit-comment').forEach(btn=>{
    btn.addEventListener('click', ()=>{
        const post = btn.closest('.post');
        const postId = post.getAttribute('data-postid');
        const input = post.querySelector('.comment-input');
        const list = post.querySelector('.comment-list');
        const text = input.value.trim();
        if(text==='') return;
        fetch(window.location.href,{
            method:'POST',
            headers:{'Content-Type':'application/x-www-form-urlencoded'},
            body:'add_comment=1&post_id='+postId+'&comment_text='+encodeURIComponent(text)
        }).then(res=>res.text()).then(res=>{
            if(res==='success'){
                const div = document.createElement('div');
                div.innerHTML = `<b><?= $_SESSION['username'] ?></b>: ${text}`;
                list.appendChild(div);
                input.value='';
            } else { alert("Comment not added, try again."); }
        });
    });
});

// Submit comment with Enter key
document.querySelectorAll('.comment-input').forEach(input=>{
    input.addEventListener('keypress', e=>{
        if(e.key==='Enter'){ e.preventDefault(); input.nextElementSibling.click(); }
    });
});

// ===== SIDEBAR BUTTON LOGIC =====
const sidebarBtns = document.querySelectorAll('.sidebar button');
const reelsContainerEl = document.getElementById('reelsContainer');
const postsContainerEl = document.getElementById('postsContainer');
const storiesWrapperEl = document.getElementById('storiesWrapper');

sidebarBtns.forEach(btn=>{
    btn.addEventListener('click', ()=>{
        sidebarBtns.forEach(b=>b.classList.remove('active'));
        btn.classList.add('active');

        if(btn.id==='homeBtn'){ 
            postsContainerEl.style.display='flex'; 
            storiesWrapperEl.style.display='flex'; 
            reelsContainerEl.style.display='none'; 
        }
        else if(btn.id==='reelsBtnSidebar'){ 
            postsContainerEl.style.display='none'; 
            storiesWrapperEl.style.display='none'; 
            reelsContainerEl.style.display='flex'; 
            reelsContainerEl.style.flexDirection='column';
        }
        else if(btn.id === 'profileBtn'){ 
            postsContainerEl.style.display='none'; 
            storiesWrapperEl.style.display='none'; 
            reelsContainerEl.style.display='none'; 
            window.location.href = 'profile.php'; // ✅ Go to profile page
        }
        else { 
            postsContainerEl.style.display='none'; 
            storiesWrapperEl.style.display='none'; 
            reelsContainerEl.style.display='none'; 
            alert("Feature coming soon!");
        }
    });
});

// ===== REELS PLAY & BUTTONS LOGIC =====
function handleReels(){
    const reels = document.querySelectorAll('.reel video');
    const likeBtns = document.querySelectorAll('.reel-like-btn');
    const commentBtns = document.querySelectorAll('.reel-comment-btn');
    const shareBtns = document.querySelectorAll('.reel-share-btn');

    // Play only the visible video in viewport
    function playVisibleVideo(){
        reels.forEach(video=>{
            const rect = video.getBoundingClientRect();
            if(rect.top >= 0 && rect.bottom <= window.innerHeight){
                video.play();
            } else video.pause();
        });
    }

    window.addEventListener('scroll', playVisibleVideo);
    playVisibleVideo();

    // Like button toggle
    likeBtns.forEach(btn=>{
        btn.addEventListener('click', ()=>{
            if(btn.textContent==='♡'){
                btn.textContent='❤️';
                btn.style.transform='scale(1.3)';
                setTimeout(()=>btn.style.transform='scale(1)',150);
            } else btn.textContent='♡';
        });
    });

    // Comment button alert
    commentBtns.forEach(btn=>{
        btn.addEventListener('click', ()=>{ alert("Comment feature coming soon!"); });
    });

    // Share button alert
    shareBtns.forEach(btn=>{
        btn.addEventListener('click', ()=>{ alert("Share feature coming soon!"); });
    });
}

document.addEventListener('DOMContentLoaded', handleReels);
</script>
</body>
</html>
