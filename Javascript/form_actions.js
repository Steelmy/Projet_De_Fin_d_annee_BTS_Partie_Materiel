/**
 * form_actions.js
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
    if (form.id === "form_modification")
      toggleCaisse = document.getElementById("toggle_caisse_modification");

    if (toggleCaisse) {
      wasChecked = toggleCaisse.checked;
    }

    // 2. Reset standard HTML
    form.reset();

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

    if (
      form.id === "form_modification_caisse" ||
      (wasChecked && form.id === "form_modification")
    ) {
      if (typeof window.resetFormModificationCaisse === "function") {
        window.resetFormModificationCaisse();
      } else {
        const contenuModif = document.getElementById("caisse_contenu_modif");
        if (contenuModif) {
          contenuModif.innerHTML = "";
          contenuModif.classList.add("hidden");
        }

        const objetsTableModif = document.getElementById(
          "objets_table_container_modif",
        );
        if (objetsTableModif) {
          objetsTableModif.innerHTML = "";
          objetsTableModif.classList.add("hidden");
        }

        const detailsModif = document.getElementById("caisse_details_modif");
        if (detailsModif) detailsModif.classList.add("hidden");
      }
    }

    // 4.5 Si c'est un formulaire principal ET une caisse était cochée, on reset le formulaire caisse aussi
    if (wasChecked) {
      let crateFormId = null;
      if (form.id === "form_ajout") crateFormId = "form_ajout_caisse";
      if (form.id === "form_suppression")
        crateFormId = "form_suppression_caisse";
      if (form.id === "form_modification")
        crateFormId = "form_modification_caisse";

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

    // 7. Specific adjustments
    if (form.id === "form_modification") {
      const etatSelect = document.getElementById("etat");
      if (etatSelect) {
        etatSelect.value = "disponible";
        etatSelect.dispatchEvent(new Event("change"));
      }
    }
  }

  // --- 2. Nettoyage des champs dépendants quand le Code-barre est vidé ---

  // A. Modification
  const idModif = document.getElementById("id_materiel");
  if (idModif) {
    idModif.addEventListener("input", () => {
      if (idModif.value.trim() === "") {
        console.log("Code-barre (Modif) vidé : nettoyage dépendances.");

        // Clear dependent fields
        // Note: Type and Nom hidden fields are being removed, but we clear if they exist/are visible just in case
        const etatSelect = document.getElementById("etat");
        const reserveurInput = document.getElementById("reserveur_emprunteur");
        const reserveurId = document.getElementById("reserveur_emprunteur_id");

        if (etatSelect) {
          etatSelect.value = "disponible";
          etatSelect.dispatchEvent(new Event("change"));
        }
        if (reserveurInput) reserveurInput.value = "";
        if (reserveurId) reserveurId.value = "";
      }
    });
  }

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
