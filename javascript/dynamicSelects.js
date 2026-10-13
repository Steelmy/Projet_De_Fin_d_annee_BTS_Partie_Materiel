/**
 * dynamicSelects.js — Selects en cascade Type → Sous-type → Nom.
 *
 * Charge l'arbre des références une fois et expose `referenceTree` en global.
 * Utilisé par les formulaires d'ajout, de consultation et de suppression.
 */

window.referenceTree = {};

document.addEventListener("DOMContentLoaded", async () => {
  await loadReferenceTree();

  initCascadeSelects("ajout");
  initCascadeSelects("consultation", true);
  initCascadeSelects("suppr", false, false);
});

/**
 * Charge l'arbre complet des références depuis le serveur,
 * peuple les selects existants et rafraîchit `window.referenceTree`.
 *
 * @returns {Promise<boolean>} true si le chargement a réussi.
 */
window.loadReferenceTree = async function () {
  try {
    const response = await fetch("php/getReferenceTree.php");
    const data = await response.json();

    if (data.success) {
      referenceTree = data.tree;

      populateTypes("ajout");
      populateTypes("consultation", true);
      populateTypes("suppr", false);

      console.log("✅ Arbre des références chargé :", referenceTree);
      return true;
    } else {
      console.error(
        "❌ Erreur lors du chargement des références:",
        data.message,
      );
      return false;
    }
  } catch (error) {
    console.error("❌ Erreur:", error);
    return false;
  }
};

/**
 * Câble les écouteurs de cascade sur les trois selects (Type/Sous-type/Nom)
 * d'un formulaire identifié par son préfixe (`ajout`, `consultation`, `suppr`).
 *
 * @param {string} formPrefix - Préfixe des IDs des selects (ex: `type_materiel_<prefix>`).
 * @param {boolean} [isFilterMode=false] - Ajoute des options "Tous les..." pour filtrage.
 * @param {boolean} [isDisabled=false] - Force les selects à rester désactivés (mode lecture seule).
 * @returns {void}
 */
function initCascadeSelects(
  formPrefix,
  isFilterMode = false,
  isDisabled = false,
) {
  const typeSelect = document.getElementById(`type_materiel_${formPrefix}`);
  const sousTypeSelect = document.getElementById(
    `sous_type_materiel_${formPrefix}`,
  );
  const nomSelect = document.getElementById(`nom_materiel_${formPrefix}`);

  if (!typeSelect || !sousTypeSelect || !nomSelect) return;

  typeSelect.addEventListener("change", () => {
    const selectedType = typeSelect.value;

    sousTypeSelect.innerHTML = "";
    nomSelect.innerHTML = "";

    if (!selectedType) {
      addDefaultOption(
        sousTypeSelect,
        isFilterMode ? "Tous les sous-types" : "Sélectionner un sous-type",
      );
      addDefaultOption(
        nomSelect,
        isFilterMode ? "Tous les noms" : "Sélectionner un nom",
      );
      sousTypeSelect.disabled = true;
      nomSelect.disabled = true;
    } else {
      const sousTypes = Object.keys(referenceTree[selectedType] || {});

      if (sousTypes.length === 1 && sousTypes[0] === "") {
        addDefaultOption(
          sousTypeSelect,
          isFilterMode ? "Tous les sous-types" : "(Aucun sous-type)",
        );
        if (!isDisabled) {
          sousTypeSelect.disabled = true;
        }
      } else {
        addDefaultOption(
          sousTypeSelect,
          isFilterMode ? "Tous les sous-types" : "Sélectionner un sous-type",
        );
        sousTypes.forEach((st) => {
          const option = document.createElement("option");
          option.value = st;
          option.textContent = st || "(Aucun sous-type)";
          sousTypeSelect.appendChild(option);
        });
        if (!isDisabled) sousTypeSelect.disabled = false;
      }

      addDefaultOption(
        nomSelect,
        isFilterMode ? "Tous les noms" : "Sélectionner un nom",
      );
      nomSelect.disabled = true;
    }

    dispatchChangeEvent(sousTypeSelect);
  });

  sousTypeSelect.addEventListener("change", () => {
    const selectedType = typeSelect.value;
    const selectedSousType = sousTypeSelect.value;

    nomSelect.innerHTML = "";

    if (!selectedType) {
      addDefaultOption(
        nomSelect,
        isFilterMode ? "Tous les noms" : "Sélectionner un nom",
      );
      nomSelect.disabled = true;
      dispatchChangeEvent(nomSelect);
      return;
    }

    let isPlaceholderSelected = false;
    const sousTypes = Object.keys(referenceTree[selectedType] || {});
    if (sousTypes.length === 1 && sousTypes[0] === "") {
      isPlaceholderSelected = false;
    } else if (
      sousTypeSelect.selectedIndex === 0 ||
      sousTypeSelect.value === ""
    ) {
      isPlaceholderSelected = true;
    }

    if (isPlaceholderSelected) {
      addDefaultOption(
        nomSelect,
        isFilterMode ? "Tous les noms" : "Sélectionner un nom",
      );
      nomSelect.disabled = true;
    } else {
      let noms = [];
      if (isFilterMode && selectedSousType === "") {
        const sousTypesObj = referenceTree[selectedType] || {};
        Object.values(sousTypesObj).forEach((nList) => {
          noms = noms.concat(nList);
        });
        noms = [...new Set(noms)];
      } else {
        noms =
          referenceTree[selectedType] &&
          referenceTree[selectedType][selectedSousType]
            ? referenceTree[selectedType][selectedSousType]
            : [];
      }

      noms.sort((a, b) => a.localeCompare(b));

      addDefaultOption(
        nomSelect,
        isFilterMode ? "Tous les noms" : "Sélectionner un nom",
      );
      noms.forEach((nom) => {
        const option = document.createElement("option");
        option.value = nom;
        option.textContent = nom;
        nomSelect.appendChild(option);
      });

      if (!isDisabled) nomSelect.disabled = false;
    }

    dispatchChangeEvent(nomSelect);
  });
}

/**
 * Remplit le select "Type" avec les clés racines de `referenceTree`
 * et préserve la valeur courante si elle existe encore.
 *
 * @param {string} formPrefix - Préfixe des IDs (ex: `ajout`).
 * @param {boolean} [isFilterMode=false] - Ajoute une option "Tous les types".
 * @returns {void}
 */
function populateTypes(formPrefix, isFilterMode = false) {
  const typeSelect = document.getElementById(`type_materiel_${formPrefix}`);

  if (!typeSelect) return;

  const currentVal = typeSelect.value;

  typeSelect.innerHTML = "";
  addDefaultOption(
    typeSelect,
    isFilterMode ? "Tous les types" : "Sélectionner un type",
  );

  Object.keys(referenceTree).forEach((type) => {
    const option = document.createElement("option");
    option.value = type;
    option.textContent = type;
    typeSelect.appendChild(option);
  });

  if (currentVal && referenceTree[currentVal]) {
    typeSelect.value = currentVal;
  }

  dispatchChangeEvent(typeSelect);
}

/**
 * Insère une option `value=""` (placeholder ou "Tous les...") dans un select.
 *
 * @param {HTMLSelectElement} selectConfig - Select cible.
 * @param {string} text - Libellé affiché.
 * @returns {void}
 */
function addDefaultOption(selectConfig, text) {
  const option = document.createElement("option");
  option.value = "";
  option.textContent = text;
  selectConfig.appendChild(option);
}

/**
 * Émet un événement `change` synthétique sur un élément.
 *
 * @param {Element} element - Élément cible.
 * @returns {void}
 */
function dispatchChangeEvent(element) {
  if ("createEvent" in document) {
    var evt = document.createEvent("HTMLEvents");
    evt.initEvent("change", false, true);
    element.dispatchEvent(evt);
  } else {
    element.fireEvent("onchange");
  }
}

/**
 * Définit en une fois les trois valeurs Type/Sous-type/Nom d'un formulaire,
 * typiquement utilisée après scan d'un code-barres.
 *
 * Les setTimeout permettent à chaque cascade de peupler ses options
 * avant que la suivante n'essaie d'y sélectionner une valeur.
 *
 * @param {string} formPrefix - Préfixe des IDs de selects.
 * @param {string} typeVal - Valeur Type à appliquer.
 * @param {string|null} sousTypeVal - Valeur Sous-type (null/'' = Aucun sous-type).
 * @param {string} nomVal - Valeur Nom à appliquer.
 * @returns {void}
 */
window.setSelectCascadeValues = function (
  formPrefix,
  typeVal,
  sousTypeVal,
  nomVal,
) {
  const typeSelect = document.getElementById(`type_materiel_${formPrefix}`);
  const sousTypeSelect = document.getElementById(
    `sous_type_materiel_${formPrefix}`,
  );
  const nomSelect = document.getElementById(`nom_materiel_${formPrefix}`);

  if (!typeSelect || !sousTypeSelect || !nomSelect) return;

  if (typeVal && referenceTree[typeVal]) {
    typeSelect.value = typeVal;
    dispatchChangeEvent(typeSelect);

    setTimeout(() => {
      const stVal = sousTypeVal || "";
      if (referenceTree[typeVal][stVal]) {
        sousTypeSelect.value = stVal;
        dispatchChangeEvent(sousTypeSelect);

        setTimeout(() => {
          if (nomVal) {
            nomSelect.value = nomVal;
            dispatchChangeEvent(nomSelect);
          }
        }, 10);
      }
    }, 10);
  }
};
