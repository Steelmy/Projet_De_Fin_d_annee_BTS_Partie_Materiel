<nav class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <button class="close-sidebar-btn" onclick="toggleSidebar()">&times;</button>
        <h2 style="color: var(--primary)">INVENTAIRE</h2>
    </div>
    <ul class="sidebar-menu">
        <li><a href="../dashboard.php">🏠 Dashboard</a></li>
        <li><a href="../dashboard?page=users">👥 Utilisateurs</a></li>
        <li class="active"><a href="index.php">📦 Gestion du matériel</a></li>
        <li onclick="toggleModal(true)" style="color: var(--primary); font-weight: bold; margin-top: 15px;">➕ Nouvel Emprunt</li>
    </ul>
</nav>
