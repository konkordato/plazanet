<?php

// Gzip sıkıştırmayı başlat

if (!ob_start("ob_gzhandler")) ob_start();



// Performans helper'ı dahil et

require_once 'includes/performance.php';



// Cache headers ayarla

PerformanceHelper::setCacheHeaders('dynamic', 3600);

// SEO ve veritabanı bağlantıları

require_once 'config/database.php';

require_once 'includes/SeoHelper.php';

require_once 'includes/analytics.php';



// Türkçe karakter düzeltmesi

header('Content-Type: text/html; charset=utf-8');



// SEO Helper başlat

$seoHelper = new SeoHelper($db);



// Ana sayfa için SEO bilgilerini al

$pageMeta = $seoHelper->getPageMeta('homepage');



// Eğer veritabanında yoksa varsayılan değerler

if (!$pageMeta) {

    $pageMeta = [

        'meta_title' => 'Plaza Emlak & Yatırım - Afyonkarahisar Güvenilir Emlak Danışmanı',

        'meta_description' => 'Afyonkarahisar satılık ve kiralık daire, ev, arsa, işyeri ilanları. Plaza Emlak ile hayalinizdeki mülkü bulun. Güvenilir emlak danışmanlığı hizmetleri. Ahmet Karaman.',

        'meta_keywords' => 'afyon emlak, afyonkarahisar emlak, satılık daire afyon, kiralık daire afyon, plaza emlak, afyon gayrimenkul, satılık ev, kiralık ev, ahmet karaman emlak',

        'og_image' => '/assets/images/plaza-logo-buyuk.png',

        'canonical_url' => 'https://www.plazaemlak.com'

    ];

}



// İlanları çek

$query = "SELECT p.*, 
          (SELECT image_path FROM property_images 
           WHERE property_id = p.id AND is_main = 1 
           ORDER BY id ASC LIMIT 1) as image_path
          FROM properties p 
          WHERE p.durum = 'aktif' 
          ORDER BY p.created_at DESC 
          LIMIT 12";

$stmt = $db->prepare($query);

$stmt->execute();

$properties = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>

<html lang="tr">



<head>

    <meta charset="UTF-8">

    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">



    <!-- SEO Meta Tagları -->

    <title><?php echo htmlspecialchars($pageMeta['meta_title']); ?></title>

    <meta name="description" content="<?php echo htmlspecialchars($pageMeta['meta_description']); ?>">

    <meta name="keywords" content="<?php echo htmlspecialchars($pageMeta['meta_keywords']); ?>">



    <!-- Open Graph / Facebook -->

    <meta property="og:type" content="website">

    <meta property="og:url" content="<?php echo htmlspecialchars($pageMeta['canonical_url']); ?>">

    <meta property="og:title" content="<?php echo htmlspecialchars($pageMeta['meta_title']); ?>">

    <meta property="og:description" content="<?php echo htmlspecialchars($pageMeta['meta_description']); ?>">

    <meta property="og:image" content="https://www.plazaemlak.com<?php echo htmlspecialchars($pageMeta['og_image']); ?>">

    <meta property="og:locale" content="tr_TR">

    <meta property="og:site_name" content="Plaza Emlak & Yatırım">



    <!-- Twitter Card -->

    <meta name="twitter:card" content="summary_large_image">

    <meta name="twitter:url" content="<?php echo htmlspecialchars($pageMeta['canonical_url']); ?>">

    <meta name="twitter:title" content="<?php echo htmlspecialchars($pageMeta['meta_title']); ?>">

    <meta name="twitter:description" content="<?php echo htmlspecialchars($pageMeta['meta_description']); ?>">

    <meta name="twitter:image" content="https://www.plazaemlak.com<?php echo htmlspecialchars($pageMeta['og_image']); ?>">



    <!-- Canonical URL -->

    <link rel="canonical" href="<?php echo htmlspecialchars($pageMeta['canonical_url']); ?>">



    <!-- Diğer SEO Tagları -->

    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">

    <meta name="googlebot" content="index, follow">

    <meta name="author" content="Plaza Emlak - Ahmet Karaman">

    <meta name="publisher" content="Plaza Emlak & Yatırım">

    <meta name="copyright" content="Plaza Emlak & Yatırım">

    <meta name="rating" content="general">

    <meta name="language" content="Turkish">

    <meta name="revisit-after" content="7 days">

    <meta name="distribution" content="global">



    <!-- Favicon -->

    <link rel="icon" type="image/x-icon" href="/favicon.ico">

    <link rel="apple-touch-icon" sizes="180x180" href="/assets/images/apple-touch-icon.png">



    <!-- CSS Dosyaları -->

    <link rel="stylesheet" href="assets/css/style.css">

    <link rel="stylesheet" href="assets/css/override.css">

    <link rel="stylesheet" href="assets/css/logo-fix.css"> <!-- YENİ EKLENEN -->

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">



    <!-- Schema.org Yapılandırılmış Veri - Yerel İşletme -->

    <script type="application/ld+json">

        {

            "@context": "https://schema.org",

            "@type": "RealEstateAgent",

            "name": "Plaza Emlak & Yatırım",

            "alternateName": "Plaza Emlak Afyon",

            "image": "https://www.plazaemlak.com/assets/images/plaza-logo-buyuk.png",

            "logo": "https://www.plazaemlak.com/assets/images/plaza-logo-buyuk.png",

            "url": "https://www.plazaemlak.com",

            "telephone": "+902722220003",

            "priceRange": "₺₺",

            "address": {

                "@type": "PostalAddress",

                "streetAddress": "Güvenevler Mah. Adnan Kahveci Bulvarı",

                "addressLocality": "Merkez",

                "addressRegion": "Afyonkarahisar",

                "postalCode": "03030",

                "addressCountry": "TR"

            },

            "geo": {

                "@type": "GeoCoordinates",

                "latitude": 38.75667,

                "longitude": 30.54333

            },

            "openingHoursSpecification": [{

                "@type": "OpeningHoursSpecification",

                "dayOfWeek": ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"],

                "opens": "09:00",

                "closes": "19:00"

            }],

            "sameAs": [

                "https://www.facebook.com/plazaemlak",

                "https://www.instagram.com/plazaemlak",

                "https://twitter.com/plazaemlak"

            ],

            "founder": {

                "@type": "Person",

                "name": "Ahmet Karaman",

                "jobTitle": "Kurucu & Gayrimenkul Danışmanı"

            },

            "areaServed": {

                "@type": "City",

                "name": "Afyonkarahisar"

            },

            "hasOfferCatalog": {

                "@type": "OfferCatalog",

                "name": "Emlak İlanları",

                "itemListElement": [{

                        "@type": "Offer",

                        "itemOffered": {

                            "@type": "Service",

                            "name": "Satılık Daireler"

                        }

                    },

                    {

                        "@type": "Offer",

                        "itemOffered": {

                            "@type": "Service",

                            "name": "Kiralık Daireler"

                        }

                    },

                    {

                        "@type": "Offer",

                        "itemOffered": {

                            "@type": "Service",

                            "name": "Satılık Arsalar"

                        }

                    }

                ]

            }

        }

    </script>



    <!-- Schema.org BreadcrumbList -->

    <script type="application/ld+json">

        {

            "@context": "https://schema.org",

            "@type": "BreadcrumbList",

            "itemListElement": [{

                "@type": "ListItem",

                "position": 1,

                "name": "Ana Sayfa",

                "item": "https://www.plazaemlak.com"

            }]

        }

    </script>



    <style>

        /* MENÜ DÜZELTMELERİ - YENİ EKLENEN */

        .nav-menu a {

            font-size: 16px !important;

            font-weight: 500 !important;

        }



        .admin-btn {

            font-size: 16px !important;

        }



        /* Ana Sayfa Özel Stilleri */

        .hero {

            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);

            color: white;

            padding: 4rem 0;

            text-align: center;

            position: relative;

        }



        .hero-title {

            font-size: 3rem;

            font-weight: 700;

            margin-bottom: 0.5rem;

            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);

        }



        .hero-subtitle {

            font-size: 1.5rem;

            margin-bottom: 0.5rem;

            opacity: 0.95;

        }



        .features {

            background: #fff;

            padding: 4rem 0;

        }



        .features-grid {

            display: grid;

            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));

            gap: 2rem;

            margin-top: 2rem;

        }



        .feature-card {

            text-align: center;

            padding: 2rem;

            background: #f8f9fa;

            border-radius: 10px;

            transition: transform 0.3s, box-shadow 0.3s;

        }



        .feature-card:hover {

            transform: translateY(-5px);

            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);

        }



        .feature-icon {

            font-size: 3rem;

            margin-bottom: 1rem;

        }



        .property-footer {

            display: flex;

            justify-content: space-between;

            align-items: center;

            margin-top: 1rem;

            padding-top: 1rem;

            border-top: 1px solid #eee;

        }



        .property-view {

            color: #3498db;

            font-weight: 500;

        }



        .no-image {

            height: 200px;

            background: #f5f5f5;

            display: flex;

            flex-direction: column;

            align-items: center;

            justify-content: center;

            color: #999;

        }



        .no-image span {

            font-size: 3rem;

            margin-bottom: 0.5rem;

        }

    </style>

    <?php echo getAnalyticsCode(); ?>

    <!-- Lazy Loading JavaScript -->

    <script src="assets/js/lazy-load.js" defer></script>

</head>



<body>

    <!-- Üst Menü -->

    <header>

        <nav class="navbar">

            <div class="container">

                <div class="logo-area">

                    <a href="index.php" class="logo-link">

                        <img src="assets/images/plaza-logo-buyuk.png" alt="Plaza Emlak & Yatırım" class="logo-img">

                    </a>

                    <!-- SLOGAN BÖLÜMÜ -->

                    <div class="logo-slogan">

                        <span class="slogan-text">Geleceğinize İyi Bir Yatırım</span>

                    </div>

                </div>

                <ul class="nav-menu">

                    <li><a href="index.php" class="active">Ana Sayfa</a></li>

                    <li><a href="pages/satilik.php">Satılık</a></li>

                    <li><a href="pages/kiralik.php">Kiralık</a></li>

                    <li><a href="pages/hizmetlerimiz.php">Verdiğimiz Hizmetler</a></li>

                    <li><a href="pages/hakkimizda.php">Hakkımızda</a></li>

                    <li><a href="pages/iletisim.php">İletişim</a></li>

                    <li><a href="admin/" class="admin-btn">Yönetim</a></li>

                </ul>

                <div class="mobile-menu-toggle">

                    <span></span>

                    <span></span>

                    <span></span>

                </div>

            </div>

        </nav>

    </header>



    <!-- Hero Bölümü -->

    <section class="hero">

        <div class="hero-overlay"></div>

        <div class="hero-content">

            <h1 class="hero-title">Geleceğinize İyi Bir Yatırım</h1>

            <p class="hero-subtitle">PLAZA EMLAK & YATIRIM</p>

            <p class="hero-description">Hayalinizdeki gayrimenkulü bulmanız için profesyonel çözümler</p>



            <!-- Arama Formu -->

            <!-- Bütçe Arama Başlığı -->

            <h2 style="color: white; margin-bottom: 20px; font-size: 24px; text-shadow: 2px 2px 4px rgba(0,0,0,0.2);">

                💰 Bütçenize Göre Arama Yapın

            </h2>

            <div class="search-box">

                <form method="GET" action="search.php">

                    <div class="search-row">

                        <select name="kategori" class="search-select">

                            <option value="">İlan Tipi</option>

                            <option value="Satılık">Satılık</option>

                            <option value="Kiralık">Kiralık</option>

                            <option value="Devren">Devren</option>

                            <option value="Devren Kiralık">Devren Kiralık</option>

                        </select>

                        <select name="emlak_tipi" class="search-select">

                            <option value="">Emlak Tipi</option>

                            <option value="Konut">Konut</option>

                            <option value="İşyeri">İşyeri</option>

                            <option value="Arsa">Arsa</option>

                            <option value="Bina">Bina</option>

                            <option value="Turistik Tesis">Turistik Tesis</option>

                        </select>

                        <input type="text" name="il" placeholder="İl" class="search-input">

                        <input type="text" name="ilce" placeholder="İlçe" class="search-input">

                        <!-- Bütçe Alanları -->

                        <input type="number" name="min_butce" placeholder="Min Bütçe (₺)" class="search-input" style="max-width: 150px;">

                        <input type="number" name="max_butce" placeholder="Max Bütçe (₺)" class="search-input" style="max-width: 150px;">

                        <button type="submit" class="search-btn">

                            <span>ARA</span>

                        </button>

                    </div>

                </form>

            </div>

        </div>

    </section>



    <!-- Özellikler Bölümü -->

    <section class="features">

        <div class="container">

            <div class="features-grid">

                <div class="feature-card">

                    <div class="feature-icon">🏆</div>

                    <h3>Güvenilir Hizmet</h3>

                    <p>20 yıllık tecrübemizle yanınızdayız</p>

                </div>

                <div class="feature-card">

                    <div class="feature-icon">📍</div>

                    <h3>En İyi Lokasyonlar</h3>

                    <p>Şehrin en değerli bölgelerinde</p>

                </div>

                <div class="feature-card">

                    <div class="feature-icon">💎</div>

                    <h3>Kaliteli Portföy</h3>

                    <p>Özenle seçilmiş gayrimenkuller</p>

                </div>

                <div class="feature-card">

                    <div class="feature-icon">🤝</div>

                    <h3>Profesyonel Destek</h3>

                    <p>Alım-satım sürecinde yanınızdayız</p>

                </div>

            </div>

        </div>

    </section>



    <!-- İlanlar Bölümü -->

    <section class="properties">

        <div class="container">

            <div class="section-header">

                <h2 class="section-title">ÖNE ÇIKAN İLANLAR</h2>

                <p class="section-subtitle">En yeni ve özel gayrimenkullerimiz</p>

            </div>



            <div class="property-grid">

                <?php if (count($properties) > 0): ?>

                    <?php foreach ($properties as $property): ?>

                        <div class="property-card">

                            <a href="pages/detail.php?id=<?php echo $property['id']; ?>">

                                <div class="property-image">

                                    <?php if ($property['image_path']): ?>

                                        <img class="lazy"

                                            src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3C/svg%3E"

                                            data-src="<?php echo $property['image_path']; ?>"

                                            alt="<?php echo htmlspecialchars($property['baslik']); ?>"

                                            loading="lazy">

                                    <?php else: ?>

                                        <div class="no-image">

                                            <span>📷</span>

                                            <p>Fotoğraf Bekleniyor</p>

                                        </div>

                                    <?php endif; ?>

                                    <span class="property-badge <?php echo $property['kategori'] == 'Satılık' ? 'sale' : 'rent'; ?>">

                                        <?php echo $property['kategori']; ?>

                                    </span>

                                </div>

                                <div class="property-info">

                                    <h3><?php echo htmlspecialchars($property['baslik']); ?></h3>

                                    <p class="property-location">

                                        📍 <?php echo $property['ilce'] . ', ' . $property['il']; ?>

                                    </p>

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

                                    <div class="property-footer">

                                        <div class="property-price">

                                            <?php echo number_format($property['fiyat'], 0, ',', '.'); ?> ₺

                                        </div>

                                        <div class="property-view">

                                            Detayları Gör →

                                        </div>

                                    </div>

                                </div>

                            </a>

                        </div>

                    <?php endforeach; ?>

                <?php else: ?>

                    <div class="no-results">

                        <p style="font-size: 1.2rem; margin-bottom: 10px;">📢 Henüz ilan eklenmemiş.</p>

                        <p style="color: #6B7280;">Admin panelinden ilk ilanınızı ekleyebilirsiniz.</p>

                    </div>

                <?php endif; ?>

            </div>

        </div>

    </section>



    <!-- Footer -->

    <?php include 'includes/footer.php'; ?>



    <script src="assets/js/main.js"></script>

    <!-- Mobil Menü JavaScript -->

    <script>

        document.querySelector('.mobile-menu-toggle').addEventListener('click', function() {

            this.classList.toggle('active');

            document.querySelector('.nav-menu').classList.toggle('active');

        });

    </script>

    <script src="assets/js/menu.js"></script>

</body>



</html>