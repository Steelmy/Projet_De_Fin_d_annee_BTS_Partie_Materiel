// Gestion de la suppression de caisses

let autocompleteSuppr;

document.addEventListener("DOMContentLoaded", () => {
  const formSuppressionCaisse = document.getElementById(
    "form_suppression_caisse",
  );
  const nomInput = document.getElementById("nom_caisse_suppr");

  if (formSuppressionCaisse && nomInput) {
    formSuppressionCaisse.addEventListener("submit", handleDeleteCaisse);
    initializeAutocomplete();
  }
});

// Initialiser l'autocomplétion avec la classe UniversalAutocomplete
function initializeAutocomplete() {
  // Utiliser la nouvelle classe unifiée
  new UniversalAutocomplete("nom_caisse_suppr", "caisse", async (item) => {
    await loadCaisseDetails(item.value);
  });
}

// Charger les détails d'une caisse
async function loadCaisseDetails(nom) {
  try {
    const response = await fetch(
      `PHP/get_caisse_details.php?nom=${encodeURIComponent(nom)}`,
    );
    const data = await response.json();

    if (data.success && data.caisse) {
      displayCaisseDetails(data.caisse);
    }
  } catch (error) {
    console.error("Erreur chargement détails:", error);
  }
}

// Afficher les détails de la caisse
function displayCaisseDetails(caisse) {
  const detailsDiv = document.getElementById("caisse_details_suppr");

  if (!detailsDiv) return;

  let contenuHTML = "";
  if (caisse.contenu && caisse.contenu.length > 0) {
    caisse.contenu.forEach((objet) => {
      contenuHTML += `<div class="py-1.5 border-b border-gray-100 last:border-0"><span class="font-mono text-xs text-gray-500">${objet.Code_bar}</span> - <span class="font-medium text-gray-800">${objet.Type}</span> <span class="text-gray-500 text-xs">(${objet.Nom})</span></div>`;
    });
  } else {
    contenuHTML =
      "<p class='text-gray-400 italic text-center py-4'>Caisse vide</p>";
  }

  detailsDiv.innerHTML = `
    <h4 class="text-lg font-bold text-gray-800 mb-3 border-b pb-2">Détails de la caisse</h4>
    <div class="space-y-2 text-sm text-gray-700">
      <p><strong class="font-semibold text-gray-900">Nom:</strong> ${caisse.nom}</p>
      <p><strong class="font-semibold text-gray-900">État:</strong> <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 border border-gray-200">${caisse.etat}</span></p>
      <p><strong class="font-semibold text-gray-900">Objets (${caisse.nombre_objets}):</strong></p>
      <div class="mt-3 bg-gray-50 p-3 rounded-lg border border-gray-200 max-h-[160px] overflow-y-auto shadow-inner">
        ${contenuHTML}
      </div>
    </div>
  `;

  detailsDiv.style.display = "block";
}

// Gérer la suppression
async function handleDeleteCaisse(e) {
  e.preventDefault();

  const nom = document.getElementById("nom_caisse_suppr").value.trim();

  if (!nom) {
    alert("Veuillez sélectionner une caisse");
    return;
  }

  if (!confirm(`Êtes-vous sûr de vouloir supprimer la caisse "${nom}" ?`)) {
    return;
  }

  try {
    const formData = new FormData();
    formData.append("nom", nom);

    const response = await fetch("PHP/delete_caisse.php", {
      method: "POST",
      body: formData,
    });

    const data = await response.json();

    if (data.success) {
      alert("Caisse supprimée avec succès");
      resetFormSuppressionCaisse();

      // Rafraîchir l'inventaire pour mettre à jour la colonne Caisse
      if (window.refreshInventory) {
        window.refreshInventory();
      }

      // Recharger les tableaux d'objets (Ajout et Modif)
      if (window.reloadAddCaisseObjects) {
        window.reloadAddCaisseObjects();
      }
      if (window.reloadModifCaisseObjects) {
        window.reloadModifCaisseObjects();
      }
    } else {
      alert("Erreur: " + data.message);
    }
  } catch (error) {
    console.error("Erreur suppression:", error);
    alert("Erreur lors de la suppression");
  }
}

// Fonction de réinitialisation
window.resetFormSuppressionCaisse = function () {
  document.getElementById("form_suppression_caisse").reset();
  const detailsDiv = document.getElementById("caisse_details_suppr");
  if (detailsDiv) {
    detailsDiv.style.display = "none";
    detailsDiv.innerHTML = "";
  }
};
