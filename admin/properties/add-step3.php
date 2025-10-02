<?php
session_start();
if(!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: ../index.php");
    exit();
}

require_once '../../config/database.php';

// Form verilerini al ve session'a kaydet
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $_SESSION['property_data'] = $_POST;
    
    // FOTOĞRAFLARI İŞLE - add-step2'den geliyor
    if(isset($_FILES['photos']) && !empty($_FILES['photos']['name'][0])) {
        $tempFiles = [];
        $uploadErrors = [];
        
        // Geçici klasör oluştur
        $tempDir = '../../assets/uploads/temp/';
        if(!is_dir($tempDir)) {
            @mkdir($tempDir, 0777, true);
        }
        
        // Her fotoğrafı işle
        $totalFiles = count($_FILES['photos']['name']);
        
        // Maksimum 50 fotoğraf kontrolü
        if($totalFiles > 50) {
            $_SESSION['warning'] = "Maksimum 50 fotoğraf yükleyebilirsiniz. İlk 50 fotoğraf alındı.";
            $totalFiles = 50;
        }
        
        for($i = 0; $i < $totalFiles; $i++) {
            // Hata kontrolü
            if($_FILES['photos']['error'][$i] !== UPLOAD_ERR_OK) {
                $uploadErrors[] = $_FILES['photos']['name'][$i] . " yüklenemedi (Hata kodu: " . $_FILES['photos']['error'][$i] . ")";
                continue;
            }
            
            // Boyut kontrolü (10MB)
            if($_FILES['photos']['size'][$i] > 10485760) {
                $uploadErrors[] = $_FILES['photos']['name'][$i] . " 10MB'dan büyük!";
                continue;
            }
            
            // Dosya tipi kontrolü
            $fileType = strtolower(pathinfo($_FILES['photos']['name'][$i], PATHINFO_EXTENSION));
            $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if(!in_array($fileType, $allowedTypes)) {
                $uploadErrors[] = $_FILES['photos']['name'][$i] . " geçersiz dosya tipi!";
                continue;
            }
            
            // Geçici dosya adı oluştur
            $tempName = 'temp_' . session_id() . '_' . time() . '_' . $i . '.' . $fileType;
            $tempPath = $tempDir . $tempName;
            
            // Dosyayı temp klasöre yükle
            if(move_uploaded_file($_FILES['photos']['tmp_name'][$i], $tempPath)) {
                // Başarılı yükleme
                $tempFiles[] = [
                    'path' => realpath($tempPath),
                    'name' => $_FILES['photos']['name'][$i],
                    'type' => $_FILES['photos']['type'][$i],
                    'size' => $_FILES['photos']['size'][$i]
                ];
            } else {
                $uploadErrors[] = $_FILES['photos']['name'][$i] . " temp klasöre yüklenemedi!";
            }
        }
        
        // Session'a kaydet
        if(!empty($tempFiles)) {
            $_SESSION['temp_photos'] = $tempFiles;
        }
        
        // Hataları session'a kaydet
        if(!empty($uploadErrors)) {
            $_SESSION['upload_errors'] = $uploadErrors;
        }
    }
}

// Session'dan verileri al
$data = $_SESSION['property_data'] ?? [];

if(empty($data)) {
    header("Location: add-step1.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Önizleme - Plaza Emlak</title>
    <link rel="stylesheet" href="../../assets/css/admin-form.css">
    <style>
        .preview-container {
            max-width: 900px;
            margin: 0 auto;
        }
        .preview-section {
            background: #f8f9fa;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            border-left: 4px solid #3498db;
        }
        .preview-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 15px;
            color: #2c3e50;
        }
        .preview-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        .preview-item {
            padding: 10px;
            background: white;
            border-radius: 5px;
        }
        .preview-label {
            font-size: 12px;
            color: #7f8c8d;
            margin-bottom: 5px;
        }
        .preview-value {
            font-size: 14px;
            color: #2c3e50;
            font-weight: 500;
        }
        .admin-only {
            background: #fff3cd;
            border-left-color: #ffc107;
        }
        .photo-preview-section {
            background: #e8f5e9;
            border-left-color: #4caf50;
        }
        .photo-count {
            background: #4caf50;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            display: inline-block;
            margin-top: 10px;
        }
        .error-box {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .warning-box {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <div class="logo">
                <img src="../../assets/images/plaza-logo.png" alt="Plaza">
                <span>İlan Önizleme</span>
            </div>
        </div>
    </div>

    <!-- Adımlar -->
    <div class="steps">
        <div class="container">
            <div class="steps-wrapper">
                <div class="step completed">
                    <div class="step-circle">✔</div>
                    <div class="step-title">Kategori Seçimi</div>
                </div>
                <div class="step-line active"></div>
                <div class="step completed">
                    <div class="step-circle">✔</div>
                    <div class="step-title">İlan Detayları</div>
                </div>
                <div class="step-line active"></div>
                <div class="step active">
                    <div class="step-circle">3</div>
                    <div class="step-title">Önizleme</div>
                </div>
                <div class="step-line"></div>
                <div class="step">
                    <div class="step-circle">4</div>
                    <div class="step-title">Tebrikler</div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="content">
            <h1 class="page-title">İlan Önizleme</h1>
            <p style="color: #666; margin-bottom: 30px;">Lütfen bilgileri kontrol edin.</p>

            <!-- Hata mesajları -->
            <?php if(isset($_SESSION['upload_errors']) && !empty($_SESSION['upload_errors'])): ?>
            <div class="error-box">
                <strong>Bazı fotoğraflar yüklenemedi:</strong><br>
                <?php 
                foreach($_SESSION['upload_errors'] as $err) {
                    echo "• " . htmlspecialchars($err) . "<br>";
                }
                unset($_SESSION['upload_errors']);
                ?>
            </div>
            <?php endif; ?>
            
            <?php if(isset($_SESSION['warning'])): ?>
            <div class="warning-box">
                <?php 
                echo $_SESSION['warning'];
                unset($_SESSION['warning']);
                ?>
            </div>
            <?php endif; ?>

            <div class="preview-container">
                <!-- Temel Bilgiler -->
                <div class="preview-section">
                    <div class="preview-title">Temel Bilgiler</div>
                    <div class="preview-grid">
                        <div class="preview-item">
                            <div class="preview-label">İlan Başlığı</div>
                            <div class="preview-value"><?php echo htmlspecialchars($data['baslik'] ?? ''); ?></div>
                        </div>
                        <div class="preview-item">
                            <div class="preview-label">Kategori</div>
                            <div class="preview-value">
                                <?php echo ucfirst($data['emlak_tipi'] ?? '') . ' > ' . ucfirst($data['kategori'] ?? ''); ?>
                                <?php if(!empty($data['alt_kategori'])): ?>
                                    > <?php echo ucfirst($data['alt_kategori']); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="preview-item">
                            <div class="preview-label">Fiyat</div>
                            <div class="preview-value"><?php echo number_format($data['fiyat'] ?? 0, 0, ',', '.') . ' ' . ($data['para_birimi'] ?? 'TL'); ?></div>
                        </div>
                        <div class="preview-item">
                            <div class="preview-label">Metrekare</div>
                            <div class="preview-value">
                                Brüt: <?php echo $data['brut_metrekare'] ?? '-'; ?> m²
                                <?php if(!empty($data['net_metrekare'])): ?>
                                    / Net: <?php echo $data['net_metrekare']; ?> m²
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if(!empty($data['oda_sayisi'])): ?>
                        <div class="preview-item">
                            <div class="preview-label">Oda Sayısı</div>
                            <div class="preview-value"><?php echo $data['oda_sayisi']; ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Adres Bilgileri -->
                <div class="preview-section">
                    <div class="preview-title">Adres Bilgileri</div>
                    <div class="preview-grid">
                        <div class="preview-item">
                            <div class="preview-label">İl</div>
                            <div class="preview-value"><?php echo htmlspecialchars($data['il'] ?? 'Afyonkarahisar'); ?></div>
                        </div>
                        <div class="preview-item">
                            <div class="preview-label">İlçe</div>
                            <div class="preview-value"><?php echo htmlspecialchars($data['ilce'] ?? '-'); ?></div>
                        </div>
                        <?php if(!empty($data['mahalle'])): ?>
                        <div class="preview-item">
                            <div class="preview-label">Mahalle</div>
                            <div class="preview-value"><?php echo htmlspecialchars($data['mahalle']); ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Açıklama -->
                <div class="preview-section">
                    <div class="preview-title">Açıklama</div>
                    <div style="background: white; padding: 15px; border-radius: 5px;">
                        <?php echo nl2br(htmlspecialchars($data['aciklama'] ?? '')); ?>
                    </div>
                </div>

                <!-- Fotoğraflar Önizleme -->
                <?php if(isset($_SESSION['temp_photos']) && !empty($_SESSION['temp_photos'])): ?>
                <div class="preview-section photo-preview-section">
                    <div class="preview-title">📷 Yüklenen Fotoğraflar</div>
                    <div style="padding: 10px;">
                        <p>Toplam <strong><?php echo count($_SESSION['temp_photos']); ?></strong> adet fotoğraf yüklendi.</p>
                        <div class="photo-count">✔ Fotoğraflar hazır</div>
                    </div>
                </div>
                <?php else: ?>
                <div class="preview-section">
                    <div class="preview-title">📷 Fotoğraflar</div>
                    <div style="padding: 10px; color: #999;">
                        Fotoğraf yüklenmedi
                    </div>
                </div>
                <?php endif; ?>

                <!-- Butonlar -->
                <div class="buttons">
                    <button type="button" class="btn btn-back" onclick="history.back()">
                        ← Geri Dön ve Düzenle
                    </button>
                    
                    <form method="POST" action="ajax/save-property.php" style="display: inline;">
                        <?php foreach($data as $key => $value): ?>
                            <?php if(!is_array($value)): ?>
                            <input type="hidden" name="<?php echo htmlspecialchars($key); ?>" 
                                   value="<?php echo htmlspecialchars($value ?? ''); ?>">
                            <?php endif; ?>
                        <?php endforeach; ?>
                        
                        <input type="hidden" name="save_property" value="1">
                        <button type="submit" class="btn btn-save" 
                                onclick="this.disabled=true; this.innerHTML='Kaydediliyor...'; this.form.submit();">
                            ✔ Onayla ve Kaydet
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>