<?php
session_start();
include("../config.php");
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== "admin"){
    header("Location: ../login.php");
    exit();
}

$total_sales = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total_amount) as total FROM orders WHERE payment_status='Paid' AND status='Delivered'"));
$total_sales = $total_sales['total'] ?: 0;
$today_sales = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total_amount) as total FROM orders WHERE DATE(order_date) = CURDATE() AND payment_status='Paid' AND status='Delivered'"));
$today_sales = $today_sales['total'] ?: 0;
$week_sales = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total_amount) as total FROM orders WHERE DATE(order_date) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND payment_status='Paid' AND status='Delivered'"));
$week_sales = $week_sales['total'] ?: 0;
$month_sales = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total_amount) as total FROM orders WHERE MONTH(order_date) = MONTH(CURDATE()) AND YEAR(order_date) = YEAR(CURDATE()) AND payment_status='Paid' AND status='Delivered'"));
$month_sales = $month_sales['total'] ?: 0;

$pending_count = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM orders WHERE status='Pending'"));
$unread_msg_count = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM messages WHERE is_read=0"));

$low_stock_res = mysqli_query($conn, "SELECT * FROM products WHERE stock <= 5 ORDER BY stock ASC");
$low_stock_count = mysqli_num_rows($low_stock_res);
$low_stock_list = [];
if($low_stock_count > 0){
    while($p = mysqli_fetch_assoc($low_stock_res)){
        $low_stock_list[] = htmlspecialchars($p['perfume_name']) . " ({$p['stock']})";
    }
}

$daily_labels = []; $daily_values = [];
for($i=6; $i>=0; $i--){
    $date = date('Y-m-d', strtotime("-{$i} days"));
    $daily_labels[] = "'" . date('M j', strtotime($date)) . "'";
    $res = mysqli_query($conn, "SELECT SUM(total_amount) as total FROM orders WHERE DATE(order_date) = '{$date}' AND payment_status='Paid' AND status='Delivered'");
    $row = mysqli_fetch_assoc($res);
    $daily_values[] = $row['total'] ?: 0;
}
$daily_labels_json = implode(',', $daily_labels);
$daily_values_json = implode(',', $daily_values);

$weekly_labels = []; $weekly_values = [];
for($i=3; $i>=0; $i--){
    $start = date('Y-m-d', strtotime("-{$i} weeks -".date('w')." days +1 day"));
    $end = date('Y-m-d', strtotime("-{$i} weeks -".date('w')." days +7 days"));
    $weekly_labels[] = "'Week ".(4-$i)."'";
    $res = mysqli_query($conn, "SELECT SUM(total_amount) as total FROM orders WHERE DATE(order_date) BETWEEN '{$start}' AND '{$end}' AND payment_status='Paid' AND status='Delivered'");
    $row = mysqli_fetch_assoc($res);
    $weekly_values[] = $row['total'] ?: 0;
}
$weekly_labels_json = implode(',', $weekly_labels);
$weekly_values_json = implode(',', $weekly_values);

$monthly_labels = []; $monthly_values = [];
for($i=5; $i>=0; $i--){
    $month_num = date('n', strtotime("-{$i} months"));
    $year_num = date('Y', strtotime("-{$i} months"));
    $monthly_labels[] = "'".date('M Y', strtotime("-{$i} months"))."'";
    $res = mysqli_query($conn, "SELECT SUM(total_amount) as total FROM orders WHERE MONTH(order_date) = {$month_num} AND YEAR(order_date) = {$year_num} AND payment_status='Paid' AND status='Delivered'");
    $row = mysqli_fetch_assoc($res);
    $monthly_values[] = $row['total'] ?: 0;
}
$monthly_labels_json = implode(',', $monthly_labels);
$monthly_values_json = implode(',', $monthly_values);

$pm_gcash = $pm_cod = $pm_bpi = 0;
$pm_stats = mysqli_query($conn, "SELECT payment_method, COUNT(*) as cnt FROM orders WHERE payment_status='Paid' AND status='Delivered' GROUP BY payment_method");
while($pm = mysqli_fetch_assoc($pm_stats)){
    $method = trim($pm['payment_method'] ?: '');
    if(stripos($method, 'bpi') !== false) $pm_bpi = $pm['cnt'];
    elseif(stripos($method, 'gcash') !== false) $pm_gcash = $pm['cnt'];
    else $pm_cod += $pm['cnt'];
}
$pm_total = $pm_gcash + $pm_cod + $pm_bpi;
$pm_gcash_pct = $pm_total > 0 ? round(($pm_gcash / $pm_total) * 100) : 0;
$pm_cod_pct = $pm_total > 0 ? round(($pm_cod / $pm_total) * 100) : 0;
$pm_bpi_pct = $pm_total > 0 ? round(($pm_bpi / $pm_total) * 100) : 0;

$best_sellers = mysqli_query($conn, "SELECT product_name, SUM(quantity) as total_qty, SUM(total_amount) as total_sold 
    FROM orders WHERE payment_status='Paid' AND status='Delivered' 
    GROUP BY product_name ORDER BY total_qty DESC LIMIT 5");

$top_customers = mysqli_query($conn, "SELECT customer_name, COUNT(*) as order_count, SUM(total_amount) as total_spent 
    FROM orders WHERE payment_status='Paid' AND status='Delivered' 
    GROUP BY customer_name ORDER BY total_spent DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Admin Dashboard — M&Scents</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.8/dist/chart.umd.min.js"></script>
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
        --color-success: #4ade80;
        --color-danger: #ef4444;
        --color-accent: #6366f1;
        --color-bpi: #0055b8;
        --sidebar-width: 250px;
    }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
        font-family: var(--font-body);
        background: var(--color-bg-dark);
        color: var(--color-text);
        line-height: 1.6;
        min-height: 100vh;
    }
    h1, h2, h3, h4 { font-family: var(--font-heading); color: #fff; }

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
        display: flex;
        align-items: center;
        justify-content: space-between;
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
    .badge {
        background: var(--color-danger);
        color: #fff;
        font-size: 10px;
        font-weight: 700;
        min-width: 18px;
        height: 18px;
        line-height: 18px;
        text-align: center;
        border-radius: 9px;
        padding: 0 5px;
    }
    .badge:empty { display: none; }

    .content {
        padding: 25px;
    }
    .content h1 {
        font-size: 24px;
        color: var(--color-gold);
        margin-bottom: 5px;
    }
    .desc {
        color: var(--color-text-muted);
        margin-bottom: 20px;
        font-size: 14px;
    }

    .lowstock-notif {
        background: linear-gradient(90deg, rgba(239,68,68,0.15), rgba(239,68,68,0.08));
        border: 1px solid rgba(239,68,68,0.3);
        border-radius: 10px;
        padding: 10px 14px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }
    .lowstock-notif .icon {
        color: var(--color-danger);
        font-weight: 700;
        font-size: 15px;
        flex-shrink: 0;
    }
    .lowstock-notif .text {
        flex: 1;
        font-size: 13px;
        color: #fca5a5;
        line-height: 1.4;
    }
    .lowstock-notif .text strong { color: #fff; }
    .lowstock-notif.ok {
        background: rgba(74,222,128,0.08);
        border-color: rgba(74,222,128,0.25);
    }
    .lowstock-notif.ok .text { color: #86efac; }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        margin-bottom: 24px;
    }
    .stat-card {
        background: var(--color-bg-card);
        border: 1px solid var(--color-border);
        border-radius: 12px;
        padding: 20px;
        transition: all 0.3s ease;
    }
    .stat-card:hover {
        border-color: rgba(212,175,55,0.35);
        transform: translateY(-2px);
    }
    .stat-card h3 {
        font-size: 26px;
        font-weight: 700;
        margin-bottom: 4px;
    }
    .stat-card p {
        font-size: 13px;
        color: var(--color-text-muted);
    }
    .stat-card a { text-decoration: none; color: inherit; display: block; }
    .stat-card.sales h3 { color: var(--color-gold); }
    .stat-card.today h3 { color: var(--color-success); }
    .stat-card.orders h3 { color: var(--color-accent); }
    .stat-card.messages h3 { color: #f59e0b; }

    .charts-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 20px;
        margin-bottom: 24px;
    }
    .chart-card {
        background: var(--color-bg-card);
        border: 1px solid var(--color-border);
        border-radius: 12px;
        padding: 20px;
    }
    .chart-card h3 {
        font-size: 15px;
        color: var(--color-gold);
        margin-bottom: 15px;
        padding-bottom: 8px;
        border-bottom: 1px solid var(--color-border);
    }
    .tabs {
        display: flex;
        gap: 6px;
        margin-bottom: 15px;
        flex-wrap: wrap;
    }
    .tab-btn {
        padding: 8px 14px;
        background: transparent;
        border: 1px solid transparent;
        color: var(--color-text-muted);
        font-size: 13px;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.2s;
    }
    .tab-btn:hover { color: var(--color-gold); border-color: rgba(212,175,55,0.25); }
    .tab-btn.active {
        background: rgba(212,175,55,0.1);
        border-color: var(--color-gold);
        color: var(--color-gold);
    }
    .chart-container { position: relative; height: 280px; }

    .pm-bar {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 12px;
    }
    .pm-label { width: 90px; font-size: 13px; flex-shrink: 0; }
    .pm-track {
        flex: 1;
        height: 36px;
        background: #0f0f0f;
        border-radius: 7px;
        overflow: hidden;
        position: relative;
    }
    .pm-fill {
        height: 100%;
        border-radius: 7px;
        display: flex;
        align-items: center;
        padding-left: 12px;
        font-weight: 600;
        font-size: 13px;
        transition: width 0.5s ease;
    }
    .pm-gcash { background: linear-gradient(90deg, #3b82f6, #60a5fa); }
    .pm-cod { background: linear-gradient(90deg, #10b981, #34d399); }
    .pm-bpi { background: linear-gradient(90deg, #0055b8, #0066d9); }
    .pm-value {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 13px;
        font-weight: 600;
        color: #fff;
    }

    .bottom-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }
    .panel {
        background: var(--color-bg-card);
        border: 1px solid var(--color-border);
        border-radius: 12px;
        padding: 20px;
    }
    .panel h3 {
        font-size: 15px;
        color: var(--color-gold);
        margin-bottom: 15px;
        padding-bottom: 8px;
        border-bottom: 1px solid var(--color-border);
    }
    .rank-item {
        display: flex;
        align-items: center;
        padding: 12px 0;
        border-bottom: 1px solid var(--color-border);
    }
    .rank-item:last-child { border-bottom: none; }
    .rank-num {
        width: 28px;
        height: 28px;
        background: linear-gradient(135deg, var(--color-gold), var(--color-gold-light));
        color: #000;
        border-radius: 50%;
        font-weight: 700;
        font-size: 13px;
        display: grid;
        place-items: center;
        margin-right: 12px;
        flex-shrink: 0;
    }
    .rank-info { flex: 1; min-width: 0; }
    .rank-name { font-size: 14px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .rank-meta { font-size: 12px; color: var(--color-text-muted); margin-top: 2px; }
    .rank-amount {
        font-size: 14px;
        font-weight: 600;
        color: var(--color-gold);
        white-space: nowrap;
    }
    .empty {
        text-align: center;
        padding: 40px 20px;
        color: var(--color-text-muted);
        font-size: 14px;
    }

    .overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.5);
        z-index: 5;
    }

    @media(max-width: 1024px){
        .charts-grid, .bottom-grid { grid-template-columns: 1fr; }
        .stats-grid { grid-template-columns: repeat(2, 1fr); }
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
        }
        .sidebar.show-menu {
            transform: translateX(0);
            box-shadow: 4px 0 20px rgba(0,0,0,0.4);
        }
        .content { padding: 15px; }
        .logo-img { height: 40px; }
        .logo-text { font-size: 16px; }
        .content h1 { font-size: 20px; }
        .chart-container { height: 240px; }
    }
    @media(max-width: 480px){
        .stats-grid { grid-template-columns: 1fr; }
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
        <a href="dashboard.php" class="active">Dashboard<span class="badge"></span></a>
        <a href="orders.php">Orders<span class="badge"><?= $pending_count ?: '' ?></span></a>
        <a href="products.php">Manage Products<span class="badge"></span></a>
        <a href="payments.php">Payments<span class="badge"></span></a>
        <a href="sales.php">Sales Report<span class="badge"></span></a>
        <a href="reviews.php">Customer Reviews<span class="badge"></span></a>
        <a href="messages.php">Customer Messages<span class="badge"><?= $unread_msg_count ?: '' ?></span></a>
        <a href="../logout.php">Logout<span class="badge"></span></a>
    </div>

    <div class="content">
        <h1>Welcome, Admin</h1>
        <p class="desc">Here's what's happening with your store</p>

        <?php if($low_stock_count > 0): ?>
        <div class="lowstock-notif">
            <span class="icon">⚠</span>
            <div class="text">
                <strong>Low Stock Alert — <?= $low_stock_count ?> product<?= $low_stock_count > 1 ? 's' : '' ?></strong><br>
                <?= implode(', ', $low_stock_list) ?>
            </div>
        </div>
        <?php else: ?>
        <div class="lowstock-notif ok">
            <span class="icon">✓</span>
            <div class="text"><strong>Stock levels are good</strong> — All products have sufficient quantity</div>
        </div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card sales">
                <h3>₱<?= number_format($total_sales, 0) ?></h3>
                <p>Total Sales</p>
            </div>
            <div class="stat-card today">
                <h3>₱<?= number_format($today_sales, 0) ?></h3>
                <p>Today's Sales</p>
            </div>
            <a href="orders.php" class="stat-card orders">
                <h3><?= $pending_count ?></h3>
                <p>Pending Orders</p>
            </a>
            <a href="messages.php" class="stat-card messages">
                <h3><?= $unread_msg_count ?></h3>
                <p>New Messages</p>
            </a>
        </div>

        <div class="charts-grid">
            <div class="chart-card">
                <h3>Sales Trend</h3>
                <div class="tabs">
                    <button class="tab-btn active" data-type="daily">Daily</button>
                    <button class="tab-btn" data-type="weekly">Weekly</button>
                    <button class="tab-btn" data-type="monthly">Monthly</button>
                </div>
                <div class="chart-container">
                    <canvas id="salesChart"></canvas>
                </div>
            </div>
            <div class="chart-card">
                <h3>Payment Methods</h3>
                <div class="pm-bar">
                    <div class="pm-label">GCash</div>
                    <div class="pm-track">
                        <div class="pm-fill pm-gcash" style="width: <?= $pm_total > 0 ? ($pm_gcash / $pm_total) * 100 : 0 ?>%">
                            <?= $pm_gcash > 0 ? $pm_gcash : '' ?>
                        </div>
                        <span class="pm-value"><?= $pm_gcash_pct ?>%</span>
                    </div>
                </div>
                <div class="pm-bar">
                    <div class="pm-label">COD</div>
                    <div class="pm-track">
                        <div class="pm-fill pm-cod" style="width: <?= $pm_total > 0 ? ($pm_cod / $pm_total) * 100 : 0 ?>%">
                            <?= $pm_cod > 0 ? $pm_cod : '' ?>
                        </div>
                        <span class="pm-value"><?= $pm_cod_pct ?>%</span>
                    </div>
                </div>
                <div class="pm-bar">
                    <div class="pm-label">BPI Bank</div>
                    <div class="pm-track">
                        <div class="pm-fill pm-bpi" style="width: <?= $pm_total > 0 ? ($pm_bpi / $pm_total) * 100 : 0 ?>%">
                            <?= $pm_bpi > 0 ? $pm_bpi : '' ?>
                        </div>
                        <span class="pm-value"><?= $pm_bpi_pct ?>%</span>
                    </div>
                </div>
                <p style="margin-top: 16px; font-size: 13px; color: var(--color-text-muted);">
                    Total paid orders: <?= $pm_total ?>
                </p>
            </div>
        </div>

        <div class="bottom-grid">
            <div class="panel">
                <h3>Best Selling Products</h3>
                <?php if(mysqli_num_rows($best_sellers) == 0): ?>
                    <div class="empty">No sales yet</div>
                <?php else: $rank = 1; while($bs = mysqli_fetch_assoc($best_sellers)): ?>
                <div class="rank-item">
                    <span class="rank-num"><?= $rank ?></span>
                    <div class="rank-info">
                        <div class="rank-name"><?= htmlspecialchars($bs['product_name']) ?></div>
                        <div class="rank-meta"><?= $bs['total_qty'] ?> units</div>
                    </div>
                    <div class="rank-amount">₱<?= number_format($bs['total_sold'], 0) ?></div>
                </div>
                <?php $rank++; endwhile; endif; ?>
            </div>
            <div class="panel">
                <h3>Top Customers</h3>
                <?php if(mysqli_num_rows($top_customers) == 0): ?>
                    <div class="empty">No customer data yet</div>
                <?php else: $rank = 1; while($tc = mysqli_fetch_assoc($top_customers)): ?>
                <div class="rank-item">
                    <span class="rank-num"><?= $rank ?></span>
                    <div class="rank-info">
                        <div class="rank-name"><?= htmlspecialchars($tc['customer_name']) ?></div>
                        <div class="rank-meta"><?= $tc['order_count'] ?> orders</div>
                    </div>
                    <div class="rank-amount">₱<?= number_format($tc['total_spent'], 0) ?></div>
                </div>
                <?php $rank++; endwhile; endif; ?>
            </div>
        </div>
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

const chartData = {
    daily: { labels: [<?= $daily_labels_json ?>], values: [<?= $daily_values_json ?>] },
    weekly: { labels: [<?= $weekly_labels_json ?>], values: [<?= $weekly_values_json ?>] },
    monthly: { labels: [<?= $monthly_labels_json ?>], values: [<?= $monthly_values_json ?>] }
};
const ctx = document.getElementById('salesChart').getContext('2d');
const salesChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: chartData.daily.labels,
        datasets: [{
            label: 'Sales (₱)',
            data: chartData.daily.values,
            borderColor: '#d4af37',
            backgroundColor: 'rgba(212,175,55,0.1)',
            fill: true,
            tension: 0.4,
            pointBackgroundColor: '#d4af37',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointRadius: 4,
            borderWidth: 3
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#1a1a1a',
                titleColor: '#d4af37',
                bodyColor: '#fff',
                padding: 10,
                cornerRadius: 6,
                callbacks: { label: c => '₱' + Number(c.raw).toLocaleString() }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: { color: 'rgba(255,255,255,0.05)' },
                ticks: { color: '#888', callback: v => '₱' + v.toLocaleString(), maxTicksLimit: 5 }
            },
            x: { grid: { display: false }, ticks: { color: '#888', maxRotation: 45, minRotation: 0 } }
        },
        animation: { duration: 600 }
    }
});

document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', function(){
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        const t = this.dataset.type;
        salesChart.data.labels = chartData[t].labels;
        salesChart.data.datasets[0].data = chartData[t].values;
        salesChart.update();
    });
});
</script>
</body>
</html>