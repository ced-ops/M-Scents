<?php
session_start();
include("config.php");

if(!isset($_SESSION['user_id'])){
    echo "<script>alert('⚠️ Please Login First!'); window.location='login.php';</script>";
    exit();
}

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $user_id = $_SESSION['user_id'];
    $id = $_POST['id'];
    $name = $_POST['name'];
    $price = $_POST['price'];
    $image = $_POST['image'];
    $size = $_POST['size'];
    $qty = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;

    // ✅ BAGO ILAGAY SA CART — CHECK MUNA ANG STOCKS!
    $stock_check = mysqli_query($conn, "SELECT stock FROM products WHERE id = '$id'");
    $product = mysqli_fetch_assoc($stock_check);
    $available = $product['stock'];

    // ✅ KUNG HIGIT SA MERON — ERROR AGAD!
    if($qty > $available){
        echo "<script>alert('❌ Not enough stocks! Only $available left available.'); window.location='product_details.php?id=$id';</script>";
        exit();
    }

    // ✅ CHECK KUNG NASA CART NA — DAGDAGAN LANG PERO CHECK PA RIN ANG STOCK!
    $check = mysqli_query($conn, "SELECT * FROM cart WHERE user_id = '$user_id' AND product_id = '$id'");
    if(mysqli_num_rows($check) > 0){
        $cart_item = mysqli_fetch_assoc($check);
        $new_qty = $cart_item['quantity'] + $qty;

        // ✅ KUNG DAGDAGAN — HINDI DAPAT LAMPAS SA STOCK!
        if($new_qty > $available){
            echo "<script>alert('❌ Cannot add more! Only $available left available. You already have {$cart_item['quantity']} in cart.'); window.location='product_details.php?id=$id';</script>";
            exit();
        }

        mysqli_query($conn, "UPDATE cart SET quantity = quantity + '$qty' WHERE user_id = '$user_id' AND product_id = '$id'");
    }else{
        // ✅ HINDI PA NASA CART — ILAGAY
        mysqli_query($conn, "INSERT INTO cart (user_id, product_id, name, price, image, size, quantity) 
            VALUES ('$user_id', '$id', '$name', '$price', '$image', '$size', '$qty')");
    }

    echo "<script>alert('✅ Added to Cart!'); window.location='cart.php';</script>";
}
?>