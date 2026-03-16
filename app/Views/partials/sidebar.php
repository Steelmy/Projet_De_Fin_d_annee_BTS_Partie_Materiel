<nav class="sidebar text-center font-bold" id="sidebar">
    <div class="sidebar-header">
        <button class="close-sidebar-btn" onclick="toggleSidebar()">&times;</button>
        <h2 class="text-custom-brandLight font-bold">INVENTAIRE</h2>
    </div>
    <ul class="sidebar-menu">
        <li class="flex justify-center"><a href="../dashboard.php">Dashboard</a></li>
        <li class="flex justify-center"><a href="../dashboard?page=users">Utilisateurs</a></li>
        <li class="active flex justify-center"><a href="index.php">Gestion du matériel</a></li>
        <li onclick="toggleModal(true)" class="text-custom-brandLight font-bold mt-4 cursor-pointer flex justify-center">Nouvel Emprunt</li>
    </ul>
</nav>
