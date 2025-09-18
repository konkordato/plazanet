<?php
require_once '../config/database.php';

// Tüm aktif ilanları çek (Ahmet Karaman'ın tüm ilanları)
$query = "SELECT p.*, pi.image_path 
          FROM properties p 
          LEFT JOIN property_images pi ON p.id = pi.property_id AND pi.is_main = 1
          WHERE p.durum = 'aktif'
          ORDER BY p.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$properties = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ahmet Karaman - Tüm İlanlar | Plaza Emlak</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { background: #f4f4f4; }
        
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 0;
            text-align: center;
            margin-bottom: 30px;
        }
        
        .agent-banner {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
            margin-bottom: 15px;
        }
        
        .agent-avatar-large {
            width: 80px;
            height: 80px;
            background: white;
            color: #667eea;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            font-weight: bold;
        }
        
        .page-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .breadcrumb {
            background: white;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .breadcrumb a {
            color: #3498db;
            text-decoration: none;
        }
        
        .breadcrumb a:hover {
            text-decoration: underline;
        }
        
        .result-bar {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .properties-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .property-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }
        
        .property-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        
        .property-card a {
            text-decoration: none;
            color: inherit;
        }
        
        .property-img {
            position: relative;
            height: 200px;
            background: #f5f5f5;
            overflow: hidden;
        }
        
        .property-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .property-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #e74c3c;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .property-badge.rent {
            background: #3498db;
        }
        
        .property-body {
            padding: 20px;
        }
        
        .property-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 10px;
            color: #333;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .property-location {
            color: #666;
            font-size: 14px;
            margin-bottom: 15px;
        }
        
        .property-features {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
            font-size: 14px;
            color: #555;
        }
        
        .property-price {
            font-size: 22px;
            font-weight: bold;
            color: #ff6000;
        }
        
        .no-photo {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            font-size: 48px;
            color: #ccc;
        }
        
        .contact-bar {
            background: white;
            padding: 30px;
            border-radius: 8px;
            text-align: center;
            margin-top: 40px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .contact-bar h3 {
            margin-bottom: 20px;
            color: #333;
        }
        
        .contact-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn-contact {
            padding: 12px 30px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn-phone {
            background: #27ae60;
            color: white;
        }
        
        .btn-phone:hover {
            background: #229954;
        }
        
        .btn-whatsapp {
            background: #25D366;
            color: white;
        }
        
        .btn-whatsapp:hover {
            background: #128C7E;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="page-header">
        <div class="agent-banner">
            <div class="agent-avatar-large">AK</div>
            <div>
                <h1 style="margin: 0; font-size: 28px;">Ahmet Karaman</h1>
                <p style="margin: 5px 0; opacity: 0.9;">Plaza Emlak & Yatırım Danışmanı</p>
            </div>
        </div>
        <p style="font-size: 18px;">Toplam <strong><?php echo count($properties); ?></strong> İlan</p>
    </div>
    
    <div class="page-container">
        <!-- Breadcrumb -->
        <div class="breadcrumb">
            <a href="../index.php">Ana Sayfa</a> → 
            <span>Danışman İlanları</span> → 
            <span>Ahmet Karaman</span>
        </div>
        
        <!-- Sonuç Bilgisi -->
        <div class="result-bar">
            <div>
                <strong style="font-size: 18px;"><?php echo count($properties); ?></strong> adet ilan listeleniyor
            </div>
            <div>
                <select style="padding: 8px 15px; border-radius: 5px; border: 1px solid #ddd;">
                    <option>Fiyata göre (Önce en düşük)</option>
                    <option>Fiyata göre (Önce en yüksek)</option>
                    <option>Tarihe göre (Önce en yeni)</option>
                </select>
            </div>
        </div>
        
        <!-- İlanlar Grid -->
        <?php if(count($properties) > 0): ?>
            <div class="properties-grid">
                <?php foreach($properties as $property): ?>
                <div class="property-card">
                    <a href="detail.php?id=<?php echo $property['id']; ?>">
                        <div class="property-img">
                            <?php if($property['image_path']): ?>
                                <img src="../<?php echo $property['image_path']; ?>" alt="<?php echo htmlspecialchars($property['baslik']); ?>">
                            <?php else: ?>
                                <div class="no-photo">📷</div>
                            <?php endif; ?>
                            <span class="property-badge <?php echo $property['kategori'] == 'Kiralık' ? 'rent' : ''; ?>">
                                <?php echo $property['kategori']; ?>
                            </span>
                        </div>
                        <div class="property-body">
                            <h3 class="property-title"><?php echo htmlspecialchars($property['baslik']); ?></h3>
                            <p class="property-location">📍 <?php echo $property['ilce'] . ', ' . $property['il']; ?></p>
                            <div class="property-features">
                                <?php if($property['oda_sayisi']): ?>
                                    <span>🏠 <?php echo $property['oda_sayisi']; ?></span>
                                <?php endif; ?>
                                <?php if($property['brut_metrekare']): ?>
                                    <span>📐 <?php echo $property['brut_metrekare']; ?> m²</span>
                                <?php endif; ?>
                                <?php if($property['bulundugu_kat']): ?>
                                    <span>🏢 <?php echo $property['bulundugu_kat']; ?>. Kat</span>
                                <?php endif; ?>
                            </div>
                            <div class="property-price">
                                <?php echo number_format($property['fiyat'], 0, ',', '.'); ?> TL
                            </div>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="background: white; padding: 60px 20px; text-align: center; border-radius: 10px;">
                <h3 style="color: #666;">Henüz aktif ilan bulunmuyor</h3>
                <p style="color: #999;">Lütfen daha sonra tekrar kontrol edin.</p>
            </div>
        <?php endif; ?>
        
        <!-- İletişim Bilgileri -->
        <div class="contact-bar">
            <h3>Hemen İletişime Geçin</h3>
            <p style="color: #666; margin-bottom: 20px;">Aradığınız gayrimenkulü bulmanız için size yardımcı olmaktan mutluluk duyarım.</p>
            <div class="contact-buttons">
                <a href="tel:02722220003" class="btn-contact btn-phone">
                    📞 (0272) 222 00 03
                </a>
                <a href="tel:05526530303" class="btn-contact btn-phone">
                    📱 (0552) 653 03 03
                </a>
                <a href="https://wa.me/905526530303" target="_blank" class="btn-contact btn-whatsapp">
                    💬 WhatsApp ile Ulaşın
                </a>
            </div>
        </div>
    </div>
</body>
</html>