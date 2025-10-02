<?php
session_start();
<<<<<<< HEAD

// GİRİŞ KONTROLÜ - HEM ADMIN HEM USER
if(!isset($_SESSION['admin_logged_in']) && !isset($_SESSION['user_logged_in'])) {
=======
if(!isset($_SESSION['admin_logged_in'])) {
>>>>>>> 1b4657b0e4a9c21769cd70a9511bc87296025d5f
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
<<<<<<< HEAD
    // İlan bilgilerini al
    $stmt = $db->prepare("SELECT * FROM properties WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $property = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if(!$property) {
        $_SESSION['error'] = "İlan bulunamadı!";
        header("Location: ../list.php");
        exit();
    }
    
    // Yetki kontrolü - sadece admin veya ilanın sahibi silebilir
    $current_user_id = $_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? 0;
    $is_admin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
    
    if(!$is_admin && $property['user_id'] != $current_user_id && $property['ekleyen_admin_id'] != $current_user_id) {
        $_SESSION['error'] = "Bu ilanı silme yetkiniz yok!";
        header("Location: ../list.php");
        exit();
    }
    
    // Önce resimleri sil (dosyalar)
=======
    // Önce resimleri sil (dosyalardan)
>>>>>>> 1b4657b0e4a9c21769cd70a9511bc87296025d5f
    $stmt = $db->prepare("SELECT image_path FROM property_images WHERE property_id = :id");
    $stmt->execute([':id' => $id]);
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
<<<<<<< HEAD
    // Upload dizini
    $baseDir = realpath(dirname(__FILE__) . '/../../../');
    
    foreach($images as $img) {
        if(!empty($img['image_path'])) {
            // Tam dosya yolu
            $filePath = $baseDir . '/' . $img['image_path'];
            
            // Dosya varsa sil
            if(file_exists($filePath)) {
                @unlink($filePath);
            }
            
            // Thumb varsa onu da sil (opsiyonel)
            $thumbPath = str_replace('/properties/', '/properties/thumbs/thumb_', $filePath);
            if(file_exists($thumbPath)) {
                @unlink($thumbPath);
            }
        }
    }
    
    // Veritabanından resimleri sil
    $stmt = $db->prepare("DELETE FROM property_images WHERE property_id = :id");
    $stmt->execute([':id' => $id]);
    
    // İlanı sil
    $stmt = $db->prepare("DELETE FROM properties WHERE id = :id");
    $stmt->execute([':id' => $id]);
    
    // İlan no'yu log'a kaydet (opsiyonel)
    error_log("İlan silindi: ID=" . $id . ", İlan No=" . $property['ilan_no'] . ", Silen=" . $current_user_id);
    
    $_SESSION['success'] = "İlan başarıyla silindi! (İlan No: " . $property['ilan_no'] . ")";
    
} catch(PDOException $e) {
    $_SESSION['error'] = "Silme hatası: " . $e->getMessage();
    error_log("İlan silme hatası: " . $e->getMessage());
} catch(Exception $e) {
    $_SESSION['error'] = "Beklenmeyen hata: " . $e->getMessage();
    error_log("Genel hata: " . $e->getMessage());
}

// Liste sayfasına dön
=======
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

>>>>>>> 1b4657b0e4a9c21769cd70a9511bc87296025d5f
header("Location: ../list.php");
exit();
?>