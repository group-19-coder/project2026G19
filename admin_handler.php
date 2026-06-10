<?php

require_once 'config.php';
requireAdmin();

$action   = $_POST['action'] ?? '';
$redirect = $_POST['redirect'] ?? 'admin_dashboard.php';

function logActivity($conn, $userId, $action, $targetType = null, $targetId = null, $details = null) {
    $userId     = intval($userId);
    $action     = $conn->real_escape_string($action);
    $targetType = $conn->real_escape_string($targetType ?? '');
    $targetId   = intval($targetId);
    $details    = $conn->real_escape_string($details ?? '');
    $ip         = $conn->real_escape_string($_SERVER['REMOTE_ADDR'] ?? '');
    $conn->query("INSERT INTO activity_log (user_id, action, target_type, target_id, details, ip_address)
                  VALUES ('$userId','$action','$targetType','$targetId','$details','$ip')");
}

$adminId = intval($_SESSION['user_id']);

if ($action === 'add_vendor_staff') {
    $name       = trim($_POST['name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $password   = $_POST['password'] ?? '';
    $restId     = intval($_POST['restaurant_id'] ?? 0);

    if (!$name || !$email || !$password) {
        $_SESSION['admin_error'] = 'Name, email and password are required.';
        header("Location: $redirect"); exit();
    }

    // Check duplicate email
    $chk = $conn->query("SELECT id FROM users WHERE email='" . $conn->real_escape_string($email) . "'");
    if ($chk && $chk->num_rows > 0) {
        $_SESSION['admin_error'] = "Email '$email' already exists.";
        header("Location: $redirect"); exit();
    }

    $nameE    = $conn->real_escape_string($name);
    $emailE   = $conn->real_escape_string($email);
    $hash     = password_hash($password, PASSWORD_DEFAULT);
    $hashE    = $conn->real_escape_string($hash);
    $restVal  = $restId > 0 ? "'$restId'" : "NULL";

    $conn->query("INSERT INTO users (name, email, password, role, restaurant_id, created_by)
                  VALUES ('$nameE','$emailE','$hashE','vendor_staff',$restVal,'$adminId')");
    $newId = $conn->insert_id;

    logActivity($conn, $adminId, 'add_vendor_staff', 'user', $newId, "Added vendor staff: $name ($email)");
    $_SESSION['admin_toast'] = "Vendor staff '$name' created successfully!";
    header("Location: $redirect"); exit();
}


if ($action === 'edit_vendor_staff') {
    $userId = intval($_POST['user_id'] ?? 0);
    $name   = trim($_POST['name'] ?? '');
    $email  = trim($_POST['email'] ?? '');
    $restId = intval($_POST['restaurant_id'] ?? 0);
    $newPw  = trim($_POST['new_password'] ?? '');

    if (!$userId || !$name || !$email) {
        $_SESSION['admin_error'] = 'User ID, name and email are required.';
        header("Location: $redirect"); exit();
    }

    $nameE   = $conn->real_escape_string($name);
    $emailE  = $conn->real_escape_string($email);
    $restVal = $restId > 0 ? "'$restId'" : "NULL";

    $pwClause = '';
    if ($newPw) {
        $hash    = password_hash($newPw, PASSWORD_DEFAULT);
        $hashE   = $conn->real_escape_string($hash);
        $pwClause = ", password='$hashE'";
    }

    $conn->query("UPDATE users SET name='$nameE', email='$emailE', restaurant_id=$restVal $pwClause
                  WHERE id='$userId' AND role='vendor_staff'");

    logActivity($conn, $adminId, 'edit_vendor_staff', 'user', $userId, "Updated vendor: $name");
    $_SESSION['admin_toast'] = "Staff '$name' updated successfully!";
    header("Location: $redirect"); exit();
}


if ($action === 'delete_user') {
    $userId = intval($_POST['user_id'] ?? 0);
    if (!$userId) { header("Location: $redirect"); exit(); }

    // Don't allow deleting self or another admin
    $usr = $conn->query("SELECT name, role FROM users WHERE id='$userId'")->fetch_assoc();
    if (!$usr || $usr['role'] === 'admin') {
        $_SESSION['admin_error'] = 'Cannot delete admin accounts.';
        header("Location: $redirect"); exit();
    }

    $conn->query("DELETE FROM users WHERE id='$userId' AND role != 'admin'");
    logActivity($conn, $adminId, 'delete_user', 'user', $userId, "Deleted user: " . ($usr['name'] ?? ''));
    $_SESSION['admin_toast'] = " User '{$usr['name']}' deleted.";
    header("Location: $redirect"); exit();
}

if ($action === 'assign_restaurant') {
    $userId = intval($_POST['user_id'] ?? 0);
    $restId = intval($_POST['restaurant_id'] ?? 0);
    $restVal = $restId > 0 ? "'$restId'" : "NULL";

    $conn->query("UPDATE users SET restaurant_id=$restVal WHERE id='$userId' AND role='vendor_staff'");
    logActivity($conn, $adminId, 'assign_restaurant', 'user', $userId, "Assigned restaurant_id=$restId");
    $_SESSION['admin_toast'] = "Restaurant assignment updated.";
    header("Location: $redirect"); exit();
}


if ($action === 'add_restaurant') {
    $name    = $conn->real_escape_string(trim($_POST['name'] ?? ''));
    $area    = $conn->real_escape_string(trim($_POST['area'] ?? ''));
    $address = $conn->real_escape_string(trim($_POST['address'] ?? ''));

    if ($name && $area) {
        $conn->query("INSERT INTO restaurants (name, area, address) VALUES ('$name','$area','$address')");
        $newId = $conn->insert_id;
        logActivity($conn, $adminId, 'add_restaurant', 'restaurant', $newId, "Added restaurant: $name");
        $_SESSION['admin_toast'] = "Restaurant '$name' added!";
    }
    header("Location: $redirect"); exit();
}

if ($action === 'delete_restaurant') {
    $restId = intval($_POST['restaurant_id'] ?? 0);
    $name   = $conn->query("SELECT name FROM restaurants WHERE id='$restId'")->fetch_assoc()['name'] ?? '';
    $conn->query("DELETE FROM restaurants WHERE id='$restId'");
    logActivity($conn, $adminId, 'delete_restaurant', 'restaurant', $restId, "Deleted restaurant: $name");
    $_SESSION['admin_toast'] = "Restaurant '$name' deleted.";
    header("Location: $redirect"); exit();
}

header("Location: $redirect");
exit();
