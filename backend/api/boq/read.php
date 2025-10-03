<?php
include_once '../../config/database.php';
include_once '../../model/boq.php';
include_once '../../util/cors.php';

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::methodNotAllowed();
}

try {
    $idProject = isset($_GET['id_project']) ? (int)$_GET['id_project'] : 0;
    if (!$idProject) {
        Response::error('Parameter id_project diperlukan', 400);
    }

    $db = (new Database())->getConnection();
    $boqModel = new Boq($db);

    $rows = $boqModel->readByProject($idProject);
    Response::success($rows);
} catch (Exception $e) {
    Response::error('Error: ' . $e->getMessage(), 500);
}
