/**
 * sortUtils.js — Comparateur de tri locale partagé.
 *
 * Centralise la logique de tri auparavant dupliquée dans
 * filterConsultation.js, addBox.js et updateBox.js.
 * Gère les accents français via localeCompare('fr'), les valeurs vides
 * (placées en fin pour asc, en début pour desc) et la colonne combinée
 * "Utilisateur" (Prénom + Nom_utilisateur).
 */

/**
 * Comparateur de tri pour `Array.prototype.sort` sur des objets.
 *
 * @param {object} a - Premier objet à comparer.
 * @param {object} b - Second objet à comparer.
 * @param {string} column - Nom de la colonne de tri (clé d'objet ou "Utilisateur").
 * @param {"asc"|"desc"} direction - Sens du tri.
 * @returns {number} Résultat de comparaison (-1, 0 ou 1).
 */
window.localeSortComparator = function (a, b, column, direction) {
  let valA, valB;

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

  if (valA === "" && valB !== "") return direction === "asc" ? 1 : -1;
  if (valB === "" && valA !== "") return direction === "asc" ? -1 : 1;

  const comparison = valA.localeCompare(valB, "fr");
  return direction === "asc" ? comparison : -comparison;
};
