// Gestion de la modale "Caisse"

document.addEventListener("DOMContentLoaded", () => {
  const modalCaisse = document.getElementById("modal_caisse");
  const closeBtnCaisse = document.getElementById("close_modal_caisse");

  const panelAjoutCaisse = document.getElementById("panel_ajout_caisse");
  const panelSuppressionCaisse = document.getElementById(
    "panel_suppression_caisse",
  );
  const panelModificationCaisse = document.getElementById(
    "panel_modification_caisse",
  );

  const btnAjout = document.getElementById("btn_caisse_ajout");
  const btnSuppr = document.getElementById("btn_caisse_suppression");
  const btnModif = document.getElementById("btn_caisse_modification");

  function hideAllPanels() {
    if (panelAjoutCaisse) panelAjoutCaisse.classList.add("hidden");
    if (panelSuppressionCaisse) panelSuppressionCaisse.classList.add("hidden");
    if (panelModificationCaisse)
      panelModificationCaisse.classList.add("hidden");
  }

  function openCaisseModal(panelToShow) {
    if (!modalCaisse) return;
    hideAllPanels();
    if (panelToShow) panelToShow.classList.remove("hidden");
    modalCaisse.classList.remove("hidden");
    modalCaisse.style.display = "flex";
    document.body.style.overflow = "hidden";
  }

  function closeCaisseModal() {
    if (modalCaisse) {
      modalCaisse.style.display = "none";
      modalCaisse.classList.add("hidden");
      document.body.style.overflow = "";
    }
  }

  if (closeBtnCaisse) {
    closeBtnCaisse.addEventListener("click", closeCaisseModal);
  }

  window.addEventListener("click", (event) => {
    if (event.target === modalCaisse) {
      closeCaisseModal();
    }
  });

  if (btnAjout) {
    btnAjout.addEventListener("click", () => openCaisseModal(panelAjoutCaisse));
  }

  if (btnSuppr) {
    btnSuppr.addEventListener("click", () =>
      openCaisseModal(panelSuppressionCaisse),
    );
  }

  if (btnModif) {
    btnModif.addEventListener("click", () =>
      openCaisseModal(panelModificationCaisse),
    );
  }
});

// IMPORTANT : Les fonctions de réinitialisation resetForm... sont déjà définies dans add_caisse.js, update_caisse.js etc. ou doivent l'être.
// caisse_toggle.js en définissait certaines, vérifions si on doit les garder.
// add_caisse.js définit window.resetFormAjoutCaisse.
// update_caisse.js définit window.resetFormModificationCaisse.
// delete_caisse.js ne semble pas définir resetFormSuppressionCaisse globalement ?
// Vérifions delete_caisse.js
