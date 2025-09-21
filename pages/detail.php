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

// İlanı ekleyen KULLANICI bilgilerini çek (users tablosundan)
$userInfo = null;
if($property['ekleyen_admin_id']) {  // Bu alan aslında user_id tutuyor
    $stmt = $db->prepare("SELECT * FROM users WHERE id = :user_id");
    $stmt->execute([':user_id' => $property['ekleyen_admin_id']]);
    $userInfo = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Eğer kullanıcı bilgisi yoksa varsayılan değerler kullan
if(!$userInfo || empty($userInfo['username'])) {
    $userInfo = [
        'username' => 'Plaza Emlak',
        'full_name' => 'Plaza Emlak',
        'phone' => '0272 222 00 03', 
        'mobile' => '0552 653 03 03',
        'company' => 'Plaza Emlak & Yatırım',
        'title' => 'Gayrimenkul Danışmanı'
    ];
} else {
    // full_name yoksa username'i kullan
    if(empty($userInfo['full_name'])) {
        $userInfo['full_name'] = $userInfo['username'];
    }
    // Eksik alanları doldur
    if(empty($userInfo['phone'])) $userInfo['phone'] = '0272 222 00 03';
    if(empty($userInfo['mobile'])) $userInfo['mobile'] = '0552 653 03 03';
    if(empty($userInfo['company'])) $userInfo['company'] = 'Plaza Emlak & Yatırım';
    if(empty($userInfo['title'])) $userInfo['title'] = 'Gayrimenkul Danışmanı';
}

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
$stmt = $db->prepare("SELECT COUNT(*) as toplam FROM properties WHERE durum = 'aktif' AND ekleyen_admin_id = :user_id AND id != :id");
$stmt->execute([':user_id' => $property['ekleyen_admin_id'], ':id' => $id]);
$digerIlanSayisi = $stmt->fetch(PDO::FETCH_ASSOC)['toplam'];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($property['baslik']); ?> - Plaza Emlak</title>
    <link rel="stylesheet" href="../assets/css/style.css">
<style>
/* SADECE BU SAYFAYA ÖZEL - DİĞER SAYFALARLA ÇAKIŞMAZ */
.detail-page-custom .detail-container { 
    max-width: 1400px !important; /* Sadece bu sayfada genişlet */
}

.detail-page-custom .detail-grid {
    display: grid !important;
    grid-template-columns: 48% 30% 20% !important; /* Güvenli oranlar */
    gap: 1% !important;
}

/* Sadece bu sayfadaki galeriye özel */
.detail-page-custom .detail-gallery {
    position: relative;
}

.detail-page-custom .detail-gallery img.gallery-main {
    width: 100% !important;
    height: 480px !important; /* Resim yüksekliği */
    object-fit: cover !important;
    border-radius: 8px !important;
}

.detail-page-custom .gallery-thumbs {
    display: flex !important;
    gap: 8px !important;
    margin-top: 12px !important;
    overflow-x: auto !important;
}

.detail-page-custom .gallery-thumb {
    min-width: 85px !important;
    width: 85px !important;
    height: 65px !important;
    object-fit: cover !important;
    cursor: pointer !important;
}

/* Orta sütun ayarları */
.detail-page-custom .detail-info .detail-table {
    width: 100% !important;
}

.detail-page-custom .detail-table td {
    padding: 7px 8px !important; /* Tabloda boşlukları azalt */
    font-size: 13px !important;
}

/* Sağ sütun (danışman) ayarları */
.detail-page-custom .detail-agent {
    padding: 15px !important;
}

.detail-page-custom .agent-phone {
    font-size: 14px !important;
    padding: 10px 12px !important;
}

/* Mobil görünüm koruması */
@media (max-width: 768px) {
    .detail-page-custom .detail-grid {
        grid-template-columns: 1fr !important; /* Mobilde alt alta */
    }
    
    .detail-page-custom .detail-gallery img.gallery-main {
        height: 300px !important; /* Mobilde daha kısa */
    }
}
</style>
</head>
<body class="detail-page-custom" style="background: #f4f4f4;">
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
                    <?php 
                    // Profil resmini kontrol et
                    if(!empty($userInfo['profile_image']) && file_exists('../' . $userInfo['profile_image'])): 
                    ?>
                        <img src="../<?php echo htmlspecialchars($userInfo['profile_image']); ?>" 
                             alt="<?php echo htmlspecialchars($userInfo['full_name'] ?? $userInfo['username']); ?>"
                             style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover; border: 2px solid #3498db;">
                    <?php else: ?>
                        <div class="agent-avatar">
                            <?php 
                            // full_name varsa onu, yoksa username'i kullan
                            $displayName = $userInfo['full_name'] ?? $userInfo['username'];
                            $nameParts = explode(' ', $displayName);
                            $initials = '';
                            foreach($nameParts as $part) {
                                $initials .= mb_strtoupper(mb_substr($part, 0, 1, 'UTF-8'), 'UTF-8');
                            }
                            echo substr($initials, 0, 2); // Maksimum 2 harf
                            ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="agent-info">
                        <h3><?php echo htmlspecialchars($userInfo['full_name'] ?? $userInfo['username']); ?></h3>
                        <p><?php echo htmlspecialchars($userInfo['company'] ?? 'Plaza Emlak & Yatırım'); ?></p>
                    </div>
                </div>
                
            <?php if(!empty($userInfo['phone'])): ?>
                <a href="tel:02722220003" class="agent-phone">
                    📞 0272 222 00 03
                </a>
                <?php endif; ?>
                
                <?php 
                // Kullanıcının kendi telefonu varsa göster, yoksa varsayılan
                $mobileNumber = !empty($userInfo['mobile']) ? $userInfo['mobile'] : 
                               (!empty($userInfo['phone']) ? $userInfo['phone'] : '0552 653 03 03');
                ?>
                <a href="tel:<?php echo preg_replace('/[^0-9]/', '', $mobileNumber); ?>" class="agent-phone">
                    📱 <?php echo htmlspecialchars($mobileNumber); ?>
                </a>
                
                <button class="agent-message" onclick="sendMessage('<?php echo $userInfo['full_name'] ?? $userInfo['username']; ?>', '<?php echo $property['ilan_no']; ?>')">
                    💬 Mesaj Gönder
                </button>
                
                <div style="margin-top:20px;padding-top:20px;border-top:1px solid #e5e5e5;">
                    <p style="font-size:13px;color:#666;margin-bottom:10px;">
                        📍 <?php echo $property['mahalle'] ? $property['mahalle'].', ' : ''; ?>
                        <?php echo $property['ilce']; ?> / <?php echo $property['il']; ?>
                    </p>
                    
                    <?php if($digerIlanSayisi > 0): ?>
                    <a href="danisman-ilanlari.php?user_id=<?php echo $property['ekleyen_admin_id']; ?>" 
                       style="color:#489ae9;text-decoration:none;font-size:14px;">
                        Bu danışmanın diğer <?php echo $digerIlanSayisi; ?> ilanını gör →
                    </a>
                    <?php else: ?>
                    <p style="font-size:13px;color:#999;">
                        Bu danışmanın başka ilanı bulunmuyor
                    </p>
                    <?php endif; ?>
                </div>
            <!-- Bütçe Arama Kutusu -->
<div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px; border: 1px solid #e0e0e0;">
    <h4 style="color: #2c3e50; margin-bottom: 12px; font-size: 16px; text-align: center; font-weight: 600;">
        💰 Bütçenize Uygun Ara
    </h4>
    <form method="GET" action="../search.php" style="display: flex; flex-direction: column; gap: 10px;">
        <input type="number" 
               name="min_butce" 
               placeholder="Min Bütçe (₺)" 
               style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px;">
        
        <input type="number" 
               name="max_butce" 
               placeholder="Max Bütçe (₺)" 
               style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px;">
        
        <button type="submit" 
                style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
                       color: white; 
                       padding: 10px; 
                       border: none; 
                       border-radius: 5px; 
                       cursor: pointer;
                       font-size: 14px;
                       font-weight: 600;
                       transition: all 0.3s;">
            🔍 Bütçeme Uygun Bul
        </button>
    </form>
</div>    
            </div>
            <!-- Danışman bölümü kapandı -->
        
            

        </div>
        <!-- 3 Kolonlu Grid kapandı - ÖNEMLİ! Bu satır eksikti -->

        <!-- AÇIKLAMA -->
        <div class="detail-description">
            <h2>Açıklama</h2>
            <p><?php echo nl2br(htmlspecialchars($property['aciklama'])); ?></p>
        </div>

        <!-- BENZER İLANLAR -->
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
    function sendMessage(agentName, propertyNo) {
    var message = prompt('Mesajınızı yazın:');
    if(message && message.trim() !== '') {
        // WhatsApp'a yönlendir
        var whatsappNumber = '905526530303'; // +90 olmadan
        var whatsappMessage = 'Merhaba ' + agentName + ', ' + propertyNo + ' nolu ilan hakkında bilgi almak istiyorum. Mesajım: ' + message;
        window.open('https://wa.me/' + whatsappNumber + '?text=' + encodeURIComponent(whatsappMessage), '_blank');
    }
}
    </script>
</body>
</html>