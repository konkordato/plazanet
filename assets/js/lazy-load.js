// assets/js/lazy-load.js
// Resim lazy loading sistemi - sayfa hızını artırır

document.addEventListener("DOMContentLoaded", function() {
    // Tüm lazy-load sınıflı resimleri bul
    var lazyImages = [].slice.call(document.querySelectorAll("img.lazy"));
    var lazyBackgrounds = [].slice.call(document.querySelectorAll(".lazy-bg"));

    if ("IntersectionObserver" in window) {
        // Modern tarayıcılar için IntersectionObserver kullan
        let lazyImageObserver = new IntersectionObserver(function(entries, observer) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    let lazyImage = entry.target;
                    
                    // Resim elementi ise
                    if (lazyImage.tagName === 'IMG') {
                        lazyImage.src = lazyImage.dataset.src;
                        
                        // srcset varsa onu da yükle
                        if (lazyImage.dataset.srcset) {
                            lazyImage.srcset = lazyImage.dataset.srcset;
                        }
                        
                        lazyImage.classList.remove("lazy");
                        lazyImage.classList.add("lazy-loaded");
                        
                        // Yükleme animasyonu
                        lazyImage.style.opacity = "0";
                        setTimeout(function() {
                            lazyImage.style.transition = "opacity 0.3s";
                            lazyImage.style.opacity = "1";
                        }, 50);
                    }
                    // Arka plan resmi ise
                    else if (lazyImage.classList.contains('lazy-bg')) {
                        lazyImage.style.backgroundImage = 'url(' + lazyImage.dataset.bg + ')';
                        lazyImage.classList.remove("lazy-bg");
                        lazyImage.classList.add("lazy-bg-loaded");
                    }
                    
                    lazyImageObserver.unobserve(lazyImage);
                }
            });
        }, {
            // Resim görünmeden 100px önce yüklemeye başla
            rootMargin: "100px 0px",
            threshold: 0.01
        });

        // Her resmi gözlemle
        lazyImages.forEach(function(lazyImage) {
            lazyImageObserver.observe(lazyImage);
        });
        
        lazyBackgrounds.forEach(function(lazyBg) {
            lazyImageObserver.observe(lazyBg);
        });
    } else {
        // Eski tarayıcılar için fallback
        let lazyLoadThrottleTimeout;
        
        function lazyLoad() {
            if(lazyLoadThrottleTimeout) {
                clearTimeout(lazyLoadThrottleTimeout);
            }
            
            lazyLoadThrottleTimeout = setTimeout(function() {
                let scrollTop = window.pageYOffset;
                
                lazyImages.forEach(function(img) {
                    if(img.offsetTop < (window.innerHeight + scrollTop + 100)) {
                        img.src = img.dataset.src;
                        if (img.dataset.srcset) {
                            img.srcset = img.dataset.srcset;
                        }
                        img.classList.remove('lazy');
                        img.classList.add('lazy-loaded');
                    }
                });
                
                lazyBackgrounds.forEach(function(bg) {
                    if(bg.offsetTop < (window.innerHeight + scrollTop + 100)) {
                        bg.style.backgroundImage = 'url(' + bg.dataset.bg + ')';
                        bg.classList.remove('lazy-bg');
                        bg.classList.add('lazy-bg-loaded');
                    }
                });
                
                // Tüm resimler yüklendiyse event listener'ı kaldır
                if(lazyImages.length == 0 && lazyBackgrounds.length == 0) {
                    document.removeEventListener("scroll", lazyLoad);
                    window.removeEventListener("resize", lazyLoad);
                    window.removeEventListener("orientationChange", lazyLoad);
                }
            }, 20);
        }
        
        document.addEventListener("scroll", lazyLoad);
        window.addEventListener("resize", lazyLoad);
        window.addEventListener("orientationChange", lazyLoad);
        
        // İlk yükleme
        lazyLoad();
    }
});

// Resim önbellekleme fonksiyonu
function preloadImage(url) {
    var img = new Image();
    img.src = url;
}

// Kritik resimler için önbellekleme
function preloadCriticalImages() {
    var criticalImages = [
        '/assets/images/plaza-logo-buyuk.png',
        // Diğer önemli resimler
    ];
    
    criticalImages.forEach(function(url) {
        preloadImage(url);
    });
}

// Sayfa yüklendiğinde kritik resimleri önbelleğe al
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', preloadCriticalImages);
} else {
    preloadCriticalImages();
}