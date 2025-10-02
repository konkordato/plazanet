<?php
// Hybrid Admin Panel - Mobil ve Masaüstü Tek Dosyada
session_start();

// Giriş kontrolü
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit();
}

// Veritabanı bağlantısı
require_once '../config/database.php';

// Admin bilgileri
$adminInfo = [
    'username' => $_SESSION['admin_username'] ?? 'Admin',
    'role' => $_SESSION['user_role'] ?? 'admin'
];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Plazanet</title>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f6fa;
            color: #333;
        }
        
        /* LOADER */
        .loader {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255,255,255,0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            display: none;
        }
        
        .loader.active {
            display: flex;
        }
        
        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #e0e0e0;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* MOBİL GÖRÜNÜM */
        @media (max-width: 768px) {
            /* Üst Header */
            .mobile-header {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 15px;
                position: sticky;
                top: 0;
                z-index: 100;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            
            .mobile-header h1 {
                font-size: 20px;
            }
            
            .user-badge {
                background: rgba(255,255,255,0.2);
                padding: 5px 10px;
                border-radius: 20px;
                font-size: 14px;
            }
            
            /* İçerik Alanı */
            #content {
                padding: 20px 15px 80px 15px;
                min-height: calc(100vh - 60px);
            }
            
            /* Alt Menü */
            .bottom-nav {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                background: white;
                display: flex;
                justify-content: space-around;
                padding: 8px 0;
                box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
                z-index: 100;
            }
            
            .nav-item {
                flex: 1;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                padding: 5px;
                color: #888;
                cursor: pointer;
                transition: all 0.3s;
            }
            
            .nav-item.active {
                color: #667eea;
            }
            
            .nav-icon {
                font-size: 24px;
                margin-bottom: 3px;
            }
            
            .nav-text {
                font-size: 11px;
            }
            
            /* Desktop elemanları gizle */
            .desktop-sidebar {
                display: none;
            }
        }
        
        /* MASAÜSTÜ GÖRÜNÜM */
        @media (min-width: 769px) {
            /* Mobil elemanları gizle */
            .mobile-header,
            .bottom-nav {
                display: none;
            }
            
            /* Sol Menü */
            .desktop-sidebar {
                width: 250px;
                background: #2c3e50;
                position: fixed;
                left: 0;
                top: 0;
                height: 100vh;
                overflow-y: auto;
            }
            
            .sidebar-header {
                background: #34495e;
                padding: 20px;
                text-align: center;
                border-bottom: 1px solid rgba(255,255,255,0.1);
            }
            
            .sidebar-header h2 {
                color: white;
                font-size: 24px;
            }
            
            .sidebar-menu {
                list-style: none;
                padding: 20px 0;
            }
            
            .sidebar-menu li {
                margin: 5px 0;
            }
            
            .sidebar-menu a {
                display: flex;
                align-items: center;
                padding: 12px 20px;
                color: #b8c7ce;
                cursor: pointer;
                transition: all 0.3s;
            }
            
            .sidebar-menu a:hover,
            .sidebar-menu a.active {
                background: #34495e;
                color: white;
                border-left: 3px solid #3498db;
            }
            
            .menu-icon {
                margin-right: 10px;
                font-size: 18px;
            }
            
            /* İçerik Alanı */
            #content {
                margin-left: 250px;
                padding: 30px;
                min-height: 100vh;
            }
        }
        
        /* ORTAK STILLER */
        .page-title {
            font-size: 24px;
            color: #2c3e50;
            margin-bottom: 20px;
        }
        
        /* İstatistik Kartları */
        .stat-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 15px;
        }
        
        .stat-icon.blue { background: rgba(52,152,219,0.1); }
        .stat-icon.green { background: rgba(39,174,96,0.1); }
        .stat-icon.orange { background: rgba(243,156,18,0.1); }
        .stat-icon.red { background: rgba(231,76,60,0.1); }
        
        .stat-value {
            font-size: 28px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 14px;
            color: #7f8c8d;
        }
        
        /* Liste Görünümü */
        .data-table {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .table-header {
            background: #f8f9fa;
            padding: 15px 20px;
            font-weight: 600;
            border-bottom: 2px solid #e9ecef;
        }
        
        .table-row {
            padding: 15px 20px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: background 0.2s;
            cursor: pointer;
        }
        
        .table-row:hover {
            background: #f8f9fa;
        }
        
        .row-info h4 {
            font-size: 15px;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .row-info p {
            font-size: 13px;
            color: #7f8c8d;
        }
        
        .row-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn-action {
            padding: 5px 10px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
        }
        
        .btn-action:hover {
            background: #2980b9;
        }
        
        /* Boş Durum */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #7f8c8d;
        }
        
        .empty-icon {
            font-size: 48px;
            margin-bottom: 20px;
            opacity: 0.5;
        }
    </style>
</head>
<body>
    <!-- Loading Spinner -->
    <div class="loader" id="loader">
        <div class="spinner"></div>
    </div>

    <!-- Mobil Header (Sadece mobilde görünür) -->
    <div class="mobile-header">
        <h1>Plazanet Admin</h1>
        <div class="user-badge">
            <?php echo $adminInfo['username']; ?>
        </div>
    </div>
    
    <!-- Desktop Sidebar (Sadece masaüstünde görünür) -->
    <div class="desktop-sidebar">
        <div class="sidebar-header">
            <h2>PLAZANET</h2>
        </div>
        <ul class="sidebar-menu">
            <li><a data-page="dashboard" class="menu-item active">
                <span class="menu-icon">🏠</span> Ana Sayfa
            </a></li>
            <li><a data-page="properties" class="menu-item">
                <span class="menu-icon">🏢</span> İlanlar
            </a></li>
            <li><a data-page="users" class="menu-item">
                <span class="menu-icon">👥</span> Kullanıcılar
            </a></li>
            <li><a data-page="crm" class="menu-item">
                <span class="menu-icon">📊</span> CRM
            </a></li>
            <li><a data-page="settings" class="menu-item">
                <span class="menu-icon">⚙️</span> Ayarlar
            </a></li>
        </ul>
    </div>
    
    <!-- Ana İçerik Alanı -->
    <div id="content">
        <!-- İçerik AJAX ile yüklenecek -->
    </div>
    
    <!-- Mobil Alt Menü (Sadece mobilde görünür) -->
    <nav class="bottom-nav">
        <div class="nav-item active" data-page="dashboard">
            <span class="nav-icon">🏠</span>
            <span class="nav-text">Ana Sayfa</span>
        </div>
        <div class="nav-item" data-page="properties">
            <span class="nav-icon">🏢</span>
            <span class="nav-text">İlanlar</span>
        </div>
        <div class="nav-item" data-page="users">
            <span class="nav-icon">👥</span>
            <span class="nav-text">Kullanıcılar</span>
        </div>
        <div class="nav-item" data-page="crm">
            <span class="nav-icon">📊</span>
            <span class="nav-text">CRM</span>
        </div>
        <div class="nav-item" data-page="settings">
            <span class="nav-icon">⚙️</span>
            <span class="nav-text">Ayarlar</span>
        </div>
    </nav>

    <script>
        // Sayfa yüklendiğinde
        document.addEventListener('DOMContentLoaded', function() {
            // Başlangıçta dashboard'u yükle
            loadPage('dashboard');
            
            // Mobil menü tıklamaları
            document.querySelectorAll('.nav-item').forEach(item => {
                item.addEventListener('click', function() {
                    const page = this.getAttribute('data-page');
                    loadPage(page);
                    
                    // Aktif sınıfı güncelle
                    document.querySelectorAll('.nav-item').forEach(nav => {
                        nav.classList.remove('active');
                    });
                    this.classList.add('active');
                });
            });
            
            // Desktop menü tıklamaları
            document.querySelectorAll('.menu-item').forEach(item => {
                item.addEventListener('click', function() {
                    const page = this.getAttribute('data-page');
                    loadPage(page);
                    
                    // Aktif sınıfı güncelle
                    document.querySelectorAll('.menu-item').forEach(menu => {
                        menu.classList.remove('active');
                    });
                    this.classList.add('active');
                });
            });
        });
        
        // Sayfa yükleme fonksiyonu
        function loadPage(page) {
            // Loader'ı göster
            document.getElementById('loader').classList.add('active');
            
            // URL'i güncelle (sayfa yenilenmeden)
            window.location.hash = page;
            
            // AJAX ile içerik yükle
            fetch('ajax/get-content.php?page=' + page)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('content').innerHTML = html;
                    document.getElementById('loader').classList.remove('active');
                })
                .catch(error => {
                    console.error('Hata:', error);
                    document.getElementById('content').innerHTML = getStaticContent(page);
                    document.getElementById('loader').classList.remove('active');
                });
        }
        
        // Statik içerik (AJAX çalışmazsa)
        function getStaticContent(page) {
            const contents = {
                'dashboard': `
                    <h2 class="page-title">Dashboard</h2>
                    <div class="stat-cards">
                        <div class="stat-card">
                            <div class="stat-icon blue">🏢</div>
                            <div class="stat-value">24</div>
                            <div class="stat-label">Toplam İlan</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon green">✓</div>
                            <div class="stat-value">18</div>
                            <div class="stat-label">Aktif İlan</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon orange">💰</div>
                            <div class="stat-value">12</div>
                            <div class="stat-label">Satılık</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon red">🔑</div>
                            <div class="stat-value">6</div>
                            <div class="stat-label">Kiralık</div>
                        </div>
                    </div>
                `,
                'properties': `
                    <h2 class="page-title">İlanlar</h2>
                    <div class="data-table">
                        <div class="table-header">Toplam 3 ilan</div>
                        <div class="table-row">
                            <div class="row-info">
                                <h4>3+1 Lüks Daire</h4>
                                <p>Merkez, Afyonkarahisar</p>
                            </div>
                            <div class="row-actions">
                                <button class="btn-action">Düzenle</button>
                            </div>
                        </div>
                        <div class="table-row">
                            <div class="row-info">
                                <h4>2+1 Kiralık</h4>
                                <p>Cumhuriyet Mah.</p>
                            </div>
                            <div class="row-actions">
                                <button class="btn-action">Düzenle</button>
                            </div>
                        </div>
                    </div>
                `,
                'users': `
                    <h2 class="page-title">Kullanıcılar</h2>
                    <div class="data-table">
                        <div class="table-header">Toplam 2 kullanıcı</div>
                        <div class="table-row">
                            <div class="row-info">
                                <h4>Admin</h4>
                                <p>Yönetici</p>
                            </div>
                            <div class="row-actions">
                                <button class="btn-action">Düzenle</button>
                            </div>
                        </div>
                    </div>
                `,
                'crm': `
                    <h2 class="page-title">CRM Sistemi</h2>
                    <div class="empty-state">
                        <div class="empty-icon">📊</div>
                        <p>CRM verileri yükleniyor...</p>
                    </div>
                `,
                'settings': `
                    <h2 class="page-title">Ayarlar</h2>
                    <div class="empty-state">
                        <div class="empty-icon">⚙️</div>
                        <p>Ayarlar sayfası hazırlanıyor...</p>
                    </div>
                `
            };
            return contents[page] || '<div class="empty-state">Sayfa bulunamadı</div>';
        }
        
        // Sayfa yenilendiğinde hash'i kontrol et
        if(window.location.hash) {
            const page = window.location.hash.substring(1);
            loadPage(page);
        }
    </script>
</body>
</html>