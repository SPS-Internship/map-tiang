<?php
include_once '../../config/database.php';
include_once '../../model/odp.php';
include_once '../../util/cors.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    $odp = new Odp($db);

    $id_placemark = isset($_GET['id_placemark']) ? (int)$_GET['id_placemark'] : null;
    $stmt = $odp->read($id_placemark);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $rows]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
