<?php
session_start();

// Kullanıcı girişi kontrolü
if(!isset($_SESSION['user_logged_in']) || $_SESSION['user_role'] !== 'user') {
    header("Location: index.php");
    exit();
}

require_once '../config/database.php';

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_fullname'];

// Kullanıcının istatistikleri
$stmt = $db->prepare("SELECT COUNT(*) as total FROM properties WHERE user_id = :user_id");
$stmt->execute([':user_id' => $user_id]);
$totalProperties = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $db->prepare("SELECT COUNT(*) as total FROM properties WHERE user_id = :user_id AND durum = 'aktif'");
$stmt->execute([':user_id' => $user_id]);
$activeProperties = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $db->prepare("SELECT COUNT(*) as total FROM properties WHERE user_id = :user_id AND kategori = 'Satılık'");
$stmt->execute([':user_id' => $user_id]);
$forSale = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $db->prepare("SELECT COUNT(*) as total FROM properties WHERE user_id = :user_id AND kategori = 'Kiralık'");
$stmt->execute([':user_id' => $user_id]);
$forRent = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Son eklenen ilanlar
$stmt = $db->prepare("SELECT id, baslik, fiyat, kategori, created_at 
                     FROM properties 
                     WHERE user_id = :user_id 
                     ORDER BY created_at DESC 
                     LIMIT 5");
$stmt->execute([':user_id' => $user_id]);
$recentProperties = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo htmlspecialchars($user_name); ?></title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .user-welcome {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        .user-welcome h2 {
            margin: 0 0 10px 0;
            font-size: 28px;
        }
        .user-welcome p {
            margin: 0;
            opacity: 0.9;
        }
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 30px;
        }
        .quick-action-btn {
            background: white;
            border: 2px solid #e0e0e0;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            text-decoration: none;
            color: #333;
            transition: all 0.3s;
        }
        .quick-action-btn:hover {
            border-color: #3498db;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .quick-action-btn .icon {
            font-size: 30px;
            margin-bottom: 10px;
            display: block;
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <!-- Sidebar (Kullanıcı için özel) -->
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
            </ul>
        </nav>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Top Navbar -->
            <div class="top-navbar">
                <div class="navbar-left">
                    <h3>Kullanıcı Paneli</h3>
                </div>
                <div class="navbar-right">
                    <div class="admin-info">
                        <span>👤 <?php echo htmlspecialchars($user_name); ?></span>
                    </div>
                    <a href="logout.php" class="btn-logout">Çıkış Yap</a>
                </div>
            </div>

            <!-- Content -->
            <div class="content">
                <!-- Hoşgeldin Mesajı -->
                <div class="user-welcome">
                    <h2>Hoş Geldiniz, <?php echo htmlspecialchars($user_name); ?>!</h2>
                    <p>Bugün <?php echo date('d F Y, l'); ?></p>
                </div>

                <!-- İstatistik Kartları -->
                <div class="dashboard-cards">
                    <div class="card">
                        <div class="card-icon blue">🏢</div>
                        <h4>Toplam İlanım</h4>
                        <div class="number"><?php echo $totalProperties; ?></div>
                    </div>
                    <div class="card">
                        <div class="card-icon green">✓</div>
                        <h4>Aktif İlan</h4>
                        <div class="number"><?php echo $activeProperties; ?></div>
                    </div>
                    <div class="card">
                        <div class="card-icon orange">💰</div>
                        <h4>Satılık</h4>
                        <div class="number"><?php echo $forSale; ?></div>
                    </div>
                    <div class="card">
                        <div class="card-icon red">🔑</div>
                        <h4>Kiralık</h4>
                        <div class="number"><?php echo $forRent; ?></div>
                    </div>
                </div>

                <!-- Son İlanlar -->
                <div class="card" style="margin-top: 30px;">
                    <h3 style="margin-bottom: 20px;">Son Eklediğim İlanlar</h3>
                    <?php if(count($recentProperties) > 0): ?>
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: #f8f9fa;">
                                    <th style="padding: 10px; text-align: left;">Başlık</th>
                                    <th style="padding: 10px; text-align: left;">Fiyat</th>
                                    <th style="padding: 10px; text-align: left;">Tip</th>
                                    <th style="padding: 10px; text-align: left;">Tarih</th>
                                    <th style="padding: 10px; text-align: center;">İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($recentProperties as $property): ?>
                                <tr style="border-bottom: 1px solid #dee2e6;">
                                    <td style="padding: 10px;"><?php echo htmlspecialchars($property['baslik']); ?></td>
                                    <td style="padding: 10px;"><?php echo number_format($property['fiyat'], 0, ',', '.'); ?> ₺</td>
                                    <td style="padding: 10px;">
                                        <span style="background: <?php echo $property['kategori'] == 'Satılık' ? '#f39c12' : '#e74c3c'; ?>; 
                                                     color: white; padding: 3px 8px; border-radius: 3px; font-size: 0.85rem;">
                                            <?php echo $property['kategori']; ?>
                                        </span>
                                    </td>
                                    <td style="padding: 10px;"><?php echo date('d.m.Y', strtotime($property['created_at'])); ?></td>
                                    <td style="padding: 10px; text-align: center;">
                                        <a href="my-properties-edit.php?id=<?php echo $property['id']; ?>" 
                                           style="color: #3498db; text-decoration: none;">✏️ Düzenle</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p style="text-align: center; color: #7f8c8d; padding: 40px;">
                            Henüz ilan eklenmemiş.
                        </p>
                    <?php endif; ?>
                </div>

                <!-- Hızlı İşlemler -->
                <div class="quick-actions">
                    <a href="properties/add-step1.php" class="quick-action-btn">
                        <span class="icon">➕</span>
                        <strong>Yeni İlan Ekle</strong>
                    </a>
                    <a href="my-properties.php" class="quick-action-btn">
                        <span class="icon">📋</span>
                        <strong>İlanlarımı Gör</strong>
                    </a>
                    <a href="my-profile.php" class="quick-action-btn">
                        <span class="icon">⚙️</span>
                        <strong>Profil Ayarları</strong>
                    </a>
                    <a href="../index.php" target="_blank" class="quick-action-btn">
                        <span class="icon">🌐</span>
                        <strong>Siteyi Görüntüle</strong>
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>