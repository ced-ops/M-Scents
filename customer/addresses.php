<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}
include("../config.php");

if(!isset($_SESSION['fullname'])) {
    $u = mysqli_fetch_assoc(mysqli_query($conn, "SELECT fullname FROM users WHERE id='{$_SESSION['user_id']}'"));
    $_SESSION['fullname'] = $u['fullname'];
}
$customer_name = $_SESSION['fullname'];

if(isset($_POST['save_address'])){
    $label = mysqli_real_escape_string($conn, trim($_POST['address_label']));
    $contact = mysqli_real_escape_string($conn, trim($_POST['contact_number']));
    $addr = mysqli_real_escape_string($conn, trim($_POST['full_address']));
    $city = mysqli_real_escape_string($conn, trim($_POST['city']));
    $prov = mysqli_real_escape_string($conn, trim($_POST['province']));
    $zip = mysqli_real_escape_string($conn, trim($_POST['zip_code']));
    $is_default = isset($_POST['is_default']) ? 1 : 0;
    $count = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM customer_addresses WHERE customer_name='$customer_name'"));
    
    if($count >= 2 && empty($_POST['addr_id'])){
        echo "<script>alert('⚠️ Maximum of 2 addresses only. Please delete one first.');</script>";
    }else{
        if(!empty($_POST['addr_id'])){
            $aid = $_POST['addr_id'];
            mysqli_query($conn, "UPDATE customer_addresses SET address_label='$label', contact_number='$contact', full_address='$addr', city='$city', province='$prov', zip_code='$zip', is_default='$is_default' WHERE id='$aid' AND customer_name='$customer_name'");
        }else{
            mysqli_query($conn, "INSERT INTO customer_addresses (customer_name,address_label,contact_number,full_address,city,province,zip_code,is_default) VALUES ('$customer_name','$label','$contact','$addr','$city','$prov','$zip','$is_default')");
        }
        echo "<script>alert('✅ Address saved successfully.'); location='addresses.php';</script>";
        exit();
    }
}

if(isset($_GET['delete'])){
    mysqli_query($conn, "DELETE FROM customer_addresses WHERE id='{$_GET['delete']}' AND customer_name='$customer_name'");
    echo "<script>alert('✅ Address deleted.'); location='addresses.php';</script>";
    exit();
}

$addresses = [];
$res = mysqli_query($conn, "SELECT * FROM customer_addresses WHERE customer_name='$customer_name' ORDER BY is_default DESC, id DESC");
while($r = mysqli_fetch_assoc($res)) $addresses[] = $r;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Addresses | M&Scents</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700;800&display=swap" rel="stylesheet">
    <style>
    :root {
        --font-heading: 'Playfair Display', Georgia, serif;
        --font-body: 'Inter', system-ui, sans-serif;
        --color-gold: #d4af37;
        --color-gold-light: #f0c94d;
        --color-bg-dark: #0f0f0f;
        --color-bg-card: #1a1a1a;
        --color-bg-sidebar: #151515;
        --color-border: rgba(212,175,55,0.15);
        --color-text: #e5e5e5;
        --color-text-muted: #888;
        --color-success: #4ade80;
        --color-danger: #ef4444;
        --color-input: #252525;
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
        background: var(--color-gold);
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
        margin-bottom: 25px;
    }

    .alert-box {
        background: rgba(212,175,55,0.08);
        border: 1px solid rgba(212,175,55,0.25);
        padding: 14px 18px;
        border-radius: 10px;
        margin-bottom: 25px;
        color: #f0c94d;
        font-size: 14px;
        line-height: 1.6;
    }

    .addr-form {
        background: var(--color-bg-card);
        padding: 28px 24px;
        border-radius: 14px;
        border: 1px solid var(--color-border);
        border-left: 3px solid var(--color-gold);
        margin-bottom: 30px;
    }
    .addr-form h3 {
        font-size: 18px;
        color: var(--color-gold);
        margin-bottom: 24px;
        padding-bottom: 12px;
        border-bottom: 1px solid var(--color-border);
    }
    .addr-form label {
        display: block;
        color: #ccc;
        font-size: 14px;
        font-weight: 500;
        margin: 0 0 8px 0;
    }
    .addr-form input,
    .addr-form textarea {
        width: 100%;
        padding: 14px 16px;
        background: var(--color-input);
        border: 1px solid transparent;
        border-radius: 8px;
        color: #fff;
        font-family: var(--font-body);
        font-size: 15px;
        margin-bottom: 18px;
    }
    .addr-form input:focus,
    .addr-form textarea:focus {
        outline: none;
        border-color: var(--color-gold);
    }
    .addr-form textarea {
        resize: vertical;
        min-height: 90px;
    }
    .checkbox-row {
        margin-top: 8px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .checkbox-row input[type="checkbox"] {
        width: auto;
        margin: 0;
    }
    .checkbox-row label {
        margin: 0;
        cursor: pointer;
    }
    .btn-primary {
        width: 100%;
        padding: 14px 30px;
        background: linear-gradient(135deg, var(--color-gold), var(--color-gold-light));
        color: #000;
        border: none;
        border-radius: 50px;
        font-size: 16px;
        font-weight: 700;
        cursor: pointer;
        margin-top: 6px;
    }
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(212,175,55,0.35);
    }

    .section-title {
        font-size: 18px;
        margin: 30px 0 18px;
        color: var(--color-text);
        padding-bottom: 10px;
        border-bottom: 1px solid var(--color-border);
    }
    .addr-card {
        background: var(--color-bg-card);
        padding: 20px;
        border-radius: 12px;
        border: 1px solid var(--color-border);
        border-left: 3px solid var(--color-text-muted);
        margin-bottom: 15px;
    }
    .addr-card.default {
        border-left-color: var(--color-success);
        background: rgba(74,222,128,0.03);
    }
    .addr-card:hover {
        background: #202020;
    }
    .addr-card h3 {
        font-size: 16px;
        margin-bottom: 10px;
        color: var(--color-gold);
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }
    .default-badge {
        font-size: 11px;
        background: var(--color-success);
        color: #000;
        padding: 2px 8px;
        border-radius: 12px;
        font-family: var(--font-body);
        font-weight: 600;
    }
    .addr-card p {
        color: var(--color-text-muted);
        font-size: 14px;
        margin: 4px 0;
        line-height: 1.7;
    }
    .addr-card p.contact {
        color: #fff;
    }
    .card-actions {
        margin-top: 15px;
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }
    .btn {
        padding: 8px 16px;
        border: none;
        border-radius: 6px;
        font-family: var(--font-body);
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .btn-edit {
        background: rgba(212,175,55,0.15);
        color: var(--color-gold);
    }
    .btn-edit:hover {
        background: rgba(212,175,55,0.25);
    }
    .btn-delete {
        background: rgba(239,68,68,0.12);
        color: var(--color-danger);
    }
    .btn-delete:hover {
        background: rgba(239,68,68,0.2);
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
        .addr-form {
            padding: 20px 15px;
        }
        .addr-card {
            padding: 15px;
        }
        .btn-primary {
            padding: 13px 25px;
            font-size: 15px;
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
        <a href="addresses.php" class="active">Addresses</a>
        <a href="settings.php">Settings</a>
        <a href="about.php">About</a>
        <a href="../logout.php">Logout</a>
    </div>
    <div class="content">
        <h1>Delivery Addresses</h1>
        <p class="subheading">Manage your delivery addresses for faster checkout</p>
        <?php if(empty($addresses)): ?>
            <div class="alert-box">⚠️ You have no saved delivery address yet. Please add at least one address before you can place an order.</div>
        <?php elseif(count($addresses) < 2): ?>
            <div class="alert-box">💡 You can save up to 2 delivery addresses. Adding an address is required before placing an order.</div>
        <?php endif; ?>
        <div class="addr-form">
            <h3><?php echo isset($_GET['edit']) ? 'Edit Address' : 'Add New Delivery Address'; ?></h3>
            <form method="post">
                <?php
                $edit = null;
                if(isset($_GET['edit'])){
                    $eid = $_GET['edit'];
                    $edit = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM customer_addresses WHERE id='$eid' AND customer_name='$customer_name'"));
                    echo '<input type="hidden" name="addr_id" value="'.$edit['id'].'">';
                }
                ?>
                <input type="text" name="address_label" placeholder="Address Label — e.g. Home, Work" required value="<?php echo $edit ? htmlspecialchars($edit['address_label']) : ''; ?>">
                <input type="tel" name="contact_number" placeholder="Contact Number — e.g. 09123456789" required value="<?php echo $edit ? htmlspecialchars($edit['contact_number']) : ''; ?>">
                <textarea name="full_address" placeholder="House Number, Street, Barangay" required rows="3"><?php echo $edit ? htmlspecialchars($edit['full_address']) : ''; ?></textarea>
                <input type="text" name="city" placeholder="City or Municipality" required value="<?php echo $edit ? htmlspecialchars($edit['city']) : ''; ?>">
                <input type="text" name="province" placeholder="Province" required value="<?php echo $edit ? htmlspecialchars($edit['province']) : ''; ?>">
                <input type="text" name="zip_code" placeholder="Zip Code" required value="<?php echo $edit ? htmlspecialchars($edit['zip_code']) : ''; ?>">
                <div class="checkbox-row">
                    <input type="checkbox" id="is_def" name="is_default" <?php echo ($edit && $edit['is_default']==1) ? 'checked' : ''; ?>>
                    <label for="is_def">Set as Default Delivery Address</label>
                </div>
                <button type="submit" name="save_address" class="btn-primary">Save Address</button>
            </form>
        </div>
        <?php if(!empty($addresses)): ?>
            <h3 class="section-title">Saved Addresses (<?php echo count($addresses); ?> of 2)</h3>
            <?php foreach($addresses as $a): ?>
            <div class="addr-card <?php echo $a['is_default']==1?'default':''; ?>">
                <h3>
                    <?php echo htmlspecialchars($a['address_label']); ?>
                    <?php if($a['is_default']==1): ?><span class="default-badge">✓ DEFAULT</span><?php endif; ?>
                </h3>
                <p class="contact"><?php echo htmlspecialchars($a['contact_number']); ?></p>
                <p><?php echo htmlspecialchars($a['full_address']); ?></p>
                <p><?php echo htmlspecialchars($a['city']); ?>, <?php echo htmlspecialchars($a['province']); ?> <?php echo htmlspecialchars($a['zip_code']); ?></p>
                <div class="card-actions">
                    <a href="addresses.php?edit=<?php echo $a['id']; ?>" class="btn btn-edit">Edit</a>
                    <a href="addresses.php?delete=<?php echo $a['id']; ?>" class="btn btn-delete" onclick="return confirm('Delete this address?')">Delete</a>
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
</script>
</body>
</html>