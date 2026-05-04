/**
 * filterConsultation.js — Filtres + tri + pagination de la vue de consultation,
 * et bascule entre la vue tableau et la vue groupée par caisse.
 *
 * Charge tout l'inventaire en mémoire au démarrage et applique les filtres
 * en local (pas de requête au serveur à chaque saisie).
 */

/** @type {Array<object>} Inventaire complet chargé une fois. */
let allInventory = [];

/** @type {boolean} true quand la vue groupée par caisses est active. */
let isCaissesViewActive = false;

const typeConsultation = document.getElementById("type_materiel_consultation");
const sousTypeConsultation = document.getElementById(
  "sous_type_materiel_consultation",
);
const nomConsultation = document.getElementById("nom_materiel_consultation");
const codeBarreConsultation = document.getElementById(
  "code_barre_consultation",
);

document.addEventListener("DOMContentLoaded", () => {
  if (
    !typeConsultation ||
    !sousTypeConsultation ||
    !nomConsultation ||
    !codeBarreConsultation
  ) {
    console.error("❌ Éléments de consultation non trouvés");
    return;
  }

  console.log("✅ Initialisation filtres consultation");

  loadAllInventory();

  const toggleCaisseCheckbox = document.getElementById(
    "toggle_caisse_consultation",
  );
  if (toggleCaisseCheckbox) {
    toggleCaisseCheckbox.addEventListener("change", (e) => {
      isCaissesViewActive = e.target.checked;
      if (isCaissesViewActive) {
        displayCaissesView();
      } else {
        restoreNormalView();
      }
    });
  }

  typeConsultation.addEventListener("change", () => {
    applyFilters();
  });

  sousTypeConsultation.addEventListener("change", () => {
    applyFilters();
  });

  nomConsultation.addEventListener("change", () => {
    applyFilters();
  });

  codeBarreConsultation.addEventListener("input", () => {
    applyFilters();
  });

  codeBarreConsultation.addEventListener("keydown", async (e) => {
    if (e.key === "Enter") {
      e.preventDefault();
      applyFilters();
    }
  });
});

/**
 * Auto-remplit Type et Nom à partir d'un code-barres scanné, puis filtre.
 * Conservée comme helper externe (pas câblée par défaut sur l'input).
 *
 * @param {string} code - Code-barres EAN-13.
 * @returns {Promise<void>}
 */
async function updateConsultationFields(code) {
  if (!code) {
    applyFilters();
    return;
  }

  try {
    const response = await fetch(
      `php/getItemDetails.php?code_barre=${encodeURIComponent(code)}`,
    );
    const data = await response.json();

    if (data.success && data.materiel) {
      const mat = data.materiel;

      console.log("📦 Objet trouvé:", mat);

      typeConsultation.value = mat.type_materiel;
      typeConsultation.dispatchEvent(new Event("change", { bubbles: true }));

      nomConsultation.value = mat.nom_materiel;

      applyFilters();
    } else {
      console.log("⚠️ Aucun objet trouvé pour ce code-barre");
      applyFilters();
    }
  } catch (error) {
    console.error("❌ Erreur récupération détails:", error);
    applyFilters();
  }
}

/**
 * Charge tout l'inventaire depuis le serveur et déclenche un premier rendu.
 *
 * @returns {Promise<void>}
 */
async function loadAllInventory() {
  console.log("📦 Chargement inventaire...");

  try {
    const response = await fetch("php/getAllItems.php");
    const data = await response.json();

    console.log("📊 Réponse PHP:", data);

    if (data.success && data.data) {
      allInventory = data.data;
      console.log("✅ Inventaire chargé:", allInventory.length, "objets");
      applyFilters();
    } else {
      console.error("❌ Pas de données");
    }
  } catch (error) {
    console.error("❌ Erreur chargement:", error);
  }
}

/**
 * Construit et affiche la vue groupée par caisse (un mini-tableau par caisse).
 *
 * @returns {Promise<void>}
 */
async function displayCaissesView() {
  console.log("📦 Chargement vue caisses...");

  try {
    const response = await fetch("php/getAllBoxes.php");
    const data = await response.json();

    if (!data.success || !data.data) {
      console.error(
        "❌ Erreur chargement caisses:",
        data.error || "Pas de données",
      );
      return;
    }

    const caisses = data.data;

    const inventoryDiv = document.getElementById("full_inventory");
    const caissesView = document.getElementById("caisses_view");

    if (inventoryDiv) inventoryDiv.style.display = "none";
    if (!caissesView) {
      console.error("❌ Conteneur caisses_view non trouvé");
      return;
    }

    caissesView.style.display = "block";
    caissesView.innerHTML = "";

    if (caisses.length === 0) {
      caissesView.innerHTML = `<p style="text-align: center; padding: 20px;">Aucune caisse trouvée</p>`;
      return;
    }

    caisses.forEach((caisse) => {
      const objets = Array.isArray(caisse.Contenu) ? caisse.Contenu : [];

      const caisseDiv = document.createElement("div");
      caisseDiv.style.marginBottom = "30px";

      const header = document.createElement("div");
      header.className =
        "flex justify-between items-center p-4 bg-gray-50 rounded-t-lg border border-gray-200 border-b-0";

      const nomSpan = document.createElement("strong");
      nomSpan.textContent = caisse.Nom;
      nomSpan.className = "text-lg text-gray-800";

      const userSpan = document.createElement("span");
      if (caisse.Prénom && caisse.Nom_utilisateur) {
        let actionTexte = "Réservé par";
        if (caisse.Etat && caisse.Etat.toLowerCase() === "emprunté") {
          actionTexte = "Emprunté par";
        }
        userSpan.textContent = `${actionTexte} : ${caisse.Prénom} ${caisse.Nom_utilisateur}`;
      } else {
        userSpan.textContent = "Disponible";
      }
      userSpan.className = "italic text-gray-500 text-sm";

      header.appendChild(nomSpan);
      header.appendChild(userSpan);

      const table = document.createElement("table");
      table.className =
        "w-full border-collapse border border-gray-200 bg-white shadow-sm rounded-b-lg overflow-hidden";

      const thead = document.createElement("thead");
      thead.innerHTML = `
        <tr class="bg-gray-100/50 text-gray-600 border-b border-gray-100">
          <th class="p-3 text-center font-semibold text-sm w-32 border-r border-gray-100">Code-barre</th>
          <th class="p-3 text-center font-semibold text-sm">Type</th>
          <th class="p-3 text-center font-semibold text-sm">Sous-type</th>
          <th class="p-3 text-center font-semibold text-sm">Nom</th>
          <th class="p-3 text-center font-semibold text-sm">État</th>
        </tr>
      `;
      table.appendChild(thead);

      const tbody = document.createElement("tbody");
      if (objets.length === 0) {
        tbody.innerHTML = `<tr><td colspan="5" class="text-center p-5 text-gray-400 italic text-sm border-t border-gray-100">Aucun objet dans cette caisse</td></tr>`;
      } else {
        objets.forEach((objet) => {
          const tr = document.createElement("tr");

          let etatStyle = "text-green-600 font-medium";
          if (objet.Etat && objet.Etat.toLowerCase() === "réservé") {
            etatStyle = "text-orange-500 font-semibold";
          } else if (objet.Etat && objet.Etat.toLowerCase() === "emprunté") {
            etatStyle = "text-red-500 font-semibold";
          }

          tr.className =
            "border-b border-gray-100 last:border-0";
          tr.innerHTML = `
            <td class="p-3 text-center text-sm border-r border-gray-100">${objet.Code_bar || "-"}</td>
            <td class="p-3 text-center text-sm border-r border-gray-100">${objet.Type || "-"}</td>
            <td class="p-3 text-center text-sm border-r border-gray-100">${objet.Sous_type || "-"}</td>
            <td class="p-3 text-center text-sm border-r border-gray-100 text-gray-600">${objet.Nom || "-"}</td>
            <td class="p-3 text-center text-sm ${etatStyle}">${objet.Etat || "disponible"}</td>
          `;
          tbody.appendChild(tr);
        });
      }
      table.appendChild(tbody);

      caisseDiv.appendChild(header);
      caisseDiv.appendChild(table);
      caissesView.appendChild(caisseDiv);
    });

    console.log(`✅ ${caisses.length} caisses affichées`);
  } catch (error) {
    console.error("❌ Erreur vue caisses:", error);
  }
}

/**
 * Restaure la vue tableau (et masque la vue groupée par caisses).
 *
 * @returns {void}
 */
function restoreNormalView() {
  console.log("🔄 Restauration vue normale...");

  const inventoryDiv = document.getElementById("full_inventory");
  const caissesView = document.getElementById("caisses_view");

  if (inventoryDiv) inventoryDiv.style.display = "block";
  if (caissesView) caissesView.style.display = "none";

  applyFilters();
}

let currentPage = 1;
const itemsPerPage = 15;
let currentSortColumn = null;
let currentSortDirection = "asc";

/**
 * Filtre `allInventory` selon les champs courants, applique le tri,
 * pagine et envoie la page courante au tableau d'inventaire.
 *
 * @returns {void}
 */
function applyFilters() {
  const typeValue = typeConsultation.value.trim();
  const sousTypeValue = sousTypeConsultation.value.trim();
  const nomValue = nomConsultation.value.trim();
  const codeBarreValue = codeBarreConsultation.value.trim();

  let filtered = [...allInventory];

  if (codeBarreValue) {
    filtered = filtered.filter((item) =>
      item.Code_bar.toLowerCase().includes(codeBarreValue.toLowerCase()),
    );
  }

  if (typeValue) {
    filtered = filtered.filter((item) => item.Type === typeValue);
  }

  if (sousTypeValue) {
    filtered = filtered.filter((item) => item.Sous_type === sousTypeValue);
  }

  if (nomValue) {
    filtered = filtered.filter((item) => item.Nom === nomValue);
  }

  if (currentSortColumn) {
    filtered.sort((a, b) =>
      window.localeSortComparator(
        a,
        b,
        currentSortColumn,
        currentSortDirection,
      ),
    );
  }

  const sortColumns = ["Type", "Sous_type", "Nom", "Etat", "Utilisateur", "Nom_caisse", "created_at"];
  sortColumns.forEach((col) => {
    const iconSpan = document.getElementById(`sort_icon_${col}`);
    if (iconSpan) {
      if (col === currentSortColumn) {
        iconSpan.textContent = currentSortDirection === "asc" ? "↑" : "↓";
        iconSpan.classList.remove("opacity-50");
      } else {
        iconSpan.textContent = "↕";
        iconSpan.classList.add("opacity-50");
      }
    }
  });

  console.log("✅ Résultats filtrés:", filtered.length);

  const totalItems = filtered.length;
  const totalPages = Math.ceil(totalItems / itemsPerPage) || 1;

  if (currentPage > totalPages) {
    currentPage = totalPages;
  }

  const startIndex = (currentPage - 1) * itemsPerPage;
  const endIndex = startIndex + itemsPerPage;
  const paginatedItems = filtered.slice(startIndex, endIndex);

  updateInventoryTable(paginatedItems);

  const btnPrev = document.getElementById("btn_prev_page");
  const btnNext = document.getElementById("btn_next_page");
  const pageInfo = document.getElementById("page_info");

  if (btnPrev && btnNext && pageInfo) {
    pageInfo.textContent = `Page ${currentPage} / ${totalPages}`;
    btnPrev.disabled = currentPage === 1;
    btnNext.disabled = currentPage === totalPages;
  }

  updateResultsCounter(filtered.length, allInventory.length);
}

/**
 * Change la page courante du tableau de consultation.
 *
 * @param {number} delta - +1 pour suivante, -1 pour précédente.
 * @returns {void}
 */
window.changePage = function (delta) {
  currentPage += delta;
  applyFilters();
};

/**
 * Trie le tableau de consultation sur la colonne donnée
 * (toggle asc/desc si déjà active, retour à la page 1).
 *
 * @param {string} column - Nom de la propriété à trier.
 * @returns {void}
 */
window.sortInventory = function (column) {
  if (currentSortColumn === column) {
    currentSortDirection = currentSortDirection === "asc" ? "desc" : "asc";
  } else {
    currentSortColumn = column;
    currentSortDirection = "asc";
  }
  currentPage = 1;
  applyFilters();
};

/**
 * Réécrit le `<tbody>` du tableau d'inventaire avec les items fournis.
 *
 * @param {Array<object>} data - Items à afficher (page courante).
 * @returns {void}
 */
function updateInventoryTable(data) {
  const tbody = document.querySelector("#inventory_table tbody");
  if (!tbody) {
    console.error("❌ Tbody non trouvé");
    return;
  }

  tbody.innerHTML = "";

  if (data.length === 0) {
    const tr = document.createElement("tr");
    tr.innerHTML =
      '<td colspan="9" style="text-align: center; padding: 20px;">Aucun résultat trouvé</td>';
    tbody.appendChild(tr);
    return;
  }

  let compteurLignes = 0;

  data.forEach((item) => {
    compteurLignes++;
    const tr = document.createElement("tr");

    let utilisateur = "-";
    if (item.Prénom && item.Nom_utilisateur) {
      utilisateur = `${item.Prénom} ${item.Nom_utilisateur}`;
    } else if (item.Prénom) {
      utilisateur = item.Prénom;
    } else if (item.Nom_utilisateur) {
      utilisateur = item.Nom_utilisateur;
    }

    let etatColor = "text-green-600 font-medium";
    if (item.Etat.toLowerCase() === "réservé") {
      etatColor = "text-orange-500 font-bold";
    } else if (item.Etat.toLowerCase() === "emprunté") {
      etatColor = "text-red-500 font-bold";
    }

    tr.className =
      "border-b border-custom-border last:border-0 bg-white";
    tr.innerHTML = `
      <td class="p-4.5 text-center text-sm align-middle">${item.Code_bar}</td>
      <td class="p-4.5 text-center text-sm align-middle">${item.Type}</td>
      <td class="p-4.5 text-center text-sm align-middle">${item.Sous_type || "-"}</td>
      <td class="p-4.5 text-center text-sm align-middle text-gray-600">${item.Nom}</td>
      <td class="p-4.5 text-center text-sm align-middle ${etatColor}">${item.Etat}</td>
      <td class="p-4.5 text-center text-sm align-middle text-gray-600">${utilisateur}</td>
      <td class="p-4.5 text-center text-sm align-middle text-gray-600">${item.created_at ? new Date(item.created_at).toLocaleDateString('fr-FR') : "-"}</td>
      <td class="p-4.5 text-center text-sm align-middle">
        <button
          onclick="openCommentModal(${item.id}, ${item.id_com ? 'true' : 'false'})"
          class="px-3 py-1.5 rounded-lg text-xs font-semibold border ${item.id_com ? 'border-custom-brandLight text-custom-brandLight' : 'border-gray-300 text-gray-500'}"
        >
          ${item.id_com ? 'Voir le commentaire' : 'Ajouter un commentaire'}
        </button>
      </td>
    `;
    tbody.appendChild(tr);
  });

  console.log("✅ Tableau rempli:", compteurLignes, "lignes insérées");
}

/**
 * Met à jour le badge "X sur Y" du nombre de résultats affichés.
 *
 * @param {number} filtered - Nombre d'items après filtrage.
 * @param {number} total - Nombre total d'items chargés.
 * @returns {void}
 */
function updateResultsCounter(filtered, total) {
  const counterSpan = document.getElementById("inventory_total");
  if (counterSpan) {
    if (filtered === total) {
      counterSpan.textContent = total;
    } else {
      counterSpan.textContent = `${filtered} sur ${total}`;
    }
  }
}

/**
 * Réinitialise les filtres, le tri et la pagination de la consultation.
 *
 * @returns {void}
 */
function resetFiltersConsultation() {
  codeBarreConsultation.value = "";
  typeConsultation.value = "";
  sousTypeConsultation.value = "";
  nomConsultation.value = "";

  currentPage = 1;
  currentSortColumn = null;
  currentSortDirection = "asc";

  typeConsultation.dispatchEvent(new Event("change"));
}

/**
 * Pré-remplit le filtre Type quand on sélectionne un Nom seul,
 * en cherchant un objet inventaire qui porte ce nom.
 *
 * @param {string} nomValue - Nom de référence sélectionné.
 * @returns {Promise<void>}
 */
async function autoFillTypeFromNom(nomValue) {
  if (!nomValue || !allInventory.length) return;

  const objet = allInventory.find((item) => item.Nom === nomValue);

  if (objet && objet.Type) {
    console.log("🔄 Remplissage auto Type depuis Nom:", objet.Type);
    typeConsultation.value = objet.Type;

    if (window.nomAutocompleteConsult) {
      window.nomAutocompleteConsult.dataFetcher = async () => {
        const response = await fetch(
          `php/get_noms.php?type=${encodeURIComponent(objet.Type)}`,
        );
        const data = await response.json();
        return data.success ? data.noms : [];
      };
      await window.nomAutocompleteConsult.loadData();
    }
  }
}

window.resetFiltersConsultation = resetFiltersConsultation;

/**
 * Recharge l'inventaire depuis le serveur et rafraîchit la vue active.
 *
 * @returns {Promise<void>}
 */
window.refreshInventory = async function () {
  await loadAllInventory();
  if (isCaissesViewActive) {
    await displayCaissesView();
  }
};
window.applyFiltersConsultation = applyFilters;
window.autoFillTypeFromNom = autoFillTypeFromNom;
