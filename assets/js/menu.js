<<<<<<< HEAD
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
=======
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
>>>>>>> 1b4657b0e4a9c21769cd70a9511bc87296025d5f
});