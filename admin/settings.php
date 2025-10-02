<<<<<<< HEAD
<?php
session_start();

// Admin kontrolü
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit();
}

// Sadece admin rolü görebilir
if ($_SESSION['user_role'] !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

require_once '../config/database.php';

// Admin ID (Ahmet Karaman = admin kullanıcısı)
$admin_id = $_SESSION['admin_id'];
$success = '';
$error = '';

// Admin bilgilerini çek
$stmt = $db->prepare("SELECT * FROM users WHERE id = :id AND role = 'admin'");
$stmt->execute([':id' => $admin_id]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin) {
    die("Admin bilgileri bulunamadı!");
}

// Form gönderildiyse
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Hangi form gönderildi?
    $form_type = $_POST['form_type'] ?? '';
    
    if ($form_type == 'profile') {
        // Profil güncelleme
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $mobile = trim($_POST['mobile'] ?? '');
        $company = trim($_POST['company'] ?? 'Plaza Emlak & Yatırım');
        $address = trim($_POST['address'] ?? '');
        
        try {
            $stmt = $db->prepare("UPDATE users SET 
                                 full_name = :full_name,
                                 email = :email,
                                 phone = :phone,
                                 mobile = :mobile,
                                 company = :company,
                                 address = :address
                                 WHERE id = :id");
            
            $stmt->execute([
                ':full_name' => $full_name,
                ':email' => $email,
                ':phone' => $phone,
                ':mobile' => $mobile,
                ':company' => $company,
                ':address' => $address,
                ':id' => $admin_id
            ]);
            
            $success = "Profil bilgileriniz güncellendi!";
            
            // Bilgileri yeniden çek
            $stmt = $db->prepare("SELECT * FROM users WHERE id = :id");
            $stmt->execute([':id' => $admin_id]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            $error = "Güncelleme hatası: " . $e->getMessage();
        }
        
    } elseif ($form_type == 'password') {
        // Şifre güncelleme
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Mevcut şifre kontrolü
        if (!password_verify($current_password, $admin['password'])) {
            $error = "Mevcut şifreniz hatalı!";
        } elseif (strlen($new_password) < 6) {
            $error = "Yeni şifre en az 6 karakter olmalıdır!";
        } elseif ($new_password !== $confirm_password) {
            $error = "Yeni şifreler eşleşmiyor!";
        } else {
            // Şifreyi güncelle
            try {
                $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE users SET password = :password WHERE id = :id");
                $stmt->execute([':password' => $hashed, ':id' => $admin_id]);
                $success = "Şifreniz başarıyla güncellendi!";
            } catch (PDOException $e) {
                $error = "Şifre güncelleme hatası!";
            }
        }
        
    } elseif ($form_type == 'site') {
        // Site ayarları (basit ayarlar)
        $site_title = trim($_POST['site_title'] ?? 'Plaza Emlak');
        $site_email = trim($_POST['site_email'] ?? '');
        $site_phone = trim($_POST['site_phone'] ?? '');
        $site_address = trim($_POST['site_address'] ?? '');
        
        // Bu bilgileri site_settings tablosuna kaydedebilirsiniz
        // Şimdilik session'a kaydedelim
        $_SESSION['site_settings'] = [
            'title' => $site_title,
            'email' => $site_email,
            'phone' => $site_phone,
            'address' => $site_address
        ];
        
        $success = "Site ayarları güncellendi!";
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ayarlar - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .settings-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .settings-nav {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            border-bottom: 2px solid #e0e0e0;
        }
        
        .settings-nav button {
            padding: 12px 24px;
            background: none;
            border: none;
            color: #666;
            font-size: 16px;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }
        
        .settings-nav button.active {
            color: #667eea;
            border-bottom-color: #667eea;
        }
        
        .settings-panel {
            display: none;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        
        .settings-panel.active {
            display: block;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .btn-primary:hover {
            background: #5a6fd8;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
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
        
        .info-box {
            background: #e3f2fd;
            color: #1976d2;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .current-admin {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .current-admin h4 {
            margin: 0 0 10px 0;
            color: #333;
        }
        
        .current-admin p {
            margin: 5px 0;
            color: #666;
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
                    <a href="dashboard.php">
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
                    <a href="users/list.php">
                        <span class="icon">👥</span>
                        <span>Kullanıcılar</span>
                    </a>
                </li>
                <li>
                    <a href="settings.php" class="active">
                        <span class="icon">⚙️</span>
                        <span>Ayarlar</span>
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Main Content -->
        <div class="main-content">
            <div class="top-navbar">
                <div class="navbar-left">
                    <h3>Sistem Ayarları</h3>
                </div>
                <div class="navbar-right">
                    <span>Admin: <?php echo htmlspecialchars($admin['full_name']); ?></span>
                    <a href="logout.php" class="btn-logout">Çıkış</a>
                </div>
            </div>

            <div class="content">
                <div class="settings-container">
                    
                    <?php if($success): ?>
                        <div class="alert alert-success">
                            ✅ <?php echo $success; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if($error): ?>
                        <div class="alert alert-error">
                            ❌ <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Mevcut Admin Bilgisi -->
                    <div class="current-admin">
                        <h4>👤 Aktif Admin</h4>
                        <p><strong>Ad Soyad:</strong> <?php echo htmlspecialchars($admin['full_name']); ?></p>
                        <p><strong>Kullanıcı Adı:</strong> <?php echo htmlspecialchars($admin['username']); ?></p>
                        <p><strong>Son Giriş:</strong> <?php echo $admin['last_login'] ? date('d.m.Y H:i', strtotime($admin['last_login'])) : 'Bilinmiyor'; ?></p>
                    </div>
                    
                    <!-- Tab Menüsü -->
                    <div class="settings-nav">
                        <button class="active" onclick="showPanel('profile')">Profil Bilgileri</button>
                        <button onclick="showPanel('password')">Şifre Değiştir</button>
                        <button onclick="showPanel('site')">Site Ayarları</button>
                    </div>
                    
                    <!-- Profil Bilgileri Paneli -->
                    <div id="profile-panel" class="settings-panel active">
                        <h3>Profil Bilgileri</h3>
                        <form method="POST">
                            <input type="hidden" name="form_type" value="profile">
                            
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Ad Soyad *</label>
                                    <input type="text" name="full_name" value="<?php echo htmlspecialchars($admin['full_name']); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label>E-posta *</label>
                                    <input type="email" name="email" value="<?php echo htmlspecialchars($admin['email']); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label>Telefon *</label>
                                    <input type="tel" name="phone" value="<?php echo htmlspecialchars($admin['phone']); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label>Mobil Telefon</label>
                                    <input type="tel" name="mobile" value="<?php echo htmlspecialchars($admin['mobile'] ?? ''); ?>" placeholder="05XX XXX XX XX">
                                </div>
                                
                                <div class="form-group">
                                    <label>Şirket</label>
                                    <input type="text" name="company" value="<?php echo htmlspecialchars($admin['company'] ?? 'Plaza Emlak & Yatırım'); ?>">
                                </div>
                                
                                <div class="form-group full-width">
                                    <label>Adres</label>
                                    <textarea name="address"><?php echo htmlspecialchars($admin['address'] ?? ''); ?></textarea>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn-primary">Bilgileri Güncelle</button>
                        </form>
                    </div>
                    
                    <!-- Şifre Değiştir Paneli -->
                    <div id="password-panel" class="settings-panel">
                        <h3>Şifre Değiştir</h3>
                        
                        <div class="info-box">
                            💡 Güvenlik için şifrenizi düzenli olarak değiştirin. Şifreniz en az 6 karakter olmalıdır.
                        </div>
                        
                        <form method="POST">
                            <input type="hidden" name="form_type" value="password">
                            
                            <div class="form-group">
                                <label>Mevcut Şifre *</label>
                                <input type="password" name="current_password" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Yeni Şifre * (En az 6 karakter)</label>
                                <input type="password" name="new_password" required minlength="6">
                            </div>
                            
                            <div class="form-group">
                                <label>Yeni Şifre Tekrar *</label>
                                <input type="password" name="confirm_password" required minlength="6">
                            </div>
                            
                            <button type="submit" class="btn-primary">Şifreyi Değiştir</button>
                        </form>
                    </div>
                    
                    <!-- Site Ayarları Paneli -->
                    <div id="site-panel" class="settings-panel">
                        <h3>Site Ayarları</h3>
                        
                        <div class="info-box">
                            📌 Bu ayarlar sitenin genel bilgilerini içerir.
                        </div>
                        
                        <form method="POST">
                            <input type="hidden" name="form_type" value="site">
                            
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Site Başlığı</label>
                                    <input type="text" name="site_title" value="<?php echo $_SESSION['site_settings']['title'] ?? 'Plaza Emlak'; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label>İletişim E-posta</label>
                                    <input type="email" name="site_email" value="<?php echo $_SESSION['site_settings']['email'] ?? 'info@plazaemlak.com'; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label>İletişim Telefon</label>
                                    <input type="tel" name="site_phone" value="<?php echo $_SESSION['site_settings']['phone'] ?? '0272 222 00 03'; ?>">
                                </div>
                                
                                <div class="form-group full-width">
                                    <label>Şirket Adresi</label>
                                    <textarea name="site_address"><?php echo $_SESSION['site_settings']['address'] ?? 'Afyonkarahisar, Türkiye'; ?></textarea>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn-primary">Ayarları Kaydet</button>
                        </form>
                    </div>
                    
                </div>
            </div>
        </div>
    </div>
    
    <script>
    function showPanel(panelName) {
        // Tüm panelleri gizle
        document.querySelectorAll('.settings-panel').forEach(panel => {
            panel.classList.remove('active');
        });
        
        // Tüm butonların active class'ını kaldır
        document.querySelectorAll('.settings-nav button').forEach(btn => {
            btn.classList.remove('active');
        });
        
        // Seçilen paneli göster
        document.getElementById(panelName + '-panel').classList.add('active');
        
        // Tıklanan butonu active yap
        event.target.classList.add('active');
    }
    </script>
</body>
=======
<?php
session_start();

// Admin kontrolü
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit();
}

// Sadece admin rolü görebilir
if ($_SESSION['user_role'] !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

require_once '../config/database.php';

// Admin ID (Ahmet Karaman = admin kullanıcısı)
$admin_id = $_SESSION['admin_id'];
$success = '';
$error = '';

// Admin bilgilerini çek
$stmt = $db->prepare("SELECT * FROM users WHERE id = :id AND role = 'admin'");
$stmt->execute([':id' => $admin_id]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin) {
    die("Admin bilgileri bulunamadı!");
}

// Form gönderildiyse
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Hangi form gönderildi?
    $form_type = $_POST['form_type'] ?? '';
    
    if ($form_type == 'profile') {
        // Profil güncelleme
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $mobile = trim($_POST['mobile'] ?? '');
        $company = trim($_POST['company'] ?? 'Plaza Emlak & Yatırım');
        $address = trim($_POST['address'] ?? '');
        
        try {
            $stmt = $db->prepare("UPDATE users SET 
                                 full_name = :full_name,
                                 email = :email,
                                 phone = :phone,
                                 mobile = :mobile,
                                 company = :company,
                                 address = :address
                                 WHERE id = :id");
            
            $stmt->execute([
                ':full_name' => $full_name,
                ':email' => $email,
                ':phone' => $phone,
                ':mobile' => $mobile,
                ':company' => $company,
                ':address' => $address,
                ':id' => $admin_id
            ]);
            
            $success = "Profil bilgileriniz güncellendi!";
            
            // Bilgileri yeniden çek
            $stmt = $db->prepare("SELECT * FROM users WHERE id = :id");
            $stmt->execute([':id' => $admin_id]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            $error = "Güncelleme hatası: " . $e->getMessage();
        }
        
    } elseif ($form_type == 'password') {
        // Şifre güncelleme
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Mevcut şifre kontrolü
        if (!password_verify($current_password, $admin['password'])) {
            $error = "Mevcut şifreniz hatalı!";
        } elseif (strlen($new_password) < 6) {
            $error = "Yeni şifre en az 6 karakter olmalıdır!";
        } elseif ($new_password !== $confirm_password) {
            $error = "Yeni şifreler eşleşmiyor!";
        } else {
            // Şifreyi güncelle
            try {
                $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE users SET password = :password WHERE id = :id");
                $stmt->execute([':password' => $hashed, ':id' => $admin_id]);
                $success = "Şifreniz başarıyla güncellendi!";
            } catch (PDOException $e) {
                $error = "Şifre güncelleme hatası!";
            }
        }
        
    } elseif ($form_type == 'site') {
        // Site ayarları (basit ayarlar)
        $site_title = trim($_POST['site_title'] ?? 'Plaza Emlak');
        $site_email = trim($_POST['site_email'] ?? '');
        $site_phone = trim($_POST['site_phone'] ?? '');
        $site_address = trim($_POST['site_address'] ?? '');
        
        // Bu bilgileri site_settings tablosuna kaydedebilirsiniz
        // Şimdilik session'a kaydedelim
        $_SESSION['site_settings'] = [
            'title' => $site_title,
            'email' => $site_email,
            'phone' => $site_phone,
            'address' => $site_address
        ];
        
        $success = "Site ayarları güncellendi!";
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ayarlar - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .settings-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .settings-nav {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            border-bottom: 2px solid #e0e0e0;
        }
        
        .settings-nav button {
            padding: 12px 24px;
            background: none;
            border: none;
            color: #666;
            font-size: 16px;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }
        
        .settings-nav button.active {
            color: #667eea;
            border-bottom-color: #667eea;
        }
        
        .settings-panel {
            display: none;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        
        .settings-panel.active {
            display: block;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .btn-primary:hover {
            background: #5a6fd8;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
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
        
        .info-box {
            background: #e3f2fd;
            color: #1976d2;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .current-admin {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .current-admin h4 {
            margin: 0 0 10px 0;
            color: #333;
        }
        
        .current-admin p {
            margin: 5px 0;
            color: #666;
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
                    <a href="dashboard.php">
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
                    <a href="users/list.php">
                        <span class="icon">👥</span>
                        <span>Kullanıcılar</span>
                    </a>
                </li>
                <li>
                    <a href="settings.php" class="active">
                        <span class="icon">⚙️</span>
                        <span>Ayarlar</span>
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Main Content -->
        <div class="main-content">
            <div class="top-navbar">
                <div class="navbar-left">
                    <h3>Sistem Ayarları</h3>
                </div>
                <div class="navbar-right">
                    <span>Admin: <?php echo htmlspecialchars($admin['full_name']); ?></span>
                    <a href="logout.php" class="btn-logout">Çıkış</a>
                </div>
            </div>

            <div class="content">
                <div class="settings-container">
                    
                    <?php if($success): ?>
                        <div class="alert alert-success">
                            ✅ <?php echo $success; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if($error): ?>
                        <div class="alert alert-error">
                            ❌ <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Mevcut Admin Bilgisi -->
                    <div class="current-admin">
                        <h4>👤 Aktif Admin</h4>
                        <p><strong>Ad Soyad:</strong> <?php echo htmlspecialchars($admin['full_name']); ?></p>
                        <p><strong>Kullanıcı Adı:</strong> <?php echo htmlspecialchars($admin['username']); ?></p>
                        <p><strong>Son Giriş:</strong> <?php echo $admin['last_login'] ? date('d.m.Y H:i', strtotime($admin['last_login'])) : 'Bilinmiyor'; ?></p>
                    </div>
                    
                    <!-- Tab Menüsü -->
                    <div class="settings-nav">
                        <button class="active" onclick="showPanel('profile')">Profil Bilgileri</button>
                        <button onclick="showPanel('password')">Şifre Değiştir</button>
                        <button onclick="showPanel('site')">Site Ayarları</button>
                    </div>
                    
                    <!-- Profil Bilgileri Paneli -->
                    <div id="profile-panel" class="settings-panel active">
                        <h3>Profil Bilgileri</h3>
                        <form method="POST">
                            <input type="hidden" name="form_type" value="profile">
                            
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Ad Soyad *</label>
                                    <input type="text" name="full_name" value="<?php echo htmlspecialchars($admin['full_name']); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label>E-posta *</label>
                                    <input type="email" name="email" value="<?php echo htmlspecialchars($admin['email']); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label>Telefon *</label>
                                    <input type="tel" name="phone" value="<?php echo htmlspecialchars($admin['phone']); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label>Mobil Telefon</label>
                                    <input type="tel" name="mobile" value="<?php echo htmlspecialchars($admin['mobile'] ?? ''); ?>" placeholder="05XX XXX XX XX">
                                </div>
                                
                                <div class="form-group">
                                    <label>Şirket</label>
                                    <input type="text" name="company" value="<?php echo htmlspecialchars($admin['company'] ?? 'Plaza Emlak & Yatırım'); ?>">
                                </div>
                                
                                <div class="form-group full-width">
                                    <label>Adres</label>
                                    <textarea name="address"><?php echo htmlspecialchars($admin['address'] ?? ''); ?></textarea>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn-primary">Bilgileri Güncelle</button>
                        </form>
                    </div>
                    
                    <!-- Şifre Değiştir Paneli -->
                    <div id="password-panel" class="settings-panel">
                        <h3>Şifre Değiştir</h3>
                        
                        <div class="info-box">
                            💡 Güvenlik için şifrenizi düzenli olarak değiştirin. Şifreniz en az 6 karakter olmalıdır.
                        </div>
                        
                        <form method="POST">
                            <input type="hidden" name="form_type" value="password">
                            
                            <div class="form-group">
                                <label>Mevcut Şifre *</label>
                                <input type="password" name="current_password" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Yeni Şifre * (En az 6 karakter)</label>
                                <input type="password" name="new_password" required minlength="6">
                            </div>
                            
                            <div class="form-group">
                                <label>Yeni Şifre Tekrar *</label>
                                <input type="password" name="confirm_password" required minlength="6">
                            </div>
                            
                            <button type="submit" class="btn-primary">Şifreyi Değiştir</button>
                        </form>
                    </div>
                    
                    <!-- Site Ayarları Paneli -->
                    <div id="site-panel" class="settings-panel">
                        <h3>Site Ayarları</h3>
                        
                        <div class="info-box">
                            📌 Bu ayarlar sitenin genel bilgilerini içerir.
                        </div>
                        
                        <form method="POST">
                            <input type="hidden" name="form_type" value="site">
                            
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Site Başlığı</label>
                                    <input type="text" name="site_title" value="<?php echo $_SESSION['site_settings']['title'] ?? 'Plaza Emlak'; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label>İletişim E-posta</label>
                                    <input type="email" name="site_email" value="<?php echo $_SESSION['site_settings']['email'] ?? 'info@plazaemlak.com'; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label>İletişim Telefon</label>
                                    <input type="tel" name="site_phone" value="<?php echo $_SESSION['site_settings']['phone'] ?? '0272 222 00 03'; ?>">
                                </div>
                                
                                <div class="form-group full-width">
                                    <label>Şirket Adresi</label>
                                    <textarea name="site_address"><?php echo $_SESSION['site_settings']['address'] ?? 'Afyonkarahisar, Türkiye'; ?></textarea>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn-primary">Ayarları Kaydet</button>
                        </form>
                    </div>
                    
                </div>
            </div>
        </div>
    </div>
    
    <script>
    function showPanel(panelName) {
        // Tüm panelleri gizle
        document.querySelectorAll('.settings-panel').forEach(panel => {
            panel.classList.remove('active');
        });
        
        // Tüm butonların active class'ını kaldır
        document.querySelectorAll('.settings-nav button').forEach(btn => {
            btn.classList.remove('active');
        });
        
        // Seçilen paneli göster
        document.getElementById(panelName + '-panel').classList.add('active');
        
        // Tıklanan butonu active yap
        event.target.classList.add('active');
    }
    </script>
</body>
>>>>>>> 1b4657b0e4a9c21769cd70a9511bc87296025d5f
</html>