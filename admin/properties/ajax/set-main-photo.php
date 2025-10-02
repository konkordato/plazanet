<?php
session_start();
if(!isset($_SESSION['admin_logged_in'])) {
    header("Location: ../../index.php");
    exit();
}

require_once '../../../config/database.php';

$photo_id = $_GET['id'] ?? 0;
$property_id = $_GET['property_id'] ?? 0;

if(!$photo_id || !$property_id) {
    $_SESSION['error'] = "Geçersiz parametreler";
    header("Location: ../edit.php?id=" . $property_id);
    exit();
}

try {
    // Önce tüm fotoğrafların ana fotoğraf işaretini kaldır
    $stmt = $db->prepare("UPDATE property_images SET is_main = 0 WHERE property_id = :pid");
    $stmt->execute([':pid' => $property_id]);
    
    // Seçilen fotoğrafı ana fotoğraf yap
    $stmt = $db->prepare("UPDATE property_images SET is_main = 1 WHERE id = :id AND property_id = :pid");
    $stmt->execute([':id' => $photo_id, ':pid' => $property_id]);
    
    $_SESSION['success'] = "Ana fotoğraf başarıyla değiştirildi!";
    
} catch(PDOException $e) {
    $_SESSION['error'] = "İşlem hatası: " . $e->getMessage();
}

header("Location: ../edit.php?id=" . $property_id);
exit();
?>