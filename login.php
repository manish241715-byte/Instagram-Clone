<?php
session_start();

// 1. Connect to database
$conn = new mysqli("127.0.0.1", "root", "", "instagram_clone");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error = "";

// 2. Handle login form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Safer query using prepared statement
    $stmt = $conn->prepare("SELECT * FROM users WHERE username=? OR email=?");
    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        // Plain text password check (for now)
        if ($row['password'] == $password) {
            $_SESSION['username'] = $row['username'];
            $_SESSION['user_id'] = $row['id']; // save user id for posts/likes
            header("Location: welcome.php");
            exit;
        } else {
            $error = "Incorrect password";
        }
    } else {
        $error = "User not found";
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
<title>Instagram - Login</title>
<link rel="stylesheet" href="style.css">
<style>
body { font-family: Arial, sans-serif; background:#fafafa; display:flex; justify-content:center; padding-top:50px; }
.container { display:flex; gap:50px; }
.phone img { width:300px; height:auto; border-radius:20px; box-shadow:0 5px 15px rgba(0,0,0,0.1); }
.login-box { width:300px; background:#fff; padding:20px; border:1px solid #ddd; border-radius:10px; text-align:center; }
.login-box h1.logo { font-family: 'Lucida Handwriting', cursive; margin-bottom:20px; }
.login-box input { width:90%; padding:10px; margin:10px 0; border:1px solid #ccc; border-radius:5px; }
.login-box button { width:95%; padding:10px; margin-top:10px; background:#0095f6; color:#fff; border:none; border-radius:5px; cursor:pointer; font-weight:bold; }
.password-box { position:relative; }
#togglePassword { position:absolute; right:15px; top:12px; cursor:pointer; }
.signup-box { text-align:center; margin-top:15px; }
.signup-box a { color:#0095f6; text-decoration:none; font-weight:bold; }
.get-app { text-align:center; margin-top:30px; }
.get-app p { margin-bottom:10px; font-weight:bold; }
.app-buttons img { width:120px; margin:5px; }
</style>
</head>
<body>
<div class="container">

    <!-- Phone mock -->
    <div class="phone">
        <img src="https://instahomepageby-keerthi.netlify.app/media/insta.png" alt="Instagram Phone Mock">
    </div>

    <!-- Login box -->
    <div>
        <div class="login-box">
            <h1 class="logo">Instagram</h1>

            <?php if($error != "") { echo "<p style='color:red;text-align:center;'>$error</p>"; } ?>

            <form method="POST" action="login.php">
                <input type="text" name="username" placeholder="Username or Email" required>
                <div class="password-box">
                    <input type="password" id="password" name="password" placeholder="Password" required>
                    <span id="togglePassword">👁️</span>
                </div>

                <button type="submit">Log In</button>
            </form>
        </div>

        <div class="signup-box">
            Don't have an account? <a href="register.php">Sign up</a>
        </div>

        <div class="get-app">
            <p>Get the app.</p>
            <div class="app-buttons">
                <a href="#"><img class="store-badge" src="https://upload.wikimedia.org/wikipedia/commons/7/78/Google_Play_Store_badge_EN.svg" alt="Google Play"></a>
                <a href="#"><img class="store-badge" src="https://developer.apple.com/assets/elements/badges/download-on-the-app-store.svg" alt="App Store"></a>
            </div>
        </div>
    </div>

</div>

<script>
const passwordField = document.getElementById("password");
const toggleBtn = document.getElementById("togglePassword");

toggleBtn.onclick = function () {
    if (passwordField.type === "password") {
        passwordField.type = "text";
        toggleBtn.textContent = "🙈";
    } else {
        passwordField.type = "password";
        toggleBtn.textContent = "👁️";
    }
};
</script>
</body>
</html>
