<?php
require_once 'config/database.php';

echo "<h3>Properties Tablosu (Son 5 ilan):</h3>";
$stmt = $db->query("SELECT id, ilan_no, baslik, created_at FROM properties ORDER BY created_at DESC LIMIT 5");
while($row = $stmt->fetch()) {
    echo "ID: {$row['id']} - İlan No: {$row['ilan_no']} - {$row['baslik']}<br>";
}

echo "<hr><h3>Property Images Tablosu (Son 10 foto):</h3>";
$stmt = $db->query("SELECT * FROM property_images ORDER BY id DESC LIMIT 10");
while($row = $stmt->fetch()) {
    echo "Foto ID: {$row['id']} - Property ID: {$row['property_id']} - Ana: {$row['is_main']} - {$row['image_path']}<br>";
}
?>