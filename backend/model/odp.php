<?php
// filepath: backend/model/odp.php
// Model untuk tabel ODP
// Kolom:
// id_odp SERIAL PRIMARY KEY
// id_placemark BIGINT NOT NULL (relasi ke placemark)
// nama_odp VARCHAR(100)
// kd_layanan VARCHAR(50)
// status_wo VARCHAR(50)
// status_tiang VARCHAR(50)
// lain_lain TEXT

class Odp {
    private $conn;
    private $table_name = "odp";

    public $id_odp;
    public $id_placemark;
    public $nama_odp;
    public $kd_layanan;
    public $status_wo;
    public $status_tiang;
    public $lain_lain;

    public function __construct($db) {
        $this->conn = $db;
    }

    // CREATE
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " (id_placemark, nama_odp, kd_layanan, status_wo, status_tiang, lain_lain) VALUES (:id_placemark, :nama_odp, :kd_layanan, :status_wo, :status_tiang, :lain_lain) RETURNING id_odp";
        $stmt = $this->conn->prepare($query);

        $this->sanitize();

        $stmt->bindParam(':id_placemark', $this->id_placemark, PDO::PARAM_INT);
        $stmt->bindParam(':nama_odp', $this->nama_odp);
        $stmt->bindParam(':kd_layanan', $this->kd_layanan);
        $stmt->bindParam(':status_wo', $this->status_wo);
        $stmt->bindParam(':status_tiang', $this->status_tiang);
        $stmt->bindParam(':lain_lain', $this->lain_lain);

        if ($stmt->execute()) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id_odp = $row['id_odp'];
            return true;
        }
        return false;
    }

    // READ ALL (opsional filter by id_placemark)
    public function read($id_placemark_filter = null) {
        $query = "SELECT id_odp, id_placemark, nama_odp, kd_layanan, status_wo, status_tiang, lain_lain FROM " . $this->table_name;
        if ($id_placemark_filter) {
            $query .= " WHERE id_placemark = :id_placemark";
        }
        $query .= " ORDER BY id_odp DESC";

        $stmt = $this->conn->prepare($query);
        if ($id_placemark_filter) {
            $stmt->bindParam(':id_placemark', $id_placemark_filter, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt;
    }

    // READ ONE
    public function readOne() {
        $query = "SELECT id_odp, id_placemark, nama_odp, kd_layanan, status_wo, status_tiang, lain_lain FROM " . $this->table_name . " WHERE id_odp = :id_odp LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_odp', $this->id_odp, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $this->id_placemark = $row['id_placemark'];
            $this->nama_odp = $row['nama_odp'];
            $this->kd_layanan = $row['kd_layanan'];
            $this->status_wo = $row['status_wo'];
            $this->status_tiang = $row['status_tiang'];
            $this->lain_lain = $row['lain_lain'];
            return true;
        }
        return false;
    }

    // UPDATE
    public function update() {
        $query = "UPDATE " . $this->table_name . " SET id_placemark = :id_placemark, nama_odp = :nama_odp, kd_layanan = :kd_layanan, status_wo = :status_wo, status_tiang = :status_tiang, lain_lain = :lain_lain WHERE id_odp = :id_odp";
        $stmt = $this->conn->prepare($query);

        $this->sanitize();

        $stmt->bindParam(':id_placemark', $this->id_placemark, PDO::PARAM_INT);
        $stmt->bindParam(':nama_odp', $this->nama_odp);
        $stmt->bindParam(':kd_layanan', $this->kd_layanan);
        $stmt->bindParam(':status_wo', $this->status_wo);
        $stmt->bindParam(':status_tiang', $this->status_tiang);
        $stmt->bindParam(':lain_lain', $this->lain_lain);
        $stmt->bindParam(':id_odp', $this->id_odp, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // DELETE
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id_odp = :id_odp";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_odp', $this->id_odp, PDO::PARAM_INT);
        return $stmt->execute();
    }

    private function sanitize() {
        $this->id_placemark = (int)$this->id_placemark;
        $this->nama_odp = htmlspecialchars(strip_tags($this->nama_odp ?? ''));
        $this->kd_layanan = htmlspecialchars(strip_tags($this->kd_layanan ?? ''));
        $this->status_wo = htmlspecialchars(strip_tags($this->status_wo ?? ''));
        $this->status_tiang = htmlspecialchars(strip_tags($this->status_tiang ?? ''));
        // lain_lain bisa mengandung teks panjang, tetap strip tags dasar
        $this->lain_lain = htmlspecialchars(strip_tags($this->lain_lain ?? ''));
    }
}
?>