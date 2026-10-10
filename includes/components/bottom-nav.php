<div class="ios-tab-bar">
    <a href="/" class="ios-tab-item <?= (basename($_SERVER['PHP_SELF']) == 'home.php') ? 'active' : 'inactive' ?>">
        <i class="<?= (basename($_SERVER['PHP_SELF']) == 'home.php') ? 'ph-fill' : 'ph' ?> ph-house"></i>
        <span>Home</span>
    </a>
    <a href="/discover" class="ios-tab-item <?= (basename($_SERVER['PHP_SELF']) == 'discover.php') ? 'active' : 'inactive' ?>">
        <i class="<?= (basename($_SERVER['PHP_SELF']) == 'discover.php') ? 'ph-fill' : 'ph' ?> ph-compass"></i>
        <span>Discover</span>
    </a>
    <a href="/bookmarks" class="ios-tab-item <?= (basename($_SERVER['PHP_SELF']) == 'bookmarks.php') ? 'active' : 'inactive' ?>">
        <i class="<?= (basename($_SERVER['PHP_SELF']) == 'bookmarks.php') ? 'ph-fill' : 'ph' ?> ph-bookmark"></i>
        <span>Bookmarks</span>
    </a>
    <a href="/library" class="ios-tab-item <?= (basename($_SERVER['PHP_SELF']) == 'catalog.php') ? 'active' : 'inactive' ?>">
        <i class="<?= (basename($_SERVER['PHP_SELF']) == 'catalog.php') ? 'ph-fill' : 'ph' ?> ph-books"></i>
        <span>Library</span>
    </a>
    <a href="/settings" class="ios-tab-item <?= (basename($_SERVER['PHP_SELF']) == 'settings.php') ? 'active' : 'inactive' ?>">
        <i class="<?= (basename($_SERVER['PHP_SELF']) == 'settings.php') ? 'ph-fill' : 'ph' ?> ph-gear"></i>
        <span>Settings</span>
    </a>
</div>
