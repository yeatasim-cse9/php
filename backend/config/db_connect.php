<?php
// backend/config/db_connect.php
// ডাটাবেস কানেকশন ফাইল

$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "restaurant_db";

// mysqli অবজেক্ট-ওরিয়েন্টেড অ্যাপ্রোচ দিয়ে কানেকশন
$conn = new mysqli($servername, $username, $password, $dbname);

// কানেকশন চেক
if ($conn->connect_error) {
    die("কানেকশন ফেইলড: " . $conn->connect_error);
}
// echo "কানেকশন সফল!";
?>
