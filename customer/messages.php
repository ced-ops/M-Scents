<?php
session_start();
if(!isset($_SESSION['fullname'])){ 
    header("Location: ../login.php"); 
    exit(); 
}
include("../config.php");
$customer_name = $_SESSION['fullname'];

$checks = [
    "sender_type" => "VARCHAR(20) DEFAULT 'customer'",
    "message_text" => "TEXT NOT NULL",
    "sent_at" => "DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
    "is_read" => "INT DEFAULT 0"
];
foreach($checks as $col => $def){
    $c = @mysqli_query($conn, "SHOW COLUMNS FROM messages LIKE '$col'");
    if(!$c || mysqli_num_rows($c) == 0) @mysqli_query($conn, "ALTER TABLE messages ADD COLUMN $col $def");
}

if(isset($_POST['send_msg'])){
    $msg = trim($_POST['message']);
    if(!empty($msg)){
        $safe_msg = mysqli_real_escape_string($conn, $msg);
        mysqli_query($conn, "INSERT INTO messages 
            (customer_name, sender_type, message_text, sent_at, is_read) 
            VALUES ('$customer_name', 'customer', '$safe_msg', NOW(), 0)");
        header("Location: messages.php");
        exit();
    }
}

$messages = [];
$safe_name = mysqli_real_escape_string($conn, $customer_name);
$res = mysqli_query($conn, "SELECT * FROM messages WHERE customer_name = '$safe_name' ORDER BY sent_at ASC");
while($r = mysqli_fetch_assoc($res)) $messages[] = $r;

$cart_count = 0;
if(isset($_SESSION['user_id'])){
    $user_id = $_SESSION['user_id'];
    $count_q = mysqli_query($conn, "SELECT COUNT(*) as total FROM cart WHERE user_id='$user_id'");
    if($count_row = mysqli_fetch_assoc($count_q)){
        $cart_count = (int)$count_row['total'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Message Admin — M&Scents</title>
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
        --color-customer: #252535;
        --color-admin: rgba(212,175,55,0.12);
        --color-cart-red: #ff3b30;
    }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    html, body { width: 100%; overflow-x: hidden; }
    body {
        font-family: var(--font-body);
        background: var(--color-bg-dark);
        color: var(--color-text);
        line-height: 1.6;
    }
    h1, h2, h3 { font-family: var(--font-heading); }
    nav {
        position: fixed; top: 0; left: 0; right: 0; z-index: 999;
        display: flex; align-items: center; justify-content: space-between;
        padding: 12px 20px;
        background: rgba(0,0,0,0.85);
        border-bottom: 1px solid var(--color-border);
        flex-wrap: wrap; gap: 10px;
    }
    nav .logo-group { display: flex; align-items: center; gap: 10px; }
    nav img { height: 45px; width: auto; }
    nav .logo-text {
        font-family: var(--font-heading); font-size: 20px; color: var(--color-gold);
        font-weight: 700; text-decoration: none;
    }
    nav ul {
        display: flex; list-style: none; gap: 10px; align-items: center;
        margin: 0; padding: 0; flex-wrap: wrap;
    }
    nav ul li a {
        color: #aaa; text-decoration: none; margin-left: 8px;
        font-size: 14px; font-weight: 500; transition: 0.25s;
    }
    nav ul li a:hover { color: var(--color-gold); }

    .floating-cart, .floating-msg {
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
        border: 2px solid rgba(212,175,55,0.4);
    }
    .floating-cart { right: 15px; }
    .floating-msg { right: 75px; }

    .floating-cart img,
    .floating-msg img {
        width: 24px;
        height: 24px;
        object-fit: contain;
        object-position: center;
        flex-shrink: 0;
        display: block;
    }

    .cart-badge {
        position: absolute;
        top: -6px;
        right: -6px;
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

    .floating-cart:hover, .floating-msg:hover {
        transform: translateY(-3px) scale(1.05);
        box-shadow: 0 6px 20px rgba(212,175,55,0.5);
    }

    .chat-container {
        max-width: 700px; margin: 100px auto 50px; padding: 0 15px;
    }
    .chat-header {
        text-align: center; padding: 15px; margin-bottom: 25px;
    }
    .chat-header h1 {
        font-size: clamp(24px, 5vw, 28px); color: var(--color-gold); margin-bottom: 6px;
    }
    .chat-header p { color: #888; font-size: 14px; }
    .send-box {
        background: var(--color-bg-card); border: 1px solid var(--color-border);
        border-radius: 16px; padding: 22px 20px; margin-bottom: 25px;
        animation: fadeIn 0.5s ease;
    }
    .send-box h3 {
        font-size: 17px; color: var(--color-gold); margin-bottom: 12px;
    }
    .send-box textarea {
        width: 100%; padding: 14px 16px;
        background: var(--color-bg-dark); border: 1px solid var(--color-border);
        color: var(--color-text); border-radius: 10px;
        font-size: 15px; min-height: 100px; resize: none;
        transition: 0.25s;
    }
    .send-box textarea:focus {
        outline: none; border-color: var(--color-gold);
        box-shadow: 0 0 0 3px rgba(212,175,55,0.1);
    }
    .send-box button {
        margin-top: 14px; width: 100%; padding: 13px 30px;
        background: linear-gradient(135deg, var(--color-gold), var(--color-gold-light));
        color: #000; border: none; border-radius: 10px;
        font-weight: 700; font-size: 15px; cursor: pointer;
        transition: all 0.25s;
    }
    .send-box button:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 14px rgba(212,175,55,0.25);
    }
    .conv-box {
        background: var(--color-bg-card); border: 1px solid var(--color-border);
        border-radius: 16px; padding: 22px 20px;
        animation: fadeInUp 0.6s ease;
    }
    .conv-box h3 {
        font-size: 17px; color: var(--color-gold); margin-bottom: 18px;
        padding-bottom: 10px; border-bottom: 1px solid var(--color-border);
    }
    .msg-bubble {
        max-width: 80%; margin-bottom: 14px;
        animation: bubbleIn 0.35s ease; position: relative;
    }
    .msg-bubble.customer { margin-left: auto; }
    .msg-bubble.admin { margin-right: auto; }
    .msg-bubble .bubble {
        padding: 12px 16px; border-radius: 18px;
        font-size: 14px; line-height: 1.5; word-wrap: break-word;
    }
    .msg-bubble.customer .bubble {
        background: var(--color-customer); border-bottom-right-radius: 4px;
    }
    .msg-bubble.admin .bubble {
        background: var(--color-admin); color: var(--color-gold);
        border: 1px solid rgba(212,175,55,0.2);
        border-bottom-left-radius: 4px;
    }
    .msg-bubble .meta {
        font-size: 10px; color: #666; margin-top: 4px; padding: 0 10px;
    }
    .msg-bubble.customer .meta { text-align: right; }
    .empty {
        text-align: center; padding: 40px 20px; color: #666;
        font-size: 14px; line-height: 1.6;
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(8px); }
        to { opacity: 1; transform: translateY(0); }
    }
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(12px); }
        to { opacity: 1; transform: translateY(0); }
    }
    @keyframes bubbleIn {
        from { opacity: 0; transform: translateY(6px); }
        to { opacity: 1; transform: translateY(0); }
    }
    @media (max-width: 768px){
        .floating-cart, .floating-msg {
            width: 44px;
            height: 44px;
        }
        .floating-cart img, .floating-msg img {
            width: 20px;
            height: 20px;
        }
        .floating-cart { right: 10px; }
        .floating-msg { right: 60px; }
        .cart-badge {
            font-size: 10px;
            width: 18px;
            height: 18px;
            top: -4px;
            right: -4px;
        }
        nav { padding: 10px 15px; flex-direction: column; gap: 8px; }
        nav ul { justify-content: center; width: 100%; }
        nav ul li a { margin: 0 6px; font-size: 13px; }
        .chat-container { padding: 0 12px; }
        .msg-bubble { max-width: 88%; }
        .send-box, .conv-box { padding: 18px 15px; }
    }
    </style>
</head>
<body>
<nav>
    <div class="logo-group">
        <img src="../images/logo.png" alt="M&Scents Logo">
        <a href="home.php" class="logo-text">M&Scents</a>
    </div>
    <ul>
        <li><a href="home.php">Home</a></li>
        <li><a href="../collection.php">Collection</a></li>
        <li><a href="dashboard.php">Dashboard</a></li>
        <li><a href="../logout.php">Logout</a></li>
    </ul>
</nav>

<a href="../cart.php" class="floating-cart <?= $cart_count == 0 ? 'empty' : '' ?>" title="Your Cart">
    <img src="../images/cart.png" alt="Cart">
    <?php if($cart_count > 0): ?>
        <span class="cart-badge"><?= $cart_count ?></span>
    <?php endif; ?>
</a>

<a href="messages.php" class="floating-msg" title="Messages">
    <img src="../images/message.png" alt="Messages">
</a>

<div class="chat-container">
    <div class="chat-header">
        <h1>Contact / Message Admin</h1>
        <p>Send a message and we will reply as soon as possible</p>
    </div>

    <div class="send-box">
        <h3>Send Message</h3>
        <form method="post">
            <textarea name="message" placeholder="Type your message here..." required></textarea>
            <button type="submit" name="send_msg">Send Message</button>
        </form>
    </div>

    <div class="conv-box">
        <h3>Your Conversation</h3>
        <?php if(empty($messages)): ?>
            <div class="empty">No messages yet.<br>Send a message above to start chatting.</div>
        <?php else: ?>
            <?php foreach($messages as $m): 
                $sender_type = isset($m['sender_type']) ? $m['sender_type'] : 'customer';
                $is_admin = ($sender_type === 'admin');
                $text = isset($m['message_text']) ? $m['message_text'] : (isset($m['message']) ? $m['message'] : '');
                $stamp = isset($m['sent_at']) ? date('M j, Y — g:i A', strtotime($m['sent_at'])) : '';
            ?>
            <div class="msg-bubble <?= $is_admin ? 'admin' : 'customer' ?>">
                <div class="bubble">
                    <?php if($is_admin): ?><strong>Admin:</strong> <?php endif; ?>
                    <?= htmlspecialchars($text) ?>
                </div>
                <div class="meta">
                    <?= $is_admin ? 'Admin' : 'You' ?> — <?= $stamp ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
</body>
</html>