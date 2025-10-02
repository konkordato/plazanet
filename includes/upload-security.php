<?php
// DOSYA YÜKLEME GÜVENLİK KONTROLÜ

function secureFileUpload($file, $uploadDir = '../assets/uploads/') {
    // 1. Dosya var mı kontrolü
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Dosya yükleme hatası'];
    }
    
    // 2. Dosya boyutu kontrolü (5MB max)
    $maxSize = 5 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'error' => 'Dosya çok büyük (Max 5MB)'];
    }
    
    // 3. GERÇEK dosya tipi kontrolü (MIME)
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    // İzin verilen MIME tipleri
    $allowedMimes = [
        'image/jpeg',
        'image/jpg', 
        'image/png',
        'image/gif',
        'image/webp'
    ];
    
    if (!in_array($mimeType, $allowedMimes)) {
        return ['success' => false, 'error' => 'Geçersiz dosya tipi! Sadece resim yüklenebilir.'];
    }
    
    // 4. Dosya uzantısı kontrolü (çift kontrol)
    $fileName = basename($file['name']);
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    if (!in_array($ext, $allowedExt)) {
        return ['success' => false, 'error' => 'Geçersiz dosya uzantısı!'];
    }
    
    // 5. Dosya adını güvenli hale getir
    $safeName = uniqid('img_') . '_' . time() . '.' . $ext;
    
    // 6. Upload klasörü güvenliği
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // 7. .htaccess ile PHP çalıştırmayı engelle
    $htaccess = $uploadDir . '.htaccess';
    if (!file_exists($htaccess)) {
        $htaccessContent = "Options -ExecCGI\n";
        $htaccessContent .= "AddHandler cgi-script .php .pl .py .jsp .asp .sh .cgi\n";
        $htaccessContent .= "<FilesMatch \"\.(php|pl|py|jsp|asp|sh|cgi)$\">\n";
        $htaccessContent .= "Order Deny,Allow\n";
        $htaccessContent .= "Deny from all\n";
        $htaccessContent .= "</FilesMatch>\n";
        file_put_contents($htaccess, $htaccessContent);
    }
    
    // 8. Dosyayı yükle
    $uploadPath = $uploadDir . $safeName;
    
    // 9. Resmi yeniden oluştur (shell kodları temizlenir)
    $image = null;
    switch($mimeType) {
        case 'image/jpeg':
        case 'image/jpg':
            $image = imagecreatefromjpeg($file['tmp_name']);
            imagejpeg($image, $uploadPath, 90);
            break;
        case 'image/png':
            $image = imagecreatefrompng($file['tmp_name']);
            imagepng($image, $uploadPath, 8);
            break;
        case 'image/gif':
            $image = imagecreatefromgif($file['tmp_name']);
            imagegif($image, $uploadPath);
            break;
        case 'image/webp':
            $image = imagecreatefromwebp($file['tmp_name']);
            imagewebp($image, $uploadPath, 90);
            break;
    }
    
    if ($image) {
        imagedestroy($image);
        
        // 10. Dosya izinlerini ayarla
        chmod($uploadPath, 0644);
        
        return [
            'success' => true,
            'filename' => $safeName,
            'path' => $uploadPath,
            'mime' => $mimeType
        ];
    }
    
    return ['success' => false, 'error' => 'Dosya işlenemedi'];
}

// Kullanım örneği:
/*
if(isset($_FILES['image'])) {
    $result = secureFileUpload($_FILES['image']);
    if($result['success']) {
        echo "Dosya yüklendi: " . $result['filename'];
    } else {
        echo "Hata: " . $result['error'];
    }
}
*/
?>