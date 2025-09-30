<?php
include_once '../../config/database.php';
include_once '../../model/odp.php';
include_once '../../util/cors.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (!isset($_GET['id_odp'])) {
    echo json_encode(['success' => false, 'message' => 'Missing id_odp parameter']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    $odp = new Odp($db);
    $odp->id_odp = (int)$_GET['id_odp'];

    if ($odp->readOne()) {
        echo json_encode([
            'success' => true,
            'data' => [
                'id_odp' => $odp->id_odp,
                'id_placemark' => $odp->id_placemark,
                'nama_odp' => $odp->nama_odp,
                'kd_layanan' => $odp->kd_layanan,
                'status_wo' => $odp->status_wo,
                'status_tiang' => $odp->status_tiang,
                'lain_lain' => $odp->lain_lain
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'ODP not found']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
