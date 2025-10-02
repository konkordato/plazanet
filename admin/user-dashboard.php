<?php
<<<<<<< HEAD
session_start();

// Kullanıcı girişi kontrolü
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_role'] !== 'user') {
    header("Location: index.php");
    exit();
}

require_once '../config/database.php';

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_fullname'] ?? $_SESSION['user_username'] ?? 'Kullanıcı';

// Kullanıcı bilgilerini çek
$stmt = $db->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute([':id' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Kullanıcının ilan istatistikleri
$stats = $db->prepare("
    SELECT 
        COUNT(*) as toplam_ilan,
        COUNT(CASE WHEN durum = 'aktif' THEN 1 END) as aktif_ilan,
        COUNT(CASE WHEN kategori = 'Satılık' THEN 1 END) as satilik_ilan,
        COUNT(CASE WHEN kategori = 'Kiralık' THEN 1 END) as kiralik_ilan
    FROM properties 
    WHERE user_id = :user_id
");
$stats->execute([':user_id' => $user_id]);
$istatistikler = $stats->fetch(PDO::FETCH_ASSOC);

// Son eklenen ilanlar
$son_ilanlar = $db->prepare("
    SELECT p.*, pi.image_path 
    FROM properties p
    LEFT JOIN property_images pi ON p.id = pi.property_id AND pi.is_main = 1
    WHERE p.user_id = :user_id
    ORDER BY p.created_at DESC
    LIMIT 5
");
$son_ilanlar->execute([':user_id' => $user_id]);
$recent_properties = $son_ilanlar->fetchAll(PDO::FETCH_ASSOC);
=======
// GÜVENLİK GÜNCELLEMELERİ EKLENDİ
require_once '../config/database.php';

// KULLANICI GİRİŞ KONTROLÜ - GÜVENLİ
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    session_unset();
    session_destroy();
    header("Location: index.php?error=unauthorized");
    exit();
}

// Session hijacking kontrolü
if (isset($_SESSION['user_ip']) && $_SESSION['user_ip'] !== $_SERVER['REMOTE_ADDR']) {
    session_unset();
    session_destroy();
    header("Location: index.php?error=session_hijacked");
    exit();
}

// Session timeout kontrolü (30 dakika)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    session_unset();
    session_destroy();
    header("Location: index.php?error=session_timeout");
    exit();
}
$_SESSION['last_activity'] = time();

// Rol kontrolü - Admin ise admin paneline yönlendir
if ($_SESSION['user_role'] === 'admin') {
    header("Location: dashboard.php");
    exit();
}

// Normal kullanıcı değilse index'e yönlendir
if ($_SESSION['user_role'] !== 'user') {
    header("Location: index.php?error=invalid_role");
    exit();
}

// Kullanıcı bilgilerini güvenli al
$user_id = intval($_SESSION['user_id']); // SQL Injection koruması
$user_name = htmlspecialchars($_SESSION['user_fullname'], ENT_QUOTES, 'UTF-8');
$username = htmlspecialchars($_SESSION['user_username'], ENT_QUOTES, 'UTF-8');

// CSRF Token oluştur (form işlemleri için)
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

try {
    // Kullanıcının ilanlarını güvenli şekilde çek (SQL Injection koruması)
    $query = "SELECT p.*, pi.image_path 
              FROM properties p 
              LEFT JOIN property_images pi ON p.id = pi.property_id AND pi.is_main = 1
              WHERE p.user_id = :user_id
              ORDER BY p.created_at DESC
              LIMIT 5";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $recent_properties = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // İstatistikleri güvenli şekilde çek
    $stats_query = "SELECT 
        COUNT(*) as total_properties,
        SUM(CASE WHEN durum = 'aktif' THEN 1 ELSE 0 END) as active_properties,
        SUM(CASE WHEN durum = 'pasif' THEN 1 ELSE 0 END) as passive_properties
        FROM properties WHERE user_id = :user_id";
    $stats_stmt = $db->prepare($stats_query);
    $stats_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stats_stmt->execute();
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    // Hata durumunda varsayılan değerler (hata detayını gösterme)
    $recent_properties = [];
    $stats = [
        'total_properties' => 0,
        'active_properties' => 0,
        'passive_properties' => 0
    ];
    
    // Hata logu (opsiyonel - canlıda aktif edilmeli)
    error_log("Dashboard Error for User $user_id: " . $e->getMessage());
}

// Mesajları güvenli şekilde göster ve temizle
$success = isset($_SESSION['success']) ? htmlspecialchars($_SESSION['success'], ENT_QUOTES, 'UTF-8') : '';
$error = isset($_SESSION['error']) ? htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8') : '';
unset($_SESSION['success'], $_SESSION['error']);

// XSS koruması için yardımcı fonksiyon
function safe_output($data) {
    return htmlspecialchars($data ?? '', ENT_QUOTES, 'UTF-8');
}
>>>>>>> 1b4657b0e4a9c21769cd70a9511bc87296025d5f
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<<<<<<< HEAD
    <title>Kullanıcı Paneli - <?php echo htmlspecialchars($user_name); ?></title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        /* Profil Kartı Stili */
        .profile-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .profile-info {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .profile-details {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .profile-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            border: 4px solid rgba(255, 255, 255, 0.3);
            object-fit: cover;
        }

        .no-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            border: 4px solid rgba(255, 255, 255, 0.3);
        }

        .profile-text h2 {
            margin: 0 0 5px 0;
            font-size: 24px;
        }

        .profile-text p {
            margin: 0;
            opacity: 0.9;
        }

        .profile-actions {
            display: flex;
            gap: 10px;
        }

        .btn-profile {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            border: 1px solid rgba(255, 255, 255, 0.3);
            transition: all 0.3s;
        }

        .btn-profile:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
=======
    <meta name="robots" content="noindex, nofollow">
    <meta http-equiv="X-Frame-Options" content="SAMEORIGIN">
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-XSS-Protection" content="1; mode=block">
    
    <title>Kullanıcı Paneli - <?php echo safe_output($user_name); ?></title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    
    <!-- CSRF Token for AJAX requests -->
    <meta name="csrf-token" content="<?php echo $csrf_token; ?>">
    
    <style>
        /* Dashboard özel stilleri */
        .dashboard-welcome {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
        }

        .dashboard-welcome h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }

        .dashboard-welcome p {
            opacity: 0.9;
            font-size: 16px;
>>>>>>> 1b4657b0e4a9c21769cd70a9511bc87296025d5f
        }

        /* İstatistik Kartları */
        .stats-grid {
            display: grid;
<<<<<<< HEAD
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
=======
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
>>>>>>> 1b4657b0e4a9c21769cd70a9511bc87296025d5f
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
<<<<<<< HEAD
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
=======
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
>>>>>>> 1b4657b0e4a9c21769cd70a9511bc87296025d5f
            text-align: center;
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
<<<<<<< HEAD
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
        }

        .stat-icon {
            font-size: 30px;
=======
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        .stat-icon {
            font-size: 36px;
>>>>>>> 1b4657b0e4a9c21769cd70a9511bc87296025d5f
            margin-bottom: 10px;
        }

        .stat-value {
<<<<<<< HEAD
            font-size: 28px;
            font-weight: bold;
            color: #2c3e50;
            margin: 10px 0;
=======
            font-size: 32px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
>>>>>>> 1b4657b0e4a9c21769cd70a9511bc87296025d5f
        }

        .stat-label {
            color: #7f8c8d;
            font-size: 14px;
        }

<<<<<<< HEAD
=======
        /* Hızlı Erişim Butonları */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .quick-action-btn {
            background: white;
            padding: 20px;
            border-radius: 10px;
            text-decoration: none;
            color: #333;
            text-align: center;
            transition: all 0.3s;
            border: 2px solid transparent;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .quick-action-btn:hover {
            border-color: #667eea;
            transform: translateY(-3px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        .quick-action-icon {
            font-size: 32px;
            margin-bottom: 10px;
            display: block;
        }

        .quick-action-title {
            font-weight: 600;
            font-size: 16px;
        }

>>>>>>> 1b4657b0e4a9c21769cd70a9511bc87296025d5f
        /* Son İlanlar Tablosu */
        .recent-properties {
            background: white;
            border-radius: 10px;
            padding: 25px;
<<<<<<< HEAD
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
=======
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
>>>>>>> 1b4657b0e4a9c21769cd70a9511bc87296025d5f
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
<<<<<<< HEAD
=======
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
>>>>>>> 1b4657b0e4a9c21769cd70a9511bc87296025d5f
        }

        .section-title {
            font-size: 20px;
<<<<<<< HEAD
            color: #2c3e50;
        }

        .btn-view-all {
            color: #3498db;
            text-decoration: none;
            font-size: 14px;
        }

        .btn-view-all:hover {
            text-decoration: underline;
        }

        .properties-table {
            width: 100%;
        }

        .properties-table th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-size: 14px;
            color: #666;
        }

        .properties-table td {
            padding: 12px;
            border-bottom: 1px solid #f0f0f0;
        }

        .property-thumb {
            width: 50px;
            height: 40px;
            object-fit: cover;
            border-radius: 4px;
        }

        .no-image-thumb {
            width: 50px;
            height: 40px;
            background: #f0f0f0;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #999;
            font-size: 18px;
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
        }

        .status-active {
            background: #d4edda;
            color: #155724;
        }

        .status-passive {
            background: #f8d7da;
            color: #721c24;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
        }

        .quick-links {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 30px;
        }

        .quick-link {
            background: white;
            border: 2px solid #e0e0e0;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            text-decoration: none;
            color: #333;
            transition: all 0.3s;
        }

        .quick-link:hover {
            border-color: #3498db;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.2);
        }

        .quick-link-icon {
            font-size: 30px;
            margin-bottom: 10px;
        }

        .quick-link-title {
            font-weight: 600;
=======
            font-weight: 600;
            color: #2c3e50;
        }

        .view-all-link {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
        }

        .view-all-link:hover {
            text-decoration: underline;
        }

        /* İlan Listesi */
        .property-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .property-item {
            display: flex;
            gap: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            align-items: center;
            transition: background 0.3s;
        }

        .property-item:hover {
            background: #e9ecef;
        }

        .property-thumb {
            width: 80px;
            height: 60px;
            object-fit: cover;
            border-radius: 5px;
            flex-shrink: 0;
        }

        .no-image {
            width: 80px;
            height: 60px;
            background: #dee2e6;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #adb5bd;
            flex-shrink: 0;
        }

        .property-info {
            flex: 1;
        }

        .property-title {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .property-meta {
            color: #7f8c8d;
            font-size: 14px;
        }

        .property-price {
            font-weight: bold;
            color: #27ae60;
            font-size: 18px;
        }

        .property-actions {
            display: flex;
            gap: 8px;
        }

        .btn-small {
            padding: 6px 12px;
            font-size: 12px;
            border-radius: 5px;
            text-decoration: none;
            transition: all 0.3s;
        }

        .btn-edit {
            background: #f39c12;
            color: white;
        }

        .btn-view {
            background: #3498db;
            color: white;
        }

        /* Boş durum */
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
        }

        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.5;
        }

        /* Mesajlar */
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            animation: slideIn 0.3s;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Güvenlik için logout butonu */
        .btn-logout {
            background: #dc3545;
            color: white;
            padding: 8px 16px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 14px;
            transition: background 0.3s;
        }
        
        .btn-logout:hover {
            background: #c82333;
>>>>>>> 1b4657b0e4a9c21769cd70a9511bc87296025d5f
        }
    </style>
</head>

<body>
    <div class="admin-wrapper">
        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="sidebar-header">
                <h2>PLAZANET</h2>
            </div>
            <ul class="sidebar-menu">
                <li>
                    <a href="user-dashboard.php" class="active">
                        <span class="icon">🏠</span>
                        <span>Ana Sayfa</span>
                    </a>
                </li>
                <li>
                    <a href="my-properties.php">
                        <span class="icon">🏢</span>
                        <span>İlanlarım</span>
                    </a>
                </li>
                <li>
                    <a href="properties/add-step1.php">
                        <span class="icon">➕</span>
                        <span>İlan Ekle</span>
                    </a>
                </li>
                <li>
<<<<<<< HEAD
=======
                    <a href="crm/index.php">
                        <span class="icon">📊</span>
                        <span>CRM Sistemi</span>
                    </a>
                </li>
                <!-- SMS SİSTEMİ MENÜSÜ -->
                <li>
                    <a href="sms/send.php">
                        <span class="icon">📤</span>
                        <span>SMS Gönder</span>
                    </a>
                </li>
                <li>
                    <a href="sms/logs.php">
                        <span class="icon">📋</span>
                        <span>SMS Logları</span>
                    </a>
                </li>
                <li>
                    <a href="sms/settings.php">
                        <span class="icon">⚙️</span>
                        <span>SMS Ayarları</span>
                    </a>
                </li>
                <li>
                    <a href="my-profile.php">
                        <span class="icon">👤</span>
                        <span>Profilim</span>
                    </a>
                </li>
                <li>
>>>>>>> 1b4657b0e4a9c21769cd70a9511bc87296025d5f
                    <a href="portfolio/closing.php">
                        <span class="icon">💰</span>
                        <span>Portföy Kapatma</span>
                    </a>
                </li>
                <li>
                    <a href="portfolio/my-reports.php">
                        <span class="icon">📊</span>
                        <span>Satış Raporlarım</span>
                    </a>
                </li>
<<<<<<< HEAD
                <li>
                    <a href="my-profile.php">
                        <span class="icon">👤</span>
                        <span>Profilim</span>
                    </a>
                </li>
                <?php if (file_exists('crm/index.php')): ?>
                    <li>
                        <a href="crm/index.php">
                            <span class="icon">📊</span>
                            <span>CRM Sistemi</span>
                        </a>
                    </li>
                <?php endif; ?>
=======
>>>>>>> 1b4657b0e4a9c21769cd70a9511bc87296025d5f
            </ul>
        </nav>

        <!-- Main Content -->
        <div class="main-content">
            <div class="top-navbar">
                <div class="navbar-left">
                    <h3>Kullanıcı Paneli</h3>
                </div>
                <div class="navbar-right">
<<<<<<< HEAD
                    <span>👤 <?php echo htmlspecialchars($user_name); ?></span>
                    <a href="logout.php" class="btn-logout">Çıkış</a>
=======
                    <span>👤 <?php echo safe_output($user_name); ?></span>
                    <a href="logout.php" class="btn-logout" onclick="return confirm('Çıkmak istediğinizden emin misiniz?')">Çıkış</a>
>>>>>>> 1b4657b0e4a9c21769cd70a9511bc87296025d5f
                </div>
            </div>

            <div class="content">
<<<<<<< HEAD
                <!-- Profil Kartı -->
                <div class="profile-card">
                    <div class="profile-info">
                        <div class="profile-details">
                            <?php if ($user['profile_image']): ?>
                                <img src="../<?php echo $user['profile_image']; ?>" alt="Profil" class="profile-avatar">
                            <?php else: ?>
                                <div class="no-avatar">👤</div>
                            <?php endif; ?>
                            <div class="profile-text">
                                <h2>Hoşgeldiniz, <?php echo htmlspecialchars($user['full_name']); ?></h2>
                                <p>📧 <?php echo htmlspecialchars($user['email']); ?></p>
                                <p>📱 <?php echo htmlspecialchars($user['phone'] ?: 'Telefon belirtilmemiş'); ?></p>
                            </div>
                        </div>
                        <div class="profile-actions">
                            <a href="my-profile.php" class="btn-profile">
                                ✏️ Profili Düzenle
                            </a>
                            <a href="my-properties.php" class="btn-profile">
                                🏢 İlanlarım
                            </a>
                        </div>
                    </div>
=======
                <!-- Mesajlar -->
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>

                <!-- Hoşgeldin Mesajı -->
                <div class="dashboard-welcome">
                    <h1>Hoş Geldiniz, <?php echo safe_output($user_name); ?>!</h1>
                    <p>Kullanıcı panelinizden ilanlarınızı yönetebilir, yeni ilan ekleyebilirsiniz.</p>
>>>>>>> 1b4657b0e4a9c21769cd70a9511bc87296025d5f
                </div>

                <!-- İstatistikler -->
                <div class="stats-grid">
                    <div class="stat-card">
<<<<<<< HEAD
                        <div class="stat-icon">📋</div>
                        <div class="stat-value"><?php echo $istatistikler['toplam_ilan']; ?></div>
=======
                        <div class="stat-icon">📊</div>
                        <div class="stat-value"><?php echo intval($stats['total_properties']); ?></div>
>>>>>>> 1b4657b0e4a9c21769cd70a9511bc87296025d5f
                        <div class="stat-label">Toplam İlan</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">✅</div>
<<<<<<< HEAD
                        <div class="stat-value"><?php echo $istatistikler['aktif_ilan']; ?></div>
                        <div class="stat-label">Aktif İlan</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">🏷️</div>
                        <div class="stat-value"><?php echo $istatistikler['satilik_ilan']; ?></div>
                        <div class="stat-label">Satılık</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">🔑</div>
                        <div class="stat-value"><?php echo $istatistikler['kiralik_ilan']; ?></div>
                        <div class="stat-label">Kiralık</div>
                    </div>
                </div>

                <!-- Son İlanlar -->
                <div class="recent-properties">
                    <div class="section-header">
                        <h3 class="section-title">Son İlanlarım</h3>
                        <a href="my-properties.php" class="btn-view-all">Tümünü Gör →</a>
                    </div>

                    <?php if (count($recent_properties) > 0): ?>
                        <table class="properties-table">
                            <thead>
                                <tr>
                                    <th>Resim</th>
                                    <th>Başlık</th>
                                    <th>Fiyat</th>
                                    <th>Durum</th>
                                    <th>Tarih</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_properties as $prop): ?>
                                    <tr>
                                        <td>
                                            <?php if ($prop['image_path']): ?>
                                                <img src="../<?php echo $prop['image_path']; ?>" class="property-thumb" alt="">
                                            <?php else: ?>
                                                <div class="no-image-thumb">📷</div>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($prop['baslik']); ?></td>
                                        <td><?php echo number_format($prop['fiyat'], 0, ',', '.'); ?> ₺</td>
                                        <td>
                                            <span class="status-badge <?php echo $prop['durum'] == 'aktif' ? 'status-active' : 'status-passive'; ?>">
                                                <?php echo ucfirst($prop['durum']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d.m.Y', strtotime($prop['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state">
                            <p>Henüz ilan eklenmemiş</p>
                            <a href="properties/add-step1.php" style="color: #3498db;">İlk ilanınızı ekleyin →</a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Hızlı Erişim -->
                <div class="quick-links">
                    <a href="properties/add-step1.php" class="quick-link">
                        <div class="quick-link-icon">➕</div>
                        <div class="quick-link-title">Yeni İlan Ekle</div>
                    </a>
                    <a href="my-properties.php" class="quick-link">
                        <div class="quick-link-icon">📋</div>
                        <div class="quick-link-title">İlanlarımı Yönet</div>
                    </a>
                    <a href="my-profile.php" class="quick-link">
                        <div class="quick-link-icon">⚙️</div>
                        <div class="quick-link-title">Ayarlar</div>
                    </a>
                    <a href="../index.php" target="_blank" class="quick-link">
                        <div class="quick-link-icon">🌐</div>
                        <div class="quick-link-title">Siteyi Görüntüle</div>
                    </a>
                </div>
            </div>
        </div>
    </div>
=======
                        <div class="stat-value"><?php echo intval($stats['active_properties']); ?></div>
                        <div class="stat-label">Aktif İlan</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">⏸️</div>
                        <div class="stat-value"><?php echo intval($stats['passive_properties']); ?></div>
                        <div class="stat-label">Pasif İlan</div>
                    </div>
                </div>

                <!-- Hızlı Erişim -->
                <div class="quick-actions">
                    <a href="properties/add-step1.php" class="quick-action-btn">
                        <span class="quick-action-icon">➕</span>
                        <span class="quick-action-title">Yeni İlan Ekle</span>
                    </a>
                    <a href="my-properties.php" class="quick-action-btn">
                        <span class="quick-action-icon">📋</span>
                        <span class="quick-action-title">İlanlarımı Gör</span>
                    </a>
                    <a href="my-profile.php" class="quick-action-btn">
                        <span class="quick-action-icon">👤</span>
                        <span class="quick-action-title">Profilimi Düzenle</span>
                    </a>
                    <a href="../index.php" target="_blank" class="quick-action-btn" rel="noopener noreferrer">
                        <span class="quick-action-icon">🌐</span>
                        <span class="quick-action-title">Siteyi Görüntüle</span>
                    </a>
                </div>

                <!-- Son İlanlar -->
                <div class="recent-properties">
                    <div class="section-header">
                        <h2 class="section-title">Son Eklenen İlanlarım</h2>
                        <?php if (count($recent_properties) > 0): ?>
                            <a href="my-properties.php" class="view-all-link">Tümünü Gör →</a>
                        <?php endif; ?>
                    </div>

                    <?php if (count($recent_properties) > 0): ?>
                        <div class="property-list">
                            <?php foreach ($recent_properties as $property): ?>
                                <div class="property-item">
                                    <?php if (!empty($property['image_path'])): ?>
                                        <img src="../<?php echo safe_output($property['image_path']); ?>"
                                            alt="<?php echo safe_output($property['baslik'] ?? $property['title'] ?? ''); ?>"
                                            class="property-thumb"
                                            onerror="this.onerror=null; this.src='../assets/images/no-image.jpg';">
                                    <?php else: ?>
                                        <div class="no-image">📷</div>
                                    <?php endif; ?>

                                    <div class="property-info">
                                        <div class="property-title">
                                            <?php echo safe_output($property['baslik'] ?? $property['title'] ?? 'İsimsiz İlan'); ?>
                                        </div>
                                        <div class="property-meta">
                                            📍 <?php echo safe_output(($property['ilce'] ?? '') . ', ' . ($property['mahalle'] ?? '')); ?>
                                            | 🏠 <?php echo safe_output($property['oda_sayisi'] ?? ''); ?>
                                            | 📐 <?php echo safe_output($property['metrekare'] ?? ''); ?>m²
                                        </div>
                                    </div>

                                    <div class="property-price">
                                        <?php echo number_format(floatval($property['fiyat'] ?? $property['price'] ?? 0), 0, ',', '.'); ?> ₺
                                    </div>

                                    <div class="property-actions">
                                        <a href="my-property-edit.php?id=<?php echo intval($property['id']); ?>" 
                                           class="btn-small btn-edit">Düzenle</a>
                                        <a href="../pages/detail.php?id=<?php echo intval($property['id']); ?>" 
                                           target="_blank" 
                                           rel="noopener noreferrer"
                                           class="btn-small btn-view">Görüntüle</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">🔭</div>
                            <h3>Henüz ilan eklenmemiş</h3>
                            <p>Hemen ilk ilanınızı ekleyin!</p>
                            <a href="properties/add-step1.php" class="btn btn-success" style="margin-top: 20px;">
                                ➕ İlk İlanımı Ekle
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Otomatik çıkış için zamanlayıcı (30 dakika)
        let inactivityTimer;
        
        function resetTimer() {
            clearTimeout(inactivityTimer);
            inactivityTimer = setTimeout(function() {
                alert('Oturumunuz güvenlik nedeniyle sonlandırıldı.');
                window.location.href = 'logout.php';
            }, 30 * 60 * 1000); // 30 dakika
        }
        
        // Kullanıcı aktivitelerini izle
        document.addEventListener('mousemove', resetTimer);
        document.addEventListener('keypress', resetTimer);
        document.addEventListener('click', resetTimer);
        document.addEventListener('scroll', resetTimer);
        
        // Sayfa yüklendiğinde zamanlayıcıyı başlat
        resetTimer();
        
        // XSS koruması için konsol uyarısı
        console.log('%cDUR!', 'color: red; font-size: 50px; font-weight: bold;');
        console.log('%cBu tarayıcı konsolu geliştiriciler içindir. Buraya yapıştırılan kodlar hesabınızın güvenliğini tehlikeye atabilir!', 'color: red; font-size: 16px;');
    </script>
>>>>>>> 1b4657b0e4a9c21769cd70a9511bc87296025d5f
</body>

</html>