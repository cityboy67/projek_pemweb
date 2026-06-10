<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "kaktus_centre_db";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die(json_encode(["success" => false, "message" => "Koneksi database gagal: " . $conn->connect_error]));
}
?>