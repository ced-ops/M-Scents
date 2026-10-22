<?php
session_start();
include("config.php");

if(!isset($_SESSION['user_id'])){ exit(); }

if(isset($_GET['id'])){
    $cart_id = $_GET['id'];
    mysqli_query($conn, "DELETE FROM cart WHERE id='$cart_id'");
}

header("Location: cart.php");
?>