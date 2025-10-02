<?php
/**
 * NETGSM SMS API Entegrasyon Sınıfı
 * Dosya: C:\xampp\htdocs\plazanet\classes\NetGSM.php
 */

class NetGSM {
    private $api_key;
    private $api_password;
    private $sender_name;
    private $test_mode;
    private $db;
    
    // NETGSM API URL'leri
    const API_URL = 'https://api.netgsm.com.tr/sms/send/get/';
    const API_URL_ALT = 'https://api.netgsm.com.tr/sms/send/xml';
    const BALANCE_URL = 'https://api.netgsm.com.tr/balance/list/get';
    const REPORT_URL = 'https://api.netgsm.com.tr/sms/report';
    
    public function __construct($db) {
        $this->db = $db;
        $this->loadSettings();
    }
    
    /**
     * Ayarları veritabanından yükle
     */
    private function loadSettings() {
        $stmt = $this->db->query("SELECT * FROM sms_settings WHERE is_active = 1 LIMIT 1");
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($settings) {
            $this->api_key = $settings['api_key'];
            $this->api_password = $settings['api_password'];
            $this->sender_name = $settings['sender_name'];
            $this->test_mode = $settings['test_mode'];
        } else {
            throw new Exception("SMS ayarları bulunamadı!");
        }
    }
    
    /**
     * Tekli SMS gönderimi
     */
    public function sendSMS($phone, $message, $sms_type = 'manuel', $sender_user_id = null, $recipient_info = array()) {
        // Telefon numarasını temizle
        $phone = $this->cleanPhoneNumber($phone);
        
        // Kara listede mi kontrol et
        if($this->isBlacklisted($phone)) {
            return array(
                'status' => false,
                'message' => 'Bu numara SMS almayı reddetmiş'
            );
        }
        
        // Test modunda gerçek gönderim yapma
        if($this->test_mode) {
            $this->logSMS($phone, $message, $sms_type, 'sent', 'TEST_MODE', null, $sender_user_id, $recipient_info);
            return array(
                'status' => true,
                'message' => 'SMS başarıyla gönderildi (TEST MODU)',
                'message_id' => 'TEST_' . time()
            );
        }
        
        // NETGSM API parametreleri
        $params = array(
            'usercode' => $this->api_key,
            'password' => $this->api_password,
            'gsmno' => $phone,
            'message' => $message,
            'msgheader' => $this->sender_name,
            'filter' => '0',
            'startdate' => '',
            'stopdate' => '',
            'dil' => 'TR'  // Türkçe karakter desteği için eklendi
        );
        
        // API'ye istek gönder
        $result = $this->makeRequest(self::API_URL, $params);
        
        // Sonucu yorumla
        if($result && strpos($result, '00') === 0) {
            // Başarılı
            $message_id = trim(str_replace('00', '', $result));
            $this->logSMS($phone, $message, $sms_type, 'sent', $message_id, null, $sender_user_id, $recipient_info);
            
            return array(
                'status' => true,
                'message' => 'SMS başarıyla gönderildi',
                'message_id' => $message_id
            );
        } else {
            // Hatalı
            $error_message = $this->getErrorMessage($result);
            $this->logSMS($phone, $message, $sms_type, 'failed', null, $error_message, $sender_user_id, $recipient_info);
            
            return array(
                'status' => false,
                'message' => 'SMS gönderilemedi: ' . $error_message,
                'error_code' => $result
            );
        }
    }
    
    /**
     * Toplu SMS gönderimi
     */
    public function sendBulkSMS($phones, $message, $sms_type = 'yeni_ilan', $sender_user_id = null) {
        $results = array();
        
        foreach($phones as $phone_info) {
            $phone = is_array($phone_info) ? $phone_info['phone'] : $phone_info;
            $recipient_info = is_array($phone_info) ? $phone_info : array();
            
            $result = $this->sendSMS($phone, $message, $sms_type, $sender_user_id, $recipient_info);
            $results[] = array(
                'phone' => $phone,
                'status' => $result['status'],
                'message' => $result['message']
            );
            
            // API limitlerini aşmamak için kısa bekleme
            usleep(100000); // 0.1 saniye
        }
        
        return $results;
    }
    
    /**
     * Kredi sorgulama
     */
    public function checkBalance() {
        $params = array(
            'usercode' => $this->api_key,
            'password' => $this->api_password
        );
        
        $result = $this->makeRequest(self::BALANCE_URL, $params);
        
        if($result && is_numeric($result)) {
            return array(
                'status' => true,
                'balance' => $result
            );
        } else {
            return array(
                'status' => false,
                'message' => 'Bakiye sorgulanamadı'
            );
        }
    }
    
    /**
     * SMS loglarını kaydet
     */
    private function logSMS($phone, $message, $sms_type, $status, $message_id = null, $error = null, $sender_user_id = null, $recipient_info = array()) {
        $sql = "INSERT INTO sms_logs (
            phone_number, message_text, sms_type, status,
            netgsm_message_id, error_message, sent_date,
            sender_user_id, sender_user_name,
            recipient_type, recipient_id, recipient_name, property_id
        ) VALUES (
            :phone, :message, :sms_type, :status,
            :message_id, :error, :sent_date,
            :sender_id, :sender_name,
            :recipient_type, :recipient_id, :recipient_name, :property_id
        )";
        
        $sent_date = ($status == 'sent') ? date('Y-m-d H:i:s') : null;
        $sender_name = isset($recipient_info['sender_name']) ? $recipient_info['sender_name'] : null;
        $recipient_type = isset($recipient_info['type']) ? $recipient_info['type'] : null;
        $recipient_id = isset($recipient_info['id']) ? $recipient_info['id'] : null;
        $recipient_name = isset($recipient_info['name']) ? $recipient_info['name'] : null;
        $property_id = isset($recipient_info['property_id']) ? $recipient_info['property_id'] : null;
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':phone' => $phone,
            ':message' => $message,
            ':sms_type' => $sms_type,
            ':status' => $status,
            ':message_id' => $message_id,
            ':error' => $error,
            ':sent_date' => $sent_date,
            ':sender_id' => $sender_user_id,
            ':sender_name' => $sender_name,
            ':recipient_type' => $recipient_type,
            ':recipient_id' => $recipient_id,
            ':recipient_name' => $recipient_name,
            ':property_id' => $property_id
        ));
    }
    
    /**
     * Telefon numarasını temizle
     */
    private function cleanPhoneNumber($phone) {
        // Tüm boşluk ve özel karakterleri kaldır
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Başında 0 varsa kaldır
        if(substr($phone, 0, 1) == '0') {
            $phone = substr($phone, 1);
        }
        
        // Başında 90 yoksa ekle
        if(substr($phone, 0, 2) != '90') {
            $phone = '90' . $phone;
        }
        
        return $phone;
    }
    
    /**
     * Kara liste kontrolü
     */
    private function isBlacklisted($phone) {
        $stmt = $this->db->prepare("SELECT id FROM sms_blacklist WHERE phone_number = :phone");
        $stmt->execute(array(':phone' => $phone));
        return $stmt->rowCount() > 0;
    }
    
    /**
     * HTTP isteği gönder - POST metodu ile
     */
    private function makeRequest($url, $params) {
        // Alt kullanıcı için POST metodu kullan
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/x-www-form-urlencoded'
        ));
        
        $result = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        // DEBUG
        error_log("NETGSM Response: " . $result);
        error_log("HTTP Code: " . $http_code);
        
        if($curl_error) {
            error_log("CURL Error: " . $curl_error);
        }
        
        return $result;
    }
    
    /**
     * Hata kodlarını yorumla
     */
    private function getErrorMessage($code) {
        $errors = array(
            '20' => 'Mesaj metninde hata',
            '30' => 'Geçersiz kullanıcı adı veya şifre',
            '40' => 'Abone hesabınızda kredi yok',
            '50' => 'Gönderen adı hatalı',
            '51' => 'Gönderen adı onaylı değil',
            '60' => 'Tarih formatı hatalı',
            '70' => 'Hatalı veya eksik parametre',
            '80' => 'Telefon numarası hatalı',
            '85' => 'Mobil hat değil',
            '100' => 'Sistem hatası'
        );
        
        return isset($errors[$code]) ? $errors[$code] : 'Bilinmeyen hata: ' . $code;
    }
    
    /**
     * Manuel SMS gönderimi (CRM'den)
     */
    public function sendManualSMS($phone, $message, $sender_id, $sender_name = '') {
        return $this->sendSMS($phone, $message, 'manuel', $sender_id, array(
            'sender_name' => $sender_name,
            'type' => 'manuel'
        ));
    }
    
    /**
     * SMS raporu al
     */
    public function getSMSReport($message_id) {
        $params = array(
            'usercode' => $this->api_key,
            'password' => $this->api_password,
            'bulkid' => $message_id,
            'type' => 0
        );
        
        $result = $this->makeRequest(self::REPORT_URL, $params);
        
        if($result) {
            // NETGSM rapor formatını parse et
            $lines = explode("\n", $result);
            $report = array();
            
            foreach($lines as $line) {
                if(!empty($line)) {
                    $parts = explode(' ', $line);
                    if(count($parts) >= 3) {
                        $report[] = array(
                            'phone' => $parts[0],
                            'status' => $parts[1],
                            'date' => $parts[2]
                        );
                    }
                }
            }
            
            return $report;
        }
        
        return false;
    }
    
    /**
     * Şablon bazlı SMS gönder
     */
    public function sendTemplatedSMS($phone, $template_type, $variables = array(), $sender_user_id = null) {
        // Şablonu getir
        $stmt = $this->db->prepare("SELECT message_template FROM sms_templates WHERE template_type = :type AND is_active = 1 LIMIT 1");
        $stmt->execute(array(':type' => $template_type));
        $template = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if(!$template) {
            return array(
                'status' => false,
                'message' => 'SMS şablonu bulunamadı'
            );
        }
        
        // Değişkenleri değiştir
        $message = $template['message_template'];
        foreach($variables as $key => $value) {
            $message = str_replace('{' . $key . '}', $value, $message);
        }
        
        // SMS'i gönder
        return $this->sendSMS($phone, $message, $template_type, $sender_user_id);
    }
    
    /**
     * Kara listeye ekle
     */
    public function addToBlacklist($phone, $reason = 'Kullanıcı isteği', $method = 'web') {
        $phone = $this->cleanPhoneNumber($phone);
        
        $sql = "INSERT INTO sms_blacklist (phone_number, reason, unsubscribe_date, unsubscribe_method) 
                VALUES (:phone, :reason, NOW(), :method)
                ON DUPLICATE KEY UPDATE reason = :reason, unsubscribe_date = NOW()";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(array(
            ':phone' => $phone,
            ':reason' => $reason,
            ':method' => $method
        ));
    }
    
    /**
     * Kara listeden çıkar
     */
    public function removeFromBlacklist($phone) {
        $phone = $this->cleanPhoneNumber($phone);
        
        $stmt = $this->db->prepare("DELETE FROM sms_blacklist WHERE phone_number = :phone");
        return $stmt->execute(array(':phone' => $phone));
    }
}
?>