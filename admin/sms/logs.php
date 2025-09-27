<?php
// C:\xampp\htdocs\plazanet\admin\sms\logs.php
// SMS Log Görüntüleme Sayfası

// Session kontrolü - eğer başlatılmamışsa başlat
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if(!isset($_SESSION['admin_logged_in']) && !isset($_SESSION['user_logged_in'])) {
    header("Location: ../index.php");
    exit();
}

require_once '../../config/database.php';

$current_user_id = $_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? 0;
$current_user_role = $_SESSION['user_role'] ?? 'user';

// Filtreleme parametreleri
$filter_status = $_GET['status'] ?? '';
$filter_type = $_GET['type'] ?? '';
$filter_date = $_GET['date'] ?? '';
$search_phone = $_GET['phone'] ?? '';

// Sayfalama
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// SQL sorgusu oluştur
$sql = "SELECT sl.*, u.username, u.full_name 
        FROM sms_logs sl
        LEFT JOIN users u ON sl.sender_user_id = u.id
        WHERE 1=1";

$params = [];

// Admin değilse sadece kendi gönderdiği SMS'leri görsün
if($current_user_role != 'admin') {
    $sql .= " AND sl.sender_user_id = :user_id";
    $params[':user_id'] = $current_user_id;
}

// Filtreler
if($filter_status) {
    $sql .= " AND sl.status = :status";
    $params[':status'] = $filter_status;
}

if($filter_type) {
    $sql .= " AND sl.sms_type = :type";
    $params[':type'] = $filter_type;
}

if($filter_date) {
    $sql .= " AND DATE(sl.created_at) = :date";
    $params[':date'] = $filter_date;
}

if($search_phone) {
    $sql .= " AND sl.phone_number LIKE :phone";
    $params[':phone'] = '%' . $search_phone . '%';
}

// Toplam kayıt sayısı
$count_sql = str_replace("SELECT sl.*, u.username, u.full_name", "SELECT COUNT(*) as total", $sql);
$count_stmt = $db->prepare($count_sql);
$count_stmt->execute($params);
$total_records = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_records / $per_page);

// Kayıtları getir
$sql .= " ORDER BY sl.created_at DESC LIMIT :offset, :limit";
$stmt = $db->prepare($sql);
foreach($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
$stmt->execute();
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// İstatistikler
$stats = [];
$stat_sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
    SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as today
    FROM sms_logs";

if($current_user_role != 'admin') {
    $stat_sql .= " WHERE sender_user_id = " . $current_user_id;
}

$stat_result = $db->query($stat_sql);
$stats = $stat_result->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMS Logları - Plazanet Emlak</title>
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <style>
        /* Ana wrapper düzeltmesi */
        .admin-wrapper {
            display: flex;
            min-height: 100vh;
        }
        
        /* Main content alanı */
        .main-content {
            flex: 1;
            margin-left: 250px;
            background: #f5f5f5;
            min-height: 100vh;
        }
        
        .content-header {
            background: white;
            padding: 20px 30px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        
        .content-header h1 {
            margin: 0;
            color: #2c3e50;
            font-size: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .content-body {
            padding: 0 30px 30px;
        }
        
        /* İstatistik Kartları */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-box {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-left: 4px solid #3498db;
        }
        
        .stat-box .value {
            font-size: 32px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .stat-box .label {
            color: #7f8c8d;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .stat-box.success { border-left-color: #27ae60; }
        .stat-box.danger { border-left-color: #e74c3c; }
        .stat-box.info { border-left-color: #3498db; }
        .stat-box.warning { border-left-color: #f39c12; }
        
        /* Filtre Bölümü */
        .filter-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .filter-form {
            display: flex;
            gap: 15px;
            align-items: end;
            flex-wrap: wrap;
        }
        
        .filter-group {
            flex: 1;
            min-width: 200px;
        }
        
        .filter-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 13px;
            color: #495057;
            font-weight: 500;
        }
        
        .filter-group input,
        .filter-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ced4da;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }
        
        .btn-filter {
            padding: 10px 30px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: background 0.3s;
        }
        
        .btn-filter:hover {
            background: #2980b9;
        }
        
        .btn-reset {
            padding: 10px 30px;
            background: #95a5a6;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: background 0.3s;
        }
        
        .btn-reset:hover {
            background: #7f8c8d;
        }
        
        /* Tablo */
        .table-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        thead {
            background: #f8f9fa;
        }
        
        th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
            color: #495057;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #dee2e6;
        }
        
        td {
            padding: 15px;
            border-bottom: 1px solid #f1f3f4;
            font-size: 14px;
            color: #636e72;
        }
        
        tbody tr:hover {
            background: #f8f9fa;
        }
        
        /* Durum Badge'leri */
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            display: inline-block;
        }
        
        .status-sent { 
            background: #d4edda; 
            color: #155724; 
        }
        
        .status-failed { 
            background: #f8d7da; 
            color: #721c24; 
        }
        
        .status-pending { 
            background: #fff3cd; 
            color: #856404; 
        }
        
        /* Tip Badge'leri */
        .type-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            background: #e3f2fd;
            color: #1976d2;
            font-weight: 500;
            display: inline-block;
        }
        
        /* Mesaj önizleme */
        .message-preview {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            cursor: pointer;
        }
        
        .message-preview:hover {
            color: #3498db;
        }
        
        /* Detay butonu */
        .btn-detail {
            padding: 5px 15px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            transition: background 0.3s;
        }
        
        .btn-detail:hover {
            background: #2980b9;
        }
        
        /* Boş durum */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #95a5a6;
        }
        
        .empty-state-icon {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        .empty-state h3 {
            color: #7f8c8d;
            margin-bottom: 10px;
        }
        
        /* Sayfalama */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 5px;
            margin-top: 30px;
        }
        
        .pagination a {
            padding: 8px 15px;
            background: white;
            border: 1px solid #dee2e6;
            text-decoration: none;
            color: #495057;
            border-radius: 5px;
            transition: all 0.3s;
        }
        
        .pagination a:hover {
            background: #f8f9fa;
            border-color: #3498db;
        }
        
        .pagination a.active {
            background: #3498db;
            color: white;
            border-color: #3498db;
        }
        
        .pagination a:first-child,
        .pagination a:last-child {
            font-weight: bold;
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
                    <a href="../crm/index.php">
                        <span class="icon">📊</span>
                        <span>CRM Sistemi</span>
                    </a>
                </li>
                <li>
                    <a href="send.php">
                        <span class="icon">📤</span>
                        <span>SMS Gönder</span>
                    </a>
                </li>
                <li>
                    <a href="logs.php" class="active">
                        <span class="icon">📋</span>
                        <span>SMS Logları</span>
                    </a>
                </li>
                <?php if($current_user_role == 'admin'): ?>
                <li>
                    <a href="settings.php">
                        <span class="icon">⚙️</span>
                        <span>SMS Ayarları</span>
                    </a>
                </li>
                <?php endif; ?>
                <li>
                    <a href="../logout.php">
                        <span class="icon">🚪</span>
                        <span>Çıkış</span>
                    </a>
                </li>
            </ul>
        </nav>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="content-header">
                <h1>📋 SMS Logları</h1>
            </div>
            
            <div class="content-body">
                <!-- İstatistikler -->
                <div class="stats-row">
                    <div class="stat-box">
                        <div class="value"><?php echo $stats['total'] ?? 0; ?></div>
                        <div class="label">Toplam SMS</div>
                    </div>
                    <div class="stat-box success">
                        <div class="value"><?php echo $stats['sent'] ?? 0; ?></div>
                        <div class="label">Başarılı</div>
                    </div>
                    <div class="stat-box danger">
                        <div class="value"><?php echo $stats['failed'] ?? 0; ?></div>
                        <div class="label">Başarısız</div>
                    </div>
                    <div class="stat-box info">
                        <div class="value"><?php echo $stats['today'] ?? 0; ?></div>
                        <div class="label">Bugün</div>
                    </div>
                </div>
                
                <!-- Filtreler -->
                <div class="filter-card">
                    <form method="GET" class="filter-form">
                        <div class="filter-group">
                            <label>Telefon No</label>
                            <input type="text" name="phone" value="<?php echo htmlspecialchars($search_phone); ?>" placeholder="05XX...">
                        </div>
                        
                        <div class="filter-group">
                            <label>Durum</label>
                            <select name="status">
                                <option value="">Tümü</option>
                                <option value="sent" <?php echo $filter_status == 'sent' ? 'selected' : ''; ?>>Gönderildi</option>
                                <option value="failed" <?php echo $filter_status == 'failed' ? 'selected' : ''; ?>>Başarısız</option>
                                <option value="pending" <?php echo $filter_status == 'pending' ? 'selected' : ''; ?>>Bekliyor</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label>SMS Tipi</label>
                            <select name="type">
                                <option value="">Tümü</option>
                                <option value="yeni_ilan" <?php echo $filter_type == 'yeni_ilan' ? 'selected' : ''; ?>>Yeni İlan</option>
                                <option value="musteri_bilgi" <?php echo $filter_type == 'musteri_bilgi' ? 'selected' : ''; ?>>Müşteri Bilgi</option>
                                <option value="manuel" <?php echo $filter_type == 'manuel' ? 'selected' : ''; ?>>Manuel</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label>Tarih</label>
                            <input type="date" name="date" value="<?php echo $filter_date; ?>">
                        </div>
                        
                        <button type="submit" class="btn-filter">🔍 Filtrele</button>
                        <a href="logs.php" class="btn-reset">↺ Sıfırla</a>
                    </form>
                </div>
                
                <!-- SMS Logları Tablosu -->
                <div class="table-card">
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Telefon</th>
                                    <th>Mesaj</th>
                                    <th>Tip</th>
                                    <th>Durum</th>
                                    <th>Gönderen</th>
                                    <th>Tarih</th>
                                    <th>İşlem</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($logs)): ?>
                                <tr>
                                    <td colspan="8">
                                        <div class="empty-state">
                                            <div class="empty-state-icon">📭</div>
                                            <h3>Henüz SMS kaydı bulunmuyor</h3>
                                            <p>Gönderilen SMS'ler burada listelenecek.</p>
                                        </div>
                                    </td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach($logs as $log): ?>
                                    <tr>
                                        <td>#<?php echo $log['id']; ?></td>
                                        <td>
                                            <?php 
                                            $phone = $log['phone_number'];
                                            // Telefon numarasını maskele
                                            if(strlen($phone) > 7) {
                                                echo substr($phone, 0, 5) . '***' . substr($phone, -2);
                                            } else {
                                                echo $phone;
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <div class="message-preview" title="<?php echo htmlspecialchars($log['message_text']); ?>">
                                                <?php echo htmlspecialchars(substr($log['message_text'], 0, 50)) . '...'; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="type-badge">
                                                <?php 
                                                $types = [
                                                    'yeni_ilan' => 'Yeni İlan',
                                                    'musteri_bilgi' => 'Müşteri',
                                                    'manuel' => 'Manuel',
                                                    'hatirlatma' => 'Hatırlatma'
                                                ];
                                                echo $types[$log['sms_type']] ?? $log['sms_type'];
                                                ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo $log['status']; ?>">
                                                <?php 
                                                $statuses = [
                                                    'sent' => '✓ Gönderildi',
                                                    'failed' => '✗ Başarısız',
                                                    'pending' => '⏳ Bekliyor'
                                                ];
                                                echo $statuses[$log['status']] ?? $log['status'];
                                                ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($log['full_name'] ?? $log['username'] ?? 'Sistem'); ?>
                                        </td>
                                        <td>
                                            <?php echo date('d.m.Y H:i', strtotime($log['created_at'])); ?>
                                        </td>
                                        <td>
                                            <button onclick="viewDetails(<?php echo $log['id']; ?>)" class="btn-detail">
                                                Detay
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Sayfalama -->
                <?php if($total_pages > 1): ?>
                <div class="pagination">
                    <?php if($page > 1): ?>
                        <a href="?page=<?php echo $page-1; ?>&<?php echo http_build_query(array_diff_key($_GET, ['page' => ''])); ?>">←</a>
                    <?php endif; ?>
                    
                    <?php 
                    $start = max(1, $page - 2);
                    $end = min($total_pages, $page + 2);
                    
                    if($start > 1): ?>
                        <a href="?page=1&<?php echo http_build_query(array_diff_key($_GET, ['page' => ''])); ?>">1</a>
                        <?php if($start > 2): ?>
                            <span style="padding: 8px;">...</span>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <?php for($i = $start; $i <= $end; $i++): ?>
                        <?php if($i == $page): ?>
                            <a href="#" class="active"><?php echo $i; ?></a>
                        <?php else: ?>
                            <a href="?page=<?php echo $i; ?>&<?php echo http_build_query(array_diff_key($_GET, ['page' => ''])); ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if($end < $total_pages): ?>
                        <?php if($end < $total_pages - 1): ?>
                            <span style="padding: 8px;">...</span>
                        <?php endif; ?>
                        <a href="?page=<?php echo $total_pages; ?>&<?php echo http_build_query(array_diff_key($_GET, ['page' => ''])); ?>"><?php echo $total_pages; ?></a>
                    <?php endif; ?>
                    
                    <?php if($page < $total_pages): ?>
                        <a href="?page=<?php echo $page+1; ?>&<?php echo http_build_query(array_diff_key($_GET, ['page' => ''])); ?>">→</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
    function viewDetails(id) {
        // İleride modal açılabilir
        alert('SMS ID: ' + id + ' detayları gösterilecek.');
    }
    </script>
</body>
</html>