<?php
session_start();
if(!isset($_SESSION['admin_logged_in'])) {
    exit('Yetkisiz erişim');
}

require_once '../../../config/database.php';

$ilce_adi = $_GET['ilce'] ?? '';

if(empty($ilce_adi)) {
    echo '<option value="">İlçe seçin</option>';
    exit;
}

// İlçe ID'sini bul
$stmt = $db->prepare("SELECT id FROM ilceler WHERE ilce_adi = :ilce_adi");
$stmt->bindParam(':ilce_adi', $ilce_adi);
$stmt->execute();
$ilce = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$ilce) {
    echo '<option value="">Mahalle bulunamadı</option>';
    exit;
}

// Mahalleleri getir
$stmt = $db->prepare("SELECT * FROM mahalleler WHERE ilce_id = :ilce_id ORDER BY mahalle_adi");
$stmt->bindParam(':ilce_id', $ilce['id']);
$stmt->execute();
$mahalleler = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo '<option value="">Mahalle Seçin</option>';
foreach($mahalleler as $mahalle) {
    echo '<option value="' . htmlspecialchars($mahalle['mahalle_adi']) . '">' . 
         htmlspecialchars($mahalle['mahalle_adi']) . '</option>';
}
?>