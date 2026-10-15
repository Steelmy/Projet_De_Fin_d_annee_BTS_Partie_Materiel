/**
 * filterConsultation.js — Filtres + tri + pagination de la vue de consultation,
 * et bascule entre la vue tableau et la vue groupée par caisse.
 *
 * Pagination côté serveur : seule la page affichée est demandée à
 * `php/getAllItems.php`, avec les filtres et le tri appliqués en SQL.
 */

/** @type {Array<object>} Lignes de la page actuellement affichée. */
let currentPageItems = [];

/** @type {number} Numéro de la dernière requête lancée (les réponses plus anciennes sont ignorées). */
let inventoryRequestId = 0;

/** @type {number|undefined} Minuterie de temporisation de la saisie du code-barre. */
let codeBarreDebounce;

/** @type {number|undefined} Minuterie regroupant les `change` émis en cascade par dynamicSelects.js. */
let filtersChangeTimer;

/** @type {string|null} Filtres de la dernière requête lancée (voir `currentFiltersKey`). */
let lastFiltersKey = null;

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

  loadInventoryPage();

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

  typeConsultation.addEventListener("change", scheduleFiltersChange);
  sousTypeConsultation.addEventListener("change", scheduleFiltersChange);
  nomConsultation.addEventListener("change", scheduleFiltersChange);

  // Une requête par pause de frappe plutôt qu'une par caractère.
  codeBarreConsultation.addEventListener("input", () => {
    clearTimeout(codeBarreDebounce);
    codeBarreDebounce = setTimeout(applyFilters, 300);
  });

  codeBarreConsultation.addEventListener("keydown", async (e) => {
    if (e.key === "Enter") {
      e.preventDefault();
      clearTimeout(codeBarreDebounce);
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
 * Clé décrivant les valeurs courantes des quatre filtres.
 *
 * @returns {string}
 */
function currentFiltersKey() {
  return JSON.stringify([
    codeBarreConsultation.value.trim(),
    typeConsultation.value.trim(),
    sousTypeConsultation.value.trim(),
    nomConsultation.value.trim(),
  ]);
}

/**
 * Réagit à un `change` sur Type/Sous-type/Nom. dynamicSelects.js en émet plusieurs
 * d'affilée (cascade, repeuplement des types) : on attend la fin de la cascade et
 * on ne recharge que si les filtres ont réellement changé.
 *
 * @returns {void}
 */
function scheduleFiltersChange() {
  clearTimeout(filtersChangeTimer);
  filtersChangeTimer = setTimeout(() => {
    if (currentFiltersKey() !== lastFiltersKey) applyFilters();
  }, 0);
}

/**
 * Demande au serveur la page courante (filtres + tri + pagination) et l'affiche.
 * Si plusieurs requêtes se chevauchent, seule la plus récente est affichée.
 *
 * @returns {Promise<void>}
 */
async function loadInventoryPage() {
  const requestId = ++inventoryRequestId;
  lastFiltersKey = currentFiltersKey();

  const params = new URLSearchParams({
    page: currentPage,
    per_page: itemsPerPage,
    code_barre: codeBarreConsultation.value.trim(),
    type: typeConsultation.value.trim(),
    sous_type: sousTypeConsultation.value.trim(),
    nom: nomConsultation.value.trim(),
  });
  if (currentSortColumn) {
    params.set("sort", currentSortColumn);
    params.set("direction", currentSortDirection);
  }

  try {
    const response = await fetch(`php/getAllItems.php?${params}`);
    const data = await response.json();
    if (requestId !== inventoryRequestId) return;

    if (!data.success || !Array.isArray(data.data)) {
      console.error("❌ Erreur chargement inventaire:", data.message);
      return;
    }

    currentPageItems = data.data;
    currentPage = data.page;
    const totalPages = Math.ceil(data.total / data.per_page) || 1;

    updateInventoryTable(currentPageItems);
    updateSortIcons();

    const btnPrev = document.getElementById("btn_prev_page");
    const btnNext = document.getElementById("btn_next_page");
    const pageInfo = document.getElementById("page_info");
    if (btnPrev && btnNext && pageInfo) {
      pageInfo.textContent = `Page ${currentPage} / ${totalPages}`;
      btnPrev.disabled = currentPage === 1;
      btnNext.disabled = currentPage === totalPages;
    }

    updateResultsCounter(data.total, data.total_all);
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
            <td class="p-3 text-center text-sm border-r border-gray-100">${escapeHtml(objet.Code_bar || "-")}</td>
            <td class="p-3 text-center text-sm border-r border-gray-100">${escapeHtml(objet.Type || "-")}</td>
            <td class="p-3 text-center text-sm border-r border-gray-100">${escapeHtml(objet.Sous_type || "-")}</td>
            <td class="p-3 text-center text-sm border-r border-gray-100 text-gray-600">${escapeHtml(objet.Nom || "-")}</td>
            <td class="p-3 text-center text-sm ${etatStyle}">${escapeHtml(objet.Etat || "disponible")}</td>
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

  loadInventoryPage();
}

let currentPage = 1;
const itemsPerPage = 15;
let currentSortColumn = null;
let currentSortDirection = "asc";

/**
 * Applique les filtres courants : revient en page 1 et recharge depuis le serveur.
 *
 * @returns {Promise<void>}
 */
function applyFilters() {
  currentPage = 1;
  return loadInventoryPage();
}

/**
 * Met à jour les icônes de tri des en-têtes selon la colonne et le sens actifs.
 *
 * @returns {void}
 */
function updateSortIcons() {
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
}

/**
 * Change la page courante du tableau de consultation.
 *
 * @param {number} delta - +1 pour suivante, -1 pour précédente.
 * @returns {void}
 */
window.changePage = function (delta) {
  currentPage = Math.max(1, currentPage + delta);
  loadInventoryPage();
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
      <td class="p-4.5 text-center text-sm align-middle">${escapeHtml(item.Code_bar)}</td>
      <td class="p-4.5 text-center text-sm align-middle">${escapeHtml(item.Type)}</td>
      <td class="p-4.5 text-center text-sm align-middle">${escapeHtml(item.Sous_type || "-")}</td>
      <td class="p-4.5 text-center text-sm align-middle text-gray-600">${escapeHtml(item.Nom)}</td>
      <td class="p-4.5 text-center text-sm align-middle ${etatColor}">${escapeHtml(item.Etat)}</td>
      <td class="p-4.5 text-center text-sm align-middle text-gray-600">${escapeHtml(utilisateur)}</td>
      <td class="p-4.5 text-center text-sm align-middle text-gray-600">${item.created_at ? new Date(item.created_at).toLocaleDateString('fr-FR') : "-"}</td>
      <td class="p-4.5 text-center text-sm align-middle">
        <button
          onclick="openCommentModal(${item.id}, ${item.id_com ? 'true' : 'false'})"
          data-comment-btn="1"
          data-id-com="${item.id_com || ''}"
          class="relative px-3 py-1.5 rounded-lg text-xs font-semibold border ${item.id_com ? 'border-custom-brandLight text-custom-brandLight' : 'border-gray-300 text-gray-500'}"
        >
          ${item.id_com ? 'Voir le commentaire' : 'Ajouter un commentaire'}
        </button>
      </td>
    `;
    tbody.appendChild(tr);
  });

  if (window.commentNotifications) {
    window.commentNotifications.applyBadges();
  }

  console.log("✅ Tableau rempli:", compteurLignes, "lignes insérées");
}

/**
 * Met à jour le badge "X sur Y" du nombre de résultats affichés.
 *
 * @param {number} filtered - Nombre d'objets correspondant aux filtres.
 * @param {number} total - Nombre total d'objets en base.
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

  // Le change remet les selects en cascade à zéro ; le rechargement est fait ici
  // (le tri a pu changer même si les filtres étaient déjà vides).
  typeConsultation.dispatchEvent(new Event("change"));
  clearTimeout(filtersChangeTimer);
  clearTimeout(codeBarreDebounce);
  applyFilters();
}

/**
 * Pré-remplit le filtre Type quand on sélectionne un Nom seul,
 * en cherchant ce nom dans l'arbre des références (`window.referenceTree`).
 *
 * @param {string} nomValue - Nom de référence sélectionné.
 * @returns {void}
 */
function autoFillTypeFromNom(nomValue) {
  const tree = window.referenceTree || {};
  const type = Object.keys(tree).find((t) =>
    Object.values(tree[t]).some((noms) => noms.includes(nomValue)),
  );

  if (nomValue && type) {
    console.log("🔄 Remplissage auto Type depuis Nom:", type);
    typeConsultation.value = type;
  }
}

window.resetFiltersConsultation = resetFiltersConsultation;

/**
 * Recharge la page courante depuis le serveur, les pastilles de commentaires
 * et la vue caisses si elle est active.
 *
 * @returns {Promise<void>}
 */
window.refreshInventory = async function () {
  if (window.commentNotifications) {
    await window.commentNotifications.reload();
  }
  await loadInventoryPage();
  if (isCaissesViewActive) {
    await displayCaissesView();
  }
};
window.applyFiltersConsultation = applyFilters;
window.autoFillTypeFromNom = autoFillTypeFromNom;
