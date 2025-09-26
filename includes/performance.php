<?php
// includes/performance.php
// Sayfa hızı optimizasyon fonksiyonları

class PerformanceHelper {
    
    // CSS minify fonksiyonu
    public static function minifyCSS($css) {
        // Yorumları kaldır
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        // Gereksiz boşlukları kaldır
        $css = str_replace(["\r\n", "\r", "\n", "\t", '  ', '    ', '     '], '', $css);
        // Son temizlik
        $css = preg_replace(['(( )+{)', '({( )+)'], '{', $css);
        $css = preg_replace(['(( )+})', '(}( )+)', '(;( )*})'], '}', $css);
        $css = preg_replace(['(;( )+)', '(( )+;)'], ';', $css);
        
        return $css;
    }
    
    // JavaScript minify fonksiyonu
    public static function minifyJS($js) {
        // Basit minify (production'da daha gelişmiş araç kullanın)
        $js = preg_replace('/(?:(?:\/\*(?:[^*]|(?:\*+[^*\/]))*\*+\/)|(?:\/\/.*))/', '', $js);
        $js = str_replace(["\r\n", "\r", "\t", "\n", '  ', '    ', '     '], '', $js);
        
        return $js;
    }
    
    // HTML minify fonksiyonu
    public static function minifyHTML($html) {
        $search = [
            '/\>[^\S ]+/s',     // strip whitespaces after tags
            '/[^\S ]+\</s',     // strip whitespaces before tags
            '/(\s)+/s',         // shorten multiple whitespace sequences
            '/<!--(.|\s)*?-->/' // Remove HTML comments
        ];
        
        $replace = [
            '>',
            '<',
            '\\1',
            ''
        ];
        
        $html = preg_replace($search, $replace, $html);
        
        return $html;
    }
    
    // Resim WebP dönüştürme kontrolü
    public static function getWebPImage($imagePath) {
        $webpPath = str_replace(['.jpg', '.jpeg', '.png'], '.webp', $imagePath);
        
        // WebP dosyası varsa onu döndür
        if (file_exists($webpPath)) {
            return $webpPath;
        }
        
        // WebP yoksa orijinal resmi döndür
        return $imagePath;
    }
    
    // Lazy loading için img tag dönüştürücü
    public static function lazyLoadImage($src, $alt = '', $class = '', $width = '', $height = '') {
        // Placeholder resim (1x1 transparent pixel)
        $placeholder = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . ($width ?: '100') . ' ' . ($height ?: '100') . '"%3E%3C/svg%3E';
        
        $html = '<img ';
        $html .= 'src="' . $placeholder . '" ';
        $html .= 'data-src="' . $src . '" ';
        $html .= 'alt="' . htmlspecialchars($alt) . '" ';
        $html .= 'class="lazy ' . $class . '" ';
        
        if ($width) {
            $html .= 'width="' . $width . '" ';
        }
        if ($height) {
            $html .= 'height="' . $height . '" ';
        }
        
        $html .= 'loading="lazy">';
        
        return $html;
    }
    
    // Kritik CSS inline etme
    public static function getCriticalCSS() {
        $criticalCSS = '
        /* Kritik CSS - İlk yükleme için gerekli stiller */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }
        
        /* Header temel stiller */
        header {
            background: #fff;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            position: relative;
            z-index: 100;
        }
        
        .navbar {
            padding: 1rem 0;
        }
        
        /* Lazy loading placeholder */
        img.lazy {
            background: #f0f0f0;
            background-image: linear-gradient(90deg, #f0f0f0 0px, #f8f8f8 40px, #f0f0f0 80px);
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite;
        }
        
        @keyframes shimmer {
            0% {
                background-position: -100% 0;
            }
            100% {
                background-position: 100% 0;
            }
        }
        
        /* Web font yüklenene kadar font görünümü */
        .font-loading {
            font-family: Arial, sans-serif !important;
        }
        ';
        
        return self::minifyCSS($criticalCSS);
    }
    
    // Browser cache headers
    public static function setCacheHeaders($type = 'default', $duration = 3600) {
        switch($type) {
            case 'static':
                // Statik dosyalar için uzun cache
                header('Cache-Control: public, max-age=31536000, immutable');
                header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');
                break;
                
            case 'image':
                // Resimler için cache
                header('Cache-Control: public, max-age=2592000');
                header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 2592000) . ' GMT');
                break;
                
            case 'dynamic':
                // Dinamik içerik için kısa cache
                header('Cache-Control: public, max-age=' . $duration);
                header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $duration) . ' GMT');
                break;
                
            default:
                // Varsayılan cache
                header('Cache-Control: public, max-age=3600');
                header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT');
        }
        
        // ETag header
        header('ETag: "' . md5_file($_SERVER['SCRIPT_FILENAME']) . '"');
    }
    
    // Preload kritik kaynaklar
    public static function getPreloadTags() {
        $preloads = [
            '<link rel="preload" href="/assets/css/style.css" as="style">',
            '<link rel="preload" href="/assets/js/main.js" as="script">',
            '<link rel="preload" href="/assets/images/plaza-logo-buyuk.png" as="image">',
            '<link rel="preconnect" href="https://fonts.googleapis.com">',
            '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>',
            '<link rel="dns-prefetch" href="https://www.googletagmanager.com">',
        ];
        
        return implode("\n", $preloads);
    }
    
    // Gzip sıkıştırma kontrolü
    public static function enableGzip() {
        if (!ob_start("ob_gzhandler")) {
            ob_start();
        }
    }
}
?>