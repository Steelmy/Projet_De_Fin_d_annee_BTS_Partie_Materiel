<div
  id="reference-modal"
  class="fixed inset-0 z-1000 hidden items-center justify-center p-4"
>
  <div
    class="absolute inset-0 bg-black/50 modal-backdrop"
    onclick="toggleReferenceModal(false)"
  ></div>
  <div
    class="bg-white p-8 border border-[#888] w-[90%] max-w-[600px] shadow-[0_4px_8px_0_rgba(0,0,0,0.2),0_6px_20px_0_rgba(0,0,0,0.19)] relative z-10 rounded-xl text-left overflow-y-auto custom-scrollbar"
    style="max-height: 90vh;"
  >
    <span
      class="close-modal text-[#aaa] float-right text-[28px] font-bold cursor-pointer"
      onclick="toggleReferenceModal(false)"
      >&times;</span
    >
    <h2 class="text-2xl font-bold mb-4 flex items-center gap-2">Gestion des Références</h2>

    <!-- Tabs -->
    <div class="flex gap-4 mb-6 border-b border-gray-200 font-medium">
        <button type="button" onclick="switchReferenceTab('add')" id="tab-ref-add" class="pb-2 border-b-2 border-custom-brandLight text-custom-brandLight">Ajouter une référence</button>
        <button type="button" onclick="switchReferenceTab('list')" id="tab-ref-list" class="pb-2 text-gray-500">Liste des références</button>
    </div>

    <!-- View: Ajout / Modif -->
    <div id="ref-view-add" class="space-y-6">
      <p id="ref-view-add-desc" class="text-sm text-gray-600">Ajoutez de nouvelles références au catalogue pour les rendre disponibles lors de l'ajout de matériel.</p>

      <form id="form_create_reference" class="space-y-4">
          <input type="hidden" id="ref_edit_id" value="" />
          <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Type <span class="text-red-500">*</span></label>
              <select id="ref_type_select" required class="w-full px-4 py-2 border-2 border-custom-border rounded-lg text-sm bg-white focus:outline-none focus:border-custom-brandLight focus:ring-4 focus:ring-custom-brandLight/15">
                  <option value="">Sélectionner un type</option>
              </select>
              <input type="text" id="ref_type_new" placeholder="Saisir le nouveau type..." class="hidden w-full mt-2 px-4 py-2 border-2 border-custom-border rounded-lg text-sm bg-white focus:outline-none focus:border-custom-brandLight focus:ring-4 focus:ring-custom-brandLight/15" />
          </div>
          <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Sous-type</label>
              <select id="ref_sous_type_select" disabled class="w-full px-4 py-2 border-2 border-custom-border rounded-lg text-sm bg-white focus:outline-none focus:border-custom-brandLight focus:ring-4 focus:ring-custom-brandLight/15 disabled:bg-gray-100 disabled:text-gray-400">
                  <option value="">Sélectionner un sous-type</option>
              </select>
              <input type="text" id="ref_sous_type_new" placeholder="Saisir le nouveau sous-type..." class="hidden w-full mt-2 px-4 py-2 border-2 border-custom-border rounded-lg text-sm bg-white focus:outline-none focus:border-custom-brandLight focus:ring-4 focus:ring-custom-brandLight/15" />
              <p class="text-xs text-gray-500 mt-1">Laissez vide si non applicable.</p>
          </div>
          <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Nom (Modèle) <span class="text-red-500">*</span></label>
              <input type="text" id="ref_nom" required placeholder="Ex: Plat, Cruciforme..." class="w-full px-4 py-2 border-2 border-custom-border rounded-lg text-sm bg-white focus:outline-none focus:border-custom-brandLight focus:ring-4 focus:ring-custom-brandLight/15" />
          </div>
          <div class="pt-4 flex justify-between border-t border-gray-100">
              <button type="button" onclick="resetReferenceForm()" class="px-4 py-2 border border-red-200 text-red-600 rounded-lg">Réinitialiser</button>
              <div class="flex gap-3">
                  <button type="button" onclick="toggleReferenceModal(false)" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg">Annuler</button>
                  <button type="submit" class="px-6 py-2 bg-custom-brandLight text-white font-semibold rounded-lg shadow-md">Enregistrer</button>
              </div>
          </div>
          <div id="ref_message" class="text-sm text-center mt-2 hidden font-medium"></div>
      </form>
    </div>

    <!-- View: Liste -->
    <div id="ref-view-list" class="space-y-4 hidden">
        <div class="overflow-y-auto custom-scrollbar border border-gray-200 rounded-lg max-h-[300px]">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead class="bg-gray-100 text-gray-700 sticky top-0 z-10 shadow-sm">
                    <tr>
                        <th class="p-3 w-10 text-center border-b border-gray-200"><input type="checkbox" id="ref-check-all" onchange="toggleAllReferences(this)" class="rounded border-gray-300 text-custom-brandLight focus:ring-custom-brandLight/20 cursor-pointer w-4 h-4"></th>
                        <th class="p-3 font-semibold border-b border-gray-200">Type</th>
                        <th class="p-3 font-semibold border-b border-gray-200">Sous-type</th>
                        <th class="p-3 font-semibold border-b border-gray-200">Nom</th>
                    </tr>
                </thead>
                <tbody id="ref-table-body" class="divide-y divide-gray-100 bg-white">
                    <tr><td colspan="4" class="p-8 text-center text-gray-500">Chargement des références...</td></tr>
                </tbody>
            </table>
        </div>
        <div id="ref-list-message" class="text-sm font-medium mt-2 hidden whitespace-pre-wrap"></div>

        <div class="flex justify-between items-center bg-gray-50 p-4 rounded-lg border border-gray-200 mt-4">
            <span class="text-sm text-gray-600 font-medium whitespace-nowrap"><span id="ref-selected-count">0</span> référence(s) sélectionnée(s)</span>
            <div class="flex gap-2">
                <button type="button" id="btn-edit-ref" onclick="editSelectedReference()" class="px-3 py-2 text-sm font-semibold rounded-lg shadow-sm text-white cursor-not-allowed hidden" style="background-color: #6b7280;" disabled>Modifier la référence</button>
                <button type="button" id="btn-delete-refs" onclick="deleteSelectedReferences()" class="px-3 py-2 text-sm font-semibold rounded-lg shadow-sm text-white cursor-not-allowed" style="background-color: #6b7280;" disabled>Supprimer la sélection</button>
            </div>
        </div>
    </div>
  </div>
</div>
