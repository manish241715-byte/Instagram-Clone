<?php
session_start();
if(!isset($_SESSION['username'])){
    header("Location: login.php");
    exit;
}

$conn = new mysqli("127.0.0.1", "root", "", "instagram_clone");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$session_user = $conn->real_escape_string($_SESSION['username']);
$session_res = $conn->query("SELECT id FROM users WHERE username='$session_user'");
$session_row = $session_res->fetch_assoc();
$session_id = $session_row['id'];

// Handle like/unlike
if(isset($_POST['like_post'])){
    $post_id = intval($_POST['post_id']);
    $like_check = $conn->query("SELECT * FROM likes WHERE post_id=$post_id AND user_id=$session_id");
    if($like_check->num_rows > 0){
        $conn->query("DELETE FROM likes WHERE post_id=$post_id AND user_id=$session_id");
    } else {
        $conn->query("INSERT INTO likes (post_id, user_id) VALUES ($post_id, $session_id)");
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
                echo "success";
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

// Fetch posts
$sql = "SELECT posts.id, posts.content, posts.image, users.username, users.profile_pic
        FROM posts
        LEFT JOIN users ON posts.user_id = users.id
        ORDER BY posts.id DESC";
$result = $conn->query($sql);

// Fetch stories
$story_res = $conn->query("SELECT stories.*, users.username FROM stories JOIN users ON stories.user_id = users.id ORDER BY stories.id ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Instagram Clone</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>
<style>
body { font-family: -apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif; background:#000; color:#fff; margin:0;}
.header { display:flex; justify-content:center; align-items:center; padding:12px; background:#111; position:sticky; top:0; z-index:100;}
.header h1 { font-family:'Pacifico', cursive; font-size:28px; color:#fff; margin:0;}
.logout-btn { position:absolute; right:20px; text-decoration:none; color:#0095f6; font-weight:bold;}

/* Story bar wrapper */
.stories-container-wrapper {
    position:sticky;
    top:60px;
    background:#111;
    z-index:50;
    padding:8px 0;
    border-bottom:1px solid #222;
    display:flex;
    justify-content:center;
}

/* Stories scrollable container */
.stories-container {
    display:flex;
    gap:12px;
    overflow-x:auto;
    scroll-behavior:smooth;
    align-items:center;
    max-width:100%;
    padding:0 12px;
}
.stories-container::-webkit-scrollbar { display:none; }
.story { text-align:center; flex:0 0 auto; }
.story-circle { width:80px; height:80px; border-radius:50%; padding:2px; background:linear-gradient(45deg,#feda75,#fa7e1e,#d62976,#962fbf,#4f5bd5); display:flex; align-items:center; justify-content:center; cursor:pointer;}
.story-circle img { width:72px; height:72px; border-radius:50%; object-fit:cover; border:2px solid #000; }
.story-username { font-size:12px; margin-top:4px; color:#fff; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:80px; }

.container { width:500px; max-width:100%; margin:15px auto 20px auto; display:flex; flex-direction:column; gap:20px;}
.post { background:#111; border-radius:0px; border:1px solid #222; overflow:hidden; position:relative; }
.post-header { display:flex; align-items:center; padding:12px; gap:12px; }
.post-header img { width:45px; height:45px; border-radius:50%; object-fit:cover; }
.post-header span { font-weight:600; font-size:15px; color:#fff; }
.post-content img { width:100%; max-height:600px; object-fit:cover; cursor:pointer; transition:0.3s; border-radius:10px; }
.post-content img:hover { transform: scale(1.02); }
.post-content p { padding:0 12px 12px 12px; margin:5px 0; font-size:14px; line-height:1.5; color:#fff;}
.post-actions { display:flex; align-items:center; justify-content:space-between; padding:8px 12px; }
.post-actions-left { display:flex; align-items:center; gap:15px; }
.post-actions button { background:none; border:none; cursor:pointer; font-size:24px; color:#fff; transition:0.2s; }
.likes-count { padding:0 12px 12px 12px; font-weight:500; font-size:14px; color:#fff; }
.comment-box { display:flex; gap:5px; padding:0 12px 12px 12px; }
.comment-box input { flex:1; padding:6px; border:1px solid #333; border-radius:4px; background:#222; color:#fff;}
.comment-box button { background:none; border:none; font-size:20px; cursor:pointer; color:#0095f6;}
.comment-list { padding:0 12px 12px 12px; font-size:14px; color:#fff;}
.comment-list b { margin-right:6px; }
.heart-overlay { position:absolute; top:50%; left:50%; transform:translate(-50%, -50%); font-size:100px; color:rgba(255,0,0,0.7); display:none; pointer-events:none; }
</style>
</head>
<body>

<div class="header">
    <h1>Instagram</h1>
    <a href="logout.php" class="logout-btn">Logout</a>
</div>

<!-- Stories -->
<div class="stories-container-wrapper">
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

<div class="container">
<?php
if($result && $result->num_rows > 0){
    while($row = $result->fetch_assoc()){
        $profile_pic = $row['profile_pic'] ? $row['profile_pic'] : 'uploads/default.jpg';
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
// Like button
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

// Double-tap like
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

// Toggle comment input
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
            } else {
                alert("Comment not added, try again.");
            }
        });
    });
});

// Submit comment on Enter key
document.querySelectorAll('.comment-input').forEach(input=>{
    input.addEventListener('keypress', e=>{
        if(e.key==='Enter'){ e.preventDefault(); input.nextElementSibling.click(); }
    });
});
</script>

</body>
</html>
