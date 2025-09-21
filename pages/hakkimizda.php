<?php
require_once '../config/database.php';

// DANIŞMANLARI USERS TABLOSUNDAN ÇEK
// Aktif durumda olan ve danışman olan kullanıcıları getir
$stmt = $db->query("SELECT * FROM users WHERE status = 'active' ORDER BY id ASC");
$consultants = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hakkımızda - Plaza Emlak & Yatırım</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/pages.css">
    <style>
        .about-content {
            background: white;
            padding: 3rem;
            border-radius: 10px;
            margin-bottom: 3rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .about-content h2 {
            color: #2c3e50;
            margin-bottom: 2rem;
            font-size: 2rem;
            text-align: center;
            border-bottom: 3px solid #3498db;
            padding-bottom: 1rem;
        }
        .about-content p {
            line-height: 1.8;
            color: #555;
            margin-bottom: 1.5rem;
            text-align: justify;
        }
        .consultants-section {
            margin-top: 3rem;
        }
        .consultants-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }
        .consultant-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s;
            text-align: center;
        }
        .consultant-card:hover {
            transform: translateY(-5px);
        }
        .consultant-image {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            margin: 0 auto 1rem;
            display: block;
            object-fit: cover;
            border: 3px solid #3498db;
        }
        .consultant-avatar {
            width: 120px;
            height: 120px;
            background: #3498db;
            color: white;
            border-radius: 50%;
            margin: 0 auto 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            font-weight: bold;
        }
        .consultant-name {
            color: #2c3e50;
            text-align: center;
            margin-bottom: 0.5rem;
            font-size: 1.2rem;
        }
        .consultant-title {
            color: #7f8c8d;
            text-align: center;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }
        .consultant-contact {
            border-top: 1px solid #eee;
            padding-top: 1rem;
            margin-top: 1rem;
        }
        .consultant-contact p {
            font-size: 0.9rem;
            color: #555;
            margin: 0.5rem 0;
        }
        .consultant-bio {
            text-align: center;
            color: #666;
            font-size: 0.9rem;
            margin: 1rem 0;
            line-height: 1.6;
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
                </div>
                <ul class="nav-menu">
                    <li><a href="../index.php">Ana Sayfa</a></li>
                    <li><a href="satilik.php">Satılık</a></li>
                    <li><a href="kiralik.php">Kiralık</a></li>
                    <li><a href="hakkimizda.php" class="active">Hakkımızda</a></li>
                    <li><a href="iletisim.php">İletişim</a></li>
                    <li><a href="../admin/" class="admin-btn">Yönetim</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <!-- Page Header -->
    <section class="page-header">
        <div class="container">
            <h1>Hakkımızda</h1>
            <p>Plaza Emlak & Yatırım - Güvenin Adresi</p>
        </div>
    </section>

    <!-- About Content -->
    <section class="about-section">
        <div class="container">
            <div class="about-content">
                <h2>PLAZA EMLAK YATIRIM AFYONKARAHİSAR'DA HİZMET VEREN BİR EMLAK MARKASIDIR</h2>
                
                <p>Bizler tamamı üniversite mezunu ve sertifikalarını tamamen almış, merkezine müşteri memnuniyeti ve inovatif emlak hizmetlerini koyarak bir bütün olarak emlak danışmanlığı ve emlak hizmetlerini kurumsal olarak işleyen bir şirketiz.</p>
                
                <p>Müşterilerimize güvenli ve şeffaf emlak hizmetlerini sunmanın ayrıcalığını yaşatmayı misyon edinmiş şirketimizde yapılan her işlem sözleşmelerle gerçekleştirilir. Kiralama, satış ve alış işlemlerinde bu sistematik işleyiş daima müşterilerimizin güvenliğini sağlamayı amaçlar.</p>
                
                <p>Müşterilerimizi evrak güvenliği ile koruruz. Kiracılarımızın her sorunuyla ilgili 7/24 hizmet anlayışına sahibiz. Kiralamalarda yıllarca süren hizmet birlikteliklerini tesis ederek, ev sahiplerimizin bütün hukuki haklarını korumayı amaçlarız.</p>
                
                <p>Plaza Emlak Yatırım sizlere doğru taşınmaz yatırım araçlarında yardımcı olmak için gece gündüz çalışır.</p>
                
                <p><strong>Yerel Müşterilerimize Global Standartlarda Hizmet sağlayan Plaza Emlak Yatırım olarak Kurumsal ve İnovatif Emlak Hizmetleri için bizi arayın.</strong></p>
            </div>

            <!-- DANIŞMANLAR BÖLÜMÜ - USERS TABLOSUNDAN VERİ ÇEKİYOR -->
            <div class="consultants-section">
                <div class="about-content">
                    <h2>DANIŞMANLARIMIZ</h2>
                    
                    <?php if(count($consultants) > 0): ?>
                        <div class="consultants-grid">
                            <?php foreach($consultants as $consultant): ?>
                                <?php
                                // İsmin baş harflerini al (avatar için)
                                $nameParts = explode(' ', $consultant['full_name']);
                                $initials = '';
                                foreach($nameParts as $part) {
                                    if(!empty($part)) {
                                        $initials .= mb_substr($part, 0, 1, 'UTF-8');
                                    }
                                }
                                $initials = strtoupper($initials);
                                ?>
                                <div class="consultant-card">
                                    <?php if(!empty($consultant['profile_image'])): ?>
                                        <img src="../<?php echo $consultant['profile_image']; ?>" 
                                             alt="<?php echo htmlspecialchars($consultant['full_name']); ?>" 
                                             class="consultant-image">
                                    <?php else: ?>
                                        <div class="consultant-avatar">
                                            <?php echo $initials; ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <h3 class="consultant-name"><?php echo htmlspecialchars($consultant['full_name']); ?></h3>
                                    
                                    <?php if(!empty($consultant['title'])): ?>
                                        <p class="consultant-title"><?php echo htmlspecialchars($consultant['title']); ?></p>
                                    <?php else: ?>
                                        <p class="consultant-title">Gayrimenkul Danışmanı</p>
                                    <?php endif; ?>
                                    
                                    <?php if(!empty($consultant['bio'])): ?>
                                        <p class="consultant-bio"><?php echo nl2br(htmlspecialchars($consultant['bio'])); ?></p>
                                    <?php endif; ?>
                                    
                                    <div class="consultant-contact">
                                        <?php if(!empty($consultant['phone'])): ?>
                                            <p>📞 <?php echo htmlspecialchars($consultant['phone']); ?></p>
                                        <?php endif; ?>
                                        
                                        <?php if(!empty($consultant['mobile'])): ?>
                                            <p>📱 <?php echo htmlspecialchars($consultant['mobile']); ?></p>
                                        <?php endif; ?>
                                        
                                        <?php if(!empty($consultant['email'])): ?>
                                            <p>✉️ <?php echo htmlspecialchars($consultant['email']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p style="text-align: center; color: #999; margin: 2rem 0;">
                            Danışman bilgileri yakında eklenecektir.
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <?php include '../includes/footer.php'; ?>
</body>
</html>