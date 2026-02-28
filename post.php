<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

if(!isset($_SESSION['username'])){
    exit('Not logged in');
}

// Connect to DB
$conn = new mysqli("127.0.0.1", "root", "", "instagram_clone");
if($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$session_user = $conn->real_escape_string($_SESSION['username']);
$session_res = $conn->query("SELECT id FROM users WHERE username='$session_user'");
$session_row = $session_res->fetch_assoc();
$session_id = $session_row['id'];

// Fetch posts without video
$sql = "SELECT posts.id, posts.content, posts.image, users.username, users.profile_pic 
        FROM posts LEFT JOIN users ON posts.user_id = users.id 
        WHERE posts.video IS NULL
        ORDER BY posts.id DESC";
$result = $conn->query($sql);

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
            $c_res = $conn->query("SELECT comments.comment_text, users.username 
                                   FROM comments 
                                   JOIN users ON comments.user_id = users.id 
                                   WHERE post_id=".$row['id']." 
                                   ORDER BY comments.id ASC");
            while($c = $c_res->fetch_assoc()){
                echo "<div><b>{$c['username']}</b>: {$c['comment_text']}</div>";
            }
        ?>
    </div>
</div>
<?php
    }
}
$conn->close();
?>