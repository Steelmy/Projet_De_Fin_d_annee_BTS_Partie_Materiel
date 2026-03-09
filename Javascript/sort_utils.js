/**
 * sort_utils.js — Utilitaire de tri centralisé
 *
 * Principe DRY : Remplace la logique de tri dupliquée dans
 * filter_consultation.js, add_caisse.js, et update_caisse.js.
 *
 * Gère : accents français via localeCompare('fr'),
 *        valeurs vides en fin (asc) ou début (desc),
 *        colonne "Utilisateur" combinée (Prénom + Nom).
 */

/**
 * Comparateur de tri locale pour les tableaux
 *
 * @param {Object} a - Premier objet à comparer
 * @param {Object} b - Second objet à comparer
 * @param {string} column - Nom de la colonne de tri
 * @param {string} direction - 'asc' ou 'desc'
 * @returns {number} Résultat de la comparaison
 */
window.localeSortComparator = function (a, b, column, direction) {
  let valA, valB;

  // Gestion spéciale de la colonne "Utilisateur" (Prénom + Nom combinés)
  if (column === "Utilisateur") {
    valA =
      (a.Prénom ? a.Prénom : "") +
      (a.Nom_utilisateur ? " " + a.Nom_utilisateur : "");
    valB =
      (b.Prénom ? b.Prénom : "") +
      (b.Nom_utilisateur ? " " + b.Nom_utilisateur : "");
    valA = valA.trim().toLowerCase();
    valB = valB.trim().toLowerCase();
  } else {
    valA = a[column] ? String(a[column]).toLowerCase() : "";
    valB = b[column] ? String(b[column]).toLowerCase() : "";
  }

  // Valeurs vides : en fin pour asc, en début pour desc
  if (valA === "" && valB !== "") return direction === "asc" ? 1 : -1;
  if (valB === "" && valA !== "") return direction === "asc" ? -1 : 1;

  // Comparaison locale française (gère les accents)
  const comparison = valA.localeCompare(valB, "fr");
  return direction === "asc" ? comparison : -comparison;
};
