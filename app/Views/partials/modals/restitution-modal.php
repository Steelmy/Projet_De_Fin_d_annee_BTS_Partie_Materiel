<div
  id="restitution-modal"
  class="fixed inset-0 z-1000 hidden items-center justify-center p-4"
>
  <div
    class="absolute inset-0 bg-black/50 modal-backdrop"
    onclick="toggleRestitutionModal(false)"
  ></div>
  <div
    class="bg-white p-8 border border-[#888] w-[90%] max-w-[550px] shadow-[0_4px_8px_0_rgba(0,0,0,0.2),0_6px_20px_0_rgba(0,0,0,0.19)] relative z-10 rounded-xl text-left overflow-y-auto custom-scrollbar"
    style="max-height: 90vh;"
  >
    <span
      class="close-modal text-[#aaa] float-right text-[28px] font-bold cursor-pointer"
      onclick="toggleRestitutionModal(false)"
      >&times;</span
    >
    <h2 class="text-2xl font-bold mb-4 flex items-center gap-2">Restituer un objet</h2>

    <p class="text-sm text-gray-600 mb-6">Scannez ou saisissez le code-barre de l'objet emprunté ou réservé à restituer. Seuls les objets empruntés/réservés sont affichés.</p>

    <form id="form_restitution" class="space-y-4">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Code-barre <span class="text-red-500">*</span></label>
        <div class="relative">
          <input
            type="text"
            id="restitution_code_barre"
            placeholder="Scannez ou saisissez le code-barre..."
            required
            autocomplete="off"
            class="w-full px-4 py-3 border-2 border-custom-border rounded-lg text-sm bg-white shadow-input focus:outline-none focus:border-custom-brandLight focus:ring-4 focus:ring-custom-brandLight/15"
          />
        </div>
      </div>

      <!-- Info panel shown after barcode lookup -->
      <div id="restitution_info" class="hidden bg-gray-50 rounded-lg p-4 border border-gray-200 space-y-2">
        <div class="flex items-center gap-2 text-sm">
          <span class="font-medium text-gray-500 w-20">Type :</span>
          <span id="restitution_type" class="text-gray-800 font-semibold"></span>
        </div>
        <div class="flex items-center gap-2 text-sm">
          <span class="font-medium text-gray-500 w-20">Sous-type :</span>
          <span id="restitution_sous_type" class="text-gray-800 font-semibold"></span>
        </div>
        <div class="flex items-center gap-2 text-sm">
          <span class="font-medium text-gray-500 w-20">Nom :</span>
          <span id="restitution_nom" class="text-gray-800 font-semibold"></span>
        </div>
        <div class="flex items-center gap-2 text-sm">
          <span class="font-medium text-gray-500 w-20">État :</span>
          <span id="restitution_etat" class="text-gray-800 font-semibold"></span>
        </div>
        <div class="flex items-center gap-2 text-sm">
          <span class="font-medium text-gray-500 w-20">Utilisateur :</span>
          <span id="restitution_utilisateur" class="text-gray-800 font-semibold"></span>
        </div>
      </div>

      <div class="pt-4 flex justify-end gap-3 border-t border-gray-100">
        <button type="button" onclick="toggleRestitutionModal(false)" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg">Annuler</button>
        <button type="submit" id="btn_restitution_submit" class="px-6 py-2 bg-custom-brandLight text-white font-semibold rounded-lg shadow-md" disabled>Restituer</button>
      </div>

      <div id="restitution_message" class="text-sm text-center mt-2 hidden font-medium"></div>
    </form>
  </div>
</div>
