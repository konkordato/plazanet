<?php
// includes/SeoHelper.php
// Bu dosya SEO işlemlerini otomatik yapar

class SeoHelper {
    private $db;
    private $site_url = "https://www.plazaemlak.com";
    private $default_image = "/assets/images/plaza-logo.jpg";
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    // SEO-friendly URL oluştur
    public function createSlug($text) {
        // Türkçe karakterleri değiştir
        $turkce = ['ş','Ş','ı','İ','ğ','Ğ','ü','Ü','ö','Ö','ç','Ç'];
        $english = ['s','s','i','i','g','g','u','u','o','o','c','c'];
        $text = str_replace($turkce, $english, $text);
        
        // Küçük harfe çevir
        $text = mb_strtolower($text, 'UTF-8');
        
        // Özel karakterleri tire yap
        $text = preg_replace('~[^\\pL\d]+~u', '-', $text);
        
        // Başta ve sonda tire varsa sil
        $text = trim($text, '-');
        
        // Çift tireleri tek yap
        $text = preg_replace('~-+~', '-', $text);
        
        return $text;
    }
    
    // Sayfa için SEO bilgilerini getir
    public function getPageMeta($page_name) {
        $stmt = $this->db->prepare("SELECT * FROM seo_settings WHERE page_name = :page");
        $stmt->execute([':page' => $page_name]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // İlan için otomatik SEO oluştur
    public function generatePropertyMeta($property_id) {
        // İlan bilgilerini al
        $stmt = $this->db->prepare("
            SELECT p.*, ps.*, pi.image_path 
            FROM properties p 
            LEFT JOIN property_seo ps ON p.id = ps.property_id
            LEFT JOIN property_images pi ON p.id = pi.property_id AND pi.is_main = 1
            WHERE p.id = :id
        ");
        $stmt->execute([':id' => $property_id]);
        $property = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$property) return null;
        
        // Otomatik başlık oluştur
        if (empty($property['meta_title'])) {
            $title = $property['satis_durumu'] . " " . 
                     $property['oda_sayisi'] . " " . 
                     $property['konut_tipi'] . " - " . 
                     $property['ilce'] . ", " . $property['mahalle'] . " - " . 
                     number_format($property['fiyat'], 0, ',', '.') . " TL";
            $property['meta_title'] = mb_substr($title, 0, 160);
        }
        
        // Otomatik açıklama oluştur
        if (empty($property['meta_description'])) {
            $desc = $property['ilce'] . " " . $property['mahalle'] . " bölgesinde " . 
                    $property['satis_durumu'] . " " . $property['brut_metrekare'] . "m² " . 
                    $property['oda_sayisi'] . " " . $property['konut_tipi'] . ". " . 
                    "Fiyat: " . number_format($property['fiyat'], 0, ',', '.') . " TL. ";
            $property['meta_description'] = mb_substr($desc, 0, 300);
        }
        
        // Otomatik anahtar kelimeler
        if (empty($property['meta_keywords'])) {
            $keywords = [
                $property['satis_durumu'],
                $property['konut_tipi'],
                $property['ilce'] . " " . $property['satis_durumu'] . " " . $property['konut_tipi'],
                $property['mahalle'] . " emlak",
                $property['oda_sayisi'],
                "afyon " . $property['satis_durumu'] . " daire"
            ];
            $property['meta_keywords'] = implode(', ', $keywords);
        }
        
        // URL oluştur
        if (empty($property['slug'])) {
            $slug = $this->createSlug(
                $property['satis_durumu'] . "-" . 
                $property['konut_tipi'] . "-" . 
                $property['ilce'] . "-" . 
                $property['mahalle'] . "-" . 
                $property['id']
            );
            
            // Veritabanına kaydet
            $this->savePropertySlug($property_id, $slug);
            $property['slug'] = $slug;
        }
        
        return $property;
    }
    
    // Slug'ı kaydet
    private function savePropertySlug($property_id, $slug) {
        $stmt = $this->db->prepare("
            INSERT INTO property_seo (property_id, slug) 
            VALUES (:id, :slug) 
            ON DUPLICATE KEY UPDATE slug = :slug
        ");
        $stmt->execute([':id' => $property_id, ':slug' => $slug]);
    }
    
    // HTML meta taglarını oluştur
    public function renderMetaTags($meta) {
        $html = '';
        
        // Sayfa başlığı
        if (!empty($meta['meta_title'])) {
            $html .= '<title>' . htmlspecialchars($meta['meta_title']) . '</title>' . "\n";
        }
        
        // Sayfa açıklaması
        if (!empty($meta['meta_description'])) {
            $html .= '<meta name="description" content="' . htmlspecialchars($meta['meta_description']) . '">' . "\n";
        }
        
        // Anahtar kelimeler
        if (!empty($meta['meta_keywords'])) {
            $html .= '<meta name="keywords" content="' . htmlspecialchars($meta['meta_keywords']) . '">' . "\n";
        }
        
        // Facebook için
        if (!empty($meta['og_title']) || !empty($meta['meta_title'])) {
            $og_title = !empty($meta['og_title']) ? $meta['og_title'] : $meta['meta_title'];
            $html .= '<meta property="og:title" content="' . htmlspecialchars($og_title) . '">' . "\n";
        }
        
        if (!empty($meta['og_description']) || !empty($meta['meta_description'])) {
            $og_desc = !empty($meta['og_description']) ? $meta['og_description'] : $meta['meta_description'];
            $html .= '<meta property="og:description" content="' . htmlspecialchars($og_desc) . '">' . "\n";
        }
        
        // Diğer önemli taglar
        $html .= '<meta name="robots" content="index, follow">' . "\n";
        $html .= '<meta name="author" content="Plaza Emlak">' . "\n";
        $html .= '<meta name="viewport" content="width=device-width, initial-scale=1.0">' . "\n";
        
        return $html;
    }
}
?>