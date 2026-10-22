<?php
session_start();
include("../config.php");
// ✅ ADMIN CHECK
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== "admin"){
    header("Location: ../login.php");
    exit();
}
// ✅ REPLY TO CUSTOMER
if(isset($_POST['send_reply']) && !empty($_POST['customer_name'])){
    $customer_name = mysqli_real_escape_string($conn, trim($_POST['customer_name']));
    $reply_text = mysqli_real_escape_string($conn, trim($_POST['reply_text']));
    
    if(!empty($reply_text) && !empty($customer_name)){
        mysqli_query($conn, "INSERT INTO messages 
            (customer_name, sender_type, message_text, sent_at, is_read)
            VALUES ('$customer_name', 'admin', '$reply_text', NOW(), 1)");
    }
    header("Location: messages.php?customer=" . urlencode($customer_name));
    exit();
}
// ✅ AUTO-ADD COLUMNS
$checks = [
    "sender_type" => "VARCHAR(20) DEFAULT 'customer'",
    "is_read" => "INT DEFAULT 0"
];
foreach($checks as $col => $def){
    $c = @mysqli_query($conn, "SHOW COLUMNS FROM messages LIKE '$col'");
    if(!$c || mysqli_num_rows($c) == 0) @mysqli_query($conn, "ALTER TABLE messages ADD COLUMN $col $def");
}
// ✅ KUNIN LAHAT NG UNIQUE NA CUSTOMER
$customers = mysqli_query($conn, "SELECT DISTINCT customer_name, MAX(sent_at) as last_time,
    SUM(CASE WHEN is_read=0 AND sender_type='customer' THEN 1 ELSE 0 END) as unread_count
    FROM messages GROUP BY customer_name ORDER BY last_time DESC");
// ✅ KUNG MAY PINILI NA CUSTOMER
$selected = [];
$selected_name = isset($_GET['customer']) ? urldecode($_GET['customer']) : '';
if(!empty($selected_name)){
    $conv = mysqli_query($conn, "SELECT * FROM messages WHERE customer_name = '" . mysqli_real_escape_string($conn, $selected_name) . "' ORDER BY sent_at ASC");
    while($row = mysqli_fetch_assoc($conv)) $selected[] = $row;
    
    // ✅ MARK AS READ
    mysqli_query($conn, "UPDATE messages SET is_read=1 WHERE customer_name='" . mysqli_real_escape_string($conn, $selected_name) . "' AND sender_type='customer'");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Customer Messages — M&Scents</title>
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
        --color-customer: #2a2a3c;
        --color-admin: #926a0f;
        --color-admin-bg: rgba(212,175,55,0.12);
        --color-danger: #ef4444;
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

    /* ✅ TOP NAVBAR — MAY HAMBURGER BUTTON */
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

    /* ✅ ADMIN LAYOUT */
    .admin-layout {
        display: grid;
        grid-template-columns: var(--sidebar-width) 280px 1fr;
        min-height: calc(100vh - 69px);
    }

    /* ✅ MAIN SIDEBAR — NAVIGATION */
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

    /* ✅ CUSTOMER LIST SIDEBAR */
    .chat-sidebar {
        background: var(--color-bg-card);
        border-right: 1px solid var(--color-border);
        padding: 20px 15px;
    }
    .chat-sidebar h2 {
        font-size: 16px;
        color: var(--color-gold);
        margin-bottom: 16px;
        padding-bottom: 10px;
        border-bottom: 1px solid var(--color-border);
    }
    .customer-item {
        display: block;
        padding: 12px 14px;
        margin: 4px 0;
        border-radius: 8px;
        text-decoration: none;
        transition: all 0.25s;
        position: relative;
        border: 1px solid transparent;
        cursor: pointer;
    }
    .customer-item:hover {
        background: rgba(212,175,55,0.05);
        border-color: rgba(212,175,55,0.15);
    }
    .customer-item.active {
        background: rgba(212,175,55,0.08);
        border-left: 3px solid var(--color-gold);
    }
    .customer-item .name {
        font-weight: 600;
        font-size: 14px;
        color: #fff;
    }
    .customer-item .time {
        font-size: 11px;
        color: var(--color-text-muted);
        margin-top: 4px;
    }
    .badge {
        position: absolute;
        right: 12px;
        top: 12px;
        background: var(--color-danger);
        color: #fff;
        font-size: 10px;
        font-weight: 700;
        padding: 2px 6px;
        border-radius: 10px;
        line-height: 1;
    }

    /* ✅ CHAT AREA */
    .chat-area {
        display: flex;
        flex-direction: column;
        padding: 0;
        background: linear-gradient(180deg, #141414 0%, #0f0f0f 100%);
    }
    .chat-header {
        padding: 18px 24px;
        border-bottom: 1px solid var(--color-border);
        background: var(--color-bg-card);
    }
    .chat-header h3 {
        color: var(--color-gold);
        font-size: 17px;
    }
    .chat-messages {
        flex: 1;
        padding: 20px 24px;
        overflow-y: auto;
        min-height: 450px;
    }
    .msg-bubble {
        max-width: 70%;
        margin-bottom: 16px;
        animation: bubbleIn 0.3s ease;
        position: relative;
    }
    .msg-bubble.customer {
        margin-right: auto;
    }
    .msg-bubble.admin {
        margin-left: auto;
        text-align: right;
    }
    .msg-bubble .bubble {
        padding: 12px 16px;
        border-radius: 16px;
        font-size: 14px;
        line-height: 1.6;
    }
    .msg-bubble.customer .bubble {
        background: var(--color-customer);
        border-bottom-left-radius: 4px;
        color: #eee;
    }
    .msg-bubble.admin .bubble {
        background: var(--color-admin-bg);
        color: var(--color-gold);
        border-bottom-right-radius: 4px;
        border: 1px solid rgba(212,175,55,0.2);
    }
    .msg-bubble .time {
        font-size: 10px;
        color: var(--color-text-muted);
        margin-top: 4px;
        padding: 0 8px;
    }

    /* ✅ REPLY INPUT BOX */
    .reply-form {
        padding: 16px 24px;
        border-top: 1px solid var(--color-border);
        background: var(--color-bg-card);
        display: flex;
        gap: 10px;
        align-items: flex-end;
    }
    .reply-form textarea {
        flex: 1;
        padding: 12px 16px;
        background: var(--color-bg-dark);
        border: 1px solid var(--color-border);
        color: var(--color-text);
        border-radius: 20px;
        font-size: 14px;
        resize: none;
        height: 44px;
        transition: all 0.25s;
    }
    .reply-form textarea:focus {
        outline: none;
        border-color: var(--color-gold);
        height: 65px;
    }
    .reply-form button {
        padding: 10px 24px;
        background: linear-gradient(135deg, var(--color-gold), var(--color-gold-light));
        color: #000;
        border: none;
        border-radius: 20px;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.25s;
    }
    .reply-form button:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(212,175,55,0.25);
    }
    .empty-state {
        text-align: center;
        padding: 80px 30px;
        color: var(--color-text-muted);
    }
    .empty-state h3 {
        margin-bottom: 8px;
        color: var(--color-text-muted);
        font-size: 18px;
    }
    .empty-state p {
        font-size: 14px;
    }

    /* ✅ ANIMATION */
    @keyframes bubbleIn {
        from { opacity: 0; transform: translateY(8px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* ✅ OVERLAY — DIMMED BACKGROUND */
    .overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.5);
        z-index: 5;
    }

    /* ====================================================== */
    /* ✅ PHONE VIEW — SIDEBAR NAKATAGO, MAY HAMBURGER */
    /* ====================================================== */
    @media(max-width: 900px){
        .admin-layout { grid-template-columns: 240px 1fr; }
        .chat-sidebar { width: 240px; }
    }

    @media(max-width: 768px){
        .menu-toggle { display: flex; }
        .admin-layout { 
            grid-template-columns: 1fr; 
            position: relative; 
        }
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
        .chat-sidebar {
            border-right: none;
            border-bottom: 1px solid var(--color-border);
        }
        .logo-img { height: 40px; }
        .logo-text { font-size: 16px; }
        .chat-messages { padding: 15px; }
        .msg-bubble { max-width: 80%; }
        .reply-form { padding: 12px 15px; }
        .reply-form button { padding: 10px 18px; font-size: 13px; }
    }

    @media(max-width: 480px){
        .chat-sidebar { padding: 12px; }
        .customer-item { padding: 10px 12px; }
        .msg-bubble { max-width: 85%; }
    }
    </style>
</head>
<body>

<!-- ✅ TOP NAVBAR -->
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

<!-- ✅ DIMMED OVERLAY -->
<div class="overlay" id="overlay"></div>

<!-- ✅ ADMIN LAYOUT -->
<div class="admin-layout">
    <!-- ✅ MAIN NAVIGATION SIDEBAR -->
    <div class="sidebar" id="sidebar">
        <h2>Control Panel</h2>
        <a href="dashboard.php">Dashboard</a>
        <a href="orders.php">Orders</a>
        <a href="products.php">Manage Products</a>
        <a href="payments.php">Payments</a>
        <a href="sales.php">Sales Report</a>
        <a href="reviews.php">Customer Reviews</a>
        <a href="messages.php" class="active">Customer Messages</a>
        <a href="../logout.php">Logout</a>
    </div>

    <!-- ✅ CUSTOMER LIST -->
    <div class="chat-sidebar">
        <h2>Conversations</h2>
        <?php if(!$customers || mysqli_num_rows($customers) == 0): ?>
            <p style="color:#666; font-size:13px; text-align:center; margin-top:20px;">No messages yet.</p>
        <?php else: ?>
            <?php while($c = mysqli_fetch_assoc($customers)): 
                $cust_name = $c['customer_name'];
                $active = ($selected_name === $cust_name) ? 'active' : '';
                $safe_name = urlencode($cust_name);
            ?>
            <a href="messages.php?customer=<?php echo $safe_name; ?>" class="customer-item <?php echo $active; ?>">
                <?php if($c['unread_count'] > 0): ?>
                    <span class="badge"><?php echo $c['unread_count']; ?></span>
                <?php endif; ?>
                <div class="name"><?php echo htmlspecialchars($cust_name); ?></div>
                <div class="time"><?php echo date('M j g:i A', strtotime($c['last_time'])); ?></div>
            </a>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>

    <!-- ✅ CHAT AREA -->
    <div class="chat-area">
        <?php if(empty($selected_name)): ?>
            <div class="empty-state">
                <h3>Select a conversation</h3>
                <p>Click a customer name from the list on the left</p>
            </div>
        <?php else: ?>
            <div class="chat-header">
                <h3><?php echo htmlspecialchars($selected_name); ?></h3>
            </div>
            <div class="chat-messages">
                <?php if(empty($selected)): ?>
                    <p style="color:#888; text-align:center;">No messages yet.</p>
                <?php else: ?>
                    <?php foreach($selected as $msg): 
                        $sender_type = isset($msg['sender_type']) ? $msg['sender_type'] : 'customer';
                        $is_admin = ($sender_type === 'admin');
                        $text = isset($msg['message_text']) ? $msg['message_text'] : (isset($msg['message']) ? $msg['message'] : '');
                        $stamp = isset($msg['sent_at']) ? date('M j g:i A', strtotime($msg['sent_at'])) : '';
                    ?>
                    <div class="msg-bubble <?php echo $is_admin?'admin':'customer'; ?>">
                        <div class="bubble">
                            <?php echo htmlspecialchars($text); ?>
                        </div>
                        <div class="time">
                            <?php echo $is_admin?'Admin':'Customer'; ?>
                            <?php if($stamp) echo " • $stamp"; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <!-- ✅ REPLY BOX -->
            <form method="post" class="reply-form">
                <input type="hidden" name="customer_name" value="<?php echo htmlspecialchars($selected_name); ?>">
                <textarea name="reply_text" placeholder="Type your reply..." required></textarea>
                <button type="submit" name="send_reply">Send</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<!-- ✅ JAVASCRIPT — HAMBURGER MENU -->
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