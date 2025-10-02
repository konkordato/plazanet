<?php
// pages/hizmetler/ortaklik-giderilmesi.php - Ortaklığın Giderilmesi (İzale-i Şuyu) Hizmet Detay Sayfası
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ortaklığın Giderilmesi (İzale-i Şuyu) Hizmeti - Plaza Emlak & Yatırım</title>
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
            background: #fce4ec;
            border-left: 4px solid #e91e63;
            padding: 20px;
            margin: 25px 0;
            border-radius: 5px;
        }
        
        .info-box h3 {
            color: #c2185b;
            margin-bottom: 10px;
        }
        
        .method-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 25px 0;
        }
        
        .method-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .method-card h4 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .method-card p {
            font-size: 0.95rem;
            color: #777;
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
            
            .method-cards {
                grid-template-columns: 1fr;
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
                <span>Ortaklığın Giderilmesi</span>
            </div>
            <h1>🤝 Ortaklığın Giderilmesi (İzale-i Şuyu)</h1>
        </div>
    </section>

    <!-- İçerik Bölümü -->
    <section class="service-detail-content">
        <div class="container">
            <div class="content-wrapper">
                <!-- Ana İçerik -->
                <div class="main-content">
                    <h2>Hisseli Mülkiyete Son, Tam Mülkiyete Geçiş</h2>
                    <p>
                        Hisseli gayrimenkullerde ortaklık çoğu zaman uyuşmazlıklara neden olur. Bu durumda 
                        ortaklığın giderilmesi, yani izale-i şuyu süreci devreye girer. Plaza Emlak & Yatırım 
                        olarak bu alanda uzman desteği sağlıyor, süreci en hızlı ve adil şekilde sonuçlandırıyoruz.
                    </p>
                    
                    <p>
                        Amacımız, hisseli taşınmazlarınızı sizin için değer kaybı olmadan ve anlaşmazlıklara 
                        yol açmadan çözüme kavuşturmaktır. Hem uzlaşma yolu hem de mahkeme süreci konusunda 
                        deneyimli ekibimizle yanınızdayız.
                    </p>

                    <div class="info-box">
                        <h3>💡 İzale-i Şuyu Nedir?</h3>
                        <p>
                            İzale-i şuyu, birden fazla kişinin ortak sahibi olduğu gayrimenkullerde, 
                            ortaklığın sona erdirilmesi işlemidir. Bu işlem, ortakların anlaşması veya 
                            mahkeme kararı ile gerçekleşebilir. Amaç, her ortağın hakkını almasını sağlamaktır.
                        </p>
                    </div>

                    <h2>Ortaklığın Giderilmesi Hizmetlerimiz</h2>
                    <ul class="feature-list">
                        <li>Hisse oranlarının tespiti</li>
                        <li>Gayrimenkul değer tespiti</li>
                        <li>Ortaklar arası uzlaşma görüşmeleri</li>
                        <li>Satış sürecinin yönetimi</li>
                        <li>Açık artırma organizasyonu</li>
                        <li>Mahkeme süreçleri takibi</li>
                        <li>Noter işlemleri</li>
                        <li>Tapu devir işlemleri</li>
                    </ul>

                    <h2>Ortaklığın Giderilmesi Yöntemleri</h2>
                    <div class="method-cards">
                        <div class="method-card">
                            <h4>📝 Anlaşmalı Satış</h4>
                            <p>Tüm ortakların rızası ile gayrimenkul satılır, bedel hisselere göre paylaşılır.</p>
                        </div>
                        <div class="method-card">
                            <h4>⚖️ Mahkeme Yolu</h4>
                            <p>Anlaşma sağlanamazsa, mahkeme kararıyla satış yapılır.</p>
                        </div>
                        <div class="method-card">
                            <h4>🔄 Hisse Devri</h4>
                            <p>Bir ortak diğerlerinin hisselerini satın alarak tek malik olur.</p>
                        </div>
                        <div class="method-card">
                            <h4>✂️ Aynen Taksim</h4>
                            <p>Gayrimenkul fiziki olarak bölünebiliyorsa, her ortağa ayrı parça verilir.</p>
                        </div>
                    </div>

                    <h2>İzale-i Şuyu Davası Süreci</h2>
                    <p>
                        <strong>1. Dava Açılması:</strong> Ortaklardan herhangi biri dava açabilir.<br><br>
                        <strong>2. Bilirkişi İncelemesi:</strong> Gayrimenkulün değeri ve bölünebilirliği tespit edilir.<br><br>
                        <strong>3. Karar:</strong> Mahkeme, satış veya taksim kararı verir.<br><br>
                        <strong>4. Satış:</strong> Açık artırma ile gayrimenkul satılır.<br><br>
                        <strong>5. Paylaşım:</strong> Satış bedeli hisse oranlarına göre dağıtılır.<br><br>
                        <strong>6. Tapu İşlemi:</strong> Yeni malik adına tapu tescili yapılır.
                    </p>

                    <h2>Neden Profesyonel Destek Almalısınız?</h2>
                    <p>
                        • Hukuki süreçleri doğru yönetiriz<br>
                        • En yüksek satış bedelini elde ederiz<br>
                        • Ortaklar arası anlaşmazlıkları çözeriz<br>
                        • Zaman ve maliyet tasarrufu sağlarız<br>
                        • Vergi ve harçları optimize ederiz<br>
                        • Tapu işlemlerini takip ederiz
                    </p>

                    <p style="margin-top: 30px;">
                        <strong>Önemli:</strong> Hisseli mülkiyet sorunları büyümeden çözülmelidir. 
                        Uzman desteği ile haklarınızı koruyarak, gayrimenkulünüzden maksimum değeri 
                        elde edebilirsiniz.
                    </p>
                </div>

                <!-- Yan Menü -->
                <div class="sidebar">
                    <!-- İletişim Kartı -->
                    <div class="contact-card">
                        <h3>Hemen İletişime Geçin</h3>
                        <p>Hisseli gayrimenkul sorunlarınıza hızlı ve adil çözüm!</p>
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
                            <li><a href="kat-irtifaki.php">→ Kat İrtifakı Kurulması</a></li>
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