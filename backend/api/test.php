<?php
require_once(__DIR__ . "/../config/database.php");

$db = new Database();
$conn = $db->getConnection();

if ($conn) {
    echo "✅ Connection success!";
} else {
    echo "❌ Connection failed!";
}
