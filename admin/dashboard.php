<?php
<<<<<<< HEAD
header('Content-Type: text/html; charset=utf-8');
session_start();

=======
// Oturum başlat
session_start();

// Admin girişi kontrolü - BASİT VE ÇALIŞAN
>>>>>>> 1b4657b0e4a9c21769cd70a9511bc87296025d5f
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit();
}

<<<<<<< HEAD
if (isset($_SESSION['user_role']) && $_SESSION['user_role'] !== 'admin') {
    header("Location: user-dashboard.php");
    exit();
}

require_once '../config/database.php';

=======
// Veritabanı bağlantısı
require_once '../config/database.php';

// Admin bilgilerini al
>>>>>>> 1b4657b0e4a9c21769cd70a9511bc87296025d5f
$adminInfo = [
    'id' => $_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? null,
    'username' => $_SESSION['admin_username'] ?? $_SESSION['user_username'] ?? 'Admin'
];

<<<<<<< HEAD
try {
    $stmt = $db->query("SELECT COUNT(*) as total FROM properties");
    $totalProperties = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    $stmt = $db->query("SELECT COUNT(*) as total FROM properties WHERE durum = 'aktif'");
    $activeProperties = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    $stmt = $db->query("SELECT COUNT(*) as total FROM properties WHERE kategori = 'Satılık' AND durum = 'aktif'");
    $forSale = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    $stmt = $db->query("SELECT COUNT(*) as total FROM properties WHERE kategori = 'Kiralık' AND durum = 'aktif'");
    $forRent = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    $stmt = $db->query("SELECT id, baslik, fiyat, kategori, created_at FROM properties ORDER BY created_at DESC LIMIT 5");
    $recentProperties = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
=======
// İstatistikleri çek - Hata kontrolü ile
try {
    // Toplam ilan sayısı
    $stmt = $db->query("SELECT COUNT(*) as total FROM properties");
    $totalProperties = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    // Aktif ilan sayısı
    $stmt = $db->query("SELECT COUNT(*) as total FROM properties WHERE durum = 'aktif'");
    $activeProperties = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    // Satılık ilan sayısı
    $stmt = $db->query("SELECT COUNT(*) as total FROM properties WHERE kategori = 'Satılık' AND durum = 'aktif'");
    $forSale = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    // Kiralık ilan sayısı
    $stmt = $db->query("SELECT COUNT(*) as total FROM properties WHERE kategori = 'Kiralık' AND durum = 'aktif'");
    $forRent = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    // Son eklenen 5 ilan
    $stmt = $db->query("SELECT id, baslik, fiyat, kategori, created_at 
                        FROM properties 
                        ORDER BY created_at DESC 
                        LIMIT 5");
    $recentProperties = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    // Hata durumunda varsayılan değerler
>>>>>>> 1b4657b0e4a9c21769cd70a9511bc87296025d5f
    $totalProperties = 0;
    $activeProperties = 0;
    $forSale = 0;
    $forRent = 0;
    $recentProperties = [];
}

<<<<<<< HEAD
=======
// user_role kontrolü için güvenli değer
>>>>>>> 1b4657b0e4a9c21769cd70a9511bc87296025d5f
$user_role = $_SESSION['user_role'] ?? 'admin';
?>
<!DOCTYPE html>
<html lang="tr">
<<<<<<< HEAD
=======

>>>>>>> 1b4657b0e4a9c21769cd70a9511bc87296025d5f
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yönetim Paneli - Plazanet Emlak</title>
<<<<<<< HEAD
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --primary: #4F46E5;
            --primary-dark: #4338CA;
            --secondary: #EC4899;
            --success: #10B981;
            --warning: #F59E0B;
            --danger: #EF4444;
            --dark: #1F2937;
            --gray: #6B7280;
            --light: #F9FAFB;
            --white: #FFFFFF;
            --sidebar-width: 280px;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--light);
            color: var(--dark);
            line-height: 1.6;
        }
        
        .admin-container {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(180deg, #1e293b 0%, #334155 100%);
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 100;
            transition: all 0.3s ease;
        }
        
        .sidebar::-webkit-scrollbar {
            width: 6px;
        }
        
        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.2);
            border-radius: 3px;
        }
        
        .sidebar-header {
            padding: 2rem;
            background: rgba(0,0,0,0.2);
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-logo {
            color: white;
            font-size: 1.5rem;
            font-weight: 700;
            text-align: center;
            letter-spacing: 1px;
        }
        
        .sidebar-nav {
            padding: 1.5rem 0;
        }
        
        .nav-item {
            margin: 0.25rem 0;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            padding: 0.875rem 1.5rem;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.2s;
            position: relative;
            font-size: 0.95rem;
        }
        
        .nav-link:hover {
            background: rgba(255,255,255,0.1);
            color: white;
        }
        
        .nav-link.active {
            background: var(--primary);
            color: white;
        }
        
        .nav-link.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: var(--secondary);
        }
        
        .nav-icon {
            width: 20px;
            height: 20px;
            margin-right: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .nav-icon svg {
            width: 20px;
            height: 20px;
            fill: currentColor;
        }
        
        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        
        /* Header */
        .header {
            background: white;
            padding: 1.5rem 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 50;
        }
        
        .header-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--dark);
        }
        
        .header-user {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .user-info {
            text-align: right;
        }
        
        .user-name {
            font-weight: 500;
            color: var(--dark);
        }
        
        .user-role {
            font-size: 0.875rem;
            color: var(--gray);
        }
        
        .btn-logout {
            padding: 0.5rem 1rem;
            background: var(--danger);
            color: white;
            border: none;
            border-radius: 0.375rem;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .btn-logout:hover {
            background: #DC2626;
        }
        
        /* Content */
        .content {
            padding: 2rem;
            flex: 1;
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 0.75rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: transform 0.2s;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .stat-icon.blue { background: rgba(79,70,229,0.1); color: var(--primary); }
        .stat-icon.green { background: rgba(16,185,129,0.1); color: var(--success); }
        .stat-icon.orange { background: rgba(245,158,11,0.1); color: var(--warning); }
        .stat-icon.red { background: rgba(239,68,68,0.1); color: var(--danger); }
        
        .stat-content {
            flex: 1;
        }
        
        .stat-label {
            font-size: 0.875rem;
            color: var(--gray);
            margin-bottom: 0.25rem;
        }
        
        .stat-value {
            font-size: 1.875rem;
            font-weight: 700;
            color: var(--dark);
        }
        
        /* Table Section */
        .table-section {
            background: white;
            border-radius: 0.75rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 2rem;
        }
        
        .table-header {
            padding: 1.5rem;
            background: var(--light);
            border-bottom: 1px solid #E5E7EB;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .table-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--dark);
        }
        
        .btn-primary {
            padding: 0.625rem 1.25rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 0.375rem;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .btn-primary:hover {
            background: var(--primary-dark);
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        thead {
            background: var(--light);
        }
        
        th {
            padding: 0.75rem 1.5rem;
            text-align: left;
            font-weight: 600;
            color: var(--dark);
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        td {
            padding: 1rem 1.5rem;
            border-top: 1px solid #E5E7EB;
            color: var(--dark);
        }
        
        .badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-block;
        }
        
        .badge-sale {
            background: rgba(245,158,11,0.1);
            color: var(--warning);
        }
        
        .badge-rent {
            background: rgba(239,68,68,0.1);
            color: var(--danger);
        }
        
        .table-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-action {
            padding: 0.375rem 0.625rem;
            border-radius: 0.25rem;
            border: 1px solid #E5E7EB;
            background: white;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-action:hover {
            background: var(--light);
        }
        
        .empty-state {
            padding: 3rem;
            text-align: center;
            color: var(--gray);
        }
        
        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-top: 1.5rem;
        }
        
        .quick-action {
            padding: 1rem;
            background: var(--primary);
            color: white;
            border-radius: 0.5rem;
            text-align: center;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .quick-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">PLAZANET</div>
            </div>
            <nav class="sidebar-nav">
                <div class="nav-item">
                    <a href="dashboard.php" class="nav-link active">
                        <span class="nav-icon">🏠</span>
                        <span>Ana Sayfa</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="properties/list.php" class="nav-link">
                        <span class="nav-icon">🏢</span>
                        <span>İlanlar</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="properties/add.php" class="nav-link">
                        <span class="nav-icon">➕</span>
                        <span>İlan Ekle</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="users/list.php" class="nav-link">
                        <span class="nav-icon">👥</span>
                        <span>Kullanıcılar</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="settings.php" class="nav-link">
                        <span class="nav-icon">⚙️</span>
                        <span>Ayarlar</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="seo/" class="nav-link">
                        <span class="nav-icon">🎯</span>
                        <span>SEO Yönetimi</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="crm/index.php" class="nav-link">
                        <span class="nav-icon">📊</span>
                        <span>CRM Sistemi</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="sms/send.php" class="nav-link">
                        <span class="nav-icon">📱</span>
                        <span>SMS Gönder</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="sms/logs.php" class="nav-link">
                        <span class="nav-icon">📋</span>
                        <span>SMS Logları</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="portfolio/closing.php" class="nav-link">
                        <span class="nav-icon">💰</span>
                        <span>Portföy Kapatma</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="portfolio/reports.php" class="nav-link">
                        <span class="nav-icon">📈</span>
                        <span>Satış Raporları</span>
                    </a>
                </div>
                <?php if ($user_role === 'admin'): ?>
                <div class="nav-item">
                    <a href="portfolio/commission-settings.php" class="nav-link">
                        <span class="nav-icon">💵</span>
                        <span>Prim Ayarları</span>
                    </a>
                </div>
                <?php endif; ?>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <header class="header">
                <h1 class="header-title">Yönetim Paneli</h1>
                <div class="header-user">
                    <div class="user-info">
                        <div class="user-name"><?php echo htmlspecialchars($adminInfo['username']); ?></div>
                        <div class="user-role">Yönetici</div>
                    </div>
                    <button class="btn-logout" onclick="window.location.href='logout.php'">Çıkış Yap</button>
                </div>
            </header>

            <!-- Content -->
            <div class="content">
                <!-- Stats -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon blue">🏢</div>
                        <div class="stat-content">
                            <div class="stat-label">Toplam İlan</div>
                            <div class="stat-value"><?php echo $totalProperties; ?></div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon green">✅</div>
                        <div class="stat-content">
                            <div class="stat-label">Aktif İlan</div>
                            <div class="stat-value"><?php echo $activeProperties; ?></div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon orange">💰</div>
                        <div class="stat-content">
                            <div class="stat-label">Satılık</div>
                            <div class="stat-value"><?php echo $forSale; ?></div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon red">🔑</div>
                        <div class="stat-content">
                            <div class="stat-label">Kiralık</div>
                            <div class="stat-value"><?php echo $forRent; ?></div>
                        </div>
                    </div>
                </div>

                <!-- Recent Properties -->
                <div class="table-section">
                    <div class="table-header">
                        <h2 class="table-title">Son Eklenen İlanlar</h2>
                        <button class="btn-primary" onclick="window.location.href='properties/add.php'">
                            Yeni İlan Ekle
                        </button>
                    </div>
                    <?php if (count($recentProperties) > 0): ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Başlık</th>
                                    <th>Fiyat</th>
                                    <th>Kategori</th>
                                    <th>Tarih</th>
                                    <th>İşlemler</th>
=======
    <link rel="stylesheet" href="../assets/css/admin.css">
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
                    <a href="dashboard.php" class="active">
                        <span class="icon">🏠</span>
                        <span>Ana Sayfa</span>
                    </a>
                </li>
                <li>
                    <a href="properties/list.php">
                        <span class="icon">🏢</span>
                        <span>İlanlar</span>
                    </a>
                </li>
                <li>
                    <a href="properties/add.php">
                        <span class="icon">➕</span>
                        <span>İlan Ekle</span>
                    </a>
                </li>
                <li>
                    <a href="users/list.php">
                        <span class="icon">👥</span>
                        <span>Kullanıcılar</span>
                    </a>
                </li>
                <li>
                    <a href="settings.php">
                        <span class="icon">⚙️</span>
                        <span>Ayarlar</span>
                    </a>
                </li>
                <!-- SEO YÖNETİMİ -->
                <li>
                    <a href="seo/">
                        <span class="icon">🎯</span>
                        <span>SEO Yönetimi</span>
                    </a>
                </li>
                <!-- CRM SİSTEMİ -->
                <li>
                    <a href="crm/index.php">
                        <span class="icon">📊</span>
                        <span>CRM Sistemi</span>
                    </a>
                </li>
                <!-- SMS SİSTEMİ -->
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
                    <a href="portfolio/closing.php">
                        <span class="icon">💰</span>
                        <span>Portföy Kapatma</span>
                    </a>
                </li>
                <li>
                    <a href="portfolio/closing-list.php">
                        <span class="icon">📋</span>
                        <span>Kapatma Listesi</span>
                    </a>
                </li>
                <li>
                    <a href="portfolio/reports.php">
                        <span class="icon">📊</span>
                        <span>Satış Raporları</span>
                    </a>
                </li>
                <?php if ($user_role === 'admin'): ?>
                    <li>
                        <a href="portfolio/commission-settings.php">
                            <span class="icon">⚙️</span>
                            <span>Prim Ayarları</span>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Top Navbar -->
            <div class="top-navbar">
                <div class="navbar-left">
                    <h3>Yönetim Paneli</h3>
                </div>
                <div class="navbar-right">
                    <div class="admin-info">
                        <span>Hoş geldin, <strong><?php echo htmlspecialchars($adminInfo['username']); ?></strong></span>
                    </div>
                    <a href="logout.php" class="btn-logout">Çıkış Yap</a>
                </div>
            </div>

            <!-- Content -->
            <div class="content">
                <!-- İstatistik Kartları -->
                <div class="dashboard-cards">
                    <div class="card">
                        <div class="card-icon blue">🏢</div>
                        <h4>Toplam İlan</h4>
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

                <!-- Son Eklenen İlanlar -->
                <div class="card">
                    <h3 style="margin-bottom: 20px;">Son Eklenen İlanlar</h3>
                    <?php if (count($recentProperties) > 0): ?>
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: #f8f9fa;">
                                    <th style="padding: 10px; text-align: left;">Başlık</th>
                                    <th style="padding: 10px; text-align: left;">Fiyat</th>
                                    <th style="padding: 10px; text-align: left;">Tip</th>
                                    <th style="padding: 10px; text-align: left;">Tarih</th>
                                    <th style="padding: 10px; text-align: center;">İşlemler</th>
>>>>>>> 1b4657b0e4a9c21769cd70a9511bc87296025d5f
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentProperties as $property): ?>
<<<<<<< HEAD
                                <tr>
                                    <td><?php echo htmlspecialchars($property['baslik']); ?></td>
                                    <td><strong><?php echo number_format($property['fiyat'], 0, ',', '.'); ?> ₺</strong></td>
                                    <td>
                                        <span class="badge <?php echo $property['kategori'] == 'Satılık' ? 'badge-sale' : 'badge-rent'; ?>">
                                            <?php echo $property['kategori']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d.m.Y', strtotime($property['created_at'])); ?></td>
                                    <td>
                                        <div class="table-actions">
                                            <button class="btn-action" onclick="window.location.href='properties/edit.php?id=<?php echo $property['id']; ?>'">✏️</button>
                                            <button class="btn-action" onclick="window.open('../pages/detail.php?id=<?php echo $property['id']; ?>')">👁️</button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="empty-state">
                        <p>Henüz ilan eklenmemiş</p>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Quick Actions -->
                <div class="table-section">
                    <div class="table-header">
                        <h2 class="table-title">Hızlı İşlemler</h2>
                    </div>
                    <div style="padding: 1.5rem;">
                        <div class="quick-actions">
                            <a href="properties/add-step1.php" class="quick-action">➕ İlan Ekle</a>
                            <a href="properties/list.php" class="quick-action" style="background: var(--success);">📋 İlanları Gör</a>
                            <a href="../index.php" target="_blank" class="quick-action" style="background: var(--secondary);">🌐 Siteyi Görüntüle</a>
                            <a href="settings.php" class="quick-action" style="background: var(--dark);">⚙️ Ayarlar</a>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
=======
                                    <tr style="border-bottom: 1px solid #dee2e6;">
                                        <td style="padding: 10px;">
                                            <?php echo htmlspecialchars($property['baslik']); ?>
                                        </td>
                                        <td style="padding: 10px;">
                                            <?php echo number_format($property['fiyat'], 0, ',', '.'); ?> ₺
                                        </td>
                                        <td style="padding: 10px;">
                                            <span style="background: <?php echo $property['kategori'] == 'Satılık' ? '#f39c12' : '#e74c3c'; ?>; 
                                                     color: white; 
                                                     padding: 3px 8px; 
                                                     border-radius: 3px; 
                                                     font-size: 0.85rem;">
                                                <?php echo $property['kategori']; ?>
                                            </span>
                                        </td>
                                        <td style="padding: 10px;">
                                            <?php echo date('d.m.Y', strtotime($property['created_at'])); ?>
                                        </td>
                                        <td style="padding: 10px; text-align: center;">
                                            <a href="properties/edit.php?id=<?php echo $property['id']; ?>"
                                                style="color: #3498db; text-decoration: none; margin-right: 10px;">
                                                ✏️ Düzenle
                                            </a>
                                            <a href="../pages/detail.php?id=<?php echo $property['id']; ?>"
                                                target="_blank"
                                                style="color: #27ae60; text-decoration: none;">
                                                👁️ Görüntüle
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p style="color: #7f8c8d; text-align: center; padding: 20px;">
                            Henüz ilan eklenmemiş.
                        </p>
                    <?php endif; ?>

                    <div style="margin-top: 20px; text-align: center;">
                        <a href="properties/add.php" class="btn btn-primary" style="
                            background: #3498db;
                            color: white;
                            padding: 10px 20px;
                            text-decoration: none;
                            border-radius: 5px;
                            display: inline-block;">
                            Yeni İlan Ekle
                        </a>
                    </div>
                </div>

                <!-- Hızlı İşlemler -->
                <div class="card" style="margin-top: 20px;">
                    <h3 style="margin-bottom: 20px;">Hızlı İşlemler</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                        <a href="properties/add-step1.php" style="background: #3498db; color: white; padding: 15px; text-align: center; text-decoration: none; border-radius: 5px;">
                            ➕ Yeni İlan Ekle
                        </a>
                        <a href="properties/list.php" style="background: #27ae60; color: white; padding: 15px; text-align: center; text-decoration: none; border-radius: 5px;">
                            📋 Tüm İlanları Gör
                        </a>
                        <a href="../index.php" target="_blank" style="background: #9b59b6; color: white; padding: 15px; text-align: center; text-decoration: none; border-radius: 5px;">
                            🌐 Siteyi Görüntüle
                        </a>
                        <a href="settings.php" style="background: #34495e; color: white; padding: 15px; text-align: center; text-decoration: none; border-radius: 5px;">
                            ⚙️ Ayarlar
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

>>>>>>> 1b4657b0e4a9c21769cd70a9511bc87296025d5f
</html>