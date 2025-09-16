<?php
require_once '../config/database.php';

$id = $_GET['id'] ?? 0;

if(!$id) {
    header("Location: ../index.php");
    exit();
}

// İlan bilgilerini çek
$stmt = $db->prepare("SELECT * FROM properties WHERE id = :id AND durum = 'aktif'");
$stmt->execute([':id' => $id]);
$property = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$property) {
    header("Location: ../index.php");
    exit();
}

// Resimleri çek
$stmt = $db->prepare("SELECT * FROM property_images WHERE property_id = :id ORDER BY id");
$stmt->execute([':id' => $id]);
$images = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($property['baslik']); ?> - Plaza Emlak</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .detail-container { max-width: 1200px; margin: 20px auto; padding: 0 20px; }
        .detail-header { background: white; padding: 20px; margin-bottom: 20px; border-radius: 8px; }
        .detail-title { font-size: 24px; margin-bottom: 10px; }
        .detail-price { font-size: 32px; color: #e74c3c; font-weight: bold; }
        .detail-info { background: white; padding: 20px; border-radius: 8px; }
        .info-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin: 20px 0; }
        .info-item { padding: 10px; background: #f8f9fa; border-radius: 5px; }
        .info-label { color: #666; font-size: 14px; }
        .info-value { font-weight: bold; color: #333; }
        .gallery { background: white; padding: 20px; margin-bottom: 20px; border-radius: 8px; }
        .main-image { width: 100%; max-height: 500px; object-fit: cover; border-radius: 8px; }
        .thumb-list { display: flex; gap: 10px; margin-top: 10px; overflow-x: auto; }
        .thumb-item { width: 100px; height: 80px; object-fit: cover; border-radius: 5px; cursor: pointer; border: 2px solid transparent; }
        .thumb-item:hover { border-color: #3498db; }
        .no-image { background: #f5f5f5; padding: 100px; text-align: center; color: #999; border-radius: 8px; }
    </style>
</head>
<body>
    <div class="detail-container">
        <div class="detail-header">
            <h1 class="detail-title"><?php echo htmlspecialchars($property['baslik']); ?></h1>
            <div class="detail-price"><?php echo number_format($property['fiyat'], 0, ',', '.'); ?> ₺</div>
        </div>

        <div class="gallery">
            <?php if(count($images) > 0): ?>
                <img id="mainImage" src="../<?php echo $images[0]['image_path']; ?>" class="main-image">
                <div class="thumb-list">
                    <?php foreach($images as $img): ?>
                        <img src="../<?php echo $img['image_path']; ?>" 
                             class="thumb-item" 
                             onclick="document.getElementById('mainImage').src='../<?php echo $img['image_path']; ?>'">
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-image">Fotoğraf Yüklenmemiş</div>
            <?php endif; ?>
        </div>

        <div class="detail-info">
            <h2>İlan Detayları</h2>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">İlan No</div>
                    <div class="info-value"><?php echo $property['ilan_no']; ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">İlan Tarihi</div>
                    <div class="info-value"><?php echo date('d.m.Y', strtotime($property['ilan_tarihi'])); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Emlak Tipi</div>
                    <div class="info-value"><?php echo $property['emlak_tipi']; ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Kategori</div>
                    <div class="info-value"><?php echo $property['kategori']; ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Oda Sayısı</div>
                    <div class="info-value"><?php echo $property['oda_sayisi'] ?? '-'; ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Brüt m²</div>
                    <div class="info-value"><?php echo $property['brut_metrekare'] ?? '-'; ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Net m²</div>
                    <div class="info-value"><?php echo $property['net_metrekare'] ?? '-'; ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Bina Yaşı</div>
                    <div class="info-value"><?php echo $property['bina_yasi'] ?? '-'; ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Bulunduğu Kat</div>
                    <div class="info-value"><?php echo $property['bulundugu_kat'] ?? '-'; ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Kat Sayısı</div>
                    <div class="info-value"><?php echo $property['kat_sayisi'] ?? '-'; ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Isıtma</div>
                    <div class="info-value"><?php echo $property['isitma'] ?? '-'; ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Banyo Sayısı</div>
                    <div class="info-value"><?php echo $property['banyo_sayisi'] ?? '-'; ?></div>
                </div>
            </div>

            <h3>Açıklama</h3>
            <p><?php echo nl2br(htmlspecialchars($property['aciklama'])); ?></p>

            <h3>Konum</h3>
            <p><?php echo $property['mahalle'] . ', ' . $property['ilce'] . ' / ' . $property['il']; ?></p>
            <?php if($property['adres']): ?>
                <p><?php echo htmlspecialchars($property['adres']); ?></p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>