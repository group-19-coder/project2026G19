<?php
session_start();
require_once 'config.php';

if (isset($_POST['action']) && $_POST['action'] === 'signup') {
    $name     = $conn->real_escape_string(trim($_POST['name']));
    $email    = $conn->real_escape_string(trim($_POST['email']));
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    $role = 'customer';

    $check = $conn->query("SELECT email FROM users WHERE email='$email'");
    if ($check->num_rows > 0) {
        $_SESSION['signup_error'] = 'Email already exists.';
        $_SESSION['active_form']  = 'signup';
    } else {
        $conn->query("INSERT INTO users (name,email,password,role) VALUES ('$name','$email','$password','$role')");
    }
    header("Location: signup_login.php");
    exit();
}

if (isset($_POST['action']) && $_POST['action'] === 'login') {
    $email    = $conn->real_escape_string(trim($_POST['email']));
    $password = $_POST['password'];

    $result = $conn->query("SELECT * FROM users WHERE email='$email' AND role='customer'");
    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id']       = $user['id'];
            $_SESSION['name']          = $user['name'];
            $_SESSION['email']         = $user['email'];
            $_SESSION['role']          = $user['role'];
            $_SESSION['restaurant_id'] = null;
            header("Location: recommendation.php");
            exit();
        }
    }
    $_SESSION['login_error'] = 'Invalid email or password.';
    $_SESSION['active_form'] = 'login';
    header("Location: signup_login.php");
    exit();
}

header("Location: signup_login.php");
exit();
