<?php
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
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
        }

        /* İstatistik Kartları */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            text-align: center;
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
        }

        .stat-icon {
            font-size: 30px;
            margin-bottom: 10px;
        }

        .stat-value {
            font-size: 28px;
            font-weight: bold;
            color: #2c3e50;
            margin: 10px 0;
        }

        .stat-label {
            color: #7f8c8d;
            font-size: 14px;
        }

        /* Son İlanlar Tablosu */
        .recent-properties {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .section-title {
            font-size: 20px;
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
                </div>

                <!-- İstatistikler -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">📋</div>
                        <div class="stat-value"><?php echo $istatistikler['toplam_ilan']; ?></div>
                        <div class="stat-label">Toplam İlan</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">✅</div>
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
</body>

</html>