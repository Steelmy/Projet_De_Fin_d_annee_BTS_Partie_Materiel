/**
 * formActions.js
 * Gère les actions de réinitialisation des formulaires et le nettoyage des champs dépendants.
 */

document.addEventListener("DOMContentLoaded", () => {
  // --- 1. Gestion du bouton Réinitialiser ---
  const resetButtons = document.querySelectorAll(".btn-reset");

  resetButtons.forEach((btn) => {
    btn.addEventListener("click", (e) => {
      e.preventDefault();
      const formId = btn.getAttribute("data-form");
      const form = document.getElementById(formId);

      if (form) {
        resetForm(form);
      }
    });
  });

  function resetForm(form) {
    // 1. Sauvegarder l'état de la checkbox Caisse si elle existe
    let toggleCaisse = null;
    let wasChecked = false;

    if (form.id === "form_ajout")
      toggleCaisse = document.getElementById("toggle_caisse_ajout");
    if (form.id === "form_suppression")
      toggleCaisse = document.getElementById("toggle_caisse_suppression");

    if (toggleCaisse) {
      wasChecked = toggleCaisse.checked;
    }

    // 2. Reset standard HTML
    form.reset();

    const typeSelect = form.querySelector('select[id^="type_materiel_"]');
    if (typeSelect) {
      typeSelect.dispatchEvent(new Event("change", { bubbles: true }));
    }

    // 3. Restaurer l'état de la checkbox
    if (toggleCaisse) {
      toggleCaisse.checked = wasChecked;
      // Trigger change event to ensure UI updates (show/hide panels)
      toggleCaisse.dispatchEvent(new Event("change"));
    }

    // 4. Custom logic based on the specific form being reset
    if (
      form.id === "form_ajout_caisse" ||
      (wasChecked && form.id === "form_ajout")
    ) {
      if (typeof window.resetFormAjoutCaisse === "function") {
        window.resetFormAjoutCaisse();
      } else {
        const listAjout = document.getElementById("objets_list_ajout");
        if (listAjout) listAjout.innerHTML = "";
      }
    }

    if (
      form.id === "form_suppression_caisse" ||
      (wasChecked && form.id === "form_suppression")
    ) {
      if (typeof window.resetFormSuppressionCaisse === "function") {
        window.resetFormSuppressionCaisse();
      } else {
        const detailsSuppr = document.getElementById("caisse_details_suppr");
        if (detailsSuppr) {
          detailsSuppr.innerHTML = "";
          detailsSuppr.classList.add("hidden");
        }
      }
    }



    // 4.5 Si c'est un formulaire principal ET une caisse était cochée, on reset le formulaire caisse aussi
    if (wasChecked) {
      let crateFormId = null;
      if (form.id === "form_ajout") crateFormId = "form_ajout_caisse";
      if (form.id === "form_suppression")
        crateFormId = "form_suppression_caisse";

      if (crateFormId) {
        const crateForm = document.getElementById(crateFormId);
        if (crateForm) {
          crateForm.reset();
          // Clear hidden inputs in crate form too
          const crateHidden = crateForm.querySelectorAll(
            "input[type='hidden']",
          );
          crateHidden.forEach((i) => (i.value = ""));
        }
      }
    }

    // 5. Clear visual autocomplete suggestions
    const suggestions = document.querySelectorAll(".autocomplete-suggestions");
    suggestions.forEach((s) => (s.style.display = "none"));

    // 6. Clear hidden ID fields in main form
    const hiddenInputs = form.querySelectorAll("input[type='hidden']");
    hiddenInputs.forEach((input) => (input.value = ""));


  }

  // --- 2. Nettoyage des champs dépendants quand le Code-barre est vidé ---



  // B. Suppression
  const idSuppr = document.getElementById("id_materiel_suppr");
  if (idSuppr) {
    idSuppr.addEventListener("input", () => {
      if (idSuppr.value.trim() === "") {
        console.log("Code-barre (Suppr) vidé : nettoyage dépendances.");

        const typeSuppr = document.getElementById("type_materiel_suppr");
        const nomSuppr = document.getElementById("nom_materiel_suppr");
        const nomCaisse = document.getElementById("nom_caisse_suppr");

        if (typeSuppr) typeSuppr.value = "";
        if (nomSuppr) nomSuppr.value = "";
        if (nomCaisse) nomCaisse.value = "";
      }
    });
  }
});
