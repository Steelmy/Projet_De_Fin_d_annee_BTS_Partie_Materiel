/**
 * sessionGuard.js — Détection de l'expiration de la session admin PHP.
 *
 * Wrappe `window.fetch` : si une réponse a suivi une redirection vers
 * `admin.php` (page de login servie par auth_check.php), affiche un avertissement
 * via showAlert puis redirige le navigateur vers la page de connexion.
 *
 * Dépendance : customModal.js (showAlert).
 */
(function () {
  const originalFetch = window.fetch;
  let sessionExpired = false;

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
      sessionExpired = true;

      await showAlert(
        "Votre session a expiré en raison d'une période d'inactivité.\nVous allez être redirigé vers la page de connexion.",
        "warning"
      );

      window.location.href = response.url;
      throw new Error("Session expirée");
    }

    return response;
  };
})();
