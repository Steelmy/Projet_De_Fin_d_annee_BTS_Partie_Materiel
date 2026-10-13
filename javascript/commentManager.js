/**
 * commentManager.js — Modale de gestion des commentaires sur un objet.
 *
 * Permet à l'admin de visualiser le commentaire élève (`com_user`),
 * de saisir/modifier son propre commentaire (`com_admin`) et de supprimer
 * indépendamment chaque champ. Quand les deux deviennent vides,
 * le serveur supprime la ligne et délie l'objet automatiquement.
 */

/** @type {number|null} ID de l'objet en cours d'édition. */
let currentCommentObjetId = null;

/** @type {number|null} ID du commentaire courant (null s'il n'existe pas encore). */
let currentCommentId = null;

/**
 * Ouvre la modale de commentaire pour un objet donné, et charge
 * le commentaire existant si `hasComment` est vrai.
 *
 * @param {number} objetId - Identifiant de l'objet (table `objets`).
 * @param {boolean} hasComment - true si l'objet a déjà un commentaire (id_com non null).
 * @returns {Promise<void>}
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

  userSection.classList.add("hidden");
  userText.textContent = "";
  adminTextarea.value = "";
  btnDeleteAdmin.classList.add("hidden");
  messageDiv.classList.add("hidden");
  messageDiv.textContent = "";

  if (hasComment) {
    title.textContent = "Commentaire";

    try {
      const response = await fetch(
        `php/getComment.php?objet_id=${encodeURIComponent(objetId)}`
      );
      const data = await response.json();

      if (data.success && data.comment) {
        const comment = data.comment;
        currentCommentId = comment.id;

        if (comment.com_user && comment.com_user.trim() !== "") {
          userSection.classList.remove("hidden");
          userText.textContent = comment.com_user;
        }

        adminTextarea.value = comment.com_admin || "";
        if (comment.com_admin && comment.com_admin.trim() !== "") {
          btnDeleteAdmin.classList.remove("hidden");
        }

        if (window.commentNotifications) {
          window.commentNotifications.markSeenByContent(
            comment.id,
            comment.com_user,
            comment.com_admin
          );
          window.commentNotifications.applyBadges();
        }
      }
    } catch (error) {
      console.error("❌ Erreur chargement commentaire:", error);
    }
  } else {
    title.textContent = "Ajouter un commentaire";
  }

  modal.classList.remove("hidden");
  modal.classList.add("flex");
  adminTextarea.focus();
}

/**
 * Ferme la modale de commentaire et réinitialise l'état interne.
 *
 * @returns {void}
 */
function closeCommentModal() {
  const modal = document.getElementById("comment-modal");
  modal.classList.add("hidden");
  modal.classList.remove("flex");
  currentCommentObjetId = null;
  currentCommentId = null;
}

/**
 * Sauvegarde le commentaire admin (création ou mise à jour selon `currentCommentId`).
 *
 * @returns {Promise<void>}
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

      if (window.commentNotifications) {
        const commentId = currentCommentId || data.comment_id;
        const userText =
          document.getElementById("comment-user-text").textContent || "";
        window.commentNotifications.markSeenByContent(
          commentId,
          userText,
          comAdmin
        );
      }

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
 * Vide le commentaire élève (`com_user`).
 * Si les deux champs deviennent vides, la ligne est supprimée côté serveur.
 *
 * @returns {Promise<void>}
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
        showCommentMessage(data.message || "Commentaire supprimé.", "success");
        setTimeout(() => {
          closeCommentModal();
          if (window.refreshInventory) window.refreshInventory();
        }, 800);
      } else {
        document.getElementById("comment-user-section").classList.add("hidden");
        document.getElementById("comment-user-text").textContent = "";
        if (window.commentNotifications) {
          const adminVal =
            document.getElementById("comment-admin-textarea").value || "";
          window.commentNotifications.markSeenByContent(
            currentCommentId,
            "",
            adminVal
          );
        }
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
 * Vide le commentaire admin (`com_admin`).
 * Si les deux champs deviennent vides, la ligne est supprimée côté serveur.
 *
 * @returns {Promise<void>}
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
        showCommentMessage(data.message || "Commentaire supprimé.", "success");
        setTimeout(() => {
          closeCommentModal();
          if (window.refreshInventory) window.refreshInventory();
        }, 800);
      } else {
        document.getElementById("comment-admin-textarea").value = "";
        document.getElementById("btn-delete-admin-comment").classList.add("hidden");
        if (window.commentNotifications) {
          const userVal =
            document.getElementById("comment-user-text").textContent || "";
          window.commentNotifications.markSeenByContent(
            currentCommentId,
            userVal,
            ""
          );
        }
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
 * Affiche un message coloré dans la modale commentaire.
 *
 * @param {string} text - Texte du message.
 * @param {"success"|"error"} type - Catégorie qui définit la couleur.
 * @returns {void}
 */
function showCommentMessage(text, type) {
  const messageDiv = document.getElementById("comment-message");
  messageDiv.textContent = text;
  messageDiv.classList.remove("hidden", "text-green-600", "text-red-600");
  messageDiv.classList.add(
    type === "success" ? "text-green-600" : "text-red-600"
  );
}

window.openCommentModal = openCommentModal;
window.closeCommentModal = closeCommentModal;
window.saveComment = saveComment;
window.deleteUserComment = deleteUserComment;
window.deleteAdminComment = deleteAdminComment;
