<?php
require_once '../config/database.php';

// URL'den danışman ID'sini al
$user_id = $_GET['user_id'] ?? 0;

// Danışman bilgilerini çek
$userInfo = null;
if ($user_id) {
    $stmt = $db->prepare("SELECT * FROM users WHERE id = :id");
    $stmt->execute([':id' => $user_id]);
    $userInfo = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Kullanıcı bulunamazsa ana sayfaya yönlendir
if (!$userInfo) {
    header("Location: ../index.php");
    exit();
}

// İsmin baş harflerini al
$nameParts = explode(' ', $userInfo['full_name']);
$initials = '';
foreach ($nameParts as $part) {
    if (!empty($part)) {
        $initials .= mb_substr($part, 0, 1, 'UTF-8');
    }
}
$initials = strtoupper($initials);

// Bu danışmanın aktif ilanlarını çek
$query = "SELECT p.*, pi.image_path 
          FROM properties p 
          LEFT JOIN property_images pi ON p.id = pi.property_id AND pi.is_main = 1
          WHERE p.durum = 'aktif' AND p.user_id = :user_id
          ORDER BY p.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute([':user_id' => $user_id]);
$properties = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($userInfo['full_name']); ?> - Tüm İlanlar | Plaza Emlak</title>

    <!-- CSS Dosyaları -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/override.css">
    <link rel="stylesheet" href="../assets/css/logo-fix.css"> <!-- LOGO DÜZELTMESİ EKLENDİ -->

    <!-- Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            background: #f4f4f4;
        }

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
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
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
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
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
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            transition: all 0.3s;
        }

        .property-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
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
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
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
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
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
    <header>
        <nav class="navbar">
            <div class="container">
                <div class="logo-area">
                    <a href="../index.php" class="logo-link">
                        <img src="../assets/images/plaza-logo-buyuk.png" alt="Plaza Emlak & Yatırım" class="logo-img">
                    </a>
                    <div class="logo-slogan">
                        <span class="slogan-text">Geleceğinize İyi Bir Yatırım</span>
                    </div>
                </div>
                <ul class="nav-menu">
                    <li><a href="../index.php">Ana Sayfa</a></li>
                    <li><a href="../pages/satilik.php">Satılık</a></li>
                    <li><a href="../pages/kiralik.php">Kiralık</a></li>
                    <li><a href="../pages/hizmetlerimiz.php">Verdiğimiz Hizmetler</a></li>
                    <li><a href="../pages/hakkimizda.php">Hakkımızda</a></li>
                    <li><a href="../pages/iletisim.php">İletişim</a></li>
                    <li><a href="../admin/" class="admin-btn">Yönetim</a></li>
                </ul>
                <div class="mobile-menu-toggle">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
            </div>
        </nav>
    </header>

    <!-- Page Header -->
    <div class="page-header">
        <div class="agent-banner">
            <div class="agent-avatar-large"><?php echo $initials; ?></div>
            <div>
                <h1 style="margin: 0; font-size: 28px;"><?php echo htmlspecialchars($userInfo['full_name']); ?></h1>
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
            <span><?php echo htmlspecialchars($userInfo['full_name']); ?></span>
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
        <?php if (count($properties) > 0): ?>
            <div class="properties-grid">
                <?php foreach ($properties as $property): ?>
                    <div class="property-card">
                        <a href="detail.php?id=<?php echo $property['id']; ?>">
                            <div class="property-img">
                                <?php if ($property['image_path']): ?>
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
                                    <?php if ($property['oda_sayisi']): ?>
                                        <span>🏠 <?php echo $property['oda_sayisi']; ?></span>
                                    <?php endif; ?>
                                    <?php if ($property['brut_metrekare']): ?>
                                        <span>📐 <?php echo $property['brut_metrekare']; ?> m²</span>
                                    <?php endif; ?>
                                    <?php if ($property['bulundugu_kat']): ?>
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
                <h3 style="color: #666;">Bu danışmanın henüz aktif ilanı bulunmuyor</h3>
                <p style="color: #999;">Lütfen daha sonra tekrar kontrol edin.</p>
            </div>
        <?php endif; ?>

        <!-- İletişim Bilgileri -->
        <div class="contact-bar">
            <h3>Hemen İletişime Geçin</h3>
            <p style="color: #666; margin-bottom: 20px;">Aradığınız gayrimenkulü bulmanız için size yardımcı olmaktan mutluluk duyarım.</p>
            <div class="contact-buttons">
                <?php if ($userInfo['phone']): ?>
                    <a href="tel:<?php echo preg_replace('/[^0-9]/', '', $userInfo['phone']); ?>" class="btn-contact btn-phone">
                        📞 <?php echo htmlspecialchars($userInfo['phone']); ?>
                    </a>
                <?php endif; ?>
                <a href="tel:02722220003" class="btn-contact btn-phone">
                    📱 (0272) 222 00 03
                </a>
                <?php
                // WhatsApp için telefon numarasını temizle
                $whatsapp = preg_replace('/[^0-9]/', '', $userInfo['phone'] ?? '');
                if ($whatsapp && substr($whatsapp, 0, 1) == '0') {
                    $whatsapp = '9' . $whatsapp; // Türkiye kodu ekle
                }
                ?>
                <?php if ($whatsapp): ?>
                    <a href="https://wa.me/<?php echo $whatsapp; ?>" target="_blank" class="btn-contact btn-whatsapp">
                        💬 WhatsApp ile Ulaşın
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include '../includes/footer.php'; ?>

    <!-- JavaScript -->
    <script>
        document.querySelector('.mobile-menu-toggle').addEventListener('click', function() {
            this.classList.toggle('active');
            document.querySelector('.nav-menu').classList.toggle('active');
        });
    </script>
    <script src="../assets/js/menu.js"></script>
</body>

</html>