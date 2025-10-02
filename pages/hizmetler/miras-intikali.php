<?php
// pages/hizmetler/miras-intikali.php - Miras İntikali Hizmet Detay Sayfası
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Miras İntikali Hizmeti - Plaza Emlak & Yatırım</title>
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
        
        .info-box {
            background: #fff4e6;
            border-left: 4px solid #ff9800;
            padding: 20px;
            margin: 25px 0;
            border-radius: 5px;
        }
        
        .info-box h3 {
            color: #e65100;
            margin-bottom: 10px;
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
                <span>Miras İntikali</span>
            </div>
            <h1>📋 Miras İntikali Hizmeti</h1>
        </div>
    </section>

    <!-- İçerik Bölümü -->
    <section class="service-detail-content">
        <div class="container">
            <div class="content-wrapper">
                <!-- Ana İçerik -->
                <div class="main-content">
                    <h2>Miras İşlemlerinizi Hukuki Güvence Altına Alın</h2>
                    <p>
                        Miras yoluyla geçen taşınmazların tapu devir işlemleri çoğu zaman karmaşık ve yorucu 
                        olabilir. Biz, hukuka uygun ve en hızlı şekilde intikal işlemlerini sonuçlandırarak 
                        sizin hak kaybı yaşamadan süreci tamamlamanızı sağlıyoruz. Miras kalan taşınmazların 
                        güvenli şekilde adınıza geçmesini sağlayarak geleceğinizi güvence altına alıyoruz.
                    </p>
                    
                    <p>
                        Plaza Emlak & Yatırım olarak, miras hukuku konusunda uzman kadromuzla tüm intikal 
                        sürecinizi yönetiyoruz. Veraset ilamı alınmasından tapu devrine kadar her aşamada 
                        yanınızdayız.
                    </p>

                    <div class="info-box">
                        <h3>⚠️ Önemli Bilgi</h3>
                        <p>
                            Miras intikali işlemleri belirli süreler içinde yapılmalıdır. Gecikme durumunda 
                            vergi cezaları ve hak kayıpları yaşanabilir. Profesyonel destek alarak bu riskleri 
                            ortadan kaldırabilirsiniz.
                        </p>
                    </div>

                    <h2>Miras İntikali Hizmetlerimiz</h2>
                    <ul class="feature-list">
                        <li>Veraset ilamı başvuru ve takibi</li>
                        <li>Mirasçılık belgesi temini</li>
                        <li>Tapu devir işlemleri</li>
                        <li>Vergi hesaplama ve ödeme takibi</li>
                        <li>Hisse hesaplamaları</li>
                        <li>Noter işlemleri</li>
                        <li>Mahkeme süreçleri takibi</li>
                        <li>Belge eksikliklerinin tamamlanması</li>
                    </ul>

                    <h2>İntikal Süreci Nasıl İşler?</h2>
                    <p>
                        <strong>1. Belge Toplama:</strong> Vefat belgesi, nüfus kayıtları ve tapu belgeleri toplanır.<br><br>
                        <strong>2. Mirasçı Tespiti:</strong> Yasal mirasçılar belirlenir ve belgelendirilir.<br><br>
                        <strong>3. Veraset İlamı:</strong> Sulh hukuk mahkemesinden veraset ilamı alınır.<br><br>
                        <strong>4. Vergi İşlemleri:</strong> Veraset ve intikal vergisi hesaplanır ve ödenir.<br><br>
                        <strong>5. Tapu İşlemleri:</strong> Tapu müdürlüğünde intikal işlemi yapılır.<br><br>
                        <strong>6. Tescil:</strong> Gayrimenkuller yeni sahipleri adına tescil edilir.
                    </p>

                    <h2>Gerekli Belgeler</h2>
                    <p>
                        • Vefat belgesi<br>
                        • Veraset ilamı veya mirasçılık belgesi<br>
                        • Tapu senedi<br>
                        • Nüfus cüzdanları<br>
                        • Vergi kimlik numaraları<br>
                        • Değer tespiti için emlak beyanı<br>
                        • Varsa vasiyetname
                    </p>

                    <p style="margin-top: 30px;">
                        <strong>Not:</strong> Her miras durumu kendine özgüdür. Profesyonel danışmanlık alarak 
                        sürecinizi hızlı ve sorunsuz tamamlayabilirsiniz.
                    </p>
                </div>

                <!-- Yan Menü -->
                <div class="sidebar">
                    <!-- İletişim Kartı -->
                    <div class="contact-card">
                        <h3>Hemen İletişime Geçin</h3>
                        <p>Miras intikal işlemleriniz için profesyonel destek alın!</p>
                        <a href="../iletisim.php" class="contact-btn">Ücretsiz Danışmanlık</a>
                    </div>

                    <!-- Diğer Hizmetler -->
                    <div class="other-services">
                        <h4>Diğer Hizmetlerimiz</h4>
                        <ul class="service-links">
                            <li><a href="tasinmaz-satisi.php">→ Taşınmaz Satışı</a></li>
                            <li><a href="tasinmaz-kiralama.php">→ Taşınmaz Kiralama</a></li>
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