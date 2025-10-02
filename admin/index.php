<<<<<<< HEAD
<?php
// OTURUM BAŞLAT - ÖNEMLİ!
session_start();

// Veritabanı bağlantısı
require_once '../config/database.php';

// Zaten giriş yapmışsa dashboard'a git
if(isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: dashboard.php");
    exit();
}

// Yardımcı fonksiyonlar
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function safeOutput($data) {
    return htmlspecialchars($data);
}

$error = '';
$success = '';

// Form gönderildiyse
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $username = cleanInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if(!empty($username) && !empty($password)) {
        
        try {
            // Kullanıcıyı bul - status kontrolünü kaldırdık
            $stmt = $db->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            
            if($stmt->rowCount() == 1) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Şifre kontrolü
                if(password_verify($password, $user['password'])) {
                    
                    // Session bilgilerini kaydet
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_id'] = $user['id'];
                    $_SESSION['admin_username'] = $user['username'];
                    $_SESSION['user_logged_in'] = true;
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_username'] = $user['username'];
                    $_SESSION['user_fullname'] = $user['full_name'] ?? 'Admin';
                    $_SESSION['user_role'] = $user['role'] ?? 'admin';
                    
                    // Son giriş zamanını güncelle
                    try {
                        $update = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = :id");
                        $update->execute([':id' => $user['id']]);
                    } catch(Exception $e) {
                        // Son giriş güncellenemese bile devam et
                    }
                    
                    // Dashboard'a yönlendir
                    header("Location: dashboard.php");
                    exit();
                    
                } else {
                    $error = 'Kullanıcı adı veya şifre hatalı!';
                }
            } else {
                $error = 'Kullanıcı adı veya şifre hatalı!';
            }
        } catch(PDOException $e) {
            $error = 'Sistem hatası oluştu!';
        }
        
    } else {
        $error = 'Lütfen tüm alanları doldurun!';
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş - Plazanet Emlak</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
            width: 100%;
            max-width: 400px;
        }
        
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 30px;
            text-align: center;
        }
        
        .login-header h1 {
            color: white;
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .login-header p {
            color: rgba(255,255,255,0.9);
            font-size: 14px;
        }
        
        .login-form {
            padding: 40px 30px;
        }
        
        .alert {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
            font-size: 14px;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
        }
        
        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102,126,234,0.3);
        }
        
        .login-footer {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }
        
        .login-footer a {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
        }
        
        .login-types {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .login-type {
            padding: 10px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 14px;
            color: #666;
        }
        
        .login-type.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        
        .login-type:hover {
            border-color: #667eea;
            color: #667eea;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>🏢 PLAZANET</h1>
            <p>Yönetim Paneli Girişi</p>
        </div>
        
        <div class="login-form">
            <div class="login-types">
                <div class="login-type active">Admin / Danışman</div>
            </div>
            
            <?php if($error): ?>
                <div class="alert alert-error">
                    <?php echo safeOutput($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if($success): ?>
                <div class="alert alert-success">
                    <?php echo safeOutput($success); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Kullanıcı Adı</label>
                    <input type="text" 
                           id="username" 
                           name="username" 
                           placeholder="Kullanıcı adınızı girin"
                           required>
                </div>
                
                <div class="form-group">
                    <label for="password">Şifre</label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           placeholder="Şifrenizi girin"
                           required>
                </div>
                
                <button type="submit" class="btn-login">
                    Giriş Yap
                </button>
            </form>
            
            <div class="login-footer">
                <a href="../index.php">← Ana Sayfaya Dön</a>
            </div>
        </div>
    </div>
</body>
=======
<?php
// OTURUM BAŞLAT - ÖNEMLİ!
session_start();

// Veritabanı bağlantısı
require_once '../config/database.php';

// Zaten giriş yapmışsa dashboard'a git
if(isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: dashboard.php");
    exit();
}

// Yardımcı fonksiyonlar
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function safeOutput($data) {
    return htmlspecialchars($data);
}

$error = '';
$success = '';

// Form gönderildiyse
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $username = cleanInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if(!empty($username) && !empty($password)) {
        
        try {
            // Kullanıcıyı bul - status kontrolünü kaldırdık
            $stmt = $db->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            
            if($stmt->rowCount() == 1) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Şifre kontrolü
                if(password_verify($password, $user['password'])) {
                    
                    // Session bilgilerini kaydet
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_id'] = $user['id'];
                    $_SESSION['admin_username'] = $user['username'];
                    $_SESSION['user_logged_in'] = true;
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_username'] = $user['username'];
                    $_SESSION['user_fullname'] = $user['full_name'] ?? 'Admin';
                    $_SESSION['user_role'] = $user['role'] ?? 'admin';
                    
                    // Son giriş zamanını güncelle
                    try {
                        $update = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = :id");
                        $update->execute([':id' => $user['id']]);
                    } catch(Exception $e) {
                        // Son giriş güncellenemese bile devam et
                    }
                    
                    // Dashboard'a yönlendir
                    header("Location: dashboard.php");
                    exit();
                    
                } else {
                    $error = 'Kullanıcı adı veya şifre hatalı!';
                }
            } else {
                $error = 'Kullanıcı adı veya şifre hatalı!';
            }
        } catch(PDOException $e) {
            $error = 'Sistem hatası oluştu!';
        }
        
    } else {
        $error = 'Lütfen tüm alanları doldurun!';
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş - Plazanet Emlak</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
            width: 100%;
            max-width: 400px;
        }
        
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 30px;
            text-align: center;
        }
        
        .login-header h1 {
            color: white;
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .login-header p {
            color: rgba(255,255,255,0.9);
            font-size: 14px;
        }
        
        .login-form {
            padding: 40px 30px;
        }
        
        .alert {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
            font-size: 14px;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
        }
        
        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102,126,234,0.3);
        }
        
        .login-footer {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }
        
        .login-footer a {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
        }
        
        .login-types {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .login-type {
            padding: 10px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 14px;
            color: #666;
        }
        
        .login-type.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        
        .login-type:hover {
            border-color: #667eea;
            color: #667eea;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>🏢 PLAZANET</h1>
            <p>Yönetim Paneli Girişi</p>
        </div>
        
        <div class="login-form">
            <div class="login-types">
                <div class="login-type active">Admin / Danışman</div>
            </div>
            
            <?php if($error): ?>
                <div class="alert alert-error">
                    <?php echo safeOutput($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if($success): ?>
                <div class="alert alert-success">
                    <?php echo safeOutput($success); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Kullanıcı Adı</label>
                    <input type="text" 
                           id="username" 
                           name="username" 
                           placeholder="Kullanıcı adınızı girin"
                           required>
                </div>
                
                <div class="form-group">
                    <label for="password">Şifre</label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           placeholder="Şifrenizi girin"
                           required>
                </div>
                
                <button type="submit" class="btn-login">
                    Giriş Yap
                </button>
            </form>
            
            <div class="login-footer">
                <a href="../index.php">← Ana Sayfaya Dön</a>
            </div>
        </div>
    </div>
</body>
>>>>>>> 1b4657b0e4a9c21769cd70a9511bc87296025d5f
</html>