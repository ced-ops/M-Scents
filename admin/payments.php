<?php
session_start();
include("../config.php");

if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== "admin"){
    header("Location: ../login.php");
    exit();
}

if(isset($_POST['bulk_update_payment']) && !empty($_POST['selected_orders'])){
    $new_status = mysqli_real_escape_string($conn, $_POST['bulk_new_status']);
    $ids = array_map('intval', $_POST['selected_orders']);
    $id_list = implode(',', $ids);
    mysqli_query($conn, "UPDATE orders SET payment_status = '$new_status' WHERE id IN ($id_list)");
    echo "<script>alert('All selected orders have been updated to $new_status!');</script>";
}

if(isset($_POST['update_payment'])){
    $order_id = intval($_POST['order_id']);
    $new_status = mysqli_real_escape_string($conn, $_POST['payment_status']);
    mysqli_query($conn, "UPDATE orders SET payment_status = '$new_status' WHERE id = '$order_id'");
    echo "<script>alert('Payment Status Updated!');</script>";
}

$status_filter = $_GET['show'] ?? 'unpaid';
$where = "";
if($status_filter === 'unpaid'){
    $where = "WHERE payment_status = 'Unpaid' OR payment_status IS NULL OR payment_status = ''";
} elseif($status_filter === 'paid'){
    $where = "WHERE payment_status = 'Paid'";
}

$result = mysqli_query($conn, "SELECT * FROM orders $where ORDER BY order_date DESC");

$method_stats = mysqli_query($conn, "
    SELECT payment_method, COUNT(*) as total, SUM(total_amount) as sales
    FROM orders
    GROUP BY payment_method
");
$gcash_count = $cod_count = $bpi_count = 0;
$gcash_sales = $cod_sales = $bpi_sales = 0;
while($m = mysqli_fetch_assoc($method_stats)){
    $method = trim($m['payment_method'] ?: '');
    
    if(stripos($method, 'bpi') !== false){
        $bpi_count = $m['total'];
        $bpi_sales = $m['sales'];
    } elseif(stripos($method, 'gcash') !== false){
        $gcash_count = $m['total'];
        $gcash_sales = $m['sales'];
    } else {
        $cod_count = $m['total'];
        $cod_sales = $m['sales'];
    }
}
$total_methods = $gcash_count + $cod_count + $bpi_count;
$total_sales_all = $gcash_sales + $cod_sales + $bpi_sales;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Payment Transactions — M&Scents</title>
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
        --color-success: #4ade80;
        --color-danger: #ef4444;
        --color-bpi: #0055b8;
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
        margin-bottom: 5px;
    }
    .desc {
        color: var(--color-text-muted);
        margin-bottom: 20px;
        font-size: 14px;
    }
    
    .tabs {
        display: flex;
        gap: 8px;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }
    .tab-btn {
        padding: 9px 18px;
        background: transparent;
        border: 1px solid var(--color-border);
        color: var(--color-text-muted);
        border-radius: 8px;
        text-decoration: none;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.2s;
    }
    .tab-btn:hover {
        border-color: var(--color-gold);
        color: var(--color-gold);
    }
    .tab-btn.active {
        background: rgba(212,175,55,0.1);
        border-color: var(--color-gold);
        color: var(--color-gold);
    }
    .tab-btn.unpaid.active {
        background: rgba(239,68,68,0.1);
        border-color: var(--color-danger);
        color: var(--color-danger);
    }
    .tab-btn.paid.active {
        background: rgba(74,222,128,0.1);
        border-color: var(--color-success);
        color: var(--color-success);
    }
    
    .graphs-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 24px;
    }
    .graph-card {
        background: var(--color-bg-card);
        border: 1px solid var(--color-border);
        border-radius: 12px;
        padding: 20px;
    }
    .graph-card h3 {
        font-size: 15px;
        color: var(--color-gold);
        margin-bottom: 15px;
    }
    .bar-row {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 10px;
    }
    .bar-label {
        width: 120px;
        font-size: 13px;
        flex-shrink: 0;
    }
    .bar-wrapper {
        flex: 1;
        height: 32px;
        background: #0f0f0f;
        border-radius: 6px;
        overflow: hidden;
        position: relative;
    }
    .bar-fill {
        height: 100%;
        border-radius: 6px;
        transition: width 0.5s ease;
        display: flex;
        align-items: center;
        padding-left: 10px;
        font-weight: 600;
        font-size: 12px;
    }
    .bar-gcash { background: linear-gradient(90deg, #3b82f6, #60a5fa); }
    .bar-cod { background: linear-gradient(90deg, #10b981, #34d399); }
    .bar-bpi { background: linear-gradient(90deg, #0055b8, #0066d9); }
    .bar-value {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 12px;
        font-weight: 600;
    }
    .sales-row {
        display: flex;
        justify-content: space-between;
        padding: 6px 0;
        font-size: 13px;
    }
    .sales-row.total {
        border-top: 1px dashed var(--color-border);
        margin-top: 8px;
        padding-top: 10px;
        font-weight: 700;
        color: var(--color-gold);
    }
    .gcash-text { color: #60a5fa; }
    .cod-text { color: #4ade80; }
    .bpi-text { color: #4d90fe; }
    
    .content-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
        flex-wrap: wrap;
        gap: 10px;
    }
    .toggle-select-btn {
        background: rgba(99,102,241,0.15);
        border: 1px solid rgba(99,102,241,0.3);
        color: #6366f1;
        padding: 8px 14px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 500;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s;
    }
    .toggle-select-btn:hover,
    .toggle-select-btn.active {
        border-color: #6366f1;
        background: rgba(99,102,241,0.2);
    }
    .bulk-menu {
        display: none;
        background: var(--color-bg-card);
        border: 1px solid var(--color-border);
        border-radius: 10px;
        padding: 16px;
        margin-bottom: 18px;
    }
    .bulk-menu.show { display: block; }
    .bulk-menu-row {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: center;
    }
    .select-all-link {
        font-size: 13px;
        color: #6366f1;
        cursor: pointer;
        text-decoration: underline;
    }
    .bulk-menu select,
    .bulk-btn {
        padding: 8px 12px;
        border-radius: 7px;
        font-size: 13px;
        font-weight: 500;
    }
    .bulk-menu select {
        background: #0f0f0f;
        border: 1px solid var(--color-border);
        color: #fff;
    }
    .bulk-btn {
        background: #6366f1;
        color: #fff;
        border: none;
        cursor: pointer;
    }
    
    .payments-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }
    .payment-card {
        background: var(--color-bg-card);
        border: 1px solid var(--color-border);
        border-radius: 12px;
        padding: 16px;
        transition: all 0.25s ease;
        position: relative;
        overflow: hidden;
    }
    .payment-card::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 3px;
        background: var(--color-border);
        transition: all 0.25s;
    }
    .payment-card:hover {
        background: #242424;
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(0,0,0,0.3);
    }
    .payment-card:hover::before { background: var(--color-gold); }
    .payment-card.selected {
        border-color: #6366f1;
        background: rgba(99,102,241,0.05);
    }
    .payment-card.selected::before { background: #6366f1; }
    
    .card-header {
        display: flex;
        align-items: flex-start;
        gap: 10px;
    }
    .pay-checkbox {
        width: 18px;
        height: 18px;
        margin-top: 2px;
        cursor: pointer;
        accent-color: #6366f1;
        display: none;
    }
    .bulk-mode .pay-checkbox { display: block; }
    
    .card-main { flex: 1; }
    .card-title {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        flex-wrap: wrap;
        gap: 8px;
    }
    .order-id {
        font-size: 15px;
        font-weight: 600;
        color: var(--color-gold);
    }
    .order-date {
        font-size: 12px;
        color: var(--color-text-muted);
    }
    
    .card-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
        gap: 12px;
        margin: 12px 0;
    }
    .card-grid .col .label {
        font-size: 11px;
        color: var(--color-text-muted);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 3px;
    }
    .card-grid .col .value {
        font-size: 14px;
        font-weight: 500;
    }
    .amount {
        color: var(--color-gold);
        font-weight: 700;
        font-size: 15px;
    }
    
    .status-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
    .status-Paid {
        background: rgba(74,222,128,0.1);
        color: var(--color-success);
        border: 1px solid rgba(74,222,128,0.25);
    }
    .status-Unpaid {
        background: rgba(239,68,68,0.1);
        color: #f87171;
        border: 1px solid rgba(239,68,68,0.25);
    }
    
    .method-tag {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 500;
    }
    .method-GCash { background: rgba(59,130,246,0.1); color: #93c5fd; }
    .method-Cash-on-Delivery { background: rgba(16,185,129,0.1); color: #6ee7b7; }
    .method-BPI { background: rgba(0,85,184,0.1); color: #7ab8ff; }
    
    .update-row {
        margin-top: 10px;
        padding-top: 12px;
        border-top: 1px dashed var(--color-border);
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }
    .pay-select {
        padding: 6px 10px;
        background: #0f0f0f;
        border: 1px solid var(--color-border);
        color: var(--color-text);
        border-radius: 6px;
        font-size: 13px;
    }
    .pay-btn {
        padding: 6px 14px;
        background: var(--color-gold);
        color: #000;
        border: none;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
    }
    .pay-btn:hover { background: var(--color-gold-light); }
    
    .empty {
        text-align: center;
        padding: 60px 20px;
        color: var(--color-text-muted);
        font-size: 16px;
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
        }
        .sidebar.show-menu {
            transform: translateX(0);
            box-shadow: 4px 0 20px rgba(0,0,0,0.4);
        }
        .content { padding: 15px; }
        .logo-img { height: 40px; }
        .logo-text { font-size: 16px; }
        .content h1 { font-size: 20px; }
        .graphs-row { grid-template-columns: 1fr; }
        .card-grid { grid-template-columns: 1fr 1fr; gap: 10px; }
        .update-row { flex-direction: column; align-items: flex-start; }
        .update-row form { width: 100%; }
        .pay-select, .pay-btn { width: 100%; margin-top: 6px; }
    }
    @media(max-width: 480px){
        .card-grid { grid-template-columns: 1fr; }
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
        <a href="payments.php" class="active">Payments</a>
        <a href="sales.php">Sales Report</a>
        <a href="reviews.php">Customer Reviews</a>
        <a href="messages.php">Customer Messages</a>
        <a href="../logout.php">Logout</a>
    </div>
    
    <div class="content" id="contentArea">
        <h1>Payment Transactions</h1>
        <p class="desc">Monitor and update payment status — Unpaid orders are shown first</p>
        
        <?php
        $unpaid_count = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM orders WHERE payment_status='Unpaid' OR payment_status IS NULL OR payment_status=''"));
        $paid_count = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM orders WHERE payment_status='Paid'"));
        $all_count = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM orders"));
        ?>
        <div class="tabs">
            <a href="?show=unpaid" class="tab-btn unpaid <?= $status_filter==='unpaid'?'active':'' ?>">
                Unpaid (<?= $unpaid_count ?>)
            </a>
            <a href="?show=paid" class="tab-btn paid <?= $status_filter==='paid'?'active':'' ?>">
                Paid (<?= $paid_count ?>)
            </a>
            <a href="?show=all" class="tab-btn <?= $status_filter==='all'?'active':'' ?>">
                All (<?= $all_count ?>)
            </a>
        </div>
        
        <div class="graphs-row">
            <div class="graph-card">
                <h3>Payment Method — Order Count</h3>
                <?php
                $max_count = max($gcash_count, $cod_count, $bpi_count, 1);
                $methods = [
                    ['name' => 'GCash', 'count' => $gcash_count, 'sales' => $gcash_sales, 'class' => 'bar-gcash'],
                    ['name' => 'Cash on Delivery', 'count' => $cod_count, 'sales' => $cod_sales, 'class' => 'bar-cod'],
                    ['name' => 'BPI Bank Transfer', 'count' => $bpi_count, 'sales' => $bpi_sales, 'class' => 'bar-bpi'],
                ];
                foreach($methods as $m):
                    $percent = round(($m['count'] / $max_count) * 100);
                    $display_name = $m['name'] === 'Cash on Delivery' ? 'COD' : $m['name'];
                ?>
                <div class="bar-row">
                    <div class="bar-label"><?= $display_name ?></div>
                    <div class="bar-wrapper">
                        <div class="bar-fill <?= $m['class'] ?>" style="width: <?= $percent ?>%;">
                            <?php if($percent > 25): ?><?= $m['count'] ?><?php endif; ?>
                        </div>
                        <span class="bar-value"><?= $m['count'] ?> orders</span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="graph-card">
                <h3>Total Sales Amount</h3>
                <div class="sales-row gcash-text">
                    <span>GCash</span>
                    <span>₱<?= number_format($gcash_sales, 2) ?></span>
                </div>
                <div class="sales-row cod-text">
                    <span>Cash on Delivery</span>
                    <span>₱<?= number_format($cod_sales, 2) ?></span>
                </div>
                <div class="sales-row bpi-text">
                    <span>BPI Bank Transfer</span>
                    <span>₱<?= number_format($bpi_sales, 2) ?></span>
                </div>
                <div class="sales-row total">
                    <span>Total</span>
                    <span>₱<?= number_format($total_sales_all, 2) ?></span>
                </div>
            </div>
        </div>
        
        <div class="content-head">
            <span></span>
            <button class="toggle-select-btn" id="toggleSelect">Select Orders</button>
        </div>
        
        <form method="post" id="bulkForm">
        <div class="bulk-menu" id="bulkMenu">
            <div class="bulk-menu-row">
                <span class="select-all-link" id="selectAllLink">Select All</span>
                <label>Mark as:</label>
                <select name="bulk_new_status">
                    <option value="Paid">Paid</option>
                    <option value="Unpaid">Unpaid</option>
                </select>
                <button type="submit" name="bulk_update_payment" class="bulk-btn">Update Selected</button>
            </div>
        </div>
        
        <?php if(mysqli_num_rows($result) === 0): ?>
            <div class="empty">
                No orders found for this status.
            </div>
        <?php else: ?>
            <div class="payments-list bulk-mode-container" id="cardContainer">
            <?php while($p = mysqli_fetch_assoc($result)): 
                $current_status = !empty($p['payment_status']) ? $p['payment_status'] : 'Unpaid';
                $payment_method = !empty($p['payment_method']) ? trim($p['payment_method']) : 'Cash on Delivery';
                
                if(stripos($payment_method, 'bpi') !== false){
                    $method_class = 'BPI';
                } elseif(stripos($payment_method, 'gcash') !== false){
                    $method_class = 'GCash';
                } else {
                    $method_class = 'Cash-on-Delivery';
                }
            ?>
                <div class="payment-card">
                    <div class="card-header">
                        <input type="checkbox" name="selected_orders[]" value="<?= $p['id'] ?>" class="pay-checkbox">
                        <div class="card-main">
                            <div class="card-title">
                                <span class="order-id">Order #<?= $p['id'] ?> — <?= htmlspecialchars($p['customer_name']) ?></span>
                                <span class="order-date"><?= date('F j, Y', strtotime($p['order_date'])) ?></span>
                            </div>
                            
                            <div class="card-grid">
                                <div class="col">
                                    <div class="label">Product</div>
                                    <div class="value"><?= htmlspecialchars($p['product_name']) ?></div>
                                </div>
                                <div class="col">
                                    <div class="label">Total Amount</div>
                                    <div class="value amount">₱<?= number_format($p['total_amount'], 2) ?></div>
                                </div>
                                <div class="col">
                                    <div class="label">Payment Method</div>
                                    <span class="method-tag method-<?= $method_class ?>">
                                        <?= htmlspecialchars($payment_method) ?>
                                    </span>
                                </div>
                                <div class="col">
                                    <div class="label">Status</div>
                                    <span class="status-badge status-<?= $current_status ?>">
                                        <?= $current_status === 'Paid' ? 'PAID' : 'UNPAID' ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="update-row">
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="order_id" value="<?= $p['id'] ?>">
                                    <label style="font-size:13px;color:#888;">Change to:</label>
                                    <select name="payment_status" class="pay-select">
                                        <option value="Unpaid" <?= $current_status==='Unpaid'?'selected':'' ?>>Unpaid</option>
                                        <option value="Paid" <?= $current_status==='Paid'?'selected':'' ?>>Paid</option>
                                    </select>
                                    <button type="submit" name="update_payment" class="pay-btn">Save</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
            </div>
        <?php endif; ?>
        </form>
    </div>
</div>
<script>
const toggleBtn = document.getElementById('toggleSelect');
const bulkMenu = document.getElementById('bulkMenu');
const cardContainer = document.getElementById('cardContainer');
const checkboxes = document.querySelectorAll('.pay-checkbox');
const cards = document.querySelectorAll('.payment-card');
const selectAllLink = document.getElementById('selectAllLink');
let selectMode = false;
toggleBtn.addEventListener('click', function(){
    selectMode = !selectMode;
    bulkMenu.classList.toggle('show', selectMode);
    cardContainer.classList.toggle('bulk-mode', selectMode);
    toggleBtn.classList.toggle('active', selectMode);
    toggleBtn.textContent = selectMode ? 'Cancel' : 'Select Orders';
    
    if(!selectMode){
        checkboxes.forEach((chk, i) => { chk.checked = false; cards[i].classList.remove('selected'); });
    }
});
selectAllLink.addEventListener('click', function(){
    const allChecked = Array.from(checkboxes).every(chk => chk.checked);
    checkboxes.forEach((chk, i) => {
        chk.checked = !allChecked;
        cards[i].classList.toggle('selected', !allChecked);
    });
    this.textContent = allChecked ? 'Select All' : 'Unselect All';
});
checkboxes.forEach((chk, i) => {
    chk.addEventListener('change', function(){
        cards[i].classList.toggle('selected', this.checked);
    });
});
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