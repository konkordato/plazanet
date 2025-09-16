<?php
session_start();
if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../index.php");
    exit();
}

require_once '../../config/database.php';

// Form verilerini al ve session'a kaydet
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $_SESSION['property_data'] = $_POST;
    
    // FOTOĞRAFLARI TEMP KLASÖRE KAYDET
    if(isset($_FILES['photos']) && !empty($_FILES['photos']['name'][0])) {
        $tempFiles = [];
        $tempDir = sys_get_temp_dir() . '/plaza_temp_' . session_id() . '/';
        
        if(!is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }
        
        for($i = 0; $i < count($_FILES['photos']['name']); $i++) {
            if($_FILES['photos']['error'][$i] == 0) {
                $tempName = 'temp_' . time() . '_' . $i . '_' . $_FILES['photos']['name'][$i];
                $tempPath = $tempDir . $tempName;
                
                if(move_uploaded_file($_FILES['photos']['tmp_name'][$i], $tempPath)) {
                    $tempFiles[] = [
                        'path' => $tempPath,
                        'name' => $_FILES['photos']['name'][$i],
                        'type' => $_FILES['photos']['type'][$i],
                        'size' => $_FILES['photos']['size'][$i]
                    ];
                }
            }
        }
        $_SESSION['temp_photos'] = $tempFiles;
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
        .edit-btn {
            background: #95a5a6;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            float: right;
            font-size: 14px;
        }
        .edit-btn:hover {
            background: #7f8c8d;
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
                    <div class="step-circle">✓</div>
                    <div class="step-title">Kategori Seçimi</div>
                </div>
                <div class="step-line active"></div>
                <div class="step completed">
                    <div class="step-circle">✓</div>
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
            <p style="color: #666; margin-bottom: 30px;">Lütfen bilgileri kontrol edin. Düzenlemek için ilgili bölümdeki düzenle butonuna tıklayın.</p>

            <div class="preview-container">
                <!-- Temel Bilgiler -->
                <div class="preview-section">
                    <button class="edit-btn" onclick="history.back()">Düzenle</button>
                    <div class="preview-title">Temel Bilgiler</div>
                    <div class="preview-grid">
                        <div class="preview-item">
                            <div class="preview-label">İlan Başlığı</div>
                            <div class="preview-value"><?php echo htmlspecialchars($data['baslik'] ?? ''); ?></div>
                        </div>
                        <div class="preview-item">
                            <div class="preview-label">Kategori</div>
                            <div class="preview-value"><?php echo ucfirst($data['emlak_tipi'] ?? '') . ' > ' . ucfirst($data['kategori'] ?? ''); ?></div>
                        </div>
                        <div class="preview-item">
                            <div class="preview-label">Fiyat</div>
                            <div class="preview-value"><?php echo number_format($data['fiyat'] ?? 0, 0, ',', '.') . ' ' . ($data['para_birimi'] ?? 'TL'); ?></div>
                        </div>
                        <div class="preview-item">
                            <div class="preview-label">Metrekare</div>
                            <div class="preview-value">Brüt: <?php echo $data['brut_metrekare'] ?? '-'; ?> m² / Net: <?php echo $data['net_metrekare'] ?? '-'; ?> m²</div>
                        </div>
                        <div class="preview-item">
                            <div class="preview-label">Oda Sayısı</div>
                            <div class="preview-value"><?php echo $data['oda_sayisi'] ?? '-'; ?></div>
                        </div>
                        <div class="preview-item">
                            <div class="preview-label">Bina Yaşı</div>
                            <div class="preview-value"><?php echo $data['bina_yasi'] ?? '-'; ?></div>
                        </div>
                        <div class="preview-item">
                            <div class="preview-label">Kat Bilgisi</div>
                            <div class="preview-value"><?php echo ($data['bulundugu_kat'] ?? '-') . ' / ' . ($data['kat_sayisi'] ?? '-'); ?></div>
                        </div>
                        <div class="preview-item">
                            <div class="preview-label">Isıtma</div>
                            <div class="preview-value"><?php echo $data['isitma'] ?? '-'; ?></div>
                        </div>
                    </div>
                </div>

                <!-- Adres Bilgileri -->
                <div class="preview-section">
                    <div class="preview-title">Adres Bilgileri</div>
                    <div class="preview-grid">
                        <div class="preview-item">
                            <div class="preview-label">İl</div>
                            <div class="preview-value"><?php echo $data['il'] ?? '-'; ?></div>
                        </div>
                        <div class="preview-item">
                            <div class="preview-label">İlçe</div>
                            <div class="preview-value"><?php echo $data['ilce'] ?? '-'; ?></div>
                        </div>
                        <div class="preview-item">
                            <div class="preview-label">Mahalle</div>
                            <div class="preview-value"><?php echo $data['mahalle'] ?? '-'; ?></div>
                        </div>
                        <div class="preview-item">
                            <div class="preview-label">Açık Adres</div>
                            <div class="preview-value"><?php echo $data['adres'] ?? '-'; ?></div>
                        </div>
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
                        <div class="photo-count">✓ Fotoğraflar hazır</div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Danışman Bilgileri (Sadece Admin Görür) -->
                <div class="preview-section admin-only">
                    <div class="preview-title">🔒 Danışman Bilgileri (Sadece Siz Görürsünüz)</div>
                    <div class="preview-grid">
                        <div class="preview-item">
                            <div class="preview-label">Anahtar Numarası</div>
                            <div class="preview-value"><?php echo $data['anahtar_no'] ?? 'Belirtilmemiş'; ?></div>
                        </div>
                        <div class="preview-item">
                            <div class="preview-label">Mülk Sahibi Telefonu</div>
                            <div class="preview-value"><?php echo $data['mulk_sahibi_tel'] ?? 'Belirtilmemiş'; ?></div>
                        </div>
                    </div>
                    <?php if(!empty($data['danisman_notu'])): ?>
                    <div class="preview-item" style="margin-top: 15px;">
                        <div class="preview-label">Danışman Notu</div>
                        <div class="preview-value"><?php echo nl2br(htmlspecialchars($data['danisman_notu'])); ?></div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Butonlar -->
                <div class="buttons">
                    <button type="button" class="btn btn-back" onclick="history.back()">
                        ← Geri Dön ve Düzenle
                    </button>
                    <form method="POST" action="ajax/save-property.php" style="display: inline;">
                        <?php foreach($data as $key => $value): ?>
                            <?php if(is_array($value)) continue; ?>
                            <input type="hidden" name="<?php echo htmlspecialchars($key); ?>" value="<?php echo htmlspecialchars($value ?? ''); ?>">
                        <?php endforeach; ?>
                        <input type="hidden" name="save_property" value="1">
                        <button type="submit" class="btn btn-save">
                            ✓ Onayla ve Kaydet
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Debug için JavaScript -->
    <script>
    document.querySelector('.btn-save').addEventListener('click', function(e) {
        console.log('Kaydet butonu tıklandı');
    });
    </script>
</body>
</html>