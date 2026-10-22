<?php
session_start();
include("config.php");

$email = trim($_POST['email']);
$password = trim($_POST['password']);

// Hanapin ang user gamit email lang
$sql = "SELECT * FROM users WHERE email = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 1) {
    $user = mysqli_fetch_assoc($result);

    // ✅ Tignan kung HASHED o PLAIN ang password
    $password_ok = false;
    
    if (strpos($user['password'], '$2y$') === 0) {
        // Naka-hash na — gamitin ang tamang paraan
        $password_ok = password_verify($password, $user['password']);
    } else {
        // Lumang account — plain text pa para gumana hanggang lumipat lahat
        $password_ok = ($user['password'] === $password);
    }

    if ($password_ok) {
        // Admin — bypass verification
        if ($user['role'] === "admin") {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['fullname'] = $user['fullname'];
            $_SESSION['role'] = $user['role'];
            header("Location: admin/dashboard.php");
            exit();
        }

        // Customer — kailangan verified muna
        if ($user['is_verified'] == 0) {
            $_SESSION['verify_email'] = $email;
            echo "<script>
            alert('⚠️ Please Verify Your Account First! Check your Gmail for the Code.');
            window.location='verify.php';
            </script>";
            exit();
        }

        // Verified — papasukin na
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['fullname'] = $user['fullname'];
        $_SESSION['role'] = $user['role'];
        header("Location: customer/home.php");
        exit();
    }
}

// Mali ang email o password
echo "<script>
alert('❌ Invalid Email or Password');
window.location='login.php';
</script>";
exit();
?>