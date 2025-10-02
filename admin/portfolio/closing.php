<?php

session_start();



// Giriş kontrolü

if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {

    header("Location: ../index.php");

    exit();
}



require_once '../../config/database.php';



$user_id = $_SESSION['user_id'];

$user_role = $_SESSION['user_role'];

$user_name = $_SESSION['user_fullname'];



// Aktif ilanları çek (kullanıcı kendi ilanlarını veya admin hepsini görsün)

$properties_sql = "SELECT id, baslik, fiyat, kategori, ilce FROM properties WHERE durum = 'aktif'";

if ($user_role != 'admin') {

    $properties_sql .= " AND user_id = " . $user_id;
}

$properties_sql .= " ORDER BY baslik ASC";

$properties = $db->query($properties_sql)->fetchAll(PDO::FETCH_ASSOC);



// Danışmanları çek (aktif kullanıcılar)

$advisors_stmt = $db->query("SELECT id, full_name, username FROM users WHERE status = 'active' ORDER BY full_name");

$advisors = $advisors_stmt->fetchAll(PDO::FETCH_ASSOC);



// Form gönderilmişse işle

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_closing'])) {

    try {

        $db->beginTransaction();



        // Form verilerini al

        $closing_date = $_POST['closing_date'];

        $property_id = $_POST['property_id'] ?: null;

        $property_title = trim($_POST['property_title']);

        $closing_type = $_POST['closing_type'];

        $total_amount = floatval(str_replace(['.', ','], ['', '.'], $_POST['total_amount']));



        // Eğer mevcut bir ilan seçildiyse, bilgilerini al

        if ($property_id) {

            $prop_stmt = $db->prepare("SELECT baslik, kategori FROM properties WHERE id = :id");

            $prop_stmt->execute([':id' => $property_id]);

            $prop = $prop_stmt->fetch(PDO::FETCH_ASSOC);

            if ($prop) {

                $property_title = $prop['baslik'];
            }
        }



        // Danışman bilgileri

        $customer_advisor_id = $_POST['customer_advisor_id'] ?: null;

        $portfolio_advisor_id = $_POST['portfolio_advisor_id'] ?: null;

        $referral_advisor_id = $_POST['referral_advisor_id'] ?: null;



        // Paylaşım hesaplama

        $office_share = floatval(str_replace(['.', ','], ['', '.'], $_POST['office_share']));

        $office_percentage = ($office_share / $total_amount) * 100;



        $customer_advisor_share = 0;

        $customer_advisor_percentage = 0;

        if ($customer_advisor_id) {

            $customer_advisor_share = floatval(str_replace(['.', ','], ['', '.'], $_POST['customer_advisor_share']));

            $customer_advisor_percentage = ($customer_advisor_share / $total_amount) * 100;
        }



        $portfolio_advisor_share = 0;

        $portfolio_advisor_percentage = 0;

        if ($portfolio_advisor_id) {

            $portfolio_advisor_share = floatval(str_replace(['.', ','], ['', '.'], $_POST['portfolio_advisor_share']));

            $portfolio_advisor_percentage = ($portfolio_advisor_share / $total_amount) * 100;
        }



        $referral_advisor_share = 0;

        $referral_advisor_percentage = 0;

        if ($referral_advisor_id) {

            $referral_advisor_share = floatval(str_replace(['.', ','], ['', '.'], $_POST['referral_advisor_share']));

            $referral_advisor_percentage = ($referral_advisor_share / $total_amount) * 100;
        }



        $notes = trim($_POST['notes']);



        // Portföy kapatmayı kaydet

        $sql = "INSERT INTO portfolio_closings (

            closing_date, property_id, property_title, closing_type, total_amount,

            office_share, office_percentage,

            customer_advisor_id, customer_advisor_share, customer_advisor_percentage,

            portfolio_advisor_id, portfolio_advisor_share, portfolio_advisor_percentage,

            referral_advisor_id, referral_advisor_share, referral_advisor_percentage,

            notes, created_by, property_status_changed

        ) VALUES (

            :closing_date, :property_id, :property_title, :closing_type, :total_amount,

            :office_share, :office_percentage,

            :customer_advisor_id, :customer_advisor_share, :customer_advisor_percentage,

            :portfolio_advisor_id, :portfolio_advisor_share, :portfolio_advisor_percentage,

            :referral_advisor_id, :referral_advisor_share, :referral_advisor_percentage,

            :notes, :created_by, :property_status_changed

        )";



        $property_status_changed = false;

        if ($property_id && isset($_POST['update_property_status'])) {

            $property_status_changed = true;
        }



        $stmt = $db->prepare($sql);

        $stmt->execute([

            ':closing_date' => $closing_date,

            ':property_id' => $property_id,

            ':property_title' => $property_title,

            ':closing_type' => $closing_type,

            ':total_amount' => $total_amount,

            ':office_share' => $office_share,

            ':office_percentage' => $office_percentage,

            ':customer_advisor_id' => $customer_advisor_id,

            ':customer_advisor_share' => $customer_advisor_share,

            ':customer_advisor_percentage' => $customer_advisor_percentage,

            ':portfolio_advisor_id' => $portfolio_advisor_id,

            ':portfolio_advisor_share' => $portfolio_advisor_share,

            ':portfolio_advisor_percentage' => $portfolio_advisor_percentage,

            ':referral_advisor_id' => $referral_advisor_id,

            ':referral_advisor_share' => $referral_advisor_share,

            ':referral_advisor_percentage' => $referral_advisor_percentage,

            ':notes' => $notes,

            ':created_by' => $user_id,

            ':property_status_changed' => $property_status_changed

        ]);



        $closing_id = $db->lastInsertId();



        // İlan durumunu güncelle (eğer seçildiyse)

        if ($property_id && $property_status_changed) {

            $new_status = $closing_type == 'kiralik' ? 'kiralik_kapandi' : 'satilik_kapandi';

            $update_property = $db->prepare("

                UPDATE properties 

                SET durum = :durum, 

                    closed_by = :closed_by, 

                    closed_at = NOW(),

                    closing_id = :closing_id

                WHERE id = :id

            ");

            $update_property->execute([

                ':durum' => $new_status,

                ':closed_by' => $user_id,

                ':closing_id' => $closing_id,

                ':id' => $property_id

            ]);
        }



        $db->commit();

        $success_msg = "Portföy kapatma başarıyla kaydedildi!";
    } catch (Exception $e) {

        $db->rollBack();

        $error_msg = "Hata: " . $e->getMessage();
    }
}



// Son kapatmaları listele (kullanıcı kendisinin, admin hepsini görsün)

$recent_closings_sql = "

    SELECT pc.*, 

           u1.full_name as customer_advisor_name,

           u2.full_name as portfolio_advisor_name,

           u3.full_name as referral_advisor_name,

           u4.full_name as created_by_name,

           p.durum as property_status

    FROM portfolio_closings pc

    LEFT JOIN users u1 ON pc.customer_advisor_id = u1.id

    LEFT JOIN users u2 ON pc.portfolio_advisor_id = u2.id

    LEFT JOIN users u3 ON pc.referral_advisor_id = u3.id

    LEFT JOIN users u4 ON pc.created_by = u4.id

    LEFT JOIN properties p ON pc.property_id = p.id

";



if ($user_role != 'admin') {

    $recent_closings_sql .= " WHERE pc.created_by = " . $user_id;
}



$recent_closings_sql .= " ORDER BY pc.created_at DESC LIMIT 10";

$recent_closings = $db->query($recent_closings_sql)->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>

<html lang="tr">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Portföy Kapatma - Plazanet</title>

    <link rel="stylesheet" href="../../assets/css/admin.css">

    <style>
        .closing-form {

            background: white;

            padding: 30px;

            border-radius: 10px;

            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);

            margin-bottom: 30px;

        }

        .form-grid {

            display: grid;

            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));

            gap: 20px;

        }

        .form-group label {

            display: block;

            margin-bottom: 5px;

            font-weight: 600;

            color: #333;

        }

        .form-group input,
        .form-group select,
        .form-group textarea {

            width: 100%;

            padding: 10px;

            border: 1px solid #ddd;

            border-radius: 5px;

            font-size: 14px;

        }

        .advisor-section {

            background: #f8f9fa;

            padding: 20px;

            border-radius: 8px;

            margin: 20px 0;

        }

        .total-display {

            background: linear-gradient(135deg, #667eea, #764ba2);

            color: white;

            padding: 20px;

            border-radius: 10px;

            text-align: center;

            font-size: 24px;

            font-weight: bold;

            margin-bottom: 20px;

        }

        .btn-submit {

            background: #27ae60;

            color: white;

            padding: 12px 40px;

            border: none;

            border-radius: 5px;

            font-size: 16px;

            cursor: pointer;

            display: block;

            margin: 20px auto 0;

        }

        .recent-closings {

            background: white;

            padding: 20px;

            border-radius: 10px;

            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);

        }

        .closings-table {

            width: 100%;

            border-collapse: collapse;

        }

        .closings-table th {

            background: #f8f9fa;

            padding: 12px;

            text-align: left;

            font-weight: 600;

            border-bottom: 2px solid #dee2e6;

        }

        .closings-table td {

            padding: 10px 12px;

            border-bottom: 1px solid #e9ecef;

        }

        .amount-cell {

            font-weight: bold;

            color: #27ae60;

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

        .property-selector {

            background: #e8f4f8;

            padding: 15px;

            border-radius: 8px;

            margin-bottom: 20px;

        }

        .checkbox-group {

            margin-top: 10px;

        }

        .checkbox-group input[type="checkbox"] {

            width: auto;

            margin-right: 8px;

        }

        .status-badge {

            padding: 3px 8px;

            border-radius: 4px;

            font-size: 12px;

            font-weight: 600;

        }

        .status-closed {

            background: #ffc107;

            color: #000;

        }

        .status-active {

            background: #28a745;

            color: white;

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
                <?php if ($user_role === 'admin'): ?>
                    <!-- Admin Menüsü -->
                    <li><a href="../dashboard.php">🏠 Ana Sayfa</a></li>
                    <li><a href="../properties/list.php">🏢 İlanlar</a></li>
                    <li><a href="../properties/add.php">➕ İlan Ekle</a></li>
                    <li><a href="../users/list.php">👥 Kullanıcılar</a></li>
                    <li><a href="../settings.php">⚙️ Ayarlar</a></li>
                    <li><a href="../seo/">🎯 SEO Yönetimi</a></li>
                    <li><a href="../crm/index.php">📊 CRM Sistemi</a></li>
                    <li><a href="../sms/send.php">📤 SMS Gönder</a></li>
                    <li><a href="../sms/logs.php">📋 SMS Logları</a></li>
                    <li><a href="../sms/settings.php">⚙️ SMS Ayarları</a></li>
                    <li><a href="closing.php" class="active">💰 Portföy Kapatma</a></li>
                    <li><a href="closing-list.php">📋 Kapatma Listesi</a></li>
                    <li><a href="reports.php">📊 Satış Raporları</a></li>
                    <li><a href="commission-settings.php">⚙️ Prim Ayarları</a></li>
                    <li><a href="closed-properties.php">🔒 Kapatılan İlanlar</a></li>
                <?php else: ?>
                    <!-- Kullanıcı (Danışman) Menüsü -->
                    <li><a href="../user-dashboard.php">🏠 Ana Sayfa</a></li>
                    <li><a href="../my-properties.php">🏢 İlanlarım</a></li>
                    <li><a href="../properties/add.php">➕ İlan Ekle</a></li>
                    <li><a href="closing.php" class="active">💰 Portföy Kapatma</a></li>
                    <li><a href="my-reports.php">📊 Satış Raporlarım</a></li>
                    <li><a href="../crm/index.php">📊 CRM Sistemi</a></li>
                    <li><a href="../my-profile.php">👤 Profilim</a></li>
                <?php endif; ?>
            </ul>
        </nav>
        
        <!-- Main Content -->

        <div class="main-content">

            <div class="top-navbar">

                <div class="navbar-left">

                    <h3>Portföy Kapatma</h3>

                </div>

                <div class="navbar-right">

                    <span><?php echo htmlspecialchars($user_name); ?></span>

                    <a href="../logout.php" class="btn-logout">Çıkış</a>

                </div>

            </div>



            <div class="content">

                <?php if (isset($success_msg)): ?>

                    <div class="alert alert-success"><?php echo $success_msg; ?></div>

                <?php endif; ?>



                <?php if (isset($error_msg)): ?>

                    <div class="alert alert-error"><?php echo $error_msg; ?></div>

                <?php endif; ?>



                <!-- Kapatma Formu -->

                <div class="closing-form">

                    <h2>Yeni Portföy Kapatma</h2>



                    <form method="POST" action="">

                        <!-- İlan Seçimi -->

                        <div class="property-selector">

                            <div class="form-group">

                                <label>Mevcut İlanlardan Seç (Opsiyonel)</label>

                                <select name="property_id" id="property_select" onchange="updatePropertyInfo()">

                                    <option value="">-- Manuel Giriş --</option>

                                    <?php foreach ($properties as $prop): ?>

                                        <option value="<?php echo $prop['id']; ?>"

                                            data-title="<?php echo htmlspecialchars($prop['baslik']); ?>"

                                            data-type="<?php echo strtolower($prop['kategori']); ?>">

                                            <?php echo htmlspecialchars($prop['baslik']); ?>

                                            (<?php echo $prop['ilce']; ?> - <?php echo number_format($prop['fiyat'], 0, ',', '.'); ?> TL)

                                        </option>

                                    <?php endforeach; ?>

                                </select>

                            </div>



                            <div class="checkbox-group">

                                <label>

                                    <input type="checkbox" name="update_property_status" value="1" checked>

                                    İlan durumunu "Kapatıldı" olarak güncelle

                                </label>

                            </div>

                        </div>



                        <div class="form-grid">

                            <div class="form-group">

                                <label>Kapatma Tarihi *</label>

                                <input type="date" name="closing_date" value="<?php echo date('Y-m-d'); ?>" required>

                            </div>



                            <div class="form-group">

                                <label>Gayrimenkul Adı *</label>

                                <input type="text" name="property_title" id="property_title"

                                    placeholder="Örn: Akyalçın Suit 1+1 Daire" required>

                            </div>



                            <div class="form-group">

                                <label>İşlem Tipi *</label>

                                <select name="closing_type" id="closing_type" required>

                                    <option value="">Seçiniz</option>

                                    <option value="kiralik">Kiralık</option>

                                    <option value="satilik">Satılık</option>

                                </select>

                            </div>



                            <div class="form-group">

                                <label>Toplam Hizmet Bedeli (TL) *</label>

                                <input type="text" name="total_amount" id="total_amount"

                                    placeholder="10.000" required

                                    onkeyup="formatCurrency(this); calculateShares();">

                            </div>

                        </div>



                        <!-- Danışman Paylaşımları -->

                        <div class="advisor-section">

                            <h3>Paylaşım Detayları</h3>



                            <div class="total-display">

                                Toplam Tutar: <span id="total_display">0</span> TL

                            </div>



                            <div class="form-grid">

                                <div class="form-group">

                                    <label>Ofis Payı (TL) *</label>

                                    <input type="text" name="office_share" id="office_share"

                                        placeholder="5.000" required

                                        onkeyup="formatCurrency(this);">

                                </div>



                                <div class="form-group">

                                    <label>Müşteri Danışmanı</label>

                                    <select name="customer_advisor_id">

                                        <option value="">Seçiniz</option>

                                        <?php foreach ($advisors as $advisor): ?>

                                            <option value="<?php echo $advisor['id']; ?>">

                                                <?php echo htmlspecialchars($advisor['full_name']); ?>

                                            </option>

                                        <?php endforeach; ?>

                                    </select>

                                </div>



                                <div class="form-group">

                                    <label>Müşteri Danışmanı Payı (TL)</label>

                                    <input type="text" name="customer_advisor_share"

                                        placeholder="2.500"

                                        onkeyup="formatCurrency(this);">

                                </div>



                                <div class="form-group">

                                    <label>Portföy Danışmanı</label>

                                    <select name="portfolio_advisor_id">

                                        <option value="">Seçiniz</option>

                                        <?php foreach ($advisors as $advisor): ?>

                                            <option value="<?php echo $advisor['id']; ?>">

                                                <?php echo htmlspecialchars($advisor['full_name']); ?>

                                            </option>

                                        <?php endforeach; ?>

                                    </select>

                                </div>



                                <div class="form-group">

                                    <label>Portföy Danışmanı Payı (TL)</label>

                                    <input type="text" name="portfolio_advisor_share"

                                        placeholder="2.500"

                                        onkeyup="formatCurrency(this);">

                                </div>



                                <div class="form-group">

                                    <label>Referans Danışmanı</label>

                                    <select name="referral_advisor_id">

                                        <option value="">Seçiniz</option>

                                        <?php foreach ($advisors as $advisor): ?>

                                            <option value="<?php echo $advisor['id']; ?>">

                                                <?php echo htmlspecialchars($advisor['full_name']); ?>

                                            </option>

                                        <?php endforeach; ?>

                                    </select>

                                </div>



                                <div class="form-group">

                                    <label>Referans Danışmanı Payı (TL)</label>

                                    <input type="text" name="referral_advisor_share"

                                        placeholder="1.250"

                                        onkeyup="formatCurrency(this);">

                                </div>

                            </div>



                            <div class="form-group" style="margin-top: 20px;">

                                <label>Notlar</label>

                                <textarea name="notes" rows="3" placeholder="Ek bilgiler..."></textarea>

                            </div>

                        </div>



                        <button type="submit" name="add_closing" class="btn-submit">

                            ✅ Kapatmayı Kaydet

                        </button>

                    </form>

                </div>



                <!-- Son Kapatmalar -->

                <div class="recent-closings">

                    <h2>Son Kapatmalar</h2>



                    <table class="closings-table">

                        <thead>

                            <tr>

                                <th>Tarih</th>

                                <th>Gayrimenkul</th>

                                <th>Tip</th>

                                <th>Toplam</th>

                                <th>Ofis</th>

                                <th>Danışmanlar</th>

                                <th>Durum</th>

                                <th>Kaydeden</th>

                            </tr>

                        </thead>

                        <tbody>

                            <?php foreach ($recent_closings as $closing): ?>

                                <tr>

                                    <td><?php echo date('d.m.Y', strtotime($closing['closing_date'])); ?></td>

                                    <td><?php echo htmlspecialchars($closing['property_title']); ?></td>

                                    <td>

                                        <span class="badge <?php echo $closing['closing_type'] == 'kiralik' ? 'badge-info' : 'badge-success'; ?>">

                                            <?php echo ucfirst($closing['closing_type']); ?>

                                        </span>

                                    </td>

                                    <td class="amount-cell">

                                        <?php echo number_format($closing['total_amount'], 2, ',', '.'); ?> TL

                                    </td>

                                    <td><?php echo number_format($closing['office_share'], 2, ',', '.'); ?> TL</td>

                                    <td>

                                        <?php

                                        $advisors_list = [];

                                        if ($closing['customer_advisor_name']) {

                                            $advisors_list[] = "M: " . $closing['customer_advisor_name'];
                                        }

                                        if ($closing['portfolio_advisor_name']) {

                                            $advisors_list[] = "P: " . $closing['portfolio_advisor_name'];
                                        }

                                        if ($closing['referral_advisor_name']) {

                                            $advisors_list[] = "R: " . $closing['referral_advisor_name'];
                                        }

                                        echo implode('<br>', $advisors_list);

                                        ?>

                                    </td>

                                    <td>

                                        <?php if ($closing['property_status_changed']): ?>

                                            <span class="status-badge status-closed">Kapatıldı</span>

                                        <?php else: ?>

                                            <span class="status-badge status-active">Manuel</span>

                                        <?php endif; ?>

                                    </td>

                                    <td><?php echo htmlspecialchars($closing['created_by_name']); ?></td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </div>



    <script>
        function formatCurrency(input) {

            let value = input.value.replace(/[^0-9,]/g, '');

            value = value.replace(',', '.');



            if (value) {

                let parts = value.split('.');

                parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');

                input.value = parts.join(',');

            }

        }



        function calculateShares() {

            let totalInput = document.getElementById('total_amount');

            let displayElement = document.getElementById('total_display');



            let value = totalInput.value.replace(/\./g, '').replace(',', '.');

            let total = parseFloat(value) || 0;



            displayElement.textContent = new Intl.NumberFormat('tr-TR', {

                minimumFractionDigits: 2,

                maximumFractionDigits: 2

            }).format(total);

        }



        function updatePropertyInfo() {

            const select = document.getElementById('property_select');

            const titleInput = document.getElementById('property_title');

            const typeSelect = document.getElementById('closing_type');



            if (select.value) {

                const selected = select.options[select.selectedIndex];

                titleInput.value = selected.getAttribute('data-title');

                typeSelect.value = selected.getAttribute('data-type');

            } else {

                titleInput.value = '';

                typeSelect.value = '';

            }

        }
    </script>

</body>

</html>