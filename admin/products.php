<?php
session_start();
include("../config.php");
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== "admin"){
    header("Location: ../login.php");
    exit();
}

if(isset($_POST['add_product'])){
    $name = mysqli_real_escape_string($conn, $_POST['perfume_name']);
    $size = mysqli_real_escape_string($conn, $_POST['size']);
    $price = mysqli_real_escape_string($conn, $_POST['price']);
    $desc = mysqli_real_escape_string($conn, $_POST['description']);
    $cat = mysqli_real_escape_string($conn, $_POST['category']);
    $gender = mysqli_real_escape_string($conn, $_POST['gender']);
    $stock = mysqli_real_escape_string($conn, $_POST['stock']);
    $rating = mysqli_real_escape_string($conn, $_POST['rating']);
    $bestseller = isset($_POST['best_seller']) ? 1 : 0;
    $custom_badge = mysqli_real_escape_string($conn, trim($_POST['custom_badge'] ?? ''));
    
    $image_name = "default_perfume.jpg";
    if(isset($_FILES['image']) && $_FILES['image']['error'] == 0){
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if(in_array($ext, $allowed)){
            $new_name = "prod_" . time() . "_" . rand(100,999) . "." . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'], "../images/" . $new_name);
            $image_name = $new_name;
        }
    }
    
    mysqli_query($conn, "INSERT INTO products (perfume_name, size, price, description, category, gender, stock, rating, best_seller, custom_badge, image, avg_rating, total_ratings) 
        VALUES ('$name', '$size', '$price', '$desc', '$cat', '$gender', '$stock', '$rating', '$bestseller', '$custom_badge', '$image_name', 0.00, 0)");
    
    echo "<script>alert('Product Added!'); window.location='products.php';</script>";
    exit();
}

if(isset($_POST['update_product'])){
    $pid = intval($_POST['product_id']);
    $name = mysqli_real_escape_string($conn, $_POST['perfume_name']);
    $size = mysqli_real_escape_string($conn, $_POST['size']);
    $price = mysqli_real_escape_string($conn, $_POST['price']);
    $desc = mysqli_real_escape_string($conn, $_POST['description']);
    $cat = mysqli_real_escape_string($conn, $_POST['category']);
    $gender = mysqli_real_escape_string($conn, $_POST['gender']);
    $stock = mysqli_real_escape_string($conn, $_POST['stock']);
    $rating = mysqli_real_escape_string($conn, $_POST['rating']);
    $bestseller = isset($_POST['best_seller']) ? 1 : 0;
    $custom_badge = mysqli_real_escape_string($conn, trim($_POST['custom_badge'] ?? ''));
    
    mysqli_query($conn, "UPDATE products SET 
        perfume_name='$name', size='$size', price='$price', description='$desc',
        category='$cat', gender='$gender', stock='$stock', rating='$rating', 
        best_seller='$bestseller', custom_badge='$custom_badge'
        WHERE id=$pid");
    
    echo "<script>alert('Product Updated!'); window.location='products.php';</script>";
    exit();
}

if(isset($_GET['del'])){
    $id = intval($_GET['del']);
    mysqli_query($conn, "DELETE FROM products WHERE id='$id'");
    echo "<script>alert('Product Deleted!'); window.location='products.php';</script>";
    exit();
}

$result = mysqli_query($conn, "SELECT * FROM products ORDER BY id DESC");
$low_stock_count = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM products WHERE stock <= 5"));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Manage Products — M&Scents</title>
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
        --sidebar-width: 250px;
    }
    html, body {
        width: 100%;
        max-width: 100%;
        overflow-x: hidden;
        margin: 0;
        padding: 0;
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
    }
    .menu-toggle {
        display: none; flex-direction: column; gap: 4px;
        background: transparent; border: none; cursor: pointer;
        padding: 6px 8px; margin-right: 4px;
    }
    .menu-toggle span {
        width: 22px; height: 2px; background: var(--color-gold);
        border-radius: 2px; transition: all 0.3s;
    }
    .logo-group { display: flex; align-items: center; gap: 10px; }
    .logo-img { height: 45px; width: auto; }
    .logo-text {
        font-family: var(--font-heading); font-size: 18px;
        color: var(--color-gold); font-weight: 700;
        text-decoration: none; letter-spacing: 0.5px;
    }
    .admin-layout { 
        display: grid; 
        grid-template-columns: var(--sidebar-width) 1fr; 
        min-height: calc(100vh - 69px); 
        width: 100%; 
    }
    .sidebar {
        background: var(--color-bg-card); border-right: 1px solid var(--color-border);
        padding: 25px 15px; transition: transform 0.3s ease;
    }
    .sidebar h2 { font-size: 17px; color: var(--color-gold); margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid var(--color-border); }
    .sidebar a {
        display: block; color: var(--color-text-muted); padding: 10px 14px; margin: 5px 0;
        text-decoration: none; border-radius: 7px; font-weight: 500; font-size: 14px; transition: all 0.25s;
    }
    .sidebar a:hover, .sidebar a.active { background: rgba(212,175,55,0.1); color: var(--color-gold); }
    
    .content { 
        padding: 20px; 
        width: 100%; 
    }
    .content h1 { font-size: 24px; margin-bottom: 6px; color: var(--color-gold); }
    .desc { color: var(--color-text-muted); margin-bottom: 18px; font-size: 14px; }
    .alert-box {
        background: rgba(239,68,68,0.08); border: 1px solid rgba(239,68,68,0.25);
        border-radius: 10px; padding: 14px 16px; margin-bottom: 22px;
        display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
    }
    .alert-box strong { color: var(--color-danger); font-size: 14px; }
    .alert-box p { color: #ccc; font-size: 13px; }
    
    .form-card {
        background: var(--color-bg-card); border: 1px solid var(--color-border);
        border-radius: 12px; padding: 22px; margin-bottom: 25px;
        animation: fadeInUp 0.5s ease;
        width: 100%;
    }
    .form-card:hover { border-color: rgba(212,175,55,0.2); }
    
    .form-grid { 
        display: grid; 
        grid-template-columns: repeat(2, 1fr); 
        gap: 14px 18px; 
        width: 100%; 
    }
    .form-grid .full { grid-column: 1 / -1; }
    
    .form-card label { 
        color: var(--color-text-muted); 
        font-size: 13px; 
        display: block; 
        margin-bottom: 5px; 
        margin-top: 4px; 
    }
    .form-card input, .form-card select, .form-card textarea {
        width: 100%; 
        padding: 12px 14px; 
        background: var(--color-bg-dark);
        border: 1px solid var(--color-border); 
        color: var(--color-text);
        border-radius: 7px; 
        font-size: 14px; 
        transition: all 0.25s;
        font-family: var(--font-body);
    }
    .form-card textarea {
        min-height: 90px;
        resize: vertical;
    }
    .form-card input:focus, .form-card select:focus, .form-card textarea:focus {
        outline: none; border-color: var(--color-gold);
        box-shadow: 0 0 0 3px rgba(212,175,55,0.1);
    }
    .upload-box { 
        border: 2px dashed var(--color-border); 
        padding: 18px; 
        border-radius: 8px; 
        text-align: center; 
        transition: all 0.25s ease; 
    }
    .upload-box:hover { border-color: var(--color-gold); background: rgba(212,175,55,0.03); }
    .upload-note, .badge-note { color: #888; font-size: 12px; margin-top: 6px; }
    .form-card button {
        margin-top: 16px; padding: 12px 28px;
        background: linear-gradient(135deg, var(--color-gold), var(--color-gold-light));
        color: #000; border: none; border-radius: 7px;
        font-weight: 600; font-size: 15px; cursor: pointer; transition: all 0.3s ease;
    }
    .form-card button:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(212,175,55,0.25); }
    .cancel-btn { background: #374151; color: #fff; margin-left: 10px; }
    .cancel-btn:hover { transform: none; box-shadow: none; }
    
    .table-card { 
        background: var(--color-bg-card); 
        border: 1px solid var(--color-border); 
        border-radius: 12px; 
        padding: 22px; 
        overflow-x: auto;
        width: 100%;
    }
    .table-card h3 { font-size: 17px; margin-bottom: 16px; color: var(--color-gold); }
    table { width: 100%; border-collapse: separate; border-spacing: 0; min-width: 750px; }
    th, td {
        padding: 12px 10px; text-align: left; border-bottom: 1px solid var(--color-border);
        color: var(--color-text); font-size: 13px; vertical-align: middle;
    }
    th { color: var(--color-gold); font-weight: 600; background: rgba(212,175,55,0.05); }
    tr:hover td { background: rgba(212,175,55,0.03); }
    tr:last-child td { border-bottom: none; }
    .low-stock-row { background: rgba(239,68,68,0.06); }
    
    .prod-img {
        width: 50px;
        height: 50px;
        object-fit: cover;
        border-radius: 8px;
        border: 1px solid var(--color-border);
    }
    .stock-badge {
        display: inline-block; padding: 4px 10px; border-radius: 20px;
        font-size: 11px; font-weight: 600; white-space: nowrap;
    }
    .badge-normal { background: rgba(74,222,128,0.1); color: var(--color-success); }
    .badge-low { background: rgba(239,68,68,0.15); color: var(--color-danger); }
    .badge-out { background: rgba(239,68,68,0.25); color: #fff; }
    .edit-btn, .del-btn {
        display: inline-block; padding: 6px 12px; text-decoration: none;
        border-radius: 6px; font-size: 12px; font-weight: 500;
        transition: all 0.25s; margin-right: 4px; white-space: nowrap;
    }
    .edit-btn { background: rgba(59,130,246,0.15); color: #60a5fa; }
    .edit-btn:hover { background: rgba(59,130,246,0.3); color: #fff; }
    .del-btn { background: rgba(239,68,68,0.1); color: #f87171; }
    .del-btn:hover { background: rgba(239,68,68,0.2); color: #fff; }
    
    .edit-form-card {
        background: rgba(30,30,30,0.9); border: 2px solid var(--color-gold);
        border-radius: 12px; padding: 22px; margin-top: 25px;
        display: none; animation: fadeInUp 0.4s ease;
        width: 100%;
    }
    .edit-form-card.show { display: block; }
    
    .overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 5; }
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    
    @media(max-width: 768px){
        .menu-toggle { display: flex; }
        .admin-layout { grid-template-columns: 1fr; }
        .sidebar { position: fixed; top: 69px; left: 0; bottom: 0; width: 250px; z-index: 10; transform: translateX(-100%); }
        .sidebar.show-menu { transform: translateX(0); box-shadow: 4px 0 20px rgba(0,0,0,0.4); }
        .content { padding: 15px; padding-bottom: 30px; }
        .logo-img { height: 40px; }
        .logo-text { font-size: 16px; }
        .content h1 { font-size: 20px; }
        
        .form-grid { grid-template-columns: 1fr; gap: 12px; }
        .form-card, .table-card, .edit-form-card { padding: 18px 15px; }
        .prod-img { width: 40px; height: 40px; }
        table { display: block; overflow-x: auto; }
        th, td { padding: 10px 8px; font-size: 12px; }
    }
    </style>
</head>
<body>
<div class="topnav">
    <button class="menu-toggle" id="menuToggle" aria-label="Toggle Menu">
        <span></span><span></span><span></span>
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
        <a href="products.php" class="active">Manage Products</a>
        <a href="payments.php">Payments</a>
        <a href="sales.php">Sales Report</a>
        <a href="reviews.php">Customer Reviews</a>
        <a href="messages.php">Customer Messages</a>
        <a href="../logout.php">Logout</a>
    </div>
    <div class="content">
        <h1>Manage Products</h1>
        <p class="desc">Add new perfumes and update your product catalog</p>
        <?php if($low_stock_count > 0): ?>
        <div class="alert-box">
            <strong>Low Stock Alert</strong>
            <p>There are <?= $low_stock_count ?> product(s) with low stock!</p>
        </div>
        <?php endif; ?>
        <div class="form-card">
            <h3>Add New Perfume</h3>
            <form method="post" enctype="multipart/form-data" class="form-grid">
                <div class="full">
                    <input type="text" name="perfume_name" placeholder="Perfume Name" required>
                </div>
                <div>
                    <label>Size</label>
                    <select name="size" required>
                        <option value="">-- Select Size --</option>
                        <option value="30ML">30ML</option>
                        <option value="50ML">50ML</option>
                    </select>
                </div>
                <div>
                    <label>Price (₱)</label>
                    <input type="number" step="0.01" name="price" placeholder="Enter Price" required>
                </div>
                <div>
                    <label>Stock Quantity</label>
                    <input type="number" name="stock" placeholder="e.g. 20" required>
                </div>
                <div>
                    <label>Category</label>
                    <select name="category">
                        <option value="">Select Category</option>
                        <option value="Sweet">Sweet</option>
                        <option value="Fresh">Fresh</option>
                        <option value="Women">Women</option>
                    </select>
                </div>
                <div>
                    <label>For</label>
                    <select name="gender">
                        <option value="">Select For</option>
                        <option value="Men">Men</option>
                        <option value="Women">Women</option>
                        <option value="Unisex">Unisex</option>
                    </select>
                </div>
                <div>
                    <label>Rating</label>
                    <input type="number" step="0.1" name="rating" placeholder="0.0 - 5.0" value="5.0">
                </div>
                <div>
                    <label>Badge Text</label>
                    <input type="text" name="custom_badge" placeholder="e.g. TOP 1 / BEST FOR SUMMER">
                    <p class="badge-note">Optional: Leave blank for none.</p>
                </div>
                <div class="full upload-box">
                    <label>Product Image</label>
                    <input type="file" name="image" accept=".jpg,.jpeg,.png,.gif,.webp">
                    <p class="upload-note">Auto-saved to images folder!</p>
                </div>
                <div class="full">
                    <label>Description</label>
                    <textarea name="description" placeholder="Product Description" rows="3"></textarea>
                </div>
                <div class="full">
                    <label style="display:flex;align-items:center;gap:8px;">
                        <input type="checkbox" name="best_seller"> Mark as Best Seller
                    </label>
                </div>
                <div class="full">
                    <button type="submit" name="add_product">Add Product</button>
                </div>
            </form>
        </div>
        <div class="table-card">
            <h3>Product List</h3>
            <table>
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Size</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Category</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($p = mysqli_fetch_assoc($result)): ?>
                    <tr class="<?= $p['stock'] <= 5 ? 'low-stock-row' : '' ?>">
                        <td>
                            <img src="../images/<?= htmlspecialchars($p['image']) ?>" 
                                 alt="<?= htmlspecialchars($p['perfume_name']) ?>" 
                                 class="prod-img"
                                 onerror="this.src='../images/default_perfume.jpg'">
                        </td>
                        <td><strong><?php echo htmlspecialchars($p['perfume_name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($p['size']); ?></td>
                        <td>₱<?php echo number_format($p['price'],2); ?></td>
                        <td>
                            <?php 
                                if($p['stock'] == 0){
                                    echo '<span class="stock-badge badge-out">SOLD OUT</span>';
                                } elseif($p['stock'] <= 5){
                                    echo '<span class="stock-badge badge-low">'.$p['stock'].' left</span>';
                                } else {
                                    echo '<span class="stock-badge badge-normal">'.$p['stock'].'</span>';
                                }
                            ?>
                        </td>
                        <td><?php echo htmlspecialchars($p['category'] ?: '-'); ?></td>
                        <td>
                            <a href="#" class="edit-btn" onclick="showEditForm(
                                <?= $p['id'] ?>, 
                                '<?= addslashes($p['perfume_name']) ?>', 
                                '<?= addslashes($p['size']) ?>', 
                                '<?= $p['price'] ?>', 
                                <?= $p['stock'] ?>, 
                                '<?= addslashes($p['category']) ?>', 
                                '<?= addslashes($p['gender']) ?>', 
                                <?= $p['rating'] ?>, 
                                '<?= addslashes($p['custom_badge'] ?? '') ?>', 
                                '<?= addslashes($p['description']) ?>', 
                                <?= $p['best_seller'] ? 'true' : 'false' ?>
                            ); return false;">Edit</a>
                            <a href="products.php?del=<?php echo $p['id']; ?>" class="del-btn" onclick="return confirm('Delete this product?')">Delete</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <div class="form-card edit-form-card" id="editFormBox">
            <h3>Edit Product</h3>
            <form method="post" class="form-grid">
                <input type="hidden" name="product_id" id="edit_id">
                <div class="full">
                    <input type="text" name="perfume_name" id="edit_name" placeholder="Perfume Name" required>
                </div>
                <div>
                    <label>Size</label>
                    <select name="size" id="edit_size" required>
                        <option value="">-- Select Size --</option>
                        <option value="30ML">30ML</option>
                        <option value="50ML">50ML</option>
                    </select>
                </div>
                <div>
                    <label>Price (₱)</label>
                    <input type="number" step="0.01" name="price" id="edit_price" placeholder="Enter Price" required>
                </div>
                <div>
                    <label>Stock Quantity</label>
                    <input type="number" name="stock" id="edit_stock" placeholder="Stock Quantity" required style="border-color:#d4af37;border-width:2px;">
                </div>
                <div>
                    <label>Category</label>
                    <select name="category" id="edit_cat">
                        <option value="">Select Category</option>
                        <option value="Sweet">Sweet</option>
                        <option value="Fresh">Fresh</option>
                        <option value="Women">Women</option>
                    </select>
                </div>
                <div>
                    <label>For</label>
                    <select name="gender" id="edit_gender">
                        <option value="">Select For</option>
                        <option value="Men">Men</option>
                        <option value="Women">Women</option>
                        <option value="Unisex">Unisex</option>
                    </select>
                </div>
                <div>
                    <label>Rating</label>
                    <input type="number" step="0.1" name="rating" id="edit_rating" placeholder="Rating">
                </div>
                <div>
                    <label>Badge Text</label>
                    <input type="text" name="custom_badge" id="edit_badge_text" placeholder="Badge text">
                </div>
                <div class="full">
                    <label>Description</label>
                    <textarea name="description" id="edit_desc" placeholder="Description" rows="3"></textarea>
                </div>
                <div class="full">
                    <label style="display:flex;align-items:center;gap:8px;">
                        <input type="checkbox" name="best_seller" id="edit_best"> Mark as Best Seller
                    </label>
                </div>
                <div class="full">
                    <button type="submit" name="update_product">Save Changes</button>
                    <button type="button" class="cancel-btn" onclick="hideEditForm()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>
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
function showEditForm(id, name, size, price, stock, cat, gender, rating, badge, desc, best){
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_size').value = size;
    document.getElementById('edit_price').value = price;
    document.getElementById('edit_stock').value = stock;
    document.getElementById('edit_cat').value = cat;
    document.getElementById('edit_gender').value = gender;
    document.getElementById('edit_rating').value = rating;
    document.getElementById('edit_badge_text').value = badge;
    document.getElementById('edit_desc').value = desc;
    document.getElementById('edit_best').checked = best;
    document.getElementById('editFormBox').classList.add('show');
    window.scrollTo({top: document.getElementById('editFormBox').offsetTop - 20, behavior:'smooth'});
}
function hideEditForm(){
    document.getElementById('editFormBox').classList.remove('show');
}
</script>
</body>
</html>