<?php
require_once '../config/database.php';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>İletişim - Plaza Emlak & Yatırım</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/pages.css">
    <style>
        .contact-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            margin-top: 3rem;
        }
        .contact-info {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .contact-info h2 {
            color: #2c3e50;
            margin-bottom: 2rem;
            font-size: 1.8rem;
        }
        .contact-item {
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid #eee;
        }
        .contact-item:last-child {
            border-bottom: none;
        }
        .contact-item h3 {
            color: #3498db;
            margin-bottom: 1rem;
            font-size: 1.2rem;
        }
        .contact-item p {
            color: #555;
            line-height: 1.6;
            font-size: 1rem;
        }
        .map-container {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .map-container h2 {
            color: #2c3e50;
            margin-bottom: 1.5rem;
        }
        #map {
            width: 100%;
            height: 400px;
            border-radius: 10px;
            border: 2px solid #eee;
        }
        .contact-form {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-top: 2rem;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #2c3e50;
            font-weight: 500;
        }
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }
        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }
        .btn-submit {
            background: #3498db;
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.3s;
        }
        .btn-submit:hover {
            background: #2980b9;
        }
        @media (max-width: 768px) {
            .contact-container {
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
                    <a href="../index.php" class="logo-link">
                        <img src="../assets/images/plaza-logo.png" alt="Plaza Emlak & Yatırım" class="logo-img">
                    </a>
                </div>
                <ul class="nav-menu">
                    <li><a href="../index.php">Ana Sayfa</a></li>
                    <li><a href="satilik.php">Satılık</a></li>
                    <li><a href="kiralik.php">Kiralık</a></li>
                    <li><a href="hakkimizda.php">Hakkımızda</a></li>
                    <li><a href="iletisim.php" class="active">İletişim</a></li>
                    <li><a href="../admin/" class="admin-btn">Yönetim</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <!-- Page Header -->
    <section class="page-header">
        <div class="container">
            <h1>İletişim</h1>
            <p>Bizimle iletişime geçin</p>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="contact-section">
        <div class="container">
            <div class="contact-container">
                <!-- İletişim Bilgileri -->
                <div class="contact-info">
                    <h2>İletişim Bilgilerimiz</h2>
                    
                    <div class="contact-item">
                        <h3>📍 Adres</h3>
                        <p>Burmalı Mahallesi Milli Birlik Caddesi<br>
                           İl Özel İdare İşhanı Kat:6 No:702<br>
                           Merkez / AFYONKARAHİSAR</p>
                    </div>
                    
                    <div class="contact-item">
                        <h3>📞 Telefon</h3>
                        <p>0272 222 00 03</p>
                    </div>
                    
                    <div class="contact-item">
                        <h3>✉️ E-Posta</h3>
                        <p>plazaemlakyatirim@gmail.com</p>
                    </div>
                    
                    <div class="contact-item">
                        <h3>🕒 Çalışma Saatleri</h3>
                        <p>Pazartesi - Cuma: 09:00 - 19:00<br>
                           Cumartesi: 09:00 - 18:00<br>
                           Pazar: Kapalı</p>
                    </div>
                </div>
                
                <!-- Harita -->
                <div class="map-container">
                    <h2>📍 Konum</h2>
                    <div id="map">
                        <iframe 
                            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3128.1234567890123!2d30.54321!3d38.72543!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMzjCsDQzJzMxLjUiTiAzMMKwMzInMzUuNiJF!5e0!3m2!1str!2str!4v1234567890123!5m2!1str!2str"
                            width="100%" 
                            height="400" 
                            style="border:0; border-radius: 10px;" 
                            allowfullscreen="" 
                            loading="lazy">
                        </iframe>
                    </div>
                </div>
            </div>
            
            <!-- İletişim Formu -->
            <div class="contact-form">
                <h2>Bize Ulaşın</h2>
                <form action="contact-send.php" method="POST">
                    <div class="form-group">
                        <label for="name">Adınız Soyadınız</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">E-Posta Adresiniz</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Telefon Numaranız</label>
                        <input type="tel" id="phone" name="phone">
                    </div>
                    
                    <div class="form-group">
                        <label for="subject">Konu</label>
                        <input type="text" id="subject" name="subject" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="message">Mesajınız</label>
                        <textarea id="message" name="message" required></textarea>
                    </div>
                    
                    <button type="submit" class="btn-submit">Mesaj Gönder</button>
                </form>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <?php include '../includes/footer.php'; ?>
</body>
</html>