<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: ../index.php");
    exit();
}

require_once '../../config/database.php';

// İlanları, ana resimleri ve kullanıcı bilgilerini çek
$query = "SELECT p.*, pi.image_path, u.username, u.full_name 
          FROM properties p 
          LEFT JOIN property_images pi ON p.id = pi.property_id AND pi.is_main = 1
          LEFT JOIN users u ON p.user_id = u.id OR p.ekleyen_admin_id = u.id
          ORDER BY p.created_at DESC";
$stmt = $db->query($query);
$properties = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>İlan Listesi - Plaza Emlak</title>
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <style>
        .properties-table {
            width: 100%;
            background: white;
            border-radius: 8px;
            overflow-x: auto;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }

        .properties-table table {
            width: 100%;
            min-width: 1100px;
        }

        .properties-table thead {
            background: #f8f9fa;
        }

        .properties-table th {
            padding: 15px 10px;
            text-align: left;
            font-weight: 600;
            color: #495057;
            border-bottom: 2px solid #dee2e6;
            white-space: nowrap;
        }

        .properties-table td {
            padding: 12px 10px;
            border-bottom: 1px solid #dee2e6;
        }

        /* Başlık sütunu için genişlik sınırı */
        .properties-table td:nth-child(3) {
            max-width: 200px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Danışman sütunu için genişlik sınırı */
        .properties-table td:nth-child(7) {
            max-width: 120px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
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
            gap: 5px;
            flex-wrap: nowrap;
        }

        .btn-small {
            padding: 5px 10px;
            font-size: 0.85rem;
            border-radius: 4px;
            text-decoration: none;
            display: inline-block;
            border: none;
            cursor: pointer;
            white-space: nowrap;
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
                    <a href="list.php" class="active">
                        <span class="icon">🏢</span>
                        <span>İlanlar</span>
                    </a>
                </li>
                <li>
                    <a href="add-step1.php">
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
            </ul>
        </nav>

        <!-- Main Content -->
        <div class="main-content">
            <div class="top-navbar">
                <div class="navbar-left">
                    <h3>İlan Listesi</h3>
                </div>
                <div class="navbar-right">
                    <span>Hoş geldin, <?php echo $_SESSION['admin_username']; ?></span>
                    <a href="../logout.php" class="btn-logout">Çıkış</a>
                </div>
            </div>

            <div class="content">
                <a href="add-step1.php" class="add-new-btn">➕ Yeni İlan Ekle</a>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success">
                        <?php
                        echo $_SESSION['success'];
                        unset($_SESSION['success']);
                        ?>
                    </div>
                <?php endif; ?>

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
                                <th>Danışman</th>
                                <th>Durum</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($properties) > 0): ?>
                                <?php foreach ($properties as $property): ?>
                                    <tr>
                                        <td>
                                            <?php if ($property['image_path']): ?>
                                                <img src="../../<?php echo $property['image_path']; ?>"
                                                    alt="<?php echo $property['baslik']; ?>"
                                                    class="property-thumb">
                                            <?php else: ?>
                                                <div class="no-image">📷</div>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $property['ilan_no']; ?></td>
                                        <td title="<?php echo htmlspecialchars($property['baslik']); ?>">
                                            <?php echo htmlspecialchars($property['baslik']); ?>
                                        </td>
                                        <td><?php echo number_format($property['fiyat'], 0, ',', '.'); ?> ₺</td>
                                        <td><?php echo $property['kategori']; ?></td>
                                        <td><?php echo $property['ilce']; ?></td>
                                        <td title="<?php 
                                            $danisman = $property['full_name'] ?? $property['username'] ?? 'Plaza Emlak';
                                            echo htmlspecialchars($danisman);
                                        ?>">
                                            <?php 
                                            $danisman_adi = $property['full_name'] ?? $property['username'] ?? 'Plaza Emlak';
                                            echo htmlspecialchars($danisman_adi); 
                                            ?>
                                        </td>
                                        <td>
                                            <span class="status-badge <?php echo $property['durum'] == 'aktif' ? 'status-active' : 'status-passive'; ?>">
                                                <?php echo ucfirst($property['durum']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="edit.php?id=<?php echo $property['id']; ?>"
                                                    class="btn-small btn-edit">Düzenle</a>
                                                <a href="../../pages/detail.php?id=<?php echo $property['id']; ?>"
                                                    target="_blank"
                                                    class="btn-small btn-view">Görüntüle</a>
                                                <button onclick="deleteProperty(<?php echo $property['id']; ?>)"
                                                    class="btn-small btn-delete">Sil</button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" style="text-align: center; padding: 40px;">
                                        Henüz ilan eklenmemiş. <a href="add-step1.php">İlk ilanınızı ekleyin</a>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        function deleteProperty(id) {
            if (confirm('Bu ilanı silmek istediğinize emin misiniz?')) {
                window.location.href = 'ajax/delete-property.php?id=' + id;
            }
        }
    </script>
</body>

</html>