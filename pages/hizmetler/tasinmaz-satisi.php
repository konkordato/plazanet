<?php
// pages/hizmetler/tasinmaz-satisi.php - Taşınmaz Satışı Hizmet Detay Sayfası
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Taşınmaz Satışı Hizmeti - Plaza Emlak & Yatırım</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/logo-fix.css">
    <link rel="stylesheet" href="../../assets/css/pages.css">
    <link rel="stylesheet" href="../../assets/css/override.css">
    <style>
        .service-detail-hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 100px 0 60px;
            color: white;
        }
        
        .service-detail-hero h1 {
            font-size: 2.5rem;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }
        
        .breadcrumb {
            background: rgba(255,255,255,0.1);
            padding: 10px 20px;
            border-radius: 25px;
            display: inline-block;
            margin-bottom: 20px;
        }
        
        .breadcrumb a {
            color: white;
            text-decoration: none;
            opacity: 0.8;
        }
        
        .breadcrumb a:hover {
            opacity: 1;
        }
        
        .service-detail-content {
            padding: 60px 0;
            background: white;
        }
        
        .content-wrapper {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 40px;
            align-items: start;
        }
        
        .main-content {
            background: #f8f9fa;
            padding: 40px;
            border-radius: 15px;
        }
        
        .main-content h2 {
            color: #2c3e50;
            margin-bottom: 20px;
            font-size: 1.8rem;
        }
        
        .main-content p {
            color: #666;
            line-height: 1.8;
            margin-bottom: 20px;
            font-size: 1.1rem;
        }
        
        .feature-list {
            list-style: none;
            padding: 0;
            margin: 30px 0;
        }
        
        .feature-list li {
            padding: 15px;
            margin-bottom: 15px;
            background: white;
            border-radius: 10px;
            display: flex;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .feature-list li:before {
            content: "✓";
            color: #27ae60;
            font-weight: bold;
            font-size: 1.5rem;
            margin-right: 15px;
        }
        
        .sidebar {
            position: sticky;
            top: 100px;
        }
        
        .contact-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 30px;
            border-radius: 15px;
            color: white;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .contact-card h3 {
            margin-bottom: 20px;
            font-size: 1.5rem;
        }
        
        .contact-card p {
            margin-bottom: 25px;
            opacity: 0.95;
        }
        
        .contact-btn {
            display: inline-block;
            background: white;
            color: #667eea;
            padding: 12px 30px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .contact-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .other-services {
            margin-top: 30px;
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .other-services h4 {
            color: #2c3e50;
            margin-bottom: 20px;
        }
        
        .service-links {
            list-style: none;
            padding: 0;
        }
        
        .service-links li {
            margin-bottom: 10px;
        }
        
        .service-links a {
            color: #3498db;
            text-decoration: none;
            display: flex;
            align-items: center;
            padding: 8px 0;
            transition: all 0.3s;
        }
        
        .service-links a:hover {
            color: #2980b9;
            padding-left: 10px;
        }
        
        @media (max-width: 768px) {
            .content-wrapper {
                grid-template-columns: 1fr;
            }
            
            .service-detail-hero h1 {
                font-size: 1.8rem;
            }
            
            .main-content {
                padding: 25px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <nav class="navbar">
            <div class="container">
                <div class="logo-area">
                    <a href="../../index.php" class="logo-link">
                        <img src="../../assets/images/plaza-logo-buyuk.png" alt="Plaza Emlak & Yatırım" class="logo-img">
                    </a>
                    <div class="logo-slogan">
                        <span class="slogan-text">Geleceğinize İyi Bir Yatırım</span>
                    </div>
                </div>
                <ul class="nav-menu">
                    <li><a href="../../index.php">Ana Sayfa</a></li>
                    <li><a href="../satilik.php">Satılık</a></li>
                    <li><a href="../kiralik.php">Kiralık</a></li>
                    <li><a href="../hizmetlerimiz.php" class="active">Verdiğimiz Hizmetler</a></li>
                    <li><a href="../hakkimizda.php">Hakkımızda</a></li>
                    <li><a href="../iletisim.php">İletişim</a></li>
                    <li><a href="../../admin/" class="admin-btn">Yönetim</a></li>
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
    <section class="service-detail-hero">
        <div class="container">
            <div class="breadcrumb">
                <a href="../../index.php">Ana Sayfa</a> / 
                <a href="../hizmetlerimiz.php">Hizmetler</a> / 
                <span>Taşınmaz Satışı</span>
            </div>
            <h1>🏠 Taşınmaz Satışı Hizmeti</h1>
        </div>
    </section>

    <!-- İçerik Bölümü -->
    <section class="service-detail-content">
        <div class="container">
            <div class="content-wrapper">
                <!-- Ana İçerik -->
                <div class="main-content">
                    <h2>Gayrimenkulünüzü Değerinde Satın</h2>
                    <p>
                        Gayrimenkulünüzü satarken en önemli unsur doğru fiyatlandırma ve güvenli bir süreçtir. 
                        Plaza Emlak & Yatırım olarak profesyonel pazar analizleri yapıyor, gayrimenkulünüzün 
                        gerçek değerini tespit ediyor ve geniş müşteri ağımız sayesinde en kısa sürede alıcıya 
                        ulaştırıyoruz.
                    </p>
                    
                    <p>
                        Satış sürecinde tüm hukuki ve idari işlemleri takip ederek sizi zahmetten kurtarıyor, 
                        hem güvenli hem de kazançlı bir satış gerçekleştirmenizi sağlıyoruz.
                    </p>

                    <h2>Sunduğumuz Avantajlar</h2>
                    <ul class="feature-list">
                        <li>Profesyonel pazar analizi ve doğru fiyatlandırma</li>
                        <li>Geniş müşteri portföyü ile hızlı satış</li>
                        <li>Tüm hukuki süreçlerin takibi ve yönetimi</li>
                        <li>Gayrimenkul değerleme raporu hizmeti</li>
                        <li>Profesyonel fotoğraf ve tanıtım hizmeti</li>
                        <li>Online ve offline pazarlama stratejileri</li>
                        <li>Güvenli ödeme ve devir işlemleri</li>
                        <li>Tapu işlemleri danışmanlığı</li>
                    </ul>

                    <h2>Satış Süreci Nasıl İlerler?</h2>
                    <p>
                        <strong>1. Değerleme:</strong> Gayrimenkulünüzü yerinde inceleyerek profesyonel değerleme yapıyoruz.<br><br>
                        <strong>2. Pazarlama:</strong> Profesyonel fotoğraflar ve tanıtım metinleri ile ilanınızı yayınlıyoruz.<br><br>
                        <strong>3. Müşteri Bulma:</strong> Geniş müşteri ağımız ve pazarlama kanallarımız ile alıcılara ulaşıyoruz.<br><br>
                        <strong>4. Görüşmeler:</strong> Potansiyel alıcılarla görüşmeleri organize ediyoruz.<br><br>
                        <strong>5. Anlaşma:</strong> Fiyat pazarlıklarını yöneterek en iyi satış fiyatını elde ediyoruz.<br><br>
                        <strong>6. Devir İşlemleri:</strong> Tapu devri ve tüm yasal süreçleri takip ediyoruz.
                    </p>
                </div>

                <!-- Yan Menü -->
                <div class="sidebar">
                    <!-- İletişim Kartı -->
                    <div class="contact-card">
                        <h3>Hemen İletişime Geçin</h3>
                        <p>Gayrimenkulünüzü değerinde satmak için profesyonel desteğe mi ihtiyacınız var?</p>
                        <a href="../iletisim.php" class="contact-btn">Ücretsiz Danışmanlık</a>
                    </div>

                    <!-- Diğer Hizmetler -->
                    <div class="other-services">
                        <h4>Diğer Hizmetlerimiz</h4>
                        <ul class="service-links">
                            <li><a href="tasinmaz-kiralama.php">→ Taşınmaz Kiralama</a></li>
                            <li><a href="miras-intikali.php">→ Miras İntikali</a></li>
                            <li><a href="miras-paylasimi.php">→ Miras Paylaşımı</a></li>
                            <li><a href="kat-irtifaki.php">→ Kat İrtifakı Kurulması</a></li>
                            <li><a href="ortaklik-giderilmesi.php">→ Ortaklığın Giderilmesi</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <?php include '../../includes/footer.php'; ?>

    <!-- Mobil Menü JavaScript -->
    <script>
        document.querySelector('.mobile-menu-toggle').addEventListener('click', function() {
            document.querySelector('.nav-menu').classList.toggle('active');
        });
    </script>
</body>
</html>