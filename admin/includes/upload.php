<?php
// Fotoğraf yükleme ve sıkıştırma fonksiyonları

// Resim sıkıştırma fonksiyonu
function compressImage($source, $destination, $quality = 85) {
    $info = getimagesize($source);
    
    // Resim tipine göre aç
    if ($info['mime'] == 'image/jpeg' || $info['mime'] == 'image/jpg') {
        $image = imagecreatefromjpeg($source);
    } elseif ($info['mime'] == 'image/png') {
        $image = imagecreatefrompng($source);
    } elseif ($info['mime'] == 'image/webp') {
        $image = imagecreatefromwebp($source);
    } else {
        return false;
    }
    
    // Boyutları al
    $width = imagesx($image);
    $height = imagesy($image);
    
    // Maksimum boyutlar (Full HD)
    $maxWidth = 1920;
    $maxHeight = 1080;
    
    // Gerekirse yeniden boyutlandır
    if ($width > $maxWidth || $height > $maxHeight) {
        $ratio = min($maxWidth/$width, $maxHeight/$height);
        $newWidth = round($width * $ratio);
        $newHeight = round($height * $ratio);
        
        $resized = imagecreatetruecolor($newWidth, $newHeight);
        
        // PNG için şeffaflığı koru
        if ($info['mime'] == 'image/png') {
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
            imagefilledrectangle($resized, 0, 0, $newWidth, $newHeight, $transparent);
        }
        
        // Yeniden boyutlandır
        imagecopyresampled($resized, $image, 0, 0, 0, 0, 
                          $newWidth, $newHeight, $width, $height);
        $image = $resized;
        $width = $newWidth;
        $height = $newHeight;
    }
    
    // Sıkıştırarak kaydet
    if ($info['mime'] == 'image/jpeg' || $info['mime'] == 'image/jpg') {
        imagejpeg($image, $destination, $quality);
    } elseif ($info['mime'] == 'image/png') {
        // PNG için 0-9 arası sıkıştırma (9 en yüksek)
        imagepng($image, $destination, 8);
    } elseif ($info['mime'] == 'image/webp') {
        imagewebp($image, $destination, $quality);
    }
    
    imagedestroy($image);
    
    // Dosya boyutunu kontrol et (10MB üstündeyse kaliteyi düşür)
    $fileSize = filesize($destination);
    if ($fileSize > 10485760 && $quality > 60) {
        // Kaliteyi düşürerek tekrar dene
        return compressImage($source, $destination, $quality - 10);
    }
    
    return true;
}

// Küçük resim (thumbnail) oluştur
function createThumbnail($source, $destination, $thumbWidth = 400, $thumbHeight = 300) {
    $info = getimagesize($source);
    
    // Resim tipine göre aç
    if ($info['mime'] == 'image/jpeg' || $info['mime'] == 'image/jpg') {
        $image = imagecreatefromjpeg($source);
    } elseif ($info['mime'] == 'image/png') {
        $image = imagecreatefrompng($source);
    } elseif ($info['mime'] == 'image/webp') {
        $image = imagecreatefromwebp($source);
    } else {
        return false;
    }
    
    $width = imagesx($image);
    $height = imagesy($image);
    
    // Kırpma oranını hesapla (resmi tam doldur)
    $ratio = max($thumbWidth/$width, $thumbHeight/$height);
    $newWidth = round($width * $ratio);
    $newHeight = round($height * $ratio);
    
    // Önce yeniden boyutlandır
    $resized = imagecreatetruecolor($newWidth, $newHeight);
    imagecopyresampled($resized, $image, 0, 0, 0, 0, 
                      $newWidth, $newHeight, $width, $height);
    
    // Ortadan kırp
    $thumb = imagecreatetruecolor($thumbWidth, $thumbHeight);
    $x = ($newWidth - $thumbWidth) / 2;
    $y = ($newHeight - $thumbHeight) / 2;
    
    imagecopy($thumb, $resized, 0, 0, $x, $y, $thumbWidth, $thumbHeight);
    
    // Kaydet
    if ($info['mime'] == 'image/jpeg' || $info['mime'] == 'image/jpg') {
        imagejpeg($thumb, $destination, 90);
    } elseif ($info['mime'] == 'image/png') {
        imagepng($thumb, $destination, 8);
    } elseif ($info['mime'] == 'image/webp') {
        imagewebp($thumb, $destination, 90);
    }
    
    imagedestroy($image);
    imagedestroy($resized);
    imagedestroy($thumb);
    
    return true;
}

// Çoklu resim yükleme fonksiyonu
function uploadPropertyImages($files, $propertyId, $db) {
    $uploadDir = '../../assets/uploads/properties/';
    $thumbDir = '../../assets/uploads/properties/thumbs/';
    
    // Klasörleri kontrol et
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    if (!file_exists($thumbDir)) {
        mkdir($thumbDir, 0777, true);
    }
    
    $uploadedFiles = [];
    $errors = [];
    
    // İzin verilen tipler
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
    $maxFileSize = 10 * 1024 * 1024; // 10MB
    
    $fileCount = count($files['name']);
    
    for ($i = 0; $i < $fileCount; $i++) {
        // Boş dosya kontrolü
        if (empty($files['name'][$i])) continue;
        
        // Hata kontrolü
        if ($files['error'][$i] !== UPLOAD_ERR_OK) {
            $errors[] = $files['name'][$i] . " yüklenemedi.";
            continue;
        }
        
        // Tip kontrolü
        $fileType = mime_content_type($files['tmp_name'][$i]);
        if (!in_array($fileType, $allowedTypes)) {
            $errors[] = $files['name'][$i] . " geçersiz dosya tipi.";
            continue;
        }
        
        // Benzersiz dosya adı
        $extension = pathinfo($files['name'][$i], PATHINFO_EXTENSION);
        $newFileName = 'prop_' . $propertyId . '_' . time() . '_' . $i . '.' . $extension;
        $uploadPath = $uploadDir . $newFileName;
        $thumbPath = $thumbDir . 'thumb_' . $newFileName;
        
        // Resmi sıkıştır ve yükle
        if (compressImage($files['tmp_name'][$i], $uploadPath)) {
            // Thumbnail oluştur
            createThumbnail($uploadPath, $thumbPath);
            
            // İlk resmi ana resim yap
            $isMain = (count($uploadedFiles) == 0) ? 1 : 0;
            
            // Veritabanına kaydet
            try {
                $stmt = $db->prepare("INSERT INTO property_images 
                    (property_id, image_path, image_name, is_main, image_order) 
                    VALUES (:property_id, :image_path, :image_name, :is_main, :image_order)");
                
                $stmt->execute([
                    ':property_id' => $propertyId,
                    ':image_path' => 'assets/uploads/properties/' . $newFileName,
                    ':image_name' => $files['name'][$i],
                    ':is_main' => $isMain,
                    ':image_order' => $i
                ]);
                
                $uploadedFiles[] = $newFileName;
            } catch(PDOException $e) {
                $errors[] = "Veritabanı hatası: " . $e->getMessage();
            }
        } else {
            $errors[] = $files['name'][$i] . " işlenemedi.";
        }
    }
    
    return [
        'success' => count($uploadedFiles) > 0,
        'uploaded' => $uploadedFiles,
        'errors' => $errors
    ];
}
?>