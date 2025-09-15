// Plaza Emlak - Ana JavaScript Dosyası

// Sayfa yüklendiğinde
document.addEventListener('DOMContentLoaded', function() {
    
    // Mobil menü toggle
    const mobileToggle = document.querySelector('.mobile-menu-toggle');
    const navMenu = document.querySelector('.nav-menu');
    
    if(mobileToggle) {
        mobileToggle.addEventListener('click', function() {
            navMenu.classList.toggle('active');
            this.classList.toggle('active');
        });
    }
    
    // Smooth scroll için
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if(target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
    
    // Navbar scroll efekti
    let lastScroll = 0;
    window.addEventListener('scroll', function() {
        const navbar = document.querySelector('header');
        const currentScroll = window.pageYOffset;
        
        if(currentScroll > 100) {
            navbar.style.boxShadow = '0 4px 6px -1px rgba(0,0,0,0.1)';
        } else {
            navbar.style.boxShadow = '0 2px 4px -1px rgba(0,0,0,0.1)';
        }
        
        lastScroll = currentScroll;
    });
    
});

// Form validasyon
function validateSearchForm() {
    const form = document.querySelector('.search-box form');
    if(form) {
        form.addEventListener('submit', function(e) {
            const inputs = form.querySelectorAll('input, select');
            let hasValue = false;
            
            inputs.forEach(input => {
                if(input.value.trim() !== '') {
                    hasValue = true;
                }
            });
            
            if(!hasValue) {
                e.preventDefault();
                alert('Lütfen en az bir arama kriteri girin.');
            }
        });
    }
}

validateSearchForm();