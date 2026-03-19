<form
  id="form_modification"
  class="flex flex-wrap items-center gap-2.5 w-full hidden"
>
<!--
  <div class="flex items-center gap-1.5">
    <button
      type="button"
      id="btn_caisse_modification"
      class="px-4 py-2 border-2 border-custom-border bg-white text-gray-800 rounded-lg font-semibold text-sm shadow-input hover:bg-gray-50 transition-all duration-300"
    >
      Caisse
    </button>
  </div>
-->
  <div class="box-input-wrapper">
    <input
      type="text"
      id="id_materiel"
      placeholder="Scan ID..."
      required
      class="w-full px-4 py-3 border-2 border-custom-border rounded-full text-sm transition-all duration-300 bg-white shadow-input focus:outline-none focus:border-custom-brandLight focus:ring-4 focus:ring-custom-brandLight/15"
    />
  </div>
  <select
    id="etat"
    class="px-4 py-3 border-2 border-custom-border rounded-full text-sm transition-all duration-300 bg-white shadow-input focus:outline-none focus:border-custom-brandLight focus:ring-4 focus:ring-custom-brandLight/15"
    style="width: 140px"
  >
    <option value="disponible">Disponible</option>
    <option value="réservé">Réservé</option>
    <option value="emprunté">Emprunté</option>
  </select>
  <div class="box-input-wrapper">
    <input
      type="text"
      id="reserveur_emprunteur"
      placeholder="Utilisateur..."
      autocomplete="off"
      class="w-full px-4 py-3 border-2 border-custom-border rounded-full text-sm transition-all duration-300 bg-white shadow-input focus:outline-none focus:border-custom-brandLight focus:ring-4 focus:ring-custom-brandLight/15"
    />
    <div
      id="autocomplete_suggestions"
      class="autocomplete-suggestions"
    ></div>
    <input type="hidden" id="reserveur_emprunteur_id" />
  </div>
  <button
    type="submit"
    class="px-6 py-3 border-2 border-custom-brandLight bg-custom-brandLight text-white rounded-full font-semibold text-sm shadow-input hover:shadow-[0_4px_15px_rgba(182,160,113,0.4)] hover:-translate-y-0.5 transition-all duration-300"
  >
    Enregistrer
  </button>
  <button
    type="button"
    class="btn-reset ml-2.5 px-6 py-3 border-2 border-custom-border bg-white text-gray-800 rounded-full font-semibold text-sm shadow-input hover:bg-gray-50 transition-all duration-300"
    data-form="form_modification"
  >
    Réinitialiser
  </button>
</form>
