<?php
session_start();
if(!isset($_SESSION['admin_logged_in']) && !isset($_SESSION['user_logged_in'])) {
    header("Location: /plazanet/index.php");
    exit();
}

// İlan bilgilerini al
$property_id = $_SESSION['new_property_id'] ?? null;
$property_no = $_SESSION['new_property_no'] ?? null;

// Eğer bilgiler yoksa direkt başarı mesajı göster
if(!$property_id && !$property_no) {
    $property_id = $_SESSION['last_property_id'] ?? 'Yeni';
    $property_no = $_SESSION['last_property_no'] ?? 'PLZ-' . date('Y') . '-' . rand(1000,9999);
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tebrikler - İlan Eklendi</title>
    <link rel="stylesheet" href="../../assets/css/admin-form.css">
    <style>
        body {
            background: #f4f4f4;
        }
        .success-container {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 10px;
            margin: 50px auto;
            max-width: 600px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .success-icon {
            width: 100px;
            height: 100px;
            background: #27ae60;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            animation: scaleIn 0.5s ease;
        }
        @keyframes scaleIn {
            from { transform: scale(0); }
            to { transform: scale(1); }
        }
        .success-icon span {
            color: white;
            font-size: 50px;
        }
        .success-title {
            font-size: 32px;
            color: #27ae60;
            margin-bottom: 20px;
        }
        .success-message {
            font-size: 18px;
            color: #666;
            margin-bottom: 30px;
        }
        .ilan-no-box {
            background: #f8f9fa;
            border: 2px dashed #3498db;
            padding: 20px;
            border-radius: 10px;
            display: inline-block;
            margin-bottom: 40px;
        }
        .ilan-no-label {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
        }
        .ilan-no {
            font-size: 28px;
            font-weight: bold;
            color: #3498db;
        }
        .action-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .action-btn {
            padding: 15px 30px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-size: 16px;
            transition: all 0.3s;
        }
        .btn-primary {
            background: #3498db;
            color: white;
        }
        .btn-success {
            background: #27ae60;
            color: white;
        }
        .btn-secondary {
            background: #95a5a6;
            color: white;
        }
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>
    <div class="success-container">
        <div class="success-icon">
            <span>✓</span>
        </div>
        
        <h1 class="success-title">Tebrikler!</h1>
        <p class="success-message">İlanınız başarıyla eklendi ve yayında.</p>
        
        <?php if($property_no): ?>
        <div class="ilan-no-box">
            <div class="ilan-no-label">İlan Numaranız</div>
            <div class="ilan-no"><?php echo htmlspecialchars($property_no); ?></div>
        </div>
        <?php endif; ?>
        
        <div class="action-buttons">
            <?php if($property_id && is_numeric($property_id)): ?>
            <a href="/plazanet/detail.php?id=<?php echo $property_id; ?>" target="_blank" class="action-btn btn-primary">
                İlanı Görüntüle
            </a>
            <?php endif; ?>
            
            <a href="/plazanet/admin/properties/add-step1.php" class="action-btn btn-success">
                Yeni İlan Ekle
            </a>
            
            <a href="/plazanet/admin/properties/list.php" class="action-btn btn-secondary">
                İlan Listesi
            </a>
        </div>
    </div>
    
    <?php
    // Session temizle
    unset($_SESSION['new_property_id']);
    unset($_SESSION['new_property_no']);
    unset($_SESSION['success']);
    ?>
</body>
</html>