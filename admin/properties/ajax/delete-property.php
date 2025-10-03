<?php
session_start();

// Admin kontrolü
if(!isset($_SESSION['admin_logged_in']) && !isset($_SESSION['user_logged_in'])) {
    header("Location: /plazanet/index.php");
    exit();
}

require_once '../../../config/database.php';

// ID al
$id = $_REQUEST['id'] ?? 0;

if($id <= 0) {
    $_SESSION['error'] = "Geçersiz ilan ID";
    header("Location: /plazanet/admin/properties/list.php");
    exit();
}

try {
    // İlan kontrol
    $stmt = $db->prepare("SELECT ilan_no, baslik FROM properties WHERE id = ?");
    $stmt->execute([$id]);
    $property = $stmt->fetch();
    
    if(!$property) {
        $_SESSION['error'] = "İlan bulunamadı!";
        header("Location: /plazanet/admin/properties/list.php");
        exit();
    }
    
    // Resimleri sil
    $stmt = $db->prepare("DELETE FROM property_images WHERE property_id = ?");
    $stmt->execute([$id]);
    
    // İlanı sil
    $stmt = $db->prepare("DELETE FROM properties WHERE id = ?");
    $stmt->execute([$id]);
    
    // Başarı mesajı
    $_SESSION['success'] = "İlan başarıyla silindi! (" . $property['ilan_no'] . " - " . $property['baslik'] . ")";
    
} catch(Exception $e) {
    $_SESSION['error'] = "Silme hatası: " . $e->getMessage();
}

// Listeye dön
header("Location: /plazanet/admin/properties/list.php");
exit();
?>