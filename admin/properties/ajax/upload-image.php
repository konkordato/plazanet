<?php
session_start();
if (!isset($_SESSION['user_logged_in'])) {
    die('Yetkisiz');
}

if (!isset($_FILES['photos'])) {
    die('Dosya yok');
}

// Temp klasörü
$tempDir = '../../../assets/uploads/temp/';
if (!is_dir($tempDir)) {
    mkdir($tempDir, 0777, true);
}

// Session'da dizi yoksa oluştur
if (!isset($_SESSION['temp_photos'])) {
    $_SESSION['temp_photos'] = [];
}

// Her dosyayı kaydet
foreach ($_FILES['photos']['tmp_name'] as $key => $tmp) {
    if ($_FILES['photos']['error'][$key] == 0) {
        $name = 'temp_' . time() . '_' . $key . '.jpg';
        $path = $tempDir . $name;
        
        if (move_uploaded_file($tmp, $path)) {
            $_SESSION['temp_photos'][] = [
                'path' => $path,
                'name' => $_FILES['photos']['name'][$key]
            ];
        }
    }
}

echo 'OK';
?>