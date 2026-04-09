/**
 * restituteItem.js
 * Gère la modale de restitution d'objet et la logique de scan/soumission.
 */

document.addEventListener("DOMContentLoaded", () => {
  const modal = document.getElementById("restitution-modal");
  const form = document.getElementById("form_restitution");
  const barcodeInput = document.getElementById("restitution_code_barre");
  const infoPanel = document.getElementById("restitution_info");
  const submitBtn = document.getElementById("btn_restitution_submit");
  const messageDiv = document.getElementById("restitution_message");

  // Info fields
  const typeSpan = document.getElementById("restitution_type");
  const sousTypeSpan = document.getElementById("restitution_sous_type");
  const nomSpan = document.getElementById("restitution_nom");
  const etatSpan = document.getElementById("restitution_etat");
  const utilisateurSpan = document.getElementById("restitution_utilisateur");

  // Currently selected object data
  let selectedItem = null;

  // -------------------------------------------------------------------------
  // 1. TOGGLE MODALE
  // -------------------------------------------------------------------------
  window.toggleRestitutionModal = (show) => {
    if (!modal) return;
    if (show) {
      modal.classList.remove("hidden");
      modal.classList.add("flex");
      resetRestitutionForm();
      // Focus on barcode input after modal opens
      setTimeout(() => barcodeInput?.focus(), 100);
    } else {
      modal.classList.add("hidden");
      modal.classList.remove("flex");
      resetRestitutionForm();
    }
  };

  // -------------------------------------------------------------------------
  // 2. AUTOCOMPLETE BARCODE (réservé/emprunté, pas dans une caisse)
  // -------------------------------------------------------------------------
  if (barcodeInput) {
    new UniversalAutocompleteBarcode(
      "restitution_code_barre",
      null, // containerId (will be created dynamically)
      null, // typeInputId
      null, // sousTypeInputId
      null, // nomInputId
      (item) => {
        // Callback after selecting a barcode from autocomplete
        barcodeInput.value = item.Code_bar;
        lookupBarcode(item.Code_bar);
      },
      null, // etatFilter - we use custom param
      false, // disponibleOnly
      true   // nonDisponibleOnly
    );

    // Also handle manual entry (paste or scanner)
    let lookupTimer = null;
    barcodeInput.addEventListener("input", () => {
      clearTimeout(lookupTimer);
      const val = barcodeInput.value.trim();
      if (val.length >= 13) {
        lookupTimer = setTimeout(() => lookupBarcode(val), 400);
      } else {
        hideInfo();
      }
    });

    // Handle Enter key from scanner
    barcodeInput.addEventListener("keydown", (e) => {
      if (e.key === "Enter") {
        e.preventDefault();
        const val = barcodeInput.value.trim();
        if (val) lookupBarcode(val);
      }
    });
  }

  // -------------------------------------------------------------------------
  // 3. LOOKUP BARCODE DETAILS
  // -------------------------------------------------------------------------
  async function lookupBarcode(codeBarre) {
    try {
      const response = await fetch(
        `php/getItemDetails.php?code_barre=${encodeURIComponent(codeBarre)}`
      );
      const data = await response.json();

      if (data.success && data.materiel) {
        const mat = data.materiel;

        // Vérifier que l'objet est éligible à la restitution
        // (réservé ou emprunté, pas dans une caisse)
        if (mat.etat === "disponible") {
          showMessage("Cet objet est déjà disponible. Il n'a pas besoin d'être restitué.", "warning");
          hideInfo();
          return;
        }

        if (mat.caisse_id) {
          showMessage("Cet objet est actuellement dans une caisse. Veuillez d'abord le retirer de la caisse.", "warning");
          hideInfo();
          return;
        }

        // Objet éligible
        selectedItem = mat;
        typeSpan.textContent = mat.type_materiel || "-";
        sousTypeSpan.textContent = mat.sous_type_materiel || "-";
        nomSpan.textContent = mat.nom_materiel || "-";
        etatSpan.textContent = mat.etat || "-";
        utilisateurSpan.textContent = mat.utilisateur
          ? mat.utilisateur.nom_complet
          : "-";

        infoPanel.classList.remove("hidden");
        submitBtn.disabled = false;
        hideMessage();
      } else {
        showMessage(data.message || "Objet non trouvé.", "error");
        hideInfo();
      }
    } catch (err) {
      console.error("Erreur lookup restitution:", err);
      showMessage("Erreur lors de la recherche de l'objet.", "error");
      hideInfo();
    }
  }

  // -------------------------------------------------------------------------
  // 4. SOUMISSION RESTITUTION
  // -------------------------------------------------------------------------
  if (form) {
    form.addEventListener("submit", async (e) => {
      e.preventDefault();

      if (!selectedItem) {
        await showAlert(
          "Veuillez sélectionner un objet à restituer.",
          "warning"
        );
        return;
      }

      const confirmed = await showConfirm(
        `Voulez-vous vraiment restituer cet objet ?\n\n${selectedItem.type_materiel} — ${selectedItem.nom_materiel}\nCode-barre : ${selectedItem.code_barre}`
      );

      if (!confirmed) return;

      try {
        const formData = new FormData();
        formData.append("code_barre", selectedItem.code_barre);

        const response = await fetch("php/restituteItem.php", {
          method: "POST",
          body: formData,
        });

        const data = await response.json();

        if (data.success) {
          await showAlert(data.message, "success");
          toggleRestitutionModal(false);
          if (window.refreshInventory) window.refreshInventory();
        } else {
          await showAlert("Erreur: " + data.message, "error");
        }
      } catch (error) {
        console.error("Erreur restitution:", error);
        await showAlert("Erreur lors de la restitution.", "error");
      }
    });
  }

  // -------------------------------------------------------------------------
  // 5. HELPERS
  // -------------------------------------------------------------------------
  function resetRestitutionForm() {
    if (barcodeInput) barcodeInput.value = "";
    hideInfo();
    hideMessage();
    selectedItem = null;
    if (submitBtn) submitBtn.disabled = true;
  }

  function hideInfo() {
    if (infoPanel) infoPanel.classList.add("hidden");
    if (submitBtn) submitBtn.disabled = true;
    selectedItem = null;
  }

  function showMessage(text, type) {
    if (!messageDiv) return;
    messageDiv.textContent = text;
    messageDiv.className =
      "text-sm text-center mt-2 font-medium " +
      (type === "error"
        ? "text-red-600"
        : type === "warning"
          ? "text-amber-600"
          : "text-green-600");
    messageDiv.classList.remove("hidden");
  }

  function hideMessage() {
    if (messageDiv) {
      messageDiv.classList.add("hidden");
      messageDiv.textContent = "";
    }
  }
});
