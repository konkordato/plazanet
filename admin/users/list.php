<?php
session_start();
if(!isset($_SESSION['admin_logged_in'])) {
    header("Location: ../index.php");
    exit();
}

require_once '../../config/database.php';

// Kullanıcıları çek
$stmt = $db->query("SELECT * FROM users ORDER BY id DESC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kullanıcı Yönetimi - Admin Panel</title>
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <style>
        .user-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .user-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        .user-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 20px;
        }
        .user-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #3498db;
        }
        .no-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            color: #999;
            border: 3px solid #ddd;
        }
        .user-info h3 {
            margin: 0 0 5px 0;
            color: #2c3e50;
            font-size: 20px;
        }
        .user-username {
            color: #7f8c8d;
            font-size: 14px;
        }
        .user-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .detail-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #555;
        }
        .detail-icon {
            color: #3498db;
        }
        .user-stats {
            display: flex;
            gap: 20px;
            padding-top: 15px;
            border-top: 1px solid #ecf0f1;
        }
        .stat-item {
            flex: 1;
            text-align: center;
        }
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
        }
        .stat-label {
            color: #7f8c8d;
            font-size: 12px;
        }
        .user-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
        }
        .btn {
            padding: 8px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s;
        }
        .btn-edit {
            background: #3498db;
            color: white;
        }
        .btn-edit:hover {
            background: #2980b9;
        }
        .btn-delete {
            background: #e74c3c;
            color: white;
        }
        .btn-delete:hover {
            background: #c0392b;
        }
        .btn-status {
            background: #27ae60;
            color: white;
        }
        .btn-status.inactive {
            background: #95a5a6;
        }
        .add-btn {
            background: #27ae60;
            color: white;
            padding: 12px 30px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 20px;
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
                    <h3>Kullanıcı Yönetimi</h3>
                </div>
                <div class="navbar-right">
                    <span>Admin: <?php echo $_SESSION['admin_username']; ?></span>
                    <a href="../logout.php" class="btn-logout">Çıkış</a>
                </div>
            </div>

            <div class="content">
                <?php if(isset($_SESSION['success'])): ?>
                    <div class="alert alert-success">
                        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>

                <a href="add.php" class="add-btn">+ Yeni Kullanıcı Ekle</a>

                <?php foreach($users as $user): ?>
                <div class="user-card">
                    <div class="user-header">
                        <?php if($user['profile_image']): ?>
                            <img src="../../<?php echo $user['profile_image']; ?>" alt="Profil" class="user-avatar">
                        <?php else: ?>
                            <div class="no-avatar">👤</div>
                        <?php endif; ?>
                        
                        <div class="user-info">
                            <h3><?php echo htmlspecialchars($user['full_name']); ?></h3>
                            <div class="user-username">@<?php echo htmlspecialchars($user['username']); ?></div>
                        </div>
                    </div>

                    <div class="user-details">
                        <div class="detail-item">
                            <span class="detail-icon">✉️</span>
                            <span><?php echo htmlspecialchars($user['email']); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-icon">📱</span>
                            <span><?php echo htmlspecialchars($user['phone'] ?: 'Belirtilmemiş'); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-icon">📅</span>
                            <span>Kayıt: <?php echo date('d.m.Y', strtotime($user['created_at'] ?? 'now')); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-icon">🔒</span>
                            <span>Son Giriş: <?php echo $user['last_login'] ? date('d.m.Y H:i', strtotime($user['last_login'])) : 'Henüz giriş yapmadı'; ?></span>
                        </div>
                    </div>

                    <div class="user-stats">
                        <div class="stat-item">
                            <div class="stat-value">
                                <?php 
                                // İlan sayısını çek
                                $stmt = $db->prepare("SELECT COUNT(*) as count FROM properties WHERE user_id = :user_id");
                                $stmt->execute([':user_id' => $user['id']]);
                                echo $stmt->fetch()['count'] ?? 0;
                                ?>
                            </div>
                            <div class="stat-label">İlan</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">
                                <?php echo date('d.m.Y', strtotime($user['created_at'] ?? 'now')); ?>
                            </div>
                            <div class="stat-label">Kayıt Tarihi</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">
                                <?php echo $user['last_login'] ? date('d.m.Y', strtotime($user['last_login'])) : '-'; ?>
                            </div>
                            <div class="stat-label">Son Giriş</div>
                        </div>
                    </div>

                    <div class="user-actions">
                        <a href="edit.php?id=<?php echo $user['id']; ?>" class="btn btn-edit">
                            ✏️ Düzenle
                        </a>
                        <button class="btn btn-status <?php echo $user['status'] == 'active' ? '' : 'inactive'; ?>">
                            <?php echo $user['status'] == 'active' ? '✅ Aktif' : '⏸️ Pasif'; ?>
                        </button>
                        <?php if($user['username'] != 'admin'): ?>
                        <button onclick="deleteUser(<?php echo $user['id']; ?>)" class="btn btn-delete">
                            🗑️ Sil
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script>
    function deleteUser(id) {
        if(confirm('Bu kullanıcıyı silmek istediğinize emin misiniz?')) {
            window.location.href = 'delete.php?id=' + id;
        }
    }
    </script>
</body>
</html>