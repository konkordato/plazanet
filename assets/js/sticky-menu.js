// Sticky Menu JavaScript Kodu
// Bu dosya sadece ilan detay sayfasında çalışacak

document.addEventListener('DOMContentLoaded', function() {
    // Sadece detail.php sayfasında çalışsın
    if (!document.body.classList.contains('detail-page-custom')) {
        return; // Detail sayfası değilse çık
    }

    // Header elementini bul
    const header = document.querySelector('header');
    
    // Admin panelinde değilse devam et
    if (!header || window.location.href.includes('/admin/')) {
        return;
    }

    // Orijinal header yüksekliğini kaydet
    const headerHeight = header.offsetHeight;
    
    // Scroll pozisyonunu takip etmek için değişken
    let lastScrollTop = 0;
    
    // Scroll event listener
    window.addEventListener('scroll', function() {
        let scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        
        // 100 piksel aşağı kaydırıldığında aktif olsun
        if (scrollTop > 100) {
            // Aşağı scroll yapılıyorsa
            if (scrollTop > lastScrollTop) {
                // Header'ı yukarı gizle (yumuşak geçiş ile)
                header.style.transform = 'translateY(-100%)';
            } else {
                // Yukarı scroll yapılıyorsa
                // Header'ı göster ve sabitlenmiş yap
                header.classList.add('sticky-active');
                header.style.transform = 'translateY(0)';
            }
        } else {
            // En üste yakınsa normal haline döndür
            header.classList.remove('sticky-active');
            header.style.transform = 'translateY(0)';
        }
        
        lastScrollTop = scrollTop;
    });
});