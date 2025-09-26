<?php
require_once '../config/database.php';
require_once '../includes/SeoHelper.php';
require_once '../includes/analytics.php';

$id = $_GET['id'] ?? 0;
if (!$id) {
    header("Location: ../index.php");
    exit();
}

// İlan bilgilerini çek
$stmt = $db->prepare("SELECT * FROM properties WHERE id = :id AND durum = 'aktif'");
$stmt->execute([':id' => $id]);
$property = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$property) {
    header("Location: ../index.php");
    exit();
}

// SEO Helper başlat
$seoHelper = new SeoHelper($db);

// İlan için SEO meta bilgilerini oluştur
$propertyMeta = $seoHelper->generatePropertyMeta($id);

// SEO meta bilgileri yoksa manuel oluştur
if (!$propertyMeta || !isset($propertyMeta['meta_title'])) {
    // Türkçe karakterleri düzelt
    $turkce = ['ş', 'Ş', 'ı', 'İ', 'ğ', 'Ğ', 'ü', 'Ü', 'ö', 'Ö', 'ç', 'Ç'];
    $english = ['s', 's', 'i', 'i', 'g', 'g', 'u', 'u', 'o', 'o', 'c', 'c'];

    // SEO dostu URL slug oluştur
    $slug_text = $property['kategori'] . "-" . $property['emlak_tipi'] . "-" . $property['ilce'] . "-" . $property['id'];
    $slug = str_replace($turkce, $english, $slug_text);
    $slug = strtolower($slug);
    $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    $slug = trim($slug, '-');

    // Meta bilgileri oluştur
    $meta_title = $property['kategori'] . " " . $property['emlak_tipi'] . " - " .
        $property['ilce'] . ($property['mahalle'] ? ", " . $property['mahalle'] : "") .
        " - " . number_format($property['fiyat'], 0, ',', '.') . " TL - Plaza Emlak";

    $meta_description = $property['ilce'] . ($property['mahalle'] ? " " . $property['mahalle'] : "") .
        " bölgesinde " . $property['kategori'] . " " .
        ($property['brut_metrekare'] ? $property['brut_metrekare'] . "m² " : "") .
        ($property['oda_sayisi'] ? $property['oda_sayisi'] . " " : "") .
        $property['emlak_tipi'] . ". Fiyat: " .
        number_format($property['fiyat'], 0, ',', '.') . " TL. " .
        mb_substr(strip_tags($property['aciklama']), 0, 100) . "...";

    $meta_keywords = $property['il'] . " " . $property['kategori'] . ", " .
        $property['ilce'] . " " . $property['kategori'] . " " . $property['emlak_tipi'] . ", " .
        ($property['mahalle'] ? $property['mahalle'] . " emlak, " : "") .
        ($property['oda_sayisi'] ? $property['oda_sayisi'] . " daire, " : "") .
        "plaza emlak " . $property['ilce'];

    $propertyMeta = [
        'meta_title' => mb_substr($meta_title, 0, 160),
        'meta_description' => mb_substr($meta_description, 0, 300),
        'meta_keywords' => $meta_keywords,
        'slug' => $slug,
        'canonical_url' => 'https://www.plazaemlak.com/ilan/' . $slug
    ];
}

// Resimleri çek
$stmt = $db->prepare("SELECT * FROM property_images WHERE property_id = :id ORDER BY is_main DESC, id");
$stmt->execute([':id' => $id]);
$images = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ana resim
$mainImage = $images[0]['image_path'] ?? 'assets/images/no-image.jpg';
$ogImage = 'https://www.plazaemlak.com/' . $mainImage;

// İlanı ekleyen KULLANICI bilgilerini çek (users tablosundan)
$userInfo = null;
if ($property['ekleyen_admin_id']) {  // Bu alan aslında user_id tutuyor
    $stmt = $db->prepare("SELECT * FROM users WHERE id = :user_id");
    $stmt->execute([':user_id' => $property['ekleyen_admin_id']]);
    $userInfo = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Eğer kullanıcı bilgisi yoksa varsayılan değerler kullan
if (!$userInfo || empty($userInfo['username'])) {
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
    if (empty($userInfo['full_name'])) {
        $userInfo['full_name'] = $userInfo['username'];
    }
    // Eksik alanları doldur
    if (empty($userInfo['phone'])) $userInfo['phone'] = '0272 222 00 03';
    if (empty($userInfo['mobile'])) $userInfo['mobile'] = '0552 653 03 03';
    if (empty($userInfo['company'])) $userInfo['company'] = 'Plaza Emlak & Yatırım';
    if (empty($userInfo['title'])) $userInfo['title'] = 'Gayrimenkul Danışmanı';
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

<>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- SEO Meta Tagları -->
    <title><?php echo htmlspecialchars($propertyMeta['meta_title']); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($propertyMeta['meta_description']); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($propertyMeta['meta_keywords']); ?>">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo htmlspecialchars($propertyMeta['canonical_url']); ?>">
    <meta property="og:title" content="<?php echo htmlspecialchars($propertyMeta['meta_title']); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($propertyMeta['meta_description']); ?>">
    <meta property="og:image" content="<?php echo htmlspecialchars($ogImage); ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:locale" content="tr_TR">
    <meta property="og:site_name" content="Plaza Emlak & Yatırım">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="<?php echo htmlspecialchars($propertyMeta['canonical_url']); ?>">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($propertyMeta['meta_title']); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($propertyMeta['meta_description']); ?>">
    <meta name="twitter:image" content="<?php echo htmlspecialchars($ogImage); ?>">

    <!-- Canonical URL -->
    <link rel="canonical" href="<?php echo htmlspecialchars($propertyMeta['canonical_url']); ?>">

    <!-- Diğer SEO Tagları -->
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
    <meta name="googlebot" content="index, follow">
    <meta name="author" content="<?php echo htmlspecialchars($userInfo['full_name']); ?> - Plaza Emlak">
    <meta name="publisher" content="Plaza Emlak & Yatırım">

    <!-- Schema.org Yapılandırılmış Veri - Emlak İlanı -->
    <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "RealEstateListing",
            "name": "<?php echo htmlspecialchars($property['baslik']); ?>",
            "description": "<?php echo htmlspecialchars(mb_substr($property['aciklama'], 0, 500)); ?>",
            "url": "<?php echo htmlspecialchars($propertyMeta['canonical_url']); ?>",
            "datePosted": "<?php echo $property['created_at']; ?>",
            "price": "<?php echo $property['fiyat']; ?>",
            "priceCurrency": "TRY",
            "image": [
                <?php
                $imageUrls = [];
                foreach ($images as $img) {
                    $imageUrls[] = '"https://www.plazaemlak.com/' . $img['image_path'] . '"';
                }
                echo implode(',', array_slice($imageUrls, 0, 10));
                ?>
            ],
            "address": {
                "@type": "PostalAddress",
                "streetAddress": "<?php echo htmlspecialchars($property['mahalle'] ?? ''); ?>",
                "addressLocality": "<?php echo htmlspecialchars($property['ilce']); ?>",
                "addressRegion": "<?php echo htmlspecialchars($property['il']); ?>",
                "addressCountry": "TR"
            },
            "numberOfRooms": "<?php echo $property['oda_sayisi'] ?? ''; ?>",
            "floorSize": {
                "@type": "QuantitativeValue",
                "value": <?php echo $property['brut_metrekare'] ?? 0; ?>,
                "unitCode": "MTK"
            },
            "seller": {
                "@type": "RealEstateAgent",
                "name": "<?php echo htmlspecialchars($userInfo['full_name']); ?>",
                "telephone": "<?php echo htmlspecialchars($userInfo['mobile']); ?>",
                "worksFor": {
                    "@type": "RealEstateAgency",
                    "name": "Plaza Emlak & Yatırım",
                    "url": "https://www.plazaemlak.com",
                    "telephone": "+902722220003"
                }
            }
            <?php if ($property['emlak_tipi'] == 'konut' || $property['emlak_tipi'] == 'Konut'): ?>,
                "accommodationCategory": "<?php echo $property['konut_tipi'] ?? 'Daire'; ?>",
                "numberOfBathroomsTotal": <?php echo $property['banyo_sayisi'] ?? 1; ?>,
                "floorLevel": "<?php echo $property['bulundugu_kat'] ?? ''; ?>"
            <?php endif; ?>
        }
    </script>

    <!-- BreadcrumbList Schema -->
    <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "BreadcrumbList",
            "itemListElement": [{
                    "@type": "ListItem",
                    "position": 1,
                    "name": "Ana Sayfa",
                    "item": "https://www.plazaemlak.com"
                },
                {
                    "@type": "ListItem",
                    "position": 2,
                    "name": "<?php echo htmlspecialchars($property['kategori']); ?>",
                    "item": "https://www.plazaemlak.com/<?php echo strtolower($property['kategori']); ?>"
                },
                {
                    "@type": "ListItem",
                    "position": 3,
                    "name": "<?php echo htmlspecialchars($property['ilce']); ?>",
                    "item": "https://www.plazaemlak.com/<?php echo strtolower($property['kategori']); ?>/<?php echo strtolower($property['ilce']); ?>"
                },
                {
                    "@type": "ListItem",
                    "position": 4,
                    "name": "<?php echo htmlspecialchars($property['baslik']); ?>",
                    "item": "<?php echo htmlspecialchars($propertyMeta['canonical_url']); ?>"
                }
            ]
        }
    </script>

    <!-- CSS Dosyaları -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/sticky-menu.css">

    <style>
        .detail-page-custom .detail-container {
            max-width: 1400px !important;
        }

        .detail-page-custom .detail-grid {
            display: grid !important;
            grid-template-columns: 48% 30% 20% !important;
            gap: 1% !important;
        }

        .detail-page-custom .detail-gallery {
            position: relative;
        }

        .detail-page-custom .detail-gallery img.gallery-main {
            width: 100% !important;
            height: 480px !important;
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

        .detail-page-custom .detail-info .detail-table {
            width: 100% !important;
        }

        .detail-page-custom .detail-table td {
            padding: 7px 8px !important;
            font-size: 13px !important;
        }

        .detail-page-custom .detail-agent {
            padding: 15px !important;
        }

        .detail-page-custom .agent-phone {
            font-size: 14px !important;
            padding: 10px 12px !important;
        }

        @media (max-width: 768px) {
            .detail-page-custom .detail-grid {
                grid-template-columns: 1fr !important;
            }

            .detail-page-custom .detail-gallery img.gallery-main {
                height: 300px !important;
            }
        }

        /* SEKME SİSTEMİ */
        .tabs-container {
            margin-top: 30px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .tab-buttons {
            display: flex;
            border-bottom: 2px solid #e0e0e0;
            padding: 0;
        }

        .tab-button {
            flex: 1;
            max-width: 200px;
            padding: 15px 20px;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 16px;
            color: #666;
            transition: all 0.3s;
            border-bottom: 3px solid transparent;
            margin-bottom: -2px;
        }

        .tab-button:hover {
            background: #f5f5f5;
        }

        .tab-button.active {
            color: #3498db;
            border-bottom: 3px solid #3498db;
            background: #f8f9fa;
            font-weight: 600;
        }

        .tab-content {
            padding: 20px;
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        /* HARİTA STİLLERİ */
        #map {
            width: 100%;
            height: 450px;
            border-radius: 8px;
            margin-top: 15px;
        }

        .map-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .map-address {
            font-size: 16px;
            color: #333;
            margin-bottom: 10px;
        }

        .get-directions-btn {
            display: inline-block;
            padding: 10px 20px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 14px;
            transition: background 0.3s;
        }

        .get-directions-btn:hover {
            background: #2980b9;
        }

        /* Lightbox animasyonu */
        #modalImage {
            animation: zoom 0.6s;
        }

        @keyframes zoom {
            from {
                transform: scale(0.5)
            }

            to {
                transform: scale(1)
            }
        }

        #imageModal {
            animation: fadeIn 0.3s;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        .gallery-main:hover {
            opacity: 0.9;
            transform: scale(1.02);
            transition: all 0.3s;
        }

        .gallery-thumb:hover {
            border-color: #3498db !important;
            transform: scale(1.05);
            transition: all 0.3s;
        }
    </style>
    <?php
    echo getAnalyticsCode();
    echo trackPropertyView(
        $property['id'],
        htmlspecialchars($property['baslik']),
        $property['fiyat'],
        $property['kategori']
    );
    ?>

    </head>

    <body class="detail-page-custom" style="background: #f4f4f4;">
        <!-- HEADER BÖLÜMÜ -->
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
                    <?php if (count($images) > 0): ?>
                        <img id="mainImage" src="../<?php echo $images[0]['image_path']; ?>" class="gallery-main" alt="<?php echo htmlspecialchars($property['baslik']); ?>">
                        <span style="position:absolute;bottom:15px;right:15px;background:rgba(0,0,0,0.7);color:white;padding:5px 10px;border-radius:4px;">
                            <span id="currentImg">1</span> / <?php echo count($images); ?>
                        </span>
                        <div class="gallery-thumbs">
                            <?php foreach ($images as $index => $img): ?>
                                <img src="../<?php echo $img['image_path']; ?>"
                                    class="gallery-thumb <?php echo $index == 0 ? 'active' : ''; ?>"
                                    onclick="changeImage(this, <?php echo $index + 1; ?>)"
                                    alt="<?php echo htmlspecialchars($property['baslik']); ?> - Resim <?php echo $index + 1; ?>">
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
                    <?php if ($m2_fiyat > 0): ?>
                        <div class="detail-price-m2">m² fiyatı: <?php echo number_format($m2_fiyat, 0, ',', '.'); ?> TL</div>
                    <?php endif; ?>

                    <table class="detail-table">
                        <tr>
                            <td>İlan No</td>
                            <td><?php echo $property['ilan_no']; ?></td>
                        </tr>
                        <tr>
                            <td>İlan Tarihi</td>
                            <td><?php echo date('d.m.Y', strtotime($property['ilan_tarihi'])); ?></td>
                        </tr>
                        <tr>
                            <td>Emlak Tipi</td>
                            <td><?php echo $property['emlak_tipi']; ?></td>
                        </tr>
                        <tr>
                            <td>Kategori</td>
                            <td><?php echo $property['kategori']; ?></td>
                        </tr>

                        <?php if ($property['emlak_tipi'] == 'arsa' || $property['emlak_tipi'] == 'Arsa'): ?>
                            <!-- ARSA BİLGİLERİ -->
                            <?php if (isset($property['imar_durumu']) && $property['imar_durumu']): ?>
                                <tr>
                                    <td>İmar Durumu</td>
                                    <td><?php echo $property['imar_durumu']; ?></td>
                                </tr>
                            <?php endif; ?>

                            <?php if (isset($property['ada_no']) && $property['ada_no']): ?>
                                <tr>
                                    <td>Ada No</td>
                                    <td><?php echo $property['ada_no']; ?></td>
                                </tr>
                            <?php endif; ?>

                            <?php if (isset($property['parsel_no']) && $property['parsel_no']): ?>
                                <tr>
                                    <td>Parsel No</td>
                                    <td><?php echo $property['parsel_no']; ?></td>
                                </tr>
                            <?php endif; ?>

                            <?php if (isset($property['pafta_no']) && $property['pafta_no']): ?>
                                <tr>
                                    <td>Pafta No</td>
                                    <td><?php echo $property['pafta_no']; ?></td>
                                </tr>
                            <?php endif; ?>

                            <?php if (isset($property['kaks']) && $property['kaks']): ?>
                                <tr>
                                    <td>Kaks (Emsal)</td>
                                    <td><?php echo $property['kaks']; ?></td>
                                </tr>
                            <?php endif; ?>

                            <?php if (isset($property['gabari']) && $property['gabari']): ?>
                                <tr>
                                    <td>Gabari</td>
                                    <td><?php echo $property['gabari']; ?></td>
                                </tr>
                            <?php endif; ?>

                            <?php if (isset($property['tapu_durumu']) && $property['tapu_durumu']): ?>
                                <tr>
                                    <td>Tapu Durumu</td>
                                    <td><?php echo $property['tapu_durumu']; ?></td>
                                </tr>
                            <?php endif; ?>
                        <?php else: ?>
                            <!-- KONUT/İŞYERİ BİLGİLERİ -->
                            <?php if ($property['oda_sayisi']): ?>
                                <tr>
                                    <td>Oda Sayısı</td>
                                    <td><?php echo $property['oda_sayisi']; ?></td>
                                </tr>
                            <?php endif; ?>
                            <?php if ($property['bina_yasi']): ?>
                                <tr>
                                    <td>Bina Yaşı</td>
                                    <td><?php echo $property['bina_yasi']; ?></td>
                                </tr>
                            <?php endif; ?>
                            <?php if ($property['bulundugu_kat']): ?>
                                <tr>
                                    <td>Bulunduğu Kat</td>
                                    <td><?php echo $property['bulundugu_kat']; ?></td>
                                </tr>
                            <?php endif; ?>
                            <?php if ($property['kat_sayisi']): ?>
                                <tr>
                                    <td>Kat Sayısı</td>
                                    <td><?php echo $property['kat_sayisi']; ?></td>
                                </tr>
                            <?php endif; ?>
                            <?php if ($property['isitma']): ?>
                                <tr>
                                    <td>Isıtma</td>
                                    <td><?php echo $property['isitma']; ?></td>
                                </tr>
                            <?php endif; ?>
                        <?php endif; ?>

                        <tr>
                            <td>Brüt m²</td>
                            <td><?php echo $property['brut_metrekare']; ?> m²</td>
                        </tr>
                        <?php if ($property['net_metrekare']): ?>
                            <tr>
                                <td>Net m²</td>
                                <td><?php echo $property['net_metrekare']; ?> m²</td>
                            </tr>
                        <?php endif; ?>
                        <tr>
                            <td>Kimden</td>
                            <td><?php echo $property['kimden'] ?? 'Emlak Ofisinden'; ?></td>
                        </tr>
                        <?php if ($property['takas']): ?>
                            <tr>
                                <td>Takas</td>
                                <td><?php echo $property['takas']; ?></td>
                            </tr>
                        <?php endif; ?>
                    </table>
                </div>

                <!-- SAĞ: Danışman -->
                <div class="detail-agent">
                    <div class="agent-header">
                        <?php if (!empty($userInfo['profile_image']) && file_exists('../' . $userInfo['profile_image'])): ?>
                            <img src="../<?php echo htmlspecialchars($userInfo['profile_image']); ?>"
                                alt="<?php echo htmlspecialchars($userInfo['full_name'] ?? $userInfo['username']); ?>"
                                style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover; border: 2px solid #3498db;">
                        <?php else: ?>
                            <div class="agent-avatar">
                                <?php
                                $displayName = $userInfo['full_name'] ?? $userInfo['username'];
                                $nameParts = explode(' ', $displayName);
                                $initials = '';
                                foreach ($nameParts as $part) {
                                    $initials .= mb_strtoupper(mb_substr($part, 0, 1, 'UTF-8'), 'UTF-8');
                                }
                                echo substr($initials, 0, 2);
                                ?>
                            </div>
                        <?php endif; ?>

                        <div class="agent-info">
                            <h3><?php echo htmlspecialchars($userInfo['full_name'] ?? $userInfo['username']); ?></h3>
                            <p><?php echo htmlspecialchars($userInfo['company'] ?? 'Plaza Emlak & Yatırım'); ?></p>
                        </div>
                    </div>

                    <?php if (!empty($userInfo['phone'])): ?>
                        <a href="tel:02722220003" class="agent-phone">
                            📞 0272 222 00 03
                        </a>
                    <?php endif; ?>

                    <?php
                    $mobileNumber = !empty($userInfo['mobile']) ? $userInfo['mobile'] : (!empty($userInfo['phone']) ? $userInfo['phone'] : '0552 653 03 03');
                    ?>
                    <a href="tel:<?php echo preg_replace('/[^0-9]/', '', $mobileNumber); ?>" class="agent-phone">
                        📱 <?php echo htmlspecialchars($mobileNumber); ?>
                    </a>

                    <button class="agent-message" onclick="sendMessage('<?php echo $userInfo['full_name'] ?? $userInfo['username']; ?>', '<?php echo $property['ilan_no']; ?>')">
                        💬 Mesaj Gönder
                    </button>

                    <div style="margin-top:20px;padding-top:20px;border-top:1px solid #e5e5e5;">
                        <p style="font-size:13px;color:#666;margin-bottom:10px;">
                            📍 <?php echo $property['mahalle'] ? $property['mahalle'] . ', ' : ''; ?>
                            <?php echo $property['ilce']; ?> / <?php echo $property['il']; ?>
                        </p>

                        <?php if ($digerIlanSayisi > 0): ?>
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
                            💰 Bütçeye Göre Arama
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
            </div>

            <!-- SEKME SİSTEMİ - AÇIKLAMA VE HARİTA -->
            <div class="tabs-container">
                <div class="tab-buttons">
                    <button class="tab-button active" onclick="openTab(event, 'aciklama-tab')">
                        İlan Detayları
                    </button>
                    <button class="tab-button" onclick="openTab(event, 'konum-tab')">
                        Konumu ve Sokak Görünümü
                    </button>
                </div>

                <!-- AÇIKLAMA SEKMESİ -->
                <div id="aciklama-tab" class="tab-content active">
                    <h2>Açıklama</h2>
                    <p><?php echo nl2br(htmlspecialchars($property['aciklama'])); ?></p>
                </div>

                <!-- KONUM SEKMESİ -->
                <div id="konum-tab" class="tab-content">
                    <h2>Konum Bilgileri</h2>
                    <div class="map-info">
                        <div class="map-address">
                            📍 <?php
                                echo $property['mahalle'] ? $property['mahalle'] . ', ' : '';
                                echo $property['ilce'] . ' / ' . $property['il'];
                                ?>
                        </div>
                        <?php
                        $fullAddress = '';
                        if ($property['mahalle']) $fullAddress .= $property['mahalle'] . ', ';
                        $fullAddress .= $property['ilce'] . ', ' . $property['il'] . ', Türkiye';
                        ?>
                        <a href="https://www.google.com/maps/search/?api=1&query=<?php echo urlencode($fullAddress); ?>"
                            target="_blank"
                            class="get-directions-btn">
                            🚗 Nasıl Giderim?
                        </a>
                    </div>
                    <div id="map" data-address="<?php echo htmlspecialchars($fullAddress); ?>"></div>
                </div>
            </div>

            <!-- BENZER İLANLAR -->
            <?php if (count($similarProperties) > 0): ?>
                <div class="detail-description" style="margin-top:20px;">
                    <h2>📍 Benzer İlanlar</h2>
                    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:15px;margin-top:20px;">
                        <?php foreach ($similarProperties as $item): ?>
                            <a href="detail.php?id=<?php echo $item['id']; ?>" style="text-decoration:none;color:inherit;">
                                <div style="background:white;border-radius:8px;overflow:hidden;box-shadow:0 2px 5px rgba(0,0,0,0.1);">
                                    <?php if ($item['image_path']): ?>
                                        <img src="../<?php echo $item['image_path']; ?>" style="width:100%;height:150px;object-fit:cover;" alt="<?php echo htmlspecialchars($item['baslik']); ?>">
                                    <?php else: ?>
                                        <div style="width:100%;height:150px;background:#f5f5f5;display:flex;align-items:center;justify-content:center;">📷</div>
                                    <?php endif; ?>
                                    <div style="padding:15px;">
                                        <h4 style="font-size:14px;margin-bottom:10px;"><?php echo htmlspecialchars(substr($item['baslik'], 0, 50)); ?>...</h4>
                                        <p style="font-size:12px;color:#666;">📍 <?php echo $item['ilce']; ?></p>
                                        <div style="font-size:18px;color:#ff6000;font-weight:bold;"><?php echo number_format($item['fiyat'], 0, ',', '.'); ?> TL</div>
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
                    <?php foreach ($popularProperties as $item): ?>
                        <a href="detail.php?id=<?php echo $item['id']; ?>" style="text-decoration:none;color:inherit;">
                            <div style="background:white;border-radius:8px;overflow:hidden;box-shadow:0 2px 5px rgba(0,0,0,0.1);">
                                <?php if ($item['image_path']): ?>
                                    <img src="../<?php echo $item['image_path']; ?>" style="width:100%;height:150px;object-fit:cover;" alt="<?php echo htmlspecialchars($item['baslik']); ?>">
                                <?php else: ?>
                                    <div style="width:100%;height:150px;background:#f5f5f5;display:flex;align-items:center;justify-content:center;">🏠</div>
                                <?php endif; ?>
                                <div style="padding:15px;">
                                    <h4 style="font-size:14px;margin-bottom:10px;"><?php echo htmlspecialchars(substr($item['baslik'], 0, 50)); ?>...</h4>
                                    <p style="font-size:12px;color:#666;">📍 <?php echo $item['ilce'] . ', ' . $item['il']; ?></p>
                                    <div style="font-size:18px;color:#ff6000;font-weight:bold;"><?php echo number_format($item['fiyat'], 0, ',', '.'); ?> TL</div>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <script>
            // Galeri fonksiyonları
            function changeImage(thumb, num) {
                document.getElementById('mainImage').src = thumb.src;
                document.getElementById('currentImg').textContent = num;
                document.querySelectorAll('.gallery-thumb').forEach(t => t.classList.remove('active'));
                thumb.classList.add('active');
            }

            function sendMessage(agentName, propertyNo) {
                // Analytics eventi gönder
                if (typeof gtag !== 'undefined') {
                    gtag('event', 'whatsapp_click', {
                        event_category: 'engagement',
                        event_label: 'property_inquiry',
                        property_id: propertyNo
                    });
                }

                // Mevcut WhatsApp kodu
                var message = prompt('Mesajınızı yazın:');
                if (message && message.trim() !== '') {
                    var whatsappNumber = '905526530303';
                    var whatsappMessage = 'Merhaba ' + agentName + ', ' + propertyNo + ' nolu ilan hakkında bilgi almak istiyorum. Mesajım: ' + message;
                    window.open('https://wa.me/' + whatsappNumber + '?text=' + encodeURIComponent(whatsappMessage), '_blank');
                }
            }

            // SEKME FONKSİYONU
            function openTab(evt, tabName) {
                var i, tabcontent, tabbuttons;

                // Tüm sekme içeriklerini gizle
                tabcontent = document.getElementsByClassName("tab-content");
                for (i = 0; i < tabcontent.length; i++) {
                    tabcontent[i].classList.remove("active");
                }

                // Tüm sekme butonlarından active sınıfını kaldır
                tabbuttons = document.getElementsByClassName("tab-button");
                for (i = 0; i < tabbuttons.length; i++) {
                    tabbuttons[i].classList.remove("active");
                }

                // Seçili sekmeyi göster ve butonu aktif yap
                document.getElementById(tabName).classList.add("active");
                evt.currentTarget.classList.add("active");

                // Harita sekmesi açıldığında haritayı başlat
                if (tabName === 'konum-tab' && typeof google !== 'undefined') {
                    setTimeout(function() {
                        initPropertyMap();
                    }, 300);
                }
            }

            // Harita başlatma fonksiyonu
            function initPropertyMap() {
                var mapElement = document.getElementById('map');
                if (!mapElement || typeof google === 'undefined') {
                    console.log('Harita elementi bulunamadı veya Google Maps yüklenmedi');
                    return;
                }

                // Adresi data attribute'tan al
                var address = mapElement.getAttribute('data-address');
                if (!address) {
                    address = 'Afyonkarahisar, Türkiye';
                }

                console.log('Harita için adres:', address);

                // Geocoding ile adresi koordinatlara çevir
                var geocoder = new google.maps.Geocoder();
                geocoder.geocode({
                    'address': address
                }, function(results, status) {
                    if (status === 'OK') {
                        // Harita oluştur
                        var map = new google.maps.Map(mapElement, {
                            center: results[0].geometry.location,
                            zoom: 15,
                            mapTypeControl: true,
                            streetViewControl: true,
                            fullscreenControl: true,
                            zoomControl: true,
                            scaleControl: true
                        });

                        // Marker ekle
                        var marker = new google.maps.Marker({
                            position: results[0].geometry.location,
                            map: map,
                            title: address,
                            animation: google.maps.Animation.DROP
                        });

                        // Bilgi penceresi
                        var infoWindow = new google.maps.InfoWindow({
                            content: '<div style="padding:10px;"><strong>📍 Konum</strong><br>' + address + '</div>'
                        });

                        // Marker'a tıklandığında bilgi penceresi aç
                        marker.addListener('click', function() {
                            infoWindow.open(map, marker);
                        });

                        // Harita yüklendiğinde marker'ı göster
                        setTimeout(function() {
                            infoWindow.open(map, marker);
                        }, 1000);

                    } else {
                        // Hata durumunda mesaj göster
                        console.error('Geocode hatası:', status);
                        mapElement.innerHTML = '<div style="text-align:center;padding:50px;color:#666;">' +
                            '<h3>⚠️ Harita Yüklenemedi</h3>' +
                            '<p>Adres: ' + address + '</p>' +
                            '<p style="font-size:12px;color:#999;">Hata: ' + status + '</p>' +
                            '</div>';
                    }
                });
            }

            // Google Maps yüklendiğinde çalışacak
            function initMap() {
                console.log('Google Maps API yüklendi');
                // Eğer harita sekmesi açıksa haritayı başlat
                var konumTab = document.getElementById('konum-tab');
                if (konumTab && konumTab.classList.contains('active')) {
                    initPropertyMap();
                }
            }

            // LIGHTBOX SİSTEMİ
            document.addEventListener('DOMContentLoaded', function() {
                // Lightbox HTML'i oluştur
                const lightboxHTML = `
                <div id="imageModal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background-color:rgba(0,0,0,0.95);">
                    <span onclick="closeModal()" style="position:absolute; top:20px; right:40px; color:#fff; font-size:40px; font-weight:bold; cursor:pointer;">&times;</span>
                    <img id="modalImage" style="margin:auto; display:block; max-width:90%; max-height:90%; margin-top:50px;">
                    <div id="caption" style="margin:auto; display:block; width:80%; max-width:700px; text-align:center; color:#ccc; padding:10px 0;"></div>
                </div>
            `;
                document.body.insertAdjacentHTML('beforeend', lightboxHTML);

                // Ana resme tıklama eventi
                const mainImg = document.getElementById('mainImage');
                if (mainImg) {
                    mainImg.style.cursor = 'pointer';
                    mainImg.onclick = function() {
                        document.getElementById('imageModal').style.display = 'block';
                        document.getElementById('modalImage').src = this.src;
                    }
                }
            });

            // Modal kapatma
            function closeModal() {
                document.getElementById('imageModal').style.display = 'none';
            }

            // ESC tuşu ile kapatma
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeModal();
                }
            });
        </script>

        <!-- Sticky Menu JavaScript -->
        <script src="../assets/js/sticky-menu.js"></script>

        <!-- Google Maps API - TEK SEFER YÜKLE -->
        <script async defer
            src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAEfetSi8hgru3jatZYeS5WaLjUD_lMED4&callback=initMap&language=tr&region=TR">
        </script>

    </body>

</html>