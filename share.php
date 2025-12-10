<?php
session_start();
$conn = new mysqli("127.0.0.1","root","","instagram_clone");
if(!isset($_SESSION['user_id'])) exit;

$post_id = $_POST['post_id'];
$user_id = $_SESSION['user_id'];

$conn->query("INSERT INTO shares (post_id,user_id) VALUES ($post_id,$user_id)");
header("Location: welcome.php");
