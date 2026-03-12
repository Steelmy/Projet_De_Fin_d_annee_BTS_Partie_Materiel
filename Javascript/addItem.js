// ===== GESTION DU FORMULAIRE D'AJOUT =====
const typeInputAjout = document.getElementById("type_materiel_ajout");
const nomInputAjout = document.getElementById("nom_materiel_ajout");
const formAjout = document.getElementById("form_ajout");

// Les fonctions de chargement des types et noms sont maintenant gérées par autocomplete_types_noms.js
// Ce fichier se concentre uniquement sur la soumission du formulaire

// Gérer la soumission du formulaire d'ajout
formAjout.addEventListener("submit", async (e) => {
  e.preventDefault();

  const type = typeInputAjout.value.trim();
  const nom = nomInputAjout.value.trim();
  const nombre = parseInt(document.getElementById("nombre_materiel").value);

  // Vérifier que tous les champs sont remplis
  if (!type) {
    alert("Veuillez entrer un type de matériel");
    return;
  }

  if (!nom) {
    alert("Veuillez entrer un nom de matériel");
    return;
  }

  if (!nombre || nombre <= 0) {
    alert("Veuillez entrer un nombre valide");
    return;
  }

  // Demander les codes-barres de manière séquentielle
  const codesBarres = [];
  for (let i = 1; i <= nombre; i++) {
    let codeBarre = prompt(`Code-barre pour matériel #${i} sur ${nombre}:`);

    // Si l'utilisateur annule, arrêter le processus
    if (codeBarre === null) {
      alert("Ajout annulé");
      return;
    }

    // Accepter un code-barre vide (génération automatique)
    if (codeBarre.trim() === "") {
      alert("Le code-barre est obligatoire.");
      i--; // Réessayer pour le même index
      continue;
    }
    const codeTrimmed = codeBarre.trim();

    // 1. Vérifier si déjà scanné dans ce lot
    if (codesBarres.includes(codeTrimmed)) {
      alert(
        `Le code-barre "${codeTrimmed}" est déjà dans la liste d'ajout actuelle.`,
      );
      i--;
      continue;
    }

    // 2. Vérification asynchrone de l'existence du code-barre en BDD
    try {
      const checkResponse = await fetch(
        `php/checkBarcode.php?code_barre=${encodeURIComponent(codeTrimmed)}`,
      );
      const checkData = await checkResponse.json();

      if (checkData.success && checkData.exists) {
        alert(
          `Le code-barre "${codeTrimmed}" existe déjà dans la base de données.\nVeuillez en saisir un autre.`,
        );
        i--; // Réessayer pour le même index
        continue;
      }
    } catch (e) {
      console.error("Erreur vérification code-barre", e);
      // On laisse passer ou on bloque ? Mieux vaut bloquer si erreur technique pour éviter doublons
      alert(
        "Erreur technique lors de la vérification du code-barre. Veuillez réessayer.",
      );
      i--;
      continue;
    }

    codesBarres.push(codeBarre.trim());
  }

  try {
    const formData = new FormData();
    formData.append("type_materiel", type);
    formData.append("nom_materiel", nom);
    formData.append("nombre", nombre);
    formData.append("codes_barres", JSON.stringify(codesBarres));

    const response = await fetch("php/addItem.php", {
      method: "POST",
      body: formData,
    });

    const data = await response.json();

    if (data.success) {
      alert(data.message + "\n\nIDs ajoutés : " + data.ids_ajoutes.join(", "));

      // Réinitialiser le formulaire
      typeInputAjout.value = "";
      nomInputAjout.value = "";
      document.getElementById("nombre_materiel").value = "1";

      // Recharger les types pour tous les formulaires (fonction définie dans autocomplete_types_noms.js)
      if (window.rechargerTousLesTypes) {
        window.rechargerTousLesTypes();
      }

      // Rafraîchir l'inventaire complet (fonction définie dans display_inventory.js)
      if (window.refreshInventory) {
        window.refreshInventory();
      }
    } else {
      alert("Erreur: " + data.message);
    }
  } catch (error) {
    console.error("Erreur lors de l'ajout:", error);
    alert("Erreur lors de l'ajout du matériel");
  }
});
