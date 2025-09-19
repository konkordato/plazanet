<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Zaten giriş yapmışsa yönlendir
if(isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) {
    if($_SESSION['user_role'] === 'admin') {
        header("Location: dashboard.php");
    } else {
        header("Location: user-dashboard.php");
    }
    exit();
}

require_once '../config/database.php';

$error = '';

// Form gönderilmişse
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if(!empty($username) && !empty($password)) {
        // YENİ: users tablosundan kontrol et
        $query = "SELECT id, username, password, full_name, role, status FROM users WHERE username = :username";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        if($stmt->rowCount() == 1) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Kullanıcı aktif mi?
            if($user['status'] !== 'active') {
                $error = "Hesabınız pasif durumda. Yönetici ile iletişime geçin.";
            }
            // Şifre kontrolü
            elseif(password_verify($password, $user['password'])) {
                // Giriş başarılı - Oturum bilgilerini kaydet
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_username'] = $user['username'];
                $_SESSION['user_fullname'] = $user['full_name'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_logged_in'] = true;
                
                // Eski admin session'ları (uyumluluk için)
                if($user['role'] === 'admin') {
                    $_SESSION['admin_id'] = $user['id'];
                    $_SESSION['admin_username'] = $user['username'];
                    $_SESSION['admin_logged_in'] = true;
                }
                
                // Son giriş zamanını güncelle
                $updateLogin = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = :id");
                $updateLogin->execute([':id' => $user['id']]);
                
                // Yönlendirme
                if($user['role'] === 'admin') {
                    header("Location: dashboard.php");
                } else {
                    header("Location: user-dashboard.php");
                }
                exit();
            } else {
                $error = "Kullanıcı adı veya şifre hatalı!";
            }
        } else {
            $error = "Kullanıcı adı veya şifre hatalı!";
        }
    } else {
        $error = "Lütfen tüm alanları doldurun!";
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş - Plazanet Emlak</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .login-info {
            background: #d1ecf1;
            color: #0c5460;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .login-info h4 {
            margin-bottom: 10px;
        }
    </style>
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-box">
            <div class="login-logo">
                <h1>PLAZANET</h1>
                <p>Yönetim Paneli</p>
            </div>
            
            <?php if($error): ?>
                <div class="alert alert-error">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <!-- Test için bilgi kutusu -->
            <div class="login-info">
                <h4>🔑 Giriş Bilgileri:</h4>
                <div><strong>Admin:</strong> admin / 123456</div>
                <div><strong>Kullanıcı:</strong> Eklediğiniz kullanıcı adı ve şifre</div>
            </div>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Kullanıcı Adı</label>
                    <input type="text" 
                           id="username" 
                           name="username" 
                           placeholder="Kullanıcı adınızı girin"
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
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
                
                <button type="submit" class="btn-login">Giriş Yap</button>
            </form>
            
            <div class="login-footer">
                <a href="../index.php">← Ana Sayfaya Dön</a>
            </div>
        </div>
    </div>
</body>
</html>