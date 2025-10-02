<?php
// pages/hizmetler/kat-irtifaki.php - Kat İrtifakı Kurulması Hizmet Detay Sayfası
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kat İrtifakı Kurulması Hizmeti - Plaza Emlak & Yatırım</title>
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
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 20px;
            margin: 25px 0;
            border-radius: 5px;
        }
        
        .info-box h3 {
            color: #1565c0;
            margin-bottom: 10px;
        }
        
        .process-steps {
            background: white;
            padding: 25px;
            border-radius: 10px;
            margin: 25px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .process-steps ol {
            padding-left: 20px;
            color: #666;
        }
        
        .process-steps li {
            margin-bottom: 15px;
            line-height: 1.6;
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
                <span>Kat İrtifakı Kurulması</span>
            </div>
            <h1>🏗️ Kat İrtifakı Kurulması Hizmeti</h1>
        </div>
    </section>

    <!-- İçerik Bölümü -->
    <section class="service-detail-content">
        <div class="container">
            <div class="content-wrapper">
                <!-- Ana İçerik -->
                <div class="main-content">
                    <h2>Yatırımınızı Hukuki Güvenceye Alın</h2>
                    <p>
                        İnşaatı tamamlanmamış projelerde kat irtifakı kurulması, yatırımınızın değerini 
                        artırır ve hukuki güvence sağlar. Bu alanda uzman ekibimizle süreci en doğru 
                        şekilde yönetiyor, tapuda kat irtifakınızı kurarak ileride doğabilecek sorunları 
                        önlüyoruz.
                    </p>
                    
                    <p>
                        Gayrimenkulünüzün hem bugünkü değerini koruyor hem de gelecekteki satış ve 
                        yatırım süreçlerini kolaylaştırıyoruz. Kat irtifakı, inşaat halindeki yapılarda 
                        bağımsız bölümlerin hukuki statüsünü belirler ve mülkiyet haklarınızı güvence 
                        altına alır.
                    </p>

                    <div class="info-box">
                        <h3>📌 Kat İrtifakı Nedir?</h3>
                        <p>
                            Kat irtifakı, henüz yapımı tamamlanmamış bir yapının ileride kat mülkiyetine 
                            konu olacak bağımsız bölümleri üzerinde, yapı tamamlandığında doğacak olan 
                            kat mülkiyetini önceden kurma amacıyla tesis edilen bir irtifak hakkıdır.
                        </p>
                    </div>

                    <h2>Kat İrtifakı Kurma Hizmetlerimiz</h2>
                    <ul class="feature-list">
                        <li>Proje inceleme ve uygunluk kontrolü</li>
                        <li>Teknik belge hazırlama</li>
                        <li>Mimari proje onay süreçleri</li>
                        <li>Belediye izin işlemleri</li>
                        <li>Tapu tescil işlemleri</li>
                        <li>Yönetim planı hazırlama</li>
                        <li>Bağımsız bölüm tahsisi</li>
                        <li>Hukuki danışmanlık</li>
                    </ul>

                    <h2>Kat İrtifakının Avantajları</h2>
                    <p>
                        <strong>✓ Hukuki Güvence:</strong> Mülkiyet hakkınız tapuya tescil edilir.<br>
                        <strong>✓ Kredi İmkanı:</strong> Bankalardan konut kredisi kullanabilirsiniz.<br>
                        <strong>✓ Satış Kolaylığı:</strong> Daire satışı yasal olarak mümkün hale gelir.<br>
                        <strong>✓ Değer Artışı:</strong> Gayrimenkulünüzün değeri artar.<br>
                        <strong>✓ Sigorta İmkanı:</strong> Dask ve diğer sigortalar yaptırabilirsiniz.
                    </p>

                    <div class="process-steps">
                        <h3>İşlem Süreci</h3>
                        <ol>
                            <li><strong>Belge Toplama:</strong> İnşaat ruhsatı, mimari proje ve diğer belgeler toplanır.</li>
                            <li><strong>Proje Kontrolü:</strong> Projenin kat irtifakına uygunluğu kontrol edilir.</li>
                            <li><strong>Belediye Onayı:</strong> İlgili belediyeden gerekli onaylar alınır.</li>
                            <li><strong>Noter İşlemleri:</strong> Yönetim planı noterde düzenlenir.</li>
                            <li><strong>Tapu İşlemleri:</strong> Tapu müdürlüğünde kat irtifakı tescil edilir.</li>
                            <li><strong>Belge Teslimi:</strong> Yeni tapu senedi sahiplerine teslim edilir.</li>
                        </ol>
                    </div>

                    <h2>Gerekli Belgeler</h2>
                    <p>
                        • Yapı ruhsatı<br>
                        • Onaylı mimari proje<br>
                        • Vaziyet planı<br>
                        • Yönetim planı<br>
                        • Malik listesi ve kimlik belgeleri<br>
                        • Arsa tapusu<br>
                        • Vergi levhası<br>
                        • İmar durumu belgesi
                    </p>

                    <p style="margin-top: 30px;">
                        <strong>Önemli Not:</strong> Kat irtifakı kurulması teknik ve hukuki bilgi 
                        gerektiren bir işlemdir. Profesyonel destek alarak sürecinizi hızlı ve sorunsuz 
                        tamamlayabilirsiniz.
                    </p>
                </div>

                <!-- Yan Menü -->
                <div class="sidebar">
                    <!-- İletişim Kartı -->
                    <div class="contact-card">
                        <h3>Hemen İletişime Geçin</h3>
                        <p>Kat irtifakı işlemleriniz için uzman desteği alın!</p>
                        <a href="../iletisim.php" class="contact-btn">Ücretsiz Danışmanlık</a>
                    </div>

                    <!-- Diğer Hizmetler -->
                    <div class="other-services">
                        <h4>Diğer Hizmetlerimiz</h4>
                        <ul class="service-links">
                            <li><a href="tasinmaz-satisi.php">→ Taşınmaz Satışı</a></li>
                            <li><a href="tasinmaz-kiralama.php">→ Taşınmaz Kiralama</a></li>
                            <li><a href="miras-intikali.php">→ Miras İntikali</a></li>
                            <li><a href="miras-paylasimi.php">→ Miras Paylaşımı</a></li>
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