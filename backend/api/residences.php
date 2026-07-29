<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once dirname(__DIR__) . '/db.php';

try {
    $rows = db()->query(
        'SELECT id, name, slug, region, price_from, email, phone, hero_image,
                total_rooms, available_rooms, detail_page, status
         FROM residences
         WHERE status = "active"
         ORDER BY name'
    )->fetchAll();

    echo json_encode(['success' => true, 'residences' => $rows]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
