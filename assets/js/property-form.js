<<<<<<< HEAD
// İlan Formu JavaScript İşlemleri

// İlçe elementi varsa event ekle (artık text input olduğu için bu kod çalışmayacak)
var ilceElement = document.getElementById('ilce');
if(ilceElement && ilceElement.tagName === 'SELECT') {
    ilceElement.addEventListener('change', function() {
        const ilce = this.value;
        const mahalleSelect = document.getElementById('mahalle');
        
        if(!ilce) {
            mahalleSelect.innerHTML = '<option value="">Önce ilçe seçin</option>';
            return;
        }
        
        // AJAX ile mahalleleri getir
        fetch('ajax/get-neighborhoods.php?ilce=' + encodeURIComponent(ilce))
            .then(response => response.text())
            .then(data => {
                mahalleSelect.innerHTML = data;
            })
            .catch(error => {
                console.error('Hata:', error);
                mahalleSelect.innerHTML = '<option value="">Mahalle yüklenemedi</option>';
            });
    });
}

// Çoklu resim yükleme ve önizleme
const photoInput = document.getElementById('photos');
const previewArea = document.getElementById('preview-area');
let selectedFiles = [];

if(photoInput) {
    photoInput.addEventListener('change', function(e) {
        handleFiles(e.target.files);
    });
}

// Sürükle-bırak desteği
const uploadArea = document.querySelector('.upload-area');
if(uploadArea) {
    uploadArea.addEventListener('dragover', function(e) {
        e.preventDefault();
        this.style.borderColor = '#3498db';
        this.style.background = '#f0f8ff';
    });
    
    uploadArea.addEventListener('dragleave', function(e) {
        e.preventDefault();
        this.style.borderColor = '#ddd';
        this.style.background = '#fafafa';
    });
    
    uploadArea.addEventListener('drop', function(e) {
        e.preventDefault();
        this.style.borderColor = '#ddd';
        this.style.background = '#fafafa';
        
        const files = e.dataTransfer.files;
        handleFiles(files);
    });
}

// Dosyaları işle
function handleFiles(files) {
    const maxFiles = 50;
    const maxSize = 10 * 1024 * 1024; // 10MB
    
    for(let i = 0; i < files.length; i++) {
        const file = files[i];
        
        // Dosya tipi kontrolü
        if(!file.type.startsWith('image/')) {
            alert(file.name + ' bir resim dosyası değil!');
            continue;
        }
        
        // Dosya boyutu kontrolü
        if(file.size > maxSize) {
            alert(file.name + ' dosyası 10MB\'dan büyük!');
            continue;
        }
        
        // Maksimum dosya sayısı kontrolü
        if(selectedFiles.length >= maxFiles) {
            alert('Maksimum 50 resim yükleyebilirsiniz!');
            break;
        }
        
        selectedFiles.push(file);
        previewImage(file, selectedFiles.length - 1);
    }
    
    updateFileCount();
}

// Resim önizleme
function previewImage(file, index) {
    const reader = new FileReader();
    
    reader.onload = function(e) {
        const div = document.createElement('div');
        div.className = 'preview-item';
        div.dataset.index = index;
        
        div.innerHTML = `
            <img src="${e.target.result}" alt="${file.name}">
            <button type="button" class="remove-btn" onclick="removeImage(${index})">×</button>
        `;
        
        previewArea.appendChild(div);
    };
    
    reader.readAsDataURL(file);
}

// Resmi kaldır
function removeImage(index) {
    selectedFiles.splice(index, 1);
    
    // Önizleme alanını yeniden oluştur
    previewArea.innerHTML = '';
    selectedFiles.forEach((file, i) => {
        previewImage(file, i);
    });
    
    updateFileCount();
}

// Dosya sayısını güncelle
function updateFileCount() {
    const uploadText = document.querySelector('.upload-text small');
    if(uploadText && selectedFiles.length > 0) {
        uploadText.textContent = `${selectedFiles.length} resim seçildi (Maksimum 50)`;
    }
}

// Form gönderilmeden önce
document.getElementById('detailForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Form verilerini topla
    const formData = new FormData(this);
    
    // Seçilen resimleri ekle
    selectedFiles.forEach((file, index) => {
        formData.append('photos[]', file);
    });
    
    // Minimum kontroller
    const baslik = formData.get('baslik');
    const fiyat = formData.get('fiyat');
    const il = formData.get('il');
    const ilce = formData.get('ilce');
    
    if(!baslik || !fiyat || !il || !ilce) {
        alert('Lütfen zorunlu alanları doldurun!');
        return;
    }
    
    // Formu gönder
    this.submit();
});

// Sayfa yüklendiğinde
document.addEventListener('DOMContentLoaded', function() {
    // Form alanlarını kontrol et
    const requiredFields = document.querySelectorAll('[required]');
    requiredFields.forEach(field => {
        field.addEventListener('blur', function() {
            if(this.value.trim() === '') {
                this.style.borderColor = '#e74c3c';
            } else {
                this.style.borderColor = '#ddd';
            }
        });
    });
    
    // Fiyat alanına sadece sayı girişi
    const priceInput = document.querySelector('input[name="fiyat"]');
    if(priceInput) {
        priceInput.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    }
    
    // Telefon formatı
    const phoneInput = document.querySelector('input[name="mulk_sahibi_tel"]');
    if(phoneInput) {
        phoneInput.addEventListener('input', function() {
            let value = this.value.replace(/[^0-9]/g, '');
            if(value.length > 11) value = value.slice(0, 11);
            
            // Format: 0XXX XXX XX XX
            if(value.length >= 4) {
                value = value.slice(0, 4) + ' ' + value.slice(4);
            }
            if(value.length >= 8) {
                value = value.slice(0, 8) + ' ' + value.slice(8);
            }
            if(value.length >= 11) {
                value = value.slice(0, 11) + ' ' + value.slice(11);
            }
            
            this.value = value;
        });
    }
=======
// İlan Formu JavaScript İşlemleri

// İlçe elementi varsa event ekle (artık text input olduğu için bu kod çalışmayacak)
var ilceElement = document.getElementById('ilce');
if(ilceElement && ilceElement.tagName === 'SELECT') {
    ilceElement.addEventListener('change', function() {
        const ilce = this.value;
        const mahalleSelect = document.getElementById('mahalle');
        
        if(!ilce) {
            mahalleSelect.innerHTML = '<option value="">Önce ilçe seçin</option>';
            return;
        }
        
        // AJAX ile mahalleleri getir
        fetch('ajax/get-neighborhoods.php?ilce=' + encodeURIComponent(ilce))
            .then(response => response.text())
            .then(data => {
                mahalleSelect.innerHTML = data;
            })
            .catch(error => {
                console.error('Hata:', error);
                mahalleSelect.innerHTML = '<option value="">Mahalle yüklenemedi</option>';
            });
    });
}

// Çoklu resim yükleme ve önizleme
const photoInput = document.getElementById('photos');
const previewArea = document.getElementById('preview-area');
let selectedFiles = [];

if(photoInput) {
    photoInput.addEventListener('change', function(e) {
        handleFiles(e.target.files);
    });
}

// Sürükle-bırak desteği
const uploadArea = document.querySelector('.upload-area');
if(uploadArea) {
    uploadArea.addEventListener('dragover', function(e) {
        e.preventDefault();
        this.style.borderColor = '#3498db';
        this.style.background = '#f0f8ff';
    });
    
    uploadArea.addEventListener('dragleave', function(e) {
        e.preventDefault();
        this.style.borderColor = '#ddd';
        this.style.background = '#fafafa';
    });
    
    uploadArea.addEventListener('drop', function(e) {
        e.preventDefault();
        this.style.borderColor = '#ddd';
        this.style.background = '#fafafa';
        
        const files = e.dataTransfer.files;
        handleFiles(files);
    });
}

// Dosyaları işle
function handleFiles(files) {
    const maxFiles = 50;
    const maxSize = 10 * 1024 * 1024; // 10MB
    
    for(let i = 0; i < files.length; i++) {
        const file = files[i];
        
        // Dosya tipi kontrolü
        if(!file.type.startsWith('image/')) {
            alert(file.name + ' bir resim dosyası değil!');
            continue;
        }
        
        // Dosya boyutu kontrolü
        if(file.size > maxSize) {
            alert(file.name + ' dosyası 10MB\'dan büyük!');
            continue;
        }
        
        // Maksimum dosya sayısı kontrolü
        if(selectedFiles.length >= maxFiles) {
            alert('Maksimum 50 resim yükleyebilirsiniz!');
            break;
        }
        
        selectedFiles.push(file);
        previewImage(file, selectedFiles.length - 1);
    }
    
    updateFileCount();
}

// Resim önizleme
function previewImage(file, index) {
    const reader = new FileReader();
    
    reader.onload = function(e) {
        const div = document.createElement('div');
        div.className = 'preview-item';
        div.dataset.index = index;
        
        div.innerHTML = `
            <img src="${e.target.result}" alt="${file.name}">
            <button type="button" class="remove-btn" onclick="removeImage(${index})">×</button>
        `;
        
        previewArea.appendChild(div);
    };
    
    reader.readAsDataURL(file);
}

// Resmi kaldır
function removeImage(index) {
    selectedFiles.splice(index, 1);
    
    // Önizleme alanını yeniden oluştur
    previewArea.innerHTML = '';
    selectedFiles.forEach((file, i) => {
        previewImage(file, i);
    });
    
    updateFileCount();
}

// Dosya sayısını güncelle
function updateFileCount() {
    const uploadText = document.querySelector('.upload-text small');
    if(uploadText && selectedFiles.length > 0) {
        uploadText.textContent = `${selectedFiles.length} resim seçildi (Maksimum 50)`;
    }
}

// Form gönderilmeden önce
document.getElementById('detailForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Form verilerini topla
    const formData = new FormData(this);
    
    // Seçilen resimleri ekle
    selectedFiles.forEach((file, index) => {
        formData.append('photos[]', file);
    });
    
    // Minimum kontroller
    const baslik = formData.get('baslik');
    const fiyat = formData.get('fiyat');
    const il = formData.get('il');
    const ilce = formData.get('ilce');
    
    if(!baslik || !fiyat || !il || !ilce) {
        alert('Lütfen zorunlu alanları doldurun!');
        return;
    }
    
    // Formu gönder
    this.submit();
});

// Sayfa yüklendiğinde
document.addEventListener('DOMContentLoaded', function() {
    // Form alanlarını kontrol et
    const requiredFields = document.querySelectorAll('[required]');
    requiredFields.forEach(field => {
        field.addEventListener('blur', function() {
            if(this.value.trim() === '') {
                this.style.borderColor = '#e74c3c';
            } else {
                this.style.borderColor = '#ddd';
            }
        });
    });
    
    // Fiyat alanına sadece sayı girişi
    const priceInput = document.querySelector('input[name="fiyat"]');
    if(priceInput) {
        priceInput.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    }
    
    // Telefon formatı
    const phoneInput = document.querySelector('input[name="mulk_sahibi_tel"]');
    if(phoneInput) {
        phoneInput.addEventListener('input', function() {
            let value = this.value.replace(/[^0-9]/g, '');
            if(value.length > 11) value = value.slice(0, 11);
            
            // Format: 0XXX XXX XX XX
            if(value.length >= 4) {
                value = value.slice(0, 4) + ' ' + value.slice(4);
            }
            if(value.length >= 8) {
                value = value.slice(0, 8) + ' ' + value.slice(8);
            }
            if(value.length >= 11) {
                value = value.slice(0, 11) + ' ' + value.slice(11);
            }
            
            this.value = value;
        });
    }
>>>>>>> 1b4657b0e4a9c21769cd70a9511bc87296025d5f
});