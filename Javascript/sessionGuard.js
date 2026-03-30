/**
 * sessionGuard.js — Détecte l'expiration de session PHP
 *
 * Intercepte les réponses fetch() : si le serveur redirige vers admin.php
 * (session absente ou expirée), affiche une popup et redirige l'utilisateur.
 *
 * Dépendance : customModal.js (showAlert)
 */
(function () {
  const originalFetch = window.fetch;
  let sessionExpired = false;

  window.fetch = async function (...args) {
    const response = await originalFetch.apply(this, args);

    if (sessionExpired) {
      throw new Error("Session expirée");
    }

    // auth_check.php redirige vers admin.php — fetch suit la redirection
    // silencieusement, donc on vérifie l'URL finale de la réponse
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
