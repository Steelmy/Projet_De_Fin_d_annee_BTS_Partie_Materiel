// Gestion du filtrage de l'inventaire dans la section consultation
// Utilise autocomplete_types_noms.js pour les champs Type et Nom

let allInventory = []; // Stockage de tous les objets
let isCaissesViewActive = false; // État de la vue caisses

// Sélecteurs
const typeConsultation = document.getElementById("type_materiel_consultation");
const nomConsultation = document.getElementById("nom_materiel_consultation");
const codeBarreConsultation = document.getElementById(
  "code_barre_consultation",
);

document.addEventListener("DOMContentLoaded", () => {
  if (!typeConsultation || !nomConsultation || !codeBarreConsultation) {
    console.error("❌ Éléments de consultation non trouvés");
    return;
  }

  console.log("✅ Initialisation filtres consultation");

  // Charger l'inventaire complet
  loadAllInventory();

  // Listener pour la checkbox "Voir uniquement les caisses"
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

  // Écouter changement de Type → filtrer
  typeConsultation.addEventListener("change", () => {
    applyFilters();
  });

  // Écouter changement de Nom → filtrer
  nomConsultation.addEventListener("change", () => {
    applyFilters();
  });

  // Écouter saisie Code-barre → remplir Type+Nom + filtrer
  codeBarreConsultation.addEventListener("change", () => {
    updateConsultationFields(codeBarreConsultation.value.trim());
  });

  // Gestion Entrée pour scan rapide
  codeBarreConsultation.addEventListener("keydown", async (e) => {
    if (e.key === "Enter") {
      e.preventDefault();
      await updateConsultationFields(codeBarreConsultation.value.trim());
    }
  });

  // Autocomplétion code-barre filtrée par Type et Nom
  let debounceTimer;
  codeBarreConsultation.addEventListener("input", () => {
    clearTimeout(debounceTimer);
    const query = codeBarreConsultation.value.trim();

    if (query.length === 0) {
      hideBarcodesSuggestions();
      applyFilters();
      return;
    }

    debounceTimer = setTimeout(() => {
      showBarcodesSuggestions(query);
      applyFilters();
    }, 300);
  });

  // Fermer suggestions au clic extérieur
  document.addEventListener("click", (e) => {
    const suggestionsDiv = document.getElementById(
      "barcode_suggestions_consultation",
    );
    if (
      !codeBarreConsultation.contains(e.target) &&
      !suggestionsDiv.contains(e.target)
    ) {
      hideBarcodesSuggestions();
    }
  });
});

// Afficher suggestions de codes-barres filtrées
function showBarcodesSuggestions(query) {
  const suggestionsDiv = document.getElementById(
    "barcode_suggestions_consultation",
  );
  const typeValue = typeConsultation.value.trim();
  const nomValue = nomConsultation.value.trim();

  console.log("🔍 Filtrage codes-barres:");
  console.log("  - Query:", query);
  console.log("  - Type sélectionné:", typeValue || "(vide)");
  console.log("  - Nom sélectionné:", nomValue || "(vide)");
  console.log("  - Total inventaire:", allInventory.length);

  // Filtrer les codes-barres selon Type, Nom ET query
  let filtered = allInventory.filter((item) => {
    const matchesQuery = item.Code_bar.toLowerCase().includes(
      query.toLowerCase(),
    );
    const matchesType = !typeValue || item.Type === typeValue;
    const matchesNom = !nomValue || item.Nom === nomValue;

    return matchesQuery && matchesType && matchesNom;
  });

  console.log("✅ Résultats filtrés:", filtered.length);
  if (filtered.length > 0) {
    console.log(
      "  Exemples:",
      filtered.slice(0, 3).map((i) => `${i.Code_bar} (${i.Type} - ${i.Nom})`),
    );
  }

  suggestionsDiv.innerHTML = "";

  if (filtered.length === 0) {
    suggestionsDiv.classList.remove("show");
    return;
  }

  // Limiter à 10 suggestions
  filtered.slice(0, 10).forEach((item) => {
    const div = document.createElement("div");
    div.className = "autocomplete-suggestion";
    div.innerHTML = `
      <strong>${item.Code_bar}</strong> - ${item.Type} - ${item.Nom}
    `;

    div.addEventListener("click", () => {
      codeBarreConsultation.value = item.Code_bar;
      updateConsultationFields(item.Code_bar);
      hideBarcodesSuggestions();
    });

    suggestionsDiv.appendChild(div);
  });

  suggestionsDiv.classList.add("show");
}

// Masquer suggestions code-barre
function hideBarcodesSuggestions() {
  const suggestionsDiv = document.getElementById(
    "barcode_suggestions_consultation",
  );
  if (suggestionsDiv) {
    suggestionsDiv.classList.remove("show");
  }
}

// AUTO-REMPLIR type + nom depuis le code-barre (comme dans delete_materiel.js)
async function updateConsultationFields(code) {
  if (!code) {
    applyFilters();
    return;
  }

  try {
    const response = await fetch(
      `PHP/get_materiel_details.php?code_barre=${encodeURIComponent(code)}`,
    );
    const data = await response.json();

    if (data.success && data.materiel) {
      const mat = data.materiel;

      console.log("📦 Objet trouvé:", mat);

      // Remplir Type
      typeConsultation.value = mat.type_materiel;
      // Déclencher change pour que autocomplete mette à jour
      typeConsultation.dispatchEvent(new Event("change", { bubbles: true }));

      // Remplir Nom
      nomConsultation.value = mat.nom_materiel;

      // Appliquer les filtres
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

// Charger tout l'inventaire
async function loadAllInventory() {
  console.log("📦 Chargement inventaire...");

  try {
    const response = await fetch("PHP/get_all_materiel.php");
    const data = await response.json();

    console.log("📊 Réponse PHP:", data);

    if (data.success && data.data) {
      allInventory = data.data;
      console.log("✅ Inventaire chargé:", allInventory.length, "objets");
      applyFilters(); // Afficher tout initialement
    } else {
      console.error("❌ Pas de données");
    }
  } catch (error) {
    console.error("❌ Erreur chargement:", error);
  }
}

// Afficher la vue groupée des caisses
async function displayCaissesView() {
  console.log("📦 Chargement vue caisses...");

  try {
    const response = await fetch("PHP/get_all_caisses.php");
    const data = await response.json();

    if (!data.success || !data.data) {
      console.error(
        "❌ Erreur chargement caisses:",
        data.error || "Pas de données",
      );
      return;
    }

    const caisses = data.data;

    // Masquer les filtres et tableau normal, afficher conteneur caisses
    const filterSection = document.getElementById("filters_consultation");
    const inventoryDiv = document.getElementById("full_inventory");
    const caissesView = document.getElementById("caisses_view");

    if (filterSection) filterSection.classList.add("hidden");
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
      // Le PHP a déjà décodé le JSON, donc Contenu est déjà un tableau
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
        userSpan.textContent = `Réservé par : ${caisse.Prénom} ${caisse.Nom_utilisateur}`;
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
        <tr class="bg-linear-to-br from-custom-brandLight to-custom-brandDark text-white">
          <th class="p-3 text-center font-semibold text-sm">Code-barre</th>
          <th class="p-3 text-center font-semibold text-sm">Type</th>
          <th class="p-3 text-center font-semibold text-sm">Nom</th>
          <th class="p-3 text-center font-semibold text-sm">État</th>
        </tr>
      `;
      table.appendChild(thead);

      const tbody = document.createElement("tbody");
      if (objets.length === 0) {
        tbody.innerHTML = `<tr><td colspan="4" class="text-center p-5 text-gray-400 italic text-sm border-t border-gray-100">Aucun objet dans cette caisse</td></tr>`;
      } else {
        objets.forEach((objet, index) => {
          const tr = document.createElement("tr");

          // Couleurs pour états
          let etatStyle = "text-green-600 font-medium";
          if (objet.Etat && objet.Etat.toLowerCase() === "réservé") {
            etatStyle = "text-orange-500 font-semibold";
          } else if (objet.Etat && objet.Etat.toLowerCase() === "emprunté") {
            etatStyle = "text-red-500 font-semibold";
          }

          tr.className =
            "hover:bg-gray-50 transition-colors border-b border-gray-100 last:border-0";
          tr.innerHTML = `
            <td class="p-3 text-center text-sm border-r border-gray-100">${objet.Code_bar || "-"}</td>
            <td class="p-3 text-center text-sm border-r border-gray-100">${objet.Type || "-"}</td>
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

// Restaurer la vue normale (tableau + filtres)
function restoreNormalView() {
  console.log("🔄 Restauration vue normale...");

  const filterSection = document.getElementById("filters_consultation");
  const inventoryDiv = document.getElementById("full_inventory");
  const caissesView = document.getElementById("caisses_view");
  const modeSelector = document.getElementById("modeSelector");

  // Afficher tableau, masquer caisses
  if (inventoryDiv) inventoryDiv.style.display = "block";
  if (caissesView) caissesView.style.display = "none";

  // Re-afficher les filtres QUE si on est dans l'onglet consultation
  if (filterSection && modeSelector && modeSelector.value === "consultation") {
    filterSection.classList.remove("hidden");
  }

  // Recharger les données du tableau
  applyFilters();
}

// FONCTION PRINCIPALE : Appliquer les filtres
function applyFilters() {
  const typeValue = typeConsultation.value.trim();
  const nomValue = nomConsultation.value.trim();
  const codeBarreValue = codeBarreConsultation.value.trim();

  let filtered = [...allInventory];

  // Priorité 1 : Code-barre (recherche partielle)
  if (codeBarreValue) {
    filtered = filtered.filter((item) =>
      item.Code_bar.toLowerCase().includes(codeBarreValue.toLowerCase()),
    );
  }

  // Priorité 2 : Type
  if (typeValue) {
    filtered = filtered.filter((item) => item.Type === typeValue);
  }

  // Priorité 3 : Nom
  if (nomValue) {
    filtered = filtered.filter((item) => item.Nom === nomValue);
  }

  console.log("✅ Résultats:", filtered.length);

  // Mettre à jour le tableau
  updateInventoryTable(filtered);

  // Mettre à jour le compteur
  updateResultsCounter(filtered.length, allInventory.length);
}

// Mettre à jour le tableau d'inventaire
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
      '<td colspan="6" style="text-align: center; padding: 20px;">Aucun résultat trouvé</td>';
    tbody.appendChild(tr);
    return;
  }

  let compteurLignes = 0;

  data.forEach((item) => {
    compteurLignes++;
    const tr = document.createElement("tr");

    // Construire le nom complet utilisateur (Prénom + Nom_utilisateur)
    let utilisateur = "-";
    if (item.Prénom && item.Nom_utilisateur) {
      utilisateur = `${item.Prénom} ${item.Nom_utilisateur}`;
    } else if (item.Prénom) {
      utilisateur = item.Prénom;
    } else if (item.Nom_utilisateur) {
      utilisateur = item.Nom_utilisateur;
    }

    // Déterminer la couleur selon l'état
    let etatColor = "text-green-600 font-medium";

    if (item.Etat.toLowerCase() === "réservé") {
      etatColor = "text-orange-500 font-bold";
    } else if (item.Etat.toLowerCase() === "emprunté") {
      etatColor = "text-red-500 font-bold";
    }

    tr.className =
      "hover:bg-linear-to-r hover:from-custom-brandLight/5 hover:to-custom-brandLight/5 transition-all duration-300 border-l-4 border-transparent hover:border-custom-brandLight hover:translate-x-2 border-b border-custom-border last:border-0 hover:shadow-[0_4px_15px_rgba(0,0,0,0.08)] bg-white";
    tr.innerHTML = `
      <td class="p-4.5 text-center text-sm align-middle">${item.Code_bar}</td>
      <td class="p-4.5 text-center text-sm align-middle">${item.Type}</td>
      <td class="p-4.5 text-center text-sm align-middle text-gray-600">${item.Nom}</td>
      <td class="p-4.5 text-center text-sm align-middle ${etatColor}">${item.Etat}</td>
      <td class="p-4.5 text-center text-sm align-middle text-gray-600">${utilisateur}</td>
      <td class="p-4.5 text-center text-sm align-middle text-gray-600">${item.Nom_caisse || "-"}</td>
    `;
    tbody.appendChild(tr);
  });

  console.log("✅ Tableau rempli:", compteurLignes, "lignes insérées");
}

// Mettre à jour le compteur de résultats
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

// Réinitialiser tous les filtres
function resetFiltersConsultation() {
  typeConsultation.value = "";
  nomConsultation.value = "";
  codeBarreConsultation.value = "";

  // Réafficher tout
  applyFilters();
}

// Remplir automatiquement le type depuis le nom sélectionné
async function autoFillTypeFromNom(nomValue) {
  if (!nomValue || !allInventory.length) return;

  // Chercher un objet avec ce nom
  const objet = allInventory.find((item) => item.Nom === nomValue);

  if (objet && objet.Type) {
    console.log("🔄 Remplissage auto Type depuis Nom:", objet.Type);
    typeConsultation.value = objet.Type;

    // Recharger les noms pour ce type via l'autocomplete
    if (window.nomAutocompleteConsult) {
      window.nomAutocompleteConsult.dataFetcher = async () => {
        const response = await fetch(
          `PHP/get_noms.php?type=${encodeURIComponent(objet.Type)}`,
        );
        const data = await response.json();
        return data.success ? data.noms : [];
      };
      await window.nomAutocompleteConsult.loadData();
    }
  }
}

// Exposer pour utilisation externe
window.resetFiltersConsultation = resetFiltersConsultation;
window.refreshInventory = loadAllInventory;
window.applyFiltersConsultation = applyFilters;
window.autoFillTypeFromNom = autoFillTypeFromNom;
