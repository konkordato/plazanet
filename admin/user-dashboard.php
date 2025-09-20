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
    <title>Kullanıcı Yönetimi</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f5f5f5;
        }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .header {
            background: white;
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header h1 { color: #333; }
        .btn-logout {
            background: #e74c3c;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
        }
        .btn-add {
            background: #27ae60;
            color: white;
            padding: 12px 25px;
            text-decoration: none;
            border-radius: 5px;
            display: inline-block;
            margin-bottom: 20px;
            font-weight: 500;
        }
        .user-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .user-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 15px;
        }
        .user-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #3498db;
        }
        .no-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: #ecf0f1;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: #95a5a6;
        }
        .user-info { flex: 1; }
        .user-name { 
            font-size: 18px; 
            font-weight: 600; 
            color: #2c3e50;
            margin-bottom: 5px;
        }
        .user-details {
            display: flex;
            gap: 20px;
            color: #7f8c8d;
            font-size: 14px;
        }
        .user-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .user-actions {
            display: flex;
            gap: 10px;
        }
        .btn {
            padding: 8px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .btn-edit {
            background: #3498db;
            color: white;
        }
        .btn-edit:hover {
            background: #2980b9;
        }
        .btn-view {
            background: #9b59b6;
            color: white;
        }
        .btn-delete {
            background: #e74c3c;
            color: white;
        }
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        .status-passive {
            background: #f8d7da;
            color: #721c24;
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
    <div class="container">
        <div class="header">
            <h1>Kullanıcı Yönetimi</h1>
            <div>
                <span style="margin-right: 20px;">Admin: <?php echo $_SESSION['admin_username']; ?></span>
                <a href="../logout.php" class="btn-logout">Çıkış</a>
            </div>
        </div>

        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <a href="add.php" class="btn-add">➕ Yeni Kullanıcı Ekle</a>

        <?php foreach($users as $user): ?>
        <div class="user-card">
            <div class="user-header">
                <?php if($user['profile_image']): ?>
                    <img src="../../<?php echo $user['profile_image']; ?>" alt="Profil" class="user-avatar">
                <?php else: ?>
                    <div class="no-avatar">👤</div>
                <?php endif; ?>
                
                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars($user['full_name']); ?></div>
                    <div class="user-details">
                        <span>👤 <?php echo htmlspecialchars($user['username']); ?></span>
                        <span class="status-badge <?php echo $user['status'] == 'active' ? 'status-active' : 'status-passive'; ?>">
                            <?php echo $user['status'] == 'active' ? '✓ Aktif' : '✗ Pasif'; ?>
                        </span>
                    </div>
                </div>
            </div>

            <div class="user-meta">
                <div class="meta-item">
                    <span>📧</span>
                    <span><?php echo htmlspecialchars($user['email']); ?></span>
                </div>
                <div class="meta-item">
                    <span>📱</span>
                    <span><?php echo htmlspecialchars($user['phone'] ?: 'Belirtilmemiş'); ?></span>
                </div>
                <div class="meta-item">
                    <span>🏢</span>
                    <span>
                        <?php 
                        // İlan sayısını hesapla
                        $stmt = $db->prepare("SELECT COUNT(*) as count FROM properties WHERE created_by = :user_id OR user_id = :user_id2");
                        $stmt->execute([':user_id' => $user['id'], ':user_id2' => $user['id']]);
                        $count = $stmt->fetch()['count'] ?? 0;
                        echo $count . " İlan";
                        ?>
                    </span>
                </div>
                <div class="meta-item">
                    <span>📅</span>
                    <span>Kayıt: <?php echo date('d.m.Y', strtotime($user['created_at'])); ?></span>
                </div>
                <div class="meta-item">
                    <span>🔒</span>
                    <span>Son Giriş: <?php echo $user['last_login'] ? date('d.m.Y H:i', strtotime($user['last_login'])) : 'Hiç giriş yapmadı'; ?></span>
                </div>
            </div>

            <div class="user-actions">
                <a href="edit.php?id=<?php echo $user['id']; ?>" class="btn btn-edit">
                    ✏️ Düzenle
                </a>
                <a href="my-properties.php?user_id=<?php echo $user['id']; ?>" class="btn btn-view">
                    🏠 İlanları Gör
                </a>
                <?php if($user['username'] != 'admin'): ?>
                <button onclick="deleteUser(<?php echo $user['id']; ?>)" class="btn btn-delete">
                    🗑️ Sil
                </button>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <script>
    function deleteUser(id) {
        if(confirm('Bu kullanıcıyı silmek istediğinize emin misiniz?')) {
            window.location.href = 'my-property-delete.php?user_id=' + id;
        }
    }
    </script>
</body>
</html>