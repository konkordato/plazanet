<?php
session_start();

// Kullanıcı girişi kontrolü
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_role'] !== 'user') {
    header("Location: index.php");
    exit();
}

require_once '../config/database.php';

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_fullname'];

// Kullanıcının ilanlarını çek
$query = "SELECT p.*, pi.image_path 
          FROM properties p 
          LEFT JOIN property_images pi ON p.id = pi.property_id AND pi.is_main = 1
          WHERE p.user_id = :user_id
          ORDER BY p.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute([':user_id' => $user_id]);
$properties = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <title>İlanlarım - <?php echo htmlspecialchars($user_name); ?></title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .properties-table {
            width: 100%;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }

        .properties-table thead {
            background: #f8f9fa;
        }

        .properties-table th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #495057;
            border-bottom: 2px solid #dee2e6;
        }

        .properties-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #dee2e6;
        }

        .property-thumb {
            width: 60px;
            height: 45px;
            object-fit: cover;
            border-radius: 4px;
        }

        .no-image {
            width: 60px;
            height: 45px;
            background: #f8f9fa;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #adb5bd;
        }

        .status-badge {
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 0.85rem;
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

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .btn-small {
            padding: 6px 12px;
            font-size: 0.9rem;
            border-radius: 4px;
            text-decoration: none;
            display: inline-block;
            border: none;
            cursor: pointer;
        }

        .btn-edit {
            background: #ffc107;
            color: #212529;
        }

        .btn-view {
            background: #17a2b8;
            color: white;
        }

        .btn-delete {
            background: #dc3545;
            color: white;
        }

        .add-new-btn {
            background: #28a745;
            color: white;
            padding: 12px 24px;
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
                    <a href="user-dashboard.php">
                        <span class="icon">🏠</span>
                        <span>Ana Sayfa</span>
                    </a>
                </li>
                <li>
                    <a href="my-properties.php" class="active">
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
                    <a href="crm/index.php">
                        <span class="icon">📊</span>
                        <span>CRM Sistemi</span>
                    </a>
                </li>
                <li>
                    <a href="my-profile.php">
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
                    <h3>İlanlarım</h3>
                </div>
                <div class="navbar-right">
                    <span>👤 <?php echo htmlspecialchars($user_name); ?></span>
                    <a href="logout.php" class="btn-logout">Çıkış</a>
                </div>
            </div>

            <div class="content">
                <!-- Mesajlar -->
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>

                <a href="properties/add-step1.php" class="add-new-btn">➕ Yeni İlan Ekle</a>

                <?php if (count($properties) > 0): ?>
                    <div class="properties-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Resim</th>
                                    <th>İlan No</th>
                                    <th>Başlık</th>
                                    <th>Fiyat</th>
                                    <th>Tip</th>
                                    <th>İlçe</th>
                                    <th>Durum</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($properties as $property): ?>
                                    <tr>
                                        <td>
                                            <?php if ($property['image_path']): ?>
                                                <img src="../<?php echo $property['image_path']; ?>"
                                                    alt="<?php echo $property['baslik']; ?>"
                                                    class="property-thumb">
                                            <?php else: ?>
                                                <div class="no-image">📷</div>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $property['ilan_no']; ?></td>
                                        <td><?php echo htmlspecialchars($property['baslik']); ?></td>
                                        <td><?php echo number_format($property['fiyat'], 0, ',', '.'); ?> ₺</td>
                                        <td><?php echo $property['kategori']; ?></td>
                                        <td><?php echo $property['ilce']; ?></td>
                                        <td>
                                            <span class="status-badge <?php echo $property['durum'] == 'aktif' ? 'status-active' : 'status-passive'; ?>">
                                                <?php echo ucfirst($property['durum']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="my-property-edit.php?id=<?php echo $property['id']; ?>"
                                                    class="btn-small btn-edit">Düzenle</a>
                                                <a href="../pages/detail.php?id=<?php echo $property['id']; ?>"
                                                    target="_blank"
                                                    class="btn-small btn-view">Görüntüle</a>
                                                <button onclick="deleteProperty(<?php echo $property['id']; ?>)"
                                                    class="btn-small btn-delete">Sil</button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <h3>Henüz ilan eklenmemiş</h3>
                        <p>İlk ilanınızı eklemek için yukarıdaki butonu kullanın.</p>
                        <a href="properties/add-step1.php" class="add-new-btn" style="margin-top: 20px;">
                            ➕ İlk İlanımı Ekle
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function deleteProperty(id) {
            if (confirm('Bu ilanı silmek istediğinize emin misiniz?')) {
                window.location.href = 'my-property-delete.php?id=' + id;
            }
        }
    </script>
</body>

</html>