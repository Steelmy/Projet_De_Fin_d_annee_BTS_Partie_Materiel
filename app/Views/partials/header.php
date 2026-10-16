<div class="bg-white py-[15px] px-4 sm:px-[30px] rounded-[12px] mb-[20px] flex flex-wrap lg:flex-nowrap justify-between items-center gap-4 shadow-[0_4px_6px_rgba(0,0,0,0.05)]">
  <div class="flex items-center gap-4">
    <button
      onclick="toggleSidebar()"
      class="shrink-0 bg-[#f3f4f6] text-black border-none w-10 h-10 rounded-lg cursor-pointer text-xl flex items-center justify-center shadow-sm"
      aria-label="Ouvrir le menu"
    >
      ☰
    </button>
    <div>
      <h1 class="text-xl sm:text-2xl font-bold text-gray-800 m-0">Gestion du matériel</h1>
      <p class="text-sm text-gray-500 m-0 font-light">Administration et inventaire global</p>
    </div>
  </div>

  <div class="flex flex-wrap lg:flex-nowrap items-center gap-2 sm:gap-3 w-full lg:w-auto">
    <button
      type="button"
      id="btn-open-restitution"
      onclick="window.toggleRestitutionModal(true)"
      class="flex-auto lg:flex-none justify-center whitespace-nowrap bg-white text-gray-700 py-2 px-4 border border-gray-300 rounded-[20px] font-bold text-sm sm:text-base shadow-sm cursor-pointer flex items-center gap-2"
      title="Restituer un objet emprunté"
    >
      Restituer un objet
    </button>
    <button
      type="button"
      onclick="window.toggleReferenceModal(true)"
      class="flex-auto lg:flex-none justify-center whitespace-nowrap bg-white text-gray-700 py-2 px-4 border border-gray-300 rounded-[20px] font-bold text-sm sm:text-base shadow-sm cursor-pointer flex items-center gap-2"
      title="Gérer les Références"
    >
      Gestion des références
    </button>
    <button
      id="btn-open-barcode"
      class="flex-auto lg:flex-none justify-center whitespace-nowrap bg-custom-brandLight text-white py-2 px-4 rounded-[20px] font-bold text-sm sm:text-base shadow-sm border-none cursor-pointer flex items-center"
    >
      Impression de codes-barres
    </button>
  </div>
</div>
