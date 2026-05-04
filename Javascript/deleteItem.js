/**
 * deleteItem.js — Soumission du formulaire de suppression d'un objet
 * (vérifications préalables : objet hors caisse et état `disponible`).
 */

const formSuppression = document.getElementById("form_suppression");
const idInputSuppr = document.getElementById("id_materiel_suppr");

const typeInputSuppr = document.getElementById("type_materiel_suppr");
const sousTypeInputSuppr = document.getElementById("sous_type_materiel_suppr");
const nomInputSuppr = document.getElementById("nom_materiel_suppr");

/**
 * Vide le champ code-barres lorsque l'utilisateur change manuellement
 * un filtre (Type/Sous-type/Nom). Ignore les changements programmatiques (scan).
 *
 * @param {Event} [e] - Événement change.
 * @returns {void}
 */
const clearBarcodeOnFilterChange = (e) => {
    if (e && e.isTrusted === false) return;
    idInputSuppr.value = "";
};

if (typeInputSuppr) typeInputSuppr.addEventListener("change", clearBarcodeOnFilterChange);
if (sousTypeInputSuppr) sousTypeInputSuppr.addEventListener("change", clearBarcodeOnFilterChange);
if (nomInputSuppr) nomInputSuppr.addEventListener("change", clearBarcodeOnFilterChange);

/**
 * Auto-remplit Type/Sous-type/Nom du formulaire de suppression
 * à partir du code-barres saisi ou scanné.
 *
 * @param {string} code - Code-barres EAN-13.
 * @returns {Promise<void>}
 */
async function updatesupprFields(code) {
  if (!code) return;
  try {
    const response = await fetch(
      `php/getItemDetails.php?code_barre=${encodeURIComponent(code)}`,
    );
    const data = await response.json();
    if (data.success && data.materiel) {
      const mat = data.materiel;
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

idInputSuppr.addEventListener("keydown", async (e) => {
  if (e.key === "Enter") {
    e.preventDefault();
    await updatesupprFields(idInputSuppr.value.trim());
  }
});

formSuppression.addEventListener("submit", async (e) => {
  e.preventDefault();

  const id = idInputSuppr.value.trim();

  if (!id) {
    await showAlert("Veuillez scanner ou entrer un code-barre à supprimer", "warning");
    return;
  }

  try {
    const detailsResponse = await fetch(
      `php/getItemDetails.php?code_barre=${encodeURIComponent(id)}`,
    );
    const detailsData = await detailsResponse.json();

    if (!detailsData.success || !detailsData.materiel) {
      await showAlert("Ce code-barre n'existe pas dans la base de données.", "error");
      return;
    }

    const mat = detailsData.materiel;

    if (mat.caisse_id) {
      await showAlert(
        `Cet objet est actuellement dans la caisse "${mat.caisse_nom || "inconnue"}". Veuillez d'abord le retirer de la caisse avant de pouvoir le supprimer.`,
        "warning"
      );
      return;
    }

    if (mat.etat !== "disponible") {
      await showAlert(
        `Cet objet est actuellement "${mat.etat}". Veuillez d'abord le remettre en état "disponible" avant de pouvoir le supprimer.`,
        "warning"
      );
      return;
    }

    if (
      !(await showConfirm(
        `Êtes-vous sûr de vouloir supprimer ce matériel ?\n\nType: ${mat.type_materiel}\nNom: ${mat.nom_materiel}\nID: ${mat.code_barre}\nEtat: ${mat.etat}`,
        { confirmText: "Supprimer", type: "error" }
      ))
    ) {
      return;
    }

    const formData = new FormData();
    formData.append("code_barre", id);

    const response = await fetch("php/deleteItem.php", {
      method: "POST",
      body: formData,
    });

    const data = await response.json();

    if (data.success) {
      await showAlert(data.message, "success");

      idInputSuppr.value = "";
      idInputSuppr.focus();

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
