<?php
// admin/seo/index.php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: ../index.php");
    exit();
}

require_once '../../config/database.php';

// Mesajları al
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

// SEO ayarlarını çek
$stmt = $db->query("SELECT * FROM seo_settings ORDER BY page_name");
$pages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// İstatistikler
$stats = $db->query("
    SELECT 
        (SELECT COUNT(*) FROM properties) as total_properties,
        (SELECT COUNT(*) FROM property_seo) as seo_properties,
        (SELECT COUNT(*) FROM seo_settings) as total_pages
")->fetch(PDO::FETCH_ASSOC);

$seo_coverage = $stats['total_properties'] > 0 
    ? round(($stats['seo_properties'] / $stats['total_properties']) * 100, 1) 
    : 0;
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEO Yönetimi - Admin Panel</title>
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <style>
        /* Admin wrapper düzeltme */
        .admin-wrapper {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar sabit */
        .sidebar {
            width: 250px;
            position: fixed;
            height: 100vh;
            background: #2c3e50;
            overflow-y: auto;
        }
        
        /* Ana içerik alanı */
        .main-content {
            margin-left: 250px;
            flex: 1;
            min-height: 100vh;
            background: #f5f5f5;
        }
        
        /* Üst navbar */
        .top-navbar {
            background: white;
            padding: 15px 30px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .navbar-left h3 {
            margin: 0;
            color: #333;
            font-size: 24px;
        }
        
        .navbar-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .navbar-right span {
            color: #666;
            font-size: 14px;
        }
        
        .btn-logout {
            background: #e74c3c;
            color: white;
            padding: 8px 20px;
            text-decoration: none;
            border-radius: 5px;
            font-size: 14px;
            transition: background 0.3s;
        }
        
        .btn-logout:hover {
            background: #c0392b;
        }
        
        /* İçerik alanı */
        .content {
            padding: 30px;
        }
        
        /* İstatistik kartları */
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
        }
        
        .stat-card h3 {
            font-size: 14px;
            color: #666;
            margin: 0 0 10px 0;
            text-transform: uppercase;
            font-weight: 600;
        }
        
        .stat-value {
            font-size: 36px;
            font-weight: bold;
            color: #333;
        }
        
        .stat-card.primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .stat-card.primary h3 {
            color: rgba(255,255,255,0.9);
        }
        
        .stat-card.primary .stat-value {
            color: white;
        }
        
        /* Mesaj kutuları */
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
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
        
        /* Beyaz kutular */
        .white-box {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }
        
        .white-box h2 {
            margin: 0 0 20px 0;
            color: #333;
            font-size: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }
        
        /* Butonlar */
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            display: inline-block;
            margin: 10px 10px 10px 0;
            font-weight: 500;
            transition: transform 0.2s;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
        }
        
        /* Tablo */
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table thead {
            background: #f8f9fa;
        }
        
        .data-table th {
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #495057;
            border-bottom: 2px solid #dee2e6;
            font-size: 14px;
        }
        
        .data-table td {
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
            font-size: 14px;
        }
        
        .data-table tr:hover {
            background: #f8f9fa;
        }
        
        .btn-edit {
            background: #27ae60;
            color: white;
            padding: 5px 15px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 13px;
            display: inline-block;
        }
        
        .btn-edit:hover {
            background: #229954;
        }
        
        /* Araçlar grid */
        .tools-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .tool-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: transform 0.2s;
            display: block;
        }
        
        .tool-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .tool-btn .icon {
            font-size: 30px;
            display: block;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <?php include '../includes/sidebar.php'; ?>
        
        <div class="main-content">
            <!-- Üst Navbar -->
            <div class="top-navbar">
                <div class="navbar-left">
                    <h3>SEO Yönetimi</h3>
                </div>
                <div class="navbar-right">
                    <span>Hoş geldiniz, <?php echo $_SESSION['admin_username'] ?? 'Admin'; ?></span>
                    <a href="../logout.php" class="btn-logout">Çıkış</a>
                </div>
            </div>
            
            <!-- İçerik Alanı -->
            <div class="content">
                <!-- Mesajlar -->
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        ✅ <?php echo $success; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        ❌ <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <!-- İstatistikler -->
                <div class="stats-grid">
                    <div class="stat-card primary">
                        <h3>SEO Kapsama Oranı</h3>
                        <div class="stat-value">%<?php echo $seo_coverage; ?></div>
                    </div>
                    
                    <div class="stat-card">
                        <h3>Toplam İlan</h3>
                        <div class="stat-value"><?php echo $stats['total_properties']; ?></div>
                    </div>
                    
                    <div class="stat-card">
                        <h3>SEO Yapılan İlan</h3>
                        <div class="stat-value"><?php echo $stats['seo_properties']; ?></div>
                    </div>
                </div>
                
                <!-- Hızlı İşlemler -->
                <div class="white-box">
                    <h2>Hızlı İşlemler</h2>
                    <div>
                        <a href="generate-seo.php" class="btn-primary">
                            📊 Tüm İlanlar İçin SEO Oluştur
                        </a>
                        <a href="sitemap-generator.php" class="btn-primary">
                            🗺️ Sitemap.xml Oluştur
                        </a>
                        <a href="robots-editor.php" class="btn-primary">
                            🤖 Robots.txt Düzenle
                        </a>
                    </div>
                </div>
                
                <!-- Sayfa SEO Ayarları -->
                <div class="white-box">
                    <h2>Sayfa SEO Ayarları</h2>
                    
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Sayfa</th>
                                <th>URL</th>
                                <th>Meta Title</th>
                                <th>Meta Description</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pages as $page): ?>
                                <tr>
                                    <td><strong><?php echo ucfirst($page['page_name']); ?></strong></td>
                                    <td><?php echo $page['page_url'] ?? '-'; ?></td>
                                    <td><?php echo mb_substr($page['meta_title'], 0, 50) . '...'; ?></td>
                                    <td><?php echo mb_substr($page['meta_description'], 0, 60) . '...'; ?></td>
                                    <td>
                                        <a href="edit-page.php?id=<?php echo $page['id']; ?>" class="btn-edit">Düzenle</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- SEO Araçları -->
                <div class="white-box">
                    <h2>SEO Araçları</h2>
                    
                    <div class="tools-grid">
                        <a href="keyword-analyzer.php" class="tool-btn">
                            <span class="icon">🔍</span>
                            <div>Anahtar Kelime Analizi</div>
                        </a>
                        
                        <a href="meta-preview.php" class="tool-btn">
                            <span class="icon">👁️</span>
                            <div>Google Önizleme</div>
                        </a>
                        
                        <a href="schema-generator.php" class="tool-btn">
                            <span class="icon">📝</span>
                            <div>Schema Markup</div>
                        </a>
                        
                        <a href="speed-test.php" class="tool-btn">
                            <span class="icon">⚡</span>
                            <div>Hız Testi</div>
                        </a>
                        
                        <a href="broken-links.php" class="tool-btn">
                            <span class="icon">🔗</span>
                            <div>Kırık Link Kontrolü</div>
                        </a>
                        
                        <a href="competitor-analysis.php" class="tool-btn">
                            <span class="icon">📈</span>
                            <div>Rakip Analizi</div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>