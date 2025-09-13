<?php
include_once '../../config/database.php';
include_once '../../model/polygon.php';
include_once '../../util/cors.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    $polygon = new Polygon($db);

    // Get POST data
    $data = json_decode(file_get_contents("php://input"));

    if (!$data) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
        exit;
    }

    error_log(print_r($data, true));

    if (!$data || !isset($data->id_project) || !isset($data->coordinate)) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }

    // Set polygon properties
    $polygon->id_project = $data->id_project;
    $polygon->nama_polygon = $data->nama_polygon ?? 'Polygon ' . date('Y-m-d H:i:s');
    $polygon->deskripsi = $data->deskripsi ?? 'Auto generated polygon';
    
    // Handle coordinate data properly for PostgreSQL
    if (is_array($data->coordinate)) {
        $polygon->coordinate = json_encode($data->coordinate);
    } else if (is_string($data->coordinate)) {
        // Validate if it's already valid JSON
        $decoded = json_decode($data->coordinate);
        if (json_last_error() === JSON_ERROR_NONE) {
            $polygon->coordinate = $data->coordinate;
        } else {
            $polygon->coordinate = json_encode($data->coordinate);
        }
    } else {
        $polygon->coordinate = json_encode($data->coordinate);
    }
    
    $polygon->panjang_meter = $data->panjang_meter ?? 0; 
    
    // Create polygon
    if ($polygon->create()) {
        echo json_encode([
            'success' => true,
            'message' => 'Polygon created successfully',
            'data' => [
                'id_polygon' => $polygon->id_polygon ?? time(),
                'id_project' => $polygon->id_project,
                'nama_polygon' => $polygon->nama_polygon,
                'panjang_meter' => $polygon->panjang_meter,
                'coordinates' => json_decode($polygon->coordinate, true)
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create polygon']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>