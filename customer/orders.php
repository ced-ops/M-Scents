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

$result = mysqli_query($conn, "
    SELECT o.*, ca.contact_number AS ca_contact_number, p.image AS product_image 
    FROM orders o
    LEFT JOIN customer_addresses ca ON o.customer_name = ca.customer_name
    LEFT JOIN products p ON o.product_id = p.id
    WHERE o.customer_name = '$customer_name' 
    ORDER BY 
        CASE o.status 
            WHEN 'Pending' THEN 1 
            WHEN 'Confirmed' THEN 2 
            WHEN 'Preparing' THEN 3 
            WHEN 'Shipped' THEN 4 
            WHEN 'Delivered' THEN 5 
            ELSE 6 
        END ASC,
        o.order_date DESC
");

$orders_by_status = [
    'Pending' => [],
    'Confirmed' => [],
    'Preparing' => [],
    'Shipped' => [],
    'Delivered' => []
];

while ($o = mysqli_fetch_assoc($result)) {
    $status = $o['status'];
    if (isset($orders_by_status[$status])) {
        $orders_by_status[$status][] = $o;
    }
}

$has_any_orders = false;
foreach ($orders_by_status as $list) {
    if (!empty($list)) { $has_any_orders = true; break; }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders | M&Scents</title>
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
        --color-pending: #facc15;
        --color-confirmed: #60a5fa;
        --color-preparing: #a78bfa;
        --color-shipped: #34d399;
        --color-delivered: #4ade80;
        --color-danger: #ef4444;
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
        padding-left: 16px;
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

    .status-section {
        margin-bottom: 15px;
        border: 1px solid var(--color-border);
        border-radius: 10px;
        background: var(--color-bg-card);
        overflow: hidden;
    }
    .status-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 14px 18px;
        cursor: pointer;
        user-select: none;
    }
    .status-header:hover {
        background: rgba(212,175,55,0.04);
    }
    .status-header-left {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }
    .status-header h2 {
        font-size: 16px;
        font-weight: 600;
    }
    .status-badge {
        font-size: 12px;
        padding: 3px 10px;
        border-radius: 20px;
        font-weight: 600;
        white-space: nowrap;
    }
    .toggle-icon {
        font-size: 18px;
        opacity: 0.7;
        flex-shrink: 0;
    }
    .status-section.open .toggle-icon {
        transform: rotate(180deg);
    }

    .status-pending .status-header h2 { color: var(--color-pending); }
    .status-pending .status-badge { background: rgba(250,173,20,0.15); color: var(--color-pending); }
    .status-confirmed .status-header h2 { color: var(--color-confirmed); }
    .status-confirmed .status-badge { background: rgba(96,165,250,0.15); color: var(--color-confirmed); }
    .status-preparing .status-header h2 { color: var(--color-preparing); }
    .status-preparing .status-badge { background: rgba(167,139,250,0.15); color: var(--color-preparing); }
    .status-shipped .status-header h2 { color: var(--color-shipped); }
    .status-shipped .status-badge { background: rgba(52,211,153,0.15); color: var(--color-shipped); }
    .status-delivered .status-header h2 { color: var(--color-delivered); }
    .status-delivered .status-badge { background: rgba(74,222,128,0.15); color: var(--color-delivered); }

    .orders-wrapper {
        max-height: 0;
        overflow: hidden;
    }
    .status-section.open .orders-wrapper {
        max-height: 3000px;
    }
    .orders-inner {
        padding: 0 18px 18px;
        border-top: 1px solid var(--color-border);
    }

    .order-card {
        background: var(--color-bg-dark);
        border: 1px solid var(--color-border);
        border-radius: 10px;
        padding: 16px;
        margin-top: 15px;
        border-left: 3px solid var(--color-gold);
    }
    .order-card:hover {
        border-color: var(--color-gold-light);
        box-shadow: 0 4px 15px rgba(0,0,0,0.3), 0 0 12px rgba(212,175,55,0.08);
    }
    .order-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 15px;
        padding-bottom: 12px;
        border-bottom: 1px solid var(--color-border);
    }
    .order-id {
        font-weight: 600;
        font-size: 15px;
        color: var(--color-gold);
    }
    .order-date {
        font-size: 13px;
        color: var(--color-text-muted);
    }

    .order-product-row {
        display: flex;
        align-items: flex-start;
        gap: 15px;
        margin-bottom: 15px;
        flex-wrap: wrap;
    }
    .product-img-box {
        width: 80px;
        height: 80px;
        border-radius: 8px;
        overflow: hidden;
        border: 1px solid var(--color-border);
        background: #1a1a1a;
        flex-shrink: 0;
    }
    .product-img-box img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .product-img-placeholder {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #555;
        font-size: 11px;
        text-align: center;
    }
    .order-grid {
        flex: 1;
        display: grid;
        grid-template-columns: repeat(3, minmax(100px, 1fr));
        gap: 10px;
        min-width: 0;
    }
    .label {
        font-size: 12px;
        color: var(--color-text-muted);
        margin-bottom: 3px;
    }
    .value {
        font-weight: 500;
        color: var(--color-text);
        font-size: 14px;
        word-break: break-word;
    }
    .product-name {
        font-weight: 600;
        color: var(--color-gold);
        font-size: 15px;
        margin-bottom: 4px;
    }
    .status-text-pending { color: var(--color-pending); font-weight: 600; }
    .status-text-confirmed { color: var(--color-confirmed); font-weight: 600; }
    .status-text-preparing { color: var(--color-preparing); font-weight: 600; }
    .status-text-shipped { color: var(--color-shipped); font-weight: 600; }
    .status-text-delivered { color: var(--color-delivered); font-weight: 600; }
    .payment-paid { color: var(--color-delivered); font-weight: 600; }
    .payment-unpaid { color: var(--color-danger); font-weight: 600; }

    .address {
        margin-top: 0;
        padding: 14px 16px;
        background: rgba(255,255,255,0.02);
        border-radius: 6px;
        font-size: 14px;
        color: #ccc;
        line-height: 1.6;
        border: 1px solid rgba(255,255,255,0.05);
    }
    .address p { margin: 4px 0; }
    .address .contact-line { color: #fff; font-weight: 500; }
    .cancel-btn {
        display: inline-block;
        margin-top: 15px;
        padding: 10px 18px;
        background: rgba(239,68,68,0.1);
        color: #ef4444;
        text-decoration: none;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 500;
        border: none;
        cursor: pointer;
    }
    .cancel-btn:hover {
        background: rgba(239,68,68,0.2);
    }

    .rating-box {
        margin-top: 18px;
        padding: 18px;
        background: rgba(212,175,55,0.05);
        border-radius: 8px;
        border: 1px solid rgba(212,175,55,0.15);
    }
    .rating-box h4 {
        color: var(--color-gold);
        margin-bottom: 12px;
        font-size: 15px;
    }
    .rating-box label {
        display: block;
        font-size: 14px;
        color: #aaa;
        margin-bottom: 6px;
        margin-top: 10px;
    }
    .rating-box select,
    .rating-box textarea {
        width: 100%;
        padding: 10px 12px;
        background: var(--color-bg-dark);
        border: 1px solid var(--color-border);
        color: #fff;
        border-radius: 6px;
        font-size: 14px;
    }
    .rating-box button {
        margin-top: 12px;
        padding: 10px 22px;
        background: linear-gradient(90deg, var(--color-gold), var(--color-gold-light));
        color: #000;
        border: none;
        border-radius: 6px;
        font-weight: 600;
        cursor: pointer;
    }
    .rating-box button:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 18px rgba(212,175,55,0.25);
    }
    .rated-box {
        margin-top: 15px;
        padding: 15px;
        background: rgba(74,222,128,0.05);
        border-radius: 8px;
        border: 1px solid rgba(74,222,128,0.15);
    }
    .rated-box strong {
        color: var(--color-delivered);
        font-size: 14px;
    }
    .rated-stars {
        color: var(--color-pending);
        font-size: 16px;
        margin-left: 6px;
    }
    .review-text {
        margin-top: 8px;
        color: #ccc;
        font-style: italic;
        font-size: 14px;
        line-height: 1.6;
    }
    .empty {
        text-align: center;
        padding: 60px 20px;
        color: var(--color-text-muted);
    }
    .empty h2 {
        color: var(--color-text);
        margin-bottom: 10px;
        font-size: 22px;
    }
    .empty p {
        margin-bottom: 20px;
    }
    .empty a {
        display: inline-block;
        padding: 10px 25px;
        background: linear-gradient(90deg, var(--color-gold), var(--color-gold-light));
        color: #000;
        border-radius: 6px;
        text-decoration: none;
        font-weight: 600;
    }
    .empty a:hover {
        transform: translateY(-2px);
    }
    .no-orders-here {
        padding: 15px 0;
        color: #555;
        font-size: 14px;
        font-style: italic;
        text-align: center;
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
        .order-product-row {
            flex-direction: column;
            align-items: center;
            text-align: center;
        }
        .product-img-box {
            width: 100px;
            height: 100px;
        }
        .order-grid {
            width: 100%;
            grid-template-columns: 1fr 1fr;
            text-align: left;
        }
        .order-card { padding: 15px; }
        .address { font-size: 13px; }
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
        .order-grid {
            grid-template-columns: 1fr;
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
        <a href="orders.php" class="active">Orders</a>
        <a href="addresses.php">Addresses</a>
        <a href="settings.php">Settings</a>
        <a href="about.php">About</a>
        <a href="../logout.php">Logout</a>
    </div>
    <div class="content">
        <h1>Orders</h1>
        <p class="subheading">Click each status to view your orders</p>
        <?php if (!$has_any_orders): ?>
            <div class="empty">
                <h2>You have no orders yet</h2>
                <p>Browse our collection and place your first order!</p>
                <a href="../collection.php">Browse Collection</a>
            </div>
        <?php else: ?>
            <?php
            $status_labels = [
                'Pending'    => ['label' => 'Pending',    'badge' => 'Waiting for Confirmation'],
                'Confirmed'  => ['label' => 'Confirmed',  'badge' => 'Order Confirmed'],
                'Preparing'  => ['label' => 'Preparing',  'badge' => 'Being Prepared'],
                'Shipped'    => ['label' => 'Shipped',    'badge' => 'On the Way'],
                'Delivered'  => ['label' => 'Delivered',  'badge' => 'Order Arrived']
            ];
            $status_order = ['Pending', 'Confirmed', 'Preparing', 'Shipped', 'Delivered'];
            $order_counter = 1;
            ?>
            <?php foreach ($status_order as $current_status): ?>
                <?php $list = $orders_by_status[$current_status]; ?>
                <div class="status-section status-<?= strtolower($current_status) ?>" id="section-<?= strtolower($current_status) ?>">
                    <div class="status-header" onclick="toggleSection('<?= strtolower($current_status) ?>')">
                        <div class="status-header-left">
                            <h2><?= $status_labels[$current_status]['label'] ?></h2>
                            <span class="status-badge"><?= count($list) ?> order<?= count($list) !== 1 ? 's' : '' ?></span>
                        </div>
                        <span class="toggle-icon">▼</span>
                    </div>
                    <div class="orders-wrapper">
                        <div class="orders-inner">
                            <?php if (empty($list)): ?>
                                <p class="no-orders-here">No orders in this stage</p>
                            <?php else: ?>
                                <?php foreach ($list as $o): ?>
                                    <?php
                                    $contact_display = !empty($o['contact_number']) ? $o['contact_number'] : $o['ca_contact_number'];
                                    $status_class = 'status-text-' . strtolower($o['status']);
                                    $payment_class = $o['payment_status'] == 'Paid' ? 'payment-paid' : 'payment-unpaid';
                                    $img_path = !empty($o['product_image']) ? '../images/' . $o['product_image'] : '';
                                    ?>
                                    <div class="order-card">
                                        <div class="order-header">
                                            <span class="order-id">Order #<?= $order_counter ?></span>
                                            <span class="order-date"><?= date('F j, Y — g:i A', strtotime($o['order_date'])) ?></span>
                                        </div>
                                        <div class="order-product-row">
                                            <div class="product-img-box">
                                                <?php if (!empty($o['product_image']) && file_exists($img_path)): ?>
                                                    <img src="<?= $img_path ?>" alt="<?= htmlspecialchars($o['product_name']) ?>">
                                                <?php else: ?>
                                                    <div class="product-img-placeholder">No Image</div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="order-grid">
                                                <div>
                                                    <div class="product-name"><?= htmlspecialchars($o['product_name']) ?> × <?= $o['quantity'] ?></div>
                                                    <div class="label">Total Amount</div>
                                                    <div class="value">₱<?= number_format($o['total_amount'], 2) ?></div>
                                                </div>
                                                <div>
                                                    <div class="label">Order Status</div>
                                                    <div class="value <?= $status_class ?>">
                                                        <?php
                                                        $s = $o['status'];
                                                        if($s == 'Pending') echo 'Pending';
                                                        elseif($s == 'Confirmed') echo 'Confirmed';
                                                        elseif($s == 'Preparing') echo 'Preparing';
                                                        elseif($s == 'Shipped') echo 'Shipped';
                                                        elseif($s == 'Delivered') echo 'Delivered';
                                                        else echo htmlspecialchars($s);
                                                        ?>
                                                    </div>
                                                </div>
                                                <div>
                                                    <div class="label">Payment Status</div>
                                                    <div class="value <?= $payment_class ?>">
                                                        <?= $o['payment_status'] == 'Paid' ? 'PAID' : 'UNPAID' ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="address">
                                            <?php if(!empty($contact_display)): ?>
                                            <p class="contact-line">Contact Number: <?= htmlspecialchars($contact_display) ?></p>
                                            <?php endif; ?>
                                            <p>Delivery Address: <?= htmlspecialchars($o['delivery_address']) ?></p>
                                            <p>Payment Method: <?= htmlspecialchars($o['payment_method']) ?></p>
                                        </div>
                                        <?php if($o['status'] == 'Pending'): ?>
                                            <a href="cancel_order.php?id=<?= $o['id'] ?>" class="cancel-btn" onclick="return confirm('Cancel this order? Stocks will be returned.');">Cancel Order</a>
                                        <?php endif; ?>
                                        <?php if($o['status'] == 'Delivered' && empty($o['rating'])): ?>
                                            <div class="rating-box">
                                                <h4>Rate this Product</h4>
                                                <form method="post" action="submit_rating.php">
                                                    <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                                    <input type="hidden" name="product_id" value="<?= $o['product_id'] ?>">
                                                    <div>
                                                        <label>Rating (1-5 Stars):</label>
                                                        <select name="rating" required>
                                                            <option value="">-- Select Stars --</option>
                                                            <option value="5">5 — Excellent</option>
                                                            <option value="4">4 — Very Good</option>
                                                            <option value="3">3 — Good</option>
                                                            <option value="2">2 — Fair</option>
                                                            <option value="1">1 — Poor</option>
                                                        </select>
                                                    </div>
                                                    <div>
                                                        <label>Your Review (Optional):</label>
                                                        <textarea name="review_text" rows="3" placeholder="Share your experience with this perfume..."></textarea>
                                                    </div>
                                                    <button type="submit">Submit Rating</button>
                                                </form>
                                            </div>
                                        <?php endif; ?>
                                        <?php if(!empty($o['rating'])): ?>
                                            <div class="rated-box">
                                                <strong>You Rated:</strong>
                                                <span class="rated-stars">
                                                    <?php
                                                    $stars = str_repeat('⭐', $o['rating']);
                                                    echo $stars . ' (' . $o['rating'] . '/5)';
                                                    ?>
                                                </span>
                                                <?php if(!empty($o['review_text'])): ?>
                                                    <p class="review-text">"<?= htmlspecialchars($o['review_text']) ?>"</p>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php $order_counter++; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
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
function toggleSection(sectionId) {
    const section = document.getElementById('section-' + sectionId);
    section.classList.toggle('open');
}
document.addEventListener('DOMContentLoaded', function() {
    const pendingSection = document.getElementById('section-pending');
    if (pendingSection) {
        pendingSection.classList.add('open');
    }
});
</script>
</body>
</html>