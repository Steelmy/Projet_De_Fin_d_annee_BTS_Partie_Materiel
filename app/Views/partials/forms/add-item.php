<form
  id="form_ajout"
  class="flex flex-wrap items-center gap-2.5 w-full hidden"
>
  <div class="flex items-center gap-1.5">
    <button
      type="button"
      id="btn_caisse_ajout"
      class="px-4 py-2 border-2 border-custom-border bg-white text-gray-800 rounded-lg font-semibold text-sm shadow-input hover:bg-gray-50 transition-all duration-300"
    >
      Caisse
    </button>
  </div>
  <div class="box-input-wrapper">
    <select
      id="type_materiel_ajout"
      class="w-full px-4 py-3 border-2 border-custom-border rounded-full text-sm transition-all duration-300 bg-white shadow-input focus:outline-none focus:border-custom-brandLight focus:ring-4 focus:ring-custom-brandLight/15"
    >
      <option value="">Sélectionner un type</option>
    </select>
  </div>
  <div class="box-input-wrapper">
    <select
      id="sous_type_materiel_ajout"
      disabled
      class="w-full px-4 py-3 border-2 border-custom-border rounded-full text-sm transition-all duration-300 bg-white shadow-input focus:outline-none focus:border-custom-brandLight focus:ring-4 focus:ring-custom-brandLight/15 disabled:bg-gray-100 disabled:text-gray-400"
    >
      <option value="">Sélectionner un sous-type</option>
    </select>
  </div>
  <div class="box-input-wrapper">
    <select
      id="nom_materiel_ajout"
      disabled
      class="w-full px-4 py-3 border-2 border-custom-border rounded-full text-sm transition-all duration-300 bg-white shadow-input focus:outline-none focus:border-custom-brandLight focus:ring-4 focus:ring-custom-brandLight/15 disabled:bg-gray-100 disabled:text-gray-400"
    >
      <option value="">Sélectionner un nom</option>
    </select>
  </div>
  <button
    type="button"
    onclick="window.toggleReferenceModal(true)"
    class="px-4 py-3 border-2 border-custom-border bg-white text-gray-700 rounded-full font-semibold text-sm shadow-input hover:bg-gray-50 hover:text-custom-brandLight hover:border-custom-brandLight transition-all duration-300 flex items-center gap-2"
    title="Gérer les Types, Sous-types et Noms"
  >
    <i data-lucide="settings" class="w-4 h-4"></i> Réf.
  </button>
  <input
    type="number"
    id="nombre_materiel"
    value="1"
    min="1"
    class="w-20 px-4 py-3 border-2 border-custom-border rounded-full text-sm transition-all duration-300 bg-white shadow-input focus:outline-none focus:border-custom-brandLight focus:ring-4 focus:ring-custom-brandLight/15"
    placeholder="Qté"
  />
  <button
    type="submit"
    class="px-6 py-3 border-2 border-custom-brandLight bg-custom-brandLight text-white rounded-full font-semibold text-sm shadow-input hover:shadow-[0_4px_15px_rgba(182,160,113,0.4)] hover:-translate-y-0.5 transition-all duration-300"
  >
    Ajouter
  </button>
  <button
    type="button"
    class="btn-reset px-6 py-3 border-2 border-custom-border bg-white text-gray-800 rounded-full font-semibold text-sm shadow-input hover:bg-gray-50 transition-all duration-300"
    data-form="form_ajout"
  >
    Réinitialiser
  </button>
</form>
