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

  let contenuHTML = '<div class="flex flex-wrap gap-2 mt-1">';
  if (caisse.contenu && caisse.contenu.length > 0) {
    caisse.contenu.forEach((objet) => {
      contenuHTML += `<div class="inline-flex items-center gap-2 bg-custom-brandLight/10 text-custom-brandDark px-3 py-1.5 rounded-full text-sm font-medium border border-custom-brandLight/20">
        ${objet.Code_bar} - ${objet.Type} <span class="opacity-70 text-xs">(${objet.Nom})</span>
      </div>`;
    });
  } else {
    contenuHTML +=
      "<p class='text-gray-400 italic text-sm py-2 px-1 w-full'>Caisse vide</p>";
  }
  contenuHTML += "</div>";

  detailsDiv.className =
    "bg-white border-2 border-custom-border rounded-xl p-5 shadow-sm mb-4 transition-all duration-300";
  detailsDiv.innerHTML = `
    <h4 class="text-custom-primary font-bold text-lg mb-4 pb-2 border-b border-gray-100 flex items-center gap-2">
      <svg class="w-5 h-5 text-custom-brandLight" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
      Informations de la caisse
    </h4>
    <div class="grid grid-cols-2 gap-4 mb-4">
      <div class="bg-gray-50 rounded-lg p-3 border border-gray-100 shadow-inner">
        <span class="text-xs text-gray-500 uppercase tracking-widest font-semibold block mb-1">Nom</span>
        <span class="font-medium text-gray-900">${caisse.nom}</span>
      </div>
      <div class="bg-gray-50 rounded-lg p-3 border border-gray-100 shadow-inner">
        <span class="text-xs text-gray-500 uppercase tracking-widest font-semibold block mb-1">État</span>
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-custom-brandLight/15 text-custom-brandDark border border-custom-brandLight/30 capitalize shadow-sm">
          ${caisse.etat}
        </span>
      </div>
    </div>
    <div class="bg-gray-50 rounded-lg p-3 border border-gray-100 shadow-inner">
      <span class="text-xs text-gray-500 uppercase tracking-widest font-semibold block mb-2">Contenu <span class="text-custom-brandLight ml-1">(${caisse.nombre_objets} objets)</span></span>
      <div class="max-h-[160px] overflow-y-auto pr-2 custom-scrollbar">
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

  if (
    !confirm(
      `Êtes-vous sûr de vouloir supprimer la caisse "${nom}" ?\n(Cela ne supprimera pas les objets qu'elle contient)`,
    )
  ) {
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
