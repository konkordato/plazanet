<<<<<<< HEAD
<?php
session_start();

// Sadece admin erişebilir
if(!isset($_SESSION['user_logged_in']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

require_once '../../config/database.php';

// Silme işlemi
if(isset($_POST['delete_closing']) && isset($_POST['closing_id'])) {
    try {
        $db->beginTransaction();
        
        $closing_id = $_POST['closing_id'];
        
        // Önce ilgili property'nin durumunu güncelle (eğer bağlıysa)
        $check_property = $db->prepare("
            SELECT property_id FROM portfolio_closings 
            WHERE id = :id AND property_status_changed = 1
        ");
        $check_property->execute([':id' => $closing_id]);
        $property_data = $check_property->fetch(PDO::FETCH_ASSOC);
        
        if($property_data && $property_data['property_id']) {
            // İlanı tekrar aktif yap
            $update_property = $db->prepare("
                UPDATE properties 
                SET durum = 'aktif',
                    closed_by = NULL,
                    closed_at = NULL,
                    closing_id = NULL
                WHERE closing_id = :closing_id
            ");
            $update_property->execute([':closing_id' => $closing_id]);
        }
        
        // Kapatma kaydını sil
        $delete_closing = $db->prepare("DELETE FROM portfolio_closings WHERE id = :id");
        $delete_closing->execute([':id' => $closing_id]);
        
        $db->commit();
        
        $_SESSION['success'] = "Kapatma kaydı başarıyla silindi!";
        
    } catch(Exception $e) {
        $db->rollBack();
        $_SESSION['error'] = "Silme işlemi başarısız: " . $e->getMessage();
    }
    
    header("Location: closing-list.php");
    exit();
}

// Eğer doğrudan erişim varsa listeye yönlendir
header("Location: closing-list.php");
=======
<?php
session_start();

// Sadece admin erişebilir
if(!isset($_SESSION['user_logged_in']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

require_once '../../config/database.php';

// Silme işlemi
if(isset($_POST['delete_closing']) && isset($_POST['closing_id'])) {
    try {
        $db->beginTransaction();
        
        $closing_id = $_POST['closing_id'];
        
        // Önce ilgili property'nin durumunu güncelle (eğer bağlıysa)
        $check_property = $db->prepare("
            SELECT property_id FROM portfolio_closings 
            WHERE id = :id AND property_status_changed = 1
        ");
        $check_property->execute([':id' => $closing_id]);
        $property_data = $check_property->fetch(PDO::FETCH_ASSOC);
        
        if($property_data && $property_data['property_id']) {
            // İlanı tekrar aktif yap
            $update_property = $db->prepare("
                UPDATE properties 
                SET durum = 'aktif',
                    closed_by = NULL,
                    closed_at = NULL,
                    closing_id = NULL
                WHERE closing_id = :closing_id
            ");
            $update_property->execute([':closing_id' => $closing_id]);
        }
        
        // Kapatma kaydını sil
        $delete_closing = $db->prepare("DELETE FROM portfolio_closings WHERE id = :id");
        $delete_closing->execute([':id' => $closing_id]);
        
        $db->commit();
        
        $_SESSION['success'] = "Kapatma kaydı başarıyla silindi!";
        
    } catch(Exception $e) {
        $db->rollBack();
        $_SESSION['error'] = "Silme işlemi başarısız: " . $e->getMessage();
    }
    
    header("Location: closing-list.php");
    exit();
}

// Eğer doğrudan erişim varsa listeye yönlendir
header("Location: closing-list.php");
>>>>>>> 1b4657b0e4a9c21769cd70a9511bc87296025d5f
exit();