<?php
include_once __DIR__ . '/../../config/database.php';
include_once __DIR__ . '/../../model/project.php';
include_once __DIR__ . '/../../util/cors.php';

// Set proper headers
header('Content-Type: application/json; charset=UTF-8');


if($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::methodNotAllowed();
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        Response::error("Database connection failed", 500);
    }
    
    $project = new Project($db);
    
    // Get id_user from query parameter (optional)
    $id_user = isset($_GET['id_user']) ? $_GET['id_user'] : null;

    $stmt = $project->read($id_user);
    $projects_arr = array();
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $project_item = array(
            "id_project" => $row['id_project'],
            "name" => $row['nama_project'],
            "nama_project" => $row['nama_project'],
            "owner" => $row['nama_user'] ?? $row['username'] ?? 'Unknown',
            "owner_id" => $row['id_user'],
            "created_at" => $row['created_at'] ?? date('Y-m-d H:i:s'),
            "updated_at" => $row['updated_at'] ?? $row['created_at'] ?? date('Y-m-d H:i:s'),
            "lastModified" => $row['updated_at'] ?? $row['created_at'] ?? date('Y-m-d H:i:s'),
            "created" => $row['created_at'] ?? date('Y-m-d H:i:s')
        );
        
        array_push($projects_arr, $project_item);
    }
    
    Response::success($projects_arr, "Projects retrieved successfully");
    
} catch(Exception $e) {
    error_log("Error in project list: " . $e->getMessage());
    Response::error("Server error occurred: " . $e->getMessage(), 500);
}
?>