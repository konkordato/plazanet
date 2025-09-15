<?php
session_start();
if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../index.php");
    exit();
}

require_once '../../config/database.php';

// Önceki adımdan gelen veriler
$emlak_tipi = $_POST['emlak_tipi'] ?? $_SESSION['emlak_tipi'] ?? '';
$kategori = $_POST['kategori'] ?? $_SESSION['kategori'] ?? '';
$alt_kategori = $_POST['alt_kategori'] ?? $_SESSION['alt_kategori'] ?? '';

// Session'a kaydet
$_SESSION['emlak_tipi'] = $emlak_tipi;
$_SESSION['kategori'] = $kategori;
$_SESSION['alt_kategori'] = $alt_kategori;

// İller ve ilçeleri çek
$iller = $db->query("SELECT * FROM iller ORDER BY il_adi")->fetchAll(PDO::FETCH_ASSOC);
$ilceler = $db->query("SELECT * FROM ilceler WHERE il_id = 1 ORDER BY ilce_adi")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>İlan Detayları | Plaza Emlak</title>
    <link rel="stylesheet" href="../../assets/css/admin-form.css">
<style>
/* Fotoğraf önizleme stilleri */
.preview-area {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 15px;
    margin-top: 20px;
}

.preview-item {
    position: relative;
    border-radius: 8px;
    overflow: hidden;
    border: 2px solid #e0e0e0;
    background: #f5f5f5;
}

.preview-item img {
    width: 100%;
    height: 100px;
    object-fit: cover;
    display: block;
}

.preview-item .remove-btn {
    position: absolute;
    top: 5px;
    right: 5px;
    width: 25px;
    height: 25px;
    background: rgba(231, 76, 60, 0.9);
    color: white;
    border: none;
    border-radius: 50%;
    cursor: pointer;
    font-size: 18px;
    line-height: 1;
    display: flex;
    align-items: center;
    justify-content: center;
}

.preview-item .remove-btn:hover {
    background: #c0392b;
}

.preview-item .photo-number {
    position: absolute;
    bottom: 5px;
    left: 5px;
    background: rgba(0, 0, 0, 0.7);
    color: white;
    padding: 2px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: bold;
}

.upload-area {
    transition: all 0.3s;
}
</style>
</head>
<body>
    <div class="header">
        <div class="container">
            <div class="logo">
                <img src="../../assets/images/plaza-logo.png" alt="Plaza">
                <span>İlan Ekle</span>
            </div>
        </div>
    </div>

    <!-- Adımlar -->
    <div class="steps">
        <div class="container">
            <div class="steps-wrapper">
                <div class="step completed">
                    <div class="step-circle">✓</div>
                    <div class="step-title">Kategori Seçimi</div>
                </div>
                <div class="step-line active"></div>
                <div class="step active">
                    <div class="step-circle">2</div>
                    <div class="step-title">İlan Detayları</div>
                </div>
                <div class="step-line"></div>
                <div class="step">
                    <div class="step-circle">3</div>
                    <div class="step-title">Önizleme</div>
                </div>
                <div class="step-line"></div>
                <div class="step">
                    <div class="step-circle">4</div>
                    <div class="step-title">Doping</div>
                </div>
                <div class="step-line"></div>
                <div class="step">
                    <div class="step-circle">5</div>
                    <div class="step-title">Tebrikler</div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="content">
            <div class="breadcrumb">
                Emlak > <?php echo ucfirst($emlak_tipi); ?> > <?php echo ucfirst($kategori); ?> 
                <?php if($alt_kategori): ?>> <?php echo ucfirst($alt_kategori); ?><?php endif; ?>
            </div>

            <h1 class="page-title">İlan Detayları</h1>
            
            <form id="detailForm" method="POST" action="add-step3.php" enctype="multipart/form-data">
                <!-- TEMEL BİLGİLER -->
                <div class="form-section">
                    <h2 class="section-title">Temel Bilgiler</h2>
                    
                    <div class="form-group">
                        <label class="required">İlan Başlığı</label>
                        <input type="text" name="baslik" maxlength="100" required 
                               placeholder="Örn: Plaza'dan Merkez'de 3+1 Kiralık Daire">
                        <small>Maksimum 100 karakter</small>
                    </div>

                    <div class="form-group">
                        <label class="required">Açıklama</label>
                        <textarea name="aciklama" rows="8" required 
                                  placeholder="İlanınız hakkında detaylı bilgi verin..."></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="required">Fiyat</label>
                            <input type="number" name="fiyat" required placeholder="0">
                        </div>
                        <div class="form-group">
                            <label>Para Birimi</label>
                            <select name="para_birimi">
                                <option value="TL">TL</option>
                                <option value="USD">USD</option>
                                <option value="EUR">EUR</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="required">Brüt m²</label>
                            <input type="number" name="brut_metrekare" required>
                        </div>
                        <div class="form-group">
                            <label>Net m²</label>
                            <input type="number" name="net_metrekare">
                        </div>
                        <div class="form-group">
                            <label class="required">Oda Sayısı</label>
                            <select name="oda_sayisi" required>
                                <option value="">Seçiniz</option>
                                <option value="1+0">1+0</option>
                                <option value="1+1">1+1</option>
                                <option value="2+1">2+1</option>
                                <option value="3+1">3+1</option>
                                <option value="4+1">4+1</option>
                                <option value="5+1">5+1</option>
                                <option value="6+1">6+1</option>
                                <option value="7+1">7+1</option>
                                <option value="8+1">8+1</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Bina Yaşı</label>
                            <select name="bina_yasi">
                                <option value="">Seçiniz</option>
                                <option value="0">0 (Yeni)</option>
                                <option value="1">1</option>
                                <option value="2">2</option>
                                <option value="3-5">3-5</option>
                                <option value="6-10">6-10</option>
                                <option value="11-15">11-15</option>
                                <option value="16-20">16-20</option>
                                <option value="21-25">21-25</option>
                                <option value="26-30">26-30</option>
                                <option value="31+">31 ve üzeri</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Bulunduğu Kat</label>
                            <select name="bulundugu_kat">
                                <option value="">Seçiniz</option>
                                <option value="Bodrum">Bodrum</option>
                                <option value="Zemin">Zemin</option>
                                <option value="Bahçe Katı">Bahçe Katı</option>
                                <option value="Giriş Katı">Giriş Katı</option>
                                <option value="Yüksek Giriş">Yüksek Giriş</option>
                                <?php for($i=1; $i<=30; $i++): ?>
                                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                <?php endfor; ?>
                                <option value="31+">31 ve üzeri</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Kat Sayısı</label>
                            <select name="kat_sayisi">
                                <option value="">Seçiniz</option>
                                <?php for($i=1; $i<=30; $i++): ?>
                                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                <?php endfor; ?>
                                <option value="31+">31 ve üzeri</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Isıtma</label>
                            <select name="isitma">
                                <option value="">Seçiniz</option>
                                <option value="Yok">Yok</option>
                                <option value="Soba">Soba</option>
                                <option value="Doğalgaz Sobası">Doğalgaz Sobası</option>
                                <option value="Kat Kaloriferi">Kat Kaloriferi</option>
                                <option value="Merkezi">Merkezi</option>
                                <option value="Merkezi (Pay Ölçer)">Merkezi (Pay Ölçer)</option>
                                <option value="Kombi (Doğalgaz)">Kombi (Doğalgaz)</option>
                                <option value="Kombi (Elektrik)">Kombi (Elektrik)</option>
                                <option value="Yerden Isıtma">Yerden Isıtma</option>
                                <option value="Klima">Klima</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Banyo Sayısı</label>
                            <select name="banyo_sayisi">
                                <option value="">Seçiniz</option>
                                <option value="0">Yok</option>
                                <option value="1">1</option>
                                <option value="2">2</option>
                                <option value="3">3</option>
                                <option value="4">4+</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Balkon</label>
                            <select name="balkon">
                                <option value="">Seçiniz</option>
                                <option value="Var">Var</option>
                                <option value="Yok">Yok</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Eşyalı</label>
                            <select name="esyali">
                                <option value="Hayır">Hayır</option>
                                <option value="Evet">Evet</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Kullanım Durumu</label>
                            <select name="kullanim_durumu">
                                <option value="Boş">Boş</option>
                                <option value="Kiracılı">Kiracılı</option>
                                <option value="Mülk Sahibi">Mülk Sahibi</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Site İçerisinde</label>
                            <select name="site_icerisinde">
                                <option value="Hayır">Hayır</option>
                                <option value="Evet">Evet</option>
                            </select>
                        </div>
                    </div>

                    <?php if($kategori == 'kiralik'): ?>
                    <div class="form-group">
                        <label>Aidat (TL)</label>
                        <input type="number" name="aidat" placeholder="0">
                    </div>
                    <?php endif; ?>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Krediye Uygun</label>
                            <input type="checkbox" name="krediye_uygun" value="Evet">
                        </div>
                        <div class="form-group">
                            <label>Takas</label>
                            <input type="checkbox" name="takas" value="Evet">
                        </div>
                    </div>
                </div>

                <!-- ADRES BİLGİLERİ -->
                <div class="form-section">
                    <h2 class="section-title">Adres Bilgileri</h2>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="required">İl</label>
                            <select name="il" id="il" required>
                                <option value="Afyonkarahisar">Afyonkarahisar</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="required">İlçe</label>
                            <select name="ilce" id="ilce" required>
                                <option value="">Seçiniz</option>
                                <?php foreach($ilceler as $ilce): ?>
                                    <option value="<?php echo $ilce['ilce_adi']; ?>">
                                        <?php echo $ilce['ilce_adi']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Mahalle</label>
                            <select name="mahalle" id="mahalle">
                                <option value="">Önce ilçe seçin</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Açık Adres</label>
                        <textarea name="adres" rows="3" placeholder="Cadde, sokak, bina no vb."></textarea>
                    </div>
                </div>

                <!-- DANIŞMAN ÖZEL ALANLARI -->
                <div class="form-section special">
                    <h2 class="section-title">🔒 Danışman Bilgileri (Sadece Siz Görebilirsiniz)</h2>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Anahtar Numarası</label>
                            <input type="text" name="anahtar_no" 
                                   placeholder="Örn: A-125, K-44">
                            <small>Ofisteki anahtar kodu</small>
                        </div>
                        <div class="form-group">
                            <label>Mülk Sahibi Telefonu</label>
                            <input type="tel" name="mulk_sahibi_tel" 
                                   placeholder="05XX XXX XX XX">
                            <small>Sadece danışmanlar görebilir</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Danışman Notu</label>
                        <textarea name="danisman_notu" rows="3" 
                                  placeholder="Özel notlarınız, dikkat edilmesi gerekenler vb."></textarea>
                    </div>
                </div>

                <!-- FOTOĞRAFLAR (İsteğe Bağlı) -->
                <div class="form-section">
                    <h2 class="section-title">Fotoğraflar (İsteğe Bağlı)</h2>
                    <p style="color: #666; margin-bottom: 15px;">Fotoğrafları daha sonra da ekleyebilirsiniz.</p>
                    <div class="photo-upload">
                        <input type="file" name="photos[]" id="photos" 
                               multiple accept="image/*" style="display:none">
                        <div class="upload-area" onclick="document.getElementById('photos').click()">
                            <div class="upload-icon">📷</div>
                            <div class="upload-text">
                                <p><strong>Fotoğraf eklemek için tıklayın</strong></p>
                                <p>veya sürükle bırak</p>
                                <small>Maksimum 50 fotoğraf, her biri en fazla 10MB</small>
                            </div>
                        </div>
                        <div id="preview-area" class="preview-area"></div>
                    </div>
                </div>

                <!-- Gizli alanlar -->
                <input type="hidden" name="emlak_tipi" value="<?php echo $emlak_tipi; ?>">
                <input type="hidden" name="kategori" value="<?php echo $kategori; ?>">
                <input type="hidden" name="alt_kategori" value="<?php echo $alt_kategori; ?>">

                <!-- Butonlar -->
                <div class="buttons">
                    <button type="button" class="btn btn-back" onclick="history.back()">
                        ← Geri
                    </button>
                    <button type="submit" class="btn btn-next">
                        Devam → 
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    // İlçe değiştiğinde mahalle getir
    document.getElementById('ilce').addEventListener('change', function() {
        const ilce = this.value;
        const mahalleSelect = document.getElementById('mahalle');
        
        if(!ilce) {
            mahalleSelect.innerHTML = '<option value="">Önce ilçe seçin</option>';
            return;
        }
        
        mahalleSelect.innerHTML = '<option value="">Yükleniyor...</option>';
        
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
    
    // Çoklu resim yükleme
    let selectedFiles = [];
    const photoInput = document.getElementById('photos');
    const previewArea = document.getElementById('preview-area');
    
    // Dosya seçildiğinde
    photoInput.addEventListener('change', function(e) {
        handleFiles(e.target.files);
    });
    
    // Sürükle bırak
    const uploadArea = document.querySelector('.upload-area');
    
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
        handleFiles(e.dataTransfer.files);
    });
    
    function handleFiles(files) {
        for(let i = 0; i < files.length; i++) {
            if(files[i].type.startsWith('image/')) {
                if(selectedFiles.length < 50) {
                    selectedFiles.push(files[i]);
                    previewImage(files[i], selectedFiles.length - 1);
                }
            }
        }
        updatePhotoInput();
    }
    
    function previewImage(file, index) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const div = document.createElement('div');
            div.className = 'preview-item';
            div.innerHTML = `
                <img src="${e.target.result}" alt="">
                <button type="button" class="remove-btn" onclick="removeImage(${index})">×</button>
            `;
            previewArea.appendChild(div);
        };
        reader.readAsDataURL(file);
    }
    
    function removeImage(index) {
        selectedFiles.splice(index, 1);
        updatePreview();
        updatePhotoInput();
    }
    
    function updatePreview() {
        previewArea.innerHTML = '';
        selectedFiles.forEach((file, index) => {
            previewImage(file, index);
        });
    }
    
    function updatePhotoInput() {
        const dataTransfer = new DataTransfer();
        selectedFiles.forEach(file => {
            dataTransfer.items.add(file);
        });
        photoInput.files = dataTransfer.files;
    }
    
    // Form gönderilmeden önce kontrol
    document.getElementById('detailForm').addEventListener('submit', function(e) {
        if(selectedFiles.length === 0) {
            if(!confirm('Fotoğraf eklemeden devam etmek istiyor musunuz?')) {
                e.preventDefault();
            }
        }
    });
    // Fotoğraf yükleme sistemi
let selectedPhotos = [];
const maxPhotos = 50;
const maxFileSize = 10 * 1024 * 1024; // 10MB

// Dosya seçildiğinde
document.getElementById('photos').addEventListener('change', function(e) {
    handlePhotoSelection(e.target.files);
});

// Sürükle bırak
const uploadArea = document.querySelector('.upload-area');

uploadArea.addEventListener('dragover', function(e) {
    e.preventDefault();
    this.style.borderColor = '#3498db';
    this.style.background = '#e3f2fd';
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
    handlePhotoSelection(e.dataTransfer.files);
});

// Fotoğraf seçim işlemi
function handlePhotoSelection(files) {
    const previewArea = document.getElementById('preview-area');
    
    for (let i = 0; i < files.length; i++) {
        const file = files[i];
        
        // Kontroller
        if (!file.type.startsWith('image/')) {
            alert(file.name + ' bir resim dosyası değil!');
            continue;
        }
        
        if (file.size > maxFileSize) {
            alert(file.name + ' dosyası 10MB\'dan büyük!');
            continue;
        }
        
        if (selectedPhotos.length >= maxPhotos) {
            alert('Maksimum 50 fotoğraf yükleyebilirsiniz!');
            break;
        }
        
        // Listeye ekle
        selectedPhotos.push(file);
        
        // Önizleme göster
        const reader = new FileReader();
        reader.onload = function(e) {
            const div = document.createElement('div');
            div.className = 'preview-item';
            div.setAttribute('data-index', selectedPhotos.length - 1);
            
            div.innerHTML = `
                <img src="${e.target.result}" alt="">
                <button type="button" class="remove-btn" onclick="removePhoto(${selectedPhotos.length - 1})">×</button>
                <div class="photo-number">${selectedPhotos.length}</div>
            `;
            
            previewArea.appendChild(div);
        };
        reader.readAsDataURL(file);
    }
    
    updatePhotoCount();
}

// Fotoğraf silme
function removePhoto(index) {
    selectedPhotos.splice(index, 1);
    refreshPreview();
}

// Önizlemeyi yenile
function refreshPreview() {
    const previewArea = document.getElementById('preview-area');
    previewArea.innerHTML = '';
    
    selectedPhotos.forEach((file, index) => {
        const reader = new FileReader();
        reader.onload = function(e) {
            const div = document.createElement('div');
            div.className = 'preview-item';
            
            div.innerHTML = `
                <img src="${e.target.result}" alt="">
                <button type="button" class="remove-btn" onclick="removePhoto(${index})">×</button>
                <div class="photo-number">${index + 1}</div>
            `;
            
            previewArea.appendChild(div);
        };
        reader.readAsDataURL(file);
    });
    
    updatePhotoCount();
}

// Sayaç güncelle
function updatePhotoCount() {
    const uploadText = document.querySelector('.upload-text small');
    if (uploadText && selectedPhotos.length > 0) {
        uploadText.textContent = `${selectedPhotos.length} fotoğraf seçildi (Maksimum 50)`;
    }
}

// Form gönderilmeden önce
document.getElementById('detailForm').addEventListener('submit', function(e) {
    // DataTransfer ile dosyaları input'a aktar
    const dataTransfer = new DataTransfer();
    selectedPhotos.forEach(file => {
        dataTransfer.items.add(file);
    });
    document.getElementById('photos').files = dataTransfer.files;
});
    </script>
</body>
</html>