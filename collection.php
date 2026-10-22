<?php
session_start();
include("config.php");

$selected_category = isset($_GET['category']) ? trim($_GET['category']) : '';
$selected_gender = isset($_GET['gender']) ? trim($_GET['gender']) : '';
$selected_size = isset($_GET['size']) ? trim($_GET['size']) : '';
$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';

$cart_count = 0;
if(isset($_SESSION['user_id'])){
    $user_id = $_SESSION['user_id'];
    $count_q = mysqli_query($conn, "SELECT COUNT(*) as total FROM cart WHERE user_id='$user_id'");
    if($count_row = mysqli_fetch_assoc($count_q)){
        $cart_count = (int)$count_row['total'];
    }
}

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

$filter_result = array();
$where_parts = array();
if($selected_category !== ''){
    $where_parts[] = "category = '" . mysqli_real_escape_string($conn, $selected_category) . "'";
}
if($selected_gender !== ''){
    $where_parts[] = "gender = '" . mysqli_real_escape_string($conn, $selected_gender) . "'";
}
if($selected_size !== ''){
    $where_parts[] = "size = '" . mysqli_real_escape_string($conn, $selected_size) . "'";
}
if($search_query !== ''){
    $search_term = mysqli_real_escape_string($conn, "%$search_query%");
    $where_parts[] = "(perfume_name LIKE '$search_term' OR description LIKE '$search_term')";
}
if(!empty($where_parts)){
    $where_sql = "WHERE " . implode(" AND ", $where_parts);
    $filter_sql = "SELECT * FROM products $where_sql ORDER BY id ASC";
    $filter_query = mysqli_query($conn, $filter_sql);
    if($filter_query && mysqli_num_rows($filter_query) > 0){
        while($row = mysqli_fetch_assoc($filter_query)){
            $filter_result[] = $row;
        }
    }
}

$all_sql = "SELECT * FROM products ORDER BY id ASC";
$all_query = mysqli_query($conn, $all_sql);
$all_products = array();
if($all_query){
    while($p = mysqli_fetch_assoc($all_query)){
        $all_products[] = $p;
    }
}

$section_title = "Filtered Results";
if($search_query !== '') {
    $section_title = "Search for: \"" . htmlspecialchars($search_query) . "\"";
} elseif($selected_category == 'Sweet') {
    $section_title = "Sweet Collection";
} elseif($selected_category == 'Fresh') {
    $section_title = "Fresh Collection";
} elseif($selected_gender == 'Women') {
    $section_title = "Women Collection";
}
if($selected_size !== '') $section_title .= " — " . $selected_size;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Collection | M&Scents</title>
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
        --color-danger: #dc2626;
        --color-cart-red: #ff3b30;
        --color-btn-bg: #242424;
        --color-btn-active: linear-gradient(135deg, #d4af37, #f0c94d);
        --nav-height: 65px;
    }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    html, body { width: 100%; overflow-x: hidden; }
    body {
        font-family: var(--font-body);
        background: var(--color-bg-dark);
        color: var(--color-text);
        min-height: 100vh;
        font-size: 14px;
        line-height: 1.6;
    }
    h1, h2, h3 { font-family: var(--font-heading); font-weight: 600; }
    .topnav {
        position: fixed; top: 0; left: 0; right: 0; z-index: 999;
        background: rgba(0,0,0,0.85);
        padding: 12px 20px;
        display: flex; justify-content: space-between; align-items: center;
        border-bottom: 1px solid var(--color-border);
        height: var(--nav-height);
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
    .topnav .nav-links a:hover,
    .topnav .nav-links a.active { color: var(--color-gold); }
    .topnav .nav-links a:hover::after,
    .topnav .nav-links a.active::after { width: 100%; }
    .search-bar-desktop {
        position: fixed; top: var(--nav-height); left: 0; right: 0; z-index: 998;
        background: transparent;
        padding: 8px 20px;
        display: flex; justify-content: flex-end;
    }
    .search-bar-desktop form {
        display: flex; gap: 6px; align-items: center;
    }
    .search-bar-desktop .search-input {
        width: 220px; height: 34px; padding: 0 14px;
        background: var(--color-bg-card); border: 1px solid var(--color-border);
        border-radius: 50px; color: #fff; font-size: 14px;
        transition: all 0.3s ease;
    }
    .search-bar-desktop .search-input:focus {
        outline: none; border-color: var(--color-gold);
        box-shadow: 0 0 0 2px rgba(212,175,55,0.1);
    }
    .search-bar-desktop .search-btn {
        height: 34px; padding: 0 18px;
        background: linear-gradient(135deg, var(--color-gold), var(--color-gold-light));
        color: #000; border: none; border-radius: 50px;
        font-size: 14px; font-weight: 600; cursor: pointer;
        transition: all 0.3s ease;
    }
    .search-bar-desktop .search-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(212,175,55,0.25);
    }
    .search-bar-desktop .clear-link {
        color: #888; font-size: 13px; text-decoration: none; margin-left: 6px;
    }
    .search-bar-desktop .clear-link:hover { color: var(--color-gold); }
    .search-mobile-wrapper { display: none; }
    .main-content {
        padding: 110px 20px 60px; max-width: 1200px; margin: 0 auto;
    }
    .page-title {
        text-align: center; font-size: clamp(28px, 6vw, 42px); color: #fff;
        margin-bottom: 25px; animation: fadeInUp 0.6s ease; letter-spacing: 1px;
    }
    .page-title span { color: var(--color-gold); }
    .categories-grid {
        display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 35px;
    }
    .category-card {
        position: relative; height: 200px; border-radius: 16px; overflow: hidden;
        text-decoration: none; display: flex; flex-direction: column;
        justify-content: center; align-items: center; text-align: center;
        transition: all 0.4s ease; border: 1px solid var(--color-border);
        animation: fadeInUp 0.6s ease 0.15s both;
    }
    .category-card:nth-child(2) { animation-delay: 0.2s; }
    .category-card:nth-child(3) { animation-delay: 0.25s; }
    .category-card.sweet {
        background: linear-gradient(rgba(0,0,0,0.55), rgba(0,0,0,0.75)), url('images/sweet.jpg') center/cover no-repeat;
    }
    .category-card.fresh {
        background: linear-gradient(rgba(0,0,0,0.55), rgba(0,0,0,0.75)), url('images/fresh.jpg') center/cover no-repeat;
    }
    .category-card.women {
        background: linear-gradient(rgba(0,0,0,0.55), rgba(0,0,0,0.75)), url('images/women.jpg') center/cover no-repeat;
    }
    .category-card:hover {
        transform: translateY(-8px) scale(1.02);
        box-shadow: 0 20px 40px rgba(0,0,0,0.4); border-color: var(--color-gold);
    }
    .category-card h3 {
        font-size: 22px; color: var(--color-gold); margin-bottom: 8px;
    }
    .category-card p { font-size: 14px; color: #ccc; }
    .size-filter-bar {
        display: flex; justify-content: center; align-items: center; gap: 8px;
        margin-bottom: 25px; flex-wrap: wrap; animation: fadeInUp 0.6s ease 0.25s both;
    }
    .size-btn {
        padding: 5px 14px; font-size: 13px; font-weight: 500;
        border-radius: 50px; border: 1px solid var(--color-border);
        background: var(--color-btn-bg); color: #aaa;
        text-decoration: none; transition: all 0.3s ease;
    }
    .size-btn:hover { border-color: var(--color-gold); color: #fff; transform: translateY(-1px); }
    .size-btn.active {
        background: var(--color-btn-active); color: #000;
        border-color: transparent; font-weight: 600;
        box-shadow: 0 3px 10px rgba(212,175,55,0.2);
    }
    .search-mobile-wrapper {
        margin-bottom: 35px; animation: fadeInUp 0.6s ease 0.3s both;
    }
    .search-mobile-wrapper form {
        display: flex; gap: 8px; justify-content: center; flex-wrap: wrap;
    }
    .search-mobile-wrapper .search-input {
        flex: 1; min-width: 200px; max-width: 280px; height: 40px; padding: 0 16px;
        background: var(--color-bg-card); border: 1px solid var(--color-border);
        border-radius: 50px; color: #fff; font-size: 14px;
    }
    .search-mobile-wrapper .search-input:focus { outline: none; border-color: var(--color-gold); }
    .search-mobile-wrapper .search-btn {
        height: 40px; padding: 0 18px;
        background: linear-gradient(135deg, var(--color-gold), var(--color-gold-light));
        color: #000; border: none; border-radius: 50px; font-size: 14px; font-weight: 600; cursor: pointer;
    }
    .search-mobile-wrapper .clear-link {
        display: block; text-align: center; margin-top: 8px;
        color: var(--color-gold-light); font-size: 13px; text-decoration: none;
    }
    .section-divider {
        text-align: center; margin: 40px 0 25px; padding-bottom: 12px;
        border-bottom: 1px solid var(--color-border);
    }
    .section-divider h2 {
        font-size: 22px; color: var(--color-gold); animation: fadeInUp 0.6s ease;
    }
    .no-results {
        text-align: center; padding: 40px 20px; color: var(--color-text-muted);
        font-style: italic; font-size: 14px;
    }
    .products-grid {
        display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 24px;
    }
    .product-card-link { text-decoration: none; color: inherit; display: block; }
    .product-card {
        background: var(--color-bg-card); border-radius: 14px; overflow: hidden;
        border: 1px solid var(--color-border); transition: all 0.35s ease;
        animation: fadeInUp 0.6s ease both; border-left: 3px solid transparent;
    }
    .product-card:hover {
        transform: translateY(-6px); border-color: rgba(212,175,55,0.35);
        border-left-color: var(--color-gold); box-shadow: 0 15px 35px rgba(0,0,0,0.35);
    }
    .product-card img {
        width: 100%; height: 250px; object-fit: cover; background: #111;
        transition: transform 0.4s ease;
    }
    .product-card:hover img { transform: scale(1.05); }
    .product-info { padding: 18px; }
    .product-info h3 { font-size: 17px; color: #fff; margin-bottom: 6px; }
    .product-info .size {
        display: inline-block; padding: 3px 10px; border-radius: 20px;
        background: rgba(212,175,55,0.1); color: var(--color-gold);
        font-size: 13px; font-weight: 500; margin-bottom: 8px;
    }
    .product-info .price {
        font-size: 20px; font-weight: 700; color: var(--color-gold); margin-bottom: 6px;
    }
    .product-info .rating-line {
        font-size: 13px; color: #facc15; margin-bottom: 6px;
    }
    .product-info .sold-status {
        font-size: 13px; margin-bottom: 10px; font-weight: 500; color: #9ca3af;
    }
    .product-info .view-btn {
        display: inline-block; font-size: 13px; color: var(--color-gold);
        padding: 6px 14px; border: 1px solid var(--color-gold);
        border-radius: 20px; transition: all 0.3s ease;
    }
    .product-card:hover .view-btn {
        background: var(--color-gold); color: #000;
    }
    .floating-msg, .floating-cart {
        position: fixed; bottom: 20px; width: 50px; height: 50px; border-radius: 50%;
        background: linear-gradient(135deg, var(--color-gold), var(--color-gold-light));
        display: flex; align-items: center; justify-content: center;
        text-decoration: none; box-shadow: 0 4px 15px rgba(212,175,55,0.35);
        z-index: 900; transition: all 0.3s ease;
        overflow: visible;
        border: 2px solid rgba(212,175,55,0.4);
    }
    .floating-cart { right: 15px; }
    .floating-msg { right: 75px; }
    .floating-msg img,
    .floating-cart img {
        width: 24px; height: 24px; object-fit: contain;
    }
    .cart-badge {
        position: absolute; top: -8px; right: -8px;
        background: var(--color-cart-red);
        color: #fff;
        font-size: 11px; font-weight: 700;
        width: 20px; height: 20px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        border: 2px solid #000;
        box-shadow: 0 2px 4px rgba(0,0,0,0.3);
        line-height: 1;
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
        .topnav { padding: 10px 15px; justify-content: center; gap: 8px; flex-wrap: wrap; height: auto; }
        .topnav .logo-text { font-size: 18px; }
        .topnav .nav-links a { margin-left: 8px; font-size: 13px; }
        .search-bar-desktop { display: none; }
        .search-mobile-wrapper { display: block; }
        .main-content { padding: 90px 15px 40px; }
        .page-title { margin-bottom: 20px; }
        .categories-grid { margin-bottom: 25px; grid-template-columns: 1fr; }
        .products-grid { grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 16px; }
        .product-card img { height: 200px; }
        .size-filter-bar { gap: 6px; margin-bottom: 15px; }
        .size-btn { padding: 4px 12px; font-size: 12px; }
        .floating-msg, .floating-cart {
            width: 44px; height: 44px;
        }
        .floating-msg img,
        .floating-cart img { width: 20px; height: 20px; }
        .floating-cart { right: 10px; }
        .floating-msg { right: 60px; }
        .cart-badge { font-size: 10px; width: 18px; height: 18px; top: -6px; right: -6px; }
    }
    @media (max-width: 400px) {
        .topnav .nav-links { display: flex; flex-wrap: wrap; justify-content: center; gap: 5px; }
        .topnav .nav-links a { margin: 0 6px; }
        .products-grid { grid-template-columns: 1fr; }
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
        <?php if(isset($_SESSION['fullname'])){ ?>
            <a href="customer/home.php">Home</a>
            <a href="collection.php" class="active">Collection</a>
            <a href="customer/dashboard.php">Dashboard</a>
            <a href="logout.php">Logout</a>
        <?php }else{ ?>
            <a href="index.php">Home</a>
            <a href="collection.php" class="active">Collection</a>
            <a href="login.php">Login</a>
            <a href="register.php">Register</a>
        <?php } ?>
    </div>
</div>
<div class="search-bar-desktop">
    <form method="get" action="collection.php">
        <?php if($selected_category): ?>
            <input type="hidden" name="category" value="<?php echo htmlspecialchars($selected_category); ?>">
        <?php endif; ?>
        <?php if($selected_gender): ?>
            <input type="hidden" name="gender" value="<?php echo htmlspecialchars($selected_gender); ?>">
        <?php endif; ?>
        <?php if($selected_size): ?>
            <input type="hidden" name="size" value="<?php echo htmlspecialchars($selected_size); ?>">
        <?php endif; ?>
        <input type="text" name="q" class="search-input" placeholder="Search perfume..." value="<?php echo htmlspecialchars($search_query); ?>">
        <button type="submit" class="search-btn">Search</button>
        <?php if($search_query !== ''): ?>
            <?php 
                $clear_params = array();
                if($selected_category) $clear_params[] = "category=".urlencode($selected_category);
                if($selected_gender) $clear_params[] = "gender=".urlencode($selected_gender);
                if($selected_size) $clear_params[] = "size=".urlencode($selected_size);
                $clear_url = "collection.php" . (!empty($clear_params) ? "?".implode("&", $clear_params) : "");
            ?>
            <a href="<?php echo $clear_url; ?>" class="clear-link">Clear</a>
        <?php endif; ?>
    </form>
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
    <h1 class="page-title">Our <span>Collection</span></h1>
    <div class="categories-grid">
        <a href="collection.php?category=Sweet<?php 
            $params = array();
            if($selected_size) $params[] = "size=".urlencode($selected_size);
            if($search_query) $params[] = "q=".urlencode($search_query);
            echo !empty($params) ? "&".implode("&", $params) : "";
        ?>" class="category-card sweet">
            <h3>Sweet Collection</h3>
            <p>Warm & creamy fragrances</p>
        </a>
        <a href="collection.php?category=Fresh<?php 
            $params = array();
            if($selected_size) $params[] = "size=".urlencode($selected_size);
            if($search_query) $params[] = "q=".urlencode($search_query);
            echo !empty($params) ? "&".implode("&", $params) : "";
        ?>" class="category-card fresh">
            <h3>Fresh Collection</h3>
            <p>Clean & aquatic scents</p>
        </a>
        <a href="collection.php?gender=Women<?php 
            $params = array();
            if($selected_size) $params[] = "size=".urlencode($selected_size);
            if($search_query) $params[] = "q=".urlencode($search_query);
            echo !empty($params) ? "&".implode("&", $params) : "";
        ?>" class="category-card women">
            <h3>Women Collection</h3>
            <p>Elegant & feminine</p>
        </a>
    </div>
    <div class="size-filter-bar">
        <?php
        $base_query = array();
        if($selected_category !== '') $base_query[] = "category=" . urlencode($selected_category);
        if($selected_gender !== '') $base_query[] = "gender=" . urlencode($selected_gender);
        if($search_query !== '') $base_query[] = "q=" . urlencode($search_query);
        $base_url = "collection.php";
        if(!empty($base_query)) $base_url .= "?" . implode("&", $base_query);
        $all_url = $base_url;
        $url_30 = $base_url . (strpos($base_url, '?') === false ? "?" : "&") . "size=30ML";
        $url_50 = $base_url . (strpos($base_url, '?') === false ? "?" : "&") . "size=50ML";
        ?>
        <a href="<?php echo $all_url; ?>" class="size-btn <?php echo ($selected_size === '') ? 'active' : ''; ?>">All Sizes</a>
        <a href="<?php echo $url_30; ?>" class="size-btn <?php echo ($selected_size == '30ML') ? 'active' : ''; ?>">30ML</a>
        <a href="<?php echo $url_50; ?>" class="size-btn <?php echo ($selected_size == '50ML') ? 'active' : ''; ?>">50ML</a>
    </div>
    <div class="search-mobile-wrapper">
        <form method="get" action="collection.php">
            <?php if($selected_category): ?>
                <input type="hidden" name="category" value="<?php echo htmlspecialchars($selected_category); ?>">
            <?php endif; ?>
            <?php if($selected_gender): ?>
                <input type="hidden" name="gender" value="<?php echo htmlspecialchars($selected_gender); ?>">
            <?php endif; ?>
            <?php if($selected_size): ?>
                <input type="hidden" name="size" value="<?php echo htmlspecialchars($selected_size); ?>">
            <?php endif; ?>
            <input type="text" name="q" class="search-input" placeholder="Search perfume..." value="<?php echo htmlspecialchars($search_query); ?>">
            <button type="submit" class="search-btn">Search</button>
        </form>
        <?php if($search_query !== ''): ?>
            <?php 
                $clear_params = array();
                if($selected_category) $clear_params[] = "category=".urlencode($selected_category);
                if($selected_gender) $clear_params[] = "gender=".urlencode($selected_gender);
                if($selected_size) $clear_params[] = "size=".urlencode($selected_size);
                $clear_url = "collection.php" . (!empty($clear_params) ? "?".implode("&", $clear_params) : "");
            ?>
            <a href="<?php echo $clear_url; ?>" class="clear-link">Clear Search</a>
        <?php endif; ?>
    </div>
    <?php if($selected_category || $selected_gender || $selected_size || $search_query): ?>
    <div class="section-divider">
        <h2><?php echo $section_title; ?></h2>
    </div>
    <?php if(!empty($filter_result)): ?>
    <div class="products-grid">
        <?php foreach($filter_result as $row): 
            $sold = getSoldCount($conn, $row['id']);
            $avg_rating = round($row['avg_rating'] ?? 0, 1);
            $stars = getRatingStars($avg_rating);
            $reviews_count = $row['total_ratings'] ?? 0;
        ?>
        <a href="product_details.php?id=<?php echo $row['id']; ?>" class="product-card-link">
            <div class="product-card">
                <img src="images/<?php echo $row['image']; ?>" alt="<?php echo $row['perfume_name']; ?>">
                <div class="product-info">
                    <h3><?php echo $row['perfume_name']; ?></h3>
                    <p class="size"><?php echo $row['size']; ?></p>
                    <p class="price">₱<?php echo number_format($row['price'], 2); ?></p>
                    <p class="rating-line"><?php echo $stars; ?> <?php echo $avg_rating; ?> (<?php echo $reviews_count; ?> reviews)</p>
                    <p class="sold-status"><?php echo $sold; ?> Sold</p>
                    <span class="view-btn">View Details</span>
                </div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <p class="no-results">No products found matching your search.</p>
    <?php endif; ?>
    <?php endif; ?>
    <div class="section-divider">
        <h2>All Fragrances</h2>
    </div>
    <div class="products-grid">
        <?php foreach($all_products as $row): 
            $sold = getSoldCount($conn, $row['id']);
            $avg_rating = round($row['avg_rating'] ?? 0, 1);
            $stars = getRatingStars($avg_rating);
            $reviews_count = $row['total_ratings'] ?? 0;
        ?>
        <a href="product_details.php?id=<?php echo $row['id']; ?>" class="product-card-link">
            <div class="product-card">
                <img src="images/<?php echo $row['image']; ?>" alt="<?php echo $row['perfume_name']; ?>">
                <div class="product-info">
                    <h3><?php echo $row['perfume_name']; ?></h3>
                    <p class="size"><?php echo $row['size']; ?></p>
                    <p class="price">₱<?php echo number_format($row['price'], 2); ?></p>
                    <p class="rating-line"><?php echo $stars; ?> <?php echo $avg_rating; ?> (<?php echo $reviews_count; ?> reviews)</p>
                    <p class="sold-status"><?php echo $sold; ?> Sold</p>
                    <span class="view-btn">View Details</span>
                </div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
</div>
</body>
</html>