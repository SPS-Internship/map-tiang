<?php
// Hentikan semua warning/notice agar tidak mengacaukan JSON
error_reporting(0);
ini_set('display_errors', 0);

include_once '../../config/database.php';
include_once '../../model/placemark.php';
include_once '../../util/cors.php';

header('Content-Type: application/json');

// Bersihkan buffer output sebelumnya
ob_clean();

// Pastikan method PUT
if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Koneksi database
$database = new Database();
$db = $database->getConnection();
$placemark = new Placemark($db);

// Ambil data JSON
$data = json_decode(file_get_contents("php://input"));

if (!$data || !isset($data->id_placemark)) {
    echo json_encode(['success' => false, 'message' => 'Missing id_placemark']);
    exit;
}

// Set property
$placemark->id_placemark = $data->id_placemark;
$placemark->nama_placemark = $data->nama_placemark ?? '';

// Jalankan update
try {
    if ($placemark->update()) {
        echo json_encode(['success' => true, 'message' => 'Placemark updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update placemark']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Exception: ' . $e->getMessage()]);
}

exit;
