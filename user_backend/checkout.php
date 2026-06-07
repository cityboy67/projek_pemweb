<?php
header('Content-Type: application/json');
include 'config.php'; // Pastikan koneksi database Anda sudah benar

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. Ambil data teks dari FormData
    $name = isset($_POST['customer_name']) ? mysqli_real_escape_string($conn, $_POST['customer_name']) : '';
    $phone = isset($_POST['customer_phone']) ? mysqli_real_escape_string($conn, $_POST['customer_phone']) : '';
    $address = isset($_POST['customer_address']) ? mysqli_real_escape_string($conn, $_POST['customer_address']) : '';
    $cart_json = isset($_POST['cart_items']) ? $_POST['cart_items'] : '[]';
    
    $cart_items = json_decode($cart_json, true);

    if (empty($name) || empty($phone) || empty($address) || empty($cart_items)) {
        echo json_encode(['success' => false, 'message' => 'Data formulir tidak lengkap.']);
        exit;
    }

    // 2. Hitung Total Harga Belanjaan
    $total_price = 0;
    foreach ($cart_items as $item) {
        $total_price += $item['price'] * $item['quantity'];
    }

    // 3. Validasi & Proses Upload File Bukti Transfer
    if (!isset($_FILES['payment_proof']) || $_FILES['payment_proof']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'Gagal membaca file bukti transfer.']);
        exit;
    }

    $file = $_FILES['payment_proof'];
    $allowed_types = ['image/png', 'image/jpeg', 'image/jpg', 'application/pdf'];
    
    // Cek format file
    if (!in_array($file['type'], $allowed_types)) {
        echo json_encode(['success' => false, 'message' => 'Format file salah! Hanya menerima PNG, JPEG, atau PDF.']);
        exit;
    }

    // Buat nama unik untuk file agar tidak bentrok (contoh: 1716300000_bukti.png)
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $new_filename = time() . '_' . uniqid() . '.' . $ext;
    
    // Tentukan folder penyimpanan di Laragon
    $upload_dir = 'bukti_pembayaran/';
    
    // Jika folder 'uploads' belum ada, buat otomatis
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $target_file = $upload_dir . $new_filename;

    // Pindahkan file dari memori sementara ke folder uploads
    if (!move_uploaded_file($file['tmp_name'], $target_file)) {
        echo json_encode(['success' => false, 'message' => 'Gagal menyimpan file bukti transfer ke server.']);
        exit;
    }

    // 4. Masukkan Data ke Tabel Orders (Simpan nama file/path juga)
    $query_order = "INSERT INTO orders (customer_name, customer_phone, customer_address, total_price, payment_proof) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query_order);
    $stmt->bind_param("sssds", $name, $phone, $address, $total_price, $target_file);

    if ($stmt->execute()) {
        $order_id = $conn->insert_id; // Ambil ID pesanan yang baru masuk

        // 5. Masukkan Detail Item ke Tabel order_items
        foreach ($cart_items as $item) {
            $product_id = $item['id'];
            $quantity = $item['quantity'];
            $price = $item['price'];

            $query_item = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
            $stmt_item = $conn->prepare($query_item);
            $stmt_item->bind_param("iiid", $order_id, $product_id, $quantity, $price);
            $stmt_item->execute();
        }

        // Response Sukses
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menyimpan data pesanan ke database.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Metode request tidak valid.']);
}
?>