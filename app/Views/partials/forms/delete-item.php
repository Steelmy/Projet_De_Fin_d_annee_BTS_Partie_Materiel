<form
  id="form_suppression"
  class="flex flex-wrap items-center gap-2.5 w-full hidden"
>
<!--
  <div class="flex items-center gap-1.5">
    <button
      type="button"
      id="btn_caisse_suppression"
      class="px-4 py-2 border-2 border-custom-border bg-white text-gray-800 rounded-lg font-semibold text-sm shadow-input hover:bg-gray-50 transition-all duration-300"
    >
      Caisse
    </button>
  </div>
-->
  <div class="box-input-wrapper">
    <input
      type="text"
      id="id_materiel_suppr"
      placeholder="Scan Code-barre..."
      class="w-full px-4 py-3 border-2 border-custom-border rounded-full text-sm transition-all duration-300 bg-white shadow-input focus:outline-none focus:border-custom-brandLight focus:ring-4 focus:ring-custom-brandLight/15"
    />
  </div>
  <div class="box-input-wrapper">
    <select
      id="type_materiel_suppr"
      class="w-full px-4 py-3 border-2 border-custom-border rounded-full text-sm transition-all duration-300 bg-white shadow-input focus:outline-none focus:border-custom-brandLight focus:ring-4 focus:ring-custom-brandLight/15"
    >
      <option value="">Type...</option>
    </select>
  </div>
  <div class="box-input-wrapper">
    <select
      id="sous_type_materiel_suppr"
      disabled
      class="w-full px-4 py-3 border-2 border-custom-border rounded-full text-sm transition-all duration-300 bg-white shadow-input focus:outline-none focus:border-custom-brandLight focus:ring-4 focus:ring-custom-brandLight/15 disabled:bg-gray-100 disabled:text-gray-400"
    >
      <option value="">Sous-type...</option>
    </select>
  </div>
  <div class="box-input-wrapper">
    <select
      id="nom_materiel_suppr"
      disabled
      class="w-full px-4 py-3 border-2 border-custom-border rounded-full text-sm transition-all duration-300 bg-white shadow-input focus:outline-none focus:border-custom-brandLight focus:ring-4 focus:ring-custom-brandLight/15 disabled:bg-gray-100 disabled:text-gray-400"
    >
      <option value="">Nom...</option>
    </select>
  </div>
  <button
    type="submit"
    class="px-6 py-3 border-2 border-custom-danger bg-linear-to-br from-custom-danger to-custom-dangerDark text-white rounded-full font-semibold text-sm shadow-input hover:shadow-[0_4px_15px_rgba(239,68,68,0.4)] hover:-translate-y-0.5 transition-all duration-300"
  >
    Supprimer
  </button>
  <button
    type="button"
    class="btn-reset ml-2.5 px-6 py-3 border-2 border-custom-border bg-white text-gray-800 rounded-full font-semibold text-sm shadow-input hover:bg-gray-50 transition-all duration-300"
    data-form="form_suppression"
  >
    Réinitialiser
  </button>
</form>
