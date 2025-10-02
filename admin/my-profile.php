<?php
<<<<<<< HEAD

session_start();



// Kullanıcı girişi kontrolü

if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {

    header("Location: index.php");

    exit();
}



// Sadece normal kullanıcılar bu sayfayı kullanabilir

if ($_SESSION['user_role'] !== 'user') {

    header("Location: index.php");

    exit();
}



require_once '../config/database.php';



$user_id = $_SESSION['user_id'];



// Kullanıcı bilgilerini çek

$stmt = $db->prepare("SELECT * FROM users WHERE id = :id");

$stmt->execute([':id' => $user_id]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);



if (!$user) {

    session_destroy();

    header("Location: index.php");

    exit();
}



$success_msg = '';

$error_msg = '';



// Form gönderildiyse güncelle

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    try {

        // Form verilerini al

        $full_name = trim($_POST['full_name']);

        $email = trim($_POST['email']);

        $phone = trim($_POST['phone']);

        $mobile = trim($_POST['mobile']);



        // E-posta kontrolü (başka kullanıcıda var mı?)

        $email_check = $db->prepare("SELECT id FROM users WHERE email = :email AND id != :id");

        $email_check->execute([':email' => $email, ':id' => $user_id]);



        if ($email_check->rowCount() > 0) {

            throw new Exception("Bu e-posta adresi başka bir kullanıcı tarafından kullanılıyor!");
        }



        // Profil resmi yükleme işlemi

        $profile_image_path = $user['profile_image']; // Mevcut resmi koru



        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {

            // DÜZELTME: assets klasörü altındaki uploads'a git

            $upload_dir = '../assets/uploads/profiles/';



            // Klasör yoksa oluştur

            if (!file_exists($upload_dir)) {

                mkdir($upload_dir, 0777, true);
            }



            // Dosya kontrolü

            $file_size = $_FILES['profile_image']['size'];

            $file_tmp = $_FILES['profile_image']['tmp_name'];

            $file_type = $_FILES['profile_image']['type'];

            $file_ext = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));



            $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];

            $max_size = 2 * 1024 * 1024; // 2MB



            if (!in_array($file_ext, $allowed_types)) {

                throw new Exception("Sadece JPG, PNG ve GIF dosyaları yüklenebilir!");
            }



            if ($file_size > $max_size) {

                throw new Exception("Dosya boyutu en fazla 2MB olabilir!");
            }



            // Yeni dosya adı

            $new_filename = 'profile_' . $user_id . '_' . time() . '.' . $file_ext;

            $upload_path = $upload_dir . $new_filename;



            // Dosyayı yükle

            if (move_uploaded_file($file_tmp, $upload_path)) {

                // Eski resmi sil

                if ($user['profile_image']) {

                    $old_path = '../' . $user['profile_image'];

                    if (file_exists($old_path)) {

                        unlink($old_path);
                    }
                }

                // DÜZELTME: Veritabanına doğru yolu kaydet

                $profile_image_path = 'assets/uploads/profiles/' . $new_filename;
            }
        }



        // Veritabanını güncelle

        $update_sql = "UPDATE users SET 

                      full_name = :full_name,

                      email = :email,

                      phone = :phone,

                      mobile = :mobile,

                      profile_image = :profile_image

                      WHERE id = :id";



        $update_params = [

            ':full_name' => $full_name,

            ':email' => $email,

            ':phone' => $phone,

            ':mobile' => $mobile,

            ':profile_image' => $profile_image_path,

            ':id' => $user_id

        ];



        // Şifre değiştirilecekse

        if (!empty($_POST['new_password'])) {

            $current_password = $_POST['current_password'];

            $new_password = $_POST['new_password'];

            $confirm_password = $_POST['confirm_password'];



            // Mevcut şifre kontrolü

            if (!password_verify($current_password, $user['password'])) {

                throw new Exception("Mevcut şifreniz yanlış!");
            }



            // Yeni şifre kontrolü

            if ($new_password !== $confirm_password) {

                throw new Exception("Yeni şifreler eşleşmiyor!");
            }



            if (strlen($new_password) < 6) {

                throw new Exception("Yeni şifre en az 6 karakter olmalı!");
            }



            // Şifreyi güncelle

            $update_sql = "UPDATE users SET 

                          full_name = :full_name,

                          email = :email,

                          phone = :phone,

                          mobile = :mobile,

                          profile_image = :profile_image,

                          password = :password

                          WHERE id = :id";



            $update_params[':password'] = password_hash($new_password, PASSWORD_DEFAULT);
        }



        $stmt = $db->prepare($update_sql);

        $stmt->execute($update_params);



        // Session bilgilerini güncelle

        $_SESSION['user_fullname'] = $full_name;



        $success_msg = "Profiliniz başarıyla güncellendi!";



        // Güncel bilgileri tekrar çek

        $stmt = $db->prepare("SELECT * FROM users WHERE id = :id");

        $stmt->execute([':id' => $user_id]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {

        $error_msg = $e->getMessage();
    }
}

?>

<!DOCTYPE html>

<html lang="tr">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Profilim - <?php echo htmlspecialchars($user['full_name']); ?></title>

    <link rel="stylesheet" href="../assets/css/admin.css">

    <style>
        .profile-container {

            max-width: 800px;

            margin: 0 auto;

            background: white;

            border-radius: 15px;

            padding: 30px;

            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);

        }

        .profile-header {

            text-align: center;

            margin-bottom: 40px;

            padding-bottom: 30px;

            border-bottom: 2px solid #f0f0f0;

        }

        .profile-avatar {

            width: 150px;

            height: 150px;

            border-radius: 50%;

            object-fit: cover;

            border: 5px solid #3498db;

            margin: 0 auto 20px;

            display: block;

        }

        .no-avatar {

            width: 150px;

            height: 150px;

            border-radius: 50%;

            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);

            display: flex;

            align-items: center;

            justify-content: center;

            margin: 0 auto 20px;

            font-size: 60px;

            color: white;

        }

        .profile-name {

            font-size: 28px;

            color: #2c3e50;

            margin-bottom: 10px;

        }

        .profile-username {

            color: #7f8c8d;

            font-size: 18px;

        }

        .form-section {

            margin-bottom: 30px;

        }

        .section-title {

            font-size: 18px;

            color: #2c3e50;

            margin-bottom: 20px;

            padding-bottom: 10px;

            border-bottom: 2px solid #3498db;

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

            grid-column: span 2;

        }

        label {

            display: block;

            margin-bottom: 8px;

            color: #555;

            font-weight: 500;

        }

        input[type="text"],

        input[type="email"],

        input[type="tel"],

        input[type="password"],

        input[type="file"] {

            width: 100%;

            padding: 12px;

            border: 1px solid #ddd;

            border-radius: 8px;

            font-size: 14px;

            transition: all 0.3s;

        }

        input:focus {

            border-color: #3498db;

            outline: none;

            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);

        }

        .file-info {

            font-size: 12px;

            color: #999;

            margin-top: 5px;

        }

        .alert {

            padding: 15px;

            margin-bottom: 20px;

            border-radius: 8px;

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

        .btn-container {

            text-align: center;

            margin-top: 30px;

            padding-top: 30px;

            border-top: 2px solid #f0f0f0;

        }

        .btn {

            padding: 12px 40px;

            border: none;

            border-radius: 8px;

            font-size: 16px;

            cursor: pointer;

            transition: all 0.3s;

            margin: 0 10px;

        }

        .btn-primary {

            background: #3498db;

            color: white;

        }

        .btn-primary:hover {

            background: #2980b9;

            transform: translateY(-2px);

        }

        .btn-secondary {

            background: #95a5a6;

            color: white;

            text-decoration: none;

            display: inline-block;

        }

        .btn-secondary:hover {

            background: #7f8c8d;

        }

        .password-info {

            background: #f0f8ff;

            padding: 10px;

            border-radius: 5px;

            font-size: 13px;

            color: #2c5282;

            margin-top: 10px;

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

                    <a href="user-dashboard.php">

                        <span class="icon">🏠</span>

                        <span>Ana Sayfa</span>

                    </a>

                </li>

                <li>

                    <a href="my-properties.php">

                        <span class="icon">🏢</span>

                        <span>İlanlarım</span>

                    </a>

                </li>

                <li>

                    <a href="properties/add-step1.php">

                        <span class="icon">➕</span>

                        <span>İlan Ekle</span>

                    </a>

                </li>
                <li>
                    <a href="portfolio/closing.php">
                        <span class="icon">💰</span>
                        <span>Portföy Kapatma</span>
                    </a>
                </li>
                <li>
                    <a href="portfolio/my-reports.php">
                        <span class="icon">📊</span>
                        <span>Satış Raporlarım</span>
                    </a>
                </li>
                <li>

                <li>

                    <a href="my-profile.php" class="active">

                        <span class="icon">👤</span>

                        <span>Profilim</span>

                    </a>

                </li>

            </ul>

        </nav>



        <!-- Main Content -->

        <div class="main-content">

            <div class="top-navbar">

                <div class="navbar-left">

                    <h3>Profil Düzenle</h3>

                </div>

                <div class="navbar-right">

                    <span>👤 <?php echo htmlspecialchars($user['full_name']); ?></span>

                    <a href="logout.php" class="btn-logout">Çıkış</a>

                </div>

            </div>



            <div class="content">

                <div class="profile-container">

                    <!-- Profil Başlığı -->

                    <div class="profile-header">

                        <?php if ($user['profile_image']): ?>

                            <img src="../<?php echo $user['profile_image']; ?>" alt="Profil" class="profile-avatar">

                        <?php else: ?>

                            <div class="no-avatar">👤</div>

                        <?php endif; ?>

                        <h1 class="profile-name"><?php echo htmlspecialchars($user['full_name']); ?></h1>

                        <p class="profile-username">@<?php echo htmlspecialchars($user['username']); ?></p>

                    </div>



                    <!-- Mesajlar -->

                    <?php if ($success_msg): ?>

                        <div class="alert alert-success">✅ <?php echo $success_msg; ?></div>

                    <?php endif; ?>



                    <?php if ($error_msg): ?>

                        <div class="alert alert-error">❌ <?php echo $error_msg; ?></div>

                    <?php endif; ?>



                    <!-- Profil Formu -->

                    <form method="POST" enctype="multipart/form-data">

                        <!-- Kişisel Bilgiler -->

                        <div class="form-section">

                            <h2 class="section-title">👤 Kişisel Bilgiler</h2>

                            <div class="form-grid">

                                <div class="form-group">

                                    <label>Ad Soyad *</label>

                                    <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>

                                </div>



                                <div class="form-group">

                                    <label>Kullanıcı Adı (Değiştirilemez)</label>

                                    <input type="text" value="<?php echo htmlspecialchars($user['username']); ?>" disabled style="background: #f5f5f5;">

                                </div>



                                <div class="form-group">

                                    <label>E-posta *</label>

                                    <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>

                                </div>



                                <div class="form-group">

                                    <label>Telefon</label>

                                    <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="0212 XXX XX XX">

                                </div>



                                <div class="form-group">

                                    <label>Mobil Telefon</label>

                                    <input type="tel" name="mobile" value="<?php echo htmlspecialchars($user['mobile'] ?? ''); ?>" placeholder="05XX XXX XX XX">

                                </div>



                                <div class="form-group">

                                    <label>Profil Resmi</label>

                                    <input type="file" name="profile_image" accept="image/*">

                                    <div class="file-info">JPG, PNG veya GIF - Maksimum 2MB</div>

                                </div>

                            </div>

                        </div>



                        <!-- Şifre Değiştirme -->

                        <div class="form-section">

                            <h2 class="section-title">🔐 Şifre Değiştir (İsteğe Bağlı)</h2>

                            <div class="form-grid">

                                <div class="form-group full-width">

                                    <label>Mevcut Şifre</label>

                                    <input type="password" name="current_password" placeholder="Mevcut şifrenizi girin">

                                </div>



                                <div class="form-group">

                                    <label>Yeni Şifre</label>

                                    <input type="password" name="new_password" placeholder="En az 6 karakter">

                                </div>



                                <div class="form-group">

                                    <label>Yeni Şifre (Tekrar)</label>

                                    <input type="password" name="confirm_password" placeholder="Yeni şifrenizi tekrar girin">

                                </div>

                            </div>

                            <div class="password-info">

                                💡 Şifrenizi değiştirmek istemiyorsanız bu alanları boş bırakabilirsiniz.

                            </div>

                        </div>



                        <!-- Butonlar -->

                        <div class="btn-container">

                            <button type="submit" class="btn btn-primary">💾 Değişiklikleri Kaydet</button>

                            <a href="user-dashboard.php" class="btn btn-secondary">← Geri Dön</a>

                        </div>

                    </form>

                </div>

            </div>

        </div>

    </div>

</body>

=======
session_start();

// Kullanıcı girişi kontrolü
if(!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: index.php");
    exit();
}

// Sadece normal kullanıcılar bu sayfayı kullanabilir
if($_SESSION['user_role'] !== 'user') {
    header("Location: index.php");
    exit();
}

require_once '../config/database.php';

$user_id = $_SESSION['user_id'];

// Kullanıcı bilgilerini çek
$stmt = $db->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute([':id' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$user) {
    session_destroy();
    header("Location: index.php");
    exit();
}

$success_msg = '';
$error_msg = '';

// Form gönderildiyse güncelle
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Form verilerini al
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $mobile = trim($_POST['mobile']);
        
        // E-posta kontrolü (başka kullanıcıda var mı?)
        $email_check = $db->prepare("SELECT id FROM users WHERE email = :email AND id != :id");
        $email_check->execute([':email' => $email, ':id' => $user_id]);
        
        if($email_check->rowCount() > 0) {
            throw new Exception("Bu e-posta adresi başka bir kullanıcı tarafından kullanılıyor!");
        }
        
        // Profil resmi yükleme işlemi
        $profile_image_path = $user['profile_image']; // Mevcut resmi koru
        
        if(isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
            // DÜZELTME: assets klasörü altındaki uploads'a git
            $upload_dir = '../assets/uploads/profiles/';
            
            // Klasör yoksa oluştur
            if(!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Dosya kontrolü
            $file_size = $_FILES['profile_image']['size'];
            $file_tmp = $_FILES['profile_image']['tmp_name'];
            $file_type = $_FILES['profile_image']['type'];
            $file_ext = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
            
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
            $max_size = 2 * 1024 * 1024; // 2MB
            
            if(!in_array($file_ext, $allowed_types)) {
                throw new Exception("Sadece JPG, PNG ve GIF dosyaları yüklenebilir!");
            }
            
            if($file_size > $max_size) {
                throw new Exception("Dosya boyutu en fazla 2MB olabilir!");
            }
            
            // Yeni dosya adı
            $new_filename = 'profile_' . $user_id . '_' . time() . '.' . $file_ext;
            $upload_path = $upload_dir . $new_filename;
            
            // Dosyayı yükle
            if(move_uploaded_file($file_tmp, $upload_path)) {
                // Eski resmi sil
                if($user['profile_image']) {
                    $old_path = '../' . $user['profile_image'];
                    if(file_exists($old_path)) {
                        unlink($old_path);
                    }
                }
                // DÜZELTME: Veritabanına doğru yolu kaydet
                $profile_image_path = 'assets/uploads/profiles/' . $new_filename;
            }
        }
        
        // Veritabanını güncelle
        $update_sql = "UPDATE users SET 
                      full_name = :full_name,
                      email = :email,
                      phone = :phone,
                      mobile = :mobile,
                      profile_image = :profile_image
                      WHERE id = :id";
        
        $update_params = [
            ':full_name' => $full_name,
            ':email' => $email,
            ':phone' => $phone,
            ':mobile' => $mobile,
            ':profile_image' => $profile_image_path,
            ':id' => $user_id
        ];
        
        // Şifre değiştirilecekse
        if(!empty($_POST['new_password'])) {
            $current_password = $_POST['current_password'];
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];
            
            // Mevcut şifre kontrolü
            if(!password_verify($current_password, $user['password'])) {
                throw new Exception("Mevcut şifreniz yanlış!");
            }
            
            // Yeni şifre kontrolü
            if($new_password !== $confirm_password) {
                throw new Exception("Yeni şifreler eşleşmiyor!");
            }
            
            if(strlen($new_password) < 6) {
                throw new Exception("Yeni şifre en az 6 karakter olmalı!");
            }
            
            // Şifreyi güncelle
            $update_sql = "UPDATE users SET 
                          full_name = :full_name,
                          email = :email,
                          phone = :phone,
                          mobile = :mobile,
                          profile_image = :profile_image,
                          password = :password
                          WHERE id = :id";
            
            $update_params[':password'] = password_hash($new_password, PASSWORD_DEFAULT);
        }
        
        $stmt = $db->prepare($update_sql);
        $stmt->execute($update_params);
        
        // Session bilgilerini güncelle
        $_SESSION['user_fullname'] = $full_name;
        
        $success_msg = "Profiliniz başarıyla güncellendi!";
        
        // Güncel bilgileri tekrar çek
        $stmt = $db->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute([':id' => $user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch(Exception $e) {
        $error_msg = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profilim - <?php echo htmlspecialchars($user['full_name']); ?></title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .profile-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .profile-header {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 30px;
            border-bottom: 2px solid #f0f0f0;
        }
        .profile-avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid #3498db;
            margin: 0 auto 20px;
            display: block;
        }
        .no-avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 60px;
            color: white;
        }
        .profile-name {
            font-size: 28px;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        .profile-username {
            color: #7f8c8d;
            font-size: 18px;
        }
        .form-section {
            margin-bottom: 30px;
        }
        .section-title {
            font-size: 18px;
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #3498db;
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
            grid-column: span 2;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
        }
        input[type="text"],
        input[type="email"],
        input[type="tel"],
        input[type="password"],
        input[type="file"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }
        input:focus {
            border-color: #3498db;
            outline: none;
            box-shadow: 0 0 0 3px rgba(52,152,219,0.1);
        }
        .file-info {
            font-size: 12px;
            color: #999;
            margin-top: 5px;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
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
        .btn-container {
            text-align: center;
            margin-top: 30px;
            padding-top: 30px;
            border-top: 2px solid #f0f0f0;
        }
        .btn {
            padding: 12px 40px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s;
            margin: 0 10px;
        }
        .btn-primary {
            background: #3498db;
            color: white;
        }
        .btn-primary:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }
        .btn-secondary {
            background: #95a5a6;
            color: white;
            text-decoration: none;
            display: inline-block;
        }
        .btn-secondary:hover {
            background: #7f8c8d;
        }
        .password-info {
            background: #f0f8ff;
            padding: 10px;
            border-radius: 5px;
            font-size: 13px;
            color: #2c5282;
            margin-top: 10px;
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
                    <a href="user-dashboard.php">
                        <span class="icon">🏠</span>
                        <span>Ana Sayfa</span>
                    </a>
                </li>
                <li>
                    <a href="my-properties.php">
                        <span class="icon">🏢</span>
                        <span>İlanlarım</span>
                    </a>
                </li>
                <li>
                    <a href="properties/add-step1.php">
                        <span class="icon">➕</span>
                        <span>İlan Ekle</span>
                    </a>
                </li>
                <li>
                    <a href="my-profile.php" class="active">
                        <span class="icon">👤</span>
                        <span>Profilim</span>
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Main Content -->
        <div class="main-content">
            <div class="top-navbar">
                <div class="navbar-left">
                    <h3>Profil Düzenle</h3>
                </div>
                <div class="navbar-right">
                    <span>👤 <?php echo htmlspecialchars($user['full_name']); ?></span>
                    <a href="logout.php" class="btn-logout">Çıkış</a>
                </div>
            </div>

            <div class="content">
                <div class="profile-container">
                    <!-- Profil Başlığı -->
                    <div class="profile-header">
                        <?php if($user['profile_image']): ?>
                            <img src="../<?php echo $user['profile_image']; ?>" alt="Profil" class="profile-avatar">
                        <?php else: ?>
                            <div class="no-avatar">👤</div>
                        <?php endif; ?>
                        <h1 class="profile-name"><?php echo htmlspecialchars($user['full_name']); ?></h1>
                        <p class="profile-username">@<?php echo htmlspecialchars($user['username']); ?></p>
                    </div>

                    <!-- Mesajlar -->
                    <?php if($success_msg): ?>
                        <div class="alert alert-success">✅ <?php echo $success_msg; ?></div>
                    <?php endif; ?>
                    
                    <?php if($error_msg): ?>
                        <div class="alert alert-error">❌ <?php echo $error_msg; ?></div>
                    <?php endif; ?>

                    <!-- Profil Formu -->
                    <form method="POST" enctype="multipart/form-data">
                        <!-- Kişisel Bilgiler -->
                        <div class="form-section">
                            <h2 class="section-title">👤 Kişisel Bilgiler</h2>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Ad Soyad *</label>
                                    <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label>Kullanıcı Adı (Değiştirilemez)</label>
                                    <input type="text" value="<?php echo htmlspecialchars($user['username']); ?>" disabled style="background: #f5f5f5;">
                                </div>
                                
                                <div class="form-group">
                                    <label>E-posta *</label>
                                    <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label>Telefon</label>
                                    <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="0212 XXX XX XX">
                                </div>
                                
                                <div class="form-group">
                                    <label>Mobil Telefon</label>
                                    <input type="tel" name="mobile" value="<?php echo htmlspecialchars($user['mobile'] ?? ''); ?>" placeholder="05XX XXX XX XX">
                                </div>
                                
                                <div class="form-group">
                                    <label>Profil Resmi</label>
                                    <input type="file" name="profile_image" accept="image/*">
                                    <div class="file-info">JPG, PNG veya GIF - Maksimum 2MB</div>
                                </div>
                            </div>
                        </div>

                        <!-- Şifre Değiştirme -->
                        <div class="form-section">
                            <h2 class="section-title">🔐 Şifre Değiştir (İsteğe Bağlı)</h2>
                            <div class="form-grid">
                                <div class="form-group full-width">
                                    <label>Mevcut Şifre</label>
                                    <input type="password" name="current_password" placeholder="Mevcut şifrenizi girin">
                                </div>
                                
                                <div class="form-group">
                                    <label>Yeni Şifre</label>
                                    <input type="password" name="new_password" placeholder="En az 6 karakter">
                                </div>
                                
                                <div class="form-group">
                                    <label>Yeni Şifre (Tekrar)</label>
                                    <input type="password" name="confirm_password" placeholder="Yeni şifrenizi tekrar girin">
                                </div>
                            </div>
                            <div class="password-info">
                                💡 Şifrenizi değiştirmek istemiyorsanız bu alanları boş bırakabilirsiniz.
                            </div>
                        </div>

                        <!-- Butonlar -->
                        <div class="btn-container">
                            <button type="submit" class="btn btn-primary">💾 Değişiklikleri Kaydet</button>
                            <a href="user-dashboard.php" class="btn btn-secondary">← Geri Dön</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
>>>>>>> 1b4657b0e4a9c21769cd70a9511bc87296025d5f
</html>