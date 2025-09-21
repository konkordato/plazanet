<?php
session_start();

// Sadece admin erişebilir
if(!isset($_SESSION['user_logged_in']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

require_once '../../config/database.php';

$user_name = $_SESSION['user_fullname'];
$current_year = date('Y');
$success_msg = '';
$error_msg = '';

// Yıl seçimi
$selected_year = $_GET['year'] ?? $current_year;

// Yeni baraj ekleme
if(isset($_POST['add_threshold'])) {
    try {
        $year = $_POST['year'];
        $level = $_POST['threshold_level'];
        $min_amount = floatval(str_replace(['.', ','], ['', '.'], $_POST['min_amount']));
        $max_amount = $_POST['max_amount'] ? floatval(str_replace(['.', ','], ['', '.'], $_POST['max_amount'])) : null;
        $office_percentage = floatval($_POST['office_percentage']);
        $advisor_percentage = floatval($_POST['advisor_percentage']);
        
        // Yüzde toplamı kontrolü
        if(($office_percentage + $advisor_percentage) != 100) {
            throw new Exception("Ofis ve danışman yüzdeleri toplamı 100 olmalıdır!");
        }
        
        $stmt = $db->prepare("
            INSERT INTO commission_thresholds 
            (year, threshold_level, min_amount, max_amount, office_percentage, advisor_percentage, is_active) 
            VALUES (:year, :level, :min, :max, :office, :advisor, 1)
        ");
        
        $stmt->execute([
            ':year' => $year,
            ':level' => $level,
            ':min' => $min_amount,
            ':max' => $max_amount,
            ':office' => $office_percentage,
            ':advisor' => $advisor_percentage
        ]);
        
        $success_msg = "Prim barajı başarıyla eklendi!";
        
    } catch(Exception $e) {
        $error_msg = $e->getMessage();
    }
}

// Baraj güncelleme
if(isset($_POST['update_threshold'])) {
    try {
        $id = $_POST['threshold_id'];
        $min_amount = floatval(str_replace(['.', ','], ['', '.'], $_POST['min_amount']));
        $max_amount = $_POST['max_amount'] ? floatval(str_replace(['.', ','], ['', '.'], $_POST['max_amount'])) : null;
        $office_percentage = floatval($_POST['office_percentage']);
        $advisor_percentage = floatval($_POST['advisor_percentage']);
        
        // Yüzde toplamı kontrolü
        if(($office_percentage + $advisor_percentage) != 100) {
            throw new Exception("Ofis ve danışman yüzdeleri toplamı 100 olmalıdır!");
        }
        
        $stmt = $db->prepare("
            UPDATE commission_thresholds 
            SET min_amount = :min, 
                max_amount = :max, 
                office_percentage = :office, 
                advisor_percentage = :advisor,
                updated_at = NOW()
            WHERE id = :id
        ");
        
        $stmt->execute([
            ':min' => $min_amount,
            ':max' => $max_amount,
            ':office' => $office_percentage,
            ':advisor' => $advisor_percentage,
            ':id' => $id
        ]);
        
        $success_msg = "Prim barajı güncellendi!";
        
    } catch(Exception $e) {
        $error_msg = $e->getMessage();
    }
}

// Baraj silme
if(isset($_POST['delete_threshold'])) {
    try {
        $id = $_POST['threshold_id'];
        
        $stmt = $db->prepare("DELETE FROM commission_thresholds WHERE id = :id");
        $stmt->execute([':id' => $id]);
        
        $success_msg = "Prim barajı silindi!";
        
    } catch(Exception $e) {
        $error_msg = "Hata: " . $e->getMessage();
    }
}

// Yıl kopyalama
if(isset($_POST['copy_year'])) {
    try {
        $from_year = $_POST['from_year'];
        $to_year = $_POST['to_year'];
        
        // Hedef yılda kayıt var mı kontrol et
        $check = $db->prepare("SELECT COUNT(*) FROM commission_thresholds WHERE year = :year");
        $check->execute([':year' => $to_year]);
        
        if($check->fetchColumn() > 0) {
            throw new Exception("$to_year yılı için zaten baraj tanımları mevcut!");
        }
        
        // Kopyala
        $stmt = $db->prepare("
            INSERT INTO commission_thresholds (year, threshold_level, min_amount, max_amount, office_percentage, advisor_percentage, is_active)
            SELECT :to_year, threshold_level, min_amount, max_amount, office_percentage, advisor_percentage, is_active
            FROM commission_thresholds
            WHERE year = :from_year
        ");
        
        $stmt->execute([
            ':to_year' => $to_year,
            ':from_year' => $from_year
        ]);
        
        $success_msg = "$from_year yılı barajları $to_year yılına kopyalandı!";
        $selected_year = $to_year;
        
    } catch(Exception $e) {
        $error_msg = $e->getMessage();
    }
}

// Mevcut barajları çek
$thresholds = $db->prepare("
    SELECT * FROM commission_thresholds 
    WHERE year = :year 
    ORDER BY threshold_level
");
$thresholds->execute([':year' => $selected_year]);
$current_thresholds = $thresholds->fetchAll(PDO::FETCH_ASSOC);

// Yılları çek
$years = $db->query("
    SELECT DISTINCT year 
    FROM commission_thresholds 
    ORDER BY year DESC
")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prim Ayarları - Admin Panel</title>
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <style>
        .settings-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        
        .settings-title {
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .settings-subtitle {
            opacity: 0.9;
        }
        
        .year-selector {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .thresholds-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .threshold-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 15px;
            border-left: 4px solid #3498db;
        }
        
        .threshold-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .threshold-level {
            font-size: 18px;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .threshold-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .detail-item {
            display: flex;
            flex-direction: column;
        }
        
        .detail-label {
            font-size: 12px;
            color: #7f8c8d;
            margin-bottom: 5px;
        }
        
        .detail-value {
            font-size: 16px;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .percentage-display {
            display: flex;
            gap: 20px;
        }
        
        .percentage-item {
            padding: 10px 20px;
            border-radius: 5px;
            background: white;
        }
        
        .office-percentage {
            border: 2px solid #e74c3c;
            color: #e74c3c;
        }
        
        .advisor-percentage {
            border: 2px solid #27ae60;
            color: #27ae60;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
        }
        
        .btn-edit {
            background: #3498db;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .btn-delete {
            background: #e74c3c;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .add-form {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .form-group input, .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .btn-submit {
            background: #27ae60;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        
        .btn-submit:hover {
            background: #229954;
        }
        
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
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
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }
        
        .modal-content {
            position: relative;
            background: white;
            width: 90%;
            max-width: 500px;
            margin: 100px auto;
            padding: 30px;
            border-radius: 10px;
        }
        
        .modal-close {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 24px;
            cursor: pointer;
            color: #999;
        }
        
        .copy-year-form {
            margin-top: 20px;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        
        .empty-icon {
            font-size: 72px;
            margin-bottom: 20px;
        }
        
        .empty-title {
            font-size: 24px;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .empty-text {
            color: #7f8c8d;
        }
        
        .btn-copy {
            background: #9b59b6;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        
        .btn-copy:hover {
            background: #8e44ad;
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
                <li><a href="closing.php">💰 Portföy Kapatma</a></li>
                <li><a href="reports.php">📊 Satış Raporları</a></li>
                <li><a href="commission-settings.php" class="active">⚙️ Prim Ayarları</a></li>
                <li><a href="closed-properties.php">🔒 Kapatılan İlanlar</a></li>
            </ul>
        </nav>

        <!-- Main Content -->
        <div class="main-content">
            <div class="top-navbar">
                <div class="navbar-left">
                    <h3>Prim Ayarları Yönetimi</h3>
                </div>
                <div class="navbar-right">
                    <span>Admin: <?php echo htmlspecialchars($user_name); ?></span>
                    <a href="../logout.php" class="btn-logout">Çıkış</a>
                </div>
            </div>

            <div class="content">
                <!-- Başlık -->
                <div class="settings-header">
                    <h1 class="settings-title">⚙️ Prim Barajları Yönetimi</h1>
                    <p class="settings-subtitle">
                        Yıllık prim barajlarını ve danışman paylaşım oranlarını buradan yönetebilirsiniz
                    </p>
                </div>

                <?php if($success_msg): ?>
                    <div class="alert alert-success"><?php echo $success_msg; ?></div>
                <?php endif; ?>
                
                <?php if($error_msg): ?>
                    <div class="alert alert-error"><?php echo $error_msg; ?></div>
                <?php endif; ?>

                <!-- Yıl Seçimi -->
                <div class="year-selector">
                    <div>
                        <form method="GET" style="display: inline-block;">
                            <select name="year" onchange="this.form.submit()" style="padding: 10px; font-size: 16px;">
                                <?php for($y = 2024; $y <= date('Y') + 2; $y++): ?>
                                    <option value="<?php echo $y; ?>" <?php echo $selected_year == $y ? 'selected' : ''; ?>>
                                        <?php echo $y; ?> Yılı
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </form>
                    </div>
                    
                    <div>
                        <button class="btn-copy" onclick="showCopyModal()">
                            📋 Başka Yıldan Kopyala
                        </button>
                    </div>
                </div>

                <!-- Yeni Baraj Ekleme Formu -->
                <div class="add-form">
                    <h3>➕ Yeni Prim Barajı Ekle</h3>
                    <form method="POST">
                        <input type="hidden" name="year" value="<?php echo $selected_year; ?>">
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Baraj Seviyesi *</label>
                                <input type="number" name="threshold_level" 
                                       value="<?php echo count($current_thresholds) + 1; ?>" 
                                       min="1" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Minimum Ciro (TL) *</label>
                                <input type="text" name="min_amount" placeholder="55.000" required
                                       onkeyup="formatCurrency(this)">
                            </div>
                            
                            <div class="form-group">
                                <label>Maximum Ciro (TL)</label>
                                <input type="text" name="max_amount" placeholder="114.999 (Boş = Sınırsız)"
                                       onkeyup="formatCurrency(this)">
                            </div>
                            
                            <div class="form-group">
                                <label>Ofis Payı (%) *</label>
                                <input type="number" name="office_percentage" placeholder="45" 
                                       min="0" max="100" step="0.1" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Danışman Payı (%) *</label>
                                <input type="number" name="advisor_percentage" placeholder="55" 
                                       min="0" max="100" step="0.1" required>
                            </div>
                        </div>
                        
                        <button type="submit" name="add_threshold" class="btn-submit">
                            ✅ Baraj Ekle
                        </button>
                    </form>
                </div>

                <!-- Mevcut Barajlar -->
                <div class="thresholds-container">
                    <h3>📊 <?php echo $selected_year; ?> Yılı Prim Barajları</h3>
                    
                    <?php if(empty($current_thresholds)): ?>
                        <div class="empty-state">
                            <div class="empty-icon">📭</div>
                            <div class="empty-title">Baraj Tanımlanmamış</div>
                            <div class="empty-text">
                                <?php echo $selected_year; ?> yılı için henüz prim barajı tanımlanmamış.
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach($current_thresholds as $threshold): ?>
                        <div class="threshold-card">
                            <div class="threshold-header">
                                <div class="threshold-level">
                                    <?php echo $threshold['threshold_level']; ?>. Baraj
                                </div>
                                <div class="action-buttons">
                                    <button class="btn-edit" onclick="editThreshold(<?php echo htmlspecialchars(json_encode($threshold)); ?>)">
                                        ✏️ Düzenle
                                    </button>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="threshold_id" value="<?php echo $threshold['id']; ?>">
                                        <button type="submit" name="delete_threshold" class="btn-delete"
                                                onclick="return confirm('Bu barajı silmek istediğinize emin misiniz?')">
                                            🗑️ Sil
                                        </button>
                                    </form>
                                </div>
                            </div>
                            
                            <div class="threshold-details">
                                <div class="detail-item">
                                    <span class="detail-label">Ciro Aralığı</span>
                                    <span class="detail-value">
                                        <?php 
                                        echo number_format($threshold['min_amount'], 0, ',', '.');
                                        if($threshold['max_amount']) {
                                            echo ' - ' . number_format($threshold['max_amount'], 0, ',', '.');
                                        } else {
                                            echo ' ve üzeri';
                                        }
                                        ?> TL
                                    </span>
                                </div>
                                
                                <div class="detail-item">
                                    <span class="detail-label">Paylaşım Oranları</span>
                                    <div class="percentage-display">
                                        <div class="percentage-item office-percentage">
                                            Ofis: %<?php echo $threshold['office_percentage']; ?>
                                        </div>
                                        <div class="percentage-item advisor-percentage">
                                            Danışman: %<?php echo $threshold['advisor_percentage']; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Düzenleme Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="modal-close" onclick="closeEditModal()">&times;</span>
            <h3>Prim Barajını Düzenle</h3>
            
            <form method="POST">
                <input type="hidden" name="threshold_id" id="edit_threshold_id">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Minimum Ciro (TL)</label>
                        <input type="text" name="min_amount" id="edit_min_amount" required
                               onkeyup="formatCurrency(this)">
                    </div>
                    
                    <div class="form-group">
                        <label>Maximum Ciro (TL)</label>
                        <input type="text" name="max_amount" id="edit_max_amount"
                               onkeyup="formatCurrency(this)">
                    </div>
                    
                    <div class="form-group">
                        <label>Ofis Payı (%)</label>
                        <input type="number" name="office_percentage" id="edit_office_percentage" 
                               min="0" max="100" step="0.1" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Danışman Payı (%)</label>
                        <input type="number" name="advisor_percentage" id="edit_advisor_percentage" 
                               min="0" max="100" step="0.1" required>
                    </div>
                </div>
                
                <button type="submit" name="update_threshold" class="btn-submit">
                    ✅ Güncelle
                </button>
            </form>
        </div>
    </div>

    <!-- Kopyalama Modal -->
    <div id="copyModal" class="modal">
        <div class="modal-content">
            <span class="modal-close" onclick="closeCopyModal()">&times;</span>
            <h3>Başka Yıldan Kopyala</h3>
            
            <form method="POST" class="copy-year-form">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Kaynak Yıl</label>
                        <select name="from_year" required>
                            <option value="">Seçiniz</option>
                            <?php foreach($years as $year): ?>
                                <option value="<?php echo $year; ?>"><?php echo $year; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Hedef Yıl</label>
                        <select name="to_year" required>
                            <option value="">Seçiniz</option>
                            <?php for($y = 2024; $y <= date('Y') + 2; $y++): ?>
                                <option value="<?php echo $y; ?>"><?php echo $y; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                
                <button type="submit" name="copy_year" class="btn-submit">
                    📋 Kopyala
                </button>
            </form>
        </div>
    </div>

    <script>
    function formatCurrency(input) {
        let value = input.value.replace(/[^0-9,]/g, '');
        value = value.replace(',', '.');
        
        if(value) {
            let parts = value.split('.');
            parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            input.value = parts.join(',');
        }
    }
    
    function editThreshold(data) {
        document.getElementById('editModal').style.display = 'block';
        document.getElementById('edit_threshold_id').value = data.id;
        document.getElementById('edit_min_amount').value = formatNumber(data.min_amount);
        document.getElementById('edit_max_amount').value = data.max_amount ? formatNumber(data.max_amount) : '';
        document.getElementById('edit_office_percentage').value = data.office_percentage;
        document.getElementById('edit_advisor_percentage').value = data.advisor_percentage;
    }
    
    function formatNumber(num) {
        return new Intl.NumberFormat('tr-TR', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(num);
    }
    
    function closeEditModal() {
        document.getElementById('editModal').style.display = 'none';
    }
    
    function showCopyModal() {
        document.getElementById('copyModal').style.display = 'block';
    }
    
    function closeCopyModal() {
        document.getElementById('copyModal').style.display = 'none';
    }
    
    // Modal dışına tıklanınca kapat
    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = 'none';
        }
    }
    </script>
</body>
</html>