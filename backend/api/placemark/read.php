<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

include_once(__DIR__ . '/../../config/database.php');
$db = (new Database())->getConnection();

// Optional filter by id_project
$id_project = isset($_GET['id_project']) ? (int)$_GET['id_project'] : 0;

if ($id_project > 0) {
    $query = "SELECT id_placemark, id_project, nama_placemark, deskripsi, latitude, longitude, created_at
              FROM placemark WHERE id_project = :id_project
              ORDER BY id_placemark DESC";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id_project', $id_project, PDO::PARAM_INT);
} else {
    $query = "SELECT id_placemark, id_project, nama_placemark, deskripsi, latitude, longitude, created_at
              FROM placemark
              ORDER BY id_placemark DESC";
    $stmt = $db->prepare($query);
}

$stmt->execute();

$placemarks = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $placemarks[] = [
        'id_placemark'   => $row['id_placemark'],
        'id_project'     => $row['id_project'],
        'nama_placemark' => $row['nama_placemark'],
        'deskripsi'      => $row['deskripsi'],
        'latitude'       => $row['latitude'],
        'longitude'      => $row['longitude'],
        'created_at'     => $row['created_at']
    ];
}

echo json_encode([
    'success' => true,
    'data' => $placemarks
]);
