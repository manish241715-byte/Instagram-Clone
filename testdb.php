<?php
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);

// Use localhost and 3306 explicitly
$conn = new mysqli("localhost","root","","instagram_clone", 3306);

if($conn->connect_error){
    die("Connection failed: ".$conn->connect_error);
} else {
    echo "Database connected successfully!";
}