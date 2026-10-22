<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}
include("../config.php");
$user_id = $_SESSION['user_id'];

if(!isset($_SESSION['fullname'])) {
    $u = mysqli_fetch_assoc(mysqli_query($conn, "SELECT fullname FROM users WHERE id='$user_id'"));
    $_SESSION['fullname'] = $u['fullname'];
}
$customer_name = $_SESSION['fullname'];

$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id='$user_id'"));

$verified = false;
if(isset($_POST['verify_password'])){
    $entered = mysqli_real_escape_string($conn, trim($_POST['current_password']));
    $db_pass = $user['password'];
    
    if($entered === $db_pass || password_verify($entered, $db_pass)){
        $_SESSION['verified_settings'] = true;
        echo "<script>alert('Password Verified! You may now update your account details.');</script>";
        $verified = true;
    }else{
        echo "<script>alert('Incorrect Password! Please try again.');</script>";
    }
}

if(isset($_SESSION['verified_settings']) && $_SESSION['verified_settings'] === true){
    $verified = true;
    
    if(isset($_POST['update_profile'])){
        $new_fullname = mysqli_real_escape_string($conn, trim($_POST['new_fullname']));
        $new_email = mysqli_real_escape_string($conn, trim($_POST['new_email']));
        $new_phone = mysqli_real_escape_string($conn, trim($_POST['new_phone']));
        
        $check = mysqli_query($conn, "SELECT * FROM users WHERE fullname='$new_fullname' AND id<>'$user_id'");
        if(mysqli_num_rows($check) > 0){
            echo "<script>alert('That Name is already taken. Please use another.');</script>";
        }else{
            mysqli_query($conn, "UPDATE users SET fullname='$new_fullname', email='$new_email', phone='$new_phone' WHERE id='$user_id'");
            $_SESSION['fullname'] = $new_fullname;
            $customer_name = $new_fullname;
            echo "<script>alert('Profile Updated Successfully!');</script>";
            $user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id='$user_id'"));
        }
    }
    
    if(isset($_POST['change_password'])){
        $old_pass = mysqli_real_escape_string($conn, trim($_POST['old_password']));
        $new_pass = mysqli_real_escape_string($conn, trim($_POST['new_password']));
        $confirm_pass = mysqli_real_escape_string($conn, trim($_POST['confirm_password']));
        $db_pass = $user['password'];
        
        if(($old_pass === $db_pass || password_verify($old_pass, $db_pass)) && $new_pass === $confirm_pass && strlen($new_pass) >= 4){
            $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
            mysqli_query($conn, "UPDATE users SET password='$hashed' WHERE id='$user_id'");
            echo "<script>alert('Password Changed Successfully! Please login again.'); location='../login.php';</script>";
            unset($_SESSION['verified_settings']);
            session_destroy();
            exit();
        }elseif($new_pass !== $confirm_pass){
            echo "<script>alert('New Password and Confirmation do not match!');</script>";
        }elseif(strlen($new_pass) < 4){
            echo "<script>alert('Password must be at least 4 characters long!');</script>";
        }else{
            echo "<script>alert('Current Password is incorrect!');</script>";
        }
    }
    
    if(isset($_GET['lock'])){
        unset($_SESSION['verified_settings']);
        header("Location: settings.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings | M&Scents</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700;800&display=swap" rel="stylesheet">
    <style>
    :root {
        --font-heading: 'Playfair Display', Georgia, serif;
        --font-body: 'Inter', system-ui, sans-serif;
        --color-gold: #d4af37;
        --color-gold-light: #f0c94d;
        --color-gold-bright: #ffd700;
        --color-bg-dark: #0f0f0f;
        --color-bg-card: #1a1a1a;
        --color-bg-sidebar: #151515;
        --color-border: rgba(212,175,55,0.15);
        --color-text: #e5e5e5;
        --color-text-muted: #888;
        --color-success: #4ade80;
        --color-input: #252525;
    }
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    html, body {
        width: 100%;
        overflow-x: hidden;
    }
    body {
        font-family: var(--font-body);
        background: var(--color-bg-dark);
        color: var(--color-text);
        min-height: 100vh;
    }
    h1, h2, h3 { font-family: var(--font-heading); }

    .hamburger {
        display: none;
        flex-direction: column;
        gap: 5px;
        cursor: pointer;
        padding: 6px 8px;
        border-radius: 6px;
        background: transparent;
    }
    .hamburger:hover {
        background: rgba(212,175,55,0.12);
    }
    .hamburger span {
        width: 24px;
        height: 2px;
        background: linear-gradient(90deg, var(--color-gold), var(--color-gold-light));
        border-radius: 2px;
    }
    .hamburger.active span:nth-child(1) {
        transform: translateY(7px) rotate(45deg);
    }
    .hamburger.active span:nth-child(2) {
        opacity: 0;
    }
    .hamburger.active span:nth-child(3) {
        transform: translateY(-7px) rotate(-45deg);
    }

    .topnav {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 999;
        padding: 12px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: rgba(0,0,0,0.85);
        border-bottom: 1px solid var(--color-border);
        box-shadow: 0 2px 15px rgba(0,0,0,0.4);
    }
    .nav-left-group {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .topnav .logo-group {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .topnav .logo-img {
        height: 45px;
        width: auto;
    }
    .topnav .logo-text {
        font-family: var(--font-heading);
        font-size: 20px;
        color: var(--color-gold);
        font-weight: 700;
        text-decoration: none;
        letter-spacing: 1px;
    }
    .topnav .nav-links {
        display: flex;
        align-items: center;
    }
    .topnav .nav-links a {
        color: #ddd;
        text-decoration: none;
        margin-left: 14px;
        font-size: 14px;
        font-weight: 500;
        position: relative;
        padding-bottom: 4px;
    }
    .topnav .nav-links a::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 0;
        height: 2px;
        background: linear-gradient(90deg, var(--color-gold), var(--color-gold-light));
    }
    .topnav .nav-links a:hover {
        color: var(--color-gold);
    }
    .topnav .nav-links a:hover::after {
        width: 100%;
    }
    .nav-links .dashboard-link {
        display: inline-block;
    }
    @media (max-width: 768px) {
        .nav-links .dashboard-link {
            display: none;
        }
    }

    .overlay {
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.6);
        z-index: 998;
        opacity: 0;
        visibility: hidden;
    }
    .overlay.active {
        opacity: 1;
        visibility: visible;
    }

    .main-layout {
        display: grid;
        grid-template-columns: 220px 1fr;
        gap: 25px;
        padding: 100px 20px 60px;
        max-width: 1100px;
        margin: 0 auto;
    }

    .sidebar {
        background: var(--color-bg-sidebar);
        border-radius: 12px;
        border: 1px solid var(--color-border);
        padding: 20px 15px;
        height: fit-content;
        position: sticky;
        top: 100px;
    }
    .sidebar h2 {
        font-size: 18px;
        color: var(--color-gold);
        margin-bottom: 18px;
        padding-bottom: 10px;
        border-bottom: 1px solid var(--color-border);
    }
    .sidebar a {
        display: block;
        color: #ccc;
        text-decoration: none;
        padding: 10px 12px;
        margin-bottom: 5px;
        border-radius: 6px;
        font-size: 14px;
    }
    .sidebar a:hover {
        background: rgba(212,175,55,0.08);
        color: var(--color-gold);
    }
    .sidebar a.active {
        background: rgba(212,175,55,0.12);
        color: var(--color-gold);
        border-left: 2px solid var(--color-gold);
    }

    .content {
        opacity: 1;
    }
    .content h1 {
        font-size: clamp(26px, 5vw, 32px);
        color: #fff;
        margin-bottom: 8px;
    }
    .subheading {
        color: var(--color-text-muted);
        font-size: 15px;
        margin-bottom: 30px;
    }

    .lock-box {
        background: linear-gradient(135deg, rgba(45,38,20,0.9), rgba(26,26,26,0.95));
        border: 1px solid rgba(212,175,55,0.25);
        border-radius: 16px;
        padding: 28px 22px;
        max-width: 550px;
    }
    .lock-box h2 {
        font-size: 20px;
        color: var(--color-gold);
        margin-bottom: 10px;
    }
    .lock-box p {
        color: #b8a878;
        margin-bottom: 20px;
        font-size: 14px;
        line-height: 1.6;
    }

    .verified-badge {
        display: inline-block;
        background: rgba(74,222,128,0.08);
        color: var(--color-success);
        border: 1px solid rgba(74,222,128,0.25);
        padding: 8px 16px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 500;
        margin-bottom: 12px;
    }
    .lock-link {
        display: inline-block;
        color: var(--color-gold);
        text-decoration: none;
        font-size: 14px;
        margin-bottom: 25px;
    }
    .lock-link:hover {
        text-decoration: underline;
    }

    .form-box {
        background: var(--color-bg-card);
        border: 1px solid var(--color-border);
        border-radius: 14px;
        padding: 22px 18px;
        margin-bottom: 25px;
    }
    .form-box h3 {
        font-size: 17px;
        color: var(--color-gold);
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 1px solid var(--color-border);
    }
    .form-group {
        margin-bottom: 16px;
    }
    .form-group label {
        display: block;
        color: #ccc;
        font-size: 14px;
        font-weight: 500;
        margin-bottom: 6px;
    }
    .form-group input {
        width: 100%;
        padding: 12px 14px;
        background: var(--color-input);
        border: 1px solid transparent;
        border-radius: 8px;
        color: #fff;
        font-family: var(--font-body);
        font-size: 14px;
    }
    .form-group input:focus {
        outline: none;
        border-color: var(--color-gold);
    }
    .hint-text {
        margin-top: 12px;
        font-size: 13px;
        color: var(--color-text-muted);
        line-height: 1.5;
    }

    .btn-primary {
        width: 100%;
        padding: 13px 30px;
        background: linear-gradient(90deg, var(--color-gold), var(--color-gold-light));
        color: #000;
        border: none;
        border-radius: 50px;
        font-size: 15px;
        font-weight: 700;
        cursor: pointer;
    }
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 18px rgba(212,175,55,0.3);
    }

    @media (max-width: 900px) {
        .hamburger { display: flex; }
        .main-layout {
            grid-template-columns: 1fr;
            gap: 20px;
            padding: 100px 15px 50px;
            position: relative;
        }
        .sidebar {
            position: fixed;
            top: 70px;
            left: 15px;
            width: 240px;
            z-index: 999;
            transform: translateX(-120%);
            opacity: 0;
        }
        .sidebar.active {
            transform: translateX(0);
            opacity: 1;
        }
    }
    @media (max-width: 600px) {
        .topnav {
            padding: 10px 15px;
        }
        .topnav .nav-links a {
            margin-left: 10px;
            font-size: 13px;
        }
        .lock-box,
        .form-box {
            padding: 20px 15px;
        }
        .btn-primary {
            padding: 12px 25px;
            font-size: 15px;
        }
    }
    @media (max-width: 480px) {
        .topnav .nav-links {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 4px;
        }
        .topnav .nav-links a {
            margin: 0 6px;
        }
    }
    </style>
</head>
<body>
<div class="overlay" id="overlay" onclick="toggleSidebar()"></div>
<div class="topnav">
    <div class="nav-left-group">
        <div class="hamburger" id="hamburger" onclick="toggleSidebar()">
            <span></span>
            <span></span>
            <span></span>
        </div>
        <div class="logo-group">
            <img src="../images/logo.png" alt="M&Scents Logo" class="logo-img">
            <a href="home.php" class="logo-text">M&Scents</a>
        </div>
    </div>
    <div class="nav-links">
        <a href="home.php">Home</a>
        <a href="../collection.php">Collection</a>
        <a href="dashboard.php" class="dashboard-link">Dashboard</a>
    </div>
</div>
<div class="main-layout">
    <div class="sidebar" id="sidebar">
        <h2>Dashboard</h2>
        <a href="profile.php">Profile</a>
        <a href="orders.php">Orders</a>
        <a href="addresses.php">Addresses</a>
        <a href="settings.php" class="active">Settings</a>
        <a href="about.php">About</a>
        <a href="../logout.php">Logout</a>
    </div>
    <div class="content">
        <h1>Account Settings</h1>
        <p class="subheading">Update your account information and change your password</p>
        <?php if(!$verified): ?>
        <div class="lock-box">
            <h2>Verify Your Identity</h2>
            <p>Please enter your current password to access and update your account settings. This is to protect your account security.</p>
            <form method="post">
                <div class="form-group">
                    <label>Enter Your Current Password</label>
                    <input type="password" name="current_password" placeholder="Type your password here..." required>
                </div>
                <button type="submit" name="verify_password" class="btn-primary">Verify Password</button>
            </form>
        </div>
        <?php else: ?>
        <div class="verified-badge">Verified — You may now edit your account</div>
        <a href="settings.php?lock=1" class="lock-link">← Lock & Re-verify later</a>
        <div class="form-box">
            <h3>Update Profile Information</h3>
            <form method="post">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="new_fullname" value="<?php echo htmlspecialchars($user['fullname']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="new_email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label>Phone / Contact Number</label>
                    <input type="text" name="new_phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" required>
                </div>
                <button type="submit" name="update_profile" class="btn-primary">Save Changes</button>
            </form>
        </div>
        <div class="form-box">
            <h3>Change Password</h3>
            <form method="post">
                <div class="form-group">
                    <label>Current Password</label>
                    <input type="password" name="old_password" placeholder="Enter current password" required>
                </div>
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="new_password" placeholder="At least 4 characters" required>
                </div>
                <div class="form-group">
                    <label>Confirm New Password</label>
                    <input type="password" name="confirm_password" placeholder="Re-type new password" required>
                </div>
                <button type="submit" name="change_password" class="btn-primary">Update Password</button>
                <p class="hint-text">After changing password, you will be logged out and required to login again.</p>
            </form>
        </div>
        <?php endif; ?>
    </div>
</div>
<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const hamburger = document.getElementById('hamburger');
    const overlay = document.getElementById('overlay');
    sidebar.classList.toggle('active');
    hamburger.classList.toggle('active');
    overlay.classList.toggle('active');
}
document.querySelectorAll('#sidebar a').forEach(link => {
    link.addEventListener('click', () => {
        if (window.innerWidth <= 900) {
            toggleSidebar();
        }
    });
});
</script>
</body>
</html>