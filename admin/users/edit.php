<?php
session_start();
if(!isset($_SESSION['admin_logged_in'])) {
    header("Location: ../index.php");
    exit();
}

require_once '../../config/database.php';

// Kullanıcı ID'sini al
$user_id = $_GET['id'] ?? 0;

// Kullanıcı bilgilerini çek
$stmt = $db->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute([':id' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$user) {
    $_SESSION['error'] = "Kullanıcı bulunamadı!";
    header("Location: list.php");
    exit();
}

// Form gönderildiyse
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $full_name = $_POST['full_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $status = $_POST['status'] ?? 'active';
    
    // Profil resmi yükleme
    $profile_image = $user['profile_image']; // Mevcut resmi koru
    
    if(isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $upload_dir = '../../assets/uploads/profiles/';
        
        // Klasör yoksa oluştur
        if(!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Dosya uzantısını al
        $file_ext = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        
        if(in_array($file_ext, $allowed)) {
            // Benzersiz dosya adı oluştur
            $new_filename = 'profile_' . $user_id . '_' . time() . '.' . $file_ext;
            $upload_path = $upload_dir . $new_filename;
            
            // Dosyayı yükle
            if(move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
                // Eski resmi sil
                if($user['profile_image'] && file_exists('../../' . $user['profile_image'])) {
                    unlink('../../' . $user['profile_image']);
                }
                
                $profile_image = 'assets/uploads/profiles/' . $new_filename;
            }
        }
    }
    
    // Şifre değiştirilmek isteniyorsa
    $password_sql = "";
    $params = [
        ':username' => $username,
        ':full_name' => $full_name,
        ':email' => $email,
        ':phone' => $phone,
        ':profile_image' => $profile_image,
        ':status' => $status,
        ':id' => $user_id
    ];
    
    if(!empty($_POST['password'])) {
        $password_sql = ", password = :password";
        $params[':password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
    }
    
    // Güncelleme sorgusu
    $sql = "UPDATE users SET 
            username = :username,
            full_name = :full_name,
            email = :email,
            phone = :phone,
            profile_image = :profile_image,
            status = :status
            $password_sql
            WHERE id = :id";
    
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $_SESSION['success'] = "Kullanıcı başarıyla güncellendi!";
        header("Location: list.php");
        exit();
    } catch(PDOException $e) {
        $error = "Güncelleme hatası: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kullanıcı Düzenle - Admin Panel</title>
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <style>
        .edit-form {
            background: white;
            padding: 30px;
            border-radius: 10px;
            max-width: 600px;
            margin: 0 auto;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        .profile-preview {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            margin: 20px auto;
            display: block;
            border: 3px solid #3498db;
        }
        .no-profile {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 20px auto;
            font-size: 48px;
            color: #999;
        }
        .btn-group {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 30px;
        }
        .btn {
            padding: 10px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
        }
        .btn-primary {
            background: #3498db;
            color: white;
        }
        .btn-secondary {
            background: #95a5a6;
            color: white;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
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
                <li><a href="../dashboard.php">🏠 Ana Sayfa</a></li>
                <li><a href="../properties/list.php">🏢 İlanlar</a></li>
                <li><a href="list.php" class="active">👥 Kullanıcılar</a></li>
                <li><a href="../settings.php">⚙️ Ayarlar</a></li>
            </ul>
        </nav>

        <!-- Main Content -->
        <div class="main-content">
            <div class="top-navbar">
                <div class="navbar-left">
                    <h3>Kullanıcı Düzenle</h3>
                </div>
                <div class="navbar-right">
                    <span>Admin: <?php echo $_SESSION['admin_username']; ?></span>
                    <a href="../logout.php" class="btn-logout">Çıkış</a>
                </div>
            </div>

            <div class="content">
                <?php if(isset($error)): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>

                <div class="edit-form">
                    <h2>Kullanıcı Bilgilerini Düzenle</h2>
                    
                    <!-- Profil Resmi Önizleme -->
                    <?php if($user['profile_image']): ?>
                        <img src="../../<?php echo $user['profile_image']; ?>" alt="Profil" class="profile-preview" id="preview">
                    <?php else: ?>
                        <div class="no-profile" id="preview">👤</div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <label>Profil Resmi</label>
                            <input type="file" name="profile_image" accept="image/*" onchange="previewImage(event)">
                            <small style="color: #666;">JPG, PNG veya GIF - Max 2MB</small>
                        </div>

                        <div class="form-group">
                            <label>Kullanıcı Adı</label>
                            <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Ad Soyad</label>
                            <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label>E-posta</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Telefon</label>
                            <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>">
                        </div>

                        <div class="form-group">
                            <label>Yeni Şifre (Boş bırakırsanız değişmez)</label>
                            <input type="password" name="password" placeholder="Yeni şifre">
                        </div>

                        <div class="form-group">
                            <label>Durum</label>
                            <select name="status">
                                <option value="active" <?php echo $user['status'] == 'active' ? 'selected' : ''; ?>>Aktif</option>
                                <option value="passive" <?php echo $user['status'] == 'passive' ? 'selected' : ''; ?>>Pasif</option>
                            </select>
                        </div>

                        <div class="btn-group">
                            <a href="list.php" class="btn btn-secondary">İptal</a>
                            <button type="submit" class="btn btn-primary">Güncelle</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
    function previewImage(event) {
        const preview = document.getElementById('preview');
        const file = event.target.files[0];
        
        if(file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                if(preview.tagName === 'IMG') {
                    preview.src = e.target.result;
                } else {
                    // Div ise img'ye çevir
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'profile-preview';
                    img.id = 'preview';
                    preview.parentNode.replaceChild(img, preview);
                }
            }
            reader.readAsDataURL(file);
        }
    }
    </script>
</body>
</html>