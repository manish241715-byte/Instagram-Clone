<?php
session_start();
if(!isset($_SESSION['username'])){
    header("Location: login.php");
    exit;
}

$conn = new mysqli("127.0.0.1","root","","instagram_clone");
if($conn->connect_error) die("Connection failed: ".$conn->connect_error);

// Logged-in user ID
$session_user = $_SESSION['username'];
$session_id = $conn->query("SELECT id FROM users WHERE username='$session_user'")->fetch_assoc()['id'];

// Check if user_id is sent
if(isset($_POST['user_id'])){
    $user_id = intval($_POST['user_id']);
    
    // Check if already following
    $check = $conn->query("SELECT * FROM follows WHERE follower_id=$session_id AND following_id=$user_id");
    if($check->num_rows > 0){
        // Unfollow
        $conn->query("DELETE FROM follows WHERE follower_id=$session_id AND following_id=$user_id");
    } else {
        // Follow
        $conn->query("INSERT INTO follows (follower_id, following_id) VALUES ($session_id, $user_id)");
    }
}

// Redirect back to the profile page
$redirect_user = isset($_GET['user']) ? $_GET['user'] : $_SESSION['username'];
header("Location: profile.php?user=".$redirect_user);
exit;
?>
