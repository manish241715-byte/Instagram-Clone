<?php
session_start();
$conn = new mysqli("127.0.0.1","root","","instagram_clone");
if(!isset($_SESSION['user_id'])) exit;

$post_id = $_POST['post_id'];
$user_id = $_SESSION['user_id'];
$comment = $_POST['comment'];

$conn->query("INSERT INTO comments (post_id,user_id,comment) VALUES ($post_id,$user_id,'$comment')");
header("Location: welcome.php");
