<?php
include_once '../../config/database.php';
include_once '../../model/odp.php';
include_once '../../util/cors.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE' && $_SERVER['REQUEST_METHOD'] !== 'POST') { // allow POST fallback
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
    exit;
}

if (!isset($data['id_odp'])) {
    echo json_encode(['success' => false, 'message' => 'Missing field: id_odp']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    $odp = new Odp($db);

    $odp->id_odp = (int)$data['id_odp'];
    if (!$odp->readOne()) {
        echo json_encode(['success' => false, 'message' => 'ODP not found']);
        exit;
    }

    if ($odp->delete()) {
        echo json_encode(['success' => true, 'message' => 'ODP deleted']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete ODP']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
