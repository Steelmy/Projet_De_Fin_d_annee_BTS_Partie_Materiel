/**
 * initAutocompletes.js — Câblage des autocomplétions restantes
 * (utilisateurs et codes-barres). Les selects en cascade Type/Sous-type/Nom
 * sont gérés par dynamicSelects.js.
 */

document.addEventListener("DOMContentLoaded", () => {
  if (document.getElementById("id_materiel")) {
    if (document.getElementById("reserveur_emprunteur")) {
      const hiddenId = document.getElementById("reserveur_emprunteur_id");
      new UniversalAutocomplete("reserveur_emprunteur", "user", (item) => {
        hiddenId.value = item.id;
      });

      const userInput = document.getElementById("reserveur_emprunteur");
      const handleClear = () => {
        if (userInput.value.trim() === "") {
          document.getElementById("reserveur_emprunteur_id").value = "";
        }
      };
      userInput.addEventListener("input", handleClear);
      userInput.addEventListener("change", handleClear);
    }
  }

  if (document.getElementById("type_materiel_consultation")) {
    if (document.getElementById("code_barre_consultation")) {
      new UniversalAutocompleteBarcode(
        "code_barre_consultation",
        null,
        "type_materiel_consultation",
        "sous_type_materiel_consultation",
        "nom_materiel_consultation",
        (item) => {
          if (window.setSelectCascadeValues) {
             window.setSelectCascadeValues('consultation', item.Type, item.Sous_type, item.Nom);
          }
        },
      );
    }
  }

  if (document.getElementById("id_materiel_suppr")) {
    new UniversalAutocompleteBarcode(
      "id_materiel_suppr",
      null,
      "type_materiel_suppr",
      "sous_type_materiel_suppr",
      "nom_materiel_suppr",
      (item) => {
          if (window.setSelectCascadeValues) {
             window.setSelectCascadeValues('suppr', item.Type, item.Sous_type, item.Nom);
          }
      },
      null,
      true,
    );
  }

  // Fallback : autocomplete générique pour tout input non encore initialisé
  const genericBarcodes = document.querySelectorAll(
    ".autocomplete-container-code-barre input",
  );
  genericBarcodes.forEach((input) => {
    if (!input.dataset.barcodeInitialized && input.id) {
      console.log(`⚠️ Auto-init fallback pour #${input.id}`);
      new UniversalAutocompleteBarcode(input.id, null);
    }
  });
});
