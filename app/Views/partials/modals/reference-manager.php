<div
  id="reference-modal"
  class="fixed inset-0 z-1000 hidden items-center justify-center p-4"
>
  <div
    class="absolute inset-0 bg-black/50 backdrop-blur-sm modal-backdrop"
    onclick="toggleReferenceModal(false)"
  ></div>
  <div
    class="bg-white p-8 border border-[#888] w-[90%] max-w-[600px] shadow-[0_4px_8px_0_rgba(0,0,0,0.2),0_6px_20px_0_rgba(0,0,0,0.19)] relative z-10 rounded-xl text-left"
  >
    <span
      class="close-modal text-[#aaa] float-right text-[28px] font-bold cursor-pointer transition-colors duration-200 hover:text-black focus:text-black"
      onclick="toggleReferenceModal(false)"
      >&times;</span
    >
    <h2 class="text-2xl font-bold mb-6 flex items-center gap-2"><i data-lucide="settings"></i> Gestion des Références</h2>

    <div class="space-y-6">
      <p class="text-sm text-gray-600">Ajoutez de nouvelles références au catalogue pour les rendre disponibles lors de l'ajout de matériel.</p>

      <form id="form_create_reference" class="space-y-4">
          <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Type <span class="text-red-500">*</span></label>
              <input type="text" id="ref_type" required placeholder="Ex: Câble, Ordinateur..." class="w-full px-4 py-2 border-2 border-custom-border rounded-lg text-sm bg-white focus:outline-none focus:border-custom-brandLight focus:ring-4 focus:ring-custom-brandLight/15" />
          </div>
          <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Sous-type</label>
              <input type="text" id="ref_sous_type" placeholder="Ex: Vidéo, Réseau, Portable..." class="w-full px-4 py-2 border-2 border-custom-border rounded-lg text-sm bg-white focus:outline-none focus:border-custom-brandLight focus:ring-4 focus:ring-custom-brandLight/15" />
              <p class="text-xs text-gray-500 mt-1">Laissez vide si non applicable.</p>
          </div>
          <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Nom (Modèle) <span class="text-red-500">*</span></label>
              <input type="text" id="ref_nom" required placeholder="Ex: HDMI 2m, Thinkpad T14..." class="w-full px-4 py-2 border-2 border-custom-border rounded-lg text-sm bg-white focus:outline-none focus:border-custom-brandLight focus:ring-4 focus:ring-custom-brandLight/15" />
          </div>
          <div class="pt-4 flex justify-between border-t border-gray-100">
              <button type="button" onclick="resetReferenceForm()" class="px-4 py-2 border border-red-200 text-red-600 rounded-lg hover:bg-red-50 active:bg-red-100 hover:border-red-300 transition-colors">Réinitialiser</button>
              <div class="flex gap-3">
                  <button type="button" onclick="toggleReferenceModal(false)" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 active:bg-gray-100 transition-colors">Annuler</button>
                  <button type="submit" class="px-6 py-2 bg-custom-brandLight text-white font-semibold rounded-lg shadow-md hover:brightness-90 active:brightness-75 transition-all">Enregistrer</button>
              </div>
          </div>
          <div id="ref_message" class="text-sm text-center mt-2 hidden font-medium"></div>
      </form>
    </div>
  </div>
</div>
