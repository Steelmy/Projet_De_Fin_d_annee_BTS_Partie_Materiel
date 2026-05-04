/**
 * customModal.js — Modales d'alerte et de confirmation personnalisées.
 *
 * Remplace `alert()` et `confirm()` natifs par des modales stylisées.
 *
 * Usage :
 *   await showAlert("Message", "success");      // types: success, error, warning, info
 *   const ok = await showConfirm("Message");    // renvoie true/false
 */

(function () {
  const style = document.createElement("style");
  style.textContent = `
    .custom-modal-overlay {
      position: fixed;
      inset: 0;
      z-index: 99999;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 1rem;
      background: rgba(0,0,0,0.45);
    }
    .custom-modal-box {
      background: #fff;
      border-radius: 14px;
      padding: 28px 32px 22px;
      max-width: 460px;
      width: 100%;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
      text-align: center;
    }
    .custom-modal-icon {
      width: 48px;
      height: 48px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 16px;
      font-size: 24px;
      font-weight: bold;
    }
    .custom-modal-message {
      font-size: 15px;
      line-height: 1.55;
      color: #374151;
      margin-bottom: 22px;
      white-space: pre-wrap;
      word-break: break-word;
    }
    .custom-modal-actions {
      display: flex;
      gap: 10px;
      justify-content: center;
    }
    .custom-modal-btn {
      padding: 9px 22px;
      border-radius: 8px;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      border: none;
      outline: none;
    }
  `;
  document.head.appendChild(style);

  /** @type {Object<string, {bg:string, iconBg:string, icon:string, btnBg:string}>} */
  const THEME = {
    success: { bg: "#ecfdf5", iconBg: "#10b981", icon: "✓", btnBg: "#10b981" },
    error:   { bg: "#fef2f2", iconBg: "#ef4444", icon: "✕", btnBg: "#ef4444" },
    warning: { bg: "#fffbeb", iconBg: "#f59e0b", icon: "!", btnBg: "#f59e0b" },
    info:    { bg: "#eff6ff", iconBg: "#3b82f6", icon: "i", btnBg: "#3b82f6" },
  };

  /**
   * Crée le div overlay racine d'une modale.
   *
   * @returns {HTMLDivElement} Élément overlay non encore attaché au DOM.
   */
  function createOverlay() {
    const overlay = document.createElement("div");
    overlay.className = "custom-modal-overlay";
    return overlay;
  }

  /**
   * Insère un overlay dans le `<body>`.
   *
   * @param {HTMLElement} overlay - Overlay à attacher.
   * @returns {void}
   */
  function animateIn(overlay) {
    document.body.appendChild(overlay);
  }

  /**
   * Retire l'overlay du DOM.
   *
   * @param {HTMLElement} overlay - Overlay à supprimer.
   * @returns {Promise<void>} Résolue quand le retrait est effectué.
   */
  function animateOut(overlay) {
    if (overlay.parentNode) overlay.parentNode.removeChild(overlay);
    return Promise.resolve();
  }

  /**
   * Affiche une modale d'alerte avec un seul bouton OK.
   *
   * @param {string} message - Texte affiché (les retours à la ligne sont préservés).
   * @param {"success"|"error"|"warning"|"info"} [type="info"] - Thème visuel.
   * @returns {Promise<void>} Résolue quand l'utilisateur clique OK ou presse Echap.
   */
  window.showAlert = function (message, type = "info") {
    const t = THEME[type] || THEME.info;

    return new Promise((resolve) => {
      const overlay = createOverlay();

      overlay.innerHTML = `
        <div class="custom-modal-box">
          <div class="custom-modal-icon" style="background:${t.iconBg}; color:#fff;">${t.icon}</div>
          <div class="custom-modal-message">${escapeHtml(message)}</div>
          <div class="custom-modal-actions">
            <button class="custom-modal-btn" style="background:${t.btnBg}; color:#fff;">OK</button>
          </div>
        </div>
      `;

      const btn = overlay.querySelector(".custom-modal-btn");
      btn.addEventListener("click", async () => {
        await animateOut(overlay);
        resolve();
      });

      const onKey = (e) => {
        if (e.key === "Escape") {
          document.removeEventListener("keydown", onKey);
          btn.click();
        }
      };
      document.addEventListener("keydown", onKey);

      animateIn(overlay);
      btn.focus();
    });
  };

  /**
   * Affiche une modale de confirmation avec deux boutons.
   *
   * @param {string} message - Texte affiché (les retours à la ligne sont préservés).
   * @param {object} [options] - Options de personnalisation.
   * @param {string} [options.confirmText="Confirmer"] - Libellé du bouton de confirmation.
   * @param {string} [options.cancelText="Annuler"] - Libellé du bouton d'annulation.
   * @param {"success"|"error"|"warning"|"info"} [options.type="warning"] - Thème visuel.
   * @returns {Promise<boolean>} true si confirmé, false sinon (annulation ou Echap).
   */
  window.showConfirm = function (message, options = {}) {
    const {
      confirmText = "Confirmer",
      cancelText = "Annuler",
      type = "warning",
    } = options;
    const t = THEME[type] || THEME.warning;

    return new Promise((resolve) => {
      const overlay = createOverlay();

      overlay.innerHTML = `
        <div class="custom-modal-box">
          <div class="custom-modal-icon" style="background:${t.iconBg}; color:#fff;">${t.icon}</div>
          <div class="custom-modal-message">${escapeHtml(message)}</div>
          <div class="custom-modal-actions">
            <button class="custom-modal-btn custom-modal-cancel" style="background:#e5e7eb; color:#374151;">${escapeHtml(cancelText)}</button>
            <button class="custom-modal-btn custom-modal-confirm" style="background:${t.btnBg}; color:#fff;">${escapeHtml(confirmText)}</button>
          </div>
        </div>
      `;

      const btnCancel = overlay.querySelector(".custom-modal-cancel");
      const btnConfirm = overlay.querySelector(".custom-modal-confirm");

      btnCancel.addEventListener("click", async () => {
        await animateOut(overlay);
        resolve(false);
      });

      btnConfirm.addEventListener("click", async () => {
        await animateOut(overlay);
        resolve(true);
      });

      const onKey = (e) => {
        if (e.key === "Escape") {
          document.removeEventListener("keydown", onKey);
          btnCancel.click();
        }
      };
      document.addEventListener("keydown", onKey);

      animateIn(overlay);
      btnConfirm.focus();
    });
  };

  /**
   * Échappe les caractères HTML d'une chaîne pour insertion via innerHTML.
   *
   * @param {*} str - Valeur à échapper (convertie en chaîne).
   * @returns {string} Chaîne sécurisée pour innerHTML.
   */
  function escapeHtml(str) {
    return (str || "").toString()
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }
})();
