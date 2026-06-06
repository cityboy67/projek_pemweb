<?php
/**
 * Backend Controller Admin Panel - Kaktus Centre
 * Database interaction API delivering standard JSON payloads (Versi MySQLi).
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Menyertakan config bawaan (koneksi MySQLi milik Anda)
if (file_exists('config.php')) {
    include 'config.php';
} else {
    // Fallback jika config.php tidak ada (Menggunakan MySQLi Object)
    $host = 'localhost';
    $db   = 'kaktus_centre_db';
    $user = 'root';
    $pass = '';
    
    $conn = new mysqli($host, $user, $pass, $db);
    if ($conn->connect_error) {
        echo json_encode(["success" => false, "message" => "Koneksi database gagal: " . $conn->connect_error]);
        exit;
    }
}

$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'get_dashboard_stats':
        try {
            // Mengambil angka statistik menggunakan fetch_row (MySQLi)
            $totalProducts = $conn->query("SELECT COUNT(*) FROM products")->fetch_row()[0];
            $totalOrders = $conn->query("SELECT COUNT(*) FROM orders")->fetch_row()[0];
            $pendingOrders = $conn->query("SELECT COUNT(*) FROM orders WHERE status = 'Pending'")->fetch_row()[0];
            $completedOrders = $conn->query("SELECT COUNT(*) FROM orders WHERE status = 'Pesanan Selesai'")->fetch_row()[0];
            
            $result = $conn->query("SELECT id, customer_name, total_price, order_date, status FROM orders ORDER BY id DESC LIMIT 5");
            $recentOrders = [];
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $recentOrders[] = $row;
                }
            }

            echo json_encode([
                "total_products" => (int)$totalProducts,
                "total_orders" => (int)$totalOrders,
                "pending_orders" => (int)$pendingOrders,
                "completed_orders" => (int)$completedOrders,
                "recent_orders" => $recentOrders
            ]);
        } catch (Exception $e) { 
            echo json_encode(["error" => $e->getMessage()]); 
        }
        break;

    case 'get_products':
        try {
            $result = $conn->query("SELECT id, name, category, price, image_path FROM products ORDER BY id DESC");
            $products = [];
            // Looping data agar kompatibel di semua versi MySQLi
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $products[] = $row;
                }
            }
            echo json_encode($products);
        } catch (Exception $e) { 
            echo json_encode(["error" => $e->getMessage()]); 
        }
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
                    // Menggunakan Prepared Statement MySQLi
                    $stmt = $conn->prepare("INSERT INTO products (name, category, price, image_path) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("ssds", $name, $category, $price, $targetFilePath); // s=string, d=double/decimal
                    
                    if ($stmt->execute()) {
                        echo json_encode(["success" => true, "message" => "Produk baru berhasil ditambahkan!"]);
                    } else { 
                        echo json_encode(["success" => false, "message" => "Gagal menyimpan ke database: " . $stmt->error]); 
                    }
                    $stmt->close();
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

            if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
                $targetDir = "uploads/";
                $fileExtension = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);
                $newFileName = uniqid("prod_", true) . '.' . $fileExtension;
                $targetFilePath = $targetDir . $newFileName;

                $allowTypes = ['jpg', 'png', 'jpeg', 'gif', 'webp'];
                if (in_array(strtolower($fileExtension), $allowTypes)) {
                    if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFilePath)) {
                        // Ambil gambar lama untuk dihapus
                        $oldStmt = $conn->prepare("SELECT image_path FROM products WHERE id = ?");
                        $oldStmt->bind_param("i", $id);
                        $oldStmt->execute();
                        $res = $oldStmt->get_result();
                        if ($row = $res->fetch_assoc()) {
                            if ($row['image_path'] && file_exists($row['image_path'])) { 
                                @unlink($row['image_path']); 
                            }
                        }
                        $oldStmt->close();

                        // Update beserta gambar baru
                        $stmt = $conn->prepare("UPDATE products SET name = ?, category = ?, price = ?, image_path = ? WHERE id = ?");
                        $stmt->bind_param("ssdsi", $name, $category, $price, $targetFilePath, $id);
                        if ($stmt->execute()) {
                            echo json_encode(["success" => true, "message" => "Data produk sukses diperbarui!"]);
                        } else {
                            echo json_encode(["success" => false, "message" => "Gagal update database: " . $stmt->error]);
                        }
                        $stmt->close();
                    } else { echo json_encode(["success" => false, "message" => "Gagal mengunggah gambar baru."]); exit; }
                } else { echo json_encode(["success" => false, "message" => "Format berkas tidak didukung."]); exit; }
            } else {
                // Update tanpa ganti gambar
                $stmt = $conn->prepare("UPDATE products SET name = ?, category = ?, price = ? WHERE id = ?");
                $stmt->bind_param("ssdi", $name, $category, $price, $id);
                if ($stmt->execute()) {
                    echo json_encode(["success" => true, "message" => "Data produk sukses diperbarui!"]);
                } else {
                    echo json_encode(["success" => false, "message" => "Gagal update database: " . $stmt->error]);
                }
                $stmt->close();
            }
        }
        break;

    case 'delete_product':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_GET['id'] ?? '';
            
            // Ambil path gambar untuk dihapus dari folder
            $stmtImg = $conn->prepare("SELECT image_path FROM products WHERE id = ?");
            $stmtImg->bind_param("i", $id);
            $stmtImg->execute();
            $res = $stmtImg->get_result();
            if ($row = $res->fetch_assoc()) {
                if ($row['image_path'] && file_exists($row['image_path'])) { 
                    @unlink($row['image_path']); 
                }
            }
            $stmtImg->close();

            // Hapus data dari database
            $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                echo json_encode(["success" => true, "message" => "Produk telah berhasil dihapus."]);
            } else {
                echo json_encode(["success" => false, "message" => "Gagal menghapus: " . $stmt->error]);
            }
            $stmt->close();
        }
        break;

    case 'get_orders':
        try {
            $result = $conn->query("SELECT id, customer_name, customer_phone, customer_address, total_price, payment_proof, order_date, status FROM orders ORDER BY id DESC");
            $orders = [];
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $orders[] = $row;
                }
            }
            echo json_encode($orders);
        } catch (Exception $e) { 
            echo json_encode(["error" => $e->getMessage()]); 
        }
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
            $stmt->bind_param("i", $orderId);
            $stmt->execute();
            $res = $stmt->get_result();
            $items = [];
            while ($row = $res->fetch_assoc()) {
                $items[] = $row;
            }
            echo json_encode($items);
            $stmt->close();
        } catch (Exception $e) { 
            echo json_encode(["error" => $e->getMessage()]); 
        }
        break;

    case 'update_order_status':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_GET['id'] ?? '';
            try {
                $stmt = $conn->prepare("UPDATE orders SET status = 'Pesanan Selesai' WHERE id = ?");
                $stmt->bind_param("i", $id);
                if ($stmt->execute()) {
                    echo json_encode(["success" => true, "message" => "Status Transaksi Pesanan telah diubah menjadi 'Pesanan Selesai'!"]);
                } else {
                    echo json_encode(["success" => false, "message" => "Gagal memperbarui status: " . $stmt->error]);
                }
                $stmt->close();
            } catch (Exception $e) { 
                echo json_encode(["success" => false, "message" => "Gagal memperbarui status: " . $e->getMessage()]); 
            }
        }
        break;
}
?>