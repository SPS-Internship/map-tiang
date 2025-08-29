<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

include_once(__DIR__ . '/../../config/database.php');
$db = (new Database())->getConnection();

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405); // Method not allowed
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$id_placemark = intval($input['id_placemark'] ?? 0);

if ($id_placemark <= 0) {
    http_response_code(400); // Bad request
    exit;
}

$query = "DELETE FROM placemarks WHERE id_placemark = :id_placemark";
$stmt = $db->prepare($query);
$stmt->bindParam(':id_placemark', $id_placemark, PDO::PARAM_INT);

if ($stmt->execute() && $stmt->rowCount() > 0) {
    http_response_code(200); // OK
} else {
    http_response_code(404); // Not found
}
?>
