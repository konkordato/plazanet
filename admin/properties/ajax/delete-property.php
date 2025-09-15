<?php
session_start();
if(!isset($_SESSION['admin_logged_in'])) {
    header("Location: ../../index.php");
    exit();
}

require_once '../../../config/database.php';

$id = $_GET['id'] ?? null;

if(!$id) {
    $_SESSION['error'] = "Geçersiz ilan ID";
    header("Location: ../list.php");
    exit();
}

try {
    // Önce resimleri sil (dosyalardan)
    $stmt = $db->prepare("SELECT image_path FROM property_images WHERE property_id = :id");
    $stmt->execute([':id' => $id]);
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach($images as $img) {
        $filePath = '../../../' . $img['image_path'];
        $thumbPath = str_replace('properties/', 'properties/thumbs/thumb_', $filePath);
        
        if(file_exists($filePath)) unlink($filePath);
        if(file_exists($thumbPath)) unlink($thumbPath);
    }
    
    // İlanı sil (resimler otomatik silinir - CASCADE)
    $stmt = $db->prepare("DELETE FROM properties WHERE id = :id");
    $stmt->execute([':id' => $id]);
    
    $_SESSION['success'] = "İlan başarıyla silindi!";
    
} catch(PDOException $e) {
    $_SESSION['error'] = "Silme hatası: " . $e->getMessage();
}

header("Location: ../list.php");
exit();
?>