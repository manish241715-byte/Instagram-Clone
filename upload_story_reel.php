<?php
session_start();
$conn = new mysqli("127.0.0.1","root","","instagram_clone");
if($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$user_id = $_SESSION['user_id']; // must be logged in

// Upload story
if(isset($_POST['upload_story'])){
    $file = $_FILES['story_file'];
    $type = $_POST['type'];
    $filename = 'uploads/'.$file['name'];
    move_uploaded_file($file['tmp_name'], $filename);
    $conn->query("INSERT INTO stories (user_id, media, type) VALUES ($user_id, '$filename', '$type')");
    echo "Story uploaded!";
}

// Upload reel
if(isset($_POST['upload_reel'])){
    $file = $_FILES['reel_file'];
    $caption = $conn->real_escape_string($_POST['caption']);
    $filename = 'uploads/'.$file['name'];
    move_uploaded_file($file['tmp_name'], $filename);
    $conn->query("INSERT INTO reels (user_id, media, caption) VALUES ($user_id, '$filename', '$caption')");
    echo "Reel uploaded!";
}
?>

<h2>Upload Story</h2>
<form method="post" enctype="multipart/form-data">
    <input type="file" name="story_file" required>
    <select name="type">
        <option value="image">Image</option>
        <option value="video">Video</option>
    </select>
    <button type="submit" name="upload_story">Upload</button>
</form>

<h2>Upload Reel</h2>
<form method="post" enctype="multipart/form-data">
    <input type="file" name="reel_file" required>
    <input type="text" name="caption" placeholder="Caption">
    <button type="submit" name="upload_reel">Upload</button>
</form>
