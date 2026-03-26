<div
  id="filters_consultation"
  class="flex flex-wrap items-center gap-2.5 w-full hidden"
>
  <div
    id="filter_section"
    class="flex flex-wrap items-center gap-2.5 w-full"
  >
    <div class="box-input-wrapper">
      <input
        type="text"
        id="code_barre_consultation"
        placeholder="Filtre Code-barre..."
        class="w-full px-4 py-3 border-2 border-custom-border rounded-full text-sm transition-all duration-300 bg-white shadow-input focus:outline-none focus:border-custom-brandLight focus:ring-4 focus:ring-custom-brandLight/15"
      />
      <div
        id="barcode_suggestions_consultation"
        class="autocomplete-suggestions"
      ></div>
    </div>
    <div class="box-input-wrapper">
      <select
        id="type_materiel_consultation"
        class="w-full px-4 py-3 border-2 border-custom-border rounded-full text-sm transition-all duration-300 bg-white shadow-input focus:outline-none focus:border-custom-brandLight focus:ring-4 focus:ring-custom-brandLight/15"
      >
        <option value="">Tous les types</option>
      </select>
    </div>
    <div class="box-input-wrapper">
      <select
        id="sous_type_materiel_consultation"
        disabled
        class="w-full px-4 py-3 border-2 border-custom-border rounded-full text-sm transition-all duration-300 bg-white shadow-input focus:outline-none focus:border-custom-brandLight focus:ring-4 focus:ring-custom-brandLight/15 disabled:bg-gray-100 disabled:text-gray-400"
      >
        <option value="">Tous les sous-types</option>
      </select>
    </div>
    <div class="box-input-wrapper">
      <select
        id="nom_materiel_consultation"
        disabled
        class="w-full px-4 py-3 border-2 border-custom-border rounded-full text-sm transition-all duration-300 bg-white shadow-input focus:outline-none focus:border-custom-brandLight focus:ring-4 focus:ring-custom-brandLight/15 disabled:bg-gray-100 disabled:text-gray-400"
      >
        <option value="">Tous les noms</option>
      </select>
    </div>
    <button
      type="button"
      onclick="resetFiltersConsultation()"
      class="px-6 py-3 border-2 border-[#ccc] bg-white text-gray-800 rounded-full font-semibold text-sm shadow-input hover:bg-gray-50 active:bg-gray-100 transition-all duration-300"
    >
      Effacer
    </button>
    <input
      type="checkbox"
      id="toggle_caisse_consultation"
      class="hidden"
    />
  </div>
</div>
