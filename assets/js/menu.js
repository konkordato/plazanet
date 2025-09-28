// assets/js/menu.js
document.addEventListener('DOMContentLoaded', function() {
    var toggleButton = document.querySelector('.mobile-menu-toggle');
    var navMenu = document.querySelector('.nav-menu');
    
    if(toggleButton && navMenu) {
        toggleButton.addEventListener('click', function() {
            this.classList.toggle('active');
            navMenu.classList.toggle('active');
        });
    }
});