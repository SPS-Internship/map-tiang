<?php
class Polygon {
    // Database connection and table name
    private $conn;
    private $table_name = "polygon";
    
    // Object properties
    public $id_polygon;
    public $id_project;
    public $nama_polygon;
    public $deskripsi;
    public $coordinate;
    public $panjang_meter;
    public $created_at;
    
    // Constructor with DB
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Read polygons by id_project (PostgreSQL style)
    public function read() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id_project = $1 ORDER BY created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$this->id_project]);
        
        return $stmt;
    }
    
    // Create polygon (PostgreSQL style)
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                (id_project, nama_polygon, deskripsi, coordinate, panjang_meter, created_at) 
                VALUES (:id_project, :nama_polygon, :deskripsi, :coordinate, :panjang_meter, NOW()) 
                RETURNING id_polygon";

        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->id_project   = htmlspecialchars(strip_tags($this->id_project));
        $this->nama_polygon = htmlspecialchars(strip_tags($this->nama_polygon));
        $this->deskripsi    = htmlspecialchars(strip_tags($this->deskripsi));
        
        // Convert coordinate to PostgreSQL array format
        if (is_string($this->coordinate)) {
            // If it's a JSON string, decode it first
            $coordArray = json_decode($this->coordinate, true);
        } else if (is_array($this->coordinate)) {
            $coordArray = $this->coordinate;
        } else {
            $coordArray = [];
        }
        
        // Convert to PostgreSQL array format: {{lat1,lng1},{lat2,lng2},...}
        if (!empty($coordArray)) {
            $pgArray = '{';
            $coordPairs = [];
            foreach ($coordArray as $coord) {
                if (isset($coord['lat']) && isset($coord['lng'])) {
                    $coordPairs[] = '{' . $coord['lat'] . ',' . $coord['lng'] . '}';
                }
            }
            $pgArray .= implode(',', $coordPairs) . '}';
            $this->coordinate = $pgArray;
        } else {
            $this->coordinate = '{}'; // Empty array
        }

        $stmt->bindParam(':id_project', $this->id_project);
        $stmt->bindParam(':nama_polygon', $this->nama_polygon);
        $stmt->bindParam(':deskripsi', $this->deskripsi);
        $stmt->bindParam(':coordinate', $this->coordinate);
        $stmt->bindParam(':panjang_meter', $this->panjang_meter);

        if ($stmt->execute()) {
            // PostgreSQL bisa RETURNING langsung
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id_polygon = $row['id_polygon'];
            return true;
        }

        return false;
    }
    
    // Update polygon (PostgreSQL style)
    public function update() {
        $query = "UPDATE " . $this->table_name . "
                  SET nama_polygon = $1, coordinate = $2, deskripsi = $3, panjang_meter = $4, updated_at = NOW()
                  WHERE id_polygon = $5 AND id_project = $6";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitize
        $this->nama_polygon = htmlspecialchars(strip_tags($this->nama_polygon));
        $this->deskripsi = htmlspecialchars(strip_tags($this->deskripsi));
        
        // Convert coordinate to PostgreSQL array format
        if (is_string($this->coordinate)) {
            // If it's a JSON string, decode it first
            $coordArray = json_decode($this->coordinate, true);
        } else if (is_array($this->coordinate)) {
            $coordArray = $this->coordinate;
        } else {
            $coordArray = [];
        }
        
        // Convert to PostgreSQL array format: {{lat1,lng1},{lat2,lng2},...}
        if (!empty($coordArray)) {
            $pgArray = '{';
            $coordPairs = [];
            foreach ($coordArray as $coord) {
                if (isset($coord['lat']) && isset($coord['lng'])) {
                    $coordPairs[] = '{' . $coord['lat'] . ',' . $coord['lng'] . '}';
                }
            }
            $pgArray .= implode(',', $coordPairs) . '}';
            $this->coordinate = $pgArray;
        } else {
            $this->coordinate = '{}'; // Empty array
        }
        
        return $stmt->execute([
            $this->nama_polygon, 
            $this->coordinate, 
            $this->deskripsi, 
            $this->panjang_meter,
            $this->id_polygon, 
            $this->id_project
        ]);
    }
    
    // Verify ownership (PostgreSQL style)
    public function verifyOwnership() {
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name . " 
                  WHERE id_polygon = $1 AND id_project = $2";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$this->id_polygon, $this->id_project]);
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return ($row['count'] > 0);
    }
    
    // Delete polygon (PostgreSQL style)
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id_polygon = $1";
        
        $stmt = $this->conn->prepare($query);
        $this->id_polygon = htmlspecialchars(strip_tags($this->id_polygon));
        
        return $stmt->execute([$this->id_polygon]);
    }
}
?>