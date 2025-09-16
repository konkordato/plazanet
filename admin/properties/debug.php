<?php
session_start();
$_SESSION['admin_logged_in'] = true;

echo "<h2>Sistem Kontrolü</h2>";

// PHP Upload ayarları
echo "<h3>PHP Ayarları:</h3>";
echo "file_uploads: " . ini_get('file_uploads') . "<br>";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "<br>";
echo "post_max_size: " . ini_get('post_max_size') . "<br>";
echo "max_file_uploads: " . ini_get('max_file_uploads') . "<br>";

// Klasör kontrolü
echo "<h3>Klasör Durumu:</h3>";
$uploadPath = '../../assets/uploads/properties/';
$fullPath = realpath($uploadPath) ?: dirname(__FILE__) . '/../../assets/uploads/properties/';

if(is_dir($fullPath)) {
    echo "✓ Klasör var: " . $fullPath . "<br>";
    echo "Yazılabilir mi: " . (is_writable($fullPath) ? 'EVET' : 'HAYIR') . "<br>";
} else {
    echo "✗ Klasör YOK - Oluşturuluyor...<br>";
    if(mkdir($fullPath, 0777, true)) {
        echo "✓ Oluşturuldu<br>";
    }
}

// Basit upload testi
if(isset($_FILES['test'])) {
    echo "<h3>Upload Sonucu:</h3>";
    $testFile = $_FILES['test'];
    $targetFile = $fullPath . '/test_' . time() . '.jpg';
    
    if(move_uploaded_file($testFile['tmp_name'], $targetFile)) {
        echo "✓ BAŞARILI - Dosya yüklendi: " . basename($targetFile) . "<br>";
    } else {
        echo "✗ BAŞARISIZ - Hata: " . error_get_last()['message'] . "<br>";
    }
}
?>

<h3>Upload Testi:</h3>
<form method="post" enctype="multipart/form-data">
    <input type="file" name="test" required>
    <button type="submit">Test Et</button>
</form>