<!DOCTYPE html>
<html>
<head>
    <title>Create Account | M&Scents</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
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
        from { opacity: 0; transform: translateY(40px); }
        to { opacity: 1; transform: translateY(0); }
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
    .register-box {
        position: relative;
        z-index: 10;
        background: var(--color-black-trans);
        padding: 40px 30px;
        border-radius: 16px;
        border: 1px solid var(--color-border);
        box-shadow: 0 15px 50px rgba(0,0,0,0.5), inset 0 1px 0 rgba(255,255,255,0.05);
        width: calc(100% - 40px);
        max-width: 420px;
        backdrop-filter: blur(12px);
        animation: slideUp 1s ease-out, glowPulse 3s ease-in-out infinite;
        margin: 20px;
    }
    .register-box h2 {
        font-family: var(--font-heading);
        text-align: center;
        font-size: 28px;
        margin-bottom: 30px;
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
    .register-box h2::after {
        content: '';
        display: block;
        width: 80px;
        height: 2px;
        background: linear-gradient(90deg, var(--color-gold), var(--color-gold-light), var(--color-gold));
        background-size: 200% 100%;
        margin: 12px auto 0;
        border-radius: 2px;
        animation: gradientFlow 3s linear infinite;
    }
    .register-box input {
        width: 100%;
        padding: 16px 18px;
        margin-bottom: 16px;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(212, 175, 55, 0.2);
        border-radius: 8px;
        color: #fff;
        font-size: 16px;
        font-family: var(--font-body);
        transition: all 0.3s ease;
        outline: none;
    }
    .register-box input::placeholder {
        color: #999;
    }
    .register-box input:focus {
        border-color: var(--color-gold);
        box-shadow: 0 0 20px rgba(212, 175, 55, 0.25);
        background: rgba(255, 255, 255, 0.08);
    }
    .register-box button {
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
        margin-top: 8px;
        margin-bottom: 20px;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(212, 175, 55, 0.3);
        letter-spacing: 0.5px;
        animation: gradientFlow 4s ease infinite;
    }
    .register-box button:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(212, 175, 55, 0.5);
    }
    .register-box button:active {
        transform: translateY(0);
    }
    .register-box p {
        color: #bbb;
        text-align: center;
        font-size: 14px;
        margin-bottom: 8px;
    }
    .register-box a {
        display: block;
        text-align: center;
        color: var(--color-gold);
        text-decoration: none;
        font-size: 15px;
        font-weight: 500;
        transition: all 0.3s ease;
        position: relative;
    }
    .register-box a::after {
        content: '';
        position: absolute;
        bottom: -2px;
        left: 50%;
        width: 0;
        height: 1px;
        background: linear-gradient(90deg, var(--color-gold), var(--color-gold-light), var(--color-gold));
        background-size: 200% 100%;
        transition: all 0.3s ease;
        transform: translateX(-50%);
    }
    .register-box a:hover {
        color: var(--color-gold-light);
    }
    .register-box a:hover::after {
        width: 100%;
    }
    @media (max-width: 480px) {
        body {
            align-items: center;
            padding: 10px 0;
        }
        .bg-image {
            background-attachment: scroll;
        }
        .register-box {
            padding: 35px 25px;
            margin: 10px;
            width: calc(100% - 20px);
        }
        .register-box h2 {
            font-size: 26px;
            margin-bottom: 25px;
        }
        .center-watermark {
            font-size: 60px;
            letter-spacing: 2px;
        }
        .register-box input {
            padding: 15px 16px;
            font-size: 16px;
        }
        .register-box button {
            padding: 15px;
            font-size: 16px;
        }
    }
    </style>
</head>
<body>
<div class="bg-image"></div>
<div class="bg-overlay"></div>
<div class="center-watermark">M&Scents</div>
<div class="register-box">
    <h2>Create Account</h2>
    <form action="save_register.php" method="POST">
        <input type="text" name="fullname" placeholder="Full Name" required>
        <input type="email" name="email" placeholder="Email Address" required>
        <input type="password" name="password" placeholder="Password" required>
        <input type="password" name="confirm_password" placeholder="Confirm Password" required>
        <button type="submit">Create Account</button>
    </form>
    <p>Already have an account?</p>
    <a href="login.php">Login Here</a>
</div>
</body>
</html>