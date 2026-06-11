<?php

$host     = "sql209.infinityfree.com";
$user     = "if0_42150857";
$password = "orderly06";
$database = "if0_42150857_orderlydb";

$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");
date_default_timezone_set('Asia/Kuala_Lumpur');

function nowMYT(): string {
    return (new DateTime('now', new DateTimeZone('Asia/Kuala_Lumpur')))->format('Y-m-d H:i:s');
}


$smtpHost      = 'smtp.gmail.com';
$smtpUsername  = 'shinchantester4@gmail.com';
$smtpPassword  = 'lhks ajor otxn ulpn';
$smtpPort      = 587;
$smtpFromEmail = 'shinchantester4@gmail.com';
$smtpFromName  = 'Orderly Support';


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function isVendorOrAdmin(): bool {
    return isset($_SESSION['role']) && in_array($_SESSION['role'], ['vendor_staff', 'admin']);
}

function requireVendor(): void {
    if (!isVendorOrAdmin()) {
        header("Location: vendor_login.php");
        exit();
    }
}

function isAdmin(): bool {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function requireAdmin(): void {
    if (!isAdmin()) {
        header("Location: vendor_login.php");
        exit();
    }
}

function isCustomer(): bool {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'customer';
}

function requireCustomer(): void {
    if (!isCustomer()) {
        header("Location: signup_login.php");
        exit();
    }
}


function getVendorRestaurantId(): int {
    if (isAdmin()) return 0; // 0 = no scope restriction
    return intval($_SESSION['restaurant_id'] ?? 0);
}



function enforceRestaurantScope(int $itemRestId): void {
    $vendorRestId = getVendorRestaurantId();
    if ($vendorRestId > 0 && $itemRestId !== $vendorRestId) {
        http_response_code(403);
        die('Access denied: this item does not belong to your restaurant.');
    }
}

function budgetLabel(string $b): string {
    return match($b) {
        'Cheap'     => '💚 Cheap',
        'Moderate'  => '💛 Moderate',
        'Expensive' => '🔴 Expensive',
        default     => $b
    };
}