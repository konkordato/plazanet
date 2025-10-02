<?php
session_start();

// Admin kontrolü - sadece admin silebilir
if(!isset($_SESSION['admin_logged_in']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../../index.php");
    exit();
}

require_once '../../config/database.php';

// Kullanıcı ID'sini al
$user_id = $_GET['id'] ?? 0;

// Geçerli ID kontrolü
if(!$user_id || !is_numeric($user_id)) {
    $_SESSION['error'] = "Geçersiz kullanıcı ID!";
    header("Location: list.php");
    exit();
}

// Admin kullanıcısını silmeye çalışıyor mu?
$stmt = $db->prepare("SELECT username, role FROM users WHERE id = :id");
$stmt->execute([':id' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$user) {
    $_SESSION['error'] = "Kullanıcı bulunamadı!";
    header("Location: list.php");
    exit();
}

// Admin kullanıcısı silinemez
if($user['username'] == 'admin' || $user['role'] == 'admin') {
    $_SESSION['error'] = "Admin kullanıcısı silinemez!";
    header("Location: list.php");
    exit();
}

// Kendini silmeye çalışıyor mu?
if($user_id == $_SESSION['admin_id']) {
    $_SESSION['error'] = "Kendinizi silemezsiniz!";
    header("Location: list.php");
    exit();
}

try {
    // Transaction başlat
    $db->beginTransaction();
    
    // 1. Kullanıcının profil resmini sil
    $stmt = $db->prepare("SELECT profile_image FROM users WHERE id = :id");
    $stmt->execute([':id' => $user_id]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if($userData['profile_image'] && file_exists('../../' . $userData['profile_image'])) {
        unlink('../../' . $userData['profile_image']);
    }
    
    // 2. Kullanıcının ilanlarını pasif yap (silmek yerine)
    $stmt = $db->prepare("UPDATE properties SET durum = 'pasif' WHERE user_id = :user_id");
    $stmt->execute([':user_id' => $user_id]);
    
    // 3. CRM'deki müşteri kayıtlarını güncelle (silinmiş kullanıcı olarak işaretle)
    $stmt = $db->prepare("UPDATE crm_alici_musteriler SET ekleyen_user_id = NULL WHERE ekleyen_user_id = :user_id");
    $stmt->execute([':user_id' => $user_id]);
    
    $stmt = $db->prepare("UPDATE crm_satici_musteriler SET ekleyen_user_id = NULL WHERE ekleyen_user_id = :user_id");
    $stmt->execute([':user_id' => $user_id]);
    
    // 4. Portfolio closing kayıtlarını güncelle
    $stmt = $db->prepare("UPDATE portfolio_closings SET created_by = NULL WHERE created_by = :user_id");
    $stmt->execute([':user_id' => $user_id]);
    
    // 5. Kullanıcıyı sil
    $stmt = $db->prepare("DELETE FROM users WHERE id = :id");
    $stmt->execute([':id' => $user_id]);
    
    // Transaction'ı onayla
    $db->commit();
    
    $_SESSION['success'] = "Kullanıcı ve ilişkili veriler başarıyla silindi!";
    
} catch(PDOException $e) {
    // Hata durumunda geri al
    $db->rollBack();
    $_SESSION['error'] = "Silme işlemi sırasında hata oluştu: " . $e->getMessage();
}

// Liste sayfasına yönlendir
header("Location: list.php");
exit();
?>