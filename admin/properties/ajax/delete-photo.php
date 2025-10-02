<<<<<<< HEAD
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
    // Önce fotoğraf bilgisini al
    $stmt = $db->prepare("SELECT * FROM property_images WHERE id = :id AND property_id = :pid");
    $stmt->execute([':id' => $photo_id, ':pid' => $property_id]);
    $photo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if($photo) {
        // Fiziksel dosyayı sil
        $filePath = '../../../' . $photo['image_path'];
        if(file_exists($filePath)) {
            unlink($filePath);
        }
        
        // Veritabanından sil
        $stmt = $db->prepare("DELETE FROM property_images WHERE id = :id");
        $stmt->execute([':id' => $photo_id]);
        
        // Eğer silinen ana fotoğrafsa, başka bir fotoğrafı ana yap
        if($photo['is_main'] == 1) {
            $stmt = $db->prepare("UPDATE property_images SET is_main = 1 
                                 WHERE property_id = :pid 
                                 ORDER BY id ASC LIMIT 1");
            $stmt->execute([':pid' => $property_id]);
        }
        
        $_SESSION['success'] = "Fotoğraf başarıyla silindi!";
    } else {
        $_SESSION['error'] = "Fotoğraf bulunamadı!";
    }
    
} catch(PDOException $e) {
    $_SESSION['error'] = "Silme hatası: " . $e->getMessage();
}

header("Location: ../edit.php?id=" . $property_id);
exit();
=======
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
    // Önce fotoğraf bilgisini al
    $stmt = $db->prepare("SELECT * FROM property_images WHERE id = :id AND property_id = :pid");
    $stmt->execute([':id' => $photo_id, ':pid' => $property_id]);
    $photo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if($photo) {
        // Fiziksel dosyayı sil
        $filePath = '../../../' . $photo['image_path'];
        if(file_exists($filePath)) {
            unlink($filePath);
        }
        
        // Veritabanından sil
        $stmt = $db->prepare("DELETE FROM property_images WHERE id = :id");
        $stmt->execute([':id' => $photo_id]);
        
        // Eğer silinen ana fotoğrafsa, başka bir fotoğrafı ana yap
        if($photo['is_main'] == 1) {
            $stmt = $db->prepare("UPDATE property_images SET is_main = 1 
                                 WHERE property_id = :pid 
                                 ORDER BY id ASC LIMIT 1");
            $stmt->execute([':pid' => $property_id]);
        }
        
        $_SESSION['success'] = "Fotoğraf başarıyla silindi!";
    } else {
        $_SESSION['error'] = "Fotoğraf bulunamadı!";
    }
    
} catch(PDOException $e) {
    $_SESSION['error'] = "Silme hatası: " . $e->getMessage();
}

header("Location: ../edit.php?id=" . $property_id);
exit();
>>>>>>> 1b4657b0e4a9c21769cd70a9511bc87296025d5f
?>