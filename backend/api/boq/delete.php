<?php
include_once '../../config/database.php';
include_once '../../model/boq.php';
include_once '../../util/cors.php';

header('Content-Type: application/json; charset=UTF-8');

if (!in_array($_SERVER['REQUEST_METHOD'], ['POST', 'DELETE'])) {
    Response::methodNotAllowed();
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $idProject = $data['id_project'] ?? ($_GET['id_project'] ?? null);
    $idProject = (int)$idProject;
    if (!$idProject) {
        Response::error('Parameter id_project diperlukan', 400);
    }

    $db = (new Database())->getConnection();
    $boqModel = new Boq($db);
    $boqModel->deleteByProject($idProject);

    Response::success(['id_project' => $idProject], 'BoQ deleted');
} catch (Exception $e) {
    Response::error('Error: ' . $e->getMessage(), 500);
}
