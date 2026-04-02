<nav class="sidebar fixed top-0 h-screen w-[280px] bg-linear-to-b from-gray-800 to-gray-900 text-white z-50" id="sidebar">
    <div class="px-5 py-[30px] text-center bg-gray-900 border-b border-gray-700 relative">
        <button class="close-sidebar-btn absolute top-3 right-3 text-gray-400 text-2xl" onclick="toggleSidebar()">&times;</button>
        <h2 class="text-[#b8a274] font-bold text-2xl">INVENTAIRE</h2>
    </div>
    <ul class="list-none py-5 m-0">
        <li class="h-[48px] px-[25px] cursor-pointer border-l-4 border-transparent flex items-center gap-2"><a href="http://localhost/IHM_admin/dashboard.php" class="text-white no-underline flex items-center gap-2 w-full font-bold">Dashboard</a></li>
        <li class="h-[48px] px-[25px] cursor-pointer border-l-4 border-transparent flex items-center gap-2"><a href="http://localhost/IHM_admin/utilisateurs.php" class="text-white no-underline flex items-center gap-2 w-full font-bold">Utilisateurs</a></li>
        <li class="h-[48px] px-[25px] cursor-pointer border-l-4 border-[#b8a274] bg-gray-700 flex items-center gap-2"><a href="http://localhost/index.php" class="text-white no-underline flex items-center gap-2 w-full font-bold">Gestion du matériel</a></li>
        <a href="http://localhost/IHM_admin/logout.php" class="logout_btn">Déconnexion</a>
    </ul>
</nav>
