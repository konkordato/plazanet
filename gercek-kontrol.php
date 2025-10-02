<?php
require_once 'config/database.php';

echo "<h2>SON 5 İLAN VE FOTOĞRAFLARı:</h2>";

$ilanlar = $db->query("SELECT * FROM properties ORDER BY id DESC LIMIT 5")->fetchAll();

foreach($ilanlar as $ilan) {
    echo "<b>İlan ID {$ilan['id']}: {$ilan['baslik']}</b><br>";
    
    $fotolar = $db->query("SELECT * FROM property_images WHERE property_id = {$ilan['id']}")->fetchAll();
    
    if($fotolar) {
        foreach($fotolar as $foto) {
            echo "- Foto: {$foto['image_path']} (Ana: {$foto['is_main']})<br>";
            echo "- Dosya var mı: " . (file_exists($foto['image_path']) ? 'EVET' : 'HAYIR') . "<br>";
        }
    } else {
        echo "- HİÇ FOTOĞRAF YOK!<br>";
    }
    echo "<hr>";
}
?>