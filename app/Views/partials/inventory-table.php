<div class="bg-white rounded-[12px] p-[25px] shadow-[0_4px_6px_rgba(0,0,0,0.05)] overflow-x-auto w-full">
  <div class="flex items-center gap-1.5 mb-4">
<!--
    <input
      type="checkbox"
      id="toggle_caisse_view"
      class="w-auto border-custom-border rounded accent-custom-brandLight"
    />
    <label
      for="toggle_caisse_view"
      class="cursor-pointer font-medium select-none"
      >Vue Caisses</label
    >
    -->
    <button
      onclick="if(window.refreshInventory) window.refreshInventory();"
      class="ml-auto px-4 py-2 border-2 border-custom-border bg-white text-gray-800 rounded-lg font-semibold text-sm shadow-input flex items-center gap-2 cursor-pointer"
      title="Rafraîchir les données"
    >
      <i data-lucide="rotate-cw"></i> Rafraîchir
    </button>
  </div>
  <div id="full_inventory">
    <table id="inventory_table" class="w-full border-collapse bg-white mt-[15px]">
      <thead
        class="bg-white text-gray-500 border-b-2 border-gray-200 select-none"
      >
        <tr>
          <th
            class="p-3 text-left font-normal tracking-wide uppercase text-xs sticky top-0 z-10 border-b-2 border-gray-200"
          >
            Code-barre
          </th>
          <th
            class="p-3 text-left font-normal tracking-wide uppercase text-xs sticky top-0 z-10 border-b-2 border-gray-200 cursor-pointer"
            onclick="window.sortInventory('Type')"
          >
            Type
            <span id="sort_icon_Type" class="text-xs opacity-50 ml-1">↕</span>
          </th>
          <th
            class="p-3 text-left font-normal tracking-wide uppercase text-xs sticky top-0 z-10 border-b-2 border-gray-200 cursor-pointer"
            onclick="window.sortInventory('Sous_type')"
          >
            Sous-type
            <span id="sort_icon_Sous_type" class="text-xs opacity-50 ml-1">↕</span>
          </th>
          <th
            class="p-3 text-left font-normal tracking-wide uppercase text-xs sticky top-0 z-10 border-b-2 border-gray-200 cursor-pointer"
            onclick="window.sortInventory('Nom')"
          >
            Nom
            <span id="sort_icon_Nom" class="text-xs opacity-50 ml-1">↕</span>
          </th>
          <th
            class="p-3 text-left font-normal tracking-wide uppercase text-xs sticky top-0 z-10 border-b-2 border-gray-200 cursor-pointer"
            onclick="window.sortInventory('Etat')"
          >
            État
            <span id="sort_icon_Etat" class="text-xs opacity-50 ml-1">↕</span>
          </th>
          <th
            class="p-3 text-left font-normal tracking-wide uppercase text-xs sticky top-0 z-10 border-b-2 border-gray-200 cursor-pointer"
            onclick="window.sortInventory('Utilisateur')"
          >
            Utilisateur
            <span id="sort_icon_Utilisateur" class="text-xs opacity-50 ml-1">↕</span>
          </th>
<!--
          <th
            class="p-4.5 font-semibold tracking-wide uppercase text-sm sticky top-0 z-10 border-b-2 border-custom-brandLight text-center cursor-pointer"
            onclick="window.sortInventory('Nom_caisse')"
          >
            Caisse
            <span id="sort_icon_Nom_caisse" class="text-xs opacity-50 ml-1">↕</span>
          </th>
-->
        </tr>
      </thead>
      <tbody
        id="inventory_tbody"
        class="[&>tr]:border-l-4 [&>tr]:border-transparent [&>tr>td]:p-3 [&>tr>td]:border-b [&>tr>td]:border-gray-200 [&>tr>td]:text-sm [&>tr>td]:text-left [&>tr>td]:align-middle"
      ></tbody>
    </table>
    <div class="table-footer text-slate-500 text-sm">
      <div></div>
      <div class="table-footer-center">
        <button
          id="btn_prev_page"
          class="px-3 py-1 bg-white border border-[#ccc] rounded-lg cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
          onclick="window.changePage(-1)"
        >
          &larr; Précédent
        </button>
        <span id="page_info" class="font-medium">Page 1 / 1</span>
        <button
          id="btn_next_page"
          class="px-3 py-1 bg-white border border-[#ccc] rounded-lg cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
          onclick="window.changePage(1)"
        >
          Suivant &rarr;
        </button>
      </div>
      <div class="table-footer-right">
        <button
          id="btn_download_pdf"
          class="px-3 py-1.5 bg-white border border-[#ccc] rounded-lg cursor-pointer shadow-input font-inherit text-gray-800"
        >
          Télécharger PDF
        </button>
        <span class="text-[1.1em] font-medium">
          Total:
          <span id="inventory_total" class="text-gray-800 font-bold">0</span>
        </span>
      </div>
    </div>
  </div>

  <div
    id="caisses_view"
    class="grid grid-cols-[repeat(auto-fill,minmax(280px,1fr))] gap-5 py-5 px-8 hidden"
  ></div>
</div>
