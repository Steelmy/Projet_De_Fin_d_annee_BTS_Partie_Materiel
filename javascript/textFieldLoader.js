/**
 * textFieldLoader.js — Auto-remplissage du formulaire de modification d'un objet
 * à partir de son code-barres (scan ou saisie manuelle + Entrée).
 */

const idInput = document.getElementById("id_materiel");

/**
 * Récupère le détail d'un objet par code-barres et remplit le formulaire
 * de modification (état, utilisateur emprunteur).
 *
 * @param {string} codeBarre - Code-barres EAN-13 à rechercher.
 * @returns {Promise<void>}
 */
async function remplirFormulaireModification(codeBarre) {
  if (!codeBarre) return;

  try {
    const response = await fetch(
      `php/getItemDetails.php?code_barre=${encodeURIComponent(codeBarre)}`,
    );
    const data = await response.json();

    if (data.success && data.materiel) {
      const mat = data.materiel;

      const etatSelect = document.getElementById("etat");
      const dbEtat = mat.etat.toLowerCase();
      let matchFound = false;
      for (let i = 0; i < etatSelect.options.length; i++) {
        if (etatSelect.options[i].value.toLowerCase() === dbEtat) {
          etatSelect.selectedIndex = i;
          matchFound = true;
          break;
        }
      }
      if (!matchFound) {
        console.warn("Etat DB non trouvé dans select:", mat.etat);
      }
      etatSelect.dispatchEvent(new Event("change"));

      const reserveurInput = document.getElementById("reserveur_emprunteur");
      const reserveurIdInput = document.getElementById(
        "reserveur_emprunteur_id",
      );

      if (mat.etat !== "disponible" && data.materiel.utilisateur) {
        const user = data.materiel.utilisateur;
        reserveurInput.value = user.nom_complet;
        reserveurIdInput.value = user.id;
      } else {
        reserveurInput.value = "";
        reserveurIdInput.value = "";
      }
    }
  } catch (error) {
    console.error("Erreur récupération détails:", error);
  }
}
window.remplirFormulaireModification = remplirFormulaireModification;

idInput.addEventListener("change", async () => {
  const code = idInput.value.trim();
  if (code) {
    await remplirFormulaireModification(code);
  }
});

idInput.addEventListener("keydown", async (e) => {
  if (e.key === "Enter") {
    e.preventDefault();
    const code = idInput.value.trim();
    if (code) {
      await remplirFormulaireModification(code);
    }
  }
});
