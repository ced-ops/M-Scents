<?php
session_start();
include("../config.php");
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== "admin"){
    header("Location: ../login.php");
    exit();
}

if(isset($_POST['bulk_update_status']) && !empty($_POST['selected_orders'])){
    $new_status = mysqli_real_escape_string($conn, $_POST['bulk_new_status']);
    $ids = array_map('intval', $_POST['selected_orders']);
    $id_list = implode(',', $ids);
    mysqli_query($conn, "UPDATE orders SET status='$new_status' WHERE id IN ($id_list)");
    echo "<script>alert('Done!');</script>";
}

if(isset($_POST['update_status'])){
    $oid = intval($_POST['order_id']);
    $status = mysqli_real_escape_string($conn, $_POST['new_status']);
    mysqli_query($conn, "UPDATE orders SET status='$status' WHERE id=$oid");
    echo "<script>alert('Order Status Updated!');</script>";
}

if(isset($_POST['update_payment'])){
    $oid = intval($_POST['order_id']);
    $p_status = mysqli_real_escape_string($conn, $_POST['payment_status']);
    mysqli_query($conn, "UPDATE orders SET payment_status='$p_status' WHERE id=$oid");
    echo "<script>alert('Payment Status Updated to $p_status!');</script>";
}

$pending_count_result = mysqli_query($conn, "SELECT COUNT(*) as total FROM orders WHERE status='Pending'");
$pending_count_row = mysqli_fetch_assoc($pending_count_result);
$pending_count = $pending_count_row['total'];

$status_filter = $_GET['status'] ?? 'all';
$where = "";
if($status_filter !== 'all'){
    $status_filter = mysqli_real_escape_string($conn, $status_filter);
    $where = "WHERE o.status='$status_filter'";
}

$orders = mysqli_query($conn, "
    SELECT o.*, ca.contact_number, p.image as product_image
    FROM orders o
    LEFT JOIN customer_addresses ca ON o.customer_name = ca.customer_name
    LEFT JOIN products p ON o.product_name = p.perfume_name
    $where 
    ORDER BY o.order_date DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Orders — M&Scents</title>
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
        --color-bg-card-hover: #242424;
        --color-border: #2a2a2a;
        --color-text: #ffffff;
        --color-text-muted: #9ca3af;
        --color-success: #10b981;
        --color-success-light: #34d399;
        --color-warning: #f59e0b;
        --color-warning-light: #fbbf24;
        --color-danger: #ef4444;
        --color-danger-light: #f87171;
        --color-info: #3b82f6;
        --color-info-light: #60a5fa;
        --color-purple: #8b5cf6;
        --color-purple-light: #a78bfa;
        --color-accent: #6366f1;
        --sidebar-width: 250px;
    }
    * { margin: 0; padding: 0; box-sizing: border-box; }
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
        box-shadow: 0 2px 8px rgba(0,0,0,0.3);
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
    .logo-img { height: 45px; width: auto; }
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
        gap: 8px;
        color: var(--color-text-muted);
        padding: 10px 14px;
        margin: 5px 0;
        text-decoration: none;
        border-radius: 7px;
        font-weight: 500;
        font-size: 14px;
        transition: all 0.25s;
        position: relative;
    }
    .sidebar a:hover,
    .sidebar a.active {
        background: rgba(212,175,55,0.1);
        color: var(--color-gold);
    }
    .pending-badge {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        background: linear-gradient(135deg, var(--color-danger), #dc2626);
        color: #fff;
        font-size: 11px;
        font-weight: 700;
        min-width: 20px;
        height: 20px;
        line-height: 20px;
        text-align: center;
        border-radius: 10px;
        box-shadow: 0 0 0 2px var(--color-bg-card);
        animation: pulse 2s infinite;
    }
    @keyframes pulse {
        0%, 100% { box-shadow: 0 0 0 2px var(--color-bg-card); }
        50% { box-shadow: 0 0 0 3px rgba(239,68,68,0.3); }
    }
    .content { padding: 20px; }
    .content-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;
    }
    .content h1 { 
        font-size: 26px; 
        background: linear-gradient(135deg, var(--color-gold), var(--color-gold-light));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    .desc { color: var(--color-text-muted); font-size: 14px; margin-top: 4px; }
    .toggle-select-btn {
        background: linear-gradient(135deg, rgba(99,102,241,0.15), rgba(139,92,246,0.1));
        border: 1px solid rgba(99,102,241,0.3);
        color: var(--color-accent);
        padding: 8px 14px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 500;
        cursor: pointer;
        width: auto;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s;
    }
    .toggle-select-btn:hover,
    .toggle-select-btn.active {
        border-color: var(--color-accent);
        background: rgba(99,102,241,0.2);
        transform: translateY(-1px);
    }
    .bulk-menu {
        display: none;
        background: linear-gradient(135deg, rgba(99,102,241,0.08), rgba(139,92,246,0.05));
        border: 1px solid rgba(99,102,241,0.2);
        border-radius: 10px;
        padding: 16px;
        margin-bottom: 18px;
        animation: slideDown 0.25s ease;
    }
    .bulk-menu.show { display: block; }
    .bulk-menu-row {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: center;
    }
    .bulk-menu label { font-size: 13px; color: #ccc; }
    .bulk-menu select,
    .bulk-menu button {
        padding: 8px 12px;
        border-radius: 7px;
        font-size: 13px;
        font-weight: 500;
    }
    .bulk-menu select {
        background: var(--color-bg-dark);
        border: 1px solid var(--color-border);
        color: #fff;
    }
    .bulk-btn {
        background: linear-gradient(135deg, var(--color-accent), var(--color-purple));
        color: #fff;
        border: none;
        cursor: pointer;
        box-shadow: 0 2px 8px rgba(99,102,241,0.25);
    }
    .bulk-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(99,102,241,0.35);
    }
    .select-all-link {
        font-size: 13px;
        color: var(--color-accent);
        cursor: pointer;
        text-decoration: underline;
    }
    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-8px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .filters {
        margin-bottom: 18px;
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }
    .filters a {
        padding: 8px 14px;
        background: var(--color-bg-card);
        color: var(--color-text-muted);
        text-decoration: none;
        border-radius: 8px;
        border: 1px solid var(--color-border);
        font-size: 13px;
        font-weight: 500;
        transition: all 0.2s;
    }
    .filters a:hover {
        border-color: var(--color-gold);
        color: var(--color-gold-light);
    }
    .filters a.active {
        border-color: var(--color-gold);
        color: #fff;
        background: linear-gradient(135deg, rgba(212,175,55,0.2), rgba(240,201,77,0.1));
    }
    .filters a.active[href*="Pending"] {
        border-color: var(--color-warning);
        color: var(--color-warning-light);
        background: linear-gradient(135deg, rgba(245,158,11,0.15), rgba(251,191,36,0.08));
    }
    .filters a.active[href*="Confirmed"] {
        border-color: var(--color-info);
        color: var(--color-info-light);
        background: linear-gradient(135deg, rgba(59,130,246,0.15), rgba(96,165,250,0.08));
    }
    .filters a.active[href*="Preparing"] {
        border-color: var(--color-info);
        color: var(--color-info-light);
        background: linear-gradient(135deg, rgba(59,130,246,0.15), rgba(96,165,250,0.08));
    }
    .filters a.active[href*="Shipped"] {
        border-color: var(--color-purple);
        color: var(--color-purple-light);
        background: linear-gradient(135deg, rgba(139,92,246,0.15), rgba(167,139,250,0.08));
    }
    .filters a.active[href*="Delivered"] {
        border-color: var(--color-success);
        color: var(--color-success-light);
        background: linear-gradient(135deg, rgba(16,185,129,0.15), rgba(52,211,153,0.08));
    }
    .order-card {
        background: var(--color-bg-card);
        border: 1px solid var(--color-border);
        border-radius: 12px;
        padding: 16px;
        margin-bottom: 12px;
        transition: all 0.25s ease;
        position: relative;
        overflow: hidden;
    }
    .order-card::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 3px;
        background: var(--color-border);
        transition: all 0.25s;
    }
    .order-card:hover {
        background: var(--color-bg-card-hover);
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(0,0,0,0.3);
    }
    .order-card:hover::before {
        background: var(--color-gold);
    }
    .order-card.selected {
        border-color: var(--color-accent);
        background: rgba(99,102,241,0.05);
    }
    .order-card.selected::before {
        background: var(--color-accent);
    }
    .order-header {
        display: flex;
        align-items: flex-start;
        gap: 10px;
    }
    .order-checkbox {
        width: 18px;
        height: 18px;
        margin-top: 2px;
        cursor: pointer;
        accent-color: var(--color-accent);
        display: none;
    }
    .bulk-mode .order-checkbox {
        display: block;
    }
    .order-info h3 { 
        font-size: 15px; 
        color: var(--color-gold); 
        font-weight: 600;
    }
    .order-date { 
        font-size: 12px; 
        color: var(--color-text-muted); 
        margin-top: 2px; 
    }
    .grid-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: 12px;
        margin: 12px 0;
    }
    .grid-col {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .grid-col .label {
        font-size: 11px;
        color: var(--color-text-muted);
        margin-bottom: 4px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .grid-col .value { font-size: 14px; font-weight: 500; }
    .amount {
        color: var(--color-gold);
        font-weight: 700;
        font-size: 15px;
    }
    .status-badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        letter-spacing: 0.3px;
    }
    .stat-Pending { 
        background: linear-gradient(135deg, rgba(245,158,11,0.18), rgba(251,191,36,0.1)); 
        color: #fcd34d; 
        border: 1px solid rgba(245,158,11,0.25);
    }
    .stat-Confirmed { 
        background: linear-gradient(135deg, rgba(59,130,246,0.18), rgba(96,165,250,0.1)); 
        color: #93c5fd; 
        border: 1px solid rgba(59,130,246,0.25);
    }
    .stat-Preparing { 
        background: linear-gradient(135deg, rgba(59,130,246,0.18), rgba(96,165,250,0.1)); 
        color: #93c5fd; 
        border: 1px solid rgba(59,130,246,0.25);
    }
    .stat-Shipped { 
        background: linear-gradient(135deg, rgba(139,92,246,0.18), rgba(167,139,250,0.1)); 
        color: #c4b5fd; 
        border: 1px solid rgba(139,92,246,0.25);
    }
    .stat-Delivered { 
        background: linear-gradient(135deg, rgba(16,185,129,0.18), rgba(52,211,153,0.1)); 
        color: #6ee7b7; 
        border: 1px solid rgba(16,185,129,0.25);
    }
    .stat-Cancelled { 
        background: linear-gradient(135deg, rgba(239,68,68,0.18), rgba(248,113,113,0.1)); 
        color: #fca5a5; 
        border: 1px solid rgba(239,68,68,0.25);
    }
    .pay-Unpaid { 
        color: #fca5a5; 
        font-weight: 600;
        background: rgba(239,68,68,0.1);
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 12px;
    }
    .pay-Paid { 
        color: #6ee7b7; 
        font-weight: 600;
        background: rgba(16,185,129,0.1);
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 12px;
    }
    .full-details {
        margin-top: 12px;
        padding-top: 12px;
        border-top: 1px dashed var(--color-border);
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        flex-wrap: wrap;
        gap: 10px;
    }
    .detail-line { font-size: 13px; line-height: 1.7; margin: 3px 0; }
    .detail-line strong { color: var(--color-text-muted); display: inline-block; min-width: 100px; }
    .detail-line span { color: #fff; }
    .gcash-ref { 
        color: var(--color-info-light); 
        font-weight: 600; 
        font-family: monospace;
        background: rgba(59,130,246,0.1);
        padding: 1px 6px;
        border-radius: 4px;
    }
    .copy-btn {
        background: linear-gradient(135deg, var(--color-accent), var(--color-purple));
        color: #fff;
        border: none;
        padding: 7px 14px;
        border-radius: 7px;
        font-size: 12px;
        font-weight: 500;
        cursor: pointer;
        width: auto;
        margin: 0;
        box-shadow: 0 2px 6px rgba(99,102,241,0.2);
        transition: all 0.2s;
    }
    .copy-btn:hover { 
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(99,102,241,0.3);
    }
    .copy-btn.copied { 
        background: linear-gradient(135deg, var(--color-success), #22c55e); 
    }
    .empty { text-align: center; padding: 80px 20px; color: var(--color-text-muted); }
    .empty h3 { color: var(--color-text); margin-bottom: 8px; }
    .overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.6);
        backdrop-filter: blur(4px);
        z-index: 5;
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(12px); }
        to { opacity: 1; transform: translateY(0); }
    }
    @media(max-width: 768px){
        .menu-toggle { display: flex; }
        .admin-layout { grid-template-columns: 1fr; position: relative; }
        .sidebar {
            position: fixed;
            top: 69px;
            left: 0;
            bottom: 0;
            width: 260px;
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
        .content h1 { font-size: 22px; }
        .grid-row { grid-template-columns: 1fr 1fr; gap: 10px; }
        .full-details { flex-direction: column; align-items: flex-start; }
        .copy-btn { width: 100%; margin-top: 8px; }
    }
    @media(max-width: 480px){
        .grid-row { grid-template-columns: 1fr; }
        .filters a { font-size: 12px; padding: 7px 10px; }
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
        <a href="orders.php" class="active">
            Orders
            <?php if($pending_count > 0): ?>
            <span class="pending-badge"><?= $pending_count ?></span>
            <?php endif; ?>
        </a>
        <a href="products.php">Manage Products</a>
        <a href="payments.php">Payments</a>
        <a href="sales.php">Sales Report</a>
        <a href="reviews.php">Customer Reviews</a>
        <a href="messages.php">Customer Messages</a>
        <a href="../logout.php">Logout</a>
    </div>
    <div class="content" id="contentArea">
        <div class="content-head">
            <div>
                <h1>Manage Orders</h1>
                <p class="desc">View and update customer orders</p>
            </div>
            <button class="toggle-select-btn" id="toggleSelect">Select Orders</button>
        </div>
        <form method="post" id="bulkForm">
        <div class="bulk-menu" id="bulkMenu">
            <div class="bulk-menu-row">
                <span class="select-all-link" id="selectAllLink">Select All</span>
                <label>Change status to:</label>
                <select name="bulk_new_status">
                    <option value="Confirmed">Confirmed</option>
                    <option value="Preparing">Preparing</option>
                    <option value="Shipped">Shipped</option>
                    <option value="Delivered">Delivered</option>
                    <option value="Pending">Pending</option>
                    <option value="Cancelled">Cancelled</option>
                </select>
                <button type="submit" name="bulk_update_status" class="bulk-btn">Update Selected</button>
            </div>
        </div>
        <div class="filters">
            <a href="orders.php?status=all" class="<?= $status_filter==='all'?'active':'' ?>">All</a>
            <a href="orders.php?status=Pending" class="<?= $status_filter==='Pending'?'active':'' ?>">Pending</a>
            <a href="orders.php?status=Confirmed" class="<?= $status_filter==='Confirmed'?'active':'' ?>">Confirmed</a>
            <a href="orders.php?status=Preparing" class="<?= $status_filter==='Preparing'?'active':'' ?>">Preparing</a>
            <a href="orders.php?status=Shipped" class="<?= $status_filter==='Shipped'?'active':'' ?>">Shipped</a>
            <a href="orders.php?status=Delivered" class="<?= $status_filter==='Delivered'?'active':'' ?>">Delivered</a>
        </div>
        <?php if(mysqli_num_rows($orders) === 0): ?>
            <div class="empty">
                <h3>No orders found</h3>
                <p>Orders will appear here once placed by customers.</p>
            </div>
        <?php else: ?>
            <?php while($o = mysqli_fetch_assoc($orders)): 
                $contact_display = !empty($o['contact_number']) ? $o['contact_number'] : 'Not provided';
                $delivery_address = !empty($o['delivery_address']) ? $o['delivery_address'] : 'Not provided';
                $payment_method = !empty($o['payment_method']) ? $o['payment_method'] : 'Cash on Delivery';
                $gcash_ref = !empty($o['gcash_ref_number']) ? $o['gcash_ref_number'] : '';
                
                $copy_text = "Customer Name: " . htmlspecialchars($o['customer_name']) . "\n"
                         . "Contact Number: " . htmlspecialchars($contact_display) . "\n"
                         . "Delivery Address: " . htmlspecialchars($delivery_address) . "\n"
                         . "Payment Method: " . htmlspecialchars($payment_method);
            ?>
            <div class="order-card">
                <div class="order-header">
                    <input type="checkbox" name="selected_orders[]" value="<?= $o['id'] ?>" class="order-checkbox">
                    <div class="order-info">
                        <h3>Order #<?= $o['id'] ?> — <?= htmlspecialchars($o['customer_name']) ?></h3>
                        <span class="order-date"><?= date('F j, Y — g:i A', strtotime($o['order_date'])) ?></span>
                    </div>
                </div>
                <div class="grid-row">
                    <div class="grid-col">
                        <?php if(!empty($o['product_image'])): ?>
                            <img src="../images/<?= htmlspecialchars($o['product_image']) ?>" 
                                 alt="<?= htmlspecialchars($o['product_name']) ?>"
                                 style="width:50px;height:50px;object-fit:cover;border-radius:8px;border:1px solid var(--color-border);flex-shrink:0;">
                        <?php else: ?>
                            <div style="width:50px;height:50px;background:var(--color-bg-dark);border-radius:8px;display:flex;align-items:center;justify-content:center;border:1px solid var(--color-border);flex-shrink:0;">Box</div>
                        <?php endif; ?>
                        <div>
                            <div class="label">Product</div>
                            <div class="value"><?= htmlspecialchars($o['product_name']) ?> × <?= $o['quantity'] ?></div>
                        </div>
                    </div>
                    <div class="grid-col">
                        <div>
                            <div class="label">Total Amount</div>
                            <div class="value amount">₱<?= number_format($o['total_amount'], 2) ?></div>
                        </div>
                    </div>
                    <div class="grid-col">
                        <div>
                            <div class="label">Order Status</div>
                            <span class="status-badge stat-<?= $o['status'] ?>">
                                <?php 
                                    $s = $o['status'];
                                    $labels = [
                                        'Pending' => 'Pending',
                                        'Confirmed' => 'Confirmed',
                                        'Preparing' => 'Preparing',
                                        'Shipped' => 'Shipped',
                                        'Delivered' => 'Delivered',
                                        'Cancelled' => 'Cancelled'
                                    ];
                                    echo $labels[$s] ?? $s;
                                ?>
                            </span>
                        </div>
                    </div>
                    <div class="grid-col">
                        <div>
                            <div class="label">Payment Status</div>
                            <?php 
                                $p = !empty($o['payment_status']) ? $o['payment_status'] : 'Unpaid';
                                echo "<span class='pay-$p'>" . ($p === 'Paid' ? 'PAID' : 'UNPAID') . "</span>";
                            ?>
                        </div>
                    </div>
                </div>
                <div class="full-details">
                    <div>
                        <div class="detail-line"><strong>Customer:</strong> <span><?= htmlspecialchars($o['customer_name']) ?></span></div>
                        <div class="detail-line"><strong>Contact:</strong> <span><?= htmlspecialchars($contact_display) ?></span></div>
                        <div class="detail-line"><strong>Address:</strong> <span><?= htmlspecialchars($delivery_address) ?></span></div>
                        <div class="detail-line"><strong>Payment Method:</strong> <span><?= htmlspecialchars($payment_method) ?></span></div>
                        <?php if(!empty($gcash_ref)): ?>
                        <div class="detail-line"><strong>GCash Ref:</strong> <span class="gcash-ref"><?= htmlspecialchars($gcash_ref) ?></span></div>
                        <?php endif; ?>
                    </div>
                    <button class="copy-btn" data-text="<?= htmlspecialchars($copy_text) ?>" type="button">Copy Details</button>
                </div>
            </div>
            <?php endwhile; ?>
        <?php endif; ?>
        </form>
    </div>
</div>
<script>
const toggleBtn = document.getElementById('toggleSelect');
const bulkMenu = document.getElementById('bulkMenu');
const contentArea = document.getElementById('contentArea');
const checkboxes = document.querySelectorAll('.order-checkbox');
const cards = document.querySelectorAll('.order-card');
const selectAllLink = document.getElementById('selectAllLink');
let selectMode = false;

toggleBtn.addEventListener('click', function(){
    selectMode = !selectMode;
    bulkMenu.classList.toggle('show', selectMode);
    contentArea.classList.toggle('bulk-mode', selectMode);
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

document.querySelectorAll('.copy-btn').forEach(btn => {
    btn.addEventListener('click', async function(){
        const text = this.getAttribute('data-text');
        try { await navigator.clipboard.writeText(text); } 
        catch {
            const ta = document.createElement('textarea');
            ta.value = text;
            document.body.appendChild(ta); ta.select(); document.execCommand('copy');
            document.body.removeChild(ta);
        }
        const orig = this.textContent;
        this.textContent = 'Copied!';
        this.classList.add('copied');
        setTimeout(() => { this.textContent = orig; this.classList.remove('copied'); }, 2500);
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