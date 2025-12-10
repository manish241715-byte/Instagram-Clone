<?php
$conn = new mysqli("127.0.0.1", "root", "", "instagram_clone");
$post_id = intval($_GET['post_id']);
$res = $conn->query("SELECT comments.comment, users.username FROM comments JOIN users ON comments.user_id = users.id WHERE post_id=$post_id ORDER BY comments.id ASC");
$comments = [];
while($row = $res->fetch_assoc()) $comments[] = $row;
echo json_encode($comments);
?>
