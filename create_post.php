<?php
session_start();
if(!isset($_SESSION['username'])) { header("Location: login.php"); exit; }

$conn = new mysqli("127.0.0.1","root","","instagram_clone");
if($conn->connect_error) die("Connection failed: ".$conn->connect_error);

// get current user id
$stmt = $conn->prepare("SELECT id FROM users WHERE username=?");
$stmt->bind_param("s", $_SESSION['username']);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();
$stmt->close();
if(!$user){ die("User not found"); }
$uid = $user['id'];

$error = "";
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $content = trim($_POST['content']);
    $image = trim($_POST['image']) ?: null;

    if($content === "" && $image === null){ $error = "Post cannot be empty."; }
    else {
        $i = $conn->prepare("INSERT INTO posts (user_id, content, image) VALUES (?, ?, ?)");
        $i->bind_param("iss", $uid, $content, $image);
        if($i->execute()) header("Location: feed.php");
        else $error = "DB error: ".$conn->error;
        $i->close();
    }
}
$conn->close();
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Create post</title></head>
<body>
<h2>Create Post</h2>
<?php if($error) echo "<p style='color:red;'>$error</p>"; ?>
<form method="post">
  <textarea name="content" placeholder="What's on your mind?" style="width:400px;height:100px"></textarea><br>
  <input type="text" name="image" placeholder="Image URL (optional)" style="width:400px"><br><br>
  <button type="submit">Post</button>
</form>
<p><a href="feed.php">Back to feed</a></p>
</body>
</html>
