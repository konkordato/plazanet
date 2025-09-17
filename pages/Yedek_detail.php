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

// Benzer ilanları çek (aynı kategori ve ilçe)
$stmt = $db->prepare("SELECT p.*, pi.image_path 
                     FROM properties p 
                     LEFT JOIN property_images pi ON p.id = pi.property_id AND pi.is_main = 1
                     WHERE p.id != :id 
                     AND p.durum = 'aktif'
                     AND p.kategori = :kategori
                     AND p.ilce = :ilce
                     LIMIT 4");
$stmt->execute([
    ':id' => $id,
    ':kategori' => $property['kategori'],
    ':ilce' => $property['ilce']
]);
$similarProperties = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Popüler ilanları çek (farklı kategorilerden)
$stmt = $db->prepare("SELECT p.*, pi.image_path 
                     FROM properties p 
                     LEFT JOIN property_images pi ON p.id = pi.property_id AND pi.is_main = 1
                     WHERE p.id != :id 
                     AND p.durum = 'aktif'
                     ORDER BY RAND()
                     LIMIT 6");
$stmt->execute([':id' => $id]);
$popularProperties = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Danışman bilgilerini al (sabit bilgiler)
$danisman = [
    'ad' => 'Ahmet Karaman',
    'telefon' => '0 (272) 222 00 03',
    'cep' => '0 (552) 653 03 03',
    'foto' => '../assets/images/ahmet-karaman.jpg'
];

// Bu danışmanın diğer ilanları
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
    <style>
        /* Header */
        .detail-header {
            background: white;
            padding: 15px 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .logo-link {
            display: flex;
            align-items: center;
            text-decoration: none;
        }
        .logo-link img {
            height: 50px;
            margin-right: 10px;
        }
        .logo-link span {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
        }
        .nav-links {
            display: flex;
            gap: 30px;
            list-style: none;
        }
        .nav-links a {
            color: #2c3e50;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }
        .nav-links a:hover {
            color: #3498db;
        }

        /* Ana içerik */
        .detail-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 30px;
        }

        /* Sol taraf - İlan detayları */
        .left-content {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .property-title {
            padding: 20px;
            border-bottom: 1px solid #eee;
        }
        .property-title h1 {
            font-size: 24px;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        .property-price {
            font-size: 28px;
            color: #e74c3c;
            font-weight: bold;
        }

        /* Galeri */
        .gallery {
            padding: 20px;
            border-bottom: 1px solid #eee;
        }
        .main-image {
            width: 100%;
            max-height: 500px;
            object-fit: cover;
            border-radius: 8px;
        }
        .thumb-list {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            overflow-x: auto;
        }
        .thumb-item {
            width: 100px;
            height: 80px;
            object-fit: cover;
            border-radius: 5px;
            cursor: pointer;
            border: 2px solid transparent;
            transition: all 0.3s;
        }
        .thumb-item:hover {
            border-color: #3498db;
        }

        /* İlan bilgileri */
        .property-details {
            padding: 20px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin: 20px 0;
        }
        .info-item {
            padding: 12px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        .info-label {
            color: #7f8c8d;
            font-size: 13px;
            margin-bottom: 5px;
        }
        .info-value {
            font-weight: 600;
            color: #2c3e50;
            font-size: 15px;
        }

        /* Sağ taraf - Danışman */
        .right-sidebar {
            position: sticky;
            top: 100px;
            height: fit-content;
        }
        .agent-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .agent-photo {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: #e0e0e0;
            margin: 0 auto 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            color: #7f8c8d;
            overflow: hidden;
        }
        .agent-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .agent-name {
            font-size: 20px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        .agent-contact {
            margin: 15px 0;
        }
        .contact-item {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
            margin-bottom: 10px;
            color: #2c3e50;
            font-weight: 500;
        }
        .btn-message {
            width: 100%;
            padding: 12px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .btn-message:hover {
            background: #2980b9;
        }
        .other-listings {
            display: block;
            text-align: center;
            margin-top: 15px;
            color: #3498db;
            text-decoration: none;
        }

        /* Benzer ilanlar */
        .similar-section {
            margin: 40px 0;
        }
        .section-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        .section-header h2 {
            font-size: 22px;
            color: #2c3e50;
        }
        .property-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }
        .property-item {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s;
            text-decoration: none;
            color: inherit;
        }
        .property-item:hover {
            transform: translateY(-5px);
        }
        .property-img {
            width: 100%;
            height: 180px;
            object-fit: cover;
        }
        .property-content {
            padding: 15px;
        }
        .property-content h3 {
            font-size: 16px;
            color: #2c3e50;
            margin-bottom: 8px;
        }
        .property-location {
            color: #7f8c8d;
            font-size: 14px;
            margin-bottom: 10px;
        }
        .property-price-small {
            font-size: 18px;
            font-weight: bold;
            color: #e74c3c;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="detail-header">
        <div class="header-content">
            <a href="../index.php" class="logo-link">
                <img src="../assets/images/plaza-logo.png" alt="Plaza Emlak">
                <span>Plaza Emlak</span>
            </a>
            <ul class="nav-links">
                <li><a href="../index.php">Ana Sayfa</a></li>
                <li><a href="../search.php?kategori=Satılık">Satılık</a></li>
                <li><a href="../search.php?kategori=Kiralık">Kiralık</a></li>
                <li><a href="../index.php#iletisim">İletişim</a></li>
            </ul>
        </div>
    </header>

    <!-- Ana İçerik -->
    <div class="detail-container">
        <!-- Sol Taraf - İlan Detayları -->
        <div class="left-content">
            <div class="property-title">
                <h1><?php echo htmlspecialchars($property['baslik']); ?></h1>
                <div class="property-price">
                    <?php echo number_format($property['fiyat'], 0, ',', '.'); ?> ₺
                </div>
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
                    <div style="padding: 100px; text-align: center; background: #f5f5f5; border-radius: 8px;">
                        <span style="font-size: 48px;">📷</span>
                        <p style="color: #999;">Fotoğraf Yüklenmemiş</p>
                    </div>
                <?php endif; ?>
            </div>

            <div class="property-details">
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
    
    <?php if($property['emlak_tipi'] == 'arsa'): ?>
        <!-- ARSA BİLGİLERİ -->
        <div class="info-item">
            <div class="info-label">Metrekare</div>
            <div class="info-value"><?php echo $property['brut_metrekare']; ?> m²</div>
        </div>
        <?php if($property['metrekare_fiyat']): ?>
        <div class="info-item">
            <div class="info-label">m² Fiyatı</div>
            <div class="info-value"><?php echo number_format($property['metrekare_fiyat'], 0, ',', '.'); ?> ₺</div>
        </div>
        <?php endif; ?>
        <?php if($property['imar_durumu']): ?>
        <div class="info-item">
            <div class="info-label">İmar Durumu</div>
            <div class="info-value"><?php echo $property['imar_durumu']; ?></div>
        </div>
        <?php endif; ?>
        <?php if($property['ada_no']): ?>
        <div class="info-item">
            <div class="info-label">Ada No</div>
            <div class="info-value"><?php echo $property['ada_no']; ?></div>
        </div>
        <?php endif; ?>
        <?php if($property['parsel_no']): ?>
        <div class="info-item">
            <div class="info-label">Parsel No</div>
            <div class="info-value"><?php echo $property['parsel_no']; ?></div>
        </div>
        <?php endif; ?>
        <?php if($property['pafta_no']): ?>
        <div class="info-item">
            <div class="info-label">Pafta No</div>
            <div class="info-value"><?php echo $property['pafta_no']; ?></div>
        </div>
        <?php endif; ?>
        <?php if($property['kaks_emsal']): ?>
        <div class="info-item">
            <div class="info-label">Kaks (Emsal)</div>
            <div class="info-value"><?php echo $property['kaks_emsal']; ?></div>
        </div>
        <?php endif; ?>
        <?php if($property['gabari']): ?>
        <div class="info-item">
            <div class="info-label">Gabari</div>
            <div class="info-value"><?php echo $property['gabari']; ?></div>
        </div>
        <?php endif; ?>
        <?php if($property['tapu_durumu']): ?>
        <div class="info-item">
            <div class="info-label">Tapu Durumu</div>
            <div class="info-value"><?php echo $property['tapu_durumu']; ?></div>
        </div>
        <?php endif; ?>
        <?php if($property['krediye_uygun']): ?>
        <div class="info-item">
            <div class="info-label">Krediye Uygunluk</div>
            <div class="info-value"><?php echo $property['krediye_uygun']; ?></div>
        </div>
        <?php endif; ?>
        <?php if($property['takas']): ?>
        <div class="info-item">
            <div class="info-label">Takas</div>
            <div class="info-value"><?php echo $property['takas']; ?></div>
        </div>
        <?php endif; ?>
    <?php else: ?>
        <!-- KONUT/İŞYERİ BİLGİLERİ -->
        <?php if($property['oda_sayisi']): ?>
        <div class="info-item">
            <div class="info-label">Oda Sayısı</div>
            <div class="info-value"><?php echo $property['oda_sayisi']; ?></div>
        </div>
        <?php endif; ?>
        <div class="info-item">
            <div class="info-label">Brüt m²</div>
            <div class="info-value"><?php echo $property['brut_metrekare']; ?></div>
        </div>
        <?php if($property['net_metrekare']): ?>
        <div class="info-item">
            <div class="info-label">Net m²</div>
            <div class="info-value"><?php echo $property['net_metrekare']; ?></div>
        </div>
        <?php endif; ?>
        <?php if($property['bina_yasi']): ?>
        <div class="info-item">
            <div class="info-label">Bina Yaşı</div>
            <div class="info-value"><?php echo $property['bina_yasi']; ?></div>
        </div>
        <?php endif; ?>
        <?php if($property['bulundugu_kat']): ?>
        <div class="info-item">
            <div class="info-label">Bulunduğu Kat</div>
            <div class="info-value"><?php echo $property['bulundugu_kat']; ?></div>
        </div>
        <?php endif; ?>
        <?php if($property['kat_sayisi']): ?>
        <div class="info-item">
            <div class="info-label">Kat Sayısı</div>
            <div class="info-value"><?php echo $property['kat_sayisi']; ?></div>
        </div>
        <?php endif; ?>
        <?php if($property['isitma']): ?>
        <div class="info-item">
            <div class="info-label">Isıtma</div>
            <div class="info-value"><?php echo $property['isitma']; ?></div>
        </div>
        <?php endif; ?>
        <?php if($property['banyo_sayisi']): ?>
        <div class="info-item">
            <div class="info-label">Banyo Sayısı</div>
            <div class="info-value"><?php echo $property['banyo_sayisi']; ?></div>
        </div>
        <?php endif; ?>
        <?php if($property['balkon']): ?>
        <div class="info-item">
            <div class="info-label">Balkon</div>
            <div class="info-value"><?php echo $property['balkon']; ?></div>
        </div>
        <?php endif; ?>
        <?php if($property['esyali']): ?>
        <div class="info-item">
            <div class="info-label">Eşyalı</div>
            <div class="info-value"><?php echo $property['esyali']; ?></div>
        </div>
        <?php endif; ?>
        <?php if($property['kullanim_durumu']): ?>
        <div class="info-item">
            <div class="info-label">Kullanım Durumu</div>
            <div class="info-value"><?php echo $property['kullanim_durumu']; ?></div>
        </div>
        <?php endif; ?>
        <?php if($property['site_icerisinde']): ?>
        <div class="info-item">
            <div class="info-label">Site İçerisinde</div>
            <div class="info-value"><?php echo $property['site_icerisinde']; ?></div>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

                <h3 style="margin: 30px 0 15px;">Açıklama</h3>
                <p style="line-height: 1.6; color: #555;">
                    <?php echo nl2br(htmlspecialchars($property['aciklama'])); ?>
                </p>

                <h3 style="margin: 30px 0 15px;">Konum</h3>
                <p style="color: #555;">
                    <?php echo $property['mahalle'] . ', ' . $property['ilce'] . ' / ' . $property['il']; ?>
                </p>
            </div>
        </div>

        <!-- Sağ Taraf - Danışman Kartı -->
        <div class="right-sidebar">
            <div class="agent-card">
                <div class="agent-photo">
                    👤
                </div>
                <h3 class="agent-name"><?php echo $danisman['ad']; ?></h3>
                
                <div class="agent-contact">
                    <div class="contact-item">
                        <span>📞</span>
                        <span><?php echo $danisman['telefon']; ?></span>
                    </div>
                    <div class="contact-item">
                        <span>📱</span>
                        <span><?php echo $danisman['cep']; ?></span>
                    </div>
                </div>
                
                <button class="btn-message">💬 Mesaj Gönder</button>
                
                <a href="../search.php" class="other-listings">
                    Bu danışmanın diğer <?php echo $digerIlanSayisi; ?> ilanını gör →
                </a>
            </div>
        </div>
    </div>

    <!-- Benzer İlanlar -->
    <div style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
        <?php if(count($similarProperties) > 0): ?>
        <div class="similar-section">
            <div class="section-header">
                <h2>📍 Benzer İlanlar</h2>
            </div>
            <div class="property-list">
                <?php foreach($similarProperties as $item): ?>
                <a href="detail.php?id=<?php echo $item['id']; ?>" class="property-item">
                    <?php if($item['image_path']): ?>
                        <img src="../<?php echo $item['image_path']; ?>" class="property-img">
                    <?php else: ?>
                        <div style="height: 180px; background: #f5f5f5; display: flex; align-items: center; justify-content: center;">
                            <span style="font-size: 36px;">🏠</span>
                        </div>
                    <?php endif; ?>
                    <div class="property-content">
                        <h3><?php echo htmlspecialchars($item['baslik']); ?></h3>
                        <p class="property-location">📍 <?php echo $item['ilce']; ?></p>
                        <div class="property-price-small">
                            <?php echo number_format($item['fiyat'], 0, ',', '.'); ?> ₺
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Popüler İlanlar -->
        <div class="similar-section">
            <div class="section-header">
                <h2>🔥 Diğer müşterilerimiz bu ilanlara da baktı</h2>
            </div>
            <div class="property-list">
                <?php foreach($popularProperties as $item): ?>
                <a href="detail.php?id=<?php echo $item['id']; ?>" class="property-item">
                    <?php if($item['image_path']): ?>
                        <img src="../<?php echo $item['image_path']; ?>" class="property-img">
                    <?php else: ?>
                        <div style="height: 180px; background: #f5f5f5; display: flex; align-items: center; justify-content: center;">
                            <span style="font-size: 36px;">🏠</span>
                        </div>
                    <?php endif; ?>
                    <div class="property-content">
                        <h3><?php echo htmlspecialchars($item['baslik']); ?></h3>
                        <p class="property-location">📍 <?php echo $item['ilce'] . ', ' . $item['il']; ?></p>
                        <div class="property-price-small">
                            <?php echo number_format($item['fiyat'], 0, ',', '.'); ?> ₺
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer style="background: #2c3e50; color: white; padding: 30px 0; margin-top: 50px;">
        <div style="max-width: 1200px; margin: 0 auto; padding: 0 20px; text-align: center;">
            <p>&copy; 2024 Plaza Emlak & Yatırım - Ahmet Karaman. Tüm hakları saklıdır.</p>
        </div>
    </footer>
</body>
</html>