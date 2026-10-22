<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}
include("../config.php");
$user_id = $_SESSION['user_id'];
// Get customer name from session or database
if(!isset($_SESSION['fullname'])) {
    $u = mysqli_fetch_assoc(mysqli_query($conn, "SELECT fullname FROM users WHERE id='$user_id'"));
    $_SESSION['fullname'] = $u['fullname'];
}
$customer_name = $_SESSION['fullname'];
// Count delivered orders for rewards
$delivered = mysqli_query($conn, "SELECT DISTINCT id FROM orders WHERE customer_name='$customer_name' AND status='Delivered'");
$total_delivered = mysqli_num_rows($delivered);
// Count redeemed rewards
$used = mysqli_query($conn, "SELECT * FROM rewards_log WHERE customer_name='$customer_name' AND is_redeemed=1");
$total_used = mysqli_num_rows($used);
// Calculate valid count (delivered minus redeemed)
$valid_count = $total_delivered - $total_used;
// Progress toward next reward
$remaining = 5 - ($valid_count % 5);
$is_eligible = ($valid_count >= 5);
// Claim Reward — Free Perfume
if(isset($_POST['claim_reward']) && $is_eligible){
    $insert = mysqli_query($conn, "INSERT INTO rewards_log (customer_name, order_id, is_redeemed) VALUES ('$customer_name', 0, 1)");
    if($insert){
        echo "<script>alert('Congratulations! You have claimed your FREE perfume! Your count has been reset. Thank you!'); location='rewards.php';</script>";
        exit();
    }
}
// Retrieve delivered orders history
$history = [];
$res = mysqli_query($conn, "SELECT * FROM orders WHERE customer_name='$customer_name' AND status='Delivered' ORDER BY order_date DESC");
while($r = mysqli_fetch_assoc($res)) $history[] = $r;

// ✅ UPDATED: Smaller circle calculations
$progress_percent = min(100, ($valid_count / 5) * 100);
$radius = 48;
$circumference = 2 * pi() * $radius;
$dash_offset = $circumference - ($progress_percent / 100) * $circumference;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rewards Program | M&Scents</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700;800&display=swap" rel="stylesheet">
    <style>
    :root {
        --font-heading: 'Playfair Display', serif;
        --font-body: 'Inter', sans-serif;
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

    /* ✅ GLOBAL ANIMATIONS */
    @keyframes gradientFlow {
        0% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }
    @keyframes glowPulse {
        0% { box-shadow: 0 0 5px var(--color-gold), 0 0 10px rgba(212,175,55,0.2); }
        50% { box-shadow: 0 0 20px var(--color-gold), 0 0 35px rgba(212,175,55,0.4); }
        100% { box-shadow: 0 0 5px var(--color-gold), 0 0 10px rgba(212,175,55,0.2); }
    }
    @keyframes borderGlow {
        0% { border-color: rgba(212,175,55,0.3); }
        50% { border-color: rgba(240,201,77,0.6); }
        100% { border-color: rgba(212,175,55,0.3); }
    }
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    @keyframes slideDown {
        from { transform: translateY(-100%); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
    @keyframes scaleIn {
        0% { transform: scale(0.8); opacity: 0; }
        100% { transform: scale(1); opacity: 1; }
    }

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
        background: linear-gradient(90deg, var(--color-gold), var(--color-gold-light));
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

    /* ✅ TOP NAVBAR — UNIFORM + RESPONSIVE + ANIMATED */
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
        animation: slideDown 0.6s ease;
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
        background: linear-gradient(90deg, var(--color-gold), var(--color-gold-light), var(--color-gold-bright), var(--color-gold));
        background-size: 300% 100%;
        -webkit-background-clip: text;
        background-clip: text;
        -webkit-text-fill-color: transparent;
        animation: gradientFlow 4s ease infinite;
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
        background: linear-gradient(90deg, var(--color-gold), var(--color-gold-light), var(--color-gold-bright));
        background-size: 200% 100%;
        transition: width 0.4s ease;
        animation: gradientFlow 3s linear infinite;
    }
    .topnav .nav-links a:hover,
    .topnav .nav-links a.active {
        color: var(--color-gold);
        transform: translateY(-2px);
        text-shadow: 0 0 8px rgba(212,175,55,0.4);
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

    /* ✅ MAIN LAYOUT */
    .main-layout {
        display: grid;
        grid-template-columns: 220px 1fr;
        gap: 25px;
        padding: 100px 20px 60px;
        max-width: 1100px;
        margin: 0 auto;
    }

    /* ✅ SIDEBAR — NO EMOJI + GLOW */
    .sidebar {
        background: var(--color-bg-sidebar);
        border-radius: 12px;
        border: 1px solid var(--color-border);
        padding: 20px 15px;
        height: fit-content;
        position: sticky;
        top: 100px;
        transition: transform 0.35s ease, opacity 0.35s ease, border-color 0.6s ease;
        animation: fadeInLeft 0.7s ease, borderGlow 3s ease-in-out infinite;
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
        box-shadow: inset 4px 0 15px rgba(212,175,55,0.15);
    }

    /* ✅ MAIN CONTENT */
    .content {
        animation: fadeInUp 0.7s ease;
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

    /* ✅ REWARDS CARD */
    .rewards-card {
        background: linear-gradient(135deg, rgba(45,38,20,0.9), rgba(26,26,26,0.95));
        border: 1px solid var(--color-border);
        border-radius: 16px;
        padding: 30px 20px;
        margin-bottom: 25px;
        animation: borderGlow 3s ease-in-out infinite;
    }
    .rewards-card h2 {
        font-family: var(--font-heading);
        font-size: 22px;
        color: var(--color-gold);
        margin-bottom: 8px;
    }
    .rewards-card > p {
        color: #b8a878;
        margin-bottom: 25px;
        font-size: 14px;
    }

    /* ✅ CIRCULAR PROGRESS — RESPONSIVE FIXED ✅ */
    .progress-container {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 20px;
        flex-wrap: wrap; /* ✅ AUTOMATICALLY PUMAPATONG SA IBABA SA PHONE */
        margin-bottom: 25px;
        width: 100%;
    }
    .circle-wrap {
        position: relative;
        width: 120px; /* ✅ PINALIIT NA PARA KASYA SA PHONE */
        height: 120px;
        flex-shrink: 0;
        animation: scaleIn 1s ease forwards;
    }
    .circle-bg {
        fill: none;
        stroke: rgba(212,175,55,0.1);
        stroke-width: 8;
    }
    .circle-progress {
        fill: none;
        stroke: url(#gold-gradient);
        stroke-width: 8;
        stroke-linecap: round;
        stroke-dasharray: <?php echo round($circumference, 2); ?>;
        stroke-dashoffset: <?php echo round($dash_offset, 2); ?>;
        transform: rotate(-90deg);
        transform-origin: 50% 50%;
        transition: stroke-dashoffset 1.5s ease-in-out;
        filter: drop-shadow(0 0 6px rgba(212,175,55,0.4));
    }
    .circle-inner {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        text-align: center;
    }
    .circle-count {
        font-family: var(--font-heading);
        font-size: 36px;
        font-weight: 700;
        color: var(--color-gold);
        line-height: 1;
        text-shadow: 0 0 12px rgba(212,175,55,0.3);
    }
    .circle-label {
        font-size: 12px;
        color: #b8a878;
        margin-top: 4px;
    }
    .progress-text {
        flex: 1;
        min-width: 100%; /* ✅ SA PHONE — BUONG LAPAD AGAD, NASA ILALIM NG BILOG */
        text-align: center; /* ✅ GITNA SA PHONE PARA MALINIS */
    }
    .progress-text h3 {
        font-size: 18px;
        color: var(--color-gold);
        margin-bottom: 6px;
    }
    .progress-text p {
        color: #b8a878;
        font-size: 14px;
        line-height: 1.5;
    }
    .remaining-badge {
        display: inline-block;
        margin-top: 10px;
        padding: 7px 14px;
        background: linear-gradient(90deg, rgba(212,175,55,0.15), rgba(240,201,77,0.08));
        border: 1px solid rgba(212,175,55,0.25);
        border-radius: 30px;
        color: var(--color-gold);
        font-weight: 600;
        font-size: 13px;
    }

    /* ✅ SA LAPAD NA SCREEN — MAGKATABI ULIT */
    @media (min-width: 520px) {
        .progress-text {
            min-width: auto;
            text-align: left;
        }
    }

    /* ✅ ELIGIBILITY BOX */
    .eligible-box {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 15px;
        padding: 16px;
        background: rgba(74,222,128,0.05);
        border: 1px solid rgba(74,222,128,0.2);
        border-radius: 12px;
        flex-wrap: wrap;
        margin-top: 5px;
    }
    .eligible-box h3 {
        font-size: 16px;
        color: var(--color-success);
        margin-bottom: 4px;
    }
    .eligible-box p {
        font-size: 13px;
        color: #b8f0c8;
    }
    .btn-claim {
        padding: 10px 20px;
        background: linear-gradient(90deg, var(--color-success), #86efac, var(--color-success));
        background-size: 200% 100%;
        color: #000;
        border: none;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 700;
        cursor: pointer;
        white-space: nowrap;
        transition: all 0.3s ease;
        animation: gradientFlow 3s linear infinite, glowPulse 2.5s ease-in-out infinite;
    }
    .btn-claim:hover {
        transform: translateY(-2px) scale(1.03);
        box-shadow: 0 8px 25px rgba(74,222,128,0.35);
    }
    .btn-disabled {
        background: linear-gradient(90deg, #2a2a2a, #333, #2a2a2a);
        background-size: 200% 100%;
        color: #777;
        cursor: not-allowed;
        transform: none;
        animation: none;
        box-shadow: none;
    }
    .btn-disabled:hover {
        transform: none;
        box-shadow: none;
    }

    /* ✅ HISTORY BOX + TABLE */
    .history-box {
        background: var(--color-bg-card);
        border: 1px solid var(--color-border);
        border-radius: 14px;
        padding: 20px;
        animation: borderGlow 3s ease-in-out infinite;
    }
    .history-box h3 {
        font-family: var(--font-heading);
        font-size: 17px;
        color: var(--color-gold);
        margin-bottom: 6px;
    }
    .history-desc {
        color: var(--color-text-muted);
        font-size: 13px;
        margin-bottom: 15px;
    }
    /* ✅ TABLE — SCROLLABLE SA PHONE! */
    .table-wrap {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        min-width: 560px;
    }
    table th,
    table td {
        padding: 10px 8px;
        text-align: left;
        border-bottom: 1px solid var(--color-border);
        font-size: 13px;
    }
    table th {
        color: var(--color-gold);
        font-weight: 600;
        background: rgba(212,175,55,0.05);
        white-space: nowrap;
    }
    table td {
        color: #ccc;
    }
    .status-delivered {
        color: var(--color-success);
        font-weight: 500;
    }
    .empty-history {
        text-align: center;
        padding: 30px 15px;
        color: var(--color-text-muted);
    }
    .empty-history p {
        margin-bottom: 6px;
        font-size: 14px;
    }

    /* ✅ RESPONSIVE */
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
    }
    @media (max-width: 600px) {
        .topnav {
            padding: 10px 15px;
        }
        .topnav .nav-links a {
            margin-left: 10px;
            font-size: 13px;
        }
        .rewards-card,
        .history-box {
            padding: 18px 15px;
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
        .eligible-box {
            flex-direction: column;
            text-align: center;
        }
    }
    </style>
</head>
<body>

<!-- ✅ OVERLAY -->
<div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

<!-- ✅ TOP NAVBAR — UNIFORM + RESPONSIVE + ANIMATED -->
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

<!-- ✅ SIDEBAR — NO EMOJI -->
<div class="main-layout">
    <div class="sidebar" id="sidebar">
        <h2>Dashboard</h2>
        <a href="profile.php">Profile</a>
        <a href="orders.php">Orders</a>
        <a href="rewards.php" class="active">Rewards</a>
        <a href="addresses.php">Addresses</a>
        <a href="settings.php">Settings</a>
        <a href="about.php">About</a>
        <a href="../logout.php">Logout</a>
    </div>

    <!-- ✅ MAIN CONTENT — REWARDS SECTION -->
    <div class="content">
        <h1>Rewards Program</h1>
        <p class="subheading">Buy 5 Bottles — Get 1 FREE Perfume</p>

        <!-- Hidden SVG Gradient Definition -->
        <svg width="0" height="0">
            <defs>
                <linearGradient id="gold-gradient" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" stop-color="#d4af37" />
                    <stop offset="50%" stop-color="#f0c94d" />
                    <stop offset="100%" stop-color="#ffd700" />
                </linearGradient>
            </defs>
        </svg>

        <!-- ✅ REWARDS PROGRESS CARD — CIRCULAR PROGRESS ✅ FIXED -->
        <div class="rewards-card">
            <h2>Your Progress</h2>
            <p>Every delivered order brings you closer to a FREE perfume.</p>

            <div class="progress-container">
                <!-- ✅ Circular Progress — SMALLER SIZE -->
                <div class="circle-wrap">
                    <svg width="120" height="120" viewBox="0 0 120 120">
                        <circle class="circle-bg" cx="60" cy="60" r="48" />
                        <circle class="circle-progress" cx="60" cy="60" r="48" />
                    </svg>
                    <div class="circle-inner">
                        <div class="circle-count"><?php echo $valid_count; ?>/5</div>
                        <div class="circle-label">Orders</div>
                    </div>
                </div>

                <!-- ✅ Progress Text — PUMAPATONG SA ILALIM SA PHONE -->
                <div class="progress-text">
                    <?php if($is_eligible): ?>
                        <h3>Congratulations!</h3>
                        <p>You have reached <strong>5 orders</strong>! Claim your FREE perfume now.</p>
                    <?php else: ?>
                        <h3>Keep Shopping</h3>
                        <p>You need <strong><?php echo $remaining; ?> more delivered order<?php echo $remaining>1?'s':''; ?></strong> to earn your FREE perfume.</p>
                        <div class="remaining-badge"><?php echo $remaining; ?> to go</div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if($is_eligible): ?>
            <div class="eligible-box">
                <div>
                    <h3>Congratulations — You Qualify!</h3>
                    <p>You have earned a FREE perfume! Click below to claim your reward.</p>
                </div>
                <form method="post">
                    <button type="submit" name="claim_reward" class="btn-claim">Claim Now</button>
                </form>
            </div>
            <?php else: ?>
            <div class="eligible-box" style="background:rgba(212,175,55,0.06); border-color:rgba(212,175,55,0.2);">
                <div>
                    <h3 style="color:var(--color-gold);">Almost There</h3>
                    <p style="color:#e5c869;">Complete <?php echo $remaining; ?> more order<?php echo $remaining>1?'s':''; ?> and get one FREE!</p>
                </div>
                <button class="btn-claim btn-disabled" disabled>Not Yet Eligible</button>
            </div>
            <?php endif; ?>
        </div>

        <!-- ✅ HISTORY BOX -->
        <div class="history-box">
            <h3>Qualified Orders History</h3>
            <p class="history-desc">Only orders marked as <strong>Delivered</strong> count toward your rewards.</p>
            <?php if(!empty($history)): ?>
            <div class="table-wrap">
                <table>
                    <tr>
                        <th>Order Date</th>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Status</th>
                    </tr>
                    <?php foreach($history as $h): ?>
                    <tr>
                        <td><?php echo date('F j, Y — g:i A', strtotime($h['order_date'])); ?></td>
                        <td><?php echo htmlspecialchars($h['product_name']); ?></td>
                        <td><?php echo $h['quantity']; ?></td>
                        <td class="status-delivered">Delivered</td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
            <?php else: ?>
            <div class="empty-history">
                <p>No delivered orders yet.</p>
                <p>Once your orders are delivered, they will appear here and count toward your rewards.</p>
            </div>
            <?php endif; ?>
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