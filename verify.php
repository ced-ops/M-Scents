<?php
session_start();
include("config.php");
if(isset($_POST['verify_code'])){
    $code = trim($_POST['verify_code']);
    $email = $_SESSION['verify_email'];
    
    $result = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");
    $user = mysqli_fetch_assoc($result);
    
    if($user){
        if($user['verification_code'] === $code && strtotime($user['code_expiry']) > time()){
            mysqli_query($conn, "UPDATE users SET is_verified=1, verification_code=NULL WHERE email='$email'");
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['fullname'] = $user['fullname'];
            $_SESSION['role'] = $user['role'];
            
            echo "<script>alert('Verified Successfully! Welcome to M&Scents!'); window.location='customer/home.php';</script>";
            exit();
        }elseif(strtotime($user['code_expiry']) <= time()){
            echo "<script>alert('Code Expired! Please Register Again.'); window.location='register.php';</script>";
        }else{
            echo "<script>alert('Incorrect Verification Code! Check your email.'); window.location='verify.php';</script>";
        }
    }else{
        echo "<script>alert('User Not Found! Please Register Again.'); window.location='register.php';</script>";
    }
}
if(isset($_GET['manual'])){
    $_SESSION['verify_email'] = $_GET['manual'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Verify Your Account | M&Scents</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
    :root {
        --font-heading: 'Playfair Display', serif;
        --font-body: 'Inter', sans-serif;
        --color-gold: #d4af37;
        --color-gold-light: #f0c94d;
        --color-gold-bright: #ffd700;
        --color-black-trans: rgba(0, 0, 0, 0.65);
        --color-border: rgba(212, 175, 55, 0.3);
    }
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    html, body {
        width: 100%;
        overflow-x: hidden;
    }
    body {
        min-height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
        font-family: var(--font-body);
        position: relative;
        overflow: hidden;
    }
    @keyframes gradientFlow {
        0% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }
    @keyframes glowPulse {
        0% { box-shadow: 0 0 5px var(--color-gold), 0 0 10px rgba(212,175,55,0.2); }
        50% { box-shadow: 0 0 20px var(--color-gold), 0 0 35px rgba(212,175,55,0.4); }
        100% { box-shadow: 0 0 5px var(--color-gold), 0 0 10px rgba(212,175,55,0.2); }
    }
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(40px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    .bg-image {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: url('images/login1.jpg') no-repeat center center;
        background-size: cover;
        background-attachment: fixed;
        background-position: center center;
        z-index: -2;
    }
    .bg-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, rgba(0,0,0,0.55), rgba(0,0,0,0.7));
        z-index: -1;
    }
    .center-watermark {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        font-family: var(--font-heading);
        font-size: clamp(60px, 15vw, 200px);
        font-weight: 700;
        color: rgba(212, 175, 55, 0.06);
        letter-spacing: 4px;
        pointer-events: none;
        z-index: 0;
        user-select: none;
        animation: fadeIn 2s ease-out;
    }
    .verify-box {
        position: relative;
        z-index: 10;
        background: var(--color-black-trans);
        padding: 40px 30px;
        border-radius: 16px;
        border: 1px solid var(--color-border);
        box-shadow: 
            0 15px 50px rgba(0,0,0,0.5),
            inset 0 1px 0 rgba(255,255,255,0.05);
        width: calc(100% - 40px);
        max-width: 420px;
        backdrop-filter: blur(12px);
        animation: slideUp 1s ease-out, glowPulse 3s ease-in-out infinite;
        margin: 20px;
    }
    .verify-box h2 {
        font-family: var(--font-heading);
        text-align: center;
        font-size: 28px;
        margin-bottom: 12px;
        letter-spacing: 2px;
        font-weight: 700;
        position: relative;
        background: linear-gradient(90deg, var(--color-gold), var(--color-gold-light), var(--color-gold-bright), var(--color-gold));
        background-size: 300% 100%;
        -webkit-background-clip: text;
        background-clip: text;
        -webkit-text-fill-color: transparent;
        animation: gradientFlow 4s ease infinite;
    }
    .verify-box h2::after {
        content: '';
        display: block;
        width: 60px;
        height: 2px;
        background: linear-gradient(90deg, var(--color-gold), var(--color-gold-light), var(--color-gold));
        background-size: 200% 100%;
        margin: 12px auto 0;
        border-radius: 2px;
        animation: gradientFlow 3s linear infinite;
    }
    .verify-box p {
        color: #bbb;
        text-align: center;
        margin: 20px 0 30px;
        font-size: 14px;
        line-height: 1.6;
    }
    .verify-box input {
        width: 100%;
        padding: 16px 18px;
        margin-bottom: 24px;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(212, 175, 55, 0.2);
        border-radius: 8px;
        color: #fff;
        font-size: 32px;
        text-align: center;
        letter-spacing: 12px;
        font-weight: 600;
        outline: none;
        transition: all 0.3s ease;
    }
    .verify-box input::placeholder {
        letter-spacing: normal;
        color: #999;
    }
    .verify-box input:focus {
        border-color: var(--color-gold);
        box-shadow: 0 0 20px rgba(212, 175, 55, 0.25);
        background: rgba(255, 255, 255, 0.08);
    }
    .verify-box button {
        width: 100%;
        padding: 15px;
        background: linear-gradient(90deg, var(--color-gold), var(--color-gold-light), var(--color-gold-bright), var(--color-gold));
        background-size: 300% 100%;
        color: #000;
        border: none;
        border-radius: 8px;
        font-size: 16px;
        font-weight: 600;
        font-family: var(--font-body);
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(212, 175, 55, 0.3);
        letter-spacing: 0.5px;
        animation: gradientFlow 4s ease infinite;
    }
    .verify-box button:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(212, 175, 55, 0.5);
    }
    .verify-box button:active {
        transform: translateY(0);
    }
    @media (max-width: 480px) {
        body {
            align-items: center;
            padding: 10px 0;
        }
        .bg-image {
            background-attachment: scroll;
        }
        .verify-box {
            padding: 35px 25px;
            margin: 10px;
            width: calc(100% - 20px);
        }
        .verify-box h2 {
            font-size: 26px;
        }
        .center-watermark {
            font-size: 60px;
            letter-spacing: 2px;
        }
        .verify-box input {
            font-size: 28px;
            letter-spacing: 10px;
            padding: 15px 16px;
        }
    }
    </style>
</head>
<body>
<div class="bg-image"></div>
<div class="bg-overlay"></div>
<div class="center-watermark">M&Scents</div>
<div class="verify-box">
    <h2>Verify Your Email</h2>
    <p>We sent a 6-digit code to your email address.<br>Please check your inbox and enter the code below.</p>
    <form action="verify.php" method="POST">
        <input type="text" name="verify_code" maxlength="6" placeholder="000000" required autocomplete="off">
        <button type="submit">Verify Account</button>
    </form>
</div>
</body>
</html>