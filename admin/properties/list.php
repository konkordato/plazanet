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
        /* Ana içerik alanı için override */
        .main-content {
            margin-left: 250px;
            width: calc(100% - 250px);
            overflow-x: hidden;
        }

        .content {
            padding: 15px;
            max-width: 100%;
            overflow-x: auto;
        }

        /* Tablo container */
        .table-wrapper {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            width: 100%;
            overflow-x: auto;
            overflow-y: hidden;
        }

        /* Ultra kompakt tablo tasarımı */
        .properties-table {
            width: 100%;
            min-width: 850px;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .properties-table thead {
            background: #f8f9fa;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .properties-table th {
            padding: 8px 4px;
            text-align: left;
            font-weight: 600;
            color: #495057;
            border-bottom: 2px solid #dee2e6;
            font-size: 0.8rem;
            white-space: nowrap;
        }

        .properties-table tbody tr {
            border-bottom: 1px solid #dee2e6;
            transition: background-color 0.2s;
        }

        .properties-table tbody tr:hover {
            background-color: #f8f9fa;
        }

        .properties-table td {
            padding: 6px 4px;
            vertical-align: middle;
            font-size: 0.8rem;
            overflow: hidden;
        }

        /* Ultra optimize kolon genişlikleri */
        .col-img { width: 50px; text-align: center; }
        .col-no { width: 70px; }
        .col-title { width: 200px; }
        .col-price { width: 85px; text-align: right; }
        .col-type { width: 60px; text-align: center; }
        .col-district { width: 70px; }
        .col-advisor { width: 90px; }
        .col-status { width: 55px; text-align: center; }
        .col-actions { width: 120px; text-align: center; }

        /* Başlık ve danışman kısaltma */
        .text-truncate {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: block;
        }

        /* Ultra küçük resim */
        .property-thumb {
            width: 40px;
            height: 30px;
            object-fit: cover;
            border-radius: 3px;
            display: block;
            margin: 0 auto;
        }

        .no-image {
            width: 40px;
            height: 30px;
            background: #f8f9fa;
            border-radius: 3px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #adb5bd;
            font-size: 0.9rem;
            margin: 0 auto;
        }

        /* Mini durum badge */
        .status-badge {
            padding: 2px 4px;
            border-radius: 2px;
            font-size: 0.7rem;
            font-weight: 500;
            display: inline-block;
        }

        .status-active {
            background: #d4edda;
            color: #155724;
        }

        .status-passive {
            background: #f8d7da;
            color: #721c24;
        }

        /* Ultra mini butonlar - Tek satırda */
        .action-buttons {
            display: flex;
            gap: 1px;
            justify-content: center;
        }

        .btn-icon {
            width: 26px;
            height: 26px;
            padding: 0;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            text-decoration: none;
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

        .btn-icon:hover {
            transform: scale(1.1);
            opacity: 0.9;
        }

        /* Yeni ilan ekle butonu */
        .add-new-btn {
            background: #28a745;
            color: white;
            padding: 8px 16px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 10px;
            font-size: 0.85rem;
            transition: all 0.3s;
        }

        .add-new-btn:hover {
            background: #218838;
            transform: translateY(-2px);
            box-shadow: 0 3px 10px rgba(40, 167, 69, 0.3);
        }

        /* Alert mesajı */
        .alert {
            padding: 8px 12px;
            border-radius: 5px;
            margin-bottom: 10px;
            font-size: 0.85rem;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        /* Fiyat formatı */
        .price-cell {
            font-weight: 600;
            color: #28a745;
            font-size: 0.75rem;
        }

        /* İlan no küçültme */
        .ilan-no {
            font-size: 0.75rem;
            color: #6c757d;
        }

        /* Tooltip için title gösterimi */
        [title] {
            cursor: help;
        }

        /* 1600px üstü geniş ekranlar için */
        @media (min-width: 1600px) {
            .properties-table {
                min-width: auto;
            }
            
            .col-title { width: 250px; }
            .col-advisor { width: 120px; }
            
            .properties-table th,
            .properties-table td {
                padding: 8px 6px;
                font-size: 0.85rem;
            }
        }

        /* 1400px altı için */
        @media (max-width: 1400px) {
            .properties-table {
                min-width: 800px;
            }
            
            .col-title { width: 180px; }
            .col-price { width: 80px; }
            .col-actions { width: 100px; }
        }

        /* Küçük laptop (1200px) */
        @media (max-width: 1200px) {
            .main-content {
                margin-left: 200px;
                width: calc(100% - 200px);
            }
            
            .sidebar {
                width: 200px;
            }

            .properties-table {
                min-width: 750px;
                font-size: 0.75rem;
            }

            .properties-table th,
            .properties-table td {
                padding: 5px 3px;
            }

            .btn-icon {
                width: 24px;
                height: 24px;
                font-size: 0.7rem;
            }
        }

        /* Tablet (992px) */
        @media (max-width: 992px) {
            .properties-table {
                min-width: 700px;
            }
            
            .col-type { display: none; }
            .properties-table th:nth-child(5),
            .properties-table td:nth-child(5) { display: none; }
        }

        /* Mobil (768px) */
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                width: 100%;
            }

            .sidebar {
                display: none;
            }

            /* Mobilde kart görünümü */
            .table-wrapper {
                overflow-x: visible;
            }

            .properties-table {
                min-width: unset;
            }

            .properties-table thead {
                display: none;
            }

            .properties-table tbody {
                display: block;
            }

            .properties-table tr {
                display: block;
                margin-bottom: 12px;
                background: white;
                border: 1px solid #dee2e6;
                border-radius: 8px;
                padding: 12px;
                box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            }

            .properties-table td {
                display: flex;
                justify-content: space-between;
                padding: 4px 0;
                border: none;
                align-items: center;
                width: 100% !important;
            }

            .properties-table td:before {
                content: attr(data-label);
                font-weight: 600;
                color: #495057;
                flex-shrink: 0;
                margin-right: 10px;
                font-size: 0.8rem;
            }

            .property-thumb,
            .no-image {
                width: 80px;
                height: 60px;
            }

            .text-truncate {
                max-width: none;
                white-space: normal;
            }

            .action-buttons {
                width: auto;
                justify-content: flex-end;
            }

            .btn-icon {
                width: 28px;
                height: 28px;
            }

            /* Resim satırını özel düzenle */
            .properties-table td:first-child {
                justify-content: center;
            }

            .properties-table td:first-child:before {
                display: none;
            }
        }

        /* Çok küçük ekranlar (480px) */
        @media (max-width: 480px) {
            .content {
                padding: 10px;
            }

            .add-new-btn {
                width: 100%;
                text-align: center;
                padding: 10px;
            }
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
                    <a href="../crm/index.php">
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
                    <span style="font-size: 0.9rem;">Hoş geldin, <?php echo $_SESSION['admin_username']; ?></span>
                    <a href="../logout.php" class="btn-logout" style="font-size: 0.85rem; padding: 6px 12px;">Çıkış</a>
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

                <div class="table-wrapper">
                    <table class="properties-table">
                        <thead>
                            <tr>
                                <th class="col-img">Resim</th>
                                <th class="col-no">İlan No</th>
                                <th class="col-title">Başlık</th>
                                <th class="col-price">Fiyat</th>
                                <th class="col-type">Tip</th>
                                <th class="col-district">İlçe</th>
                                <th class="col-advisor">Danışman</th>
                                <th class="col-status">Durum</th>
                                <th class="col-actions">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($properties) > 0): ?>
                                <?php foreach ($properties as $property): ?>
                                    <tr>
                                        <td class="col-img" data-label="Resim">
                                            <?php if ($property['image_path']): ?>
                                                <img src="../../<?php echo $property['image_path']; ?>"
                                                    alt="<?php echo $property['baslik']; ?>"
                                                    class="property-thumb">
                                            <?php else: ?>
                                                <div class="no-image">📷</div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="col-no" data-label="İlan No">
                                            <span class="ilan-no"><?php echo $property['ilan_no']; ?></span>
                                        </td>
                                        <td class="col-title" data-label="Başlık">
                                            <span class="text-truncate" title="<?php echo htmlspecialchars($property['baslik']); ?>">
                                                <?php echo htmlspecialchars($property['baslik']); ?>
                                            </span>
                                        </td>
                                        <td class="col-price price-cell" data-label="Fiyat">
                                            <?php echo number_format($property['fiyat'], 0, ',', '.'); ?>₺
                                        </td>
                                        <td class="col-type" data-label="Tip">
                                            <?php echo $property['kategori']; ?>
                                        </td>
                                        <td class="col-district" data-label="İlçe">
                                            <?php echo $property['ilce']; ?>
                                        </td>
                                        <td class="col-advisor" data-label="Danışman">
                                            <span class="text-truncate" title="<?php 
                                                $danisman = $property['full_name'] ?? $property['username'] ?? 'Plaza Emlak';
                                                echo htmlspecialchars($danisman);
                                            ?>">
                                                <?php 
                                                $danisman_adi = $property['full_name'] ?? $property['username'] ?? 'Plaza Emlak';
                                                // İsmi kısalt - sadece ad ve soyadın ilk harfi
                                                $parcalar = explode(' ', $danisman_adi);
                                                if(count($parcalar) > 1) {
                                                    $kisa_isim = $parcalar[0] . ' ' . mb_substr(end($parcalar), 0, 1) . '.';
                                                } else {
                                                    $kisa_isim = $danisman_adi;
                                                }
                                                echo htmlspecialchars($kisa_isim); 
                                                ?>
                                            </span>
                                        </td>
                                        <td class="col-status" data-label="Durum">
                                            <span class="status-badge <?php echo $property['durum'] == 'aktif' ? 'status-active' : 'status-passive'; ?>">
                                                <?php echo ucfirst($property['durum']); ?>
                                            </span>
                                        </td>
                                        <td class="col-actions" data-label="İşlemler">
                                            <div class="action-buttons">
                                                <a href="edit.php?id=<?php echo $property['id']; ?>"
                                                    class="btn-icon btn-edit" title="Düzenle">✏️</a>
                                                <a href="../../pages/detail.php?id=<?php echo $property['id']; ?>"
                                                    target="_blank"
                                                    class="btn-icon btn-view" title="Görüntüle">👁️</a>
                                                <button onclick="deleteProperty(<?php echo $property['id']; ?>)"
                                                    class="btn-icon btn-delete" title="Sil">🗑️</button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" style="text-align: center; padding: 40px; font-size: 0.9rem; color: #6c757d;">
                                        Henüz ilan eklenmemiş. <a href="add-step1.php" style="color: #007bff;">İlk ilanınızı ekleyin</a>
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