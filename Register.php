<?php
session_start();
$conn = new mysqli("127.0.0.1", "root", "", "instagram_clone");
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

$error = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fullname = $_POST['fullname'];
    $username = $_POST['username'];
    $email    = $_POST['email'];
    $password = $_POST['password'];

    // Check if username or email already exists
    $stmt = $conn->prepare("SELECT * FROM users WHERE username=? OR email=?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows > 0){
        $error = "Username or Email already exists";
    } else {
        $stmt2 = $conn->prepare("INSERT INTO users (fullname, username, email, password) VALUES (?, ?, ?, ?)");
        $stmt2->bind_param("ssss", $fullname, $username, $email, $password);
        if($stmt2->execute()){
            header("Location: login.php");
            exit;
        } else {
            $error = "Error: ".$conn->error;
        }
        $stmt2->close();
    }
    $stmt->close();
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Instagram - Register</title>
<link rel="stylesheet" href="style.css">
<style>
/* Simple, gentle Instagram-like form */
body {
    font-family: Arial, sans-serif;
    background-color: #fafafa;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
}

.container {
    display: flex;
    gap: 40px;
    align-items: center;
}

.phone img {
    height: 500px;
    border-radius: 20px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
}

.login-box {
    width: 320px;
    padding: 30px;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    text-align: center;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
}

.login-box .logo {
    font-family: 'Billabong', cursive;
    font-size: 40px;
    margin-bottom: 20px;
}

.login-box form input {
    width: 100%;
    padding: 10px;
    margin-bottom: 10px;
    border: 1px solid #ddd;
    border-radius: 3px;
    background-color: #fafafa;
    font-size: 14px;
}

.password-box {
    position: relative;
}

.password-box span {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
}

.login-box button {
    width: 100%;
    padding: 10px;
    background-color: #0095f6;
    color: white;
    border: none;
    border-radius: 4px;
    font-weight: bold;
    margin-top: 10px;
    cursor: pointer;
}

.login-box button:hover {
    background-color: #007acc;
}

.signup-box {
    margin-top: 15px;
    font-size: 14px;
}
.signup-box a {
    color: #0095f6;
    text-decoration: none;
    font-weight: bold;
}

.error {
    color: red;
    font-size: 13px;
    margin-bottom: 10px;
}
</style>
</head>
<body>
<div class="container">
    <div class="phone">
        <img src="https://instahomepageby-keerthi.netlify.app/media/insta.png" alt="Instagram Phone Mock">
    </div>

    <div class="login-box">
        <h1 class="logo">Instagram</h1>
        <?php if($error != "") { echo "<div class='error'>$error</div>"; } ?>

        <form method="POST" action="register.php">
            <input type="text" name="fullname" placeholder="Full Name" required>
            <input type="text" name="username" placeholder="Username" required>
            <input type="email" name="email" placeholder="Email" required>

            <!-- Password -->
            <div class="password-box">
                <input type="password" id="password" name="password" placeholder="Password" required>
                <span id="togglePassword">⏿</span>
            </div>

            <!-- Confirm Password -->
            <div class="password-box">
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm Password" required>
                <span id="toggleConfirm">⏿</span>
            </div>

            <button type="submit">Sign Up</button>
        </form>

        <div class="signup-box">
            Already have an account? <a href="login.php">Log in</a>
        </div>
    </div>
</div>

<script>
const passwordField = document.getElementById("password");
const togglePasswordBtn = document.getElementById("togglePassword");
const confirmPasswordField = document.getElementById("confirm_password");
const toggleConfirmBtn = document.getElementById("toggleConfirm");

togglePasswordBtn.onclick = function () {
    if(passwordField.type === "password"){
        passwordField.type = "text";
        togglePasswordBtn.textContent = "👀";
    } else {
        passwordField.type = "password";
        togglePasswordBtn.textContent = "⏿";
    }
};

toggleConfirmBtn.onclick = function () {
    if(confirmPasswordField.type === "password"){
        confirmPasswordField.type = "text";
        toggleConfirmBtn.textContent = "👀";
    } else {
        confirmPasswordField.type = "password";
        toggleConfirmBtn.textContent = "⏿";
    }
};

document.querySelector("form").onsubmit = function(e){
    if(passwordField.value !== confirmPasswordField.value){
        alert("Passwords do not match!");
        e.preventDefault();
    }
};
</script>
</body>
</html>
