<?php
include_once '../../config/database.php';
include_once '../../model/odp.php';
include_once '../../util/cors.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    $odp = new Odp($db);

    $data = json_decode(file_get_contents('php://input'));
    if (!$data || !isset($data->id_placemark)) {
        echo json_encode(['success' => false, 'message' => 'Missing required field: id_placemark']);
        exit;
    }

    $odp->id_placemark = $data->id_placemark;
    $odp->nama_odp = $data->nama_odp ?? '';
    $odp->kd_layanan = $data->kd_layanan ?? '';
    $odp->status_wo = $data->status_wo ?? '';
    $odp->status_tiang = $data->status_tiang ?? '';
    $odp->lain_lain = $data->lain_lain ?? '';

    if ($odp->create()) {
        echo json_encode([
            'success' => true,
            'message' => 'ODP created successfully',
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
        echo json_encode(['success' => false, 'message' => 'Failed to create ODP']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
