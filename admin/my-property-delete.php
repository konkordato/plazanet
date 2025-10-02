<<<<<<< HEAD
<?php
session_start();

// Kullanıcı girişi kontrolü
if(!isset($_SESSION['user_logged_in']) || $_SESSION['user_role'] !== 'user') {
    header("Location: index.php");
    exit();
}

require_once '../config/database.php';

$user_id = $_SESSION['user_id'];
$property_id = $_GET['id'] ?? null;

if(!$property_id) {
    $_SESSION['error'] = "Geçersiz ilan ID";
    header("Location: my-properties.php");
    exit();
}

try {
    // İlanın kullanıcıya ait olduğunu kontrol et
    $checkStmt = $db->prepare("SELECT id, user_id FROM properties WHERE id = :id AND user_id = :user_id");
    $checkStmt->execute([
        ':id' => $property_id,
        ':user_id' => $user_id
    ]);
    
    if($checkStmt->rowCount() == 0) {
        $_SESSION['error'] = "Bu ilan size ait değil veya bulunamadı!";
        header("Location: my-properties.php");
        exit();
    }
    
    // Önce resimleri sil (dosyalardan)
    $stmt = $db->prepare("SELECT image_path FROM property_images WHERE property_id = :id");
    $stmt->execute([':id' => $property_id]);
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach($images as $img) {
        $filePath = '../' . $img['image_path'];
        $thumbPath = str_replace('properties/', 'properties/thumbs/thumb_', $filePath);
        
        if(file_exists($filePath)) unlink($filePath);
        if(file_exists($thumbPath)) unlink($thumbPath);
    }
    
    // İlanı sil (resimler otomatik silinir - CASCADE)
    $stmt = $db->prepare("DELETE FROM properties WHERE id = :id AND user_id = :user_id");
    $stmt->execute([
        ':id' => $property_id,
        ':user_id' => $user_id
    ]);
    
    $_SESSION['success'] = "İlan başarıyla silindi!";
    
} catch(PDOException $e) {
    $_SESSION['error'] = "Silme hatası: " . $e->getMessage();
}

header("Location: my-properties.php");
exit();
=======
<?php
session_start();

// Kullanıcı girişi kontrolü
if(!isset($_SESSION['user_logged_in']) || $_SESSION['user_role'] !== 'user') {
    header("Location: index.php");
    exit();
}

require_once '../config/database.php';

$user_id = $_SESSION['user_id'];
$property_id = $_GET['id'] ?? null;

if(!$property_id) {
    $_SESSION['error'] = "Geçersiz ilan ID";
    header("Location: my-properties.php");
    exit();
}

try {
    // İlanın kullanıcıya ait olduğunu kontrol et
    $checkStmt = $db->prepare("SELECT id, user_id FROM properties WHERE id = :id AND user_id = :user_id");
    $checkStmt->execute([
        ':id' => $property_id,
        ':user_id' => $user_id
    ]);
    
    if($checkStmt->rowCount() == 0) {
        $_SESSION['error'] = "Bu ilan size ait değil veya bulunamadı!";
        header("Location: my-properties.php");
        exit();
    }
    
    // Önce resimleri sil (dosyalardan)
    $stmt = $db->prepare("SELECT image_path FROM property_images WHERE property_id = :id");
    $stmt->execute([':id' => $property_id]);
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach($images as $img) {
        $filePath = '../' . $img['image_path'];
        $thumbPath = str_replace('properties/', 'properties/thumbs/thumb_', $filePath);
        
        if(file_exists($filePath)) unlink($filePath);
        if(file_exists($thumbPath)) unlink($thumbPath);
    }
    
    // İlanı sil (resimler otomatik silinir - CASCADE)
    $stmt = $db->prepare("DELETE FROM properties WHERE id = :id AND user_id = :user_id");
    $stmt->execute([
        ':id' => $property_id,
        ':user_id' => $user_id
    ]);
    
    $_SESSION['success'] = "İlan başarıyla silindi!";
    
} catch(PDOException $e) {
    $_SESSION['error'] = "Silme hatası: " . $e->getMessage();
}

header("Location: my-properties.php");
exit();
>>>>>>> 1b4657b0e4a9c21769cd70a9511bc87296025d5f
?>