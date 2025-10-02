<?php
// admin/includes/sidebar.php
// Sol menü dosyası - Tüm admin sayfalarında kullanılır

// Hangi sayfada olduğumuzu bul
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Sol Menü -->
<nav class="sidebar">
    <div class="sidebar-header">
        <h2>PLAZANET</h2>
    </div>
    <ul class="sidebar-menu">
        <!-- Ana Sayfa -->
        <li>
            <a href="/plazanet/admin/dashboard.php" data-tooltip="Ana Sayfa" <?php echo ($current_page == 'dashboard.php') ? 'class="active"' : ''; ?>>
                <span class="icon">🏠</span>
                <span>Ana Sayfa</span>
            </a>
        </li>
        
        <!-- İlanlar -->
        <li>
            <a href="/plazanet/admin/properties/list.php" data-tooltip="İlanlar" <?php echo ($current_page == 'list.php') ? 'class="active"' : ''; ?>>
                <span class="icon">🏢</span>
                <span>İlanlar</span>
            </a>
        </li>
        
        <!-- İlan Ekle -->
        <li>
            <a href="/plazanet/admin/properties/add-step1.php" data-tooltip="İlan Ekle" <?php echo ($current_page == 'add-step1.php') ? 'class="active"' : ''; ?>>
                <span class="icon">➕</span>
                <span>İlan Ekle</span>
            </a>
        </li>
        
        <!-- SEO YÖNETİMİ -->
        <li>
            <a href="/plazanet/admin/seo/" data-tooltip="SEO Yönetimi" <?php echo ($current_page == 'index.php' && strpos($_SERVER['PHP_SELF'], '/seo/') !== false) ? 'class="active"' : ''; ?>>
                <span class="icon">🎯</span>
                <span>SEO Yönetimi</span>
            </a>
        </li>
        
        <!-- Kullanıcılar -->
        <li>
            <a href="/plazanet/admin/users/list.php" data-tooltip="Kullanıcılar" <?php echo ($current_page == 'list.php' && strpos($_SERVER['PHP_SELF'], '/users/') !== false) ? 'class="active"' : ''; ?>>
                <span class="icon">👥</span>
                <span>Kullanıcılar</span>
            </a>
        </li>
        
        <!-- CRM -->
        <li>
            <a href="/plazanet/admin/crm/index.php" data-tooltip="CRM Sistemi">
                <span class="icon">📊</span>
                <span>CRM Sistemi</span>
            </a>
        </li>
        
        <!-- Portföy -->
        <li>
            <a href="/plazanet/admin/portfolio/" data-tooltip="Portföy">
                <span class="icon">💼</span>
                <span>Portföy</span>
            </a>
        </li>
        
        <!-- Ayarlar -->
        <li>
            <a href="/plazanet/admin/settings.php" data-tooltip="Ayarlar">
                <span class="icon">⚙️</span>
                <span>Ayarlar</span>
            </a>
        </li>
        
        <!-- Çıkış -->
        <li style="margin-top: 30px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 15px;">
            <a href="/plazanet/admin/logout.php" data-tooltip="Çıkış Yap" style="color: #e74c3c;">
                <span class="icon">🚪</span>
                <span>Çıkış Yap</span>
            </a>
        </li>
    </ul>
</nav>