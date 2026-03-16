// javascript/dynamicSelects.js
// Gère la logique des listes déroulantes en cascade pour (Type -> Sous-type -> Nom)

let referenceTree = {};

document.addEventListener('DOMContentLoaded', async () => {
    await loadReferenceTree();
    
    // Initialiser les écouteurs sur les formulaires
    initCascadeSelects('ajout');
    initCascadeSelects('consultation', true); // true = mode filtre (ajoute "Tous les...")
    initCascadeSelects('suppr', false, false); // disabled a été enlevé pour permettre le choix manuel
});

/**
 * Charge l'arbre complet des références depuis le serveur
 */
window.loadReferenceTree = async function() {
    try {
        const response = await fetch('php/getReferenceTree.php');
        const data = await response.json();
        
        if (data.success) {
            referenceTree = data.tree;
            
            // Re-remplir les selects 'Type' initiaux existants sur la page
            populateTypes('ajout');
            populateTypes('consultation', true);
            // Pour la suppression, on est souvent readonly/disabled mais on popule quand même les options pour pouvoir sélectionner dynamiquement via JS (scan barcode)
            populateTypes('suppr', false); 
            
            console.log("✅ Arbre des références chargé :", referenceTree);
            return true;
        } else {
            console.error('❌ Erreur lors du chargement des références:', data.message);
            return false;
        }
    } catch (error) {
        console.error('❌ Erreur:', error);
        return false;
    }
}

/**
 * Initialise les écouteurs de cascade pour un formulaire spécifique
 */
function initCascadeSelects(formPrefix, isFilterMode = false, isDisabled = false) {
    const typeSelect = document.getElementById(`type_materiel_${formPrefix}`);
    const sousTypeSelect = document.getElementById(`sous_type_materiel_${formPrefix}`);
    const nomSelect = document.getElementById(`nom_materiel_${formPrefix}`);

    if (!typeSelect || !sousTypeSelect || !nomSelect) return;

    // Au changement du Type
    typeSelect.addEventListener('change', () => {
        const selectedType = typeSelect.value;
        
        // Vider les sous-listes
        sousTypeSelect.innerHTML = '';
        nomSelect.innerHTML = '';
        
        if (!selectedType) {
            // Option vide ou "Tous"
            addDefaultOption(sousTypeSelect, isFilterMode ? "Tous les sous-types" : "Sélectionner un sous-type");
            addDefaultOption(nomSelect, isFilterMode ? "Tous les noms" : "Sélectionner un nom");
            if (!isFilterMode) {
                sousTypeSelect.disabled = true;
                nomSelect.disabled = true;
            }
        } else {
            // Remplir Sous-types
            const sousTypes = Object.keys(referenceTree[selectedType] || {});
            
            if (sousTypes.length === 1 && sousTypes[0] === "") {
                // Aucun sous-type distinct pour ce type
                addDefaultOption(sousTypeSelect, isFilterMode ? "Tous les sous-types" : "(Aucun sous-type)");
                if (!isDisabled && !isFilterMode) {
                    sousTypeSelect.disabled = true;
                }
            } else {
                addDefaultOption(sousTypeSelect, isFilterMode ? "Tous les sous-types" : "Sélectionner un sous-type");
                sousTypes.forEach(st => {
                    const option = document.createElement('option');
                    option.value = st;
                    option.textContent = st || "(Aucun sous-type)";
                    sousTypeSelect.appendChild(option);
                });
                if (!isDisabled) sousTypeSelect.disabled = false;
            }
            
            // On laisse l'événement 'change' sur sousTypeSelect peupler les Noms
            addDefaultOption(nomSelect, isFilterMode ? "Tous les noms" : "Sélectionner un nom");
            if (!isFilterMode) nomSelect.disabled = true; 
        }
        
        // Déclencher events potentiels liés
        dispatchChangeEvent(sousTypeSelect);
    });

    // Au changement du Sous-type
    sousTypeSelect.addEventListener('change', () => {
        const selectedType = typeSelect.value;
        const selectedSousType = sousTypeSelect.value;
        
        nomSelect.innerHTML = '';
        
        if (!selectedType) {
            addDefaultOption(nomSelect, isFilterMode ? "Tous les noms" : "Sélectionner un nom");
            if (!isFilterMode) nomSelect.disabled = true;
            dispatchChangeEvent(nomSelect);
            return;
        }

        let isPlaceholderSelected = false;
        if (!isFilterMode) {
            const sousTypes = Object.keys(referenceTree[selectedType] || {});
            if (sousTypes.length === 1 && sousTypes[0] === "") {
                isPlaceholderSelected = false; // Seule option valide
            } else if (sousTypeSelect.selectedIndex === 0) {
                isPlaceholderSelected = true; // "Sélectionner un..." est choisi
            }
        }

        if (isPlaceholderSelected) {
            addDefaultOption(nomSelect, "Sélectionner un nom");
            if (!isFilterMode) nomSelect.disabled = true;
        } else {
            let noms = [];
            if (isFilterMode && selectedSousType === "") {
                const sousTypesObj = referenceTree[selectedType] || {};
                Object.values(sousTypesObj).forEach(nList => {
                    noms = noms.concat(nList);
                });
                noms = [...new Set(noms)];
            } else {
                noms = (referenceTree[selectedType] && referenceTree[selectedType][selectedSousType]) ? referenceTree[selectedType][selectedSousType] : [];
            }
            
            noms.sort((a, b) => a.localeCompare(b));
            
            addDefaultOption(nomSelect, isFilterMode ? "Tous les noms" : "Sélectionner un nom");
            noms.forEach(nom => {
                const option = document.createElement('option');
                option.value = nom;
                option.textContent = nom;
                nomSelect.appendChild(option);
            });
            
            if (!isDisabled && !isFilterMode) nomSelect.disabled = false;
        }
        
        dispatchChangeEvent(nomSelect);
    });
}

/**
 * Remplit le select "Type" avec les clés de l'arbre
 */
function populateTypes(formPrefix, isFilterMode = false) {
    const typeSelect = document.getElementById(`type_materiel_${formPrefix}`);
    const sousTypeSelect = document.getElementById(`sous_type_materiel_${formPrefix}`);
    const nomSelect = document.getElementById(`nom_materiel_${formPrefix}`);

    if (!typeSelect) return;

    // Conserver la sélection actuelle si possible
    const currentVal = typeSelect.value;

    typeSelect.innerHTML = '';
    addDefaultOption(typeSelect, isFilterMode ? "Tous les types" : "Sélectionner un type");

    Object.keys(referenceTree).forEach(type => {
        const option = document.createElement('option');
        option.value = type;
        option.textContent = type;
        typeSelect.appendChild(option);
    });

    if (currentVal && referenceTree[currentVal]) {
        typeSelect.value = currentVal;
    }
    
    // Si on vient de recharger, on relance la cascade
    dispatchChangeEvent(typeSelect);
}

/**
 * Construit l'option par défaut d'un select
 */
function addDefaultOption(selectConfig, text) {
    const option = document.createElement('option');
    option.value = "";
    option.textContent = text;
    selectConfig.appendChild(option);
}

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
 * Utilisé principalement par le scan de code-barre pour 
 * définir dynamiquement les 3 valeurs d'un coup
 */
window.setSelectCascadeValues = function(formPrefix, typeVal, sousTypeVal, nomVal) {
    const typeSelect = document.getElementById(`type_materiel_${formPrefix}`);
    const sousTypeSelect = document.getElementById(`sous_type_materiel_${formPrefix}`);
    const nomSelect = document.getElementById(`nom_materiel_${formPrefix}`);
    
    if (!typeSelect || !sousTypeSelect || !nomSelect) return;
    
    // Set Type
    if (typeVal && referenceTree[typeVal]) {
        typeSelect.value = typeVal;
        dispatchChangeEvent(typeSelect); // Remplit les sous-types
        
        // Timeout pour laisser le temps au script de peupler
        setTimeout(() => {
            const stVal = sousTypeVal || ""; // Si null, utilise "" (Aucun sous-type)
            if (referenceTree[typeVal][stVal]) {
                sousTypeSelect.value = stVal;
                dispatchChangeEvent(sousTypeSelect); // Remplit les noms
                
                setTimeout(() => {
                    if (nomVal) {
                        nomSelect.value = nomVal;
                        // On dispatch le change pour que les vues (comme consultation) se mettent à jour si besoin
                        dispatchChangeEvent(nomSelect);
                    }
                }, 10);
            }
        }, 10);
    }
};
