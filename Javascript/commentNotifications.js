/**
 * commentNotifications.js — Pastille "nouveau commentaire" + liste centralisée.
 *
 * Stratégie : chaque commentaire (id_com) est identifié par un fingerprint
 * (hash de com_user + com_admin) sauvegardé en localStorage. Si le fingerprint
 * stocké diffère du fingerprint courant (ou est absent), le commentaire est
 * considéré comme nouveau pour cet admin / ce navigateur, et une pastille
 * rouge est dessinée sur le bouton "Voir le commentaire" correspondant.
 *
 * Quand l'admin :
 *   - ouvre la modale via openCommentModal → markSeen(item)
 *   - sauvegarde son propre commentaire → markSeen avec le nouveau contenu
 * → ses propres updates n'apparaissent pas comme "nouveaux".
 *
 * Limitation : localStorage est par navigateur, donc deux admins partageant
 * le même poste partagent l'état "lu/non-lu". Acceptable pour ce projet.
 */

(function () {
  const STORAGE_KEY = "commentSeen:v1";

  /**
   * Lit la map { commentId: fingerprint } depuis localStorage.
   *
   * @returns {Record<string, string>}
   */
  function getSeenMap() {
    try {
      const raw = localStorage.getItem(STORAGE_KEY);
      return raw ? JSON.parse(raw) : {};
    } catch (_) {
      return {};
    }
  }

  /**
   * Écrit la map dans localStorage (silencieusement en cas d'échec quota).
   *
   * @param {Record<string, string>} map
   * @returns {void}
   */
  function saveSeenMap(map) {
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(map));
    } catch (_) {
      /* quota ou mode privé : on ignore */
    }
  }

  /**
   * Hash djb2 stable et léger sur la chaîne `com_user|com_admin`.
   *
   * @param {string} a
   * @param {string} b
   * @returns {string} Empreinte hexadécimale.
   */
  function fingerprint(a, b) {
    const s = (a || "") + "␟" + (b || "");
    let h = 5381;
    for (let i = 0; i < s.length; i++) {
      h = ((h << 5) + h + s.charCodeAt(i)) | 0;
    }
    return (h >>> 0).toString(16);
  }

  /**
   * Calcule l'empreinte courante d'un item (ou null s'il n'a pas de commentaire).
   *
   * @param {object} item
   * @returns {string|null}
   */
  function itemFingerprint(item) {
    if (!item || !item.id_com) return null;
    return fingerprint(item.com_user, item.com_admin);
  }

  /**
   * Indique si le commentaire associé à `item` doit afficher une pastille.
   * Vrai quand l'item a un commentaire et que son empreinte courante diffère
   * de l'empreinte connue (ou qu'aucune empreinte n'est encore enregistrée).
   *
   * @param {object} item
   * @returns {boolean}
   */
  function isNew(item) {
    const fp = itemFingerprint(item);
    if (!fp) return false;
    const seen = getSeenMap()[String(item.id_com)];
    return seen !== fp;
  }

  /**
   * Marque le commentaire de `item` comme vu avec son empreinte courante.
   *
   * @param {object} item
   * @returns {void}
   */
  function markSeen(item) {
    const fp = itemFingerprint(item);
    if (!fp) return;
    const map = getSeenMap();
    map[String(item.id_com)] = fp;
    saveSeenMap(map);
  }

  /**
   * Marque manuellement un commentaire vu à partir de ses textes (utile
   * juste après une sauvegarde admin, avant que l'inventaire ne soit rechargé).
   *
   * @param {number|string} commentId
   * @param {string} comUser
   * @param {string} comAdmin
   * @returns {void}
   */
  function markSeenByContent(commentId, comUser, comAdmin) {
    if (!commentId) return;
    const map = getSeenMap();
    map[String(commentId)] = fingerprint(comUser, comAdmin);
    saveSeenMap(map);
  }

  /**
   * Renvoie la liste des items de l'inventaire courant qui possèdent un
   * commentaire considéré comme nouveau.
   *
   * @returns {Array<object>}
   */
  function getNewComments() {
    const inv = window.allInventory || [];
    return inv.filter(isNew);
  }

  /**
   * Dessine la pastille rouge sur les boutons "Voir le commentaire" des
   * commentaires nouveaux, et synchronise le compteur du bouton global.
   *
   * @returns {void}
   */
  function applyBadges() {
    const inv = window.allInventory || [];
    const fpByCommentId = {};
    inv.forEach((it) => {
      if (it.id_com) fpByCommentId[String(it.id_com)] = itemFingerprint(it);
    });
    const seen = getSeenMap();

    document
      .querySelectorAll("button[data-comment-btn][data-id-com]")
      .forEach((btn) => {
        const idCom = btn.getAttribute("data-id-com");
        const fp = fpByCommentId[idCom];
        const isNewForUi = !!fp && seen[idCom] !== fp;
        btn.classList.toggle("relative", true);
        let dot = btn.querySelector(".comment-badge-dot");
        if (isNewForUi) {
          if (!dot) {
            dot = document.createElement("span");
            dot.className =
              "comment-badge-dot absolute -top-1 -right-1 w-2.5 h-2.5 rounded-full bg-red-500 ring-2 ring-white";
            btn.appendChild(dot);
          }
        } else if (dot) {
          dot.remove();
        }
      });

    updateGlobalButton();
  }

  /**
   * Met à jour le bouton global "Nouveaux commentaires" : compteur visible,
   * masqué si aucun.
   *
   * @returns {void}
   */
  function updateGlobalButton() {
    const btn = document.getElementById("btn_new_comments");
    if (!btn) return;
    const count = getNewComments().length;
    const counter = btn.querySelector("#new_comments_counter");
    if (counter) counter.textContent = count;
    btn.classList.toggle("hidden", count === 0);
  }

  /**
   * Ouvre une modale listant tous les commentaires considérés comme nouveaux.
   * Chaque entrée permet d'ouvrir directement la modale de commentaire de
   * l'objet correspondant. Un bouton "Tout marquer comme lu" est proposé.
   *
   * @returns {void}
   */
  function openNewCommentsModal() {
    const items = getNewComments();
    const existing = document.getElementById("new-comments-modal");
    if (existing) existing.remove();

    const overlay = document.createElement("div");
    overlay.id = "new-comments-modal";
    const sidebar = document.getElementById("sidebar");
    const sidebarOpen = sidebar && !sidebar.classList.contains("collapsed");
    overlay.style.cssText = [
      "position: fixed",
      "top: 0",
      "right: 0",
      "bottom: 0",
      "left: " + (sidebarOpen ? "var(--sidebar-w, 280px)" : "0"),
      "z-index: 1000",
      "display: flex",
      "align-items: center",
      "justify-content: center",
      "padding: 24px",
      "background: rgba(0,0,0,0.5)",
    ].join("; ");
    overlay.innerHTML = `
      <div data-close style="position: absolute; inset: 0;"></div>
      <div style="background: #fff; border-radius: 14px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); width: 100%; max-width: 640px; max-height: 85vh; display: flex; flex-direction: column; position: relative; z-index: 1;">
        <div class="flex items-center justify-between border-b border-gray-100" style="padding: 24px 24px 20px;">
          <h2 class="text-xl font-bold flex items-center gap-2">
            Nouveaux commentaires
            <span class="text-sm font-semibold text-white bg-red-500" style="padding: 4px 10px; border-radius: 8px; min-width: 32px; text-align: center; display: inline-block;">${items.length}</span>
          </h2>
          <button class="text-gray-400 text-2xl leading-none" data-close>&times;</button>
        </div>
        <div class="overflow-y-auto custom-scrollbar flex-1" style="padding: 24px;" id="new-comments-list"></div>
        <div class="flex justify-end gap-3 border-t border-gray-100" style="padding: 20px 24px 24px;">
          <button class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg text-sm font-medium" data-close>Fermer</button>
          <button id="btn_mark_all_seen" class="px-4 py-2 bg-custom-brandLight text-white font-semibold rounded-lg text-sm ${items.length === 0 ? "hidden" : ""}">
            Tout marquer comme lu
          </button>
        </div>
      </div>
    `;
    document.body.appendChild(overlay);

    if (sidebar) {
      const sync = () => {
        const open = !sidebar.classList.contains("collapsed");
        overlay.style.left = open ? "var(--sidebar-w, 280px)" : "0";
      };
      const observer = new MutationObserver(sync);
      observer.observe(sidebar, { attributes: true, attributeFilter: ["class"] });
      overlay.addEventListener("remove-cleanup", () => observer.disconnect());
    }
    const _origRemove = overlay.remove.bind(overlay);
    overlay.remove = function () {
      overlay.dispatchEvent(new Event("remove-cleanup"));
      _origRemove();
    };

    const list = overlay.querySelector("#new-comments-list");
    if (items.length === 0) {
      list.innerHTML =
        '<p class="text-sm text-gray-500 text-center py-8">Aucun nouveau commentaire.</p>';
    } else {
      list.innerHTML = items
        .map((it) => {
          const preview = (it.com_user || it.com_admin || "")
            .toString()
            .replace(/[<>&]/g, (c) => ({ "<": "&lt;", ">": "&gt;", "&": "&amp;" }[c]))
            .slice(0, 140);
          const more =
            ((it.com_user || "").length + (it.com_admin || "").length) > 140
              ? "…"
              : "";
          return `
            <div class="border border-gray-200 rounded-lg p-3 mb-3 last:mb-0 flex items-start gap-3">
              <div class="flex-1 min-w-0">
                <div class="text-xs text-gray-500 mb-1">${it.Code_bar} — ${it.Type || ""} / ${it.Nom || ""}</div>
                <div class="text-sm text-gray-700 whitespace-pre-wrap break-words">${preview}${more}</div>
              </div>
              <button class="px-3 py-1.5 bg-custom-brandLight text-white rounded-lg text-xs font-semibold shrink-0"
                      data-open-comment="${it.id}" data-has-comment="${it.id_com ? "1" : "0"}">
                Ouvrir
              </button>
            </div>
          `;
        })
        .join("");
    }

    overlay.querySelectorAll("[data-close]").forEach((el) => {
      el.addEventListener("click", () => overlay.remove());
    });

    overlay.querySelectorAll("[data-open-comment]").forEach((btn) => {
      btn.addEventListener("click", () => {
        const objetId = Number(btn.getAttribute("data-open-comment"));
        const has = btn.getAttribute("data-has-comment") === "1";
        overlay.remove();
        if (window.openCommentModal) window.openCommentModal(objetId, has);
      });
    });

    const markAll = overlay.querySelector("#btn_mark_all_seen");
    if (markAll) {
      markAll.addEventListener("click", () => {
        items.forEach(markSeen);
        applyBadges();
        overlay.remove();
      });
    }
  }

  window.commentNotifications = {
    isNew,
    markSeen,
    markSeenByContent,
    getNewComments,
    applyBadges,
    openNewCommentsModal,
  };
})();
