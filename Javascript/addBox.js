// Gestion de l'ajout de caisses

let selectedObjects = []; // Objets sélectionnés pour la caisse

document.addEventListener("DOMContentLoaded", () => {
  const formAjoutCaisse = document.getElementById("form_ajout_caisse");

  if (formAjoutCaisse) {
    formAjoutCaisse.addEventListener("submit", handleSubmitCaisse);
    initializeObjectSelection();
    updateSelectedObjectsDisplay();
  }
});

// Initialiser la sélection d'objets (scan + tableau)
function initializeObjectSelection() {
  const searchInput = document.getElementById("search_objets_ajout");

  if (!searchInput) return;

  // Créer le conteneur pour le sélecteur d'objets
  const container = searchInput.parentElement;

  // Conteneur du tableau (visible par défaut)
  const tableContainer = document.createElement("div");
  tableContainer.id = "objets_table_container";
  tableContainer.style.display = "block";
  tableContainer.style.marginTop = "15px";
  tableContainer.style.maxHeight = "400px";
  tableContainer.style.overflowY = "auto";
  tableContainer.className =
    "rounded-lg border border-custom-border p-2.5 bg-gray-50";

  container.appendChild(tableContainer);

  // Charger automatiquement le tableau
  loadObjectsTable();

  // NOUVEAU : UniversalAutocompleteBarcode
  new UniversalAutocompleteBarcode(
    "search_objets_ajout",
    null,
    null, // Pas de filtre type
    null, // Pas de filtre nom
    async (item) => {
      await addObjectByBarcode(item.Code_bar);
      document.getElementById("search_objets_ajout").value = "";
    },
    "disponible", // Filtre : uniquement les objets disponibles
  );

  // Gérer la touche Entrée (Optionnel si on veut forcer le clic, mais pratique)
  searchInput.addEventListener("keypress", async (e) => {
    if (e.key === "Enter") {
      e.preventDefault();
      const codeBarre = searchInput.value.trim();
      if (codeBarre) {
        await addObjectByBarcode(codeBarre);
        searchInput.value = "";

        // Masquer les suggestions
        const suggestionsDiv = document.getElementById(
          "objets_suggestions_ajout",
        );
        if (suggestionsDiv) {
          suggestionsDiv.innerHTML = "";
          suggestionsDiv.style.display = "none";
        }
      }
    }
  });
}

// Rechercher des objets disponibles
async function searchObjects(query, suggestionsDiv) {
  try {
    const response = await fetch("php/getAvailableObjects.php");
    const data = await response.json();

    if (data.success && data.objets.length > 0) {
      // Filtrer les objets par code-barre, type ou nom
      const filtered = data.objets
        .filter(
          (objet) =>
            objet.Code_bar.toLowerCase().includes(query.toLowerCase()) ||
            objet.Type.toLowerCase().includes(query.toLowerCase()) ||
            objet.Nom.toLowerCase().includes(query.toLowerCase()),
        )
        .slice(0, 5); // Limiter à 5 résultats

      if (filtered.length > 0) {
        displayObjectSuggestions(filtered, suggestionsDiv);
      } else {
        suggestionsDiv.innerHTML = "";
        suggestionsDiv.style.display = "none";
      }
    }
  } catch (error) {
    console.error("Erreur recherche objets:", error);
  }
}

// Afficher les suggestions d'objets
function displayObjectSuggestions(objets, suggestionsDiv) {
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
      await addObjectByBarcode(objet.Code_bar);
      document.getElementById("search_objets_ajout").value = "";
      suggestionsDiv.innerHTML = "";
      suggestionsDiv.style.display = "none";
    });

    suggestionsDiv.appendChild(div);
  });
}

// Pagination et Tri pour Ajout Caisse
let availableObjectsForAdd = [];
let addCaisseCurrentPage = 1;
const addCaisseItemsPerPage = 10;
let addCaisseSortColumn = null;
let addCaisseSortDirection = "asc";

// Charger le tableau d'objets disponibles
async function loadObjectsTable() {
  const container = document.getElementById("objets_table_container");

  try {
    const response = await fetch("php/getAvailableObjects.php");
    const data = await response.json();

    if (data.success && data.objets) {
      availableObjectsForAdd = data.objets;
      renderAddCaisseTable();
    } else {
      container.innerHTML = "<p>Aucun objet disponible</p>";
    }
  } catch (error) {
    console.error("Erreur lors du chargement des objets:", error);
    container.innerHTML = "<p>Erreur lors du chargement</p>";
  }
}

// Trier et afficher le tableau
function renderAddCaisseTable() {
  const container = document.getElementById("objets_table_container");
  if (!container || availableObjectsForAdd.length === 0) return;

  let filtered = [...availableObjectsForAdd];

  // TRI (DRY : utilise sortUtils.js)
  if (addCaisseSortColumn) {
    filtered.sort((a, b) =>
      window.localeSortComparator(
        a,
        b,
        addCaisseSortColumn,
        addCaisseSortDirection,
      ),
    );
  }

  // PAGINATION
  const totalItems = filtered.length;
  const totalPages = Math.ceil(totalItems / addCaisseItemsPerPage) || 1;

  if (addCaisseCurrentPage > totalPages) addCaisseCurrentPage = totalPages;
  if (addCaisseCurrentPage < 1) addCaisseCurrentPage = 1;

  const startIndex = (addCaisseCurrentPage - 1) * addCaisseItemsPerPage;
  const endIndex = startIndex + addCaisseItemsPerPage;
  const paginatedItems = filtered.slice(startIndex, endIndex);

  // Helper pour l'icône de tri
  const getSortIcon = (col) => {
    if (addCaisseSortColumn === col) {
      return addCaisseSortDirection === "asc" ? "↑" : "↓";
    }
    return '<span class="opacity-50">↕</span>';
  };

  let html = `
    <table class="w-full border-collapse mt-2.5 bg-white shadow-input rounded-lg overflow-hidden text-sm">
      <thead class="bg-linear-to-br from-custom-brandLight to-custom-brandDark text-white select-none">
        <tr>
          <th class="p-3 text-left font-semibold w-12">
            <input type="checkbox" id="select_all_objets" class="rounded border-white/40 text-custom-primary focus:ring-white" />
          </th>
          <th class="p-3 font-semibold text-center">
            Code-barre
          </th>
          <th class="p-3 font-semibold text-center cursor-pointer hover:bg-white/10 transition-colors" onclick="window.sortAddCaisse('Type')">
            Type ${getSortIcon("Type")}
          </th>
          <th class="p-3 font-semibold text-center cursor-pointer hover:bg-white/10 transition-colors" onclick="window.sortAddCaisse('Nom')">
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
      const isSelected = selectedObjects.some((o) => o.id === objet.id);
      html += `
        <tr class="hover:bg-gray-50 transition-colors">
          <td class="p-3 text-center">
            <input type="checkbox" 
                   class="objet-checkbox rounded border-gray-300 text-custom-primary focus:ring-custom-primary" 
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
              onclick="window.changeAddCaissePage(-1)" ${addCaisseCurrentPage === 1 ? "disabled" : ""}>
        &larr; Précédent
      </button>
      <span class="font-medium">Page ${addCaisseCurrentPage} / ${totalPages}</span>
      <button type="button" class="px-3 py-1 bg-white border border-[#ccc] rounded-lg cursor-pointer hover:bg-gray-50 disabled:opacity-50" 
              onclick="window.changeAddCaissePage(1)" ${addCaisseCurrentPage === totalPages ? "disabled" : ""}>
        Suivant &rarr;
      </button>
    </div>
  `;

  container.innerHTML = html;

  // Gérer le "Tout sélectionner" de la page courante
  const selectAll = document.getElementById("select_all_objets");
  if (selectAll) {
    selectAll.addEventListener("change", (e) => {
      document.querySelectorAll(".objet-checkbox").forEach((cb) => {
        cb.checked = e.target.checked;
        handleCheckboxChange({ target: cb });
      });
    });
  }

  // Gérer les checkboxes individuelles
  document.querySelectorAll(".objet-checkbox").forEach((checkbox) => {
    checkbox.addEventListener("change", handleCheckboxChange);
  });
}

// Changer de page (+1 ou -1)
window.changeAddCaissePage = function (delta) {
  addCaisseCurrentPage += delta;
  renderAddCaisseTable();
};

// Trier l'inventaire au clic sur l'en-tête
window.sortAddCaisse = function (column) {
  if (addCaisseSortColumn === column) {
    addCaisseSortDirection = addCaisseSortDirection === "asc" ? "desc" : "asc";
  } else {
    addCaisseSortColumn = column;
    addCaisseSortDirection = "asc";
  }
  addCaisseCurrentPage = 1; // Retour page 1 au tri
  renderAddCaisseTable();
};

// Gérer le changement de checkbox
function handleCheckboxChange(e) {
  const objet = JSON.parse(e.target.dataset.objet);

  if (e.target.checked) {
    // Ajouter l'objet s'il n'est pas déjà sélectionné
    if (!selectedObjects.some((o) => o.id === objet.id)) {
      selectedObjects.push(objet);
      updateSelectedObjectsDisplay();
    }
  } else {
    // Retirer l'objet
    selectedObjects = selectedObjects.filter((o) => o.id !== objet.id);
    updateSelectedObjectsDisplay();
  }
}

// Ajouter un objet par code-barre (scan)
async function addObjectByBarcode(codeBarre) {
  try {
    const response = await fetch(
      `php/getItemDetails.php?code_barre=${encodeURIComponent(codeBarre)}`,
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

      // Vérifier si déjà sélectionné
      if (selectedObjects.some((o) => o.id === objet.id)) {
        alert("Cet objet est déjà dans la sélection");
        return;
      }

      selectedObjects.push(objet);
      updateSelectedObjectsDisplay();

      // Mettre à jour la checkbox correspondante dans le tableau
      const checkbox = document.querySelector(
        `.objet-checkbox[data-objet*='"id":${objet.id}']`,
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

// Mettre à jour l'affichage des objets sélectionnés
function updateSelectedObjectsDisplay() {
  const listContainer = document.getElementById("objets_list_ajout");

  if (!listContainer) return;

  if (selectedObjects.length === 0) {
    listContainer.innerHTML =
      "<p style='color: #6c757d; margin: 10px;'>Aucun objet sélectionné</p>";
    return;
  }

  let html = "";
  selectedObjects.forEach((objet) => {
    html += `
      <div class="inline-flex items-center gap-2 bg-custom-brandLight/10 text-custom-brandDark px-3 py-1.5 rounded-full text-sm font-medium mr-2 mb-2 border border-custom-brandLight/20">
        ${objet.Code_bar} - ${objet.Type} <span class="opacity-70 text-xs">(${objet.Nom})</span>
        <span class="remove-objet cursor-pointer hover:text-custom-danger transition-colors ml-1 text-lg leading-none" onclick="removeObject(${objet.id})">&times;</span>
      </div>
    `;
  });

  listContainer.innerHTML = html;
}

// Retirer un objet de la sélection
window.removeObject = function (objetId) {
  selectedObjects = selectedObjects.filter((o) => o.id !== objetId);
  updateSelectedObjectsDisplay();

  // Mettre à jour les checkboxes si le tableau est affiché
  const checkbox = document.querySelector(
    `.objet-checkbox[data-objet*='"id":${objetId}']`,
  );
  if (checkbox) {
    checkbox.checked = false;
  }
};

// Soumettre le formulaire d'ajout de caisse
async function handleSubmitCaisse(e) {
  e.preventDefault();

  const nomCaisse = document.getElementById("nom_caisse_ajout").value.trim();

  if (!nomCaisse) {
    alert("Veuillez entrer un nom pour la caisse");
    return;
  }

  // Préparer la liste des IDs des objets sélectionnés
  const objets_ids = selectedObjects.map((o) => o.id);

  try {
    const formData = new FormData();
    formData.append("nom", nomCaisse);
    formData.append("objets_ids", JSON.stringify(objets_ids));

    const response = await fetch("php/addBox.php", {
      method: "POST",
      body: formData,
    });

    const data = await response.json();

    if (data.success) {
      alert(
        `Caisse "${nomCaisse}" ajoutée avec succès (${objets_ids.length} objets)`,
      );
      resetFormAjoutCaisse();

      // Rafraîchir l'inventaire pour mettre à jour la colonne Caisse
      if (window.refreshInventory) {
        window.refreshInventory();
      }

      // Rafraîchir aussi la table de modification
      if (window.reloadModifCaisseObjects) {
        window.reloadModifCaisseObjects();
      }
    } else {
      alert("Erreur: " + data.message);
    }
  } catch (error) {
    console.error("Erreur lors de l'ajout de la caisse:", error);
    alert("Erreur lors de l'ajout de la caisse");
  }
}

// Fonction de réinitialisation (aussi appelée depuis caisse_toggle.js)
window.resetFormAjoutCaisse = function () {
  document.getElementById("form_ajout_caisse").reset();
  selectedObjects = [];
  updateSelectedObjectsDisplay();

  // Recharger le tableau pour mettre à jour les checkboxes
  loadObjectsTable();
};

window.reloadAddCaisseObjects = loadObjectsTable;
