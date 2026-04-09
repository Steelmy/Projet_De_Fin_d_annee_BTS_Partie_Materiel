// Initialisation centralisée des autocomplétions restantes (Codes-barres et Utilisateurs)
// Les menus déroulants (Type/Sous-type/Nom) sont gérés par javascript/dynamicSelects.js

document.addEventListener("DOMContentLoaded", () => {
  // --- Formulaire Modification de Matériel ---
  if (document.getElementById("id_materiel")) {
    // Utilisateur pour modification matériel (champ reserveur_emprunteur)
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

  // --- Consultation (Filtres) ---
  if (document.getElementById("type_materiel_consultation")) {
    // Autocomplétion Code-Barre Consultation (Avec Cascade Selects)
    if (document.getElementById("code_barre_consultation")) {
      new UniversalAutocompleteBarcode(
        "code_barre_consultation",
        null,
        "type_materiel_consultation",
        "sous_type_materiel_consultation",
        "nom_materiel_consultation",
        (item) => {
          // Remplissage Cascade via select
          if (window.setSelectCascadeValues) {
             window.setSelectCascadeValues('consultation', item.Type, item.Sous_type, item.Nom);
          }
          // Trigger refresh is handled inside setSelectCascadeValues via dispatchChangeEvent on nomSelect
        },
      );
    }
  }

  // --- Barcodes Spécifiques (Cascade) ---
  // Suppression
  if (document.getElementById("id_materiel_suppr")) {
    new UniversalAutocompleteBarcode(
      "id_materiel_suppr",
      null, // containerId est recréé dynamiquement dans le body pour éviter les problèmes de z-index
      "type_materiel_suppr",
      "sous_type_materiel_suppr",
      "nom_materiel_suppr",
      (item) => {
          if (window.setSelectCascadeValues) {
             window.setSelectCascadeValues('suppr', item.Type, item.Sous_type, item.Nom);
          }
      },
      null, // etatFilter
      true, // disponibleOnly
    );
  }



  // --- INITIALISATION GÉNÉRIQUE DE SECOURS ---
  // Pour tous les champs dans .autocomplete-container-code-barre qui n'ont pas été init
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

