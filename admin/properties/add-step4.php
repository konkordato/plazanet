<?php
session_start();
if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../index.php");
    exit();
}

// İlan bilgilerini al
$property_id = $_SESSION['new_property_id'] ?? null;
$property_no = $_SESSION['new_property_no'] ?? null;
$success_msg = $_SESSION['success'] ?? null;

// Session'ı temizle
unset($_SESSION['new_property_id']);
unset($_SESSION['new_property_no']);
unset($_SESSION['success']);

if(!$property_id) {
    header("Location: add-step1.php");
    exit();
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
        .success-container {
            text-align: center;
            padding: 60px 20px;
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
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <div class="logo">
                <img src="../../assets/images/plaza-logo.png" alt="Plaza">
                <span>İlan Eklendi</span>
            </div>
        </div>
    </div>

    <!-- Adımlar -->
    <div class="steps">
        <div class="container">
            <div class="steps-wrapper">
                <div class="step completed">
                    <div class="step-circle">✓</div>
                    <div class="step-title">Kategori Seçimi</div>
                </div>
                <div class="step-line active"></div>
                <div class="step completed">
                    <div class="step-circle">✓</div>
                    <div class="step-title">İlan Detayları</div>
                </div>
                <div class="step-line active"></div>
                <div class="step completed">
                    <div class="step-circle">✓</div>
                    <div class="step-title">Önizleme</div>
                </div>
                <div class="step-line active"></div>
                <div class="step active">
                    <div class="step-circle">4</div>
                    <div class="step-title">Tebrikler</div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="content">
            <div class="success-container">
                <div class="success-icon">
                    <span>✓</span>
                </div>
                
                <h1 class="success-title">Tebrikler!</h1>
                <p class="success-message">İlanınız başarıyla yayınlandı.</p>
                
                <div class="ilan-no-box">
                    <div class="ilan-no-label">İlan Numaranız</div>
                    <div class="ilan-no"><?php echo $property_no; ?></div>
                </div>
                
                <div class="action-buttons">
                    <a href="../../pages/detail.php?id=<?php echo $property_id; ?>" target="_blank" class="action-btn btn-primary">
                        İlanı Görüntüle
                    </a>
                    
                    <a href="add-step1.php" class="action-btn btn-success">
                        Yeni İlan Ekle
                    </a>
                    
                    <a href="../dashboard.php" class="action-btn btn-secondary">
                        Dashboard'a Dön
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>