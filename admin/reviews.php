<?php
session_start();
include("../config.php");
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== "admin"){
    header("Location: ../login.php");
    exit();
}

if(isset($_GET['delete'])){
    $order_id = intval($_GET['delete']);
    $pid = intval($_GET['pid']);
    
    mysqli_query($conn, "UPDATE orders SET rating=NULL, review_text=NULL, reviewed_at=NULL WHERE id='$order_id'");
    
    $result = mysqli_query($conn, "SELECT AVG(rating) as avg_rtg, COUNT(*) as total FROM orders WHERE product_id='$pid' AND rating IS NOT NULL");
    $row = mysqli_fetch_assoc($result);
    $new_avg = $row['avg_rtg'] ? round($row['avg_rtg'], 2) : 0.00;
    $total_count = $row['total'] ? $row['total'] : 0;
    mysqli_query($conn, "UPDATE products SET avg_rating='$new_avg', total_ratings='$total_count' WHERE id='$pid'");
    
    echo "<script>alert('Review Deleted! Average Rating Updated!'); location='reviews.php';</script>";
    exit();
}

$result = mysqli_query($conn, "SELECT orders.*, products.perfume_name 
    FROM orders 
    LEFT JOIN products ON orders.product_id = products.id 
    WHERE orders.rating IS NOT NULL 
    ORDER BY orders.reviewed_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Customer Reviews — M&Scents</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
    <style>
    :root {
        --font-heading: 'Playfair Display', serif;
        --font-body: 'Inter', sans-serif;
        --color-gold: #d4af37;
        --color-gold-light: #f0c94d;
        --color-bg-dark: #0f0f0f;
        --color-bg-card: #1a1a1a;
        --color-border: #2a2a2a;
        --color-text: #e5e5e5;
        --color-text-muted: #888888;
        --color-danger: #ef4444;
        --color-success: #4ade80;
        --sidebar-width: 250px;
    }
    * { margin:0; padding:0; box-sizing:border-box; }
    body {
        font-family: var(--font-body);
        background: var(--color-bg-dark);
        color: var(--color-text);
        line-height: 1.6;
    }
    h1, h2, h3 { font-family: var(--font-heading); color: #fff; }
    .topnav {
        background: var(--color-bg-card);
        border-bottom: 1px solid var(--color-border);
        padding: 12px 18px;
        display: flex;
        align-items: center;
        gap: 12px;
        position: sticky;
        top: 0;
        z-index: 100;
    }
    .menu-toggle {
        display: none;
        flex-direction: column;
        gap: 4px;
        background: transparent;
        border: none;
        cursor: pointer;
        padding: 6px 8px;
        margin-right: 4px;
    }
    .menu-toggle span {
        width: 22px;
        height: 2px;
        background: var(--color-gold);
        border-radius: 2px;
        transition: all 0.3s;
    }
    .logo-group {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .logo-img {
        height: 45px;
        width: auto;
    }
    .logo-text {
        font-family: var(--font-heading);
        font-size: 18px;
        color: var(--color-gold);
        font-weight: 700;
        text-decoration: none;
        letter-spacing: 0.5px;
    }
    .admin-layout {
        display: grid;
        grid-template-columns: var(--sidebar-width) 1fr;
        min-height: calc(100vh - 69px);
    }
    .sidebar {
        background: var(--color-bg-card);
        border-right: 1px solid var(--color-border);
        padding: 25px 15px;
        transition: transform 0.3s ease;
    }
    .sidebar h2 {
        font-size: 17px;
        color: var(--color-gold);
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 1px solid var(--color-border);
    }
    .sidebar a {
        display: block;
        color: var(--color-text-muted);
        padding: 10px 14px;
        margin: 5px 0;
        text-decoration: none;
        border-radius: 7px;
        font-weight: 500;
        font-size: 14px;
        transition: all 0.25s;
    }
    .sidebar a:hover,
    .sidebar a.active {
        background: rgba(212,175,55,0.1);
        color: var(--color-gold);
    }
    .content {
        padding: 25px;
    }
    .content h1 {
        font-size: 24px;
        color: var(--color-gold);
        margin-bottom: 20px;
    }
    .review-card {
        background: var(--color-bg-card);
        padding: 20px;
        margin-bottom: 16px;
        border-radius: 12px;
        border: 1px solid var(--color-border);
        transition: all 0.25s ease;
        animation: fadeInUp 0.4s ease;
    }
    .review-card:hover {
        border-color: rgba(212,175,55,0.25);
    }
    .review-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 12px;
    }
    .customer-name {
        font-weight: 600;
        font-size: 15px;
        color: #fff;
    }
    .product-name {
        color: var(--color-gold);
        font-size: 14px;
        margin-left: 10px;
    }
    .rating-stars {
        color: #facc15;
        font-size: 18px;
        margin: 10px 0;
    }
    .rating-stars span {
        font-size: 14px;
        color: #aaa;
        margin-left: 8px;
    }
    .review-text {
        padding: 12px 15px;
        background: rgba(255,255,255,0.03);
        border-radius: 8px;
        color: #ccc;
        font-style: italic;
        margin: 10px 0;
        line-height: 1.6;
        font-size: 14px;
    }
    .review-date {
        color: #666;
        font-size: 13px;
        margin-top: 8px;
    }
    .delete-btn {
        padding: 7px 14px;
        background: rgba(239,68,68,0.1);
        color: #ef4444;
        border-radius: 6px;
        text-decoration: none;
        font-size: 13px;
        font-weight: 500;
        transition: all 0.2s;
        white-space: nowrap;
    }
    .delete-btn:hover {
        background: rgba(239,68,68,0.2);
        color: #fff;
    }
    .empty {
        text-align: center;
        padding: 60px 20px;
        color: var(--color-text-muted);
        font-size: 16px;
    }
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(8px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.5);
        z-index: 5;
    }
    @media(max-width: 768px){
        .menu-toggle { display: flex; }
        .admin-layout { grid-template-columns: 1fr; position: relative; }
        .sidebar {
            position: fixed;
            top: 69px;
            left: 0;
            bottom: 0;
            width: 250px;
            z-index: 10;
            transform: translateX(-100%);
            box-shadow: none;
        }
        .sidebar.show-menu {
            transform: translateX(0);
            box-shadow: 4px 0 20px rgba(0,0,0,0.4);
        }
        .content { padding: 15px; }
        .logo-img { height: 40px; }
        .logo-text { font-size: 16px; }
        .content h1 { font-size: 20px; }
        .review-card { padding: 15px; }
        .review-header { flex-direction: column; align-items: flex-start; }
        .delete-btn { width: 100%; text-align: center; margin-top: 8px; }
    }
    </style>
</head>
<body>
<div class="topnav">
    <button class="menu-toggle" id="menuToggle" aria-label="Toggle Menu">
        <span></span>
        <span></span>
        <span></span>
    </button>
    <div class="logo-group">
        <img src="../images/logo.png" alt="M&Scents Logo" class="logo-img">
        <a href="dashboard.php" class="logo-text">M&Scents</a>
    </div>
</div>
<div class="overlay" id="overlay"></div>
<div class="admin-layout">
    <div class="sidebar" id="sidebar">
        <h2>Control Panel</h2>
        <a href="dashboard.php">Dashboard</a>
        <a href="orders.php">Orders</a>
        <a href="products.php">Manage Products</a>
        <a href="payments.php">Payments</a>
        <a href="sales.php">Sales Report</a>
        <a href="reviews.php" class="active">Customer Reviews</a>
        <a href="messages.php">Customer Messages</a>
        <a href="../logout.php">Logout</a>
    </div>
    <div class="content">
        <h1>Customer Reviews & Ratings</h1>
        <?php if(mysqli_num_rows($result) == 0): ?>
            <div class="empty">No reviews yet.</div>
        <?php else: ?>
            <?php while($r = mysqli_fetch_assoc($result)): ?>
            <div class="review-card">
                <div class="review-header">
                    <div>
                        <span class="customer-name"><?= htmlspecialchars($r['customer_name']) ?></span>
                        <span class="product-name"><?= htmlspecialchars($r['perfume_name']) ?></span>
                    </div>
                    <a href="reviews.php?delete=<?= $r['id'] ?>&pid=<?= $r['product_id'] ?>" class="delete-btn" onclick="return confirm('Delete this review? Average rating will recalculate.');">Delete</a>
                </div>
                <div class="rating-stars">
                    <?php 
                    $s = intval($r['rating']);
                    echo str_repeat('★', $s) . str_repeat('☆', 5 - $s);
                    ?>
                    <span><?= $s ?>/5</span>
                </div>
                <?php if(!empty($r['review_text'])): ?>
                <div class="review-text">"<?= htmlspecialchars($r['review_text']) ?>"</div>
                <?php else: ?>
                <p style="color:#666; font-size:13px; font-style:italic;">No written review.</p>
                <?php endif; ?>
                <div class="review-date"><?= date('F j, Y — g:i A', strtotime($r['reviewed_at'])) ?></div>
            </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>
</div>
<script>
const menuToggle = document.getElementById('menuToggle');
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('overlay');

function toggleMenu(){
    sidebar.classList.toggle('show-menu');
    overlay.style.display = sidebar.classList.contains('show-menu') ? 'block' : 'none';
}

menuToggle.addEventListener('click', toggleMenu);
overlay.addEventListener('click', toggleMenu);

sidebar.querySelectorAll('a').forEach(link => {
    link.addEventListener('click', () => {
        if(window.innerWidth <= 768){
            sidebar.classList.remove('show-menu');
            overlay.style.display = 'none';
        }
    });
});
</script>
</body>
</html>