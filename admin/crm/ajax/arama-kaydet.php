<?php
session_start();
if(!isset($_SESSION['admin_logged_in']) && !isset($_SESSION['user_logged_in'])) {
    echo json_encode(['success' => false, 'message' => 'Oturum geçersiz']);
    exit();
}

require_once '../../../config/database.php';

$musteri_id = $_POST['musteri_id'] ?? 0;
$tip = $_POST['tip'] ?? '';

if(!$musteri_id || !$tip) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz parametreler']);
    exit();
}

try {
    if($tip == 'satici') {
        // Satıcı müşteri arama sayısını artır
        $sql = "UPDATE crm_satici_musteriler 
                SET arama_sayisi = arama_sayisi + 1, 
                    guncelleme_tarihi = NOW() 
                WHERE id = :id";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([':id' => $musteri_id]);
        
        // Görüşme notu ekle
        $user_id = $_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? 0;
        $user_name = $_SESSION['admin_username'] ?? $_SESSION['username'] ?? '';
        
        $not_sql = "INSERT INTO crm_gorusme_notlari 
                    (musteri_tipi, musteri_id, gorusme_tarihi, gorusme_notu, gorusen_user_id, gorusen_user_adi) 
                    VALUES ('satici', :id, NOW(), 'Müşteri arandı.', :user_id, :user_name)";
        
        $not_stmt = $db->prepare($not_sql);
        $not_stmt->execute([
            ':id' => $musteri_id,
            ':user_id' => $user_id,
            ':user_name' => $user_name
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Arama kaydedildi']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Geçersiz tip']);
    }
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası']);
}
?>