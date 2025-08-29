<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

include_once(__DIR__ . '/../../config/database.php');
$db = (new Database())->getConnection();

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$id_placemark = intval($input['id_placemark'] ?? 0);

if ($id_placemark <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid placemark ID']);
    exit;
}

$query = "DELETE FROM placemark WHERE id_placemark = :id_placemark";
$stmt = $db->prepare($query);
$stmt->bindParam(':id_placemark', $id_placemark, PDO::PARAM_INT);

if ($stmt->execute() && $stmt->rowCount() > 0) {
    echo json_encode(['success' => true, 'message' => 'Placemark deleted successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Placemark not found or already deleted']);
}
?>
