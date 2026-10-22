<?php
session_start();
include("config.php");
if(!isset($_SESSION['user_id'])){
    echo "<script>alert('Please login first to view your cart.'); location='login.php';</script>";
    exit();
}
$user_id = $_SESSION['user_id'];
$customer_name = $_SESSION['fullname'];
$cart_count = 0;
$count_q = mysqli_query($conn, "SELECT COUNT(*) as total FROM cart WHERE user_id='$user_id'");
if($count_row = mysqli_fetch_assoc($count_q)){
    $cart_count = (int)$count_row['total'];
}
if(isset($_GET['remove'])){
    $cart_id = intval($_GET['remove']);
    mysqli_query($conn, "DELETE FROM cart WHERE id='$cart_id' AND user_id='$user_id'");
    header("Location: cart.php");
    exit();
}
if(isset($_POST['update_qty'])){
    $cart_id = intval($_POST['cart_id']);
    $new_qty = intval($_POST['quantity']);
    $item_q = mysqli_query($conn, "SELECT product_id FROM cart WHERE id='$cart_id' AND user_id='$user_id'");
    $item_row = mysqli_fetch_assoc($item_q);
    $pid = $item_row['product_id'];
    $stock_q = mysqli_query($conn, "SELECT stock FROM products WHERE id='$pid'");
    $stock_row = mysqli_fetch_assoc($stock_q);
    $available = $stock_row['stock'];
    if($new_qty < 1){
        mysqli_query($conn, "DELETE FROM cart WHERE id='$cart_id' AND user_id='$user_id'");
    }elseif($new_qty > $available){
        echo "<script>alert('Only $available left available!');</script>";
    }else{
        mysqli_query($conn, "UPDATE cart SET quantity='$new_qty' WHERE id='$cart_id' AND user_id='$user_id'");
    }
    header("Location: cart.php");
    exit();
}

$shipping_rates = [
    'Luzon' => 50.00,
    'Visayas' => 70.00,
    'Mindanao' => 100.00
];

if(isset($_POST['place_order'])){
    $address_id = intval($_POST['delivery_address_id']);
    $payment_method = $_POST['payment_method'];
    $shipping_region = $_POST['shipping_region'] ?? '';
    $gcash_ref = trim($_POST['gcash_ref_number'] ?? '');
    $bpi_ref = trim($_POST['bpi_ref_number'] ?? '');

    if(empty($address_id)){
        echo "<script>alert('Please select a delivery address.');</script>";
        exit();
    }
    if(empty($shipping_region) || !isset($shipping_rates[$shipping_region])){
        echo "<script>alert('Please select your shipping region.');</script>";
        exit();
    }
    if($payment_method === 'GCash' && !preg_match('/^\d{13}$/', $gcash_ref)){
        echo "<script>alert('GCash Reference Number must be exactly 13 digits.');</script>";
        exit();
    }
    if($payment_method === 'BPI Bank Transfer' && !preg_match('/^\d{13}$/', $bpi_ref)){
        echo "<script>alert('BPI Reference Number must be exactly 13 digits.');</script>";
        exit();
    }

    $delivery_fee = $shipping_rates[$shipping_region];

    $addr_q = mysqli_query($conn, "SELECT * FROM customer_addresses WHERE id='$address_id' AND customer_name='$customer_name'");
    $address = mysqli_fetch_assoc($addr_q);
    $full_delivery_address = $address['address_label'].": ".$address['full_address'].", ".$address['city'].", ".$address['province']." ".$address['zip_code'];

    $result = mysqli_query($conn, "SELECT cart.*, products.stock as available_stock 
        FROM cart LEFT JOIN products ON cart.product_id = products.id 
        WHERE cart.user_id='$user_id'");
    $all_ok = true;
    $items_to_order = [];
    $subtotal = 0;
    while($item = mysqli_fetch_assoc($result)){
        if($item['quantity'] > $item['available_stock']){
            echo "<script>alert('Not enough stock for {$item['name']}! Only {$item['available_stock']} left.');</script>";
            $all_ok = false;
            break;
        }
        $items_to_order[] = $item;
        $subtotal += $item['price'] * $item['quantity'];
    }
    if(!$all_ok) exit();

    $grand_total = $subtotal + $delivery_fee;

    foreach($items_to_order as $item){
        $item_subtotal = $item['price'] * $item['quantity'];
        mysqli_query($conn, "UPDATE products SET stock = stock - {$item['quantity']} WHERE id = '{$item['product_id']}'");
        mysqli_query($conn, "INSERT INTO orders (
            customer_name, product_id, product_name, quantity, price, subtotal, delivery_fee, total_amount,
            delivery_address, payment_method, gcash_ref_number, bpi_ref_number, order_date, status, payment_status
        ) VALUES (
            '$customer_name', '{$item['product_id']}', '".mysqli_real_escape_string($conn,$item['name'])."', 
            '{$item['quantity']}', '{$item['price']}', '$item_subtotal', '$delivery_fee', '$grand_total',
            '".mysqli_real_escape_string($conn,$full_delivery_address)."', '$payment_method', '$gcash_ref', '$bpi_ref', NOW(), 'Pending', 'Unpaid'
        )");
    }
    mysqli_query($conn, "DELETE FROM cart WHERE user_id='$user_id'");
    echo "<script>alert('Order placed successfully! Shipping Fee: ₱".number_format($delivery_fee,2)." — Total: ₱".number_format($grand_total,2)."'); location='customer/orders.php';</script>";
    exit();
}

$addresses = [];
$addr_res = mysqli_query($conn, "SELECT * FROM customer_addresses WHERE customer_name='$customer_name' ORDER BY is_default DESC, id DESC");
while($a = mysqli_fetch_assoc($addr_res)) $addresses[] = $a;

$result = mysqli_query($conn, "SELECT cart.*, products.stock as available_stock 
    FROM cart LEFT JOIN products ON cart.product_id = products.id 
    WHERE cart.user_id='$user_id'");
$cart_items = [];
$subtotal = 0;
while($row = mysqli_fetch_assoc($result)){
    $cart_items[] = $row;
    $subtotal += $row['price'] * $row['quantity'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart | M&Scents</title>
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
        --color-bg-hover: #242424;
        --color-border: #2a2a2a;
        --color-text: #e5e5e5;
        --color-text-muted: #888888;
        --color-success: #4ade80;
        --color-danger: #ef4444;
        --color-accent-blue: #3b82f6;
        --color-cart-red: #ff3b30;
        --color-bpi: #0055b8;
    }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    html, body { width: 100%; overflow-x: hidden; }
    body {
        font-family: var(--font-body);
        background-color: var(--color-bg-dark);
        color: var(--color-text);
        line-height: 1.6;
        min-height: 100vh;
    }
    h1, h2, h3, h4 { font-family: var(--font-heading); color: #ffffff; }
    nav {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 20px;
        background: rgba(0,0,0,0.85);
        border-bottom: 1px solid var(--color-border);
        position: sticky;
        top: 0;
        z-index: 99;
        backdrop-filter: blur(10px);
        flex-wrap: wrap;
        gap: 10px;
    }
    nav .logo { height: 45px; width: auto; }
    nav ul {
        display: flex;
        list-style: none;
        gap: 12px;
        align-items: center;
        flex-wrap: wrap;
    }
    nav ul li a {
        color: var(--color-text-muted);
        text-decoration: none;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.25s ease;
        padding-bottom: 4px;
        border-bottom: 2px solid transparent;
    }
    nav ul li a:hover,
    nav ul li a.active {
        color: var(--color-gold);
        border-bottom-color: var(--color-gold);
    }
    .floating-msg, .floating-cart {
        position: fixed;
        bottom: 20px;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--color-gold), var(--color-gold-light));
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        box-shadow: 0 4px 15px rgba(212,175,55,0.35);
        z-index: 900;
        transition: all 0.3s ease;
        overflow: visible;
        border: 2px solid rgba(212,175,55,0.4);
    }
    .floating-cart { right: 15px; }
    .floating-msg { right: 75px; }
    .floating-msg img, .floating-cart img {
        width: 24px;
        height: 24px;
        object-fit: contain;
    }
    .cart-badge {
        position: absolute;
        top: -8px;
        right: -8px;
        background: var(--color-cart-red);
        color: #fff;
        font-size: 11px;
        font-weight: 700;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 2px solid #000;
        box-shadow: 0 2px 4px rgba(0,0,0,0.3);
        line-height: 1;
    }
    .floating-cart.empty .cart-badge { display: none; }
    .floating-msg:hover, .floating-cart:hover {
        transform: translateY(-3px) scale(1.05);
        box-shadow: 0 6px 20px rgba(212,175,55,0.5);
    }
    .cart-container {
        max-width: 1100px;
        margin: 30px auto;
        padding: 0 15px;
    }
    .cart-container h1 {
        font-size: clamp(26px, 5vw, 32px);
        margin-bottom: 8px;
    }
    .cart-sub {
        color: var(--color-text-muted);
        margin-bottom: 30px;
        font-size: 15px;
    }
    .empty-cart-box {
        text-align: center;
        padding: 60px 20px;
        background: var(--color-bg-card);
        border-radius: 16px;
        border: 1px solid var(--color-border);
    }
    .empty-cart-box h2 { font-size: 22px; margin-bottom: 12px; }
    .empty-cart-box p { color: var(--color-text-muted); margin-bottom: 24px; }
    .btn-gold {
        display: inline-block;
        padding: 12px 28px;
        background: linear-gradient(135deg, var(--color-gold), var(--color-gold-light));
        color: #000;
        text-decoration: none;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    .btn-gold:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(212,175,55,0.3);
    }
    .cart-items {
        background: var(--color-bg-card);
        border-radius: 14px;
        border: 1px solid var(--color-border);
        overflow: hidden;
        margin-bottom: 25px;
    }
    .cart-row {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr 1fr 0.8fr;
        padding: 16px 20px;
        align-items: center;
        border-bottom: 1px solid var(--color-border);
        transition: background 0.2s ease;
        gap: 10px;
    }
    .cart-row:hover { background: var(--color-bg-hover); }
    .cart-row.header {
        background: rgba(212,175,55,0.06);
        font-weight: 600;
        color: var(--color-gold);
        font-size: 14px;
    }
    .cart-prod {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .cart-prod img {
        width: 60px;
        height: 60px;
        object-fit: cover;
        border-radius: 8px;
    }
    .cart-prod h4 {
        font-family: var(--font-body);
        font-size: 15px;
        margin-bottom: 2px;
    }
    .cart-prod p {
        color: var(--color-text-muted);
        font-size: 13px;
    }
    .cart-price, .cart-sub {
        font-weight: 600;
        font-size: 15px;
    }
    .cart-qty input {
        width: 60px;
        padding: 8px 6px;
        background: var(--color-bg-dark);
        border: 1px solid var(--color-border);
        color: #fff;
        border-radius: 6px;
        text-align: center;
        font-size: 15px;
    }
    .btn-remove {
        color: var(--color-danger);
        text-decoration: none;
        font-size: 14px;
        font-weight: 500;
    }
    .btn-remove:hover { opacity: 0.7; }
    .stock-warning {
        color: #facc15;
        font-size: 12px;
        margin-top: 4px;
    }
    .checkout-box {
        background: var(--color-bg-card);
        border-radius: 14px;
        border: 1px solid var(--color-border);
        padding: 25px 20px;
    }
    .checkout-box h3 {
        font-size: 20px;
        margin-bottom: 22px;
        color: var(--color-gold);
    }
    .form-group { margin-bottom: 18px; }
    .form-group label {
        display: block;
        font-size: 14px;
        font-weight: 500;
        margin-bottom: 8px;
        color: var(--color-text-muted);
    }
    .form-group select, .form-group input {
        width: 100%;
        padding: 12px 14px;
        background: var(--color-bg-dark);
        border: 1px solid var(--color-border);
        color: #fff;
        border-radius: 8px;
        font-size: 15px;
        transition: border 0.25s ease;
    }
    .form-group select:focus, .form-group input:focus {
        outline: none;
        border-color: var(--color-gold);
    }
    .form-group input.error {
        border-color: var(--color-danger);
        box-shadow: 0 0 0 2px rgba(239,68,68,0.15);
    }
    .hint-text {
        font-size: 12px;
        color: #666;
        margin-top: 6px;
    }
    .payment-option {
        display: flex;
        align-items: flex-start;
        padding: 16px 14px;
        background: var(--color-bg-dark);
        border: 1px solid var(--color-border);
        border-radius: 8px;
        margin-bottom: 10px;
        cursor: pointer;
        transition: all 0.25s ease;
    }
    .payment-option:hover { border-color: var(--color-gold); }
    .payment-option.selected {
        border-color: var(--color-gold);
        background: rgba(212,175,55,0.06);
    }
    .payment-option input {
        width: auto;
        margin-right: 12px;
        margin-top: 2px;
    }
    .payment-option label {
        cursor: pointer;
        flex: 1;
        font-size: 14px;
        line-height: 1.5;
    }
    .payment-details-box {
        padding: 18px;
        border-radius: 8px;
        margin-top: 12px;
        display: none;
        animation: fadeIn 0.3s ease;
    }
    .payment-details-box.show { display: block; }
    .gcash-box {
        background: rgba(59,130,246,0.06);
        border: 1px solid rgba(59,130,246,0.2);
    }
    .bpi-box {
        background: rgba(0,85,184,0.06);
        border: 1px solid rgba(0,85,184,0.2);
    }
    .payment-details-box p {
        font-size: 14px;
        color: var(--color-text-muted);
        margin-bottom: 8px;
    }
    .payment-details-box strong { color: #fff; }
    .payment-details-box img {
        max-width: 160px;
        border-radius: 8px;
        margin: 8px 0;
    }
    .subtotal-row, .shipping-row, .grandtotal-row {
        display: flex;
        justify-content: space-between;
        padding: 10px 0;
    }
    .subtotal-row {
        border-bottom: 1px dashed var(--color-border);
        color: var(--color-text-muted);
    }
    .shipping-row {
        color: var(--color-text-muted);
    }
    .grandtotal-row {
        border-top: 1px solid var(--color-border);
        margin-top: 8px;
        padding-top: 16px;
        font-size: 22px;
        font-weight: 700;
    }
    .grandtotal-row span:last-child {
        color: var(--color-gold);
        font-family: var(--font-heading);
    }
    .btn-place {
        width: 100%;
        padding: 15px;
        background: linear-gradient(135deg, var(--color-gold), var(--color-gold-light));
        color: #000;
        border: none;
        border-radius: 8px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        font-family: var(--font-body);
        margin-top: 20px;
    }
    .btn-place:disabled {
        background: #444;
        color: #888;
        cursor: not-allowed;
        transform: none;
    }
    .btn-place:not(:disabled):hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(212,175,55,0.3);
    }
    .alert-warning {
        background: rgba(212,175,55,0.08);
        border: 1px solid rgba(212,175,55,0.25);
        padding: 14px 16px;
        border-radius: 8px;
        color: #f0c94d;
        margin-bottom: 20px;
        font-size: 14px;
        line-height: 1.5;
    }
    .alert-warning a {
        color: var(--color-gold);
        font-weight: 600;
        text-decoration: underline;
    }
    @keyframes fadeIn {
        from { opacity:0; transform:translateY(-8px); }
        to { opacity:1; transform:translateY(0); }
    }
    @media (max-width: 900px) {
        .cart-row {
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        .cart-row.header { display: none; }
        .cart-prod { grid-column: 1 / -1; border-bottom: 1px dashed var(--color-border); padding-bottom: 10px; }
    }
    @media (max-width: 600px) {
        nav { padding: 10px 15px; }
        nav ul { gap: 8px; }
        nav ul li a { font-size: 13px; }
        .cart-row {
            grid-template-columns: 1fr 1fr;
            padding: 14px 12px;
        }
        .cart-qty input { width: 50px; }
        .checkout-box { padding: 20px 15px; }
        .grandtotal-row { font-size: 18px; }
        .floating-cart, .floating-msg {
            width: 44px; height: 44px;
        }
        .floating-cart img, .floating-msg img { width: 20px; height: 20px; }
        .floating-cart { right: 10px; }
        .floating-msg { right: 60px; }
        .cart-badge { font-size: 10px; width: 18px; height: 18px; top: -6px; right: -6px; }
    }
    @media (max-width: 400px) {
        nav ul { justify-content: center; width: 100%; }
        .cart-row { grid-template-columns: 1fr; }
    }
    </style>
</head>
<body>
<nav>
    <img src="images/logo.png" class="logo" alt="M&Scents Logo">
    <ul>
        <li><a href="customer/home.php">Home</a></li>
        <li><a href="collection.php">Collection</a></li>
        <li><a href="customer/dashboard.php">Dashboard</a></li>
        <li><a href="logout.php">Logout</a></li>
    </ul>
</nav>
<a href="customer/messages.php" class="floating-msg" title="Messages">
    <img src="images/message.png" alt="Messages">
</a>
<a href="cart.php" class="floating-cart <?php echo ($cart_count == 0) ? 'empty' : ''; ?>" title="Your Cart">
    <img src="images/cart.png" alt="Cart">
    <?php if($cart_count > 0): ?>
        <span class="cart-badge"><?php echo $cart_count; ?></span>
    <?php endif; ?>
</a>
<div class="cart-container">
    <h1>Shopping Cart</h1>
    <p class="cart-sub">Review your items and complete your order</p>
    <?php if(empty($cart_items)): ?>
        <div class="empty-cart-box">
            <h2>Your cart is empty</h2>
            <p>Add some perfumes to get started with your order</p>
            <a href="collection.php" class="btn-gold">Browse Collection</a>
        </div>
    <?php else: ?>
    <div class="cart-items">
        <div class="cart-row header">
            <div>Product</div>
            <div>Price</div>
            <div>Quantity</div>
            <div>Subtotal</div>
            <div></div>
        </div>
        <?php foreach($cart_items as $item):
            $item_subtotal = $item['price'] * $item['quantity'];
            $is_over_stock = $item['quantity'] > $item['available_stock'];
        ?>
        <div class="cart-row">
            <div class="cart-prod">
                <img src="images/<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                <div>
                    <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                    <p><?php echo htmlspecialchars($item['size']); ?></p>
                    <?php if($is_over_stock): ?>
                        <div class="stock-warning">Only <?php echo $item['available_stock']; ?> left available!</div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="cart-price">₱<?php echo number_format($item['price'], 2); ?></div>
            <div class="cart-qty">
                <form method="post" style="display:flex; gap:4px; align-items:center;">
                    <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                    <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" onchange="this.form.submit()">
                    <input type="hidden" name="update_qty" value="1">
                </form>
            </div>
            <div class="cart-sub">₱<?php echo number_format($item_subtotal, 2); ?></div>
            <div><a href="cart.php?remove=<?php echo $item['id']; ?>" class="btn-remove" onclick="return confirm('Remove this item?')">Remove</a></div>
        </div>
        <?php endforeach; ?>
    </div>
    <div class="checkout-box">
        <h3>Checkout Details</h3>
        <?php if(empty($addresses)): ?>
            <div class="alert-warning">You have no saved delivery address. Please <a href="customer/addresses.php">add an address</a> first before placing an order.</div>
        <?php endif; ?>
        <form method="post" id="checkoutForm">
            <div class="form-group">
                <label>Delivery Address *</label>
                <select name="delivery_address_id" id="addressSelect" required>
                    <option value="">-- Select Delivery Address --</option>
                    <?php foreach($addresses as $a): ?>
                    <option value="<?php echo $a['id']; ?>">
                        <?php echo ($a['is_default'] ? 'Default: ' : ''); ?><?php echo htmlspecialchars($a['address_label']); ?> — <?php echo htmlspecialchars($a['full_address']); ?>, <?php echo htmlspecialchars($a['city']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Shipping Region *</label>
                <select name="shipping_region" id="shippingRegion" required onchange="updateShippingDisplay()">
                    <option value="">-- Select Region --</option>
                    <option value="Luzon">Luzon — ₱50.00</option>
                    <option value="Visayas">Visayas — ₱70.00</option>
                    <option value="Mindanao">Mindanao — ₱100.00</option>
                </select>
            </div>
            <div class="form-group">
                <label>Payment Method *</label>
                <div class="payment-option" data-method="COD" onclick="selectPayment('COD')">
                    <input type="radio" name="payment_method" id="pay_cod" value="Cash on Delivery">
                    <label for="pay_cod">Cash on Delivery — Pay when your order arrives</label>
                </div>
                <div class="payment-option" data-method="GCash" onclick="selectPayment('GCash')">
                    <input type="radio" name="payment_method" id="pay_gcash" value="GCash">
                    <label for="pay_gcash">GCash — Pay via GCash</label>
                </div>
                <div class="payment-option" data-method="BPI" onclick="selectPayment('BPI')">
                    <input type="radio" name="payment_method" id="pay_bpi" value="BPI Bank Transfer">
                    <label for="pay_bpi">BPI Bank Transfer — Direct bank deposit</label>
                </div>
            </div>
            <div class="payment-details-box gcash-box" id="gcashBox">
                <p><strong>Send Payment to GCash:</strong></p>
                <p>Number: 09XX-XXX-XXXX — Name: M&Scents</p>
                <p>Scan QR or send payment then enter your 13-digit reference number below</p>
                <img src="images/QR.jpg" alt="GCash QR Code">
                <div class="form-group">
                    <label>GCash Reference Number *</label>
                    <input type="text" name="gcash_ref_number" id="gcashRef" placeholder="13 digits only" maxlength="13" inputmode="numeric">
                    <p class="hint-text">Must be exactly 13 digits — no more, no less</p>
                </div>
            </div>
            <div class="payment-details-box bpi-box" id="bpiBox">
                <p><strong>Send Payment to BPI Bank:</strong></p>
                <p>Account Name: M&Scents</p>
                <p>Account Number: <strong>7339890727</strong></p>
                <p>After transfer, enter your 13-digit reference number below</p>
                <div class="form-group">
                    <label>BPI Reference Number *</label>
                    <input type="text" name="bpi_ref_number" id="bpiRef" placeholder="13 digits only" maxlength="13" inputmode="numeric">
                    <p class="hint-text">Must be exactly 13 digits — no more, no less</p>
                </div>
            </div>

            <div class="subtotal-row">
                <span>Subtotal</span>
                <span>₱<?php echo number_format($subtotal, 2); ?></span>
            </div>
            <div class="shipping-row" id="shippingRow" style="display:none;">
                <span>Shipping Fee (<span id="regionName"></span>)</span>
                <span id="shippingFee">₱0.00</span>
            </div>
            <div class="grandtotal-row">
                <span>Total Amount</span>
                <span id="grandTotal">₱<?php echo number_format($subtotal, 2); ?></span>
            </div>

            <button type="submit" name="place_order" id="placeBtn" class="btn-place" <?php echo empty($addresses)?'disabled':''; ?>>
                <?php echo empty($addresses) ? 'Add Address First' : 'Place Order'; ?>
            </button>
        </form>
    </div>
    <?php endif; ?>
</div>
<script>
const shippingRates = {
    'Luzon': 50.00,
    'Visayas': 70.00,
    'Mindanao': 100.00
};
const subtotal = <?php echo $subtotal; ?>;

function updateShippingDisplay(){
    const region = document.getElementById('shippingRegion').value;
    const shippingRow = document.getElementById('shippingRow');
    if(region){
        const fee = shippingRates[region];
        shippingRow.style.display = 'flex';
        document.getElementById('regionName').textContent = region;
        document.getElementById('shippingFee').textContent = '₱' + fee.toFixed(2);
        document.getElementById('grandTotal').textContent = '₱' + (subtotal + fee).toFixed(2);
    }else{
        shippingRow.style.display = 'none';
        document.getElementById('grandTotal').textContent = '₱' + subtotal.toFixed(2);
    }
    validateForm();
}

let selectedMethod = null;
function selectPayment(method){
    selectedMethod = method;
    document.querySelectorAll('.payment-option').forEach(el => {
        el.classList.remove('selected');
        el.style.borderColor = 'var(--color-border)';
    });
    document.querySelector('[data-method="'+method+'"]').classList.add('selected');
    document.querySelector('[data-method="'+method+'"]').style.borderColor = 'var(--color-gold)';
    document.getElementById('pay_'+method.toLowerCase()).checked = true;
    document.getElementById('gcashBox').classList.remove('show');
    document.getElementById('bpiBox').classList.remove('show');
    if(method === 'GCash'){
        document.getElementById('gcashBox').classList.add('show');
        document.getElementById('gcashRef').setAttribute('required','required');
        document.getElementById('gcashRef').value = '';
    }else{
        document.getElementById('gcashRef').removeAttribute('required');
        document.getElementById('gcashRef').value = '';
    }
    if(method === 'BPI'){
        document.getElementById('bpiBox').classList.add('show');
        document.getElementById('bpiRef').setAttribute('required','required');
        document.getElementById('bpiRef').value = '';
    }else{
        document.getElementById('bpiRef').removeAttribute('required');
        document.getElementById('bpiRef').value = '';
    }
    validateForm();
}
function enforceDigitsOnly(input){
    input.value = input.value.replace(/\D/g, '');
}
function checkRefLength(refInput){
    const val = refInput.value.trim();
    if(val.length > 0 && val.length !== 13){
        refInput.classList.add('error');
    }else{
        refInput.classList.remove('error');
    }
}
function validateForm(){
    const addr = document.getElementById('addressSelect').value;
    const region = document.getElementById('shippingRegion').value;
    const btn = document.getElementById('placeBtn');
    let refOk = true;
    if(selectedMethod === 'GCash'){
        const gcashVal = document.getElementById('gcashRef').value.trim();
        refOk = /^\d{13}$/.test(gcashVal);
    }else if(selectedMethod === 'BPI'){
        const bpiVal = document.getElementById('bpiRef').value.trim();
        refOk = /^\d{13}$/.test(bpiVal);
    }
    btn.disabled = !(addr && region && selectedMethod && refOk);
    if(!addr) btn.innerText = 'Select Delivery Address';
    else if(!region) btn.innerText = 'Select Shipping Region';
    else if(!selectedMethod) btn.innerText = 'Select Payment Method';
    else if(!refOk) btn.innerText = 'Enter 13-digit Reference';
    else btn.innerText = 'Place Order';
}
document.getElementById('addressSelect').addEventListener('change', validateForm);
document.getElementById('shippingRegion').addEventListener('change', validateForm);
const gcashInput = document.getElementById('gcashRef');
gcashInput.addEventListener('input', function(){
    enforceDigitsOnly(this);
    checkRefLength(this);
    validateForm();
});
const bpiInput = document.getElementById('bpiRef');
bpiInput.addEventListener('input', function(){
    enforceDigitsOnly(this);
    checkRefLength(this);
    validateForm();
});
</script>
</body>
</html>