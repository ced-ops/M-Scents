<?php
session_start();
include("config.php");

$cart_count = 0;
if(isset($_SESSION['user_id'])){
    $user_id = $_SESSION['user_id'];
    $count_q = mysqli_query($conn, "SELECT COUNT(*) as total FROM cart WHERE user_id='$user_id'");
    if($count_row = mysqli_fetch_assoc($count_q)){
        $cart_count = (int)$count_row['total'];
    }
}

if(isset($_GET['id'])){
    $product_id = $_GET['id'];
    $sql = "SELECT * FROM products WHERE id = '$product_id'";
    $result = mysqli_query($conn, $sql);
    
    if(mysqli_num_rows($result) > 0){
        $perfume = mysqli_fetch_assoc($result);
    } else {
        echo "Perfume not found!";
        exit();
    }
} else {
    header("Location: collection.php");
    exit();
}

$is_logged_in = isset($_SESSION['fullname']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $perfume['perfume_name']; ?> | M&Scents</title>
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
        --color-border: rgba(212,175,55,0.15);
        --color-text: #e5e5e5;
        --color-text-muted: #888;
        --color-success: #4ade80;
        --color-danger: #ef4444;
        --color-cart-red: #ff3b30;
    }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    html, body { width: 100%; overflow-x: hidden; }
    body {
        font-family: var(--font-body);
        background: var(--color-bg-dark);
        color: var(--color-text);
        min-height: 100vh;
    }
    h1, h2, h3 { font-family: var(--font-heading); }
    .topnav {
        position: fixed; top: 0; left: 0; right: 0; z-index: 999;
        padding: 12px 20px;
        display: flex; justify-content: space-between; align-items: center;
        background: rgba(0,0,0,0.85);
        border-bottom: 1px solid var(--color-border);
        box-shadow: 0 2px 15px rgba(0,0,0,0.4);
        flex-wrap: wrap; height: auto; backdrop-filter: blur(10px);
    }
    .topnav .logo-group { display: flex; align-items: center; gap: 10px; }
    .topnav .logo-img { height: 45px; width: auto; }
    .topnav .logo-text {
        font-family: var(--font-heading); font-size: 20px; color: var(--color-gold);
        font-weight: 700; text-decoration: none; letter-spacing: 1px;
    }
    .topnav .nav-links a {
        color: #ddd; text-decoration: none; margin-left: 12px;
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
    .main-content {
        padding: 100px 20px 60px; max-width: 1200px; margin: 0 auto;
    }
    .product-container {
        display: grid; grid-template-columns: 1fr 1fr; gap: 50px; align-items: center;
        animation: fadeInUp 0.8s ease;
    }
    .product-image-wrapper {
        border-radius: 20px; overflow: hidden;
        background: linear-gradient(145deg, #1a1a1a 0%, #0d0d0d 100%);
        border: 1px solid var(--color-border);
        box-shadow: 0 15px 40px rgba(0,0,0,0.4);
        position: relative; aspect-ratio: 3 / 4;
        display: flex; align-items: center; justify-content: center;
    }
    .product-image-wrapper img {
        width: 100%; height: 100%; object-fit: contain; object-position: center;
        transition: transform 0.5s ease;
    }
    .product-image-wrapper:hover img { transform: scale(1.05); }
    .name-row {
        display: flex; align-items: center; gap: 12px; flex-wrap: wrap; margin-bottom: 15px;
    }
    .product-name {
        font-size: clamp(32px, 6vw, 42px); color: #fff; letter-spacing: 1px;
        line-height: 1.2; font-family: var(--font-heading);
    }
    .custom-badge {
        display: inline-block;
        background: linear-gradient(135deg, var(--color-gold), var(--color-gold-light));
        color: #000; padding: 6px 14px; border-radius: 30px;
        font-size: 12px; font-weight: 700; letter-spacing: 0.5px;
        box-shadow: 0 4px 12px rgba(212,175,55,0.25); white-space: nowrap;
    }
    .price-tag {
        font-size: clamp(28px, 5vw, 38px); font-weight: 700; color: var(--color-gold);
        margin-bottom: 8px; letter-spacing: 1px;
    }
    .size-text { color: var(--color-text-muted); font-size: 15px; margin-bottom: 12px; }
    .stock-row {
        display: flex; align-items: center; gap: 10px; margin-bottom: 25px; flex-wrap: wrap;
    }
    .in-stock { color: var(--color-success); font-weight: 600; font-size: 15px; }
    .stock-left { color: var(--color-gold); font-size: 14px; }
    .out-stock { color: var(--color-danger); font-weight: 600; }
    .divider {
        height: 1px; background: linear-gradient(90deg, transparent, var(--color-border), transparent);
        margin: 30px 0;
    }
    .section-block h3 {
        font-size: 16px; color: var(--color-gold); margin-bottom: 12px;
        text-transform: uppercase; letter-spacing: 1px;
    }
    .section-block p { color: #ccc; line-height: 1.8; font-size: 15px; }
    .info-grid {
        display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 12px;
    }
    .info-grid p {
        padding: 10px 14px; background: rgba(212,175,55,0.04); border-radius: 8px;
        border: 1px solid rgba(212,175,55,0.08);
    }
    .info-grid p strong { color: #fff; }
    .cart-action {
        display: flex; gap: 14px; align-items: center; margin-top: 30px; flex-wrap: wrap;
    }
    .qty-wrapper {
        display: flex; align-items: center; border: 1px solid var(--color-border);
        border-radius: 10px; overflow: hidden; flex-shrink: 0;
        background: var(--color-bg-card);
    }
    .qty-wrapper label {
        padding: 0 14px; color: #ccc; font-size: 14px; font-weight: 500;
    }
    .qty-wrapper input {
        width: 70px; padding: 16px 8px; background: var(--color-bg-dark); border: none;
        color: #fff; font-size: 16px; font-weight: 600; text-align: center;
        outline: none;
    }
    .add-cart-btn {
        flex: 1; min-width: 240px; padding: 16px 24px;
        background: linear-gradient(135deg, var(--color-gold), var(--color-gold-light));
        color: #000; border: none; border-radius: 10px;
        font-size: 15px; font-weight: 700; cursor: pointer;
        transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(212,175,55,0.3);
    }
    .add-cart-btn:hover {
        transform: translateY(-2px); box-shadow: 0 8px 25px rgba(212,175,55,0.4);
    }
    .add-cart-btn:disabled, .add-cart-btn.out-of-stock {
        background: #333; color: #888; cursor: not-allowed;
        box-shadow: none; transform: none;
    }
    .login-note { margin-top: 14px; font-size: 13px; color: var(--color-text-muted); }
    .login-note a {
        color: var(--color-gold); text-decoration: none; font-weight: 500;
    }
    .back-link {
        display: inline-block; margin-top: 30px; color: var(--color-gold);
        text-decoration: none; font-size: 14px; transition: 0.2s;
    }
    .back-link:hover { text-decoration: underline; }
    .related-section { margin-top: 70px; }
    .related-title {
        font-size: 24px; color: #fff; margin-bottom: 25px;
        padding-bottom: 12px; border-bottom: 1px solid var(--color-border);
        font-family: var(--font-heading);
    }
    .related-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 16px; }
    .related-card {
        background: var(--color-bg-card); border-radius: 12px; overflow: hidden;
        border: 1px solid var(--color-border); transition: all 0.3s ease;
        animation: fadeInUp 0.6s ease both; display: flex; flex-direction: column;
    }
    .related-card:hover {
        transform: translateY(-5px); border-color: var(--color-gold);
        box-shadow: 0 10px 25px rgba(0,0,0,0.3);
    }
    .related-card a {
        text-decoration: none; color: inherit; display: flex; flex-direction: column;
        height: 100%;
    }
    .related-card img {
        width: 100%; aspect-ratio: 3 / 4; object-fit: contain; object-position: center;
        padding: 12px; background: linear-gradient(180deg, #1a1a1a, #0f0f0f);
        border-radius: 8px 8px 0 0; transition: transform 0.4s ease;
    }
    .related-card:hover img { transform: scale(1.05); }
    .related-card h4 {
        font-size: 13px; color: #fff; padding: 10px 12px 4px;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .related-card .price {
        font-size: 14px; font-weight: 700; color: var(--color-gold);
        padding: 0 12px 12px; margin-top: auto;
    }
    .reviews-section {
        margin: 60px 0 30px; padding-top: 30px; border-top: 1px solid var(--color-border);
    }
    .reviews-section h3 {
        color: var(--color-gold); margin-bottom: 20px; font-size: 20px;
    }
    .rating-summary {
        margin-bottom: 25px; padding: 18px 20px;
        background: rgba(212,175,55,0.05); border-radius: 10px;
        border: 1px solid rgba(212,175,55,0.1);
    }
    .rating-stars { color: #facc15; font-size: 22px; }
    .rating-value { color: #fff; font-size: 18px; font-weight: bold; margin-left: 10px; }
    .rating-count { color: #888; margin-left: 10px; font-size: 14px; }
    .review-item { padding: 18px 0; border-bottom: 1px solid #2a2a2a; }
    .review-header {
        display: flex; justify-content: space-between; align-items: flex-start;
        margin-bottom: 10px; flex-wrap: wrap; gap: 8px;
    }
    .reviewer-name { color: #fff; font-size: 15px; font-weight: 600; }
    .review-rating { color: #facc15; margin-left: 10px; }
    .review-date { color: #666; font-size: 12px; }
    .review-text {
        color: #ccc; font-style: italic; line-height: 1.6; padding-left: 0;
    }
    .no-reviews { color: #888; font-style: italic; }
    .floating-msg, .floating-cart {
        position: fixed; bottom: 20px; width: 50px; height: 50px; border-radius: 50%;
        background: linear-gradient(135deg, var(--color-gold), var(--color-gold-light));
        display: flex; align-items: center; justify-content: center;
        text-decoration: none; box-shadow: 0 4px 15px rgba(212,175,55,0.35);
        z-index: 900; transition: all 0.3s ease; overflow: visible;
        border: 2px solid rgba(212,175,55,0.4);
    }
    .floating-cart { right: 15px; }
    .floating-msg { right: 75px; }
    .floating-msg img, .floating-cart img {
        width: 24px; height: 24px; object-fit: contain;
    }
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
    @media (max-width: 1100px) {
        .related-grid { grid-template-columns: repeat(5, 1fr); }
    }
    @media (max-width: 900px) {
        .product-container { grid-template-columns: 1fr; gap: 35px; }
        .product-image-wrapper { max-width: 380px; margin: 0 auto; width: 100%; aspect-ratio: 3 / 4; }
        .related-grid { grid-template-columns: repeat(4, 1fr); }
    }
    @media (max-width: 768px) {
        .topnav { padding: 10px 15px; flex-direction: column; gap: 8px; }
        .topnav .nav-links a { margin-left: 8px; font-size: 13px; }
        .main-content { padding: 110px 15px 40px; }
        .product-image-wrapper { max-width: 320px; aspect-ratio: 4 / 5; }
        .related-grid { grid-template-columns: repeat(3, 1fr); }
        .info-grid { grid-template-columns: 1fr; }
        .cart-action { flex-direction: column; width: 100%; }
        .qty-wrapper { width: 100%; justify-content: center; }
        .add-cart-btn { width: 100%; }
        .floating-cart, .floating-msg { width: 44px; height: 44px; }
        .floating-cart img, .floating-msg img { width: 20px; height: 20px; }
        .floating-cart { right: 10px; }
        .floating-msg { right: 60px; }
        .cart-badge { font-size: 10px; width: 18px; height: 18px; top: -6px; right: -6px; }
    }
    @media (max-width: 480px) {
        .related-grid { grid-template-columns: repeat(2, 1fr); }
        .topnav .nav-links { display: flex; flex-wrap: wrap; justify-content: center; gap: 5px; }
        .topnav .nav-links a { margin: 0 6px; }
        .product-image-wrapper { max-width: 280px; aspect-ratio: 1 / 1.25; }
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
<a href="cart.php" class="floating-cart <?php echo ($cart_count == 0) ? 'empty' : ''; ?>" title="Your Cart">
    <img src="images/cart.png" alt="Cart">
    <?php if($cart_count > 0): ?>
        <span class="cart-badge"><?php echo $cart_count; ?></span>
    <?php endif; ?>
</a>

<div class="main-content">
    <div class="product-container">
        <div class="product-image-wrapper">
            <img src="images/<?php echo $perfume['image']; ?>" alt="<?php echo htmlspecialchars($perfume['perfume_name']); ?>">
        </div>
        <div class="product-info-wrapper">
            <div class="name-row">
                <h1 class="product-name"><?php echo $perfume['perfume_name']; ?></h1>
                <?php if(!empty($perfume['custom_badge'])): ?>
                    <span class="custom-badge"><?php echo $perfume['custom_badge']; ?></span>
                <?php endif; ?>
            </div>
            <p class="price-tag">₱<?php echo number_format($perfume['price'], 2); ?></p>
            <p class="size-text">Size: <?php echo $perfume['size']; ?></p>
            <div class="stock-row">
                <?php if($perfume['stock'] > 0): ?>
                    <span class="in-stock">In Stock</span>
                    <span class="stock-left">Only <?php echo $perfume['stock']; ?> left</span>
                <?php else: ?>
                    <span class="out-stock">Out of Stock</span>
                <?php endif; ?>
            </div>
            <div class="divider"></div>
            <div class="section-block">
                <h3>Description</h3>
                <p><?php echo $perfume['description']; ?></p>
            </div>
            <div class="info-grid">
                <?php if(!empty($perfume['category'])): ?>
                <p><strong>Category:</strong> <?php echo $perfume['category']; ?></p>
                <?php endif; ?>
                <?php if(!empty($perfume['gender'])): ?>
                <p><strong>For:</strong> <?php echo $perfume['gender']; ?></p>
                <?php endif; ?>
            </div>
            <div class="divider"></div>
            
            <?php if($perfume['stock'] > 0): ?>
                <?php if($is_logged_in): ?>
                <div class="cart-action">
                    <div class="qty-wrapper">
                        <label>Qty:</label>
                        <input type="number" id="qty" value="1" min="1" max="<?php echo $perfume['stock']; ?>">
                    </div>
                    <button class="add-cart-btn" id="add-to-cart-btn">Add to Cart</button>
                </div>
                <?php else: ?>
                <div class="cart-action">
                    <div class="qty-wrapper">
                        <label>Qty:</label>
                        <input type="number" id="qty" value="1" min="1" max="<?php echo $perfume['stock']; ?>" disabled>
                    </div>
                    <button class="add-cart-btn out-of-stock" onclick="window.location='login.php'">Login to Purchase</button>
                </div>
                <p class="login-note">Please <a href="login.php">login</a> or <a href="register.php">create an account</a> to buy this item.</p>
                <?php endif; ?>
            <?php else: ?>
                <div class="cart-action">
                    <div class="qty-wrapper">
                        <label>Qty:</label>
                        <input type="number" id="qty" value="1" disabled>
                    </div>
                    <button class="add-cart-btn out-of-stock" disabled>Out of Stock</button>
                </div>
            <?php endif; ?>
            
            <a href="collection.php" class="back-link">Back to Collection</a>
        </div>
    </div>

    <div class="related-section">
        <h2 class="related-title">You May Also Like</h2>
        <div class="related-grid">
            <?php
            $related = mysqli_query($conn, "SELECT * FROM products WHERE id != '$product_id' ORDER BY RAND() LIMIT 7");
            while($item = mysqli_fetch_assoc($related)):
            ?>
            <div class="related-card">
                <a href="product_details.php?id=<?php echo $item['id']; ?>">
                    <img src="images/<?php echo $item['image']; ?>" alt="<?php echo htmlspecialchars($item['perfume_name']); ?>">
                    <h4><?php echo $item['perfume_name']; ?></h4>
                    <p class="price">₱<?php echo number_format($item['price'], 2); ?></p>
                </a>
            </div>
            <?php endwhile; ?>
        </div>
    </div>

    <div class="reviews-section">
        <h3>Customer Reviews</h3>
        
        <?php
        $review_result = mysqli_query($conn, "SELECT * FROM orders WHERE product_id = '{$perfume['id']}' AND rating IS NOT NULL ORDER BY reviewed_at DESC");
        $total_reviews = mysqli_num_rows($review_result);
        ?>
        
        <div class="rating-summary">
            <span class="rating-stars">
                <?php 
                $avg = number_format($perfume['avg_rating'] ?? 0, 1);
                $full_stars = floor($perfume['avg_rating'] ?? 0);
                for($i=1; $i<=5; $i++){
                    echo $i <= $full_stars ? "★" : "☆";
                }
                ?>
            </span>
            <span class="rating-value"><?= $avg ?> / 5.0</span>
            <span class="rating-count">(<?= $total_reviews ?> review<?= $total_reviews != 1 ? 's' : '' ?>)</span>
        </div>
        
        <?php if($total_reviews == 0): ?>
            <p class="no-reviews">No reviews yet. Be the first to rate this product.</p>
        <?php else: ?>
            <?php while($review = mysqli_fetch_assoc($review_result)): ?>
            <div class="review-item">
                <div class="review-header">
                    <div>
                        <span class="reviewer-name"><?= htmlspecialchars($review['customer_name']) ?></span>
                        <span class="review-rating">
                            <?php 
                            $r = intval($review['rating']);
                            echo str_repeat('★', $r) . str_repeat('☆', 5 - $r);
                            ?>
                            (<?= $r ?>/5)
                        </span>
                    </div>
                    <span class="review-date">
                        <?= date('F j, Y', strtotime($review['reviewed_at'])) ?>
                    </span>
                </div>
                <?php if(!empty($review['review_text'])): ?>
                <p class="review-text">
                    "<?= htmlspecialchars($review['review_text']) ?>"
                </p>
                <?php else: ?>
                <p class="no-reviews">No written review.</p>
                <?php endif; ?>
            </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function(){
    const btn = document.getElementById('add-to-cart-btn');
    if(btn){
        btn.addEventListener('click', function(){
            const qty = document.getElementById('qty').value;
            const formData = new FormData();
            formData.append('id', <?= $perfume['id'] ?>);
            formData.append('name', '<?= addslashes($perfume['perfume_name']) ?>');
            formData.append('price', <?= $perfume['price'] ?>);
            formData.append('image', '<?= $perfume['image'] ?>');
            formData.append('size', '<?= $perfume['size'] ?>');
            formData.append('quantity', qty);
            
            fetch('add_to_cart.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.text())
            .then(msg => {
                location.reload();
            });
        });
    }
});
</script>
</body>
</html>