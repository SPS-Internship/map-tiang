<?php
include_once '../../config/database.php';
include_once '../../model/odp.php';
include_once '../../util/cors.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'POST') { // allow POST fallback
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
    exit;
}

$required = ['id_odp'];
foreach ($required as $r) {
    if (!isset($data[$r])) {
        echo json_encode(['success' => false, 'message' => 'Missing field: ' . $r]);
        exit;
    }
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

    // Only update provided fields
    if (isset($data['id_placemark'])) $odp->id_placemark = (int)$data['id_placemark'];
    if (isset($data['nama_odp'])) $odp->nama_odp = $data['nama_odp'];
    if (isset($data['kd_layanan'])) $odp->kd_layanan = $data['kd_layanan'];
    if (isset($data['status_wo'])) $odp->status_wo = $data['status_wo'];
    if (isset($data['status_tiang'])) $odp->status_tiang = $data['status_tiang'];
    if (isset($data['lain_lain'])) $odp->lain_lain = $data['lain_lain'];

    if ($odp->update()) {
        echo json_encode([
            'success' => true,
            'message' => 'ODP updated',
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
        echo json_encode(['success' => false, 'message' => 'Failed to update ODP']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
