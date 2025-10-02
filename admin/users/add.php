<?php
session_start();
// Admin kontrolü
if(!isset($_SESSION['admin_logged_in']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

require_once '../../config/database.php';

// Form gönderildiyse
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    
    // Validasyon
    $errors = [];
    
    if(strlen($username) < 3) {
        $errors[] = "Kullanıcı adı en az 3 karakter olmalı";
    }
    
    if(strlen($password) < 6) {
        $errors[] = "Şifre en az 6 karakter olmalı";
    }
    
    if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Geçerli bir e-posta adresi girin";
    }
    
    // Kullanıcı adı ve email kontrolü
    $stmt = $db->prepare("SELECT id FROM users WHERE username = :username OR email = :email");
    $stmt->execute([':username' => $username, ':email' => $email]);
    if($stmt->rowCount() > 0) {
        $errors[] = "Bu kullanıcı adı veya e-posta zaten kullanılıyor";
    }
    
    if(empty($errors)) {
        try {
            // Kullanıcıyı ekle
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $db->prepare("
                INSERT INTO users (username, password, full_name, email, phone, role, status, created_by) 
                VALUES (:username, :password, :full_name, :email, :phone, 'user', 'active', :created_by)
            ");
            
            $stmt->execute([
                ':username' => $username,
                ':password' => $hashedPassword,
                ':full_name' => $full_name,
                ':email' => $email,
                ':phone' => $phone,
                ':created_by' => $_SESSION['admin_id'] ?? 1
            ]);
            
            $_SESSION['success'] = "Kullanıcı başarıyla eklendi!";
            header("Location: list.php");
            exit();
            
        } catch(PDOException $e) {
            $errors[] = "Veritabanı hatası: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yeni Kullanıcı Ekle - Plaza Emlak</title>
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <style>
        .form-container {
            max-width: 600px;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #2c3e50;
            font-weight: 500;
        }
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        .form-group input:focus {
            outline: none;
            border-color: #3498db;
        }
        .form-group small {
            display: block;
            margin-top: 5px;
            color: #7f8c8d;
            font-size: 12px;
        }
        .form-buttons {
            display: flex;
            gap: 10px;
            margin-top: 30px;
        }
        .btn-submit {
            background: #27ae60;
            color: white;
            padding: 10px 30px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
        }
        .btn-cancel {
            background: #95a5a6;
            color: white;
            padding: 10px 30px;
            text-decoration: none;
            border-radius: 5px;
            display: inline-block;
        }
        .error-box {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .password-info {
            background: #d1ecf1;
            color: #0c5460;
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
            font-size: 13px;
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
                    <a href="../dashboard.php">
                        <span class="icon">🏠</span>
                        <span>Ana Sayfa</span>
                    </a>
                </li>
                <li>
                    <a href="list.php" class="active">
                        <span class="icon">👥</span>
                        <span>Kullanıcılar</span>
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Main Content -->
        <div class="main-content">
            <div class="top-navbar">
                <div class="navbar-left">
                    <h3>Yeni Kullanıcı Ekle</h3>
                </div>
                <div class="navbar-right">
                    <a href="../logout.php" class="btn-logout">Çıkış</a>
                </div>
            </div>

            <div class="content">
                <div class="form-container">
                    <!-- Hatalar -->
                    <?php if(!empty($errors)): ?>
                        <div class="error-box">
                            <?php foreach($errors as $error): ?>
                                <div>• <?php echo $error; ?></div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Form -->
                    <form method="POST" action="">
                        <div class="form-group">
                            <label>Ad Soyad *</label>
                            <input type="text" name="full_name" required 
                                   value="<?php echo $_POST['full_name'] ?? ''; ?>"
                                   placeholder="Örn: Ahmet Yılmaz">
                            <small>Kullanıcının gerçek adı ve soyadı</small>
                        </div>

                        <div class="form-group">
                            <label>Kullanıcı Adı *</label>
                            <input type="text" name="username" required 
                                   value="<?php echo $_POST['username'] ?? ''; ?>"
                                   placeholder="Örn: ahmetyilmaz">
                            <small>Sisteme giriş için kullanılacak (min 3 karakter)</small>
                        </div>

                        <div class="form-group">
                            <label>E-posta Adresi *</label>
                            <input type="email" name="email" required 
                                   value="<?php echo $_POST['email'] ?? ''; ?>"
                                   placeholder="Örn: ahmet@email.com">
                        </div>

                        <div class="form-group">
                            <label>Telefon Numarası</label>
                            <input type="text" name="phone" 
                                   value="<?php echo $_POST['phone'] ?? ''; ?>"
                                   placeholder="Örn: 0532 123 45 67">
                        </div>

                        <div class="form-group">
                            <label>Şifre *</label>
                            <input type="password" name="password" required 
                                   placeholder="En az 6 karakter">
                            <div class="password-info">
                                💡 Bu şifreyi kullanıcıya bildirin. Kullanıcı ilk girişten sonra 
                                şifresini kendisi değiştirebilir.
                            </div>
                        </div>

                        <div class="form-buttons">
                            <button type="submit" class="btn-submit">✓ Kullanıcı Ekle</button>
                            <a href="list.php" class="btn-cancel">İptal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>