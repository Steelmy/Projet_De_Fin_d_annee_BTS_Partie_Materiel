// ===== GESTION DE LA SOUMISSION DU FORMULAIRE DE SUPPRESSION =====
const formSuppression = document.getElementById("form_suppression");
const idInputSuppr = document.getElementById("id_materiel_suppr");

// Récupération des inputs (au lieu des selects)
const typeInputSuppr = document.getElementById("type_materiel_suppr");
const nomInputSuppr = document.getElementById("nom_materiel_suppr");

// Fonction pour auto-remplir les champs type et nom lors du scan/entré manuelle
async function updatesupprFields(code) {
  if (!code) return;
  try {
    const response = await fetch(
      `PHP/get_materiel_details.php?code_barre=${encodeURIComponent(code)}`,
    );
    const data = await response.json();
    if (data.success && data.materiel) {
      const mat = data.materiel;
      // Remplir Type (affichage)
      if (typeInputSuppr) {
        typeInputSuppr.value = mat.type_materiel;
      }
      // Remplir Nom (affichage)
      if (nomInputSuppr) {
        nomInputSuppr.value = mat.nom_materiel;
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
    alert("Veuillez scanner ou entrer un code-barre à supprimer");
    return;
  }

  try {
    // 1. D'abord, récupérer les infos du matériel pour confirmation
    const detailsResponse = await fetch(
      `PHP/get_materiel_details.php?code_barre=${encodeURIComponent(id)}`,
    );
    const detailsData = await detailsResponse.json();

    if (!detailsData.success || !detailsData.materiel) {
      alert("Ce code-barre n'existe pas dans la base de données.");
      return;
    }

    const mat = detailsData.materiel;

    // 2. Demander confirmation avec les vraies infos
    if (
      !confirm(
        `Êtes-vous sûr de vouloir supprimer ce matériel ?\n\nType: ${mat.type_materiel}\nNom: ${mat.nom_materiel}\nID: ${mat.code_barre}\nEtat: ${mat.etat}`,
      )
    ) {
      return;
    }

    // 3. Envoyer la requête de suppression
    const formData = new FormData();
    formData.append("code_barre", id);

    const response = await fetch("PHP/delete_materiel.php", {
      method: "POST",
      body: formData,
    });

    const data = await response.json();

    if (data.success) {
      alert(data.message);

      // Réinitialiser le formulaire
      idInputSuppr.value = "";
      idInputSuppr.focus(); // Remettre le focus pour le prochain scan

      // Rafraîchir l'inventaire complet (fonction définie dans display_inventory.js)
      if (window.refreshInventory) {
        window.refreshInventory();
      }
    } else {
      alert("Erreur: " + data.message);
    }
  } catch (error) {
    console.error("Erreur lors de la suppression:", error);
    alert("Erreur lors de la suppression du matériel");
  }
});
