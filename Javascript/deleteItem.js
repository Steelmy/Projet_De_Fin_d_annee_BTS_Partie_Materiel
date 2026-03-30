// ===== GESTION DE LA SOUMISSION DU FORMULAIRE DE SUPPRESSION =====
const formSuppression = document.getElementById("form_suppression");
const idInputSuppr = document.getElementById("id_materiel_suppr");

// Récupération des inputs (au lieu des selects)
const typeInputSuppr = document.getElementById("type_materiel_suppr");
const sousTypeInputSuppr = document.getElementById("sous_type_materiel_suppr");
const nomInputSuppr = document.getElementById("nom_materiel_suppr");

// Vider le code-barre si l'utilisateur change manuellement les filtres
const clearBarcodeOnFilterChange = (e) => {
    // Si le changement est programmé (via le scan par exemple), on ne vide pas le champ
    if (e && e.isTrusted === false) return;
    
    idInputSuppr.value = "";
    // L'autocomplete se mettra à jour à la prochaine saisie ou clic sur l'input barcode
    // car il lit dynamiquement typeInputSuppr.value
};

if (typeInputSuppr) typeInputSuppr.addEventListener("change", clearBarcodeOnFilterChange);
if (sousTypeInputSuppr) sousTypeInputSuppr.addEventListener("change", clearBarcodeOnFilterChange);
if (nomInputSuppr) nomInputSuppr.addEventListener("change", clearBarcodeOnFilterChange);

// Fonction pour auto-remplir les champs type et nom lors du scan/entré manuelle
async function updatesupprFields(code) {
  if (!code) return;
  try {
    const response = await fetch(
      `php/getItemDetails.php?code_barre=${encodeURIComponent(code)}`,
    );
    const data = await response.json();
    if (data.success && data.materiel) {
      const mat = data.materiel;
      // Remplir Type, Sous-type et Nom via la cascade
      if (window.setSelectCascadeValues) {
         window.setSelectCascadeValues('suppr', mat.type_materiel, mat.sous_type_materiel, mat.nom_materiel);
      }
    }
  } catch (e) {
    console.error(e);
  }
}

idInputSuppr.addEventListener("change", () => {
  updatesupprFields(idInputSuppr.value.trim());
});

// Prevent Enter from submitting immediately if we want to visualize first, or let it submit?
// User request: "les champs précédent se mettent avec les bonnes sélection".
// So let's ensure we fill them on Enter too before potentially submitting (or instead of).
idInputSuppr.addEventListener("keydown", async (e) => {
  if (e.key === "Enter") {
    e.preventDefault(); // Stop submit
    await updatesupprFields(idInputSuppr.value.trim());
  }
});

formSuppression.addEventListener("submit", async (e) => {
  e.preventDefault();

  const id = idInputSuppr.value.trim();

  // Vérifier qu'un ID est renseigné
  if (!id) {
    await showAlert("Veuillez scanner ou entrer un code-barre à supprimer", "warning");
    return;
  }

  try {
    // 1. D'abord, récupérer les infos du matériel pour confirmation
    const detailsResponse = await fetch(
      `php/getItemDetails.php?code_barre=${encodeURIComponent(id)}`,
    );
    const detailsData = await detailsResponse.json();

    if (!detailsData.success || !detailsData.materiel) {
      await showAlert("Ce code-barre n'existe pas dans la base de données.", "error");
      return;
    }

    const mat = detailsData.materiel;

    // Vérifier que l'objet n'est pas dans une caisse
    if (mat.caisse_id) {
      await showAlert(
        `Cet objet est actuellement dans la caisse "${mat.caisse_nom || "inconnue"}". Veuillez d'abord le retirer de la caisse avant de pouvoir le supprimer.`,
        "warning"
      );
      return;
    }

    // Vérifier que l'objet n'est pas réservé/emprunté
    if (mat.etat !== "disponible") {
      await showAlert(
        `Cet objet est actuellement "${mat.etat}". Veuillez d'abord le remettre en état "disponible" avant de pouvoir le supprimer.`,
        "warning"
      );
      return;
    }

    // 2. Demander confirmation avec les vraies infos
    if (
      !(await showConfirm(
        `Êtes-vous sûr de vouloir supprimer ce matériel ?\n\nType: ${mat.type_materiel}\nNom: ${mat.nom_materiel}\nID: ${mat.code_barre}\nEtat: ${mat.etat}`,
        { confirmText: "Supprimer", type: "error" }
      ))
    ) {
      return;
    }

    // 3. Envoyer la requête de suppression
    const formData = new FormData();
    formData.append("code_barre", id);

    const response = await fetch("php/deleteItem.php", {
      method: "POST",
      body: formData,
    });

    const data = await response.json();

    if (data.success) {
      await showAlert(data.message, "success");

      // Réinitialiser le formulaire
      idInputSuppr.value = "";
      idInputSuppr.focus(); // Remettre le focus pour le prochain scan

      // Rafraîchir l'inventaire complet (fonction définie dans display_inventory.js)
      if (window.refreshInventory) {
        window.refreshInventory();
      }
    } else {
      await showAlert("Erreur: " + data.message, "error");
    }
  } catch (error) {
    console.error("Erreur lors de la suppression:", error);
    await showAlert("Erreur lors de la suppression du matériel", "error");
  }
});
