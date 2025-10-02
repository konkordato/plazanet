<<<<<<< HEAD
<?php
// pages/hizmetler/miras-paylasimi.php - Miras Paylaşımı Hizmet Detay Sayfası
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Miras Paylaşımı Hizmeti - Plaza Emlak & Yatırım</title>
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
            background: #e8f5e9;
            border-left: 4px solid #4caf50;
            padding: 20px;
            margin: 25px 0;
            border-radius: 5px;
        }
        
        .info-box h3 {
            color: #2e7d32;
            margin-bottom: 10px;
        }
        
        .warning-box {
            background: #fff3e0;
            border-left: 4px solid #ff9800;
            padding: 20px;
            margin: 25px 0;
            border-radius: 5px;
        }
        
        .warning-box h3 {
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
                <span>Miras Paylaşımı</span>
            </div>
            <h1>⚖️ Miras Paylaşımı Hizmeti</h1>
        </div>
    </section>

    <!-- İçerik Bölümü -->
    <section class="service-detail-content">
        <div class="container">
            <div class="content-wrapper">
                <!-- Ana İçerik -->
                <div class="main-content">
                    <h2>Adil ve Uzlaşmacı Miras Paylaşımı</h2>
                    <p>
                        Aile bireyleri arasındaki miras paylaşımı hassasiyet gerektiren bir süreçtir. 
                        Yanlış adımlar, uzun süren davalara ve aile içi anlaşmazlıklara yol açabilir. 
                        Plaza Emlak & Yatırım olarak bu süreçte profesyonel danışmanlık sunuyor, adil 
                        ve şeffaf çözümler geliştiriyoruz.
                    </p>
                    
                    <p>
                        Amacımız, aile bireyleri arasındaki uzlaşmayı koruyarak paylaşım sürecini huzur 
                        ve güven içinde tamamlamaktır. Hukuki bilgimiz ve tecrübemizle, her mirasçının 
                        haklarını koruyarak adil bir paylaşım sağlıyoruz.
                    </p>

                    <div class="info-box">
                        <h3>💡 Uzlaşma Önceliğimiz</h3>
                        <p>
                            Mahkeme süreçleri hem maddi hem manevi yıpratıcı olabilir. Bu nedenle öncelikle 
                            uzlaşma yoluyla çözüm üretmeye çalışıyoruz. Tarafsız ve profesyonel yaklaşımımızla 
                            aile içi huzurun korunmasına özen gösteriyoruz.
                        </p>
                    </div>

                    <h2>Miras Paylaşımı Hizmetlerimiz</h2>
                    <ul class="feature-list">
                        <li>Miras payı hesaplamaları</li>
                        <li>Mirasçılar arası uzlaşma görüşmeleri</li>
                        <li>Taksim sözleşmesi hazırlama</li>
                        <li>Gayrimenkul değer tespiti</li>
                        <li>Noter işlemleri koordinasyonu</li>
                        <li>Mahkeme süreçleri yönetimi</li>
                        <li>Vergi danışmanlığı</li>
                        <li>Tapu devir işlemleri</li>
                    </ul>

                    <h2>Paylaşım Yöntemleri</h2>
                    <p>
                        <strong>1. Anlaşmalı Paylaşım:</strong> Tüm mirasçıların uzlaşması ile gerçekleşir. En hızlı ve ekonomik yöntemdir.<br><br>
                        <strong>2. Fiili Paylaşım:</strong> Gayrimenkulün fiziki olarak bölünmesi mümkünse uygulanır.<br><br>
                        <strong>3. Satış Yoluyla Paylaşım:</strong> Gayrimenkul satılır ve bedel paylaşılır.<br><br>
                        <strong>4. Mahkeme Yoluyla Paylaşım:</strong> Anlaşma sağlanamazsa mahkeme kararıyla paylaşım yapılır.
                    </p>

                    <div class="warning-box">
                        <h3>⚠️ Dikkat Edilmesi Gerekenler</h3>
                        <p>
                            • Saklı pay haklarına dikkat edilmelidir<br>
                            • Vasiyetname varsa dikkate alınmalıdır<br>
                            • Vergi yükümlülükleri göz önünde bulundurulmalıdır<br>
                            • Tüm mirasçıların rızası alınmalıdır
                        </p>
                    </div>

                    <h2>Süreç Nasıl İşler?</h2>
                    <p>
                        <strong>1. Durum Analizi:</strong> Miras konusu malvarlığı ve mirasçılar tespit edilir.<br><br>
                        <strong>2. Değerleme:</strong> Gayrimenkuller ve diğer varlıklar değerlenir.<br><br>
                        <strong>3. Pay Hesaplama:</strong> Yasal miras payları hesaplanır.<br><br>
                        <strong>4. Görüşmeler:</strong> Mirasçılar arası uzlaşma görüşmeleri yapılır.<br><br>
                        <strong>5. Anlaşma:</strong> Taksim sözleşmesi hazırlanır ve imzalanır.<br><br>
                        <strong>6. Tescil:</strong> Anlaşma tapu ve ilgili kurumlarda tescil edilir.
                    </p>

                    <p style="margin-top: 30px;">
                        <strong>Sonuç:</strong> Profesyonel desteğimizle miras paylaşımı sürecinizi 
                        aile huzurunu koruyarak, adil ve hızlı bir şekilde tamamlayabilirsiniz.
                    </p>
                </div>

                <!-- Yan Menü -->
                <div class="sidebar">
                    <!-- İletişim Kartı -->
                    <div class="contact-card">
                        <h3>Hemen İletişime Geçin</h3>
                        <p>Miras paylaşımı için uzman desteği alın, aile huzurunu koruyun!</p>
                        <a href="../iletisim.php" class="contact-btn">Ücretsiz Danışmanlık</a>
                    </div>

                    <!-- Diğer Hizmetler -->
                    <div class="other-services">
                        <h4>Diğer Hizmetlerimiz</h4>
                        <ul class="service-links">
                            <li><a href="tasinmaz-satisi.php">→ Taşınmaz Satışı</a></li>
                            <li><a href="tasinmaz-kiralama.php">→ Taşınmaz Kiralama</a></li>
                            <li><a href="miras-intikali.php">→ Miras İntikali</a></li>
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
=======
<?php
// pages/hizmetler/miras-paylasimi.php - Miras Paylaşımı Hizmet Detay Sayfası
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Miras Paylaşımı Hizmeti - Plaza Emlak & Yatırım</title>
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
            background: #e8f5e9;
            border-left: 4px solid #4caf50;
            padding: 20px;
            margin: 25px 0;
            border-radius: 5px;
        }
        
        .info-box h3 {
            color: #2e7d32;
            margin-bottom: 10px;
        }
        
        .warning-box {
            background: #fff3e0;
            border-left: 4px solid #ff9800;
            padding: 20px;
            margin: 25px 0;
            border-radius: 5px;
        }
        
        .warning-box h3 {
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
                <span>Miras Paylaşımı</span>
            </div>
            <h1>⚖️ Miras Paylaşımı Hizmeti</h1>
        </div>
    </section>

    <!-- İçerik Bölümü -->
    <section class="service-detail-content">
        <div class="container">
            <div class="content-wrapper">
                <!-- Ana İçerik -->
                <div class="main-content">
                    <h2>Adil ve Uzlaşmacı Miras Paylaşımı</h2>
                    <p>
                        Aile bireyleri arasındaki miras paylaşımı hassasiyet gerektiren bir süreçtir. 
                        Yanlış adımlar, uzun süren davalara ve aile içi anlaşmazlıklara yol açabilir. 
                        Plaza Emlak & Yatırım olarak bu süreçte profesyonel danışmanlık sunuyor, adil 
                        ve şeffaf çözümler geliştiriyoruz.
                    </p>
                    
                    <p>
                        Amacımız, aile bireyleri arasındaki uzlaşmayı koruyarak paylaşım sürecini huzur 
                        ve güven içinde tamamlamaktır. Hukuki bilgimiz ve tecrübemizle, her mirasçının 
                        haklarını koruyarak adil bir paylaşım sağlıyoruz.
                    </p>

                    <div class="info-box">
                        <h3>💡 Uzlaşma Önceliğimiz</h3>
                        <p>
                            Mahkeme süreçleri hem maddi hem manevi yıpratıcı olabilir. Bu nedenle öncelikle 
                            uzlaşma yoluyla çözüm üretmeye çalışıyoruz. Tarafsız ve profesyonel yaklaşımımızla 
                            aile içi huzurun korunmasına özen gösteriyoruz.
                        </p>
                    </div>

                    <h2>Miras Paylaşımı Hizmetlerimiz</h2>
                    <ul class="feature-list">
                        <li>Miras payı hesaplamaları</li>
                        <li>Mirasçılar arası uzlaşma görüşmeleri</li>
                        <li>Taksim sözleşmesi hazırlama</li>
                        <li>Gayrimenkul değer tespiti</li>
                        <li>Noter işlemleri koordinasyonu</li>
                        <li>Mahkeme süreçleri yönetimi</li>
                        <li>Vergi danışmanlığı</li>
                        <li>Tapu devir işlemleri</li>
                    </ul>

                    <h2>Paylaşım Yöntemleri</h2>
                    <p>
                        <strong>1. Anlaşmalı Paylaşım:</strong> Tüm mirasçıların uzlaşması ile gerçekleşir. En hızlı ve ekonomik yöntemdir.<br><br>
                        <strong>2. Fiili Paylaşım:</strong> Gayrimenkulün fiziki olarak bölünmesi mümkünse uygulanır.<br><br>
                        <strong>3. Satış Yoluyla Paylaşım:</strong> Gayrimenkul satılır ve bedel paylaşılır.<br><br>
                        <strong>4. Mahkeme Yoluyla Paylaşım:</strong> Anlaşma sağlanamazsa mahkeme kararıyla paylaşım yapılır.
                    </p>

                    <div class="warning-box">
                        <h3>⚠️ Dikkat Edilmesi Gerekenler</h3>
                        <p>
                            • Saklı pay haklarına dikkat edilmelidir<br>
                            • Vasiyetname varsa dikkate alınmalıdır<br>
                            • Vergi yükümlülükleri göz önünde bulundurulmalıdır<br>
                            • Tüm mirasçıların rızası alınmalıdır
                        </p>
                    </div>

                    <h2>Süreç Nasıl İşler?</h2>
                    <p>
                        <strong>1. Durum Analizi:</strong> Miras konusu malvarlığı ve mirasçılar tespit edilir.<br><br>
                        <strong>2. Değerleme:</strong> Gayrimenkuller ve diğer varlıklar değerlenir.<br><br>
                        <strong>3. Pay Hesaplama:</strong> Yasal miras payları hesaplanır.<br><br>
                        <strong>4. Görüşmeler:</strong> Mirasçılar arası uzlaşma görüşmeleri yapılır.<br><br>
                        <strong>5. Anlaşma:</strong> Taksim sözleşmesi hazırlanır ve imzalanır.<br><br>
                        <strong>6. Tescil:</strong> Anlaşma tapu ve ilgili kurumlarda tescil edilir.
                    </p>

                    <p style="margin-top: 30px;">
                        <strong>Sonuç:</strong> Profesyonel desteğimizle miras paylaşımı sürecinizi 
                        aile huzurunu koruyarak, adil ve hızlı bir şekilde tamamlayabilirsiniz.
                    </p>
                </div>

                <!-- Yan Menü -->
                <div class="sidebar">
                    <!-- İletişim Kartı -->
                    <div class="contact-card">
                        <h3>Hemen İletişime Geçin</h3>
                        <p>Miras paylaşımı için uzman desteği alın, aile huzurunu koruyun!</p>
                        <a href="../iletisim.php" class="contact-btn">Ücretsiz Danışmanlık</a>
                    </div>

                    <!-- Diğer Hizmetler -->
                    <div class="other-services">
                        <h4>Diğer Hizmetlerimiz</h4>
                        <ul class="service-links">
                            <li><a href="tasinmaz-satisi.php">→ Taşınmaz Satışı</a></li>
                            <li><a href="tasinmaz-kiralama.php">→ Taşınmaz Kiralama</a></li>
                            <li><a href="miras-intikali.php">→ Miras İntikali</a></li>
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
>>>>>>> 1b4657b0e4a9c21769cd70a9511bc87296025d5f
</html>