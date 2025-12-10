<?php
session_start();
if(!isset($_SESSION['username'])){
    header("Location: login.php");
    exit;
}

$conn = new mysqli("127.0.0.1","root","","instagram_clone");
if($conn->connect_error) die("Connection failed: ".$conn->connect_error);

$session_user = $_SESSION['username'];

// Fetch user info
$user_sql = $conn->query("SELECT * FROM users WHERE username='$session_user'");
$user = $user_sql->fetch_assoc();

// Count posts
$post_count_sql = $conn->query("SELECT COUNT(*) as total FROM posts WHERE user_id=".$user['id']);
$post_count = $post_count_sql->fetch_assoc()['total'];

// Count followers
$follower_count_sql = $conn->query("SELECT COUNT(*) as total FROM followers WHERE following_id=".$user['id']);
$follower_count = $follower_count_sql->fetch_assoc()['total'];

// Count following
$following_count_sql = $conn->query("SELECT COUNT(*) as total FROM followers WHERE follower_id=".$user['id']);
$following_count = $following_count_sql->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html>
<head>
<title><?php echo $user['username']; ?> | Instagram Clone</title>
<style>
body {font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #fafafa;}
.profile-header {display: flex; align-items: center; gap: 20px; margin-bottom: 20px;}
.profile-pic {width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 2px solid #ccc;}
.stats {display: flex; gap: 20px; margin-top: 10px;}
.stats div {text-align: center;}
.posts {display: flex; flex-wrap: wrap; gap: 10px; margin-top: 20px;}
.posts img {width: 150px; height: 150px; object-fit: cover; cursor: pointer;}
button.edit-profile {padding: 5px 15px; margin-top: 10px; cursor: pointer; border: 1px solid #ccc; border-radius: 5px; background: white;}
</style>
</head>
<body>

<div class="profile-header">
    <img src="uploads/<?php echo $user['profile_pic']; ?>" class="profile-pic">
    <div>
        <h2><?php echo $user['username']; ?></h2>
        <div class="stats">
            <div><strong><?php echo $post_count; ?></strong><br>Posts</div>
            <div><strong><?php echo $follower_count; ?></strong><br>Followers</div>
            <div><strong><?php echo $following_count; ?></strong><br>Following</div>
        </div>
        <p><?php echo $user['bio']; ?></p>
        <button class="edit-profile">Edit Profile</button>
    </div>
</div>

<div class="posts">
<?php
$posts_sql = $conn->query("SELECT * FROM posts WHERE user_id=".$user['id']." ORDER BY id DESC");
while($post = $posts_sql->fetch_assoc()){
    echo '<img src="uploads/'.$post['media'].'" alt="post">';
}
?>
</div>

</body>
</html>
