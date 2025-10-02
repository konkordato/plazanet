<?php
session_start();
if(!isset($_SESSION['admin_logged_in']) && !isset($_SESSION['user_logged_in'])) {
    header("Location: ../index.php");
    exit();
}

require_once '../../config/database.php';

$current_user_id = $_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? 0;
$current_user_role = $_SESSION['user_role'] ?? 'user';

// Tarih filtreleri
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Excel header
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="CRM_Rapor_' . date('Y-m-d') . '.xls"');
header('Cache-Control: max-age=0');

// UTF-8 BOM for Excel
echo "\xEF\xBB\xBF";

// Where koşulu
$where_user = ($current_user_role != 'admin') ? " AND ekleyen_user_id = $current_user_id" : "";
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        table { border-collapse: collapse; width: 100%; }
        th { background-color: #34495e; color: white; padding: 8px; border: 1px solid black; }
        td { padding: 8px; border: 1px solid #ddd; }
        .header { background-color: #3498db; color: white; padding: 10px; margin-bottom: 10px; }
        .section-title { font-weight: bold; background-color: #ecf0f1; padding: 5px; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>PLAZANET EMLAK - CRM RAPORU</h1>
    <p>Rapor Tarihi: <?php echo date('d.m.Y H:i'); ?></p>
    <p>Dönem: <?php echo date('d.m.Y', strtotime($start_date)); ?> - <?php echo date('d.m.Y', strtotime($end_date)); ?></p>
    
    <!-- ALICI MÜŞTERİLER -->
    <h2 class="section-title">ALICI MÜŞTERİ LİSTESİ</h2>
    <table>
        <thead>
            <tr>
                <th>Ad Soyad</th>
                <th>Telefon</th>
                <th>E-posta</th>
                <th>Aradığı Taşınmaz</th>
                <th>Bölge</th>
                <th>Min Bütçe</th>
                <th>Max Bütçe</th>
                <th>Ekleyen</th>
                <th>Kayıt Tarihi</th>
                <th>Durum</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $sql = "SELECT * FROM crm_alici_musteriler 
                    WHERE DATE(ekleme_tarihi) BETWEEN :start_date AND :end_date $where_user
                    ORDER BY ekleme_tarihi DESC";
            $stmt = $db->prepare($sql);
            $stmt->execute([':start_date' => $start_date, ':end_date' => $end_date]);
            $alicilar = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach($alicilar as $alici):
            ?>
            <tr>
                <td><?php echo $alici['ad'] . ' ' . $alici['soyad']; ?></td>
                <td>0<?php echo $alici['telefon']; ?></td>
                <td><?php echo $alici['email']; ?></td>
                <td><?php echo $alici['aranan_tasinmaz']; ?></td>
                <td><?php echo $alici['aranan_il'] . '/' . $alici['aranan_ilce']; ?></td>
                <td><?php echo number_format($alici['min_butce'], 0, ',', '.'); ?> TL</td>
                <td><?php echo number_format($alici['max_butce'], 0, ',', '.'); ?> TL</td>
                <td><?php echo $alici['ekleyen_user_adi']; ?></td>
                <td><?php echo date('d.m.Y', strtotime($alici['ekleme_tarihi'])); ?></td>
                <td><?php echo $alici['durum'] == 'aktif' ? 'Aktif' : 'Pasif'; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <br><br>
    
    <!-- SATICI MÜŞTERİLER -->
    <h2 class="section-title">SATICI MÜŞTERİ LİSTESİ</h2>
    <table>
        <thead>
            <tr>
                <th>Ad Soyad</th>
                <th>Telefon</th>
                <th>E-posta</th>
                <th>Taşınmaz Cinsi</th>
                <th>Ada/Parsel</th>
                <th>Sahibinden No</th>
                <th>Arama Sayısı</th>
                <th>Ekleyen</th>
                <th>Kayıt Tarihi</th>
                <th>Durum</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $sql = "SELECT * FROM crm_satici_musteriler 
                    WHERE DATE(ekleme_tarihi) BETWEEN :start_date AND :end_date $where_user
                    ORDER BY ekleme_tarihi DESC";
            $stmt = $db->prepare($sql);
            $stmt->execute([':start_date' => $start_date, ':end_date' => $end_date]);
            $saticilar = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach($saticilar as $satici):
            ?>
            <tr>
                <td><?php echo $satici['ad'] . ' ' . $satici['soyad']; ?></td>
                <td>0<?php echo $satici['telefon']; ?></td>
                <td><?php echo $satici['email']; ?></td>
                <td><?php echo $satici['tasinmaz_cinsi']; ?></td>
                <td><?php echo $satici['ada'] . '/' . $satici['parsel']; ?></td>
                <td><?php echo $satici['sahibinden_no']; ?></td>
                <td><?php echo $satici['arama_sayisi']; ?></td>
                <td><?php echo $satici['ekleyen_user_adi']; ?></td>
                <td><?php echo date('d.m.Y', strtotime($satici['ekleme_tarihi'])); ?></td>
                <td><?php echo $satici['durum'] == 'aktif' ? 'Aktif' : 'Pasif'; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <br><br>
    
    <!-- GÖRÜŞME NOTLARI -->
    <h2 class="section-title">GÖRÜŞME NOTLARI</h2>
    <table>
        <thead>
            <tr>
                <th>Tarih</th>
                <th>Müşteri Tipi</th>
                <th>Müşteri ID</th>
                <th>Görüşme Notu</th>
                <th>Görüşen</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $sql = "SELECT * FROM crm_gorusme_notlari 
                    WHERE DATE(gorusme_tarihi) BETWEEN :start_date AND :end_date";
            if($current_user_role != 'admin') {
                $sql .= " AND gorusen_user_id = :user_id";
            }
            $sql .= " ORDER BY gorusme_tarihi DESC";
            
            $stmt = $db->prepare($sql);
            $params = [':start_date' => $start_date, ':end_date' => $end_date];
            if($current_user_role != 'admin') {
                $params[':user_id'] = $current_user_id;
            }
            $stmt->execute($params);
            $notlar = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach($notlar as $not):
            ?>
            <tr>
                <td><?php echo date('d.m.Y H:i', strtotime($not['gorusme_tarihi'])); ?></td>
                <td><?php echo $not['musteri_tipi'] == 'alici' ? 'Alıcı' : 'Satıcı'; ?></td>
                <td><?php echo $not['musteri_id']; ?></td>
                <td><?php echo $not['gorusme_notu']; ?></td>
                <td><?php echo $not['gorusen_user_adi']; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <br><br>
    
    <!-- ÖZET İSTATİSTİKLER -->
    <h2 class="section-title">ÖZET İSTATİSTİKLER</h2>
    <table>
        <tr>
            <td><strong>Toplam Alıcı Müşteri:</strong></td>
            <td><?php echo count($alicilar); ?></td>
        </tr>
        <tr>
            <td><strong>Toplam Satıcı Müşteri:</strong></td>
            <td><?php echo count($saticilar); ?></td>
        </tr>
        <tr>
            <td><strong>Toplam Görüşme:</strong></td>
            <td><?php echo count($notlar); ?></td>
        </tr>
        <tr>
            <td><strong>Rapor Oluşturan:</strong></td>
            <td><?php echo $_SESSION['admin_username'] ?? $_SESSION['username'] ?? ''; ?></td>
        </tr>
    </table>
    
    <?php if($current_user_role == 'admin'): ?>
    <br><br>
    
    <!-- DANIŞMAN PERFORMANSI -->
    <h2 class="section-title">DANIŞMAN PERFORMANS RAPORU</h2>
    <table>
        <thead>
            <tr>
                <th>Danışman</th>
                <th>Alıcı Sayısı</th>
                <th>Satıcı Sayısı</th>
                <th>Toplam Müşteri</th>
                <th>Görüşme Sayısı</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $sql = "SELECT 
                u.full_name, u.username,
                (SELECT COUNT(*) FROM crm_alici_musteriler WHERE ekleyen_user_id = u.id 
                 AND DATE(ekleme_tarihi) BETWEEN :start_date AND :end_date) as alici_sayisi,
                (SELECT COUNT(*) FROM crm_satici_musteriler WHERE ekleyen_user_id = u.id 
                 AND DATE(ekleme_tarihi) BETWEEN :start_date AND :end_date) as satici_sayisi,
                (SELECT COUNT(*) FROM crm_gorusme_notlari WHERE gorusen_user_id = u.id 
                 AND DATE(gorusme_tarihi) BETWEEN :start_date AND :end_date) as gorusme_sayisi
                FROM users u 
                WHERE u.status = 'active'";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([':start_date' => $start_date, ':end_date' => $end_date]);
            $danismanlar = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach($danismanlar as $danisman):
                $toplam = $danisman['alici_sayisi'] + $danisman['satici_sayisi'];
            ?>
            <tr>
                <td><?php echo $danisman['full_name'] ?: $danisman['username']; ?></td>
                <td><?php echo $danisman['alici_sayisi']; ?></td>
                <td><?php echo $danisman['satici_sayisi']; ?></td>
                <td><?php echo $toplam; ?></td>
                <td><?php echo $danisman['gorusme_sayisi']; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</body>
</html>