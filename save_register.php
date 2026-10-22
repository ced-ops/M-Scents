<?php
session_start();
include("config.php");

if (isset($_POST['fullname']) && isset($_POST['email']) && isset($_POST['password']) && isset($_POST['confirm_password'])) {
    
    // Linisin at i-escape ang input — proteksyon laban sa SQL Injection
    $fullname = mysqli_real_escape_string($conn, trim($_POST['fullname']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    
    // ✅ Magkaparehas ba ang password?
    if ($password !== $confirm_password) {
        echo "<script>
        alert('❌ Password does not match! Please re-type your password.');
        window.location='register.php';
        </script>";
        exit();
    }
    
    // ✅ Sapat ba ang haba ng password?
    if (strlen($password) < 6) {
        echo "<script>
        alert('❌ Password must be at least 6 characters long.');
        window.location='register.php';
        </script>";
        exit();
    }
    
    // ✅ Valid ba ang email format?
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>
        alert('❌ Invalid email format!');
        window.location='register.php';
        </script>";
        exit();
    }
    
    // ✅ May kaparehas na ba na email?
    $check_email = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");
    if (mysqli_num_rows($check_email) > 0) {
        echo "<script>
        alert('❌ Email already exists! Please login instead.');
        window.location='register.php';
        </script>";
        exit();
    }
    
    // ✅ I-HASH ANG PASSWORD — ITO ANG MAHALAGA!
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Ihanda ang verification
    $verification_code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    // ✅ I-save sa database — NAKA-HASH NA ANG PASSWORD!
    $sql = "INSERT INTO users (fullname, email, password, verification_code, code_expiry, is_verified, role) 
            VALUES ('$fullname', '$email', '$hashed_password', '$verification_code', '$expiry', 0, 'customer')";
    
    if (mysqli_query($conn, $sql)) {
        $_SESSION['verify_email'] = $email;
        $_SESSION['verify_code'] = $verification_code; // I-save sa session para madali sa verify.php
        echo "<script>
        alert('✅ Account Created! Verification Code: $verification_code');
        window.location='verify.php';
        </script>";
    } else {
        echo "<script>
        alert('❌ Registration Failed! Please try again.');
        window.location='register.php';
        </script>";
    }
} else {
    echo "<script>
    alert('❌ Please fill up the form properly!');
    window.location='register.php';
    </script>";
}
?>