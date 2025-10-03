<?php
include_once '../../config/database.php';
include_once '../../model/boq.php';
include_once '../../util/cors.php';

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::methodNotAllowed();
}

try {
    $pdo = (new Database())->getConnection();
    $boqModel = new Boq($pdo);

    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data || empty($data['id_project']) || empty($data['rows']) || !is_array($data['rows'])) {
        Response::error('Invalid payload: id_project and rows[] are required', 400);
    }

    $idProject = (int)$data['id_project'];

    // Clear previous rows for this project to avoid duplicates
    $boqModel->deleteByProject($idProject);
    // Default prices as per requirement
    $PRICE_CABLE = 12000;     // per meter
    $PRICE_POLE  = 2000000;   // per unit


    // Helper: load polygons and placemarks for project
    $stmt = $pdo->prepare("SELECT id_polygon, panjang_meter FROM polygon WHERE id_project = :p");
    $stmt->execute([':p' => $idProject]);
    $polygons = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT id_placemark FROM placemark WHERE id_project = :p");
    $stmt->execute([':p' => $idProject]);
    $placemarks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ODP map for placemarks (id_placemark -> id_odp)
    $placemarkIds = array_map(fn($r) => (int)$r['id_placemark'], $placemarks);
    $odpMap = [];
    if (count($placemarkIds) > 0) {
        $in = implode(',', array_fill(0, count($placemarkIds), '?'));
        $q = $pdo->prepare("SELECT id_placemark, MIN(id_odp) AS id_odp FROM odp WHERE id_placemark IN ($in) GROUP BY id_placemark");
        $q->execute($placemarkIds);
        foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $odpMap[(int)$r['id_placemark']] = (int)$r['id_odp'];
        }
    }

    // Extract aggregated values from payload by label
    $byLabel = [];
    foreach ($data['rows'] as $r) {
        $label = isset($r['uraian']) ? strtolower(trim($r['uraian'])) : '';
        if ($label !== '') $byLabel[$label] = $r;
    }

    $inserted = [];

    // 1) Kabel FO 24 core -> per polygon
    foreach ($polygons as $pg) {
        $row = $byLabel['kabel fo 24 core'] ?? null;
        if (!$row) continue;
        $vol = (float)($pg['panjang_meter'] ?? 0);
        if ($vol <= 0) continue;
        $id = $boqModel->create([
            'id_polygon'   => (int)$pg['id_polygon'],
            'satuan'       => 'm',
            'volume'       => $vol,
            'harga_satuan' => $PRICE_CABLE,
            'status_odp'   => '-',
        ]);
        if ($id) $inserted[] = $id;
    }

    // 2) Kabel Drop FO -> distribute equally to each placemark
    $rowDrop = $byLabel['kabel drop fo'] ?? null;
    if ($rowDrop && count($placemarkIds) > 0) {
        $totalDrop = (float)($rowDrop['volume'] ?? 0);
        $per = $totalDrop > 0 ? $totalDrop / count($placemarkIds) : 0;
        foreach ($placemarkIds as $pid) {
            if ($per <= 0) break;
            $id = $boqModel->create([
                'id_placemark' => $pid,
                'satuan'       => 'm',
                'volume'       => $per,
                'harga_satuan' => $PRICE_CABLE,
                'status_odp'   => '-',
            ]);
            if ($id) $inserted[] = $id;
        }
    }

    // 3) Tiang Besi 9 m -> 1 unit per placemark, status ODP per placemark
    $rowTiang = $byLabel['tiang besi 9 m'] ?? null;
    if ($rowTiang) {
        foreach ($placemarkIds as $pid) {
            $id = $boqModel->create([
                'id_placemark' => $pid,
                'satuan'       => 'unit',
                'volume'       => 1,
                'harga_satuan' => $PRICE_POLE,
                'status_odp'   => isset($odpMap[$pid]) ? 'odp' : '-',
                'id_odp'       => $odpMap[$pid] ?? null,
            ]);
            if ($id) $inserted[] = $id;
        }
    }

    // 4) Pondasi Beton -> 1 unit per placemark, status ODP per placemark
    $rowPondasi = $byLabel['pondasi beton'] ?? null;
    if ($rowPondasi) {
        foreach ($placemarkIds as $pid) {
            $id = $boqModel->create([
                'id_placemark' => $pid,
                'satuan'       => 'unit',
                'volume'       => 1,
                'harga_satuan' => $PRICE_POLE,
                'status_odp'   => isset($odpMap[$pid]) ? 'odp' : '-',
                'id_odp'       => $odpMap[$pid] ?? null,
            ]);
            if ($id) $inserted[] = $id;
        }
    }

    Response::success(['inserted_ids' => $inserted, 'count' => count($inserted)], 'BoQ saved');
} catch (Exception $e) {
    Response::error('Error: ' . $e->getMessage(), 500);
}
