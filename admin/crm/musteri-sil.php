<<<<<<< HEAD
<?php
session_start();
// Sadece admin silme yetkisine sahip
if(!isset($_SESSION['admin_logged_in']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

require_once '../../config/database.php';

$musteri_tip = $_GET['tip'] ?? '';
$musteri_id = $_GET['id'] ?? 0;

if(!$musteri_tip || !$musteri_id) {
    $_SESSION['error_message'] = "Geçersiz parametreler!";
    header("Location: index.php");
    exit();
}

try {
    // Transaction başlat
    $db->beginTransaction();
    
    // Önce görüşme notlarını sil
    $not_sql = "DELETE FROM crm_gorusme_notlari 
                WHERE musteri_tipi = :tip AND musteri_id = :id";
    $not_stmt = $db->prepare($not_sql);
    $not_stmt->execute([':tip' => $musteri_tip, ':id' => $musteri_id]);
    
    // SMS kayıtlarını sil
    $sms_sql = "DELETE FROM crm_sms_kayitlari 
                WHERE musteri_tipi = :tip AND musteri_id = :id";
    $sms_stmt = $db->prepare($sms_sql);
    $sms_stmt->execute([':tip' => $musteri_tip, ':id' => $musteri_id]);
    
    // Müşteriyi sil
    if($musteri_tip == 'alici') {
        $musteri_sql = "DELETE FROM crm_alici_musteriler WHERE id = :id";
        $redirect = "alici-liste.php";
    } else {
        $musteri_sql = "DELETE FROM crm_satici_musteriler WHERE id = :id";
        $redirect = "satici-liste.php";
    }
    
    $musteri_stmt = $db->prepare($musteri_sql);
    $musteri_stmt->execute([':id' => $musteri_id]);
    
    // Transaction'ı onayla
    $db->commit();
    
    $_SESSION['success_message'] = "Müşteri ve tüm ilişkili kayıtlar başarıyla silindi!";
    header("Location: $redirect");
    exit();
    
} catch(PDOException $e) {
    // Hata durumunda geri al
    $db->rollBack();
    
    $_SESSION['error_message'] = "Silme işlemi sırasında hata oluştu: " . $e->getMessage();
    header("Location: index.php");
    exit();
}
=======
<?php
session_start();
// Sadece admin silme yetkisine sahip
if(!isset($_SESSION['admin_logged_in']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

require_once '../../config/database.php';

$musteri_tip = $_GET['tip'] ?? '';
$musteri_id = $_GET['id'] ?? 0;

if(!$musteri_tip || !$musteri_id) {
    $_SESSION['error_message'] = "Geçersiz parametreler!";
    header("Location: index.php");
    exit();
}

try {
    // Transaction başlat
    $db->beginTransaction();
    
    // Önce görüşme notlarını sil
    $not_sql = "DELETE FROM crm_gorusme_notlari 
                WHERE musteri_tipi = :tip AND musteri_id = :id";
    $not_stmt = $db->prepare($not_sql);
    $not_stmt->execute([':tip' => $musteri_tip, ':id' => $musteri_id]);
    
    // SMS kayıtlarını sil
    $sms_sql = "DELETE FROM crm_sms_kayitlari 
                WHERE musteri_tipi = :tip AND musteri_id = :id";
    $sms_stmt = $db->prepare($sms_sql);
    $sms_stmt->execute([':tip' => $musteri_tip, ':id' => $musteri_id]);
    
    // Müşteriyi sil
    if($musteri_tip == 'alici') {
        $musteri_sql = "DELETE FROM crm_alici_musteriler WHERE id = :id";
        $redirect = "alici-liste.php";
    } else {
        $musteri_sql = "DELETE FROM crm_satici_musteriler WHERE id = :id";
        $redirect = "satici-liste.php";
    }
    
    $musteri_stmt = $db->prepare($musteri_sql);
    $musteri_stmt->execute([':id' => $musteri_id]);
    
    // Transaction'ı onayla
    $db->commit();
    
    $_SESSION['success_message'] = "Müşteri ve tüm ilişkili kayıtlar başarıyla silindi!";
    header("Location: $redirect");
    exit();
    
} catch(PDOException $e) {
    // Hata durumunda geri al
    $db->rollBack();
    
    $_SESSION['error_message'] = "Silme işlemi sırasında hata oluştu: " . $e->getMessage();
    header("Location: index.php");
    exit();
}
>>>>>>> 1b4657b0e4a9c21769cd70a9511bc87296025d5f
?>