<<<<<<< HEAD
<?php
// pages/hizmetlerimiz.php - Verdiğimiz Hizmetler Ana Sayfası
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verdiğimiz Hizmetler - Plaza Emlak & Yatırım</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/logo-fix.css">
    <link rel="stylesheet" href="../assets/css/pages.css">
    <link rel="stylesheet" href="../assets/css/override.css">
    <style>
        .services-hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 80px 0;
            color: white;
            text-align: center;
        }
        
        .services-hero h1 {
            font-size: 2.5rem;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }
        
        .services-hero p {
            font-size: 1.2rem;
            opacity: 0.95;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .services-container {
            padding: 60px 0;
            background: #f8f9fa;
        }
        
        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }
        
        .service-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .service-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }
        
        .service-icon {
            font-size: 3rem;
            margin-bottom: 20px;
            display: inline-block;
        }
        
        .service-card h3 {
            color: #2c3e50;
            margin-bottom: 15px;
            font-size: 1.5rem;
        }
        
        .service-card p {
            color: #666;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        
        .service-link {
            display: inline-block;
            color: #3498db;
            text-decoration: none;
            font-weight: 600;
            padding: 10px 20px;
            border: 2px solid #3498db;
            border-radius: 25px;
            transition: all 0.3s;
        }
        
        .service-link:hover {
            background: #3498db;
            color: white;
        }
        
        @media (max-width: 768px) {
            .services-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .services-hero h1 {
                font-size: 2rem;
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
                    <a href="../index.php" class="logo-link">
                        <img src="../assets/images/plaza-logo-buyuk.png" alt="Plaza Emlak & Yatırım" class="logo-img">
                    </a>
                    <div class="logo-slogan">
                        <span class="slogan-text">Geleceğinize İyi Bir Yatırım</span>
                    </div>
                </div>
                <ul class="nav-menu">
                    <li><a href="../index.php">Ana Sayfa</a></li>
                    <li><a href="satilik.php">Satılık</a></li>
                    <li><a href="kiralik.php">Kiralık</a></li>
                    <li><a href="hizmetlerimiz.php" class="active">Verdiğimiz Hizmetler</a></li>
                    <li><a href="hakkimizda.php">Hakkımızda</a></li>
                    <li><a href="iletisim.php">İletişim</a></li>
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

    <!-- Hero Bölümü -->
    <section class="services-hero">
        <div class="container">
            <h1>Verdiğimiz Hizmetler</h1>
            <p>Plaza Emlak & Yatırım olarak gayrimenkul sektöründe profesyonel ve güvenilir hizmetler sunuyoruz</p>
        </div>
    </section>

    <!-- Hizmetler Listesi -->
    <section class="services-container">
        <div class="container">
            <div class="services-grid">
                <!-- Taşınmaz Satışı -->
                <div class="service-card">
                    <span class="service-icon">🏠</span>
                    <h3>Taşınmaz Satışı</h3>
                    <p>Gayrimenkulünüzü en doğru fiyattan, güvenli ve hızlı bir şekilde satmanızı sağlıyoruz.</p>
                    <a href="hizmetler/tasinmaz-satisi.php" class="service-link">Detaylı Bilgi →</a>
                </div>

                <!-- Taşınmaz Kiralama -->
                <div class="service-card">
                    <span class="service-icon">🔑</span>
                    <h3>Taşınmaz Kiralama</h3>
                    <p>Doğru kiracıya hızlı ve güvenilir şekilde ulaşmanız için profesyonel kiralama hizmeti.</p>
                    <a href="hizmetler/tasinmaz-kiralama.php" class="service-link">Detaylı Bilgi →</a>
                </div>

                <!-- Miras İntikali -->
                <div class="service-card">
                    <span class="service-icon">📋</span>
                    <h3>Miras İntikali</h3>
                    <p>Miras yoluyla geçen taşınmazların tapu devir işlemlerini hukuka uygun şekilde yönetiyoruz.</p>
                    <a href="hizmetler/miras-intikali.php" class="service-link">Detaylı Bilgi →</a>
                </div>

                <!-- Miras Paylaşımı -->
                <div class="service-card">
                    <span class="service-icon">⚖️</span>
                    <h3>Miras Paylaşımı</h3>
                    <p>Aile bireyleri arasındaki miras paylaşımını adil ve uzlaşmacı şekilde çözüme kavuşturuyoruz.</p>
                    <a href="hizmetler/miras-paylasimi.php" class="service-link">Detaylı Bilgi →</a>
                </div>

                <!-- Kat İrtifakı Kurulması -->
                <div class="service-card">
                    <span class="service-icon">🏗️</span>
                    <h3>Kat İrtifakı Kurulması</h3>
                    <p>İnşaatı devam eden projelerinizde kat irtifakı kurulması işlemlerini yönetiyoruz.</p>
                    <a href="hizmetler/kat-irtifaki.php" class="service-link">Detaylı Bilgi →</a>
                </div>

                <!-- Ortaklığın Giderilmesi -->
                <div class="service-card">
                    <span class="service-icon">🤝</span>
                    <h3>Ortaklığın Giderilmesi</h3>
                    <p>Hisseli gayrimenkullerde ortaklığın giderilmesi (İzale-i Şuyu) sürecini yönetiyoruz.</p>
                    <a href="hizmetler/ortaklik-giderilmesi.php" class="service-link">Detaylı Bilgi →</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <?php include '../includes/footer.php'; ?>

    <!-- Mobil Menü JavaScript - DÜZELTİLDİ -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var toggleButton = document.querySelector('.mobile-menu-toggle');
        var navMenu = document.querySelector('.nav-menu');
        
        if(toggleButton && navMenu) {
            toggleButton.addEventListener('click', function() {
                this.classList.toggle('active');
                navMenu.classList.toggle('active');
            });
        }
    });
    </script>
</body>
=======
<?php
// pages/hizmetlerimiz.php - Verdiğimiz Hizmetler Ana Sayfası
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verdiğimiz Hizmetler - Plaza Emlak & Yatırım</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/logo-fix.css">
    <link rel="stylesheet" href="../assets/css/pages.css">
    <link rel="stylesheet" href="../assets/css/override.css">
    <style>
        .services-hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 80px 0;
            color: white;
            text-align: center;
        }
        
        .services-hero h1 {
            font-size: 2.5rem;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }
        
        .services-hero p {
            font-size: 1.2rem;
            opacity: 0.95;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .services-container {
            padding: 60px 0;
            background: #f8f9fa;
        }
        
        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }
        
        .service-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .service-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }
        
        .service-icon {
            font-size: 3rem;
            margin-bottom: 20px;
            display: inline-block;
        }
        
        .service-card h3 {
            color: #2c3e50;
            margin-bottom: 15px;
            font-size: 1.5rem;
        }
        
        .service-card p {
            color: #666;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        
        .service-link {
            display: inline-block;
            color: #3498db;
            text-decoration: none;
            font-weight: 600;
            padding: 10px 20px;
            border: 2px solid #3498db;
            border-radius: 25px;
            transition: all 0.3s;
        }
        
        .service-link:hover {
            background: #3498db;
            color: white;
        }
        
        @media (max-width: 768px) {
            .services-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .services-hero h1 {
                font-size: 2rem;
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
                    <a href="../index.php" class="logo-link">
                        <img src="../assets/images/plaza-logo-buyuk.png" alt="Plaza Emlak & Yatırım" class="logo-img">
                    </a>
                    <div class="logo-slogan">
                        <span class="slogan-text">Geleceğinize İyi Bir Yatırım</span>
                    </div>
                </div>
                <ul class="nav-menu">
                    <li><a href="../index.php">Ana Sayfa</a></li>
                    <li><a href="satilik.php">Satılık</a></li>
                    <li><a href="kiralik.php">Kiralık</a></li>
                    <li><a href="hizmetlerimiz.php" class="active">Verdiğimiz Hizmetler</a></li>
                    <li><a href="hakkimizda.php">Hakkımızda</a></li>
                    <li><a href="iletisim.php">İletişim</a></li>
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

    <!-- Hero Bölümü -->
    <section class="services-hero">
        <div class="container">
            <h1>Verdiğimiz Hizmetler</h1>
            <p>Plaza Emlak & Yatırım olarak gayrimenkul sektöründe profesyonel ve güvenilir hizmetler sunuyoruz</p>
        </div>
    </section>

    <!-- Hizmetler Listesi -->
    <section class="services-container">
        <div class="container">
            <div class="services-grid">
                <!-- Taşınmaz Satışı -->
                <div class="service-card">
                    <span class="service-icon">🏠</span>
                    <h3>Taşınmaz Satışı</h3>
                    <p>Gayrimenkulünüzü en doğru fiyattan, güvenli ve hızlı bir şekilde satmanızı sağlıyoruz.</p>
                    <a href="hizmetler/tasinmaz-satisi.php" class="service-link">Detaylı Bilgi →</a>
                </div>

                <!-- Taşınmaz Kiralama -->
                <div class="service-card">
                    <span class="service-icon">🔑</span>
                    <h3>Taşınmaz Kiralama</h3>
                    <p>Doğru kiracıya hızlı ve güvenilir şekilde ulaşmanız için profesyonel kiralama hizmeti.</p>
                    <a href="hizmetler/tasinmaz-kiralama.php" class="service-link">Detaylı Bilgi →</a>
                </div>

                <!-- Miras İntikali -->
                <div class="service-card">
                    <span class="service-icon">📋</span>
                    <h3>Miras İntikali</h3>
                    <p>Miras yoluyla geçen taşınmazların tapu devir işlemlerini hukuka uygun şekilde yönetiyoruz.</p>
                    <a href="hizmetler/miras-intikali.php" class="service-link">Detaylı Bilgi →</a>
                </div>

                <!-- Miras Paylaşımı -->
                <div class="service-card">
                    <span class="service-icon">⚖️</span>
                    <h3>Miras Paylaşımı</h3>
                    <p>Aile bireyleri arasındaki miras paylaşımını adil ve uzlaşmacı şekilde çözüme kavuşturuyoruz.</p>
                    <a href="hizmetler/miras-paylasimi.php" class="service-link">Detaylı Bilgi →</a>
                </div>

                <!-- Kat İrtifakı Kurulması -->
                <div class="service-card">
                    <span class="service-icon">🏗️</span>
                    <h3>Kat İrtifakı Kurulması</h3>
                    <p>İnşaatı devam eden projelerinizde kat irtifakı kurulması işlemlerini yönetiyoruz.</p>
                    <a href="hizmetler/kat-irtifaki.php" class="service-link">Detaylı Bilgi →</a>
                </div>

                <!-- Ortaklığın Giderilmesi -->
                <div class="service-card">
                    <span class="service-icon">🤝</span>
                    <h3>Ortaklığın Giderilmesi</h3>
                    <p>Hisseli gayrimenkullerde ortaklığın giderilmesi (İzale-i Şuyu) sürecini yönetiyoruz.</p>
                    <a href="hizmetler/ortaklik-giderilmesi.php" class="service-link">Detaylı Bilgi →</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <?php include '../includes/footer.php'; ?>

    <!-- Mobil Menü JavaScript - DÜZELTİLDİ -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var toggleButton = document.querySelector('.mobile-menu-toggle');
        var navMenu = document.querySelector('.nav-menu');
        
        if(toggleButton && navMenu) {
            toggleButton.addEventListener('click', function() {
                this.classList.toggle('active');
                navMenu.classList.toggle('active');
            });
        }
    });
    </script>
</body>
>>>>>>> 1b4657b0e4a9c21769cd70a9511bc87296025d5f
</html>