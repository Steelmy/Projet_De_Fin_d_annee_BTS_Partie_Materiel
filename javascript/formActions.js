/**
 * formActions.js — Boutons "Réinitialiser" des formulaires
 * et nettoyage des champs dépendants quand le code-barres est vidé.
 *
 * Conserve l'état de la checkbox "Caisse" pendant le reset
 * pour ne pas perdre la sélection du panneau actif.
 */

document.addEventListener("DOMContentLoaded", () => {
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

  /**
   * Réinitialise un formulaire en préservant la checkbox "Caisse" associée
   * et en nettoyant les sous-formulaires de caisse correspondants.
   *
   * @param {HTMLFormElement} form - Formulaire à réinitialiser.
   * @returns {void}
   */
  function resetForm(form) {
    let toggleCaisse = null;
    let wasChecked = false;

    if (form.id === "form_ajout")
      toggleCaisse = document.getElementById("toggle_caisse_ajout");
    if (form.id === "form_suppression")
      toggleCaisse = document.getElementById("toggle_caisse_suppression");

    if (toggleCaisse) {
      wasChecked = toggleCaisse.checked;
    }

    form.reset();

    const typeSelect = form.querySelector('select[id^="type_materiel_"]');
    if (typeSelect) {
      typeSelect.dispatchEvent(new Event("change", { bubbles: true }));
    }

    if (toggleCaisse) {
      toggleCaisse.checked = wasChecked;
      toggleCaisse.dispatchEvent(new Event("change"));
    }

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

    if (wasChecked) {
      let crateFormId = null;
      if (form.id === "form_ajout") crateFormId = "form_ajout_caisse";
      if (form.id === "form_suppression")
        crateFormId = "form_suppression_caisse";

      if (crateFormId) {
        const crateForm = document.getElementById(crateFormId);
        if (crateForm) {
          crateForm.reset();
          const crateHidden = crateForm.querySelectorAll(
            "input[type='hidden']",
          );
          crateHidden.forEach((i) => (i.value = ""));
        }
      }
    }

    const suggestions = document.querySelectorAll(".autocomplete-suggestions");
    suggestions.forEach((s) => (s.style.display = "none"));

    const hiddenInputs = form.querySelectorAll("input[type='hidden']");
    hiddenInputs.forEach((input) => (input.value = ""));
  }

  const idSuppr = document.getElementById("id_materiel_suppr");
  if (idSuppr) {
    idSuppr.addEventListener("input", () => {
      if (idSuppr.value.trim() === "") {
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
