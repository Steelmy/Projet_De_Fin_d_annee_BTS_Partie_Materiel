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
  tableContainer.className =
    "rounded-lg border border-custom-border p-2.5 bg-gray-50";

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
      <div class="inline-flex items-center gap-2 bg-custom-brandLight/10 text-custom-brandDark px-3 py-1.5 rounded-full text-sm font-medium mr-2 mb-2 border border-custom-brandLight/20">
        ${objet.Code_bar} - ${objet.Type} <span class="opacity-70 text-xs">(${objet.Nom})</span>
        <span class="remove-objet cursor-pointer hover:text-custom-danger transition-colors ml-1 text-lg leading-none" onclick="removeFromModif(${objet.id})">&times;</span>
      </div>
    `;
  });

  contenuDiv.innerHTML = html;
}

// Retirer un objet de la caisse en modification
window.removeFromModif = function (objetId) {
  modifSelectedObjects = modifSelectedObjects.filter((o) => o.id !== objetId);
  updateModifObjectsDisplay();

  // Mettre à jour la checkbox si le tableau est affiché, sans recharger tout
  const checkbox = document.querySelector(
    `.objet-checkbox-modif[data-objet*='"id":${objetId}']`,
  );
  if (checkbox) {
    checkbox.checked = false;
  }
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

      // Mettre à jour la checkbox correspondante dans le tableau
      const checkbox = document.querySelector(
        `.objet-checkbox-modif[data-objet*='"id":${objet.id}']`,
      );
      if (checkbox) {
        checkbox.checked = true;
      }
    } else {
      alert("Objet non trouvé ou non disponible");
    }
  } catch (error) {
    console.error("Erreur lors de l'ajout de l'objet:", error);
    alert("Erreur lors de l'ajout de l'objet");
  }
}

// Pagination et Tri pour Modification Caisse
let availableObjectsForModif = [];
let modifCaisseCurrentPage = 1;
const modifCaisseItemsPerPage = 10;
let modifCaisseSortColumn = null;
let modifCaisseSortDirection = "asc";

// Charger le tableau d'objets pour la modification
async function loadObjectsTableModif() {
  const container = document.getElementById("objets_table_container_modif");

  if (!container) return;

  try {
    const response = await fetch("PHP/get_available_objects.php");
    const data = await response.json();

    if (data.success && data.objets) {
      availableObjectsForModif = data.objets;
      renderModifCaisseTable();
    } else {
      container.innerHTML = "<p>Aucun objet disponible</p>";
    }
  } catch (error) {
    console.error("Erreur lors du chargement des objets:", error);
    container.innerHTML = "<p>Erreur lors du chargement</p>";
  }
}

// Trier et afficher le tableau
function renderModifCaisseTable() {
  const container = document.getElementById("objets_table_container_modif");
  if (!container) return;

  // Le tableau doit montrer les objets disponibles + les objets ACTUELLEMENT dans la caisse
  // pour qu'on puisse les décocher/recocher
  let allTableObjects = [...availableObjectsForModif];

  if (currentCaisse && currentCaisse.contenu) {
    currentCaisse.contenu.forEach((obj) => {
      if (!allTableObjects.some((o) => o.id === obj.id)) {
        allTableObjects.push(obj);
      }
    });
  }

  if (allTableObjects.length === 0) {
    container.innerHTML = "<p>Aucun objet disponible ou dans la caisse</p>";
    return;
  }

  let filtered = [...allTableObjects];

  // TRI
  if (modifCaisseSortColumn) {
    filtered.sort((a, b) => {
      let valA = a[modifCaisseSortColumn]
        ? String(a[modifCaisseSortColumn]).toLowerCase()
        : "";
      let valB = b[modifCaisseSortColumn]
        ? String(b[modifCaisseSortColumn]).toLowerCase()
        : "";

      if (valA === "" && valB !== "")
        return modifCaisseSortDirection === "asc" ? 1 : -1;
      if (valB === "" && valA !== "")
        return modifCaisseSortDirection === "asc" ? -1 : 1;

      const comparison = valA.localeCompare(valB, "fr");
      return modifCaisseSortDirection === "asc" ? comparison : -comparison;
    });
  }

  // PAGINATION
  const totalItems = filtered.length;
  const totalPages = Math.ceil(totalItems / modifCaisseItemsPerPage) || 1;

  if (modifCaisseCurrentPage > totalPages) modifCaisseCurrentPage = totalPages;
  if (modifCaisseCurrentPage < 1) modifCaisseCurrentPage = 1;

  const startIndex = (modifCaisseCurrentPage - 1) * modifCaisseItemsPerPage;
  const endIndex = startIndex + modifCaisseItemsPerPage;
  const paginatedItems = filtered.slice(startIndex, endIndex);

  // Helper pour l'icône de tri
  const getSortIcon = (col) => {
    if (modifCaisseSortColumn === col) {
      return modifCaisseSortDirection === "asc" ? "↑" : "↓";
    }
    return '<span class="opacity-50">↕</span>';
  };

  let html = `
    <table class="w-full border-collapse mt-2.5 bg-white shadow-input rounded-lg overflow-hidden text-sm">
      <thead class="bg-linear-to-br from-custom-brandLight to-custom-brandDark text-white select-none">
        <tr>
          <th class="p-3 text-left font-semibold w-12">
            <input type="checkbox" id="select_all_objets_modif" class="rounded border-white/40 text-custom-primary focus:ring-white" />
          </th>
          <th class="p-3 font-semibold text-center">
            Code-barre
          </th>
          <th class="p-3 font-semibold text-center cursor-pointer hover:bg-white/10 transition-colors" onclick="window.sortModifCaisse('Type')">
            Type ${getSortIcon("Type")}
          </th>
          <th class="p-3 font-semibold text-center cursor-pointer hover:bg-white/10 transition-colors" onclick="window.sortModifCaisse('Nom')">
            Nom ${getSortIcon("Nom")}
          </th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100">
  `;

  if (paginatedItems.length === 0) {
    html += `<tr><td colspan="4" class="p-3 text-center text-gray-400 italic">Aucun objet sur cette page</td></tr>`;
  } else {
    paginatedItems.forEach((objet) => {
      const isSelected = modifSelectedObjects.some((o) => o.id === objet.id);
      html += `
        <tr class="hover:bg-gray-50 transition-colors">
          <td class="p-3 text-center">
            <input type="checkbox" 
                   class="objet-checkbox-modif rounded border-gray-300 text-custom-primary focus:ring-custom-primary" 
                   data-objet='${JSON.stringify(objet)}'
                   ${isSelected ? "checked" : ""} />
          </td>
          <td class="p-3 text-center">${objet.Code_bar}</td>
          <td class="p-3 text-center">${objet.Type}</td>
          <td class="p-3 text-center text-gray-600">${objet.Nom}</td>
        </tr>
      `;
    });
  }

  html += `</tbody></table>`;

  // Contrôles de pagination
  html += `
    <div class="p-3 flex justify-between items-center text-slate-500 text-sm border-t border-gray-200 mt-2">
      <button type="button" class="px-3 py-1 bg-white border border-[#ccc] rounded-lg cursor-pointer hover:bg-gray-50 disabled:opacity-50" 
              onclick="window.changeModifCaissePage(-1)" ${modifCaisseCurrentPage === 1 ? "disabled" : ""}>
        &larr; Précédent
      </button>
      <span class="font-medium">Page ${modifCaisseCurrentPage} / ${totalPages}</span>
      <button type="button" class="px-3 py-1 bg-white border border-[#ccc] rounded-lg cursor-pointer hover:bg-gray-50 disabled:opacity-50" 
              onclick="window.changeModifCaissePage(1)" ${modifCaisseCurrentPage === totalPages ? "disabled" : ""}>
        Suivant &rarr;
      </button>
    </div>
  `;

  container.innerHTML = html;

  // Gérer le "Tout sélectionner" de la page courante
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
}

// Changer de page (+1 ou -1)
window.changeModifCaissePage = function (delta) {
  modifCaisseCurrentPage += delta;
  renderModifCaisseTable();
};

// Trier l'inventaire au clic sur l'en-tête
window.sortModifCaisse = function (column) {
  if (modifCaisseSortColumn === column) {
    modifCaisseSortDirection =
      modifCaisseSortDirection === "asc" ? "desc" : "asc";
  } else {
    modifCaisseSortColumn = column;
    modifCaisseSortDirection = "asc";
  }
  modifCaisseCurrentPage = 1;
  renderModifCaisseTable();
};

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
