<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}
include("../config.php");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About M&Scents</title>
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
        background: 
            radial-gradient(circle at 10% 0%, rgba(212,175,55,0.08) 0%, transparent 40%),
            radial-gradient(circle at 90% 10%, rgba(240,201,77,0.06) 0%, transparent 35%),
            radial-gradient(circle at 50% 90%, rgba(212,175,55,0.05) 0%, transparent 40%),
            var(--color-bg-dark);
        background-attachment: fixed;
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

    .about-card {
        background: linear-gradient(135deg, rgba(45,38,20,0.9), rgba(26,26,26,0.95));
        border: 1px solid rgba(212,175,55,0.2);
        border-radius: 16px;
        padding: 30px 25px;
        margin-bottom: 30px;
    }
    .about-card h2 {
        font-size: 18px;
        color: var(--color-gold);
        margin: 24px 0 10px;
    }
    .about-card h2:first-child {
        margin-top: 0;
    }
    .about-card p {
        color: #e0d8b8;
        font-size: 14px;
        line-height: 1.8;
    }

    .social-section {
        margin-top: 40px;
    }
    .social-section h2 {
        font-size: 18px;
        color: var(--color-gold);
        margin-bottom: 25px;
        text-align: center;
    }
    .qr-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
    }
    .qr-grid.rated-grid {
        grid-template-columns: repeat(2, 1fr);
        max-width: 400px;
        margin: 0 auto;
    }
    .qr-card {
        background: var(--color-bg-card);
        border: 2px solid transparent;
        border-image: linear-gradient(135deg, var(--color-gold), var(--color-gold-light), var(--color-gold)) 1;
        border-radius: 14px;
        padding: 18px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    .qr-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(212,175,55,0.25);
    }
    .qr-card img {
        width: 100%;
        max-width: 130px;
        height: auto;
        border-radius: 10px;
        margin-bottom: 12px;
        border: 3px solid var(--color-gold);
        box-shadow: 0 0 0 4px rgba(212,175,55,0.15);
    }
    .qr-card h3 {
        font-size: 15px;
        color: var(--color-gold);
        margin-bottom: 4px;
    }
    .qr-card p {
        font-size: 13px;
        color: var(--color-text-muted);
    }

    .modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.85);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 2000;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
    }
    .modal-overlay.active {
        opacity: 1;
        visibility: visible;
    }
    .modal-box {
        background: var(--color-bg-card);
        border: 1px solid var(--color-gold);
        border-radius: 14px;
        padding: 28px 22px;
        max-width: 420px;
        width: 90%;
        text-align: center;
        transform: translateY(20px);
        transition: transform 0.3s ease;
    }
    .modal-overlay.active .modal-box {
        transform: translateY(0);
    }
    .modal-box h3 {
        font-size: 17px;
        color: var(--color-gold);
        margin-bottom: 10px;
    }
    .modal-box p {
        color: #ccc;
        font-size: 14px;
        margin-bottom: 15px;
    }
    .modal-box a {
        color: var(--color-gold-light);
        word-break: break-all;
        font-size: 13px;
        display: inline-block;
        margin-bottom: 20px;
    }
    .modal-close {
        padding: 10px 25px;
        background: linear-gradient(135deg, var(--color-gold), var(--color-gold-light));
        color: #000;
        border: none;
        border-radius: 6px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    .modal-close:hover {
        transform: translateY(-2px);
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
        .qr-grid {
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
        }
        .qr-grid.rated-grid {
            grid-template-columns: repeat(2, 1fr);
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
        .about-card {
            padding: 22px 16px;
        }
        .qr-grid {
            grid-template-columns: 1fr;
            gap: 20px;
        }
        .qr-grid.rated-grid {
            grid-template-columns: 1fr;
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
<div class="modal-overlay" id="qrModal" onclick="closeModalOnOverlay(event)">
    <div class="modal-box">
        <h3 id="modalTitle"></h3>
        <p>Click the link below to visit our page:</p>
        <a id="modalLink" href="" target="_blank"></a>
        <br>
        <button class="modal-close" onclick="closeModal()">Close</button>
    </div>
</div>
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
        <a href="settings.php">Settings</a>
        <a href="about.php" class="active">About</a>
        <a href="../logout.php">Logout</a>
    </div>
    <div class="content">
        <h1>About</h1>
        <p class="subheading">Learn more about our brand, our story, and what we stand for</p>
        <div class="about-card">
            <h2>Luxury Inspired Perfumes</h2>
            <p>M&Scents offers premium inspired fragrances crafted for those who appreciate elegance, sophistication, and long-lasting scents. We believe that everyone deserves to experience the allure of luxury without the extravagant price tag. Every bottle is carefully curated to capture the essence of the world's most beloved fragrances — allowing you to wear your favorite scent with confidence and pride.</p>
            <h2>Our Mission</h2>
            <p>To provide affordable luxury perfumes with exceptional quality and unmatched customer satisfaction. We are dedicated to crafting fragrances that not only smell exquisite but also tell a story — your story. We strive to make every purchase a delightful experience, from the moment you choose your scent until the very last drop. Quality, integrity, and genuine care for our customers are at the heart of everything we do.</p>
            <h2>Our Vision</h2>
            <p>To become one of the leading local perfume brands trusted by fragrance lovers across the Philippines. We envision M&Scents as the go-to destination for anyone seeking beautiful, long-lasting, and inspired fragrances — proudly crafted here at home. Through passion, creativity, and an unwavering commitment to excellence, we aim to bring the beauty of fine fragrance into every Filipino home, one bottle at a time.</p>
        </div>
        <div class="social-section">
            <h2>Connect With Us</h2>
            <div class="qr-grid">
                <div class="qr-card" onclick="openModal('Facebook — M&Scents', 'https://www.facebook.com/profile.php?id=61568506688813')">
                    <img src="../images/QR facebook.png" alt="Facebook QR">
                    <h3>Facebook</h3>
                    <p>M&Scents</p>
                </div>
                <div class="qr-card" onclick="openModal('Instagram — M&Scents', 'https://www.instagram.com/marco.jear/')">
                    <img src="../images/QR instagram.png" alt="Instagram QR">
                    <h3>Instagram</h3>
                    <p>@marco.jear</p>
                </div>
                <div class="qr-card" onclick="openModal('TikTok — M&Scents', 'https://www.tiktok.com/@mscents33')">
                    <img src="../images/QR tiktok.png" alt="TikTok QR">
                    <h3>TikTok</h3>
                    <p>@mscents33</p>
                </div>
            </div>
        </div>
        <div class="social-section">
            <h2>Rated Us!</h2>
            <div class="qr-grid rated-grid">
                <div class="qr-card" onclick="openModal('Questionnaire — M&Scents System', 'https://docs.google.com/forms/d/e/1FAIpQLSd-Z257CWBolERZnO8j5A6f0-FlqsHRDP_W_pHTccYxmFZGDg/viewform')">
                    <img src="../images/rateus1.png" alt="Questionnaire QR">
                    <h3>Questionnaire</h3>
                    <p>System Survey</p>
                </div>
                <div class="qr-card" onclick="openModal('Evaluation — M&Scents System', 'https://docs.google.com/forms/d/12lw-UmeeTY20CviPOxEDgHygyTCJsIinx6SucpCdbV0/preview?edit_requested=true')">
                    <img src="../images/rateus2.png" alt="Evaluation QR">
                    <h3>Evaluation</h3>
                    <p>Rate Our System</p>
                </div>
            </div>
        </div>
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
function openModal(title, url) {
    document.getElementById('modalTitle').textContent = title;
    document.getElementById('modalLink').href = url;
    document.getElementById('modalLink').textContent = url;
    document.getElementById('qrModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}
function closeModal() {
    document.getElementById('qrModal').classList.remove('active');
    document.body.style.overflow = '';
}
function closeModalOnOverlay(e) {
    if (e.target.id === 'qrModal') {
        closeModal();
    }
}
</script>
</body>
</html>