// Plaza Emlak - Ana JavaScript Dosyası

// Sayfa yüklendiğinde
document.addEventListener("DOMContentLoaded", function () {
  // Mobil menü toggle
  const mobileToggle = document.querySelector(".mobile-menu-toggle");
  const navMenu = document.querySelector(".nav-menu");

  if (mobileToggle) {
    mobileToggle.addEventListener("click", function () {
      navMenu.classList.toggle("active");
      this.classList.toggle("active");
    });
  }

  // Smooth scroll için
  document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
    anchor.addEventListener("click", function (e) {
      e.preventDefault();
      const target = document.querySelector(this.getAttribute("href"));
      if (target) {
        target.scrollIntoView({
          behavior: "smooth",
          block: "start",
        });
      }
    });
  });

  // Navbar scroll efekti
  let lastScroll = 0;
  window.addEventListener("scroll", function () {
    const navbar = document.querySelector("header");
    const currentScroll = window.pageYOffset;

    if (currentScroll > 100) {
      navbar.style.boxShadow = "0 4px 6px -1px rgba(0,0,0,0.1)";
    } else {
      navbar.style.boxShadow = "0 2px 4px -1px rgba(0,0,0,0.1)";
    }

    lastScroll = currentScroll;
  });
});

// Form validasyon
function validateSearchForm() {
  const form = document.querySelector(".search-box form");
  if (form) {
    form.addEventListener("submit", function (e) {
      const inputs = form.querySelectorAll("input, select");
      let hasValue = false;

      inputs.forEach((input) => {
        if (input.value.trim() !== "") {
          hasValue = true;
        }
      });

      if (!hasValue) {
        e.preventDefault();
        alert("Lütfen en az bir arama kriteri girin.");
      }
    });
  }
}

validateSearchForm();
// ========== LIGHTBOX (FOTOĞRAF BÜYÜTME) SİSTEMİ ==========
// Bu kodları main.js dosyasının EN SONUNA ekleyin

// Global değişkenler
let currentImageIndex = 0;
let galleryImages = [];

// Lightbox HTML yapısını oluştur
function createLightbox() {
  // Eğer zaten varsa tekrar oluşturma
  if (document.querySelector(".lightbox-overlay")) {
    return;
  }

  const lightboxHTML = `
        <div class="lightbox-overlay" id="lightbox">
            <button class="lightbox-close" onclick="closeLightbox()">×</button>
            <button class="lightbox-prev" onclick="previousImage()">❮</button>
            <button class="lightbox-next" onclick="nextImage()">❯</button>
            <div class="lightbox-container">
                <img src="" alt="" class="lightbox-image" id="lightbox-image">
            </div>
            <div class="lightbox-counter">
                <span id="current-image">1</span> / <span id="total-images">1</span>
            </div>
        </div>
    `;

  document.body.insertAdjacentHTML("beforeend", lightboxHTML);
}

// Lightbox'ı aç
function openLightbox(imageSrc, index) {
  createLightbox();

  const lightbox = document.getElementById("lightbox");
  const lightboxImage = document.getElementById("lightbox-image");

  currentImageIndex = index || 0;
  lightboxImage.src = imageSrc;
  lightbox.classList.add("active");

  // Sayacı güncelle
  updateCounter();

  // ESC tuşu ile kapatma
  document.addEventListener("keydown", handleKeyPress);

  // Body scroll'u kapat
  document.body.style.overflow = "hidden";
}

// Lightbox'ı kapat
function closeLightbox() {
  const lightbox = document.getElementById("lightbox");
  if (lightbox) {
    lightbox.classList.remove("active");
    document.removeEventListener("keydown", handleKeyPress);
    document.body.style.overflow = "";
  }
}

// Önceki resim
function previousImage() {
  if (galleryImages.length > 0) {
    currentImageIndex =
      (currentImageIndex - 1 + galleryImages.length) % galleryImages.length;
    document.getElementById("lightbox-image").src =
      galleryImages[currentImageIndex];
    updateCounter();
  }
}

// Sonraki resim
function nextImage() {
  if (galleryImages.length > 0) {
    currentImageIndex = (currentImageIndex + 1) % galleryImages.length;
    document.getElementById("lightbox-image").src =
      galleryImages[currentImageIndex];
    updateCounter();
  }
}

// Sayacı güncelle
function updateCounter() {
  const currentSpan = document.getElementById("current-image");
  const totalSpan = document.getElementById("total-images");

  if (currentSpan && totalSpan) {
    currentSpan.textContent = currentImageIndex + 1;
    totalSpan.textContent = galleryImages.length;
  }
}

// Klavye kontrolleri
function handleKeyPress(e) {
  if (e.key === "Escape") {
    closeLightbox();
  } else if (e.key === "ArrowLeft") {
    previousImage();
  } else if (e.key === "ArrowRight") {
    nextImage();
  }
}

// Sayfa yüklendiğinde galeriyi hazırla
document.addEventListener("DOMContentLoaded", function () {
  // Detail sayfasındaki ana resme tıklama
  const mainImage = document.querySelector(".gallery-main");
  if (mainImage) {
    mainImage.addEventListener("click", function () {
      // Tüm galeri resimlerini topla
      galleryImages = [];

      // Ana resmi ekle
      galleryImages.push(this.src);

      // Küçük resimleri ekle
      const thumbs = document.querySelectorAll(".gallery-thumb");
      thumbs.forEach((thumb) => {
        // data-full-src varsa onu kullan, yoksa src'yi kullan
        const fullSrc = thumb.dataset.fullSrc || thumb.src;
        if (!galleryImages.includes(fullSrc)) {
          galleryImages.push(fullSrc);
        }
      });

      openLightbox(this.src, 0);
    });
  }

  // Küçük resimlere tıklama
  const thumbImages = document.querySelectorAll(".gallery-thumb");
  thumbImages.forEach((thumb, index) => {
    thumb.addEventListener("click", function (e) {
      // Varsayılan tıklama davranışını durdur
      e.stopPropagation();

      // Tüm resimleri topla
      galleryImages = [];
      const mainImg = document.querySelector(".gallery-main");
      if (mainImg) {
        galleryImages.push(mainImg.src);
      }

      thumbImages.forEach((t) => {
        const fullSrc = t.dataset.fullSrc || t.src;
        if (!galleryImages.includes(fullSrc)) {
          galleryImages.push(fullSrc);
        }
      });

      // Bu resmin indexini bul
      const fullSrc = this.dataset.fullSrc || this.src;
      const realIndex = galleryImages.indexOf(fullSrc);

      openLightbox(fullSrc, realIndex >= 0 ? realIndex : 0);
    });
  });

  // Overlay'e tıklayınca kapat
  document.addEventListener("click", function (e) {
    if (e.target.classList.contains("lightbox-overlay")) {
      closeLightbox();
    }
  });
});

// Admin panel ve diğer sayfalardaki resimler için
document.addEventListener("click", function (e) {
  // property-thumb sınıfına sahip resimlere tıklama
  if (e.target.classList.contains("property-thumb")) {
    openLightbox(e.target.src, 0);
    galleryImages = [e.target.src];
  }

  // property-image içindeki img elementlerine tıklama
  if (e.target.tagName === "IMG" && e.target.closest(".property-image")) {
    openLightbox(e.target.src, 0);
    galleryImages = [e.target.src];
  }
});
