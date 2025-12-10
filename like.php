<?php
session_start();
$conn = new mysqli("127.0.0.1","root","","instagram_clone");
if(!isset($_SESSION['user_id'])) exit;

$post_id = $_POST['post_id'];
$user_id = $_SESSION['user_id'];

// Check if already liked
$check = $conn->query("SELECT * FROM likes WHERE post_id=$post_id AND user_id=$user_id");
if($check->num_rows == 0){
    $conn->query("INSERT INTO likes (post_id,user_id) VALUES ($post_id,$user_id)");
}
header("Location: welcome.php");
