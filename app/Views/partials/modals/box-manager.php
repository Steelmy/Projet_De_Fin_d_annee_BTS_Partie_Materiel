<div
  id="modal_caisse"
  class="fixed inset-0 z-1000 hidden items-center justify-center p-4"
>
  <div
    class="absolute inset-0 bg-black/50 backdrop-blur-sm modal-backdrop"
  ></div>

  <div
    class="bg-white p-8 border border-[#888] w-[80%] max-w-[700px] shadow-[0_4px_8px_0_rgba(0,0,0,0.2),0_6px_20px_0_rgba(0,0,0,0.19)] relative z-10 rounded-xl"
  >
    <span
      class="close-modal text-[#aaa] float-right text-[28px] font-bold cursor-pointer transition-colors duration-200 hover:text-black focus:text-black absolute top-[15px] right-[20px] z-10001"
      id="close_modal_caisse"
      >&times;</span
    >
    <div id="modal_caisse_body" class="mt-5">
      <!-- Panel Ajout Caisse -->
      <div id="panel_ajout_caisse" class="hidden w-full pt-2.5">
        <form id="form_ajout_caisse" class="w-full">
          <h4 class="mb-2.5 text-lg font-semibold">Nouvelle Caisse</h4>
          <div class="flex gap-2.5 mb-2.5">
            <input
              type="text"
              id="nom_caisse_ajout"
              placeholder="Nom de la caisse"
              required
              class="flex-1 px-4 py-3 border-2 border-custom-border rounded-lg text-sm transition-all duration-300 bg-white shadow-input focus:outline-none focus:border-custom-brandLight focus:ring-4 focus:ring-custom-brandLight/15"
            />
            <button
              type="submit"
              class="px-6 py-3 border-2 border-custom-brandLight bg-custom-brandLight text-white rounded-lg font-semibold text-sm shadow-input hover:brightness-90 active:brightness-75 transition-all duration-300"
            >
              Créer
            </button>
            <button
              type="button"
              class="btn-reset px-6 py-3 border-2 border-custom-border bg-white text-gray-800 rounded-lg font-semibold text-sm shadow-input hover:bg-gray-50 active:bg-gray-100 transition-all duration-300"
              data-form="form_ajout_caisse"
            >
              Réinitialiser
            </button>
          </div>
          <div
            class="box-input-wrapper w-full relative min-w-[150px] flex-1 overflow-visible"
          >
            <input
              type="text"
              id="search_objets_ajout"
              placeholder="Scanner objets pour la caisse..."
              class="w-full px-4 py-3 border-2 border-custom-border rounded-lg text-sm transition-all duration-300 bg-white shadow-input focus:outline-none focus:border-custom-brandLight focus:ring-4 focus:ring-custom-brandLight/15"
            />
            <div
              id="objets_suggestions_ajout"
              class="autocomplete-suggestions"
            ></div>
          </div>
          <div
            id="objets_list_ajout"
            class="mt-2.5 p-2.5 border border-dashed border-[#ccc] min-h-[40px] rounded-lg bg-gray-50"
          ></div>
        </form>
      </div>

      <!-- Panel Suppression Caisse -->
      <div id="panel_suppression_caisse" class="hidden w-full pt-2.5">
        <form id="form_suppression_caisse" class="w-full">
          <div
            id="caisse_input_group_suppr"
            class="box-input-wrapper mb-4 relative min-w-[150px] flex-1 overflow-visible"
          >
            <input
              type="text"
              id="nom_caisse_suppr"
              placeholder="Nom caisse..."
              class="w-full px-4 py-3 border-2 border-custom-border rounded-lg text-sm transition-all duration-300 bg-white shadow-input focus:outline-none focus:border-custom-brandLight focus:ring-4 focus:ring-custom-brandLight/15"
            />
            <div class="autocomplete-suggestions"></div>
          </div>
          <p class="text-gray-600 mb-4">
            Sélectionnez la caisse à supprimer ci-dessus.
          </p>
          <div id="caisse_details_suppr" class="mb-4"></div>
          <div class="flex gap-2.5 mt-2.5">
            <button
              type="submit"
              class="px-6 py-3 border-2 border-custom-danger bg-linear-to-br from-custom-danger to-custom-dangerDark text-white rounded-lg font-semibold text-sm shadow-input hover:brightness-90 active:brightness-75 transition-all duration-300"
            >
              Confirmer Suppression
            </button>
            <button
              type="button"
              class="btn-reset px-6 py-3 border-2 border-custom-border bg-white text-gray-800 rounded-lg font-semibold text-sm shadow-input hover:bg-gray-50 active:bg-gray-100 transition-all duration-300"
              data-form="form_suppression_caisse"
            >
              Réinitialiser
            </button>
          </div>
        </form>
      </div>

      <!-- Panel Modification Caisse -->
      <div id="panel_modification_caisse" class="hidden w-full pt-2.5">
        <form id="form_modification_caisse" class="w-full">
          <div
            class="box-input-wrapper mb-2.5 relative min-w-[150px] flex-1 overflow-visible"
          >
            <input
              type="text"
              id="nom_caisse_modif"
              placeholder="Rechercher caisse..."
              class="w-full px-4 py-3 border-2 border-custom-border rounded-lg text-sm transition-all duration-300 bg-white shadow-input focus:outline-none focus:border-custom-brandLight focus:ring-4 focus:ring-custom-brandLight/15"
            />
            <div class="autocomplete-suggestions"></div>
          </div>
          <div id="caisse_details_modif" class="hidden">
            <div class="flex gap-2.5 mb-2.5">
              <input
                type="text"
                id="nouveau_nom_caisse"
                placeholder="Nouveau nom"
                class="flex-1 px-4 py-3 border-2 border-custom-border rounded-lg text-sm transition-all duration-300 bg-white shadow-input focus:outline-none focus:border-custom-brandLight focus:ring-4 focus:ring-custom-brandLight/15"
              />
              <select
                id="etat_caisse_modif"
                class="px-4 py-3 border-2 border-custom-border rounded-lg text-sm transition-all duration-300 bg-white shadow-input focus:outline-none focus:border-custom-brandLight focus:ring-4 focus:ring-custom-brandLight/15"
              >
                <option value="disponible">Disponible</option>
                <option value="réservé">Réservé</option>
                <option value="emprunté">Emprunté</option>
              </select>
            </div>
            <div
              class="box-input-wrapper mb-2.5 relative min-w-[150px] flex-1 overflow-visible"
            >
              <input
                type="text"
                id="utilisateur_caisse_modif"
                placeholder="Utilisateur..."
                class="w-full px-4 py-3 border-2 border-custom-border rounded-lg text-sm transition-all duration-300 bg-white shadow-input focus:outline-none focus:border-custom-brandLight focus:ring-4 focus:ring-custom-brandLight/15"
              />
              <input type="hidden" id="utilisateur_caisse_modif_id" />
            </div>

            <div
              class="box-input-wrapper relative min-w-[150px] flex-1 overflow-visible"
            >
              <input
                type="text"
                id="search_objets_modif"
                placeholder="Scanner objets..."
                class="w-full px-4 py-3 border-2 border-custom-border rounded-lg text-sm transition-all duration-300 bg-white shadow-input focus:outline-none focus:border-custom-brandLight focus:ring-4 focus:ring-custom-brandLight/15"
              />
              <div
                id="objets_suggestions_modif"
                class="autocomplete-suggestions"
              ></div>
            </div>
            <div id="caisse_contenu_modif" class="my-2.5"></div>
            <div id="objets_table_container_modif"></div>

            <div class="flex gap-2.5 mt-2.5">
              <button
                type="submit"
                class="px-6 py-3 border-2 border-custom-brandLight bg-custom-brandLight text-white rounded-lg font-semibold text-sm shadow-input hover:brightness-90 active:brightness-75 transition-all duration-300"
              >
                Sauvegarder Caisse
              </button>
              <button
                type="button"
                class="btn-reset px-6 py-3 border-2 border-custom-border bg-white text-gray-800 rounded-lg font-semibold text-sm shadow-input hover:bg-gray-50 active:bg-gray-100 transition-all duration-300"
                data-form="form_modification_caisse"
              >
                Réinitialiser
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
