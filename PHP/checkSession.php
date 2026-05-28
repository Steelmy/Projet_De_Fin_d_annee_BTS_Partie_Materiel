<?php
/**
 * Vérification passive de la session admin (heartbeat polling).
 *
 * Reproduit la logique d'expiration de `IHM_admin/auth_check.php`
 * (session admin absente ou inactivité > 15 min) MAIS ne met
 * volontairement PAS à jour `$_SESSION['last_activity']`.
 *
 * Cet endpoint est destiné à être appelé périodiquement par le frontend
 * (cf. sessionGuard.js) pour détecter l'expiration sans la prolonger.
 *
 * Réponses :
 *   - Session expirée ou absente : redirige vers `../IHM_admin/admin.php`
 *     (avec `?msg=expire` en cas d'inactivité dépassée), ce qui déclenche
 *     la popup côté `sessionGuard.js` via `response.redirected`.
 *   - Session valide : JSON `{"ok": true}` (last_activity intact).
 */

session_start();

header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../IHM_admin/admin.php');
    exit;
}

$delai_inactivite = 900;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $delai_inactivite)) {
    session_unset();
    session_destroy();
    header('Location: ../IHM_admin/admin.php?msg=expire');
    exit;
}

header('Content-Type: application/json');
echo json_encode(['ok' => true]);
