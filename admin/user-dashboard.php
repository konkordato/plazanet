<?php
session_start();

// KULLANICI GİRİŞİ KONTROLÜ - ÖNEMLİ DÜZELTME!
// Kullanıcı giriş yapmamışsa index.php'ye yönlendir
if(!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: index.php");
    exit();
}

// Eğer kullanıcı rolü 'user' değilse ve 'admin' ise admin paneline yönlendir
if($_SESSION['user_role'] === 'admin') {
    header("Location: dashboard.php");
    exit();
}

// Normal kullanıcı değilse de index'e yönlendir
if($_SESSION['user_role'] !== 'user') {
    header("Location: index.php");
    exit();
}

require_once '../config/database.php';

// Kullanıcı bilgileri
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_fullname'];
$username = $_SESSION['user_username'];

// Kullanıcının ilanlarını çek (son 5 ilan)
$query = "SELECT p.*, pi.image_path 
          FROM properties p 
          LEFT JOIN property_images pi ON p.id = pi.property_id AND pi.is_main = 1
          WHERE p.user_id = :user_id
          ORDER BY p.created_at DESC
          LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute([':user_id' => $user_id]);
$recent_properties = $stmt->fetchAll(PDO::FETCH_ASSOC);

// İstatistikleri çek
$stats_query = "SELECT 
    COUNT(*) as total_properties,
    SUM(CASE WHEN durum = 'aktif' THEN 1 ELSE 0 END) as active_properties,
    SUM(CASE WHEN durum = 'pasif' THEN 1 ELSE 0 END) as passive_properties
    FROM properties WHERE user_id = :user_id";
$stats_stmt = $db->prepare($stats_query);
$stats_stmt->execute([':user_id' => $user_id]);
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

// Mesajları göster
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kullanıcı Paneli - <?php echo htmlspecialchars($user_name); ?></title>
    <link rel="stylesheet" href="../assets/css/admin.css">
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
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            text-align: center;
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
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
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .quick-action-btn:hover {
            border-color: #667eea;
            transform: translateY(-3px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
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
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
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
                    <span>👤 <?php echo htmlspecialchars($user_name); ?></span>
                    <a href="logout.php" class="btn-logout">Çıkış</a>
                </div>
            </div>

            <div class="content">
                <!-- Mesajlar -->
                <?php if($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                <?php if($error): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>

                <!-- Hoşgeldin Mesajı -->
                <div class="dashboard-welcome">
                    <h1>Hoş Geldiniz, <?php echo htmlspecialchars($user_name); ?>!</h1>
                    <p>Kullanıcı panelinizden ilanlarınızı yönetebilir, yeni ilan ekleyebilirsiniz.</p>
                </div>

                <!-- İstatistikler -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">📊</div>
                        <div class="stat-value"><?php echo $stats['total_properties'] ?? 0; ?></div>
                        <div class="stat-label">Toplam İlan</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">✅</div>
                        <div class="stat-value"><?php echo $stats['active_properties'] ?? 0; ?></div>
                        <div class="stat-label">Aktif İlan</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">⏸️</div>
                        <div class="stat-value"><?php echo $stats['passive_properties'] ?? 0; ?></div>
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
                    <a href="../index.php" target="_blank" class="quick-action-btn">
                        <span class="quick-action-icon">🌐</span>
                        <span class="quick-action-title">Siteyi Görüntüle</span>
                    </a>
                </div>

                <!-- Son İlanlar -->
                <div class="recent-properties">
                    <div class="section-header">
                        <h2 class="section-title">Son Eklenen İlanlarım</h2>
                        <?php if(count($recent_properties) > 0): ?>
                            <a href="my-properties.php" class="view-all-link">Tümünü Gör →</a>
                        <?php endif; ?>
                    </div>

                    <?php if(count($recent_properties) > 0): ?>
                        <div class="property-list">
                            <?php foreach($recent_properties as $property): ?>
                            <div class="property-item">
                                <?php if($property['image_path']): ?>
                                    <img src="../<?php echo $property['image_path']; ?>" 
                                         alt="<?php echo htmlspecialchars($property['baslik'] ?? $property['title'] ?? ''); ?>" 
                                         class="property-thumb">
                                <?php else: ?>
                                    <div class="no-image">📷</div>
                                <?php endif; ?>
                                
                                <div class="property-info">
                                    <div class="property-title">
                                        <?php echo htmlspecialchars($property['baslik'] ?? $property['title'] ?? 'İsimsiz İlan'); ?>
                                    </div>
                                    <div class="property-meta">
                                        📍 <?php echo htmlspecialchars(($property['ilce'] ?? '') . ', ' . ($property['mahalle'] ?? '')); ?> 
                                        | 🏠 <?php echo $property['oda_sayisi'] ?? ''; ?> 
                                        | 📐 <?php echo $property['metrekare'] ?? ''; ?>m²
                                    </div>
                                </div>
                                
                                <div class="property-price">
                                    <?php echo number_format($property['fiyat'] ?? $property['price'] ?? 0, 0, ',', '.'); ?> ₺
                                </div>
                                
                                <div class="property-actions">
                                    <a href="my-property-edit.php?id=<?php echo $property['id']; ?>" class="btn-small btn-edit">Düzenle</a>
                                    <a href="../pages/detail.php?id=<?php echo $property['id']; ?>" target="_blank" class="btn-small btn-view">Görüntüle</a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">📭</div>
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
</body>
</html>