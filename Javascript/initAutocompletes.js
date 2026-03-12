// Initialisation centralisée de toutes les autocomplétions du projet avec UniversalAutocomplete
// Remplace : autocomplete_types_noms.js, autocomplete_utilisateurs.js, autocomplete_caisse.js etc.

document.addEventListener("DOMContentLoaded", () => {
  // --- 1. Formulaire Ajout de Matériel ---
  if (document.getElementById("type_materiel_ajout")) {
    new UniversalAutocomplete(
      "type_materiel_ajout",
      "materiel_type",
      (item) => {
        document.getElementById("nom_materiel_ajout").value = "";
      },
    );

    new UniversalAutocomplete(
      "nom_materiel_ajout",
      "materiel_nom",
      null,
      () => {
        return document.getElementById("type_materiel_ajout").value.trim();
      },
    );
  }

  // --- 2. Formulaire Suppression de Matériel ---
  if (document.getElementById("type_materiel_suppr")) {
    new UniversalAutocomplete(
      "type_materiel_suppr",
      "materiel_type",
      (item) => {
        document.getElementById("nom_materiel_suppr").value = "";
      },
    );

    new UniversalAutocomplete(
      "nom_materiel_suppr",
      "materiel_nom",
      null,
      () => {
        return document.getElementById("type_materiel_suppr").value.trim();
      },
    );
  }

  // --- 3. Formulaire Modification de Matériel ---
  if (document.getElementById("id_materiel")) {
    // Note: Type and Nom fields for modification have been removed from UI.
    // We only keep user autocomplete and barcode below.

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

  // --- 4. Consultation (Filtres) ---
  if (document.getElementById("type_materiel_consultation")) {
    new UniversalAutocomplete(
      "type_materiel_consultation",
      "materiel_type",
      (item) => {
        document.getElementById("nom_materiel_consultation").value = "";
        if (window.applyFiltersConsultation) window.applyFiltersConsultation();
      },
    );

    new UniversalAutocomplete(
      "nom_materiel_consultation",
      "materiel_nom",
      (item) => {
        if (window.applyFiltersConsultation) window.applyFiltersConsultation();
      },
      () => {
        return document
          .getElementById("type_materiel_consultation")
          .value.trim();
      },
    );

    // Autocomplétion Code-Barre Consultation (Avec Cascade)
    if (document.getElementById("code_barre_consultation")) {
      new UniversalAutocompleteBarcode(
        "code_barre_consultation",
        null,
        "type_materiel_consultation",
        "nom_materiel_consultation",
        (item) => {
          // Remplissage Cascade
          if (item.Type)
            document.getElementById("type_materiel_consultation").value =
              item.Type;
          if (item.Nom)
            document.getElementById("nom_materiel_consultation").value =
              item.Nom;
          // Trigger refresh
          if (window.applyFiltersConsultation)
            window.applyFiltersConsultation();
        },
      );
    }
  }

  // --- Barcodes Spécifiques (Cascade) ---
  // Suppression
  if (document.getElementById("id_materiel_suppr")) {
    new UniversalAutocompleteBarcode(
      "id_materiel_suppr",
      null,
      "type_materiel_suppr",
      "nom_materiel_suppr",
      (item) => {
        if (item.Type)
          document.getElementById("type_materiel_suppr").value = item.Type;
        if (item.Nom)
          document.getElementById("nom_materiel_suppr").value = item.Nom;
      },
      null, // etatFilter
      true, // disponibleOnly
    );
  }

  // Modification
  if (document.getElementById("id_materiel")) {
    new UniversalAutocompleteBarcode(
      "id_materiel",
      null,
      null, // Pas de champ Type pour filtre
      null, // Pas de champ Nom pour filtre
      (item) => {
        // Plus de remplissage de Type/Nom car champs supprimés

        // Si on est dans le formulaire de modif, on déclenche aussi le chargement des autres infos
        // Note: remplirFormulaireModification s'occupe du reste (User, Etat)
        if (typeof window.remplirFormulaireModification === "function") {
          window.remplirFormulaireModification(item.Code_bar);
        } else {
          console.warn("remplirFormulaireModification non trouvée");
        }
      },
      null, // etatFilter
      true, // disponibleOnly
    );
  }

  // --- 5. INITIALISATION GÉNÉRIQUE DE SECOURS ---
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
