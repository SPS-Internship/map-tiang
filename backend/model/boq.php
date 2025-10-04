<?php
class Boq {
    private $conn;
    private $table_name = "boq";

    public $id_boq;
    public $id_placemark; 
    public $id_polygon;   
    public $satuan;
    public $volume;
    public $harga_satuan;
    public $id_odp;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function deleteByProject($id_project) {
        // Fallback: delete rows linked via placemark/polygon of the project
        $sql = "DELETE FROM {$this->table_name} b
                WHERE b.id_placemark IN (SELECT id_placemark FROM placemark WHERE id_project = :pid)
                   OR b.id_polygon   IN (SELECT id_polygon   FROM polygon   WHERE id_project = :pid)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([':pid' => $id_project]);
    }

    public function create($row) {
        $columns = [];
        $params  = [];
        $values  = [];

        if (array_key_exists('id_placemark', $row)) { $columns[] = 'id_placemark'; $params[':id_placemark'] = $row['id_placemark'] !== null ? (int)$row['id_placemark'] : null; $values[] = ':id_placemark'; }
        if (array_key_exists('id_polygon',   $row)) { $columns[] = 'id_polygon';   $params[':id_polygon']   = $row['id_polygon']   !== null ? (int)$row['id_polygon']   : null; $values[] = ':id_polygon'; }

        $columns[] = 'satuan';        $params[':satuan'] = (string)($row['satuan'] ?? '');        $values[] = ':satuan';
        $columns[] = 'volume';        $params[':volume'] = (float)($row['volume'] ?? 0);          $values[] = ':volume';
        $columns[] = 'harga_satuan';  $params[':harga']  = (float)($row['harga_satuan'] ?? 0);     $values[] = ':harga';
        if (array_key_exists('id_odp', $row)) { $columns[] = 'id_odp'; $params[':id_odp'] = $row['id_odp'] !== null ? (int)$row['id_odp'] : null; $values[] = ':id_odp'; }

        $sql = "INSERT INTO {$this->table_name} (" . implode(',', $columns) . ") VALUES (" . implode(',', $values) . ") RETURNING id_boq";
        $stmt = $this->conn->prepare($sql);
        if ($stmt->execute($params)) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? $row['id_boq'] : null;
        }
        return null;
    }

    public function readByProject($id_project) {
    $sql = "SELECT b.*, pm.nama_placemark, pg.nama_polygon
        FROM {$this->table_name} b
        LEFT JOIN placemark pm ON pm.id_placemark = b.id_placemark
        LEFT JOIN polygon   pg ON pg.id_polygon   = b.id_polygon
        WHERE COALESCE(pm.id_project, pg.id_project) = :id_project
        ORDER BY b.created_at DESC, b.id_boq DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id_project' => $id_project]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
