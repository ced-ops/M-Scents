<?php
session_start();
include("../config.php");
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

/* ============================
   SAVE PROFILE
============================ */
if(isset($_POST['save_profile'])){
    $fullname = mysqli_real_escape_string($conn,$_POST['fullname']);
    $email = mysqli_real_escape_string($conn,$_POST['email']);
    $phone = mysqli_real_escape_string($conn,$_POST['phone']);
    $birthday = mysqli_real_escape_string($conn,$_POST['birthday']);
    $gender = mysqli_real_escape_string($conn,$_POST['gender']);
    $province = mysqli_real_escape_string($conn,$_POST['province']);
    $city = mysqli_real_escape_string($conn,$_POST['city']);
    $barangay = mysqli_real_escape_string($conn,$_POST['barangay']);
    $street = mysqli_real_escape_string($conn,$_POST['street']);
    $zipcode = mysqli_real_escape_string($conn,$_POST['zipcode']);
    
    $update = mysqli_query($conn,"UPDATE users SET
        fullname='$fullname',
        email='$email',
        phone='$phone',
        birthday='$birthday',
        gender='$gender',
        province='$province',
        city='$city',
        barangay='$barangay',
        street='$street',
        zipcode='$zipcode',
        profile_completed='1'
        WHERE id='$user_id'");
    
    if($update){
        $_SESSION['fullname']=$fullname;
        echo "<script>
        alert('Profile updated successfully!');
        window.location='profile.php';
        </script>";
        exit();
    }else{
        echo "<script>alert('Something went wrong!');</script>";
    }
}

/* ============================
   LOAD USER INFO
============================ */
$getUser = mysqli_query($conn,"SELECT * FROM users WHERE id='$user_id'");
$user = mysqli_fetch_assoc($getUser);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | M&Scents</title>
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

    /* ✅ HAMBURGER ICON — NO ANIMATION */
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

    /* ✅ TOP NAVBAR — NO ANIMATION */
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
        background: linear-gradient(90deg, var(--color-gold), var(--color-gold-light), var(--color-gold-bright));
        -webkit-background-clip: text;
        background-clip: text;
        -webkit-text-fill-color: transparent;
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
    .topnav .nav-links a:hover,
    .topnav .nav-links a.active {
        color: var(--color-gold);
    }
    .topnav .nav-links a:hover::after,
    .topnav .nav-links a.active::after {
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

    /* ✅ OVERLAY — NO TRANSITION */
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

    /* ✅ MAIN LAYOUT */
    .main-layout {
        display: grid;
        grid-template-columns: 220px 1fr;
        gap: 25px;
        padding: 100px 20px 60px;
        max-width: 1100px;
        margin: 0 auto;
    }

    /* ✅ SIDEBAR — NO ANIMATION, REWARDS REMOVED */
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
        padding-left: 16px;
    }
    .sidebar a.active {
        background: rgba(212,175,55,0.12);
        color: var(--color-gold);
        border-left: 2px solid var(--color-gold);
    }

    /* ✅ MAIN CONTENT — NO ANIMATION */
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

    /* ✅ FORM GRID */
    .form-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 25px;
    }
    .form-card {
        background: var(--color-bg-card);
        border-radius: 14px;
        border: 1px solid var(--color-border);
        border-left: 3px solid var(--color-gold);
        padding: 25px 20px;
        opacity: 1;
    }
    .form-card:hover {
        border-color: var(--color-gold);
        box-shadow: 0 6px 20px rgba(0,0,0,0.3);
    }
    .form-card h2 {
        font-size: 18px;
        color: var(--color-gold);
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 1px solid var(--color-border);
    }
    .form-card label {
        display: block;
        font-size: 14px;
        font-weight: 500;
        color: #ccc;
        margin-bottom: 6px;
        margin-top: 14px;
    }
    .form-card label:first-of-type {
        margin-top: 0;
    }
    .form-card input,
    .form-card select {
        width: 100%;
        padding: 12px 14px;
        background: var(--color-bg-dark);
        border: 1px solid var(--color-border);
        color: #fff;
        border-radius: 8px;
        font-size: 15px;
    }
    .form-card input:focus,
    .form-card select:focus {
        outline: none;
        border-color: var(--color-gold);
        box-shadow: 0 0 0 2px rgba(212,175,55,0.15);
    }
    .form-card .note {
        margin-top: 15px;
        font-size: 13px;
        color: var(--color-text-muted);
        line-height: 1.5;
    }
    .form-card .note b {
        color: var(--color-gold);
    }

    /* ✅ SAVE BUTTON — STATIC, NO ANIMATION */
    .btn-wrap {
        grid-column: 1 / -1;
        margin-top: 10px;
    }
    .save-btn {
        width: 100%;
        padding: 15px;
        background: linear-gradient(90deg, var(--color-gold), var(--color-gold-light), var(--color-gold-bright));
        color: #000;
        border: none;
        border-radius: 8px;
        font-size: 16px;
        font-weight: 700;
        cursor: pointer;
        font-family: var(--font-body);
    }
    .save-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(212,175,55,0.35);
    }

    /* ✅ RESPONSIVE — NO TRANSITION */
    @media (max-width: 900px) {
        .hamburger {
            display: flex;
        }
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
        .form-grid {
            grid-template-columns: 1fr;
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
        .form-card {
            padding: 20px 15px;
        }
        .form-card input,
        .form-card select {
            padding: 11px 12px;
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
<!-- ✅ OVERLAY -->
<div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

<!-- ✅ TOP NAVBAR -->
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

<!-- ✅ SIDEBAR — REWARDS REMOVED -->
<div class="main-layout">
    <div class="sidebar" id="sidebar">
        <h2>Dashboard</h2>
        <a href="profile.php" class="active">Profile</a>
        <a href="orders.php">Orders</a>
        <a href="addresses.php">Addresses</a>
        <a href="settings.php">Settings</a>
        <a href="about.php">About</a>
        <a href="../logout.php">Logout</a>
    </div>

    <!-- ✅ MAIN CONTENT -->
    <div class="content">
        <h1>My Profile</h1>
        <p class="subheading">Please complete your information before placing an order.</p>
        <form method="POST" class="form-grid">
            <div class="form-card">
                <h2>Personal Information</h2>
                <label>Full Name</label>
                <input type="text" name="fullname" value="<?php echo htmlspecialchars($user['fullname']); ?>">
                <label>Email</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>">
                <label>Phone Number</label>
                <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>">
                <label>Birthday</label>
                <input type="date" name="birthday" value="<?php echo isset($user['birthday']) ? $user['birthday'] : ''; ?>">
                <label>Gender</label>
                <select name="gender">
                    <option value="">Select Gender</option>
                    <option value="Male" <?php if(isset($user['gender']) && $user['gender']=="Male") echo "selected"; ?>>Male</option>
                    <option value="Female" <?php if(isset($user['gender']) && $user['gender']=="Female") echo "selected"; ?>>Female</option>
                </select>
            </div>

            <div class="form-card">
                <h2>Address</h2>
                <label>Province</label>
                <input type="text" name="province" value="<?php echo htmlspecialchars(isset($user['province']) ? $user['province'] : ''); ?>">
                <label>City</label>
                <input type="text" name="city" value="<?php echo htmlspecialchars(isset($user['city']) ? $user['city'] : ''); ?>">
                <label>Barangay</label>
                <input type="text" name="barangay" value="<?php echo htmlspecialchars(isset($user['barangay']) ? $user['barangay'] : ''); ?>">
                <label>Street</label>
                <input type="text" name="street" value="<?php echo htmlspecialchars(isset($user['street']) ? $user['street'] : ''); ?>">
                <label>Zip Code</label>
                <input type="text" name="zipcode" value="<?php echo htmlspecialchars(isset($user['zipcode']) ? $user['zipcode'] : ''); ?>">
                <p class="note">Address information will also be saved when you click <b>Save Profile</b>.</p>
            </div>

            <div class="btn-wrap">
                <button type="submit" name="save_profile" class="save-btn">Save Profile</button>
            </div>
        </form>
    </div>
</div>

<!-- ✅ JAVASCRIPT -->
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