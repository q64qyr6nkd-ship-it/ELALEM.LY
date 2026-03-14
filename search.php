<?php
include "db/db.php";

$q = trim($_GET['q'] ?? '');

if ($q === '') {
    header("Location: index.php");
    exit;
}

$stmt = $conn->prepare("
    SELECT id, category
    FROM products
    WHERE name LIKE ? AND available = 1
    LIMIT 1
");

$search = "%$q%";
$stmt->execute([$search]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if ($product) {
    header("Location: sections/{$product['category']}.php#product-{$product['id']}");
    exit;
} else {
    header("Location: index.php?not_found=1");
    exit;
}