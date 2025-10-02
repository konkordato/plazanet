<?php
/**
 * BÜTÇE ARAMA MODÜLÜ
 * Bu modül hem ana sayfada hem de detay sayfalarında kullanılabilir
 * Müşterinin bütçesine göre tüm kategorilerdeki ilanları filtreler
 */

// Veritabanı bağlantısını kontrol et
if(!isset($db)) {
    // Ana sayfadan mı detay sayfadan mı çağrıldığını anla
    $config_path = file_exists('../config/database.php') ? '../config/database.php' : 'config/database.php';
    require_once $config_path;
}

// Form gönderildi mi kontrol et
$search_performed = false;
$budget_results = [];
$min_budget = '';
$max_budget = '';

if(isset($_GET['budget_search']) && $_GET['budget_search'] == '1') {
    $search_performed = true;
    $min_budget = $_GET['min_budget'] ?? 0;
    $max_budget = $_GET['max_budget'] ?? 999999999;
    
    // Güvenlik için değerleri temizle
    $min_budget = intval($min_budget);
    $max_budget = intval($max_budget);
    
    // Bütçeye uygun ilanları çek
    $query = "SELECT p.*, pi.image_path 
              FROM properties p 
              LEFT JOIN property_images pi ON p.id = pi.property_id AND pi.is_main = 1
              WHERE p.durum = 'aktif' 
              AND p.fiyat >= :min_budget 
              AND p.fiyat <= :max_budget
              ORDER BY p.fiyat ASC
              LIMIT 20";
    
    $stmt = $db->prepare($query);
    $stmt->execute([
        ':min_budget' => $min_budget,
        ':max_budget' => $max_budget
    ]);
    $budget_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!-- BÜTÇE ARAMA MODÜLÜ HTML -->
<div class="budget-search-module">
    <div class="budget-search-header">
        <h3>💰 Bütçenize Göre Arama</h3>
        <p>Bütçenizi belirtin, size uygun tüm ilanları görelim</p>
    </div>
    
    <form method="GET" action="" class="budget-search-form">
        <input type="hidden" name="budget_search" value="1">
        
        <div class="budget-input-group">
            <label>Min Bütçe (₺)</label>
            <input type="number" 
                   name="min_budget" 
                   placeholder="Örn: 100000" 
                   value="<?php echo $min_budget; ?>"
                   class="budget-input">
        </div>
        
        <div class="budget-input-group">
            <label>Max Bütçe (₺)</label>
            <input type="number" 
                   name="max_budget" 
                   placeholder="Örn: 500000" 
                   value="<?php echo $max_budget; ?>"
                   class="budget-input">
        </div>
        
        <button type="submit" class="budget-search-btn">
            🔍 Bütçeme Uygun İlanları Göster
        </button>
    </form>
    
    <?php if($search_performed && count($budget_results) > 0): ?>
        <div class="budget-results-info">
            <strong><?php echo count($budget_results); ?></strong> adet uygun ilan bulundu
        </div>
    <?php elseif($search_performed && count($budget_results) == 0): ?>
        <div class="budget-no-results">
            Bu bütçe aralığında ilan bulunamadı
        </div>
    <?php endif; ?>
</div>

<!-- MODÜL İÇİN CSS -->
<style>
.budget-search-module {
    background: white;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin: 20px 0;
}

.budget-search-header {
    text-align: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid #f0f0f0;
}

.budget-search-header h3 {
    color: #2c3e50;
    font-size: 20px;
    margin-bottom: 8px;
    font-weight: 600;
}

.budget-search-header p {
    color: #7f8c8d;
    font-size: 14px;
}

.budget-search-form {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.budget-input-group {
    display: flex;
    flex-direction: column;
}

.budget-input-group label {
    color: #555;
    font-size: 13px;
    margin-bottom: 5px;
    font-weight: 500;
}

.budget-input {
    padding: 10px 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
    transition: all 0.3s;
}

.budget-input:focus {
    outline: none;
    border-color: #3498db;
    box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
}

.budget-search-btn {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    padding: 12px 20px;
    border-radius: 6px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    text-align: center;
}

.budget-search-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
}

.budget-results-info {
    margin-top: 15px;
    padding: 10px;
    background: #d4edda;
    color: #155724;
    border-radius: 6px;
    text-align: center;
    font-size: 14px;
}

.budget-no-results {
    margin-top: 15px;
    padding: 10px;
    background: #f8d7da;
    color: #721c24;
    border-radius: 6px;
    text-align: center;
    font-size: 14px;
}

/* Mobil için responsive */
@media (max-width: 768px) {
    .budget-search-module {
        margin: 10px;
        padding: 15px;
    }
}
</style>

<?php
// Eğer sonuç varsa ve bu ana sayfada gösterilecekse
if($search_performed && count($budget_results) > 0): 
?>
<script>
// Sayfada sonuç göster butonu için JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Sonuçları göstermek için bir modal veya alan oluştur
    var resultsHTML = '<div class="budget-search-results-modal" id="budgetResultsModal">';
    resultsHTML += '<div class="modal-content">';
    resultsHTML += '<h3>Bütçenize Uygun İlanlar</h3>';
    resultsHTML += '<div class="results-grid">';
    
    <?php foreach($budget_results as $result): ?>
    resultsHTML += `
        <div class="result-item">
            <a href="<?php echo strpos($_SERVER['REQUEST_URI'], 'pages/') !== false ? 'detail.php?id=' : 'pages/detail.php?id='; ?><?php echo $result['id']; ?>">
                <div class="result-title"><?php echo htmlspecialchars($result['baslik']); ?></div>
                <div class="result-location">📍 <?php echo $result['ilce'] . ', ' . $result['il']; ?></div>
                <div class="result-price"><?php echo number_format($result['fiyat'], 0, ',', '.'); ?> ₺</div>
            </a>
        </div>
    `;
    <?php endforeach; ?>
    
    resultsHTML += '</div>';
    resultsHTML += '<button onclick="closeBudgetResults()" class="close-modal-btn">Kapat</button>';
    resultsHTML += '</div></div>';
    
    // Sonuçları body'e ekle
    document.body.insertAdjacentHTML('beforeend', resultsHTML);
    
    // Modal'ı göster
    setTimeout(function() {
        document.getElementById('budgetResultsModal').style.display = 'block';
    }, 500);
});

function closeBudgetResults() {
    document.getElementById('budgetResultsModal').style.display = 'none';
}
</script>

<style>
.budget-search-results-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.7);
    z-index: 9999;
    overflow-y: auto;
}

.modal-content {
    background: white;
    max-width: 800px;
    margin: 50px auto;
    padding: 30px;
    border-radius: 10px;
}

.results-grid {
    display: grid;
    gap: 15px;
    margin: 20px 0;
}

.result-item {
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
    transition: all 0.3s;
}

.result-item:hover {
    background: #e9ecef;
    transform: translateX(5px);
}

.result-item a {
    text-decoration: none;
    color: inherit;
    display: block;
}

.result-title {
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 5px;
}

.result-location {
    color: #7f8c8d;
    font-size: 14px;
    margin-bottom: 5px;
}

.result-price {
    color: #ff6b35;
    font-size: 18px;
    font-weight: bold;
}

.close-modal-btn {
    background: #e74c3c;
    color: white;
    border: none;
    padding: 10px 25px;
    border-radius: 5px;
    cursor: pointer;
    font-weight: 600;
}

.close-modal-btn:hover {
    background: #c0392b;
}
</style>
<?php endif; ?>