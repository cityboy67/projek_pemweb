<?php
header('Content-Type: application/json');
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Mengambil data JSON mentah dari request fetch frontend
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || empty($input['nama']) || empty($input['telepon']) || empty($input['alamat']) || empty($input['cart'])) {
        echo json_encode(['success' => false, 'message' => 'Harap lengkapi semua data formulir dan keranjang belanja Anda.']);
        exit;
    }

    $nama = $conn->real_escape_string($input['nama']);
    $telepon = $conn->real_escape_string($input['telepon']);
    $alamat = $conn->real_escape_string($input['alamat']);
    $cart = $input['cart'];
    $total_price = floatval($input['total_price']);

    // Validasi aturan bisnis: Minimal pembelian 3 pcs (sesuai info section 5 Anda)
    $total_qty = 0;
    foreach ($cart as $item) {
        $total_qty += intval($item['quantity']);
    }

    if ($total_qty < 3) {
        echo json_encode(['success' => false, 'message' => 'Gagal! Minimal pembelian produk Kaktus Centre adalah 3 pcs tanaman.']);
        exit;
    }

    // Memulai database transaction demi keamanan integritas data
    $conn->begin_transaction();

    try {
        // 1. Simpan ke data induk tabel orders
        $sql_order = "INSERT INTO orders (customer_name, customer_phone, customer_address, total_price) VALUES ('$nama', '$telepon', '$alamat', $total_price)";
        $conn->query($sql_order);
        $order_id = $conn->insert_id;

        // 2. Simpan setiap item ke tabel order_items
        $sql_item = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        
        foreach ($cart as $item) {
            $product_id = intval($item['id']);
            $quantity = intval($item['quantity']);
            $price = floatval($item['price']);
            
            $sql_item->bind_param("iiid", $order_id, $product_id, $quantity, $price);
            $sql_item->execute();
        }

        // Jika semua lancar, terapkan perubahan ke database
        $conn->commit();
        echo json_encode([
            'success' => true, 
            'message' => "Pesanan berhasil dibuat!\nID Order Anda: #" . $order_id . "\n\nTerima kasih telah berbelanja di Kaktus Centre. Tim kami akan segera menghubungi nomor telepon Anda."
        ]);

    } catch (Exception $e) {
        // Batalkan jika ada error struktural
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan internal sistem: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Metode request tidak sah.']);
}
?>