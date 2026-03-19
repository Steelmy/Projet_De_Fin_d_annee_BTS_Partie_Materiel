<nav class="sidebar fixed left-0 top-0 h-screen w-[280px] bg-linear-to-b from-gray-800 to-gray-900 text-white z-50 transition-all duration-300" id="sidebar">
    <div class="px-5 py-[30px] text-center bg-gray-900 border-b border-gray-700 relative">
        <button class="close-sidebar-btn absolute top-3 right-3 text-gray-400 hover:text-white text-2xl" onclick="toggleSidebar()">&times;</button>
        <h2 class="text-[#b8a274] font-bold text-2xl">INVENTAIRE</h2>
    </div>
    <ul class="list-none py-5 m-0">
        <li class="h-[48px] px-[25px] cursor-pointer border-l-4 border-transparent flex items-center gap-2 hover:bg-gray-700 transition-colors"><a href="http://localhost/Projet-Partie-Alban/dashboard.php" class="text-white no-underline flex items-center gap-2 w-full font-bold">Dashboard</a></li>
        <li class="h-[48px] px-[25px] cursor-pointer border-l-4 border-transparent flex items-center gap-2 hover:bg-gray-700 transition-colors"><a href="http://localhost/Projet-Partie-Alban/dashboard.php?page=users" class="text-white no-underline flex items-center gap-2 w-full font-bold">Utilisateurs</a></li>
        <li class="h-[48px] px-[25px] cursor-pointer border-l-4 border-[#b8a274] bg-gray-700 flex items-center gap-2"><a href="http://localhost/Projet%20de%20fin%20d'ann%C3%A9e%20BTS/index.php" class="text-white no-underline flex items-center gap-2 w-full font-bold">Gestion du matériel</a></li>
        <li onclick="toggleModal(true)" class="h-[48px] px-[25px] cursor-pointer border-l-4 border-transparent flex items-center gap-2 hover:bg-gray-700 transition-colors text-[#b8a274] font-bold">Nouvel Emprunt</li>
    </ul>
</nav>
