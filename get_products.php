<?php
header('Content-Type: application/json');
include 'config.php'; // Menghubungkan ke database Anda

// Ambil semua data produk dari database (Urutkan dari yang terbaru)
// Sesuai struktur kolom di database Anda: id, name, category, price, image_path
$query = "SELECT id, name, price, image_path FROM products ORDER BY id DESC";
$result = mysqli_query($conn, $query);

$products = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $row['price'] = (float)$row['price']; // Konversi harga ke angka
        $products[] = $row;
    }
}

// Mengirimkan data dalam bentuk JSON ke JavaScript
echo json_encode($products);
?>