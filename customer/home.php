<?php
session_start();
if(!isset($_SESSION['fullname'])){ header("Location: ../login.php"); exit(); }
include("../config.php");

$cart_count = 0;
if(isset($_SESSION['user_id'])){
    $user_id = $_SESSION['user_id'];
    $count_q = mysqli_query($conn, "SELECT COUNT(*) as total FROM cart WHERE user_id='$user_id'");
    if($count_row = mysqli_fetch_assoc($count_q)){
        $cart_count = (int)$count_row['total'];
    }
}

$best_sellers = mysqli_query($conn, "SELECT * FROM products ORDER BY total_ratings DESC, avg_rating DESC LIMIT 8");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home | M&Scents</title>
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
        --color-border: rgba(212,175,55,0.15);
        --color-bg-card: #1a1a1a;
        --color-text: #e5e5e5;
        --color-cart-red: #ff3b30;
        --btn-size: 50px;
        --icon-size: 24px;
    }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    html, body { width: 100%; overflow-x: hidden; }
    body {
        font-family: var(--font-body);
        background: #000;
        color: #fff;
        min-height: 100vh;
    }
    h1, h2, h3 { font-family: var(--font-heading); }

    .topnav {
        position: fixed; top: 0; left: 0; right: 0; z-index: 999;
        padding: 12px 20px;
        display: flex; justify-content: space-between; align-items: center;
        background: var(--color-nav-bg);
        border-bottom: 1px solid var(--color-border);
        box-shadow: 0 2px 15px rgba(0,0,0,0.4);
        flex-wrap: wrap; height: auto;
    }
    .topnav .logo-group { display: flex; align-items: center; gap: 10px; }
    .topnav .logo-img { height: 45px; width: auto; }
    .topnav .logo-text {
        font-family: var(--font-heading); font-size: 20px; color: var(--color-gold);
        font-weight: 700; text-decoration: none; letter-spacing: 1px;
    }
    .topnav .nav-links a {
        color: #ddd; text-decoration: none; margin-left: 10px;
        font-size: 14px; font-weight: 500; position: relative; padding-bottom: 4px;
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
        display: flex; flex-direction: column; justify-content: center; align-items: center;
        text-align: center; padding: 100px 20px 60px;
        background: linear-gradient(rgba(0,0,0,0.35), rgba(0,0,0,0.65)),
                    url('../images/main.png') no-repeat center center;
        background-size: cover; background-attachment: fixed;
        background-position: center center; position: relative; width: 100%;
    }
    .hero h1 {
        font-size: clamp(32px, 8vw, 52px); color: var(--color-gold);
        margin-bottom: 12px; animation: fadeInUp 1s ease; letter-spacing: 2px;
    }
    .hero h1 span { color: #fff; }
    .hero p {
        font-size: 16px; color: #ccc; margin-bottom: 36px;
        animation: fadeInUp 1s ease 0.2s both; padding: 0 10px;
    }
    .hero-btn {
        padding: 14px 36px;
        background: linear-gradient(135deg, var(--color-gold), var(--color-gold-light));
        color: #000; border: none; border-radius: 50px;
        font-size: 15px; font-weight: 600; cursor: pointer;
        box-shadow: 0 4px 18px rgba(212,175,55,0.3);
        animation: fadeInUp 1s ease 0.4s both; transition: all 0.3s ease;
    }
    .hero-btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(212,175,55,0.4);
    }

    .section {
        padding: 80px 20px; max-width: 1200px; margin: 0 auto;
    }
    .section-heading {
        text-align: center; margin-bottom: 50px;
    }
    .section-heading h2 {
        font-size: clamp(26px, 5vw, 36px); color: var(--color-gold); margin-bottom: 10px;
    }
    .section-heading p { color: #888; font-size: 15px; }

    .best-sellers-grid {
        display: grid; grid-template-columns: repeat(4, 1fr); gap: 25px;
    }
    .product-card {
        background: var(--color-bg-card); border-radius: 14px; overflow: hidden;
        border: 1px solid var(--color-border); transition: all 0.3s ease;
        animation: fadeInUp 0.6s ease both;
    }
    .product-card:hover {
        transform: translateY(-6px); border-color: var(--color-gold);
        box-shadow: 0 12px 30px rgba(0,0,0,0.35);
    }
    .product-card a { text-decoration: none; color: inherit; display: block; }
    .product-img-wrapper {
        aspect-ratio: 3 / 4; background: #0d0d0d; overflow: hidden;
    }
    .product-img-wrapper img {
        width: 100%; height: 100%; object-fit: contain; padding: 15px;
        transition: transform 0.4s ease;
    }
    .product-card:hover img { transform: scale(1.08); }
    .product-info { padding: 18px; }
    .product-name {
        font-size: 16px; color: #fff; margin-bottom: 8px;
        font-weight: 600; line-height: 1.4;
    }
    .product-price {
        font-size: 18px; color: var(--color-gold); font-weight: 700;
    }
    .product-rating {
        margin-top: 8px; font-size: 14px; color: #facc15;
    }

    .model-section {
        width: 100%; min-height: 100vh;
        display: flex; justify-content: center; align-items: center;
        padding: 0; opacity: 0; transform: translateY(80px);
        transition: all 1.2s ease-out; overflow: hidden; background: #000;
    }
    .model-section.visible { opacity: 1; transform: translateY(0); }
    .model-img {
        width: 100%; height: 100vh; object-fit: cover; object-position: center;
        display: block; background: #000;
    }

    .floating-msg, .floating-cart {
        position: fixed; bottom: 20px;
        width: var(--btn-size); height: var(--btn-size); border-radius: 50%;
        background: linear-gradient(135deg, var(--color-gold), var(--color-gold-light));
        display: flex; align-items: center; justify-content: center;
        text-decoration: none; box-shadow: 0 4px 15px rgba(212,175,55,0.35);
        z-index: 900; transition: all 0.3s ease; overflow: visible;
        border: 2px solid rgba(212,175,55,0.4);
    }
    .floating-cart { right: 15px; }
    .floating-msg { right: 75px; }
    .floating-msg img, .floating-cart img {
        width: var(--icon-size); height: var(--icon-size);
        object-fit: contain; object-position: center; flex-shrink: 0; display: block;
    }
    .cart-badge {
        position: absolute; top: -6px; right: -6px;
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

    @media (max-width: 1000px) {
        .best-sellers-grid { grid-template-columns: repeat(3, 1fr); }
    }
    @media (max-width: 768px) {
        :root { --btn-size: 44px; --icon-size: 20px; }
        .topnav { padding: 10px 15px; flex-direction: column; gap: 8px; }
        .topnav .logo-text { font-size: 18px; }
        .topnav .nav-links a { margin-left: 8px; font-size: 13px; }
        .hero { padding: 110px 15px 60px; background-attachment: scroll; }
        .hero h1 { font-size: 28px; }
        .hero p { font-size: 15px; }
        .best-sellers-grid { grid-template-columns: repeat(2, 1fr); gap: 18px; }
        .section { padding: 60px 15px; }
        .model-section { min-height: 100vh; }
        .model-img { width: 100%; height: 100vh; object-fit: cover; min-height: auto; }
        .floating-cart { right: 10px; }
        .floating-msg { right: 60px; }
        .cart-badge { font-size: 10px; width: 18px; height: 18px; top: -4px; right: -4px; }
    }
    @media (max-width: 400px) {
        .topnav .nav-links { display: flex; flex-wrap: wrap; justify-content: center; gap: 5px; }
        .topnav .nav-links a { margin: 0 6px; }
        .best-sellers-grid { grid-template-columns: 1fr; }
    }
    </style>
</head>
<body>
<div class="topnav">
    <div class="logo-group">
        <img src="../images/logo.png" alt="M&Scents Logo" class="logo-img">
        <a href="home.php" class="logo-text">M&Scents</a>
    </div>
    <div class="nav-links">
        <a href="home.php">Home</a>
        <a href="../collection.php">Collection</a>
        <a href="dashboard.php">Dashboard</a>
        <a href="../logout.php">Logout</a>
    </div>
</div>

<section class="hero">
    <h1>Welcome, <span><?php echo $_SESSION['fullname']; ?></span></h1>
    <p>Thank you for shopping at M&Scents.</p>
    <button class="hero-btn" onclick="location='../collection.php'">Browse Perfumes</button>
</section>

<section class="section">
    <div class="section-heading">
        <h2>Best Sellers</h2>
        <p>Our most loved fragrances</p>
    </div>
    <div class="best-sellers-grid">
        <?php while($product = mysqli_fetch_assoc($best_sellers)): 
            $avg = number_format($product['avg_rating'] ?? 0, 1);
            $full_stars = floor($product['avg_rating'] ?? 0);
        ?>
        <div class="product-card">
            <a href="../product_details.php?id=<?php echo $product['id']; ?>">
                <div class="product-img-wrapper">
                    <img src="../images/<?php echo $product['image']; ?>" alt="<?php echo htmlspecialchars($product['perfume_name']); ?>">
                </div>
                <div class="product-info">
                    <h3 class="product-name"><?php echo $product['perfume_name']; ?></h3>
                    <p class="product-price">₱<?php echo number_format($product['price'], 2); ?></p>
                    <p class="product-rating">
                        <?php for($i=1; $i<=5; $i++) echo $i <= $full_stars ? "★" : "☆"; ?>
                        <?php echo $avg; ?>
                    </p>
                </div>
            </a>
        </div>
        <?php endwhile; ?>
    </div>
</section>

<section class="model-section" id="model1">
    <img src="../images/model1.png" alt="M&Scents" class="model-img">
</section>

<section class="section">
    <div class="section-heading">
        <h2>Curated For You</h2>
        <p>Discover your next signature scent</p>
    </div>
    <div class="best-sellers-grid">
        <?php
        $others = mysqli_query($conn, "SELECT * FROM products ORDER BY RAND() LIMIT 4");
        while($product = mysqli_fetch_assoc($others)):
        ?>
        <div class="product-card">
            <a href="../product_details.php?id=<?php echo $product['id']; ?>">
                <div class="product-img-wrapper">
                    <img src="../images/<?php echo $product['image']; ?>" alt="<?php echo htmlspecialchars($product['perfume_name']); ?>">
                </div>
                <div class="product-info">
                    <h3 class="product-name"><?php echo $product['perfume_name']; ?></h3>
                    <p class="product-price">₱<?php echo number_format($product['price'], 2); ?></p>
                </div>
            </a>
        </div>
        <?php endwhile; ?>
    </div>
</section>

<section class="model-section" id="model2">
    <img src="../images/model2.png" alt="M&Scents" class="model-img">
</section>

<section class="section">
    <div class="section-heading">
        <h2>New Arrivals</h2>
        <p>Fresh scents just added</p>
    </div>
    <div class="best-sellers-grid">
        <?php
        $new = mysqli_query($conn, "SELECT * FROM products ORDER BY id DESC LIMIT 4");
        while($product = mysqli_fetch_assoc($new)):
        ?>
        <div class="product-card">
            <a href="../product_details.php?id=<?php echo $product['id']; ?>">
                <div class="product-img-wrapper">
                    <img src="../images/<?php echo $product['image']; ?>" alt="<?php echo htmlspecialchars($product['perfume_name']); ?>">
                </div>
                <div class="product-info">
                    <h3 class="product-name"><?php echo $product['perfume_name']; ?></h3>
                    <p class="product-price">₱<?php echo number_format($product['price'], 2); ?></p>
                </div>
            </a>
        </div>
        <?php endwhile; ?>
    </div>
</section>

<section class="model-section" id="model3">
    <img src="../images/model3.png" alt="M&Scents" class="model-img">
</section>

<a href="messages.php" class="floating-msg" title="Message Admin">
    <img src="../images/message.png" alt="Messages">
</a>
<a href="../cart.php" class="floating-cart <?= $cart_count == 0 ? 'empty' : '' ?>" title="Your Cart">
    <img src="../images/cart.png" alt="Cart">
    <?php if($cart_count > 0): ?>
        <span class="cart-badge"><?= $cart_count ?></span>
    <?php endif; ?>
</a>

<script>
const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
};
const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('visible');
        }
    });
}, observerOptions);
document.querySelectorAll('.model-section').forEach(section => {
    observer.observe(section);
});
</script>
</body>
</html>