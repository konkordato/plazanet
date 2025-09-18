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
$stmt = $db->prepare("SELECT * FROM property_images WHERE property_id = :id ORDER BY is_main DESC, id");
$stmt->execute([':id' => $id]);
$images = $stmt->fetchAll(PDO::FETCH_ASSOC);

// m² fiyatı hesapla
$m2_fiyat = $property['brut_metrekare'] > 0 ? round($property['fiyat'] / $property['brut_metrekare']) : 0;

// Benzer ilanları çek
$stmt = $db->prepare("SELECT p.*, pi.image_path 
                     FROM properties p 
                     LEFT JOIN property_images pi ON p.id = pi.property_id AND pi.is_main = 1
                     WHERE p.id != :id AND p.durum = 'aktif' AND p.kategori = :kategori AND p.ilce = :ilce
                     LIMIT 4");
$stmt->execute([':id' => $id, ':kategori' => $property['kategori'], ':ilce' => $property['ilce']]);
$similarProperties = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Popüler ilanları çek
$stmt = $db->prepare("SELECT p.*, pi.image_path 
                     FROM properties p 
                     LEFT JOIN property_images pi ON p.id = pi.property_id AND pi.is_main = 1
                     WHERE p.id != :id AND p.durum = 'aktif' ORDER BY RAND() LIMIT 6");
$stmt->execute([':id' => $id]);
$popularProperties = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Bu danışmanın toplam ilan sayısı
$stmt = $db->prepare("SELECT COUNT(*) as toplam FROM properties WHERE durum = 'aktif' AND id != :id");
$stmt->execute([':id' => $id]);
$digerIlanSayisi = $stmt->fetch(PDO::FETCH_ASSOC)['toplam'];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($property['baslik']); ?> - Plaza Emlak</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body style="background: #f4f4f4;">
    <!-- Üst Bar -->
    <div class="detail-nav">
        <a href="../index.php">← Ana Sayfa</a> / 
        <span><?php echo $property['emlak_tipi']; ?></span> / 
        <span><?php echo $property['kategori']; ?></span>
    </div>

    <div class="detail-container">
        <h1 class="detail-title"><?php echo htmlspecialchars($property['baslik']); ?></h1>
        
        <!-- 3 Kolonlu Grid -->
        <div class="detail-grid">
            <!-- SOL: Galeri -->
            <div class="detail-gallery">
                <?php if(count($images) > 0): ?>
                    <img id="mainImage" src="../<?php echo $images[0]['image_path']; ?>" class="gallery-main">
                    <span style="position:absolute;bottom:15px;right:15px;background:rgba(0,0,0,0.7);color:white;padding:5px 10px;border-radius:4px;">
                        <span id="currentImg">1</span> / <?php echo count($images); ?>
                    </span>
                    <div class="gallery-thumbs">
                        <?php foreach($images as $index => $img): ?>
                            <img src="../<?php echo $img['image_path']; ?>" 
                                 class="gallery-thumb <?php echo $index == 0 ? 'active' : ''; ?>"
                                 onclick="changeImage(this, <?php echo $index + 1; ?>)">
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div style="padding:100px 20px;text-align:center;background:#f5f5f5;border-radius:8px;">
                        📷 Henüz fotoğraf eklenmemiş
                    </div>
                <?php endif; ?>
            </div>

            <!-- ORTA: Bilgiler -->
            <div class="detail-info">
                <div class="detail-price"><?php echo number_format($property['fiyat'], 0, ',', '.'); ?> TL</div>
                <?php if($m2_fiyat > 0): ?>
                <div class="detail-price-m2">m² fiyatı: <?php echo number_format($m2_fiyat, 0, ',', '.'); ?> TL</div>
                <?php endif; ?>

                <table class="detail-table">
                    <tr><td>İlan No</td><td><?php echo $property['ilan_no']; ?></td></tr>
                    <tr><td>İlan Tarihi</td><td><?php echo date('d.m.Y', strtotime($property['ilan_tarihi'])); ?></td></tr>
                    <tr><td>Emlak Tipi</td><td><?php echo $property['emlak_tipi']; ?></td></tr>
                    <tr><td>Kategori</td><td><?php echo $property['kategori']; ?></td></tr>
                    
                    <!-- ARSA BİLGİLERİ -->
<!-- Bu kodu detail.php dosyasında 108. satır civarındaki ARSA BİLGİLERİ bölümüyle değiştirin -->

<?php if($property['emlak_tipi'] == 'arsa' || $property['emlak_tipi'] == 'Arsa'): ?>
    <!-- ARSA BİLGİLERİ -->
    <?php if(isset($property['imar_durumu']) && $property['imar_durumu']): ?>
    <tr><td>İmar Durumu</td><td><?php echo $property['imar_durumu']; ?></td></tr>
    <?php endif; ?>
    
    <?php if(isset($property['ada_no']) && $property['ada_no']): ?>
    <tr><td>Ada No</td><td><?php echo $property['ada_no']; ?></td></tr>
    <?php endif; ?>
    
    <?php if(isset($property['parsel_no']) && $property['parsel_no']): ?>
    <tr><td>Parsel No</td><td><?php echo $property['parsel_no']; ?></td></tr>
    <?php endif; ?>
    
    <?php if(isset($property['pafta_no']) && $property['pafta_no']): ?>
    <tr><td>Pafta No</td><td><?php echo $property['pafta_no']; ?></td></tr>
    <?php endif; ?>
    
    <?php if(isset($property['kaks']) && $property['kaks']): ?>
    <tr><td>Kaks (Emsal)</td><td><?php echo $property['kaks']; ?></td></tr>
    <?php endif; ?>
    
    <?php if(isset($property['gabari']) && $property['gabari']): ?>
    <tr><td>Gabari</td><td><?php echo $property['gabari']; ?></td></tr>
    <?php endif; ?>
    
    <?php if(isset($property['tapu_durumu']) && $property['tapu_durumu']): ?>
    <tr><td>Tapu Durumu</td><td><?php echo $property['tapu_durumu']; ?></td></tr>
    <?php endif; ?>
<?php else: ?>
                        <!-- KONUT/İŞYERİ BİLGİLERİ -->
                        <?php if($property['oda_sayisi']): ?>
                        <tr><td>Oda Sayısı</td><td><?php echo $property['oda_sayisi']; ?></td></tr>
                        <?php endif; ?>
                        <?php if($property['bina_yasi']): ?>
                        <tr><td>Bina Yaşı</td><td><?php echo $property['bina_yasi']; ?></td></tr>
                        <?php endif; ?>
                        <?php if($property['bulundugu_kat']): ?>
                        <tr><td>Bulunduğu Kat</td><td><?php echo $property['bulundugu_kat']; ?></td></tr>
                        <?php endif; ?>
                        <?php if($property['kat_sayisi']): ?>
                        <tr><td>Kat Sayısı</td><td><?php echo $property['kat_sayisi']; ?></td></tr>
                        <?php endif; ?>
                        <?php if($property['isitma']): ?>
                        <tr><td>Isıtma</td><td><?php echo $property['isitma']; ?></td></tr>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <tr><td>Brüt m²</td><td><?php echo $property['brut_metrekare']; ?> m²</td></tr>
                    <?php if($property['net_metrekare']): ?>
                    <tr><td>Net m²</td><td><?php echo $property['net_metrekare']; ?> m²</td></tr>
                    <?php endif; ?>
                    <tr><td>Kimden</td><td><?php echo $property['kimden'] ?? 'Emlak Ofisinden'; ?></td></tr>
                    <?php if($property['takas']): ?>
                    <tr><td>Takas</td><td><?php echo $property['takas']; ?></td></tr>
                    <?php endif; ?>
                </table>
            </div>

            <!-- SAĞ: Danışman -->
            <div class="detail-agent">
                <div class="agent-header">
                    <div class="agent-avatar">AK</div>
                    <div class="agent-info">
                        <h3>Ahmet Karaman</h3>
                        <p>Plaza Emlak & Yatırım</p>
                    </div>
                </div>
                <a href="tel:02722220003" class="agent-phone">📞 0 (272) 222 00 03</a>
                <a href="tel:05526530303" class="agent-phone">📱 0 (552) 653 03 03</a>
                <button class="agent-message">💬 Mesaj Gönder</button>
                <div style="margin-top:20px;padding-top:20px;border-top:1px solid #e5e5e5;">
                    <p style="font-size:13px;color:#666;margin-bottom:10px;">
                        📍 <?php echo $property['mahalle'] ? $property['mahalle'].', ' : ''; ?>
                        <?php echo $property['ilce']; ?> / <?php echo $property['il']; ?>
                    </p>
                    <a href="../index.php" style="color:#489ae9;text-decoration:none;font-size:14px;">
                        Bu danışmanın diğer <?php echo $digerIlanSayisi; ?> ilanını gör →
                    </a>
                </div>
            </div>
        </div>

        <!-- AÇIKLAMA -->
        <div class="detail-description">
            <h2>Açıklama</h2>
            <p><?php echo nl2br(htmlspecialchars($property['aciklama'])); ?></p>
        </div>

        <!-- BENZEer İLANLAR -->
        <?php if(count($similarProperties) > 0): ?>
        <div class="detail-description" style="margin-top:20px;">
            <h2>📍 Benzer İlanlar</h2>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:15px;margin-top:20px;">
                <?php foreach($similarProperties as $item): ?>
                <a href="detail.php?id=<?php echo $item['id']; ?>" style="text-decoration:none;color:inherit;">
                    <div style="background:white;border-radius:8px;overflow:hidden;box-shadow:0 2px 5px rgba(0,0,0,0.1);">
                        <?php if($item['image_path']): ?>
                            <img src="../<?php echo $item['image_path']; ?>" style="width:100%;height:150px;object-fit:cover;">
                        <?php else: ?>
                            <div style="width:100%;height:150px;background:#f5f5f5;display:flex;align-items:center;justify-content:center;">📷</div>
                        <?php endif; ?>
                        <div style="padding:15px;">
                            <h4 style="font-size:14px;margin-bottom:10px;"><?php echo htmlspecialchars(substr($item['baslik'],0,50)); ?>...</h4>
                            <p style="font-size:12px;color:#666;">📍 <?php echo $item['ilce']; ?></p>
                            <div style="font-size:18px;color:#ff6000;font-weight:bold;"><?php echo number_format($item['fiyat'],0,',','.'); ?> TL</div>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- POPÜLER İLANLAR -->
        <div class="detail-description" style="margin-top:20px;">
            <h2>🔥 Diğer müşterilerimiz bu ilanlara da baktı</h2>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:15px;margin-top:20px;">
                <?php foreach($popularProperties as $item): ?>
                <a href="detail.php?id=<?php echo $item['id']; ?>" style="text-decoration:none;color:inherit;">
                    <div style="background:white;border-radius:8px;overflow:hidden;box-shadow:0 2px 5px rgba(0,0,0,0.1);">
                        <?php if($item['image_path']): ?>
                            <img src="../<?php echo $item['image_path']; ?>" style="width:100%;height:150px;object-fit:cover;">
                        <?php else: ?>
                            <div style="width:100%;height:150px;background:#f5f5f5;display:flex;align-items:center;justify-content:center;">🏠</div>
                        <?php endif; ?>
                        <div style="padding:15px;">
                            <h4 style="font-size:14px;margin-bottom:10px;"><?php echo htmlspecialchars(substr($item['baslik'],0,50)); ?>...</h4>
                            <p style="font-size:12px;color:#666;">📍 <?php echo $item['ilce'].', '.$item['il']; ?></p>
                            <div style="font-size:18px;color:#ff6000;font-weight:bold;"><?php echo number_format($item['fiyat'],0,',','.'); ?> TL</div>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script>
    function changeImage(thumb, num) {
        document.getElementById('mainImage').src = thumb.src;
        document.getElementById('currentImg').textContent = num;
        document.querySelectorAll('.gallery-thumb').forEach(t => t.classList.remove('active'));
        thumb.classList.add('active');
    }
    </script>
</body>
</html>