<?php
session_start();
include("../config.php");

if(!isset($_SESSION['fullname']) || !isset($_GET['id'])){
    header("Location: orders.php");
    exit();
}

$order_id = $_GET['id'];

// ✅ KUNIN ANG ORDER DETAILS BAGO I-UPDATE
$order = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM orders WHERE id = '$order_id'"));

// ✅ BALIKAN ANG STOCKS — KUNG MAY ORDER ITEMS
if(!empty($order['product_name']) && !empty($order['quantity'])){
    // ✅ HANAPIN ANG PRODUCT ID — KAsILANGAN MO NG PRODUCT ID SA ORDERS TABLE
    // Kung may product_id ka na sa orders table — gamitin mo yan
    // Kung wala — eto ang alternatibo:
    mysqli_query($conn, "UPDATE products SET stock = stock + {$order['quantity']} WHERE perfume_name = '{$order['product_name']}'");
}

// ✅ I-UPDATE ANG STATUS BILANG CANCELLED
mysqli_query($conn, "UPDATE orders SET status = 'Cancelled' WHERE id = '$order_id'");

echo "<script>alert('✅ Order Cancelled! Stocks returned.'); window.location='orders.php';</script>";
?>