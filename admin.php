<?php
/**
 * Backend Controller Admin Panel - Kaktus Centre
 * Database interaction API delivering standard JSON payloads.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Menyertakan config bawaan atau membuat instansiasi PDO mandiri
if (file_exists('config.php')) {
    include 'config.php';
} else {
    $host = 'localhost';
    $db   = 'kaktus_centre_db';
    $user = 'root';
    $pass = '';
    $charset = 'utf8mb4';

    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ATTR_ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    try {
         $conn = new PDO($dsn, $user, $pass, $options);
    } catch (\PDOException $e) {
         echo json_encode(["success" => false, "message" => "Koneksi database gagal: " . $e->getMessage()]);
         exit;
    }
}

// Sinkronisasi variabel penampung PDO handler
if (!isset($conn) && isset($pdo)) { $conn = $pdo; }

$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'get_dashboard_stats':
        try {
            $totalProducts = $conn->query("SELECT COUNT(*) FROM products")->fetchColumn();
            $totalOrders = $conn->query("SELECT COUNT(*) FROM orders")->fetchColumn();
            $pendingOrders = $conn->query("SELECT COUNT(*) FROM orders WHERE status = 'Pending'")->fetchColumn();
            $completedOrders = $conn->query("SELECT COUNT(*) FROM orders WHERE status = 'Pesanan Selesai'")->fetchColumn();
            
            $stmt = $conn->query("SELECT id, customer_name, total_price, order_date, status FROM orders ORDER BY id DESC LIMIT 5");
            $recentOrders = $stmt->fetchAll();

            echo json_encode([
                "total_products" => (int)$totalProducts,
                "total_orders" => (int)$totalOrders,
                "pending_orders" => (int)$pendingOrders,
                "completed_orders" => (int)$completedOrders,
                "recent_orders" => $recentOrders
            ]);
        } catch (PDOException $e) { echo json_encode(["error" => $e->getMessage()]); }
        break;

    case 'get_products':
        try {
            $stmt = $conn->query("SELECT id, name, category, price, image_path FROM products ORDER BY id DESC");
            echo json_encode($stmt->fetchAll());
        } catch (PDOException $e) { echo json_encode(["error" => $e->getMessage()]); }
        break;

    case 'add_product':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = $_POST['name'] ?? '';
            $category = $_POST['category'] ?? '';
            $price = $_POST['price'] ?? 0;
            
            if (empty($name) || empty($category) || empty($price) || !isset($_FILES['image'])) {
                echo json_encode(["success" => false, "message" => "Data input form belum lengkap."]);
                exit;
            }

            $targetDir = "uploads/";
            if (!is_dir($targetDir)) { mkdir($targetDir, 0755, true); }

            $fileExtension = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);
            $newFileName = uniqid("prod_", true) . '.' . $fileExtension;
            $targetFilePath = $targetDir . $newFileName;

            $allowTypes = ['jpg', 'png', 'jpeg', 'gif', 'webp'];
            if (in_array(strtolower($fileExtension), $allowTypes)) {
                if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFilePath)) {
                    try {
                        $stmt = $conn->prepare("INSERT INTO products (name, category, price, image_path) VALUES (?, ?, ?, ?)");
                        $stmt->execute([$name, $category, $price, $targetFilePath]);
                        echo json_encode(["success" => true, "message" => "Produk baru berhasil ditambahkan!"]);
                    } catch (PDOException $e) { echo json_encode(["success" => false, "message" => "Gagal ke database: " . $e->getMessage()]); }
                } else { echo json_encode(["success" => false, "message" => "Gagal mengunggah file gambar."]); }
            } else { echo json_encode(["success" => false, "message" => "Format file gambar tidak didukung."]); }
        }
        break;

    case 'edit_product':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'] ?? '';
            $name = $_POST['name'] ?? '';
            $category = $_POST['category'] ?? '';
            $price = $_POST['price'] ?? 0;

            if (empty($id) || empty($name) || empty($category) || empty($price)) {
                echo json_encode(["success" => false, "message" => "Data tidak valid."]);
                exit;
            }

            try {
                if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
                    $targetDir = "uploads/";
                    $fileExtension = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);
                    $newFileName = uniqid("prod_", true) . '.' . $fileExtension;
                    $targetFilePath = $targetDir . $newFileName;

                    $allowTypes = ['jpg', 'png', 'jpeg', 'gif', 'webp'];
                    if (in_array(strtolower($fileExtension), $allowTypes)) {
                        if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFilePath)) {
                            $oldStmt = $conn->prepare("SELECT image_path FROM products WHERE id = ?");
                            $oldStmt->execute([$id]);
                            $oldImg = $oldStmt->fetchColumn();
                            if ($oldImg && file_exists($oldImg)) { @unlink($oldImg); }

                            $stmt = $conn->prepare("UPDATE products SET name = ?, category = ?, price = ?, image_path = ? WHERE id = ?");
                            $stmt->execute([$name, $category, $price, $targetFilePath, $id]);
                        } else { echo json_encode(["success" => false, "message" => "Gagal mengunggah gambar baru."]); exit; }
                    } else { echo json_encode(["success" => false, "message" => "Format berkas tidak didukung."]); exit; }
                } else {
                    $stmt = $conn->prepare("UPDATE products SET name = ?, category = ?, price = ? WHERE id = ?");
                    $stmt->execute([$name, $category, $price, $id]);
                }
                echo json_encode(["success" => true, "message" => "Data produk sukses diperbarui!"]);
            } catch (PDOException $e) { echo json_encode(["success" => false, "message" => "Gagal memperbarui: " . $e->getMessage()]); }
        }
        break;

    case 'delete_product':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_GET['id'] ?? '';
            try {
                $stmtImg = $conn->prepare("SELECT image_path FROM products WHERE id = ?");
                $stmtImg->execute([$id]);
                $imgPath = $stmtImg->fetchColumn();
                if ($imgPath && file_exists($imgPath)) { @unlink($imgPath); }

                $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
                $stmt->execute([$id]);
                echo json_encode(["success" => true, "message" => "Produk telah berhasil dihapus."]);
            } catch (PDOException $e) { echo json_encode(["success" => false, "message" => "Gagal menghapus: " . $e->getMessage()]); }
        }
        break;

    case 'get_orders':
        try {
            $stmt = $conn->query("SELECT id, customer_name, customer_phone, customer_address, total_price, payment_proof, order_date, status FROM orders ORDER BY id DESC");
            echo json_encode($stmt->fetchAll());
        } catch (PDOException $e) { echo json_encode(["error" => $e->getMessage()]); }
        break;

    case 'get_order_items':
        $orderId = $_GET['order_id'] ?? '';
        try {
            $stmt = $conn->prepare("
                SELECT oi.quantity, oi.price, p.name AS product_name 
                FROM order_items oi
                LEFT JOIN products p ON oi.product_id = p.id
                WHERE oi.order_id = ?
            ");
            $stmt->execute([$orderId]);
            echo json_encode($stmt->fetchAll());
        } catch (PDOException $e) { echo json_encode(["error" => $e->getMessage()]); }
        break;

    case 'update_order_status':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_GET['id'] ?? '';
            try {
                $stmt = $conn->prepare("UPDATE orders SET status = 'Pesanan Selesai' WHERE id = ?");
                $stmt->execute([$id]);
                echo json_encode(["success" => true, "message" => "Status Transaksi Pesanan telah diubah menjadi 'Pesanan Selesai'!"]);
            } catch (PDOException $e) { echo json_encode(["success" => false, "message" => "Gagal memperbarui status: " . $e->getMessage()]); }
        }
        break;
}
?>