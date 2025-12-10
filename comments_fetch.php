<?php
session_start();
header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);
$post_id = intval($data['post_id'] ?? 0);

$conn = new mysqli("127.0.0.1","root","","instagram_clone");
if($conn->connect_error){ echo json_encode(['success'=>false,'error'=>$conn->connect_error]); exit; }

$stmt = $conn->prepare("SELECT c.comment, c.created_at, u.username FROM comments c JOIN users u ON c.user_id=u.id WHERE c.post_id=? ORDER BY c.created_at ASC");
$stmt->bind_param("i",$post_id);
$stmt->execute();
$res = $stmt->get_result();
$comments = [];
while($row = $res->fetch_assoc()) $comments[] = $row;
$stmt->close();
echo json_encode(['success'=>true,'comments'=>$comments]);
$conn->close();
