/**
 * sessionGuard.js — Détection de l'expiration de la session admin PHP.
 *
 * 1) Wrappe `window.fetch` : si une réponse a suivi une redirection vers
 *    `admin.php` (page de login servie par auth_check.php), affiche un
 *    avertissement via showAlert puis redirige le navigateur.
 * 2) Heartbeat : un poll toutes les 15 s sur `php/checkSession.php`
 *    (endpoint passif qui NE prolonge PAS la session) permet de détecter
 *    l'expiration des 15 minutes d'inactivité côté serveur sans attendre
 *    une action utilisateur.
 *
 * Dépendance : customModal.js (showAlert).
 */
(function () {
  const originalFetch = window.fetch;
  let sessionExpired = false;

  /**
   * Déclenche la popup d'expiration et redirige vers la page de login.
   * Idempotent : une fois `sessionExpired = true`, les appels suivants ne
   * réaffichent pas la modale.
   *
   * @param {string} redirectUrl - URL de la page de connexion à charger.
   * @returns {Promise<void>}
   */
  async function triggerExpiration(redirectUrl) {
    if (sessionExpired) return;
    sessionExpired = true;

    await showAlert(
      "Votre session a expiré en raison d'une période d'inactivité.\nVous allez être redirigé vers la page de connexion.",
      "warning",
    );

    window.location.href = redirectUrl;
  }

  /**
   * Remplacement de window.fetch qui intercepte les redirections d'auth.
   *
   * @param {...*} args - Arguments d'origine de fetch (input, init).
   * @returns {Promise<Response>} Réponse fetch standard, ou rejet si session expirée.
   */
  window.fetch = async function (...args) {
    const response = await originalFetch.apply(this, args);

    if (sessionExpired) {
      throw new Error("Session expirée");
    }

    if (response.redirected && response.url.includes("admin.php")) {
      await triggerExpiration(response.url);
      throw new Error("Session expirée");
    }

    return response;
  };

  /**
   * Heartbeat passif : interroge `auth_check.php` toutes les 15 s.
   * L'en-tête X-Session-Guard empêche la mise à jour de l'activité.
   * En cas d'expiration, auth_check.php redirige vers `admin.php`,
   * ce qui est détecté par l'intercepteur ci-dessus.
   */
  const HEARTBEAT_MS = 15000;
  setInterval(() => {
    if (sessionExpired) return;
    window
      .fetch("/php/checkSession.php", {
        headers: { "X-Session-Guard": "1" },
        cache: "no-store",
      })
      .catch(() => {
        /* l'intercepteur a déjà géré l'expiration ou il s'agit d'une erreur réseau */
      });
  }, HEARTBEAT_MS);
})();
