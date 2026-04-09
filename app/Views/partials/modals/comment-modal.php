<div
  id="comment-modal"
  class="fixed inset-0 z-1000 hidden items-center justify-center p-4"
>
  <div
    class="absolute inset-0 bg-black/50 modal-backdrop"
    onclick="closeCommentModal()"
  ></div>
  <div
    class="bg-white p-8 border border-[#888] w-[90%] max-w-[550px] shadow-[0_4px_8px_0_rgba(0,0,0,0.2),0_6px_20px_0_rgba(0,0,0,0.19)] relative z-10 rounded-xl text-left overflow-y-auto custom-scrollbar"
    style="max-height: 90vh;"
  >
    <span
      class="close-modal text-[#aaa] float-right text-[28px] font-bold cursor-pointer"
      onclick="closeCommentModal()"
      >&times;</span
    >
    <h2 id="comment-modal-title" class="text-2xl font-bold mb-5 flex items-center gap-2"></h2>

    <!-- Section commentaire élève -->
    <div id="comment-user-section" class="mb-5 hidden">
      <label class="block text-sm font-semibold text-gray-700 mb-2">Commentaire de l'élève</label>
      <div class="relative">
        <div
          id="comment-user-text"
          class="w-full px-4 py-3 border-2 border-custom-border rounded-lg text-sm bg-gray-50 text-gray-700 min-h-[80px] whitespace-pre-wrap"
        ></div>
        <button
          type="button"
          id="btn-delete-user-comment"
          onclick="deleteUserComment()"
          class="mt-2 px-4 py-2 border border-red-200 text-red-600 rounded-lg text-sm font-medium"
          title="Supprimer le commentaire de l'élève"
        >
          Supprimer le commentaire élève
        </button>
      </div>
    </div>

    <!-- Section commentaire admin -->
    <div class="mb-5">
      <label class="block text-sm font-semibold text-gray-700 mb-2">Commentaire admin</label>
      <textarea
        id="comment-admin-textarea"
        rows="4"
        placeholder="Saisissez votre commentaire..."
        class="w-full px-4 py-3 border-2 border-custom-border rounded-lg text-sm bg-white focus:outline-none focus:border-custom-brandLight focus:ring-4 focus:ring-custom-brandLight/15 resize-y"
      ></textarea>
      <button
        type="button"
        id="btn-delete-admin-comment"
        onclick="deleteAdminComment()"
        class="mt-2 px-4 py-2 border border-red-200 text-red-600 rounded-lg text-sm font-medium hidden"
        title="Supprimer votre commentaire admin"
      >
        Supprimer mon commentaire
      </button>
    </div>

    <!-- Actions -->
    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
      <button
        type="button"
        onclick="closeCommentModal()"
        class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg text-sm font-medium"
      >
        Annuler
      </button>
      <button
        type="button"
        id="btn-save-comment"
        onclick="saveComment()"
        class="px-6 py-2 bg-custom-brandLight text-white font-semibold rounded-lg shadow-md text-sm"
      >
        Enregistrer
      </button>
    </div>

    <!-- Message -->
    <div id="comment-message" class="text-sm text-center mt-3 hidden font-medium"></div>
  </div>
</div>
