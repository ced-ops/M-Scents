<?php
session_start();
include("config.php");
$is_logged_in = isset($_SESSION['fullname']);

function getSoldCount($conn, $pid){
    $r = mysqli_query($conn, "SELECT SUM(quantity) as sold FROM orders WHERE product_id='$pid' AND status!='Cancelled'");
    $d = mysqli_fetch_assoc($r);
    return $d['sold'] ? (int)$d['sold'] : 0;
}

function getRatingStars($avg){
    $s = round($avg);
    $h = 5 - $s;
    return str_repeat('★', $s).str_repeat('☆', $h);
}

$best_all = mysqli_query($conn, "SELECT p.*, SUM(o.quantity) as total_sold 
    FROM products p 
    LEFT JOIN orders o ON p.id=o.product_id AND o.status!='Cancelled' 
    GROUP BY p.id 
    ORDER BY total_sold DESC, p.avg_rating DESC 
    LIMIT 4");

$best_sweet = mysqli_query($conn, "SELECT p.*, SUM(o.quantity) as total_sold 
    FROM products p 
    LEFT JOIN orders o ON p.id=o.product_id AND o.status!='Cancelled' 
    WHERE p.category='Sweet' 
    GROUP BY p.id 
    ORDER BY total_sold DESC, p.avg_rating DESC 
    LIMIT 4");

$best_fresh = mysqli_query($conn, "SELECT p.*, SUM(o.quantity) as total_sold 
    FROM products p 
    LEFT JOIN orders o ON p.id=o.product_id AND o.status!='Cancelled' 
    WHERE p.category='Fresh' 
    GROUP BY p.id 
    ORDER BY total_sold DESC, p.avg_rating DESC 
    LIMIT 4");

$best_women = mysqli_query($conn, "SELECT p.*, SUM(o.quantity) as total_sold 
    FROM products p 
    LEFT JOIN orders o ON p.id=o.product_id AND o.status!='Cancelled' 
    WHERE p.gender='Women' 
    GROUP BY p.id 
    ORDER BY total_sold DESC, p.avg_rating DESC 
    LIMIT 4");

$cart_count = 0;
if($is_logged_in && isset($_SESSION['user_id'])){
    $c = mysqli_query($conn, "SELECT COUNT(*) as total FROM cart WHERE user_id='".$_SESSION['user_id']."'");
    if($cr = mysqli_fetch_assoc($c)) $cart_count = (int)$cr['total'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>M&Scents | Luxury Inspired Perfumes</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700;800&display=swap" rel="stylesheet">
    <style>
    :root {
        --font-heading: 'Playfair Display', serif;
        --font-body: 'Inter', sans-serif;
        --color-gold: #d4af37;
        --color-gold-light: #f0c94d;
        --color-nav-bg: rgba(0, 0, 0, 0.85);
        --color-bg-dark: #000;
        --color-bg-card: #1a1a1a;
        --color-border: rgba(212,175,55,0.15);
        --color-text-muted: #888;
        --color-cart-red: #ff3b30;
    }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    html, body { width: 100%; overflow-x: hidden; }
    body {
        font-family: var(--font-body);
        background: var(--color-bg-dark);
        color: #fff;
        min-height: 100vh;
        font-size: 14px;
        line-height: 1.6;
    }
    h1, h2, h3 { font-family: var(--font-heading); font-weight: 600; }
    .topnav {
        position: fixed; top: 0; left: 0; right: 0; z-index: 999;
        padding: 12px 20px;
        display: flex; justify-content: space-between; align-items: center;
        background: var(--color-nav-bg);
        border-bottom: 1px solid var(--color-border);
        box-shadow: 0 2px 15px rgba(0,0,0,0.4);
    }
    .topnav .logo-group { display: flex; align-items: center; gap: 10px; }
    .topnav .logo-img { height: 45px; width: auto; }
    .topnav .logo-text {
        font-family: var(--font-heading); font-size: 20px; color: var(--color-gold);
        font-weight: 700; text-decoration: none; letter-spacing: 1px;
    }
    .topnav .nav-links a {
        color: #ddd; text-decoration: none; margin-left: 12px;
        font-size: 14px; font-weight: 500; position: relative; padding-bottom: 6px;
        transition: color 0.3s ease;
    }
    .topnav .nav-links a::after {
        content: ''; position: absolute; bottom: 0; left: 0; width: 0; height: 2px;
        background: linear-gradient(90deg, var(--color-gold), var(--color-gold-light));
        transition: width 0.35s ease;
    }
    .topnav .nav-links a:hover { color: var(--color-gold); }
    .topnav .nav-links a:hover::after { width: 100%; }
    .hero {
        min-height: 100vh;
        display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center;
        padding: 100px 20px 60px;
        background: linear-gradient(rgba(0,0,0,0.35), rgba(0,0,0,0.65)), url('images/main.png') no-repeat center center;
        background-size: cover; background-attachment: fixed; background-position: center;
        position: relative; width: 100%;
    }
    .hero h1 {
        font-size: clamp(36px, 10vw, 64px); color: var(--color-gold); margin-bottom: 16px;
        animation: fadeInUp 1s ease; letter-spacing: 2px;
    }
    .hero p {
        font-size: 16px; color: #ccc; margin-bottom: 36px;
        animation: fadeInUp 1s ease 0.2s both; max-width: 700px; padding: 0 10px;
    }
    .hero-btn {
        padding: 14px 36px;
        background: linear-gradient(135deg, var(--color-gold), var(--color-gold-light));
        color: #000; border: none; border-radius: 50px;
        font-size: 15px; font-weight: 600; cursor: pointer;
        box-shadow: 0 4px 18px rgba(212,175,55,0.3);
        animation: fadeInUp 1s ease 0.4s both; transition: all 0.3s ease;
    }
    .hero-btn:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(212,175,55,0.4); }
    .hero-links {
        margin-top: 18px; font-size: 14px; color: #888;
        animation: fadeInUp 1s ease 0.5s both;
    }
    .hero-links a {
        color: var(--color-gold); text-decoration: none; font-weight: 500; margin: 0 6px; transition: 0.2s;
    }
    .hero-links a:hover { text-decoration: underline; }
    .bestsellers-section {
        padding: 60px 20px; max-width: 1200px; margin: 0 auto;
    }
    .section-heading {
        text-align: center; margin-bottom: 40px;
    }
    .section-heading h2 {
        font-size: clamp(24px, 5vw, 32px); color: var(--color-gold); margin-bottom: 8px;
    }
    .section-heading p { color: var(--color-text-muted); font-size: 14px; }
    .category-block {
        margin-bottom: 50px;
    }
    .category-header {
        display: flex; justify-content: space-between; align-items: center;
        margin-bottom: 20px; padding-bottom: 10px;
        border-bottom: 1px solid var(--color-border);
    }
    .category-header h3 {
        font-size: 20px; color: #fff;
    }
    .view-all-link {
        color: var(--color-gold); text-decoration: none; font-size: 13px;
        transition: opacity 0.2s;
    }
    .view-all-link:hover { opacity: 0.8; }
    .products-scroll {
        display: flex; gap: 20px; overflow-x: auto; padding-bottom: 8px;
        scrollbar-width: thin; scrollbar-color: var(--color-gold) transparent;
    }
    .products-scroll::-webkit-scrollbar { height: 4px; }
    .products-scroll::-webkit-scrollbar-thumb { background: var(--color-gold); border-radius: 2px; }
    .product-card {
        flex: 0 0 220px; background: var(--color-bg-card); border-radius: 12px;
        overflow: hidden; border: 1px solid var(--color-border);
        transition: all 0.3s ease; text-decoration: none; color: inherit;
    }
    .product-card:hover {
        transform: translateY(-5px); border-color: rgba(212,175,55,0.35);
        box-shadow: 0 12px 25px rgba(0,0,0,0.35);
    }
    .product-card img {
        width: 100%; height: 200px; object-fit: cover; background: #111;
    }
    .prod-info { padding: 15px; }
    .prod-info h4 { font-size: 15px; color: #fff; margin-bottom: 4px; }
    .prod-size { font-size: 12px; color: var(--color-gold); margin-bottom: 6px; }
    .prod-price { font-size: 18px; font-weight: 700; color: var(--color-gold); margin-bottom: 4px; }
    .prod-rating { font-size: 12px; color: #facc15; margin-bottom: 4px; }
    .prod-sold { font-size: 12px; color: #9ca3af; }
    .empty-note {
        text-align: center; padding: 20px; color: var(--color-text-muted);
        font-size: 14px; font-style: italic;
    }
    .floating-msg, .floating-cart {
        position: fixed; bottom: 20px; width: 50px; height: 50px; border-radius: 50%;
        background: linear-gradient(135deg, var(--color-gold), var(--color-gold-light));
        display: flex; align-items: center; justify-content: center;
        text-decoration: none; box-shadow: 0 4px 15px rgba(212,175,55,0.35);
        z-index: 900; transition: all 0.3s ease; overflow: visible;
        border: 2px solid rgba(212,175,55,0.4);
    }
    .floating-msg { right: 75px; }
    .floating-cart { right: 15px; }
    .floating-msg img, .floating-cart img { width: 24px; height: 24px; object-fit: contain; }
    .cart-badge {
        position: absolute; top: -8px; right: -8px;
        background: var(--color-cart-red); color: #fff;
        font-size: 11px; font-weight: 700; width: 20px; height: 20px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        border: 2px solid #000; box-shadow: 0 2px 4px rgba(0,0,0,0.3); line-height: 1;
    }
    .floating-cart.empty .cart-badge { display: none; }
    .floating-msg:hover, .floating-cart:hover {
        transform: translateY(-3px) scale(1.05);
        box-shadow: 0 6px 20px rgba(212,175,55,0.5);
    }
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(25px); }
        to { opacity: 1; transform: translateY(0); }
    }
    @media (max-width: 768px) {
        .topnav { padding: 10px 15px; flex-direction: column; gap: 8px; }
        .topnav .logo-text { font-size: 18px; }
        .topnav .nav-links a { margin-left: 10px; font-size: 13px; }
        .hero { padding: 110px 15px 60px; background-attachment: scroll; }
        .hero h1 { font-size: 32px; }
        .hero p { font-size: 15px; }
        .bestsellers-section { padding: 40px 15px; }
        .product-card { flex: 0 0 180px; }
        .product-card img { height: 160px; }
        .floating-msg, .floating-cart { width: 44px; height: 44px; }
        .floating-msg img, .floating-cart img { width: 20px; height: 20px; }
        .floating-msg { right: 60px; }
        .floating-cart { right: 10px; }
        .cart-badge { font-size: 10px; width: 18px; height: 18px; top: -6px; right: -6px; }
    }
    @media (max-width: 400px) {
        .topnav .nav-links { display: flex; flex-wrap: wrap; justify-content: center; gap: 5px; }
        .topnav .nav-links a { margin: 0 8px; }
        .product-card { flex: 0 0 160px; }
    }
    </style>
</head>
<body>
<div class="topnav">
    <div class="logo-group">
        <img src="images/logo.png" alt="M&Scents Logo" class="logo-img">
        <a href="index.php" class="logo-text">M&Scents</a>
    </div>
    <div class="nav-links">
        <?php if($is_logged_in){ ?>
            <a href="customer/home.php">Home</a>
            <a href="collection.php">Collection</a>
            <a href="customer/dashboard.php">Dashboard</a>
            <a href="logout.php">Logout</a>
        <?php }else{ ?>
            <a href="index.php">Home</a>
            <a href="collection.php">Collection</a>
            <a href="login.php">Login</a>
            <a href="register.php">Register</a>
        <?php } ?>
    </div>
</div>

<a href="customer/messages.php" class="floating-msg" title="Message Admin">
    <img src="images/message.png" alt="Messages">
</a>
<?php if($is_logged_in){ ?>
<a href="cart.php" class="floating-cart <?= $cart_count==0?'empty':'' ?>" title="Your Cart">
    <img src="images/cart.png" alt="Cart">
    <?php if($cart_count > 0): ?>
        <span class="cart-badge"><?= $cart_count ?></span>
    <?php endif; ?>
</a>
<?php } ?>

<section class="hero">
    <h1>M&Scents</h1>
    <p>Luxury Inspired Perfumes Crafted for Every Occasion</p>
    <?php if($is_logged_in){ ?>
        <button class="hero-btn" onclick="window.location='collection.php'">Shop Now</button>
    <?php }else{ ?>
        <button class="hero-btn" onclick="window.location='login.php'">Login to Shop</button>
        <div class="hero-links">
            <a href="login.php">Login</a> or 
            <a href="register.php">Create Account</a> to start shopping
        </div>
    <?php } ?>
</section>

<section class="bestsellers-section">
    <div class="section-heading">
        <h2>Best Selling Fragrances</h2>
        <p>Our most loved picks by customers</p>
    </div>

    <div class="category-block">
        <div class="category-header">
            <h3>Overall Best Sellers</h3>
            <a href="collection.php" class="view-all-link">View All →</a>
        </div>
        <div class="products-scroll">
            <?php 
            $has_best = false;
            while($p = mysqli_fetch_assoc($best_all)):
                $has_best = true;
                $sold = getSoldCount($conn, $p['id']);
                $avg = round($p['avg_rating'] ?? 0, 1);
                $stars = getRatingStars($avg);
                $count = $p['total_ratings'] ?? 0;
            ?>
            <a href="product_details.php?id=<?= $p['id'] ?>" class="product-card">
                <img src="images/<?= $p['image'] ?>" alt="<?= htmlspecialchars($p['perfume_name']) ?>">
                <div class="prod-info">
                    <h4><?= htmlspecialchars($p['perfume_name']) ?></h4>
                    <p class="prod-size"><?= $p['size'] ?></p>
                    <p class="prod-price">₱<?= number_format($p['price'], 2) ?></p>
                    <p class="prod-rating"><?= $stars ?> <?= $avg ?> (<?= $count ?>)</p>
                    <p class="prod-sold"><?= $sold ?> Sold</p>
                </div>
            </a>
            <?php endwhile; ?>
            <?php if(!$has_best): ?>
                <p class="empty-note">No sales data yet</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="category-block">
        <div class="category-header">
            <h3>Sweet Collection — Best Sellers</h3>
            <a href="collection.php?category=Sweet" class="view-all-link">View All →</a>
        </div>
        <div class="products-scroll">
            <?php 
            $has_sweet = false;
            while($p = mysqli_fetch_assoc($best_sweet)):
                $has_sweet = true;
                $sold = getSoldCount($conn, $p['id']);
                $avg = round($p['avg_rating'] ?? 0, 1);
                $stars = getRatingStars($avg);
                $count = $p['total_ratings'] ?? 0;
            ?>
            <a href="product_details.php?id=<?= $p['id'] ?>" class="product-card">
                <img src="images/<?= $p['image'] ?>" alt="<?= htmlspecialchars($p['perfume_name']) ?>">
                <div class="prod-info">
                    <h4><?= htmlspecialchars($p['perfume_name']) ?></h4>
                    <p class="prod-size"><?= $p['size'] ?></p>
                    <p class="prod-price">₱<?= number_format($p['price'], 2) ?></p>
                    <p class="prod-rating"><?= $stars ?> <?= $avg ?> (<?= $count ?>)</p>
                    <p class="prod-sold"><?= $sold ?> Sold</p>
                </div>
            </a>
            <?php endwhile; ?>
            <?php if(!$has_sweet): ?>
                <p class="empty-note">No sales data yet</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="category-block">
        <div class="category-header">
            <h3>Fresh Collection — Best Sellers</h3>
            <a href="collection.php?category=Fresh" class="view-all-link">View All →</a>
        </div>
        <div class="products-scroll">
            <?php 
            $has_fresh = false;
            while($p = mysqli_fetch_assoc($best_fresh)):
                $has_fresh = true;
                $sold = getSoldCount($conn, $p['id']);
                $avg = round($p['avg_rating'] ?? 0, 1);
                $stars = getRatingStars($avg);
                $count = $p['total_ratings'] ?? 0;
            ?>
            <a href="product_details.php?id=<?= $p['id'] ?>" class="product-card">
                <img src="images/<?= $p['image'] ?>" alt="<?= htmlspecialchars($p['perfume_name']) ?>">
                <div class="prod-info">
                    <h4><?= htmlspecialchars($p['perfume_name']) ?></h4>
                    <p class="prod-size"><?= $p['size'] ?></p>
                    <p class="prod-price">₱<?= number_format($p['price'], 2) ?></p>
                    <p class="prod-rating"><?= $stars ?> <?= $avg ?> (<?= $count ?>)</p>
                    <p class="prod-sold"><?= $sold ?> Sold</p>
                </div>
            </a>
            <?php endwhile; ?>
            <?php if(!$has_fresh): ?>
                <p class="empty-note">No sales data yet</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="category-block">
        <div class="category-header">
            <h3>Women Collection — Best Sellers</h3>
            <a href="collection.php?gender=Women" class="view-all-link">View All →</a>
        </div>
        <div class="products-scroll">
            <?php 
            $has_women = false;
            while($p = mysqli_fetch_assoc($best_women)):
                $has_women = true;
                $sold = getSoldCount($conn, $p['id']);
                $avg = round($p['avg_rating'] ?? 0, 1);
                $stars = getRatingStars($avg);
                $count = $p['total_ratings'] ?? 0;
            ?>
            <a href="product_details.php?id=<?= $p['id'] ?>" class="product-card">
                <img src="images/<?= $p['image'] ?>" alt="<?= htmlspecialchars($p['perfume_name']) ?>">
                <div class="prod-info">
                    <h4><?= htmlspecialchars($p['perfume_name']) ?></h4>
                    <p class="prod-size"><?= $p['size'] ?></p>
                    <p class="prod-price">₱<?= number_format($p['price'], 2) ?></p>
                    <p class="prod-rating"><?= $stars ?> <?= $avg ?> (<?= $count ?>)</p>
                    <p class="prod-sold"><?= $sold ?> Sold</p>
                </div>
            </a>
            <?php endwhile; ?>
            <?php if(!$has_women): ?>
                <p class="empty-note">No sales data yet</p>
            <?php endif; ?>
        </div>
    </div>
</section>
</body>
</html>