<?php
require_once 'config/database.php';

$stmt = $db->query("
    SELECT p.id, p.ilan_no, p.baslik, pi.image_path, pi.is_main
    FROM properties p
    LEFT JOIN property_images pi ON p.id = pi.property_id AND pi.is_main = 1
    ORDER BY p.id DESC
    LIMIT 5
");

while($row = $stmt->fetch()) {
    echo "İlan ID: {$row['id']} - {$row['baslik']}<br>";
    echo "Ana Foto: {$row['image_path']}<br>";
    echo "Dosya var mı: " . (file_exists($row['image_path']) ? 'EVET' : 'HAYIR') . "<br>";
    echo "<hr>";
}
?>