<div
  id="barcode-modal"
  class="fixed inset-0 z-1000 hidden items-center justify-center p-4"
>
  <div
    class="absolute inset-0 bg-black/50 backdrop-blur-sm modal-backdrop"
  ></div>
  <div
    class="bg-white p-5 border border-[#888] w-[80%] shadow-[0_4px_8px_0_rgba(0,0,0,0.2),0_6px_20px_0_rgba(0,0,0,0.19)] relative z-10 rounded-xl text-center"
  >
    <span
      class="close-modal text-[#aaa] float-right text-[28px] font-bold cursor-pointer transition-colors duration-200 hover:text-black focus:text-black"
      id="close-barcode-modal"
      >&times;</span
    >
    <h2 class="text-2xl font-bold mb-4">Réimpression Codes-barres</h2>

    <!-- Filtres -->
    <div class="flex flex-wrap items-center justify-center gap-3 my-4">
      <select
        id="barcode-filter-type"
        class="px-4 py-2 border-2 border-custom-border rounded-full text-sm bg-white shadow-input focus:outline-none focus:border-custom-brandLight transition-all duration-300"
      >
        <option value="">Tous les types</option>
      </select>
      <select
        id="barcode-filter-sous-type"
        disabled
        class="px-4 py-2 border-2 border-custom-border rounded-full text-sm bg-white shadow-input focus:outline-none focus:border-custom-brandLight transition-all duration-300 disabled:bg-gray-100 disabled:text-gray-400"
      >
        <option value="">Tous les sous-types</option>
      </select>
      <select
        id="barcode-filter-nom"
        disabled
        class="px-4 py-2 border-2 border-custom-border rounded-full text-sm bg-white shadow-input focus:outline-none focus:border-custom-brandLight transition-all duration-300 disabled:bg-gray-100 disabled:text-gray-400"
      >
        <option value="">Tous les noms</option>
      </select>
      <button
        id="btn-load-barcodes"
        class="px-5 py-2 border-2 border-custom-brandLight bg-custom-brandLight text-white rounded-full font-semibold text-sm shadow-input hover:brightness-90 active:brightness-75 transition-all duration-300"
      >
        Ajouter
      </button>
      <button
        id="btn-clear-print-zone"
        type="button"
        class="px-5 py-2 border-2 border-custom-border bg-white text-gray-800 rounded-full font-semibold text-sm shadow-input hover:bg-gray-50 active:bg-gray-100 transition-all duration-300"
      >
        Vider
      </button>
    </div>

    <div
      id="barcode_table_container"
      class="mt-4 text-left"
    ></div>

    <p id="barcode-count" class="text-sm text-gray-500 my-4">0 code(s) à imprimer</p>

    <button
      id="btn-print"
      style="display: none;"
      class="px-6 py-3 border-2 border-custom-brandLight bg-custom-brandLight text-white rounded-full font-semibold text-sm shadow-input hover:brightness-90 active:brightness-75 transition-all duration-300 mb-2"
    >
      Imprimer la sélection
    </button>
    <div
      id="print-zone"
      style="display: none;"
      class="flex flex-wrap justify-center gap-4 mt-2 max-h-[300px] overflow-y-auto custom-scrollbar border-t border-gray-100 pt-4"
    ></div>
  </div>
</div>
