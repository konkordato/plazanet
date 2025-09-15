<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Admin Test ve Şifre Sıfırlama</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 20px; 
            background: #f5f5f5; 
        }
        .container { 
            max-width: 800px; 
            margin: 0 auto; 
            background: white; 
            padding: 20px; 
            border-radius: 10px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1); 
        }
        h1 { 
            color: #2563EB; 
            border-bottom: 3px solid #DC2626; 
            padding-bottom: 10px; 
        }
        .success { 
            background: #10B981; 
            color: white; 
            padding: 10px; 
            border-radius: 5px; 
            margin: 10px 0; 
        }
        .error { 
            background: #EF4444; 
            color: white; 
            padding: 10px; 
            border-radius: 5px; 
            margin: 10px 0; 
        }
        .info { 
            background: #3B82F6; 
            color: white; 
            padding: 10px; 
            border-radius: 5px; 
            margin: 10px 0; 
        }
        button { 
            background: #2563EB; 
            color: white; 
            padding: 10px 20px; 
            border: none; 
            border-radius: 5px; 
            cursor: pointer; 
            font-size: 16px; 
        }
        button:hover { 
            background: #1E40AF; 
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 20px 0; 
        }
        th, td { 
            padding: 10px; 
            text-align: left; 
            border: 1px solid #ddd; 
        }
        th { 
            background: #f3f4f6; 
        }
        .test-form { 
            background: #f9fafb; 
            padding: 15px; 
            border-radius: 5px; 
            margin: 20px 0; 
        }
        .test-form input { 
            padding: 8px; 
            margin: 5px; 
            border: 1px solid #ddd; 
            border-radius: 3px; 
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 Admin Giriş Test ve Şifre Sıfırlama</h1>
        
        <?php
        // Mevcut admin kullanıcılarını listele
        echo "<h2>📋 Mevcut Admin Kullanıcıları:</h2>";
        
        try {
            $stmt = $db->query("SELECT id, username, email, created_at FROM admins");
            $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if(count($admins) > 0) {
                echo "<table>";
                echo "<tr><th>ID</th><th>Kullanıcı Adı</th><th>E-posta</th><th>Oluşturma Tarihi</th></tr>";
                foreach($admins as $admin) {
                    echo "<tr>";
                    echo "<td>" . $admin['id'] . "</td>";
                    echo "<td><strong>" . $admin['username'] . "</strong></td>";
                    echo "<td>" . $admin['email'] . "</td>";
                    echo "<td>" . $admin['created_at'] . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<p class='error'>❌ Hiç admin kullanıcısı bulunamadı!</p>";
            }
            
        } catch(PDOException $e) {
            echo "<p class='error'>Veritabanı hatası: " . $e->getMessage() . "</p>";
        }
        
        // Şifre test formu
        echo "<h2>🔑 Şifre Test Et:</h2>";
        
        if(isset($_POST['test_login'])) {
            $test_username = $_POST['test_username'];
            $test_password = $_POST['test_password'];
            
            $stmt = $db->prepare("SELECT password FROM admins WHERE username = :username");
            $stmt->bindParam(':username', $test_username);
            $stmt->execute();
            
            if($stmt->rowCount() > 0) {
                $hash = $stmt->fetch(PDO::FETCH_ASSOC)['password'];
                
                if(password_verify($test_password, $hash)) {
                    echo "<p class='success'>✅ Şifre DOĞRU! Giriş yapabilirsiniz.</p>";
                } else {
                    echo "<p class='error'>❌ Şifre YANLIŞ!</p>";
                    echo "<p>Hash: " . substr($hash, 0, 30) . "...</p>";
                }
            } else {
                echo "<p class='error'>❌ Kullanıcı bulunamadı!</p>";
            }
        }
        ?>
        
        <div class="test-form">
            <form method="POST">
                <input type="text" name="test_username" placeholder="Kullanıcı adı" required>
                <input type="password" name="test_password" placeholder="Şifre" required>
                <button type="submit" name="test_login">Test Et</button>
            </form>
        </div>
        
        <?php
        // Şifre sıfırlama
        echo "<h2>🔄 Şifreyi Sıfırla:</h2>";
        
        if(isset($_POST['reset_password'])) {
            $new_password = '123456';
            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
            
            try {
                // Önce admin kullanıcısının var olup olmadığını kontrol et
                $check = $db->query("SELECT id FROM admins WHERE username = 'admin'");
                
                if($check->rowCount() > 0) {
                    // Varsa güncelle
                    $stmt = $db->prepare("UPDATE admins SET password = :password WHERE username = 'admin'");
                    $stmt->bindParam(':password', $new_hash);
                    $stmt->execute();
                    echo "<p class='success'>✅ Admin şifresi sıfırlandı!</p>";
                } else {
                    // Yoksa yeni admin ekle
                    $stmt = $db->prepare("INSERT INTO admins (username, password, email) VALUES ('admin', :password, 'admin@plazanet.com')");
                    $stmt->bindParam(':password', $new_hash);
                    $stmt->execute();
                    echo "<p class='success'>✅ Admin kullanıcısı oluşturuldu!</p>";
                }
                
                echo "<div class='info'>";
                echo "<strong>Yeni Giriş Bilgileri:</strong><br>";
                echo "Kullanıcı Adı: <strong>admin</strong><br>";
                echo "Şifre: <strong>123456</strong>";
                echo "</div>";
                
            } catch(PDOException $e) {
                echo "<p class='error'>Hata: " . $e->getMessage() . "</p>";
            }
        }
        ?>
        
        <form method="POST">
            <button type="submit" name="reset_password" style="background: #DC2626;">
                🔄 Admin Şifresini Sıfırla (123456 yap)
            </button>
        </form>
        
        <h2>📝 Manuel SQL ile Şifre Sıfırlama:</h2>
        <div style="background: #f3f4f6; padding: 15px; border-radius: 5px;">
            <p>phpMyAdmin'de şu SQL kodunu çalıştırın:</p>
            <code style="display: block; background: white; padding: 10px; border-radius: 3px;">
                UPDATE admins SET password = '$2y$10$4DQ2rHHfJHGSYuT8iNV6OuFbNJJxVxQKQtLnBp7D6K3Uz/TfC.S0C' WHERE username = 'admin';
            </code>
            <p>Bu kod admin şifresini <strong>123456</strong> yapar.</p>
        </div>
        
        <h2>🚀 Hızlı Linkler:</h2>
        <p>
            <a href="admin/" style="background: #2563EB; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;">
                Admin Girişe Git
            </a>
            <a href="index.php" style="background: #10B981; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-left: 10px;">
                Ana Sayfaya Git
            </a>
        </p>
    </div>
</body>
</html>