<?php
session_start();
// Admin kontrolü
if(!isset($_SESSION['admin_logged_in']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

require_once '../../config/database.php';

// Kullanıcıları çek
$stmt = $db->prepare("
    SELECT u.*, 
           (SELECT COUNT(*) FROM properties WHERE user_id = u.id) as total_properties
    FROM users u 
    WHERE u.role = 'user'
    ORDER BY u.created_at DESC
");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Mesajları göster
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kullanıcı Yönetimi - Plaza Emlak</title>
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <style>
        .user-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .user-info {
            flex: 1;
        }
        .user-name {
            font-size: 18px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        .user-details {
            color: #666;
            font-size: 14px;
            line-height: 1.5;
        }
        .user-stats {
            display: flex;
            gap: 20px;
            margin-top: 10px;
        }
        .stat-item {
            background: #f8f9fa;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 13px;
        }
        .user-actions {
            display: flex;
            gap: 10px;
        }
        .btn-edit {
            background: #3498db;
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 14px;
        }
        .btn-delete {
            background: #e74c3c;
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 14px;
        }
        .btn-toggle {
            background: #95a5a6;
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 14px;
        }
        .btn-toggle.active {
            background: #27ae60;
        }
        .add-user-btn {
            background: #27ae60;
            color: white;
            padding: 12px 25px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 20px;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 10px;
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
                    <a href="../properties/list.php">
                        <span class="icon">🏢</span>
                        <span>İlanlar</span>
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
                    <h3>Kullanıcı Yönetimi</h3>
                </div>
                <div class="navbar-right">
                    <span>Admin: <?php echo $_SESSION['admin_username']; ?></span>
                    <a href="../logout.php" class="btn-logout">Çıkış</a>
                </div>
            </div>

            <div class="content">
                <!-- Mesajlar -->
                <?php if($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                <?php if($error): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>

                <!-- Yeni Kullanıcı Butonu -->
                <a href="add.php" class="add-user-btn">➕ Yeni Kullanıcı Ekle</a>

                <!-- Kullanıcı Listesi -->
                <?php if(count($users) > 0): ?>
                    <?php foreach($users as $user): ?>
                        <div class="user-card">
                            <div class="user-info">
                                <div class="user-name">
                                    <?php echo htmlspecialchars($user['full_name']); ?>
                                    <?php if($user['status'] == 'passive'): ?>
                                        <span style="color: #e74c3c; font-size: 12px;">(Pasif)</span>
                                    <?php endif; ?>
                                </div>
                                <div class="user-details">
                                    <div>👤 Kullanıcı Adı: <?php echo htmlspecialchars($user['username']); ?></div>
                                    <div>📧 E-posta: <?php echo htmlspecialchars($user['email']); ?></div>
                                    <div>📞 Telefon: <?php echo htmlspecialchars($user['phone'] ?? '-'); ?></div>
                                </div>
                                <div class="user-stats">
                                    <span class="stat-item">
                                        🏠 <?php echo $user['total_properties']; ?> İlan
                                    </span>
                                    <span class="stat-item">
                                        📅 Kayıt: <?php echo date('d.m.Y', strtotime($user['created_at'])); ?>
                                    </span>
                                    <?php if($user['last_login']): ?>
                                        <span class="stat-item">
                                            🕒 Son Giriş: <?php echo date('d.m.Y H:i', strtotime($user['last_login'])); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="user-actions">
                                <a href="edit.php?id=<?php echo $user['id']; ?>" class="btn-edit">
                                    ✏️ Düzenle
                                </a>
                                <a href="toggle-status.php?id=<?php echo $user['id']; ?>" 
                                   class="btn-toggle <?php echo $user['status'] == 'active' ? 'active' : ''; ?>">
                                    <?php echo $user['status'] == 'active' ? '✓ Aktif' : '✗ Pasif'; ?>
                                </a>
                                <?php if($user['total_properties'] == 0): ?>
                                    <a href="delete.php?id=<?php echo $user['id']; ?>" 
                                       class="btn-delete"
                                       onclick="return confirm('Bu kullanıcıyı silmek istediğinize emin misiniz?')">
                                        🗑️ Sil
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <h3>Henüz kullanıcı eklenmemiş</h3>
                        <p>Yeni kullanıcı eklemek için yukarıdaki butonu kullanın.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>