<?php
// includes/analytics.php
// Google Analytics takip kodunu tüm sayfalara eklemek için kullanılır

// Google Analytics Measurement ID (G-XXXXXXXXXX formatında)
// Kendi Google Analytics ID'nizi buraya yazın
$google_analytics_id = "G-XXXXXXXXXX"; // BU KODU DEĞİŞTİRİN!

// Analytics kodunu döndüren fonksiyon
function getAnalyticsCode($tracking_id = null) {
    global $google_analytics_id;
    
    // Eğer parametre verilmemişse global ID'yi kullan
    if (!$tracking_id) {
        $tracking_id = $google_analytics_id;
    }
    
    // Eğer ID yoksa veya test ID'si ise boş döndür
    if (!$tracking_id || $tracking_id == "G-XXXXXXXXXX") {
        return "<!-- Google Analytics kodu henüz eklenmedi -->";
    }
    
    // Google Analytics 4 (GA4) kodu
    $analytics_code = "
    <!-- Google Analytics 4 -->
    <script async src=\"https://www.googletagmanager.com/gtag/js?id={$tracking_id}\"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', '{$tracking_id}');
    </script>
    <!-- /Google Analytics 4 -->";
    
    return $analytics_code;
}

// Enhanced Ecommerce için ürün görüntüleme eventi
function trackPropertyView($property_id, $property_name, $property_price, $property_category) {
    global $google_analytics_id;
    
    if (!$google_analytics_id || $google_analytics_id == "G-XXXXXXXXXX") {
        return "";
    }
    
    $event_code = "
    <script>
    // İlan görüntüleme eventi
    gtag('event', 'view_item', {
      currency: 'TRY',
      value: {$property_price},
      items: [{
        item_id: '{$property_id}',
        item_name: '{$property_name}',
        item_category: '{$property_category}',
        price: {$property_price},
        quantity: 1
      }]
    });
    </script>";
    
    return $event_code;
}

// Arama eventi
function trackSearch($search_term, $results_count) {
    global $google_analytics_id;
    
    if (!$google_analytics_id || $google_analytics_id == "G-XXXXXXXXXX") {
        return "";
    }
    
    $event_code = "
    <script>
    // Arama eventi
    gtag('event', 'search', {
      search_term: '{$search_term}',
      results_count: {$results_count}
    });
    </script>";
    
    return $event_code;
}

// İletişim form gönderimi eventi
function trackContactForm($property_id = null) {
    global $google_analytics_id;
    
    if (!$google_analytics_id || $google_analytics_id == "G-XXXXXXXXXX") {
        return "";
    }
    
    $event_code = "
    <script>
    // İletişim formu gönderimi
    gtag('event', 'generate_lead', {
      value: 1,
      currency: 'TRY'";
    
    if ($property_id) {
        $event_code .= ",
      property_id: '{$property_id}'";
    }
    
    $event_code .= "
    });
    </script>";
    
    return $event_code;
}

// WhatsApp tıklama eventi
function trackWhatsAppClick($property_id = null) {
    global $google_analytics_id;
    
    if (!$google_analytics_id || $google_analytics_id == "G-XXXXXXXXXX") {
        return "";
    }
    
    $event_code = "
    <script>
    // WhatsApp butonu tıklaması
    gtag('event', 'whatsapp_click', {
      event_category: 'engagement',
      event_label: 'property_inquiry'";
    
    if ($property_id) {
        $event_code .= ",
      property_id: '{$property_id}'";
    }
    
    $event_code .= "
    });
    </script>";
    
    return $event_code;
}
?>