// ===== GESTION DU FORMULAIRE DE MODIFICATION ET SUPPRESSION DU MATERIEL =====
// Remplace l'ancien script avec les bons IDs et la logique adaptée aux inputs

document.addEventListener("DOMContentLoaded", () => {
  // -------------------------------------------------------------------------
  // 1. GESTION MODIFICATION
  // -------------------------------------------------------------------------
  const formModification = document.getElementById("form_modification");
  const etatSelect = document.getElementById("etat");
  const reserveurEmprunteurInput = document.getElementById(
    "reserveur_emprunteur",
  );
  const reserveurEmprunteurIdInput = document.getElementById(
    "reserveur_emprunteur_id",
  );

  // Champs à reset
  const idModifInput = document.getElementById("id_materiel");

  // Label
  const labelReserveurEmprunteur = document.getElementById(
    "label_reserveur_emprunteur",
  );

  if (etatSelect) {
    etatSelect.addEventListener("change", toggleReserveurEmprunteur);
    // Initialiser l'état
    toggleReserveurEmprunteur();
  }

  function toggleReserveurEmprunteur() {
    if (!etatSelect || !reserveurEmprunteurInput) return;

    const etat = etatSelect.value;
    if (etat === "disponible") {
      reserveurEmprunteurInput.value = "";
      reserveurEmprunteurIdInput.value = ""; // On peut reset l'ID si dispo
      reserveurEmprunteurInput.disabled = true;
      reserveurEmprunteurInput.placeholder = "Utilisateur... (Non applicable)";
      reserveurEmprunteurInput.style.backgroundColor = "#e9ecef";
    } else {
      reserveurEmprunteurInput.disabled = false;
      reserveurEmprunteurInput.style.backgroundColor = "";

      if (etat === "réservé") {
        reserveurEmprunteurInput.placeholder = "Réservé par...";
      } else if (etat === "emprunté") {
        reserveurEmprunteurInput.placeholder = "Emprunté par...";
      } else {
        reserveurEmprunteurInput.placeholder = "Utilisateur...";
      }
    }
  }

  if (formModification) {
    formModification.addEventListener("submit", async (e) => {
      e.preventDefault();

      const id = idModifInput.value.trim();
      const etat = etatSelect.value;
      const reserveur_id = reserveurEmprunteurIdInput
        ? reserveurEmprunteurIdInput.value
        : "";

      // Validation
      if (!id) {
        alert(
          "Veuillez sélectionner un matériel à modifier (code-barre manquant)",
        );
        return;
      }

      if (
        etat !== "disponible" &&
        (!reserveur_id || reserveur_id.trim() === "")
      ) {
        alert(
          "Veuillez sélectionner un utilisateur pour un matériel réservé ou emprunté",
        );
        return;
      }

      try {
        const formData = new FormData();
        formData.append("code_barre", id);
        formData.append("etat", etat);

        if (etat === "disponible") {
          formData.append("reserveur_emprunteur", "0"); // 0 ou NULL selon la logique PHP (souvent 0 pour aucun)
          // Note: update_materiel.php attend souvent un ID user
        } else {
          formData.append("reserveur_emprunteur", reserveur_id);
        }

        const response = await fetch("PHP/update_materiel.php", {
          method: "POST",
          body: formData,
        });

        const data = await response.json();

        if (data.success) {
          alert(data.message);

          // Reset fields
          if (idModifInput) idModifInput.value = "";

          if (etatSelect) {
            etatSelect.value = "disponible";
            toggleReserveurEmprunteur();
          }

          // Refresh inventory
          if (window.refreshInventory) window.refreshInventory();
        } else {
          alert("Erreur: " + data.message);
        }
      } catch (error) {
        console.error("Erreur update:", error);
        alert("Erreur lors de la modification");
      }
    });
  }

  // -------------------------------------------------------------------------
  // 2. GESTION SUPPRESSION (Initialisation simple si nécessaire)
  // -------------------------------------------------------------------------
  // La suppression est probablement gérée par delete_materiel.js
  // On s'assure juste ici de ne pas interférer.
});
