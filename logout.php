<?php
session_start();
session_destroy(); // clear all session data
header("Location: login.php");
exit;
?>
