// Gestion de la modification de caisses

let currentCaisse = null; // Caisse en cours de modification
let modifSelectedObjects = []; // Objets sélectionnés pour la modification

document.addEventListener("DOMContentLoaded", () => {
  const formModificationCaisse = document.getElementById(
    "form_modification_caisse",
  );

  if (formModificationCaisse) {
    initializeModificationForm();
  }
});

function initializeModificationForm() {
  // 1. Autocomplétion CAISSE (Nouveau système unifié - On garde ça car c'était validé)
  new UniversalAutocomplete("nom_caisse_modif", "caisse", async (item) => {
    // Callback : charger la caisse sélectionnée
    await loadCaisseForModification(item.value);
  });

  // 2. Autocomplétion UTILISATEUR (Nouveau système unifié)
  const userHiddenId = document.getElementById("utilisateur_caisse_modif_id");
  new UniversalAutocomplete("utilisateur_caisse_modif", "user", (item) => {
    // Callback : stocker l'ID
    userHiddenId.value = item.id;
  });

  // Gérer l'effacement du champ utilisateur
  const userInput = document.getElementById("utilisateur_caisse_modif");
  if (userInput) {
    const handleClear = () => {
      if (userInput.value.trim() === "") {
        console.log("Clearing hidden user ID");
        document.getElementById("utilisateur_caisse_modif_id").value = "";
      }
    };
    userInput.addEventListener("input", handleClear);
    userInput.addEventListener("change", handleClear);
  }

  // Initialiser la sélection d'objets
  initializeObjectSelectionModif();

  // Gérer l'état et le champ utilisateur
  const etatSelect = document.getElementById("etat_caisse_modif");
  const utilisateurInput = document.getElementById("utilisateur_caisse_modif");

  if (etatSelect && utilisateurInput) {
    etatSelect.addEventListener("change", () => {
      toggleUtilisateurField();
    });

    // Initialiser l'état au chargement
    toggleUtilisateurField();
  }

  // Soumettre le formulaire
  const form = document.getElementById("form_modification_caisse");
  form.addEventListener("submit", handleUpdateCaisse);
}

// Activer/désactiver le champ utilisateur selon l'état
function toggleUtilisateurField() {
  const etat = document.getElementById("etat_caisse_modif").value;
  const utilisateurInput = document.getElementById("utilisateur_caisse_modif");
  const utilisateurId = document.getElementById("utilisateur_caisse_modif_id");

  if (etat === "disponible") {
    utilisateurInput.disabled = true;
    utilisateurInput.value = "";
    if (utilisateurId) utilisateurId.value = ""; // Reset ID aussi
    utilisateurInput.style.backgroundColor = "#e9ecef";
    utilisateurInput.style.cursor = "not-allowed";
  } else {
    // Note: On ne reset PAS la valeur ici quand on passe à réservé,
    // car on veut peut-être garder l'utilisateur pré-rempli si on vient de charger la caisse
    utilisateurInput.disabled = false;
    utilisateurInput.style.backgroundColor = "";
    utilisateurInput.style.cursor = "";
  }
}

// Initialiser la sélection d'objets pour la modification
function initializeObjectSelectionModif() {
  const searchInput = document.getElementById("search_objets_modif");

  if (!searchInput) return;

  // Créer le tableau permanent
  const tableContainer = document.getElementById(
    "objets_table_container_modif",
  );
  tableContainer.style.marginTop = "15px";
  tableContainer.style.maxHeight = "400px";
  tableContainer.style.overflowY = "auto";
  tableContainer.style.border = "1px solid #ced4da";
  tableContainer.style.borderRadius = "4px";
  tableContainer.style.padding = "10px";

  // Gérer l'autocomplétion unifiée Barcode
  new UniversalAutocompleteBarcode(
    "search_objets_modif",
    null,
    null,
    null,
    async (item) => {
      await addObjectToModif(item.Code_bar);
      document.getElementById("search_objets_modif").value = "";
    },
    "disponible", // Filtre : uniquement disponibles
  );

  searchInput.addEventListener("keypress", async (e) => {
    if (e.key === "Enter") {
      e.preventDefault();
      const codeBarre = searchInput.value.trim();
      if (codeBarre) {
        await addObjectToModif(codeBarre);
        searchInput.value = "";
      }
    }
  });
}

// Rechercher des objets disponibles pour modification
async function searchObjectsModif(query, suggestionsDiv) {
  try {
    const response = await fetch("PHP/get_available_objects.php");
    const data = await response.json();

    if (data.success && data.objets.length > 0) {
      const filtered = data.objets
        .filter(
          (objet) =>
            objet.Code_bar.toLowerCase().includes(query.toLowerCase()) ||
            objet.Type.toLowerCase().includes(query.toLowerCase()) ||
            objet.Nom.toLowerCase().includes(query.toLowerCase()),
        )
        .slice(0, 5);

      if (filtered.length > 0) {
        displayObjectSuggestionsModif(filtered, suggestionsDiv);
      } else {
        suggestionsDiv.innerHTML = "";
        suggestionsDiv.style.display = "none";
      }
    }
  } catch (error) {
    console.error("Erreur recherche objets:", error);
  }
}

// Afficher les suggestions d'objets pour modification
function displayObjectSuggestionsModif(objets, suggestionsDiv) {
  suggestionsDiv.innerHTML = "";
  suggestionsDiv.style.display = "block";

  objets.forEach((objet) => {
    const div = document.createElement("div");
    div.className = "autocomplete-suggestion";
    div.innerHTML = `
      <span class="user-name">${objet.Code_bar}</span>
      <span class="user-id">${objet.Type} - ${objet.Nom}</span>
    `;

    div.addEventListener("click", async () => {
      await addObjectToModif(objet.Code_bar);
      document.getElementById("search_objets_modif").value = "";
      suggestionsDiv.innerHTML = "";
      suggestionsDiv.style.display = "none";
    });

    suggestionsDiv.appendChild(div);
  });
}

// Charger une caisse pour modification
async function loadCaisseForModification(nom) {
  try {
    const response = await fetch(
      `PHP/get_caisse_details.php?nom=${encodeURIComponent(nom)}`,
    );
    const data = await response.json();

    if (data.success && data.caisse) {
      currentCaisse = data.caisse;
      modifSelectedObjects = data.caisse.contenu
        ? [...data.caisse.contenu]
        : [];
      displayCaisseForModification(data.caisse);

      // Charger le tableau d'objets
      loadObjectsTableModif();
    }
  } catch (error) {
    console.error("Erreur chargement:", error);
  }
}

// Afficher la caisse dans le formulaire
function displayCaisseForModification(caisse) {
  const detailsDiv = document.getElementById("caisse_details_modif");

  if (!detailsDiv) return;

  // Remplir les champs
  document.getElementById("nouveau_nom_caisse").value = "";
  document.getElementById("etat_caisse_modif").value =
    caisse.etat || "disponible";

  // Mettre à jour l'état du champ utilisateur
  toggleUtilisateurField();

  // Afficher le contenu actuel
  updateModifObjectsDisplay();

  // Stocker l'ID de la caisse
  detailsDiv.dataset.caisseId = caisse.id;
  detailsDiv.style.display = "block";

  // Remplir les infos utilisateur si présentes
  const utilisateurInput = document.getElementById("utilisateur_caisse_modif");
  const utilisateurId = document.getElementById("utilisateur_caisse_modif_id");

  if (caisse.etat !== "disponible" && caisse.utilisateur) {
    utilisateurInput.value = caisse.utilisateur.nom_complet;
    if (utilisateurId) utilisateurId.value = caisse.utilisateur.id;
  } else {
    // Si disponible ou pas d'utilisateur, on reset
    // (Déjà fait partiellement par toggleUtilisateurField mais on assure le coup)
    if (utilisateurId) utilisateurId.value = "";
  }
}

// Mettre à jour l'affichage des objets de la caisse
function updateModifObjectsDisplay() {
  const contenuDiv = document.getElementById("caisse_contenu_modif");

  if (!contenuDiv) return;

  if (modifSelectedObjects.length === 0) {
    contenuDiv.innerHTML =
      "<p style='color: #6c757d; margin: 10px;'>Caisse vide</p>";
    return;
  }

  let html = "";
  modifSelectedObjects.forEach((objet) => {
    html += `
      <div class="objet-tag">
        ${objet.Code_bar} - ${objet.Type} (${objet.Nom})
        <span class="remove-objet" onclick="removeFromModif(${objet.id})">×</span>
      </div>
    `;
  });

  contenuDiv.innerHTML = html;
}

// Retirer un objet de la caisse en modification
window.removeFromModif = function (objetId) {
  modifSelectedObjects = modifSelectedObjects.filter((o) => o.id !== objetId);
  updateModifObjectsDisplay();

  // Recharger le tableau pour mettre à jour les checkboxes
  loadObjectsTableModif();
};

// Ajouter un objet à la caisse en modification
async function addObjectToModif(codeBarre) {
  try {
    const response = await fetch(
      `PHP/get_materiel_details.php?code_barre=${encodeURIComponent(codeBarre)}`,
    );
    const data = await response.json();

    if (data.success && data.materiel) {
      const objet = {
        id: data.materiel.id,
        Code_bar: data.materiel.code_barre,
        Type: data.materiel.type_materiel,
        Nom: data.materiel.nom_materiel,
      };

      // Vérifier si l'objet est disponible
      if (data.materiel.etat !== "disponible") {
        alert("Cet objet n'est pas disponible");
        return;
      }

      // Vérifier si déjà dans la caisse
      if (modifSelectedObjects.some((o) => o.id === objet.id)) {
        alert("Cet objet est déjà dans la caisse");
        return;
      }

      modifSelectedObjects.push(objet);
      updateModifObjectsDisplay();

      // Recharger le tableau
      loadObjectsTableModif();
    } else {
      alert("Objet non trouvé ou non disponible");
    }
  } catch (error) {
    console.error("Erreur lors de l'ajout de l'objet:", error);
    alert("Erreur lors de l'ajout de l'objet");
  }
}

// Charger le tableau d'objets pour la modification
async function loadObjectsTableModif() {
  const container = document.getElementById("objets_table_container_modif");

  if (!container) return;

  try {
    const response = await fetch("PHP/get_available_objects.php");
    const data = await response.json();

    if (data.success && data.objets.length > 0) {
      let html = `
        <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
          <thead>
            <tr>
              <th style="padding: 8px; border: 1px solid #dee2e6; text-align: left;">
                <input type="checkbox" id="select_all_objets_modif" /> Tout sélectionner
              </th>
              <th style="padding: 8px; border: 1px solid #dee2e6;">Code-barre</th>
              <th style="padding: 8px; border: 1px solid #dee2e6;">Type</th>
              <th style="padding: 8px; border: 1px solid #dee2e6;">Nom</th>
            </tr>
          </thead>
          <tbody>
      `;

      data.objets.forEach((objet) => {
        const isSelected = modifSelectedObjects.some((o) => o.id === objet.id);
        html += `
          <tr>
            <td style="padding: 8px; border: 1px solid #dee2e6; text-align: center;">
              <input type="checkbox" 
                     class="objet-checkbox-modif" 
                     data-objet='${JSON.stringify(objet)}'
                     ${isSelected ? "checked" : ""} />
            </td>
            <td style="padding: 8px; border: 1px solid #dee2e6;">${objet.Code_bar}</td>
            <td style="padding: 8px; border: 1px solid #dee2e6;">${objet.Type}</td>
            <td style="padding: 8px; border: 1px solid #dee2e6;">${objet.Nom}</td>
          </tr>
        `;
      });

      html += `</tbody></table>`;
      container.innerHTML = html;

      // Gérer le "Tout sélectionner"
      const selectAll = document.getElementById("select_all_objets_modif");
      if (selectAll) {
        selectAll.addEventListener("change", (e) => {
          document.querySelectorAll(".objet-checkbox-modif").forEach((cb) => {
            cb.checked = e.target.checked;
            handleCheckboxChangeModif({ target: cb });
          });
        });
      }

      // Gérer les checkboxes individuelles
      document.querySelectorAll(".objet-checkbox-modif").forEach((checkbox) => {
        checkbox.addEventListener("change", handleCheckboxChangeModif);
      });
    } else {
      container.innerHTML = "<p>Aucun objet disponible</p>";
    }
  } catch (error) {
    console.error("Erreur lors du chargement des objets:", error);
    container.innerHTML = "<p>Erreur lors du chargement</p>";
  }
}

// Gérer le changement de checkbox dans la modification
function handleCheckboxChangeModif(e) {
  const objet = JSON.parse(e.target.dataset.objet);

  if (e.target.checked) {
    if (!modifSelectedObjects.some((o) => o.id === objet.id)) {
      modifSelectedObjects.push(objet);
      updateModifObjectsDisplay();
    }
  } else {
    modifSelectedObjects = modifSelectedObjects.filter(
      (o) => o.id !== objet.id,
    );
    updateModifObjectsDisplay();
  }
}

// Soumettre la modification
async function handleUpdateCaisse(e) {
  e.preventDefault();

  const detailsDiv = document.getElementById("caisse_details_modif");
  const caisseId = detailsDiv.dataset.caisseId;

  // Vérifier qu'une caisse est sélectionnée ET que les détails sont visibles
  if (!caisseId || detailsDiv.style.display === "none" || !currentCaisse) {
    alert("Veuillez d'abord rechercher et sélectionner une caisse");
    return;
  }

  const nouveauNom = document.getElementById("nouveau_nom_caisse").value.trim();
  const etat = document.getElementById("etat_caisse_modif").value;

  // Préparer le nouveau contenu
  const objets_ids = modifSelectedObjects.map((o) => o.id);

  try {
    const formData = new FormData();
    formData.append("id", caisseId);
    if (nouveauNom) {
      formData.append("nouveau_nom", nouveauNom);
    }
    formData.append("etat", etat);
    formData.append("objets_ids", JSON.stringify(objets_ids));

    // Ajouter l'ID utilisateur si l'état n'est pas disponible
    if (etat !== "disponible") {
      const emprunteurId = document.getElementById(
        "utilisateur_caisse_modif_id",
      ).value;

      if (!emprunteurId) {
        alert(
          "Veuillez sélectionner un utilisateur pour réserver ou emprunter la caisse",
        );
        return;
      }
      formData.append("emprunteur_id", emprunteurId);
    }

    const response = await fetch("PHP/update_caisse.php", {
      method: "POST",
      body: formData,
    });

    const data = await response.json();

    if (data.success) {
      alert("Caisse modifiée avec succès");
      resetFormModificationCaisse();

      // Rafraîchir l'inventaire pour mettre à jour la colonne Caisse
      if (window.refreshInventory) {
        window.refreshInventory();
      }

      // Rafraîchir aussi la table d'ajout
      if (window.reloadAddCaisseObjects) {
        window.reloadAddCaisseObjects();
      }
    } else {
      alert("Erreur: " + data.message);
    }
  } catch (error) {
    console.error("Erreur modification:", error);
    alert("Erreur lors de la modification");
  }
}

// Fonction appelée depuis caisse_toggle.js
window.resetFormModificationCaisse = function () {
  document.getElementById("form_modification_caisse").reset();
  modifSelectedObjects = [];
  currentCaisse = null;

  const detailsDiv = document.getElementById("caisse_details_modif");
  if (detailsDiv) {
    detailsDiv.style.display = "none";
    // IMPORTANT: Supprimer l'ID pour éviter les modifications accidentelles
    delete detailsDiv.dataset.caisseId;
  }

  const tableContainer = document.getElementById(
    "objets_table_container_modif",
  );
  if (tableContainer) {
    tableContainer.innerHTML = "";
  }
};

window.reloadModifCaisseObjects = loadObjectsTableModif;
