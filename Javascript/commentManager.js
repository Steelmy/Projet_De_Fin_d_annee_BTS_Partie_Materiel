/**
 * commentManager.js — Gestion des commentaires sur les objets
 *
 * Ouvre une modale pour ajouter/voir/modifier/supprimer les commentaires
 * liés à un objet du tableau d'inventaire.
 */

// État interne de la modale
let currentCommentObjetId = null;
let currentCommentId = null;

/**
 * Ouvre la modale de commentaire pour un objet donné.
 * @param {number} objetId - L'id de l'objet dans la table objets
 * @param {boolean} hasComment - true si l'objet a déjà un commentaire (id_com non null)
 */
async function openCommentModal(objetId, hasComment) {
  currentCommentObjetId = objetId;
  currentCommentId = null;

  const modal = document.getElementById("comment-modal");
  const title = document.getElementById("comment-modal-title");
  const userSection = document.getElementById("comment-user-section");
  const userText = document.getElementById("comment-user-text");
  const adminTextarea = document.getElementById("comment-admin-textarea");
  const btnDeleteAdmin = document.getElementById("btn-delete-admin-comment");
  const messageDiv = document.getElementById("comment-message");

  // Reset
  userSection.classList.add("hidden");
  userText.textContent = "";
  adminTextarea.value = "";
  btnDeleteAdmin.classList.add("hidden");
  messageDiv.classList.add("hidden");
  messageDiv.textContent = "";

  if (hasComment) {
    // Charger le commentaire existant
    title.textContent = "Commentaire";

    try {
      const response = await fetch(
        `php/getComment.php?objet_id=${encodeURIComponent(objetId)}`
      );
      const data = await response.json();

      if (data.success && data.comment) {
        const comment = data.comment;
        currentCommentId = comment.id;

        // Afficher com_user si non vide
        if (comment.com_user && comment.com_user.trim() !== "") {
          userSection.classList.remove("hidden");
          userText.textContent = comment.com_user;
        }

        // Pré-remplir com_admin et afficher le bouton supprimer si non vide
        adminTextarea.value = comment.com_admin || "";
        if (comment.com_admin && comment.com_admin.trim() !== "") {
          btnDeleteAdmin.classList.remove("hidden");
        }
      }
    } catch (error) {
      console.error("❌ Erreur chargement commentaire:", error);
    }
  } else {
    title.textContent = "Ajouter un commentaire";
  }

  // Afficher la modale
  modal.classList.remove("hidden");
  modal.classList.add("flex");
  adminTextarea.focus();
}

/**
 * Ferme la modale de commentaire.
 */
function closeCommentModal() {
  const modal = document.getElementById("comment-modal");
  modal.classList.add("hidden");
  modal.classList.remove("flex");
  currentCommentObjetId = null;
  currentCommentId = null;
}

/**
 * Sauvegarde le commentaire admin (création ou mise à jour).
 */
async function saveComment() {
  const adminTextarea = document.getElementById("comment-admin-textarea");
  const comAdmin = adminTextarea.value.trim();

  if (!comAdmin) {
    showCommentMessage("Le commentaire ne peut pas être vide.", "error");
    return;
  }

  if (!currentCommentObjetId) {
    showCommentMessage("Erreur : aucun objet sélectionné.", "error");
    return;
  }

  try {
    const formData = new FormData();
    formData.append("objet_id", currentCommentObjetId);
    formData.append("com_admin", comAdmin);
    if (currentCommentId) {
      formData.append("comment_id", currentCommentId);
    }

    const response = await fetch("php/saveComment.php", {
      method: "POST",
      body: formData,
    });
    const data = await response.json();

    if (data.success) {
      showCommentMessage(data.message || "Commentaire enregistré.", "success");

      // Rafraîchir l'inventaire après un court délai
      setTimeout(() => {
        closeCommentModal();
        if (window.refreshInventory) {
          window.refreshInventory();
        }
      }, 800);
    } else {
      showCommentMessage(data.error || "Erreur lors de la sauvegarde.", "error");
    }
  } catch (error) {
    console.error("❌ Erreur sauvegarde commentaire:", error);
    showCommentMessage("Erreur réseau.", "error");
  }
}

/**
 * Supprime le commentaire élève (vide com_user).
 * Si les deux champs sont vides, la ligne est supprimée côté serveur.
 */
async function deleteUserComment() {
  if (!currentCommentId) {
    showCommentMessage("Aucun commentaire à supprimer.", "error");
    return;
  }

  const confirmed = await showConfirm(
    "Êtes-vous sûr de vouloir supprimer le commentaire de l'élève ?",
    { confirmText: "Supprimer", type: "warning" }
  );

  if (!confirmed) return;

  try {
    const formData = new FormData();
    formData.append("comment_id", currentCommentId);

    const response = await fetch("php/deleteUserComment.php", {
      method: "POST",
      body: formData,
    });
    const data = await response.json();

    if (data.success) {
      if (data.row_deleted) {
        // La ligne a été supprimée (les deux champs étaient vides)
        showCommentMessage(data.message || "Commentaire supprimé.", "success");
        setTimeout(() => {
          closeCommentModal();
          if (window.refreshInventory) window.refreshInventory();
        }, 800);
      } else {
        // Seul com_user a été vidé
        document.getElementById("comment-user-section").classList.add("hidden");
        document.getElementById("comment-user-text").textContent = "";
        showCommentMessage(
          data.message || "Commentaire élève supprimé.",
          "success"
        );
      }
    } else {
      showCommentMessage(
        data.error || "Erreur lors de la suppression.",
        "error"
      );
    }
  } catch (error) {
    console.error("❌ Erreur suppression commentaire élève:", error);
    showCommentMessage("Erreur réseau.", "error");
  }
}

/**
 * Supprime le commentaire admin (vide com_admin).
 * Si les deux champs sont vides, la ligne est supprimée côté serveur.
 */
async function deleteAdminComment() {
  if (!currentCommentId) {
    showCommentMessage("Aucun commentaire à supprimer.", "error");
    return;
  }

  const confirmed = await showConfirm(
    "Êtes-vous sûr de vouloir supprimer votre commentaire admin ?",
    { confirmText: "Supprimer", type: "warning" }
  );

  if (!confirmed) return;

  try {
    const formData = new FormData();
    formData.append("comment_id", currentCommentId);

    const response = await fetch("php/deleteAdminComment.php", {
      method: "POST",
      body: formData,
    });
    const data = await response.json();

    if (data.success) {
      if (data.row_deleted) {
        // La ligne a été supprimée (les deux champs étaient vides)
        showCommentMessage(data.message || "Commentaire supprimé.", "success");
        setTimeout(() => {
          closeCommentModal();
          if (window.refreshInventory) window.refreshInventory();
        }, 800);
      } else {
        // Seul com_admin a été vidé, com_user existe encore
        document.getElementById("comment-admin-textarea").value = "";
        document.getElementById("btn-delete-admin-comment").classList.add("hidden");
        showCommentMessage(
          data.message || "Commentaire admin supprimé.",
          "success"
        );
      }
    } else {
      showCommentMessage(
        data.error || "Erreur lors de la suppression.",
        "error"
      );
    }
  } catch (error) {
    console.error("❌ Erreur suppression commentaire admin:", error);
    showCommentMessage("Erreur réseau.", "error");
  }
}

/**
 * Affiche un message dans la modale commentaire.
 */
function showCommentMessage(text, type) {
  const messageDiv = document.getElementById("comment-message");
  messageDiv.textContent = text;
  messageDiv.classList.remove("hidden", "text-green-600", "text-red-600");
  messageDiv.classList.add(
    type === "success" ? "text-green-600" : "text-red-600"
  );
}

// Exposer globalement pour les onclick inline
window.openCommentModal = openCommentModal;
window.closeCommentModal = closeCommentModal;
window.saveComment = saveComment;
window.deleteUserComment = deleteUserComment;
window.deleteAdminComment = deleteAdminComment;
