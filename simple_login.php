<?php
// BASİT GİRİŞ SİSTEMİ - XAMPP İÇİN
session_start();

// Basit veritabanı bağlantısı
try {
    $db = new PDO('mysql:host=localhost;dbname=plazanet;charset=utf8mb4', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Veritabanı bağlantı hatası: " . $e->getMessage());
}

$message = '';

// OTOMATIK ŞİFRE SIFIRLAMA
if(isset($_GET['reset'])) {
    $new_password = password_hash('123456', PASSWORD_DEFAULT);
    
    $stmt = $db->prepare("UPDATE users SET password = :password WHERE username = 'admin'");
    $stmt->execute([':password' => $new_password]);
    
    $message = 'Şifre sıfırlandı! Yeni şifre: 123456';
}

// FORM GÖNDERİLDİYSE
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Kullanıcıyı bul
    $stmt = $db->prepare("SELECT * FROM users WHERE username = :username");
    $stmt->execute([':username' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if($user && password_verify($password, $user['password'])) {
        // GİRİŞ BAŞARILI
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_id'] = $user['id'];
        $_SESSION['admin_username'] = $user['username'];
        $_SESSION['user_logged_in'] = true;
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_username'] = $user['username'];
        $_SESSION['user_role'] = $user['role'];
        
        header("Location: dashboard.php");
        exit();
    } else {
        $message = 'Kullanıcı adı veya şifre hatalı!';
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Girişi</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f0f0f0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        .login-box {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            width: 350px;
        }
        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            color: #666;
        }
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-sizing: border-box;
        }
        button {
            width: 100%;
            padding: 12px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
        }
        button:hover {
            background: #45a049;
        }
        .message {
            background: #f44336;
            color: white;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        .success {
            background: #4CAF50;
        }
        .reset-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #666;
            text-decoration: none;
        }
        .reset-link:hover {
            text-decoration: underline;
        }
        .info {
            background: #2196F3;
            color: white;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>🏢 PLAZANET GİRİŞ</h2>
        
        <?php if($message): ?>
            <div class="message <?php echo strpos($message, 'sıfırlandı') !== false ? 'success' : ''; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <div class="info">
            Test Bilgileri:<br>
            Kullanıcı: admin<br>
            Şifre: 123456
        </div>
        
        <form method="POST">
            <div class="form-group">
                <label>Kullanıcı Adı:</label>
                <input type="text" name="username" required>
            </div>
            
            <div class="form-group">
                <label>Şifre:</label>
                <input type="password" name="password" required>
            </div>
            
            <button type="submit">Giriş Yap</button>
        </form>
        
        <a href="?reset=1" class="reset-link">🔑 Şifreyi Sıfırla (123456 yap)</a>
    </div>
</body>
</html>