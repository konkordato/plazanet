<?php
<<<<<<< HEAD

session_start();

if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {

    header("Location: ../index.php");

    exit();

}



require_once '../../config/database.php';



// POST'tan gelen verileri SESSION'a kaydet ve GET ile yönlendir

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['emlak_tipi'])) {

    $_SESSION['emlak_tipi'] = $_POST['emlak_tipi'];

    $_SESSION['kategori'] = $_POST['kategori'];

    $_SESSION['alt_kategori'] = $_POST['alt_kategori'] ?? '';

    

    // POST-REDIRECT-GET pattern ile yönlendir

    header("Location: add-step2.php");

    exit();

}



// Session'dan verileri al

$emlak_tipi = $_SESSION['emlak_tipi'] ?? '';

$kategori = $_SESSION['kategori'] ?? '';

$alt_kategori = $_SESSION['alt_kategori'] ?? '';



// Eğer session'da veri yoksa step1'e yönlendir

if (empty($emlak_tipi) || empty($kategori)) {

    header("Location: add-step1.php");

    exit();

}



// Lokasyon önerilerini çek

$il_onerileri = $db->query("SELECT DISTINCT il FROM lokasyon_onerileri ORDER BY kullanim_sayisi DESC, il ASC")->fetchAll(PDO::FETCH_COLUMN);

$ilce_onerileri = $db->query("SELECT DISTINCT ilce FROM lokasyon_onerileri ORDER BY kullanim_sayisi DESC, ilce ASC")->fetchAll(PDO::FETCH_COLUMN);

$mahalle_onerileri = $db->query("SELECT DISTINCT mahalle FROM lokasyon_onerileri WHERE mahalle IS NOT NULL ORDER BY kullanim_sayisi DESC, mahalle ASC")->fetchAll(PDO::FETCH_COLUMN);

?>

<!DOCTYPE html>

<html lang="tr">



<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>İlan Detayları | Plaza Emlak</title>

    <link rel="stylesheet" href="../../assets/css/admin-form.css">

    <style>

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



        .upload-area {

            transition: all 0.3s;

        }



        /* HARİTA STİLLERİ */

        #map-container {

            height: 400px;

            border: 2px solid #ddd;

            border-radius: 8px;

            margin-top: 10px;

        }

    </style>

</head>



<body>

    <div class="header">

        <div class="container">

            <div class="logo">

                <img src="https://plazaemlak.net/assets/images/plaza-logo-buyuk.png" alt="Plaza">

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

                    <div class="step-title">Tebrikler</div>

                </div>

            </div>

        </div>

    </div>



    <div class="container">

        <div class="content">

            <div class="breadcrumb">

                Emlak > <?php echo ucfirst($emlak_tipi); ?> > <?php echo ucfirst($kategori); ?>

                <?php if ($alt_kategori): ?>> <?php echo ucfirst($alt_kategori); ?><?php endif; ?>

            </div>



            <h1 class="page-title">İlan Detayları</h1>



            <form id="detailForm" method="POST" action="add-step3.php" enctype="multipart/form-data">

                <!-- TEMEL BİLGİLER -->

                <div class="form-section">

                    <h2 class="section-title">Temel Bilgiler</h2>



                    <div class="form-group">

                        <label class="required">İlan Başlığı</label>

                        <input type="text" name="baslik" maxlength="100" required

                            placeholder="Örn: <?php echo $emlak_tipi == 'arsa' ? 'Satılık İmarlı Arsa' : 'Plaza\'dan Merkez\'de 3+1 Kiralık Daire'; ?>">

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



                    <?php if ($emlak_tipi == 'arsa'): ?>

                        <!-- ARSA İÇİN ÖZEL ALANLAR -->

                        <div class="form-row">

                            <div class="form-group">

                                <label class="required">m²</label>

                                <input type="number" name="brut_metrekare" required placeholder="Arsa metrekaresi">

                            </div>

                            <div class="form-group">

                                <label>m² Fiyatı</label>

                                <input type="number" name="metrekare_fiyat" placeholder="Metrekare başına fiyat">

                            </div>

                        </div>



                        <div class="form-row">

                            <div class="form-group">

                                <label>İmar Durumu</label>

                                <select name="imar_durumu">

                                    <option value="">Seçiniz</option>

                                    <option value="İmarlı">İmarlı</option>

                                    <option value="İmarsız">İmarsız</option>

                                    <option value="Tarla">Tarla</option>

                                    <option value="Bağ">Bağ</option>

                                    <option value="Bahçe">Bahçe</option>

                                </select>

                            </div>

                            <div class="form-group">

                                <label>Ada No</label>

                                <input type="text" name="ada_no" placeholder="Ada numarası">

                            </div>

                            <div class="form-group">

                                <label>Parsel No</label>

                                <input type="text" name="parsel_no" placeholder="Parsel numarası">

                            </div>

                        </div>



                        <div class="form-row">

                            <div class="form-group">

                                <label>Pafta No</label>

                                <input type="text" name="pafta_no" placeholder="Pafta numarası">

                            </div>

                            <div class="form-group">

                                <label>Kaks (Emsal)</label>

                                <input type="text" name="kaks_emsal" placeholder="Örn: 2.5">

                            </div>

                            <div class="form-group">

                                <label>Gabari</label>

                                <input type="text" name="gabari" placeholder="Örn: 12.5m">

                            </div>

                        </div>



                        <div class="form-row">

                            <div class="form-group">

                                <label>Tapu Durumu</label>

                                <select name="tapu_durumu">

                                    <option value="">Seçiniz</option>

                                    <option value="Müstakil Tapulu">Müstakil Tapulu</option>

                                    <option value="Kat İrtifaklı">Kat İrtifaklı</option>

                                    <option value="Kat Mülkiyetli">Kat Mülkiyetli</option>

                                    <option value="Hisseli">Hisseli</option>

                                    <option value="Arsa Tapulu">Arsa Tapulu</option>

                                </select>

                            </div>

                            <div class="form-group">

                                <label>Krediye Uygunluk</label>

                                <select name="krediye_uygun">

                                    <option value="">Seçiniz</option>

                                    <option value="Evet">Evet</option>

                                    <option value="Hayır">Hayır</option>

                                    <option value="Bilinmiyor">Bilinmiyor</option>

                                </select>

                            </div>

                            <div class="form-group">

                                <label>Takas</label>

                                <select name="takas">

                                    <option value="Hayır">Hayır</option>

                                    <option value="Evet">Evet</option>

                                </select>

                            </div>

                        </div>



                    <?php else: ?>

                        <!-- KONUT VE İŞYERİ İÇİN NORMAL ALANLAR -->

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

                                    <?php for ($i = 1; $i <= 30; $i++): ?>

                                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>

                                    <?php endfor; ?>

                                </select>

                            </div>

                            <div class="form-group">

                                <label>Kat Sayısı</label>

                                <select name="kat_sayisi">

                                    <option value="">Seçiniz</option>

                                    <?php for ($i = 1; $i <= 30; $i++): ?>

                                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>

                                    <?php endfor; ?>

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

                        <!-- YENİ SATIR - Mutfak, Asansör, Otopark -->

                        <div class="form-row">

                            <div class="form-group">

                                <label>Mutfak</label>

                                <select name="mutfak">

                                    <option value="">Seçiniz</option>

                                    <option value="Açık">Açık</option>

                                    <option value="Kapalı">Kapalı</option>

                                    <option value="Amerikan">Amerikan</option>

                                </select>

                            </div>

                            <div class="form-group">

                                <label>Asansör</label>

                                <select name="asansor">

                                    <option value="">Seçiniz</option>

                                    <option value="Var">Var</option>

                                    <option value="Yok">Yok</option>

                                </select>

                            </div>

                            <div class="form-group">

                                <label>Otopark</label>

                                <select name="otopark">

                                    <option value="">Seçiniz</option>

                                    <option value="Yok">Yok</option>

                                    <option value="Açık">Açık Otopark</option>

                                    <option value="Kapalı">Kapalı Otopark</option>

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

                            <div class="form-group">

                                <label>Site Adı</label>

                                <input type="text" name="site_adi" id="site_adi" placeholder="Site içindeyse adını yazın">

                                <small>Site içerisinde ise site adını belirtin</small>

                            </div>

                        </div>



                        <?php if ($kategori == 'kiralik'): ?>

                            <div class="form-group">

                                <label>Aidat (TL)</label>

                                <input type="number" name="aidat" placeholder="0">

                            </div>

                        <?php endif; ?>

                    <?php endif; ?>

                </div>



                <!-- ADRES BİLGİLERİ -->

                <div class="form-section">

                    <h2 class="section-title">Adres Bilgileri</h2>



                    <div class="form-row">

                        <div class="form-group">

                            <label class="required">İl</label>

                            <input type="text" name="il" id="il" value="Afyonkarahisar" required

                                placeholder="İl adını yazın" list="il-listesi"

                                style="text-transform: capitalize;">

                            <datalist id="il-listesi">

                                <?php foreach ($il_onerileri as $il): ?>

                                    <option value="<?php echo htmlspecialchars($il); ?>">

                                    <?php endforeach; ?>

                            </datalist>

                            <small>İlk harf büyük, diğerleri küçük yazın</small>

                        </div>

                        <div class="form-group">

                            <label class="required">İlçe</label>

                            <input type="text" name="ilce" id="ilce" required

                                placeholder="İlçe adını yazın" list="ilce-listesi"

                                style="text-transform: capitalize;">

                            <datalist id="ilce-listesi">

                                <?php foreach ($ilce_onerileri as $ilce): ?>

                                    <option value="<?php echo htmlspecialchars($ilce); ?>">

                                    <?php endforeach; ?>

                            </datalist>

                            <small>İlk harf büyük yazın</small>

                        </div>

                        <div class="form-group">

                            <label>Mahalle/Köy</label>

                            <input type="text" name="mahalle" id="mahalle"

                                placeholder="Mahalle veya köy adını yazın" list="mahalle-listesi"

                                style="text-transform: capitalize;">

                            <datalist id="mahalle-listesi">

                                <?php foreach ($mahalle_onerileri as $mahalle): ?>

                                    <option value="<?php echo htmlspecialchars($mahalle); ?>">

                                    <?php endforeach; ?>

                            </datalist>

                            <small>İlk harf büyük yazın</small>

                        </div>

                    </div>



                    <div class="form-group">

                        <label>Açık Adres</label>

                        <textarea name="adres" rows="3" placeholder="Cadde, sokak, bina no vb."></textarea>

                    </div>

                    <!-- HARİTA İLE KONUM SEÇİMİ -->

                    <div class="form-group" style="margin-top: 20px;">

                        <label>📍 Haritada Konum Belirle</label>

                        <div style="margin-bottom: 10px;">

                            <input type="text" id="map-search" placeholder="Adres ara... (örn: Afyon merkez)"

                                style="width: 70%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">

                            <button type="button" onclick="searchAddress()"

                                style="padding: 8px 15px; background: #3498db; color: white; border: none; border-radius: 4px; cursor: pointer;">

                                Ara

                            </button>

                        </div>

                        <div id="map-container"></div>



                        <input type="hidden" name="latitude" id="latitude">

                        <input type="hidden" name="longitude" id="longitude">



                        <div style="margin-top: 10px; padding: 10px; background: #f0f8ff; border-radius: 5px;">

                            <small><strong>Kullanım:</strong> Haritada istediğiniz noktaya tıklayın veya üstteki arama kutusunu kullanın.</small>

                        </div>

                    </div>

                </div>

                <!-- DANIŞMAN ÖZEL ALANLARI -->

                <div class="form-section special">

                    <h2 class="section-title">🔒 Danışman Bilgileri (Sadece Siz Görebilirsiniz)</h2>



                    <div class="form-row">

                        <div class="form-group">

                            <label>Anahtar Numarası</label>

                            <input type="text" name="anahtar_no" placeholder="Örn: A-125, K-44">

                            <small>Ofisteki anahtar kodu</small>

                        </div>

                        <div class="form-group">

                            <label>Mülk Sahibi Telefonu</label>

                            <input type="tel" name="mulk_sahibi_tel" placeholder="05XX XXX XX XX">

                            <small>Sadece danışmanlar görebilir</small>

                        </div>

                    </div>



                    <div class="form-group">

                        <label>Danışman Notu</label>

                        <textarea name="danisman_notu" rows="3"

                            placeholder="Özel notlarınız, dikkat edilmesi gerekenler vb."></textarea>

                    </div>

                </div>



                <!-- FOTOĞRAFLAR -->

                <div class="form-section">

                    <h2 class="section-title">Fotoğraflar</h2>

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

                    <a href="add-step1.php" class="btn btn-back">← Geri</a>

                    <button type="submit" class="btn btn-next">Devam →</button>

                </div>

            </form>

        </div>

    </div>



    <script src="../../assets/js/property-form.js"></script>

    <script>

        // İl, ilçe, mahalle format kontrolü

        function formatText(text) {

            if (!text) return '';

            // Her kelimenin ilk harfi büyük, diğerleri küçük

            return text.toLowerCase().replace(/\b\w/g, l => l.toUpperCase());

        }



        document.getElementById('il').addEventListener('blur', function() {

            this.value = formatText(this.value);

        });



        document.getElementById('ilce').addEventListener('blur', function() {

            this.value = formatText(this.value);

        });



        var mahalleInput = document.getElementById('mahalle');

        if (mahalleInput) {

            mahalleInput.addEventListener('blur', function() {

                this.value = formatText(this.value);

            });

        }

    </script>

    <script>

        var map;

        var marker;



        function initMap() {

            // Varsayılan konum (Afyonkarahisar)

            var defaultLocation = {

                lat: 38.7507,

                lng: 30.5567

            };



            map = new google.maps.Map(document.getElementById('map-container'), {

                center: defaultLocation,

                zoom: 13,

                mapTypeControl: true,

                streetViewControl: true,

                fullscreenControl: true

            });



            // Haritaya tıklama olayı

            map.addListener('click', function(event) {

                placeMarker(event.latLng);

            });

        }



        function placeMarker(location) {

            // Eski marker varsa kaldır

            if (marker) {

                marker.setMap(null);

            }



            // Yeni marker ekle

            marker = new google.maps.Marker({

                position: location,

                map: map,

                draggable: true,

                animation: google.maps.Animation.DROP

            });



            // Koordinatları form alanlarına yaz

            document.getElementById('latitude').value = location.lat();

            document.getElementById('longitude').value = location.lng();



            // Marker sürüklendiğinde koordinatları güncelle

            marker.addListener('dragend', function(event) {

                document.getElementById('latitude').value = event.latLng.lat();

                document.getElementById('longitude').value = event.latLng.lng();

            });

        }



        function searchAddress() {

            var address = document.getElementById('map-search').value;

            if (!address) {

                alert('Lütfen bir adres girin');

                return;

            }



            var geocoder = new google.maps.Geocoder();

            geocoder.geocode({

                'address': address + ', Türkiye'

            }, function(results, status) {

                if (status === 'OK') {

                    map.setCenter(results[0].geometry.location);

                    map.setZoom(15);

                    placeMarker(results[0].geometry.location);

                } else {

                    alert('Adres bulunamadı. Daha detaylı yazın veya haritada tıklayın.');

                }

            });

        }

    </script>



    <!-- Google Maps API -->

    <script async defer

        src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAEfetSi8hgru3jatZYeS5WaLjUD_lMED4&callback=initMap&language=tr">

    </script>

</body>



=======
session_start();
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: ../index.php");
    exit();
}

require_once '../../config/database.php';

// POST'tan gelen verileri SESSION'a kaydet ve GET ile yönlendir
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['emlak_tipi'])) {
    $_SESSION['emlak_tipi'] = $_POST['emlak_tipi'];
    $_SESSION['kategori'] = $_POST['kategori'];
    $_SESSION['alt_kategori'] = $_POST['alt_kategori'] ?? '';
    
    // POST-REDIRECT-GET pattern ile yönlendir
    header("Location: add-step2.php");
    exit();
}

// Session'dan verileri al
$emlak_tipi = $_SESSION['emlak_tipi'] ?? '';
$kategori = $_SESSION['kategori'] ?? '';
$alt_kategori = $_SESSION['alt_kategori'] ?? '';

// Eğer session'da veri yoksa step1'e yönlendir
if (empty($emlak_tipi) || empty($kategori)) {
    header("Location: add-step1.php");
    exit();
}

// Lokasyon önerilerini çek
$il_onerileri = $db->query("SELECT DISTINCT il FROM lokasyon_onerileri ORDER BY kullanim_sayisi DESC, il ASC")->fetchAll(PDO::FETCH_COLUMN);
$ilce_onerileri = $db->query("SELECT DISTINCT ilce FROM lokasyon_onerileri ORDER BY kullanim_sayisi DESC, ilce ASC")->fetchAll(PDO::FETCH_COLUMN);
$mahalle_onerileri = $db->query("SELECT DISTINCT mahalle FROM lokasyon_onerileri WHERE mahalle IS NOT NULL ORDER BY kullanim_sayisi DESC, mahalle ASC")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>İlan Detayları | Plaza Emlak</title>
    <link rel="stylesheet" href="../../assets/css/admin-form.css">
    <style>
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

        .upload-area {
            transition: all 0.3s;
        }

        /* HARİTA STİLLERİ */
        #map-container {
            height: 400px;
            border: 2px solid #ddd;
            border-radius: 8px;
            margin-top: 10px;
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
                    <div class="step-title">Tebrikler</div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="content">
            <div class="breadcrumb">
                Emlak > <?php echo ucfirst($emlak_tipi); ?> > <?php echo ucfirst($kategori); ?>
                <?php if ($alt_kategori): ?>> <?php echo ucfirst($alt_kategori); ?><?php endif; ?>
            </div>

            <h1 class="page-title">İlan Detayları</h1>

            <form id="detailForm" method="POST" action="add-step3.php" enctype="multipart/form-data">
                <!-- TEMEL BİLGİLER -->
                <div class="form-section">
                    <h2 class="section-title">Temel Bilgiler</h2>

                    <div class="form-group">
                        <label class="required">İlan Başlığı</label>
                        <input type="text" name="baslik" maxlength="100" required
                            placeholder="Örn: <?php echo $emlak_tipi == 'arsa' ? 'Satılık İmarlı Arsa' : 'Plaza\'dan Merkez\'de 3+1 Kiralık Daire'; ?>">
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

                    <?php if ($emlak_tipi == 'arsa'): ?>
                        <!-- ARSA İÇİN ÖZEL ALANLAR -->
                        <div class="form-row">
                            <div class="form-group">
                                <label class="required">m²</label>
                                <input type="number" name="brut_metrekare" required placeholder="Arsa metrekaresi">
                            </div>
                            <div class="form-group">
                                <label>m² Fiyatı</label>
                                <input type="number" name="metrekare_fiyat" placeholder="Metrekare başına fiyat">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>İmar Durumu</label>
                                <select name="imar_durumu">
                                    <option value="">Seçiniz</option>
                                    <option value="İmarlı">İmarlı</option>
                                    <option value="İmarsız">İmarsız</option>
                                    <option value="Tarla">Tarla</option>
                                    <option value="Bağ">Bağ</option>
                                    <option value="Bahçe">Bahçe</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Ada No</label>
                                <input type="text" name="ada_no" placeholder="Ada numarası">
                            </div>
                            <div class="form-group">
                                <label>Parsel No</label>
                                <input type="text" name="parsel_no" placeholder="Parsel numarası">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Pafta No</label>
                                <input type="text" name="pafta_no" placeholder="Pafta numarası">
                            </div>
                            <div class="form-group">
                                <label>Kaks (Emsal)</label>
                                <input type="text" name="kaks_emsal" placeholder="Örn: 2.5">
                            </div>
                            <div class="form-group">
                                <label>Gabari</label>
                                <input type="text" name="gabari" placeholder="Örn: 12.5m">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Tapu Durumu</label>
                                <select name="tapu_durumu">
                                    <option value="">Seçiniz</option>
                                    <option value="Müstakil Tapulu">Müstakil Tapulu</option>
                                    <option value="Kat İrtifaklı">Kat İrtifaklı</option>
                                    <option value="Kat Mülkiyetli">Kat Mülkiyetli</option>
                                    <option value="Hisseli">Hisseli</option>
                                    <option value="Arsa Tapulu">Arsa Tapulu</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Krediye Uygunluk</label>
                                <select name="krediye_uygun">
                                    <option value="">Seçiniz</option>
                                    <option value="Evet">Evet</option>
                                    <option value="Hayır">Hayır</option>
                                    <option value="Bilinmiyor">Bilinmiyor</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Takas</label>
                                <select name="takas">
                                    <option value="Hayır">Hayır</option>
                                    <option value="Evet">Evet</option>
                                </select>
                            </div>
                        </div>

                    <?php else: ?>
                        <!-- KONUT VE İŞYERİ İÇİN NORMAL ALANLAR -->
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
                                    <?php for ($i = 1; $i <= 30; $i++): ?>
                                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Kat Sayısı</label>
                                <select name="kat_sayisi">
                                    <option value="">Seçiniz</option>
                                    <?php for ($i = 1; $i <= 30; $i++): ?>
                                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                    <?php endfor; ?>
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
                        <!-- YENİ SATIR - Mutfak, Asansör, Otopark -->
                        <div class="form-row">
                            <div class="form-group">
                                <label>Mutfak</label>
                                <select name="mutfak">
                                    <option value="">Seçiniz</option>
                                    <option value="Açık">Açık</option>
                                    <option value="Kapalı">Kapalı</option>
                                    <option value="Amerikan">Amerikan</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Asansör</label>
                                <select name="asansor">
                                    <option value="">Seçiniz</option>
                                    <option value="Var">Var</option>
                                    <option value="Yok">Yok</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Otopark</label>
                                <select name="otopark">
                                    <option value="">Seçiniz</option>
                                    <option value="Yok">Yok</option>
                                    <option value="Açık">Açık Otopark</option>
                                    <option value="Kapalı">Kapalı Otopark</option>
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
                            <div class="form-group">
                                <label>Site Adı</label>
                                <input type="text" name="site_adi" id="site_adi" placeholder="Site içindeyse adını yazın">
                                <small>Site içerisinde ise site adını belirtin</small>
                            </div>
                        </div>

                        <?php if ($kategori == 'kiralik'): ?>
                            <div class="form-group">
                                <label>Aidat (TL)</label>
                                <input type="number" name="aidat" placeholder="0">
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <!-- ADRES BİLGİLERİ -->
                <div class="form-section">
                    <h2 class="section-title">Adres Bilgileri</h2>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="required">İl</label>
                            <input type="text" name="il" id="il" value="Afyonkarahisar" required
                                placeholder="İl adını yazın" list="il-listesi"
                                style="text-transform: capitalize;">
                            <datalist id="il-listesi">
                                <?php foreach ($il_onerileri as $il): ?>
                                    <option value="<?php echo htmlspecialchars($il); ?>">
                                    <?php endforeach; ?>
                            </datalist>
                            <small>İlk harf büyük, diğerleri küçük yazın</small>
                        </div>
                        <div class="form-group">
                            <label class="required">İlçe</label>
                            <input type="text" name="ilce" id="ilce" required
                                placeholder="İlçe adını yazın" list="ilce-listesi"
                                style="text-transform: capitalize;">
                            <datalist id="ilce-listesi">
                                <?php foreach ($ilce_onerileri as $ilce): ?>
                                    <option value="<?php echo htmlspecialchars($ilce); ?>">
                                    <?php endforeach; ?>
                            </datalist>
                            <small>İlk harf büyük yazın</small>
                        </div>
                        <div class="form-group">
                            <label>Mahalle/Köy</label>
                            <input type="text" name="mahalle" id="mahalle"
                                placeholder="Mahalle veya köy adını yazın" list="mahalle-listesi"
                                style="text-transform: capitalize;">
                            <datalist id="mahalle-listesi">
                                <?php foreach ($mahalle_onerileri as $mahalle): ?>
                                    <option value="<?php echo htmlspecialchars($mahalle); ?>">
                                    <?php endforeach; ?>
                            </datalist>
                            <small>İlk harf büyük yazın</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Açık Adres</label>
                        <textarea name="adres" rows="3" placeholder="Cadde, sokak, bina no vb."></textarea>
                    </div>
                    <!-- HARİTA İLE KONUM SEÇİMİ -->
                    <div class="form-group" style="margin-top: 20px;">
                        <label>📍 Haritada Konum Belirle</label>
                        <div style="margin-bottom: 10px;">
                            <input type="text" id="map-search" placeholder="Adres ara... (örn: Afyon merkez)"
                                style="width: 70%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                            <button type="button" onclick="searchAddress()"
                                style="padding: 8px 15px; background: #3498db; color: white; border: none; border-radius: 4px; cursor: pointer;">
                                Ara
                            </button>
                        </div>
                        <div id="map-container"></div>

                        <input type="hidden" name="latitude" id="latitude">
                        <input type="hidden" name="longitude" id="longitude">

                        <div style="margin-top: 10px; padding: 10px; background: #f0f8ff; border-radius: 5px;">
                            <small><strong>Kullanım:</strong> Haritada istediğiniz noktaya tıklayın veya üstteki arama kutusunu kullanın.</small>
                        </div>
                    </div>
                </div>
                <!-- DANIŞMAN ÖZEL ALANLARI -->
                <div class="form-section special">
                    <h2 class="section-title">🔒 Danışman Bilgileri (Sadece Siz Görebilirsiniz)</h2>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Anahtar Numarası</label>
                            <input type="text" name="anahtar_no" placeholder="Örn: A-125, K-44">
                            <small>Ofisteki anahtar kodu</small>
                        </div>
                        <div class="form-group">
                            <label>Mülk Sahibi Telefonu</label>
                            <input type="tel" name="mulk_sahibi_tel" placeholder="05XX XXX XX XX">
                            <small>Sadece danışmanlar görebilir</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Danışman Notu</label>
                        <textarea name="danisman_notu" rows="3"
                            placeholder="Özel notlarınız, dikkat edilmesi gerekenler vb."></textarea>
                    </div>
                </div>

                <!-- FOTOĞRAFLAR -->
                <div class="form-section">
                    <h2 class="section-title">Fotoğraflar</h2>
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
                    <a href="add-step1.php" class="btn btn-back">← Geri</a>
                    <button type="submit" class="btn btn-next">Devam →</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../../assets/js/property-form.js"></script>
    <script>
        // İl, ilçe, mahalle format kontrolü
        function formatText(text) {
            if (!text) return '';
            // Her kelimenin ilk harfi büyük, diğerleri küçük
            return text.toLowerCase().replace(/\b\w/g, l => l.toUpperCase());
        }

        document.getElementById('il').addEventListener('blur', function() {
            this.value = formatText(this.value);
        });

        document.getElementById('ilce').addEventListener('blur', function() {
            this.value = formatText(this.value);
        });

        var mahalleInput = document.getElementById('mahalle');
        if (mahalleInput) {
            mahalleInput.addEventListener('blur', function() {
                this.value = formatText(this.value);
            });
        }
    </script>
    <script>
        var map;
        var marker;

        function initMap() {
            // Varsayılan konum (Afyonkarahisar)
            var defaultLocation = {
                lat: 38.7507,
                lng: 30.5567
            };

            map = new google.maps.Map(document.getElementById('map-container'), {
                center: defaultLocation,
                zoom: 13,
                mapTypeControl: true,
                streetViewControl: true,
                fullscreenControl: true
            });

            // Haritaya tıklama olayı
            map.addListener('click', function(event) {
                placeMarker(event.latLng);
            });
        }

        function placeMarker(location) {
            // Eski marker varsa kaldır
            if (marker) {
                marker.setMap(null);
            }

            // Yeni marker ekle
            marker = new google.maps.Marker({
                position: location,
                map: map,
                draggable: true,
                animation: google.maps.Animation.DROP
            });

            // Koordinatları form alanlarına yaz
            document.getElementById('latitude').value = location.lat();
            document.getElementById('longitude').value = location.lng();

            // Marker sürüklendiğinde koordinatları güncelle
            marker.addListener('dragend', function(event) {
                document.getElementById('latitude').value = event.latLng.lat();
                document.getElementById('longitude').value = event.latLng.lng();
            });
        }

        function searchAddress() {
            var address = document.getElementById('map-search').value;
            if (!address) {
                alert('Lütfen bir adres girin');
                return;
            }

            var geocoder = new google.maps.Geocoder();
            geocoder.geocode({
                'address': address + ', Türkiye'
            }, function(results, status) {
                if (status === 'OK') {
                    map.setCenter(results[0].geometry.location);
                    map.setZoom(15);
                    placeMarker(results[0].geometry.location);
                } else {
                    alert('Adres bulunamadı. Daha detaylı yazın veya haritada tıklayın.');
                }
            });
        }
    </script>

    <!-- Google Maps API -->
    <script async defer
        src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAEfetSi8hgru3jatZYeS5WaLjUD_lMED4&callback=initMap&language=tr">
    </script>
</body>

>>>>>>> 1b4657b0e4a9c21769cd70a9511bc87296025d5f
</html>