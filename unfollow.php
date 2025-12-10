<?php
session_start();
$conn = new mysqli("localhost","root","","instagram_clone");

$session_id = $_SESSION['user_id'];
$profile_id = $_GET['id'];

$sql = "DELETE FROM follows WHERE follower_id=$session_id AND following_id=$profile_id";
$conn->query($sql);

header("Location: profile.php?id=$profile_id");
?>
