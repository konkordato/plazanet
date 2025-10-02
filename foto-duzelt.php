<?php
require_once 'config/database.php';

try {
    // Tüm ilanların ana fotoğraflarını sıfırla
    $db->exec("UPDATE property_images SET is_main = 0");
    
    // Her ilan için ilk fotoğrafı ana yap
    $sql = "UPDATE property_images pi1
            SET is_main = 1
            WHERE pi1.id = (
                SELECT MIN(pi2.id) 
                FROM (SELECT * FROM property_images) pi2 
                WHERE pi2.property_id = pi1.property_id
            )";
    
    $db->exec($sql);
    
    echo "Tüm fotoğraflar düzeltildi!";
    
} catch(PDOException $e) {
    echo "Hata: " . $e->getMessage();
}
?>