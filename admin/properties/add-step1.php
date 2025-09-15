<?php
// add-step1.php dosyasının en başına ekleyin:
   error_reporting(E_ALL);
   ini_set('display_errors', 1);
session_start();
if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../index.php");
    exit();
}

require_once '../../config/database.php';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>İlan Ekle - Kategori Seçimi | Plaza Emlak</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f5f5f5;
        }
        .header {
            background: #fff;
            border-bottom: 1px solid #e0e0e0;
            padding: 15px 0;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .logo img { height: 40px; }
        .logo span { font-size: 24px; font-weight: bold; color: #333; }
        
        /* Adım göstergesi */
        .steps {
            background: #fff;
            padding: 30px 0;
            margin-bottom: 20px;
        }
        .steps-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
        }
        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            z-index: 2;
        }
        .step-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e0e0e0;
            color: #666;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .step.active .step-circle {
            background: #2ecc71;
            color: white;
        }
        .step-title {
            font-size: 14px;
            color: #666;
        }
        .step.active .step-title {
            color: #333;
            font-weight: 500;
        }
        .step-line {
            position: absolute;
            top: 20px;
            width: 200px;
            height: 2px;
            background: #e0e0e0;
            z-index: 1;
        }
        
        /* İçerik alanı */
        .content {
            background: #fff;
            border-radius: 8px;
            padding: 30px;
            min-height: 400px;
        }
        .page-title {
            font-size: 24px;
            color: #333;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .page-subtitle {
            color: #666;
            margin-bottom: 30px;
        }
        
        /* Kategori seçimi */
        .category-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        .category-box {
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s;
            background: #fff;
        }
        .category-box:hover {
            border-color: #3498db;
            background: #f8f9fa;
        }
        .category-box.selected {
            border-color: #2ecc71;
            background: #e8f5e9;
        }
        .category-title {
            font-size: 16px;
            font-weight: 500;
            color: #333;
            margin-bottom: 10px;
        }
        .category-list {
            list-style: none;
        }
        .category-list li {
            padding: 8px 0;
            color: #666;
            cursor: pointer;
            transition: color 0.2s;
        }
        .category-list li:hover {
            color: #3498db;
        }
        .category-list li.active {
            color: #2ecc71;
            font-weight: 500;
        }
        
        /* Alt kategori */
        .subcategory-section {
            display: none;
            margin-top: 20px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .subcategory-section.show {
            display: block;
        }
        .subcategory-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
        }
        .subcategory-item {
            padding: 12px;
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 5px;
            cursor: pointer;
            text-align: center;
            transition: all 0.2s;
        }
        .subcategory-item:hover {
            border-color: #3498db;
            background: #e3f2fd;
        }
        .subcategory-item.selected {
            border-color: #2ecc71;
            background: #e8f5e9;
        }
        
        /* Butonlar */
        .buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-back {
            background: #95a5a6;
            color: white;
        }
        .btn-back:hover {
            background: #7f8c8d;
        }
        .btn-next {
            background: #3498db;
            color: white;
        }
        .btn-next:hover {
            background: #2980b9;
        }
        .btn-next:disabled {
            background: #bdc3c7;
            cursor: not-allowed;
        }
        
        /* Seçim özeti */
        .selection-summary {
            padding: 15px;
            background: #e8f5e9;
            border-radius: 5px;
            margin-top: 20px;
            display: none;
        }
        .selection-summary.show {
            display: block;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="container">
            <div class="logo">
                <img src="../../assets/images/plaza-logo.png" alt="Plaza Emlak">
                <span>İlan Ekle</span>
            </div>
        </div>
    </div>

    <!-- Adımlar -->
    <div class="steps">
        <div class="container">
            <div class="steps-wrapper">
                <div class="step active">
                    <div class="step-circle">1</div>
                    <div class="step-title">Kategori Seçimi</div>
                </div>
                <div class="step-line"></div>
                <div class="step">
                    <div class="step-circle">2</div>
                    <div class="step-title">İlan Detayları</div>
                </div>
                <div class="step-line" style="left: 40%"></div>
                <div class="step">
                    <div class="step-circle">3</div>
                    <div class="step-title">Önizleme</div>
                </div>
                <div class="step-line" style="left: 55%"></div>
                <div class="step">
                    <div class="step-circle">4</div>
                    <div class="step-title">Doping</div>
                </div>
                <div class="step-line" style="left: 70%"></div>
                <div class="step">
                    <div class="step-circle">5</div>
                    <div class="step-title">Tebrikler</div>
                </div>
            </div>
        </div>
    </div>

    <!-- İçerik -->
    <div class="container">
        <div class="content">
            <h1 class="page-title">📋 Adım Adım Kategori Seç</h1>
            <p class="page-subtitle">İlanınız için uygun kategoriyi seçin</p>

            <!-- Ana Kategoriler -->
            <div class="category-grid">
                <div class="category-box" data-category="konut">
                    <h3 class="category-title">🏠 Konut</h3>
                    <ul class="category-list">
                        <li data-type="satilik">Satılık</li>
                        <li data-type="kiralik">Kiralık</li>
                        <li data-type="devren">Devren Satılık Konut</li>
                    </ul>
                </div>
                
                <div class="category-box" data-category="isyeri">
                    <h3 class="category-title">🏢 İş Yeri</h3>
                    <ul class="category-list">
                        <li data-type="satilik">Satılık</li>
                        <li data-type="kiralik">Kiralık</li>
                        <li data-type="devren">Devren</li>
                    </ul>
                </div>
                
                <div class="category-box" data-category="arsa">
                    <h3 class="category-title">🌍 Arsa</h3>
                    <ul class="category-list">
                        <li data-type="satilik">Satılık</li>
                        <li data-type="kiralik">Kiralık</li>
                    </ul>
                </div>
            </div>

            <!-- Alt Kategori (Konut için) -->
            <div id="konut-types" class="subcategory-section">
                <h3 style="margin-bottom: 15px;">Konut Tipi Seçin:</h3>
                <div class="subcategory-grid">
                    <div class="subcategory-item" data-subtype="daire">Daire</div>
                    <div class="subcategory-item" data-subtype="rezidans">Rezidans</div>
                    <div class="subcategory-item" data-subtype="mustakil-ev">Müstakil Ev</div>
                    <div class="subcategory-item" data-subtype="villa">Villa</div>
                    <div class="subcategory-item" data-subtype="ciftlik-evi">Çiftlik Evi</div>
                    <div class="subcategory-item" data-subtype="kosk">Köşk & Konak</div>
                    <div class="subcategory-item" data-subtype="yali">Yalı</div>
                    <div class="subcategory-item" data-subtype="yazlik">Yazlık</div>
                </div>
            </div>

            <!-- Seçim Özeti -->
            <div class="selection-summary">
                <strong>Seçiminiz:</strong> <span id="selection-text"></span>
            </div>

            <!-- Form (gizli) -->
            <form id="categoryForm" method="POST" action="add-step2.php">
                <input type="hidden" name="emlak_tipi" id="emlak_tipi">
                <input type="hidden" name="kategori" id="kategori">
                <input type="hidden" name="alt_kategori" id="alt_kategori">
            </form>

            <!-- Butonlar -->
            <div class="buttons">
                <button type="button" class="btn btn-back" onclick="window.location.href='../dashboard.php'">
                    ← Geri
                </button>
                <button type="button" class="btn btn-next" id="nextBtn" disabled>
                    Devam →
                </button>
            </div>
        </div>
    </div>

    <script>
        let selectedCategory = null;
        let selectedType = null;
        let selectedSubtype = null;

        // Kategori kutusuna tıklama
        document.querySelectorAll('.category-box').forEach(box => {
            box.addEventListener('click', function(e) {
                if(e.target.tagName === 'LI') return;
                
                document.querySelectorAll('.category-box').forEach(b => b.classList.remove('selected'));
                this.classList.add('selected');
                selectedCategory = this.dataset.category;
                
                // Alt kategoriyi gizle
                document.querySelectorAll('.subcategory-section').forEach(s => s.classList.remove('show'));
                checkSelection();
            });
        });

        // Tip seçimi (Satılık/Kiralık)
        document.querySelectorAll('.category-list li').forEach(item => {
            item.addEventListener('click', function(e) {
                e.stopPropagation();
                
                // Önce tüm aktif sınıfları kaldır
                document.querySelectorAll('.category-list li').forEach(li => li.classList.remove('active'));
                this.classList.add('active');
                
                selectedType = this.dataset.type;
                selectedCategory = this.closest('.category-box').dataset.category;
                
                // Kategori kutusunu seç
                document.querySelectorAll('.category-box').forEach(b => b.classList.remove('selected'));
                this.closest('.category-box').classList.add('selected');
                
                // Konut ise alt kategori göster
                if(selectedCategory === 'konut') {
                    document.getElementById('konut-types').classList.add('show');
                } else {
                    checkSelection();
                }
            });
        });

        // Alt kategori seçimi
        document.querySelectorAll('.subcategory-item').forEach(item => {
            item.addEventListener('click', function() {
                document.querySelectorAll('.subcategory-item').forEach(i => i.classList.remove('selected'));
                this.classList.add('selected');
                selectedSubtype = this.dataset.subtype;
                checkSelection();
            });
        });

        // Seçim kontrolü
        function checkSelection() {
            let isValid = false;
            let selectionText = '';
            
            if(selectedCategory && selectedType) {
                if(selectedCategory === 'konut' && selectedSubtype) {
                    isValid = true;
                    selectionText = `Emlak > Konut > ${selectedType} > ${selectedSubtype}`;
                } else if(selectedCategory !== 'konut') {
                    isValid = true;
                    selectionText = `Emlak > ${selectedCategory} > ${selectedType}`;
                }
            }
            
            // Özeti göster/gizle
            if(isValid) {
                document.querySelector('.selection-summary').classList.add('show');
                document.getElementById('selection-text').textContent = selectionText;
                document.getElementById('nextBtn').disabled = false;
                
                // Form alanlarını doldur
                document.getElementById('emlak_tipi').value = selectedCategory;
                document.getElementById('kategori').value = selectedType;
                document.getElementById('alt_kategori').value = selectedSubtype || '';
            } else {
                document.querySelector('.selection-summary').classList.remove('show');
                document.getElementById('nextBtn').disabled = true;
            }
        }

        // Devam butonu
        document.getElementById('nextBtn').addEventListener('click', function() {
            if(!this.disabled) {
                document.getElementById('categoryForm').submit();
            }
        });
    </script>
</body>
</html>