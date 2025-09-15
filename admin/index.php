<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Zaten giriş yapmışsa dashboard'a yönlendir
if(isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: dashboard.php");
    exit();
}

require_once '../config/database.php';

// Zaten giriş yapmışsa dashboard'a yönlendir
if(isset($_SESSION['admin_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';

// Form gönderilmişse
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if(!empty($username) && !empty($password)) {
        // Kullanıcıyı veritabanında ara
        $query = "SELECT id, username, password FROM admins WHERE username = :username";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        if($stmt->rowCount() == 1) {
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Şifre kontrolü
            if(password_verify($password, $admin['password'])) {
                // Giriş başarılı
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_logged_in'] = true;
                
                header("Location: dashboard.php");
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
    <title>Admin Girişi - Plazanet Emlak</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
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