// Récupérer les éléments du formulaire de modification
const idInput = document.getElementById("id_materiel");

// Les fonctions de chargement des types et noms sont maintenant gérées par autocomplete_types_noms.js

// Fonction pour remplir le formulaire à partir du code-barre
async function remplirFormulaireModification(codeBarre) {
  if (!codeBarre) return;

  try {
    const response = await fetch(
      `php/getItemDetails.php?code_barre=${encodeURIComponent(codeBarre)}`,
    );
    const data = await response.json();

    if (data.success && data.materiel) {
      const mat = data.materiel;

      // 1. & 2. Type et Nom ne sont plus affichés/peuplés explicitement ici
      // Ils sont gérés côté serveur lors de la modif si inchangés, ou via l'objet chargé si on voulait les afficher.

      // 3. Remplir l'État
      const etatSelect = document.getElementById("etat");
      // Essayer de trouver la valeur correspondante (casse insensible)
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
        // Fallback?
      }
      // Déclencher l'event change pour gérer l'affichage reserveur
      etatSelect.dispatchEvent(new Event("change"));

      // 4. Remplir le Réserveur/Emprunteur
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

// Événements sur le champ ID (Code-barre)
idInput.addEventListener("change", async () => {
  const code = idInput.value.trim();
  if (code) {
    await remplirFormulaireModification(code);
  }
});

// Empêcher la soumission du formulaire quand on appuie sur Entrée dans le champ code-barre
idInput.addEventListener("keydown", async (e) => {
  if (e.key === "Enter") {
    e.preventDefault();
    const code = idInput.value.trim();
    if (code) {
      await remplirFormulaireModification(code);
    }
  }
});

// ===== FORMULAIRE DE SUPPRESSION =====
// Le formulaire utilise maintenant les champs d'autocomplétion
// Aucune fonction de chargement n'est nécessaire ici car tout est géré par autocomplete_types_noms.js
