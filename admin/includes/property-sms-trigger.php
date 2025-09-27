<?php
// C:\xampp\htdocs\plazanet\includes\property-sms-trigger.php
// İlan eklendiğinde SMS gönderimi için tetikleyici

require_once __DIR__ . '/../classes/NetGSM.php';

class PropertySMSTrigger {
    private $db;
    private $netgsm;
    
    public function __construct($db) {
        $this->db = $db;
        
        // SMS sistemi aktifse NetGSM'i başlat
        $stmt = $db->query("SELECT is_active FROM sms_settings WHERE id = 1");
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($settings && $settings['is_active']) {
            try {
                $this->netgsm = new NetGSM($db);
            } catch(Exception $e) {
                error_log("SMS sistemi başlatılamadı: " . $e->getMessage());
            }
        }
    }
    
    // Yeni ilan eklendiğinde çağrılacak
    public function onPropertyAdded($property_id, $property_data, $user_info) {
        if(!$this->netgsm) return;
        
        // İlan linkini oluştur
        $base_url = "https://plazaemlak.com"; // Gerçek domain'i buraya yazın
        $ilan_link = $base_url . "/pages/detail.php?id=" . $property_id;
        
        // Kısa link oluştur (opsiyonel - bit.ly veya benzeri servis kullanabilirsiniz)
        $short_link = $this->createShortLink($ilan_link);
        
        // 1. TÜM DANIŞMANLARA SMS GÖNDER
        $this->sendToAllAdvisors($property_data, $short_link, $user_info);
        
        // 2. BÜTÇEYE UYGUN ALICI MÜŞTERİLERE SMS GÖNDER
        $this->sendToMatchingBuyers($property_data, $short_link, $user_info);
    }
    
    // Tüm danışmanlara SMS gönder
    private function sendToAllAdvisors($property_data, $ilan_link, $user_info) {
        // Tüm aktif danışmanları çek
        $sql = "SELECT id, full_name, mobile, sms_permission 
                FROM users 
                WHERE status = 'active' 
                AND mobile IS NOT NULL 
                AND mobile != ''
                AND sms_permission = 1";
        
        $stmt = $this->db->query($sql);
        $advisors = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // SMS şablonunu al
        $template = $this->getSMSTemplate('yeni_ilan');
        
        // Mesaj metnini hazırla
        $message = str_replace(
            ['{ilan_link}', '{danisman_adi}', '{ilan_baslik}', '{fiyat}'],
            [$ilan_link, $user_info['full_name'], $property_data['baslik'], number_format($property_data['fiyat'])],
            $template
        );
        
        // Toplu gönderim için telefon listesi hazırla
        $phone_list = [];
        foreach($advisors as $advisor) {
            if(!empty($advisor['mobile'])) {
                $phone_list[] = [
                    'phone' => $advisor['mobile'],
                    'type' => 'danisman',
                    'id' => $advisor['id'],
                    'name' => $advisor['full_name'],
                    'property_id' => $property_data['id'] ?? null
                ];
            }
        }
        
        // Toplu SMS gönder
        if(!empty($phone_list)) {
            $results = $this->netgsm->sendBulkSMS(
                $phone_list, 
                $message, 
                'yeni_ilan',
                $user_info['id'] ?? null
            );
            
            // Sonuçları logla
            $this->logBulkResults($results, 'yeni_ilan_danisman');
        }
    }
    
    // Bütçeye uygun alıcılara SMS gönder
    private function sendToMatchingBuyers($property_data, $ilan_link, $user_info) {
        // Bütçesi uygun alıcıları bul
        $sql = "SELECT id, ad, soyad, telefon, min_butce, max_butce, sms_permission, mersis_permission
                FROM crm_alici_musteriler
                WHERE durum = 'aktif'
                AND sms_permission = 1
                AND mersis_permission = 1
                AND telefon IS NOT NULL
                AND telefon != ''
                AND :fiyat BETWEEN min_butce AND max_butce";
        
        // İl/ilçe filtresi ekle
        if(!empty($property_data['il'])) {
            $sql .= " AND (aranan_il = :il OR aranan_il IS NULL OR aranan_il = '')";
        }
        
        $stmt = $this->db->prepare($sql);
        $params = [':fiyat' => $property_data['fiyat']];
        
        if(!empty($property_data['il'])) {
            $params[':il'] = $property_data['il'];
        }
        
        $stmt->execute($params);
        $buyers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // SMS şablonunu al
        $template = $this->getSMSTemplate('musteri_bilgi');
        
        // Her alıcıya özel mesaj gönder
        foreach($buyers as $buyer) {
            $musteri_adi = $buyer['ad'] . ' ' . $buyer['soyad'];
            
            // MERSIS kontrolü için özel mesaj ekle
            $mersis_text = "\nBu mesaj talebiniz üzerine gönderilmiştir. SMS almak istemiyorsanız VAZGEC yazıp 5656'ya gönderin.";
            
            $message = str_replace(
                ['{musteri_adi}', '{ilan_link}', '{danisman_adi}', '{fiyat}'],
                [$musteri_adi, $ilan_link, $user_info['full_name'], number_format($property_data['fiyat'])],
                $template
            );
            
            // MERSIS metnini ekle
            $message .= $mersis_text;
            
            // SMS gönder
            $result = $this->netgsm->sendSMS(
                $buyer['telefon'],
                $message,
                'musteri_bilgi',
                $user_info['id'] ?? null,
                [
                    'type' => 'alici',
                    'id' => $buyer['id'],
                    'name' => $musteri_adi,
                    'property_id' => $property_data['id'] ?? null
                ]
            );
            
            // Başarılı gönderimde müşteriyi bilgilendir
            if($result['status']) {
                $this->logCustomerNotification($buyer['id'], $property_data['id'], 'sent');
            }
        }
    }
    
    // SMS şablonunu getir
    private function getSMSTemplate($type) {
        $stmt = $this->db->prepare("SELECT message_template FROM sms_templates WHERE template_type = :type AND is_active = 1 LIMIT 1");
        $stmt->execute([':type' => $type]);
        $template = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($template) {
            return $template['message_template'];
        }
        
        // Varsayılan şablonlar
        $defaults = [
            'yeni_ilan' => 'Plaza Emlak Yeni Portföy: {ilan_link} Ekleyen: {danisman_adi}',
            'musteri_bilgi' => 'Sevgili {musteri_adi}, Plaza Emlak\'ta size uygun daire var: {ilan_link} Danışman: {danisman_adi}'
        ];
        
        return $defaults[$type] ?? 'Plaza Emlak: {ilan_link}';
    }
    
    // Kısa link oluştur (opsiyonel)
    private function createShortLink($url) {
        // Bit.ly veya benzeri servis kullanabilirsiniz
        // Şimdilik orijinal linki döndürelim
        return $url;
    }
    
    // Toplu sonuçları logla
    private function logBulkResults($results, $type) {
        $success_count = 0;
        $fail_count = 0;
        
        foreach($results as $result) {
            if($result['status']) {
                $success_count++;
            } else {
                $fail_count++;
                error_log("SMS gönderilemedi ({$type}): " . $result['phone'] . " - " . $result['message']);
            }
        }
        
        error_log("Toplu SMS sonucu ({$type}): {$success_count} başarılı, {$fail_count} başarısız");
    }
    
    // Müşteri bildirimi logla
    private function logCustomerNotification($customer_id, $property_id, $status) {
        $sql = "INSERT INTO crm_musteri_bildirimleri (musteri_id, property_id, bildirim_tipi, durum, tarih) 
                VALUES (:musteri_id, :property_id, 'sms', :durum, NOW())
                ON DUPLICATE KEY UPDATE durum = :durum, tarih = NOW()";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':musteri_id' => $customer_id,
                ':property_id' => $property_id,
                ':durum' => $status
            ]);
        } catch(PDOException $e) {
            error_log("Bildirim log hatası: " . $e->getMessage());
        }
    }
}