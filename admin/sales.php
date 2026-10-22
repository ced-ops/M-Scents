<?php
session_start();
include("../config.php");
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== "admin"){
    header("Location: ../login.php");
    exit();
}

$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

$total_sales = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total_amount) as total FROM orders WHERE payment_status='Paid' AND status='Delivered'"));
$total_sales = $total_sales['total'] ?: 0;

$today_sales = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total_amount) as total FROM orders WHERE DATE(order_date) = CURDATE() AND payment_status='Paid' AND status='Delivered'"));
$today_sales = $today_sales['total'] ?: 0;

$week_sales = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total_amount) as total FROM orders WHERE DATE(order_date) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND payment_status='Paid' AND status='Delivered'"));
$week_sales = $week_sales['total'] ?: 0;

$month_sales = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total_amount) as total FROM orders WHERE MONTH(order_date) = MONTH(CURDATE()) AND YEAR(order_date) = YEAR(CURDATE()) AND payment_status='Paid' AND status='Delivered'"));
$month_sales = $month_sales['total'] ?: 0;

$custom_sales = 0;
$custom_label = 'Custom Range';
$custom_daily_labels = [];
$custom_daily_values = [];

if(!empty($start_date)){
    $s = $start_date;
    $e = !empty($end_date) ? $end_date : $start_date;
    
    $custom = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total_amount) as total FROM orders WHERE DATE(order_date) BETWEEN '$s' AND '$e' AND payment_status='Paid' AND status='Delivered'"));
    $custom_sales = $custom['total'] ?: 0;
    
    $custom_label = ($s === $e) ? date('M j, Y', strtotime($s)) : date('M j', strtotime($s)).' – '.date('M j, Y', strtotime($e));
    
    $range = [];
    $current = strtotime($s);
    $last = strtotime($e);
    while($current <= $last){
        $d = date('Y-m-d', $current);
        $range[] = $d;
        $res = mysqli_query($conn, "SELECT SUM(total_amount) as total FROM orders WHERE DATE(order_date) = '$d' AND payment_status='Paid' AND status='Delivered'");
        $row = mysqli_fetch_assoc($res);
        $custom_daily_labels[] = "'".date('M j', $current)."'";
        $custom_daily_values[] = $row['total'] ?: 0;
        $current = strtotime('+1 day', $current);
    }
}

$daily_labels = [];
$daily_values = [];
for($i=6; $i>=0; $i--){
    $date = date('Y-m-d', strtotime("-{$i} days"));
    $daily_labels[] = "'" . date('M j', strtotime($date)) . "'";
    $res = mysqli_query($conn, "SELECT SUM(total_amount) as total FROM orders WHERE DATE(order_date) = '{$date}' AND payment_status='Paid' AND status='Delivered'");
    $row = mysqli_fetch_assoc($res);
    $daily_values[] = $row['total'] ?: 0;
}
$daily_labels_json = implode(',', $daily_labels);
$daily_values_json = implode(',', $daily_values);

$weekly_labels = [];
$weekly_values = [];
for($i=3; $i>=0; $i--){
    $start = date('Y-m-d', strtotime("-{$i} weeks -".date('w')." days +1 day"));
    $end = date('Y-m-d', strtotime("-{$i} weeks -".date('w')." days +7 days"));
    $weekly_labels[] = "'Week ".(4-$i)."'";
    $res = mysqli_query($conn, "SELECT SUM(total_amount) as total FROM orders WHERE DATE(order_date) BETWEEN '$start' AND '$end' AND payment_status='Paid' AND status='Delivered'");
    $row = mysqli_fetch_assoc($res);
    $weekly_values[] = $row['total'] ?: 0;
}
$weekly_labels_json = implode(',', $weekly_labels);
$weekly_values_json = implode(',', $weekly_values);

$monthly_labels = [];
$monthly_values = [];
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

$custom_labels_json = implode(',', $custom_daily_labels);
$custom_values_json = implode(',', $custom_daily_values);

$best_sellers = mysqli_query($conn, "SELECT product_name, SUM(quantity) as total_qty, SUM(total_amount) as total_sold 
    FROM orders 
    WHERE payment_status='Paid' AND status='Delivered' 
    GROUP BY product_name 
    ORDER BY total_qty DESC 
    LIMIT 10");

$result = mysqli_query($conn, "SELECT * FROM orders WHERE status='Delivered' AND payment_status='Paid' ORDER BY order_date DESC LIMIT 15");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Sales Report — M&Scents</title>
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
        --sidebar-width: 250px;
    }
    * { margin:0; padding:0; box-sizing:border-box; }
    body { 
        font-family:var(--font-body); 
        background:var(--color-bg-dark); 
        color:var(--color-text); 
        line-height:1.6; 
    }
    h1,h2,h3,h4 { font-family:var(--font-heading); color: #fff; }
    .topnav {
        background:var(--color-bg-card); 
        border-bottom:1px solid var(--color-border);
        padding:12px 18px; 
        display:flex; 
        align-items:center;
        gap:12px;
        position:sticky; 
        top:0; 
        z-index:100;
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
    .logo-group { display:flex; align-items:center; gap:10px; }
    .logo-img { height:45px; width:auto; }
    .logo-text { 
        font-family:var(--font-heading); 
        font-size:18px; 
        color:var(--color-gold); 
        text-decoration:none; 
        font-weight:700; 
        letter-spacing: 0.5px;
    }
    .admin-layout { 
        display:grid; 
        grid-template-columns: var(--sidebar-width) 1fr; 
        min-height:calc(100vh - 69px);
    }
    .sidebar {
        background:var(--color-bg-card); 
        border-right:1px solid var(--color-border);
        padding:25px 15px;
        transition: transform 0.3s ease;
    }
    .sidebar h2 {
        font-size:17px;
        color:var(--color-gold); 
        margin-bottom:20px;
        padding-bottom:10px; 
        border-bottom:1px solid var(--color-border);
    }
    .sidebar a {
        display:block; 
        color:var(--color-text-muted); 
        padding:10px 14px;
        margin:5px 0;
        text-decoration:none; 
        border-radius:7px;
        font-weight:500; 
        font-size:14px;
        transition:all 0.25s;
    }
    .sidebar a:hover, 
    .sidebar a.active { 
        background:rgba(212,175,55,0.1); 
        color:var(--color-gold); 
    }
    .content { padding:20px; max-width:100%; }
    .page-header { margin-bottom:18px; }
    .page-header h1 { 
        font-size:24px;
        color:var(--color-gold); 
        margin-bottom:4px; 
    }
    .page-header p { color:var(--color-text-muted); font-size:14px; }
    .cards-grid {
        display:grid; 
        grid-template-columns:repeat(4, 1fr); 
        gap:18px;
        margin-bottom:22px;
    }
    .sale-card {
        background:var(--color-bg-card); 
        border:1px solid var(--color-border); 
        border-radius:12px;
        padding:22px 18px;
        transition: all 0.3s ease;
    }
    .sale-card:hover {
        border-color: rgba(212,175,55,0.35);
        transform: translateY(-3px);
        box-shadow: 0 6px 20px rgba(0,0,0,0.3);
    }
    .sale-card h3 { font-size:13px; color:var(--color-text-muted); margin-bottom:6px; }
    .sale-card .amount { font-size:32px; font-weight:700; color:var(--color-gold); }
    .sale-card .label { font-size:11px; color:var(--color-text-muted); margin-top:4px; }
    .custom-card {
        background:var(--color-bg-card); 
        border:1px solid var(--color-border); 
        border-radius:12px;
        padding:22px;
        margin-bottom:22px;
    }
    .custom-card h3 { font-size:18px; color:var(--color-gold); margin-bottom:12px; }
    .custom-form {
        display:flex; gap:12px; align-items:flex-end; flex-wrap:wrap;
    }
    .field-group { flex:1; min-width:160px; }
    .field-group label {
        display:block; font-size:13px; color:var(--color-text-muted); margin-bottom:5px;
    }
    .field-group input {
        width:100%; padding:10px 12px; background:var(--color-bg-dark);
        border:1px solid var(--color-border); border-radius:7px;
        color:var(--color-text); font-size:14px;
    }
    .field-group input:focus { outline:none; border-color:var(--color-gold); }
    .submit-btn {
        padding:10px 22px; background:linear-gradient(135deg, var(--color-gold), var(--color-gold-light));
        color:#000; border:none; border-radius:7px; font-weight:600; cursor:pointer; transition:all 0.3s;
    }
    .submit-btn:hover { transform:translateY(-2px); box-shadow:0 4px 12px rgba(212,175,55,0.25); }
    .reset-btn {
        padding:10px 22px; background:#374151; color:#fff; border:none; border-radius:7px;
        font-weight:500; cursor:pointer; text-decoration:none; display:inline-block; text-align:center;
    }
    .chart-card {
        background:var(--color-bg-card); 
        border:1px solid var(--color-border); 
        border-radius:12px;
        padding:22px;
        margin-bottom:22px; 
    }
    .chart-card h3 { font-size:18px; color:var(--color-gold); margin-bottom:12px; }
    .tabs { 
        display:flex; 
        gap:8px; 
        margin-bottom:14px; 
        border-bottom:1px solid var(--color-border); 
        padding-bottom:8px; 
        flex-wrap:wrap;
    }
    .tab-btn {
        padding:8px 14px; 
        background:transparent; 
        border:none; 
        color:var(--color-text-muted); 
        font-size:13px; 
        border-radius:7px;
        cursor:pointer;
        transition: all 0.2s;
    }
    .tab-btn:hover { color: var(--color-gold); }
    .tab-btn.active {
        color:var(--color-gold); 
        border-bottom:2px solid var(--color-gold); 
        background:rgba(212,175,55,0.08);
    }
    .chart-container { position:relative; height:280px; }
    .bottom-grid { display:grid; grid-template-columns:320px 1fr; gap:18px; }
    .panel {
        background:var(--color-bg-card); 
        border:1px solid var(--color-border); 
        border-radius:12px;
        padding:22px;
    }
    .panel h3 { 
        font-size:18px;
        color:var(--color-gold); 
        margin-bottom:14px; 
        padding-bottom:10px; 
        border-bottom:1px solid var(--color-border); 
    }
    .best-item {
        padding:10px 0; 
        border-bottom:1px solid var(--color-border); 
        display:flex; 
        align-items:center; 
    }
    .rank { 
        width:28px; 
        height:28px; 
        background:linear-gradient(135deg, var(--color-gold), var(--color-gold-light)); 
        color:#000; 
        border-radius:50%; 
        font-weight:700; 
        font-size:13px; 
        display:grid; place-items:center;
        margin-right:12px; 
        flex-shrink:0; 
    }
    .prod-name { font-size:14px; }
    .prod-stats { font-size:12px; color:var(--color-text-muted); margin-top:2px; }
    .prod-total { font-size:14px; font-weight:600; color:var(--color-gold); }
    table { width:100%; border-collapse:collapse; }
    th, td { padding:12px 10px; font-size:13px; border-bottom:1px solid var(--color-border); }
    th { color:var(--color-gold); background:rgba(212,175,55,0.05); }
    .amount-paid { color:var(--color-success); font-weight:600; }
    .empty { text-align:center; padding:60px 20px; color:var(--color-text-muted); font-size:14px; }
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
        }
        .sidebar.show-menu {
            transform: translateX(0);
            box-shadow: 4px 0 20px rgba(0,0,0,0.4);
        }
        .content { padding:15px; }
        .logo-img { height: 40px; }
        .logo-text { font-size: 16px; }
        .page-header h1 { font-size: 20px; }
        .cards-grid { grid-template-columns: repeat(2, 1fr); gap: 12px; }
        .sale-card { padding: 16px 12px; }
        .sale-card .amount { font-size: 26px; }
        .chart-container { height: 240px; }
        .bottom-grid { grid-template-columns: 1fr; gap: 15px; }
        .panel { padding: 16px; }
        .custom-form { flex-direction:column; align-items:stretch; }
    }
    @media(max-width: 480px){
        .cards-grid { grid-template-columns: 1fr; }
        .tabs { gap: 4px; }
        .tab-btn { padding: 6px 10px; font-size: 12px; }
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
        <a href="sales.php" class="active">Sales Report</a>
        <a href="reviews.php">Customer Reviews</a>
        <a href="messages.php">Customer Messages</a>
        <a href="../logout.php">Logout</a>
    </div>
    <div class="content">
        <div class="page-header">
            <h1>Sales Report — Paid & Delivered Only</h1>
            <p>Track your revenue growth — select a custom range or use the tabs below</p>
        </div>
        <div class="cards-grid">
            <div class="sale-card">
                <h3>Total Sales</h3>
                <div class="amount">₱<?php echo number_format($total_sales, 2); ?></div>
                <div class="label">All Time</div>
            </div>
            <div class="sale-card">
                <h3>Today's Sales</h3>
                <div class="amount">₱<?php echo number_format($today_sales, 2); ?></div>
                <div class="label"><?php echo date('M j, Y'); ?></div>
            </div>
            <div class="sale-card">
                <h3>Weekly Sales</h3>
                <div class="amount">₱<?php echo number_format($week_sales, 2); ?></div>
                <div class="label">Last 7 Days</div>
            </div>
            <div class="sale-card">
                <h3>Monthly Sales</h3>
                <div class="amount">₱<?php echo number_format($month_sales, 2); ?></div>
                <div class="label"><?php echo date('F Y'); ?></div>
            </div>
        </div>
        <div class="custom-card">
            <h3>Custom Date Range</h3>
            <?php if(!empty($start_date)): ?>
            <div style="margin-bottom:12px; padding:10px 14px; background:rgba(212,175,55,0.08); border-radius:7px;">
                <strong style="color:var(--color-gold);">Selected:</strong> <?php echo $custom_label; ?> — 
                <strong>₱<?php echo number_format($custom_sales, 2); ?></strong>
            </div>
            <?php endif; ?>
            <form method="get" class="custom-form">
                <div class="field-group">
                    <label>Start Date</label>
                    <input type="date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>" required>
                </div>
                <div class="field-group">
                    <label>End Date</label>
                    <input type="date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
                </div>
                <button type="submit" class="submit-btn">Apply</button>
                <a href="sales.php" class="reset-btn">Reset</a>
            </form>
        </div>
        <div class="chart-card">
            <h3>Sales Trend — Click to View:</h3>
            <div class="tabs">
                <?php if(!empty($start_date)): ?>
                <button class="tab-btn" data-type="custom"><?php echo $custom_label; ?></button>
                <?php endif; ?>
                <button class="tab-btn <?php echo empty($start_date) ? 'active' : ''; ?>" data-type="daily">Daily</button>
                <button class="tab-btn" data-type="weekly">Weekly</button>
                <button class="tab-btn" data-type="monthly">Monthly</button>
            </div>
            <div class="chart-container">
                <canvas id="salesChart"></canvas>
            </div>
        </div>
        <div class="bottom-grid">
            <div class="panel">
                <h3>Best Selling Products</h3>
                <?php if(mysqli_num_rows($best_sellers) == 0): ?>
                    <div class="empty">No sales yet.</div>
                <?php else: $rank=1; while($bs = mysqli_fetch_assoc($best_sellers)): ?>
                <div class="best-item">
                    <span class="rank"><?php echo $rank; ?></span>
                    <div class="prod-info">
                        <div class="prod-name"><?php echo htmlspecialchars($bs['product_name']); ?></div>
                        <div class="prod-stats"><?php echo $bs['total_qty']; ?> units sold</div>
                    </div>
                    <div class="prod-total">₱<?php echo number_format($bs['total_sold'], 0); ?></div>
                </div>
                <?php $rank++; endwhile; endif; ?>
            </div>
            <div class="panel">
                <h3>Recent Delivered Orders</h3>
                <?php if(mysqli_num_rows($result) == 0): ?>
                    <div class="empty">No delivered orders yet.</div>
                <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Product</th>
                            <th>Amount</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($o = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($o['customer_name']); ?></td>
                            <td><?php echo htmlspecialchars($o['product_name']); ?></td>
                            <td class="amount-paid">₱<?php echo number_format($o['total_amount'], 2); ?></td>
                            <td><?php echo date('m/d/y', strtotime($o['order_date'])); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php endif; ?>
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
</script>
<script>
const chartData = {
    <?php if(!empty($start_date)): ?>
    custom: { labels: [<?php echo $custom_labels_json; ?>], values: [<?php echo $custom_values_json; ?>] },
    <?php endif; ?>
    daily: { labels: [<?php echo $daily_labels_json; ?>], values: [<?php echo $daily_values_json; ?>] },
    weekly: { labels: [<?php echo $weekly_labels_json; ?>], values: [<?php echo $weekly_values_json; ?>] },
    monthly: { labels: [<?php echo $monthly_labels_json; ?>], values: [<?php echo $monthly_values_json; ?>] }
};
let defaultType = '<?php echo empty($start_date) ? 'daily' : 'custom'; ?>';
let ctx = document.getElementById('salesChart').getContext('2d');
let salesChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: chartData[defaultType].labels,
        datasets: [{
            label: 'Sales Amount (₱)',
            data: chartData[defaultType].values,
            borderColor: '#d4af37',
            backgroundColor: 'rgba(212,175,55,0.1)',
            fill: true,
            tension: 0.4,
            pointBackgroundColor: '#d4af37',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointRadius: 5,
            borderWidth: 3
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#1a1a1a', titleColor: '#d4af37',
                bodyColor: '#fff', borderColor: '#d4af37',
                padding: 10, cornerRadius: 6,
                callbacks: { label: c => '₱' + Number(c.raw).toLocaleString() }
            }
        },
        scales: {
            y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.05)' },
                ticks: { color: '#888', callback: v => '₱' + v.toLocaleString() } },
            x: { grid: { display: false }, ticks: { color: '#888' } }
        }
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