<?php
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
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
        }

        /* İstatistik Kartları */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            text-align: center;
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        .stat-icon {
            font-size: 36px;
            margin-bottom: 10px;
        }

        .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #7f8c8d;
            font-size: 14px;
        }

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

        /* Son İlanlar Tablosu */
        .recent-properties {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }

        .section-title {
            font-size: 20px;
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
            </ul>
        </nav>

        <!-- Main Content -->
        <div class="main-content">
            <div class="top-navbar">
                <div class="navbar-left">
                    <h3>Kullanıcı Paneli</h3>
                </div>
                <div class="navbar-right">
                    <span>👤 <?php echo safe_output($user_name); ?></span>
                    <a href="logout.php" class="btn-logout" onclick="return confirm('Çıkmak istediğinizden emin misiniz?')">Çıkış</a>
                </div>
            </div>

            <div class="content">
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
                </div>

                <!-- İstatistikler -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">📊</div>
                        <div class="stat-value"><?php echo intval($stats['total_properties']); ?></div>
                        <div class="stat-label">Toplam İlan</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">✅</div>
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
</body>

</html>