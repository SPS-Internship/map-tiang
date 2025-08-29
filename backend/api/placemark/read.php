<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

include_once(__DIR__ . '/../../config/database.php');
$db = (new Database())->getConnection();

// Query semua placemark
$query = "SELECT id_placemark, id_project, nama_placemark, deskripsi, latitude, longitude, created_at
          FROM placemark
          ORDER BY id_placemark DESC";

$stmt = $db->prepare($query);
$stmt->execute();

$placemarks = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $placemarks[] = [
        'id_placemark'   => $row['id_placemark'],   // 🔑 ini penting
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
