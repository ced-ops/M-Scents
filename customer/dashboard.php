<?php
session_start();
if(!isset($_SESSION['fullname'])){
    header("Location: ../login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | M&Scents</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700;800&display=swap" rel="stylesheet">
    <style>
    :root {
        --font-heading: 'Playfair Display', serif;
        --font-body: 'Inter', sans-serif;
        --color-gold: #d4af37;
        --color-gold-light: #f0c94d;
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

    /* ✅ HAMBURGER ICON */
    .hamburger {
        display: none;
        flex-direction: column;
        gap: 5px;
        cursor: pointer;
        padding: 6px 8px;
        border-radius: 6px;
        transition: background 0.25s ease;
    }
    .hamburger:hover {
        background: rgba(212,175,55,0.12);
    }
    .hamburger span {
        width: 24px;
        height: 2px;
        background: var(--color-gold);
        border-radius: 2px;
        transition: all 0.3s ease;
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

    /* ✅ TOP NAVBAR */
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
        animation: slideDown 0.5s ease;
    }
    @keyframes slideDown {
        from { transform: translateY(-100%); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
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
        transition: color 0.3s ease, transform 0.25s ease;
    }
    .topnav .nav-links a::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 0;
        height: 2px;
        background: linear-gradient(90deg, var(--color-gold), var(--color-gold-light));
        transition: width 0.35s ease;
    }
    .topnav .nav-links a:hover,
    .topnav .nav-links a.active {
        color: var(--color-gold);
        transform: translateY(-2px);
    }
    .topnav .nav-links a:hover::after,
    .topnav .nav-links a.active::after {
        width: 100%;
    }

    /* ✅ HIDE Dashboard LINK ON MOBILE */
    .nav-links .dashboard-link {
        display: inline-block;
    }
    @media (max-width: 768px) {
        .nav-links .dashboard-link {
            display: none;
        }
    }

    /* ✅ OVERLAY */
    .overlay {
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.6);
        z-index: 998;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
    }
    .overlay.active {
        opacity: 1;
        visibility: visible;
    }

    /* ✅ FLOATING BUTTONS — IMAGE ICONS */
    .floating-cart, .floating-msg {
        position: fixed;
        bottom: 20px;
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--color-gold), var(--color-gold-light));
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        box-shadow: 0 4px 15px rgba(212,175,55,0.35);
        z-index: 900;
        transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }
    .floating-cart { right: 75px; }
    .floating-msg { right: 15px; }

    .floating-cart img, .floating-msg img {
        width: 24px;
        height: 24px;
        object-fit: contain;
    }

    .floating-cart .badge {
        position: absolute;
        top: -5px;
        right: -5px;
        background: #ef4444;
        color: #fff;
        font-size: 11px;
        font-weight: 700;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .floating-cart:hover, .floating-msg:hover {
        transform: scale(1.15) translateY(-3px);
        box-shadow: 0 8px 25px rgba(212,175,55,0.5);
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

    /* ✅ SIDEBAR */
    .sidebar {
        background: var(--color-bg-sidebar);
        border-radius: 12px;
        border: 1px solid var(--color-border);
        padding: 20px 15px;
        height: fit-content;
        position: sticky;
        top: 100px;
        transition: transform 0.35s ease, opacity 0.35s ease;
        animation: fadeInLeft 0.7s ease;
    }
    @keyframes fadeInLeft {
        from { opacity: 0; transform: translateX(-20px); }
        to { opacity: 1; transform: translateX(0); }
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
        transition: all 0.25s ease;
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

    /* ✅ MAIN CONTENT */
    .content {
        animation: fadeInUp 0.7s ease;
    }
    .content h1 {
        font-size: clamp(28px, 5vw, 36px);
        color: #fff;
        margin-bottom: 8px;
    }
    .subheading {
        color: var(--color-gold);
        font-size: 16px;
        margin-bottom: 35px;
    }

    /* ✅ CARDS GRID */
    .cards-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 24px;
    }
    .dashboard-card {
        background: var(--color-bg-card);
        border: 1px solid var(--color-border);
        border-left: 3px solid var(--color-gold);
        border-radius: 14px;
        padding: 28px 24px;
        text-decoration: none;
        color: inherit;
        transition: all 0.35s ease;
        display: block;
        opacity: 0;
        animation: cardFadeIn 0.6s ease forwards;
    }
    .dashboard-card:nth-child(1) { animation-delay: 0.1s; }
    .dashboard-card:nth-child(2) { animation-delay: 0.2s; }
    .dashboard-card:nth-child(3) { animation-delay: 0.3s; }
    .dashboard-card:nth-child(4) { animation-delay: 0.4s; }

    @keyframes cardFadeIn {
        from { opacity: 0; transform: translateY(25px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .dashboard-card:hover {
        transform: translateY(-6px);
        border-color: var(--color-gold);
        box-shadow: 0 15px 35px rgba(0,0,0,0.45);
    }
    .dashboard-card h3 {
        font-size: 18px;
        color: var(--color-gold);
        margin-bottom: 10px;
    }
    .dashboard-card p {
        color: #aaa;
        font-size: 14px;
        line-height: 1.6;
    }

    /* ✅ BASE ANIMATION */
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(25px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* ✅ RESPONSIVE */
    @media (max-width: 900px) {
        .hamburger {
            display: flex;
        }
        .main-layout {
            grid-template-columns: 1fr;
            gap: 25px;
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
        .cards-grid {
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
        .dashboard-card {
            padding: 22px 18px;
        }
    }
    </style>
</head>
<body>

<!-- ✅ OVERLAY -->
<div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

<!-- ✅ TOP NAVBAR — Hides Dashboard on Mobile -->
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
        <a href="dashboard.php" class="active dashboard-link">Dashboard</a>
    </div>
</div>

<!-- ✅ FLOATING BUTTONS — IMAGE ICONS -->
<a href="../cart.php" class="floating-cart" title="Your Cart">
    <img src="../images/cart.png" alt="Cart">
    <?php if(isset($_SESSION['cart']) && count($_SESSION['cart'])>0): ?>
        <span class="badge"><?php echo count($_SESSION['cart']); ?></span>
    <?php endif; ?>
</a>
<a href="messages.php" class="floating-msg" title="Message Admin">
    <img src="../images/message.png" alt="Messages">
</a>

<!-- ✅ SIDEBAR -->
<div class="main-layout">
    <div class="sidebar" id="sidebar">
        <h2>Dashboard</h2>
        <a href="profile.php">Profile</a>
        <a href="orders.php">Orders</a>
        <a href="rewards.php">Rewards</a>
        <a href="addresses.php">Addresses</a>
        <a href="settings.php">Settings</a>
        <a href="about.php">About</a>
        <a href="../logout.php">Logout</a>
    </div>

    <!-- ✅ MAIN CONTENT -->
    <div class="content">
        <h1>Welcome Back</h1>
        <p class="subheading"><?php echo $_SESSION['fullname']; ?></p>
        <div class="cards-grid">
            <a href="profile.php" class="dashboard-card">
                <h3>Profile</h3>
                <p>Complete your personal information before placing an order.</p>
            </a>
            <a href="orders.php" class="dashboard-card">
                <h3>Orders</h3>
                <p>Track your pending, completed and cancelled orders.</p>
            </a>
            <a href="rewards.php" class="dashboard-card">
                <h3>Rewards</h3>
                <p>Buy 5 bottles and receive a FREE perfume.</p>
            </a>
            <a href="settings.php" class="dashboard-card">
                <h3>Settings</h3>
                <p>Manage your account settings.</p>
            </a>
        </div>
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