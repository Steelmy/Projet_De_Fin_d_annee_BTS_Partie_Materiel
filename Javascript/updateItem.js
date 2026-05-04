/**
 * updateItem.js — Soumission du formulaire de modification d'un objet
 * (changement d'état + utilisateur emprunteur).
 */

document.addEventListener("DOMContentLoaded", () => {
  const formModification = document.getElementById("form_modification");
  const etatSelect = document.getElementById("etat");
  const reserveurEmprunteurInput = document.getElementById(
    "reserveur_emprunteur",
  );
  const reserveurEmprunteurIdInput = document.getElementById(
    "reserveur_emprunteur_id",
  );

  const idModifInput = document.getElementById("id_materiel");

  /**
   * Active/désactive et configure le placeholder du champ utilisateur
   * en fonction de l'état sélectionné. L'état `disponible` vide les champs.
   *
   * @returns {void}
   */
  function toggleReserveurEmprunteur() {
    if (!etatSelect || !reserveurEmprunteurInput) return;

    const etat = etatSelect.value;
    if (etat === "disponible") {
      reserveurEmprunteurInput.value = "";
      reserveurEmprunteurIdInput.value = "";
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

  if (etatSelect) {
    etatSelect.addEventListener("change", toggleReserveurEmprunteur);
    toggleReserveurEmprunteur();
  }

  if (formModification) {
    formModification.addEventListener("submit", async (e) => {
      e.preventDefault();

      const id = idModifInput.value.trim();
      const etat = etatSelect.value;
      const reserveur_id = reserveurEmprunteurIdInput
        ? reserveurEmprunteurIdInput.value
        : "";

      if (!id) {
        await showAlert(
          "Veuillez sélectionner un matériel à modifier (code-barre manquant)",
          "warning"
        );
        return;
      }

      if (
        etat !== "disponible" &&
        (!reserveur_id || reserveur_id.trim() === "")
      ) {
        await showAlert(
          "Veuillez sélectionner un utilisateur pour un matériel réservé ou emprunté",
          "warning"
        );
        return;
      }

      try {
        const formData = new FormData();
        formData.append("code_barre", id);
        formData.append("etat", etat);

        if (etat === "disponible") {
          formData.append("reserveur_emprunteur", "0");
        } else {
          formData.append("reserveur_emprunteur", reserveur_id);
        }

        const response = await fetch("php/updateItem.php", {
          method: "POST",
          body: formData,
        });

        const data = await response.json();

        if (data.success) {
          await showAlert(data.message, "success");

          if (idModifInput) idModifInput.value = "";

          if (etatSelect) {
            etatSelect.value = "disponible";
            toggleReserveurEmprunteur();
          }

          if (window.refreshInventory) window.refreshInventory();
        } else {
          await showAlert("Erreur: " + data.message, "error");
        }
      } catch (error) {
        console.error("Erreur update:", error);
        await showAlert("Erreur lors de la modification", "error");
      }
    });
  }
});
