/**
 * referenceManager.js — Modale "Gestion des références" (catalogue Type/Sous-type/Nom).
 *
 * Deux onglets :
 *   - "Ajouter une référence" : formulaire create/update avec selects + option "+ Nouveau...".
 *   - "Liste" : tableau avec sélection multiple (suppression) et édition à l'unité.
 *
 * Les selects Type et Sous-type partagent un placeholder spécial NEW_VALUE
 * qui révèle un input texte pour saisir une nouvelle valeur.
 */

document.addEventListener('DOMContentLoaded', () => {
    const refModal = document.getElementById('reference-modal');
    const refForm = document.getElementById('form_create_reference');
    const refMessage = document.getElementById('ref_message');

    const typeSelect = document.getElementById('ref_type_select');
    const typeNewInput = document.getElementById('ref_type_new');
    const sousTypeSelect = document.getElementById('ref_sous_type_select');
    const sousTypeNewInput = document.getElementById('ref_sous_type_new');

    /** Marqueur de l'option "+ Nouveau..." dans les selects Type/Sous-type. */
    const NEW_VALUE = '__new__';

    /**
     * Peuple le select Type depuis `window.referenceTree`
     * (chargé par dynamicSelects.js) et ajoute l'option "+ Nouveau type...".
     *
     * @returns {void}
     */
    function populateRefTypeSelect() {
        if (!typeSelect) return;
        const tree = window.referenceTree || {};

        typeSelect.innerHTML = '';
        typeSelect.appendChild(createOption('', 'Sélectionner un type'));

        Object.keys(tree).sort().forEach(type => {
            typeSelect.appendChild(createOption(type, type));
        });

        typeSelect.appendChild(createOption(NEW_VALUE, '+ Nouveau type...'));

        resetSousTypeSelect();
        typeNewInput.classList.add('hidden');
        typeNewInput.value = '';
    }

    /**
     * Peuple le select Sous-type pour le type sélectionné.
     *
     * @param {string} selectedType - Type parent.
     * @returns {void}
     */
    function populateRefSousTypeSelect(selectedType) {
        if (!sousTypeSelect) return;
        const tree = window.referenceTree || {};

        sousTypeSelect.innerHTML = '';
        sousTypeNewInput.classList.add('hidden');
        sousTypeNewInput.value = '';

        if (!selectedType || !tree[selectedType]) {
            sousTypeSelect.appendChild(createOption('', 'Sélectionner un sous-type'));
            sousTypeSelect.disabled = true;
            return;
        }

        const sousTypes = Object.keys(tree[selectedType]);

        if (sousTypes.length === 1 && sousTypes[0] === '') {
            sousTypeSelect.appendChild(createOption('', '(Aucun sous-type)'));
            sousTypeSelect.disabled = true;
        } else {
            sousTypeSelect.appendChild(createOption('', 'Sélectionner un sous-type'));
            sousTypes.sort().forEach(st => {
                sousTypeSelect.appendChild(createOption(st, st || '(Aucun sous-type)'));
            });
            sousTypeSelect.disabled = false;
        }

        sousTypeSelect.appendChild(createOption(NEW_VALUE, '+ Nouveau sous-type...'));
    }

    /**
     * Réinitialise le select Sous-type à son placeholder par défaut.
     *
     * @returns {void}
     */
    function resetSousTypeSelect() {
        if (!sousTypeSelect) return;
        sousTypeSelect.innerHTML = '';
        sousTypeSelect.appendChild(createOption('', 'Sélectionner un sous-type'));
        sousTypeSelect.disabled = true;
        sousTypeNewInput.classList.add('hidden');
        sousTypeNewInput.value = '';
    }

    /**
     * Crée un élément `<option>` configuré.
     *
     * @param {string} value - Attribut value.
     * @param {string} text - Texte affiché.
     * @returns {HTMLOptionElement}
     */
    function createOption(value, text) {
        const opt = document.createElement('option');
        opt.value = value;
        opt.textContent = text;
        return opt;
    }

    if (typeSelect) {
        typeSelect.addEventListener('change', () => {
            const val = typeSelect.value;
            if (val === NEW_VALUE) {
                typeNewInput.classList.remove('hidden');
                typeNewInput.focus();
                resetSousTypeSelect();
                sousTypeSelect.disabled = false;
                sousTypeSelect.innerHTML = '';
                sousTypeSelect.appendChild(createOption('', '(Aucun sous-type)'));
                sousTypeSelect.appendChild(createOption(NEW_VALUE, '+ Nouveau sous-type...'));
            } else {
                typeNewInput.classList.add('hidden');
                typeNewInput.value = '';
                populateRefSousTypeSelect(val);
            }
        });
    }

    if (sousTypeSelect) {
        sousTypeSelect.addEventListener('change', () => {
            if (sousTypeSelect.value === NEW_VALUE) {
                sousTypeNewInput.classList.remove('hidden');
                sousTypeNewInput.focus();
            } else {
                sousTypeNewInput.classList.add('hidden');
                sousTypeNewInput.value = '';
            }
        });
    }

    /**
     * Renvoie la valeur effective d'un couple (select, input) :
     * texte du nouvel input si "+ Nouveau..." est sélectionné, sinon valeur du select.
     *
     * @param {HTMLSelectElement} select - Select source.
     * @param {HTMLInputElement} newInput - Input "nouvelle valeur" associé.
     * @returns {string} Valeur effective trimée.
     */
    function getEffectiveValue(select, newInput) {
        if (select.value === NEW_VALUE) {
            return newInput.value.trim();
        }
        return select.value;
    }

    /**
     * Réinitialise le formulaire d'ajout/édition de référence
     * et restaure les libellés par défaut (mode création).
     *
     * @returns {void}
     */
    window.resetReferenceForm = function() {
        if (refForm) refForm.reset();

        populateRefTypeSelect();

        const nomInput = document.getElementById('ref_nom');
        if (nomInput) nomInput.value = '';

        const editIdInput = document.getElementById('ref_edit_id');
        if (editIdInput) editIdInput.value = '';

        const tabAdd = document.getElementById('tab-ref-add');
        if (tabAdd) tabAdd.textContent = 'Ajouter une référence';

        const descAdd = document.getElementById('ref-view-add-desc');
        if (descAdd) descAdd.textContent = "Ajoutez de nouvelles références au catalogue pour les rendre disponibles lors de l'ajout de matériel.";

        if (refMessage) {
            refMessage.classList.add('hidden');
            refMessage.textContent = '';
        }
    };

    /**
     * Ouvre ou ferme la modale de gestion des références.
     *
     * @param {boolean} show - true pour ouvrir, false pour fermer.
     * @returns {void}
     */
    window.toggleReferenceModal = function(show) {
        if (!refModal) return;

        if (show) {
            refModal.classList.remove('hidden');
            refModal.style.display = 'flex';
            document.body.style.overflow = 'hidden';

            populateRefTypeSelect();

            if (refMessage) {
                refMessage.classList.add('hidden');
                refMessage.className = 'text-sm text-center mt-2 hidden font-medium';
                refMessage.textContent = '';
            }

            setTimeout(() => {
                if (typeSelect) typeSelect.focus();
            }, 100);

        } else {
            refModal.classList.add('hidden');
            refModal.style.display = 'none';
            document.body.style.overflow = '';
            if (window.resetReferenceForm) window.resetReferenceForm();
            if (window.switchReferenceTab) window.switchReferenceTab('add');
        }
    };

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && refModal && !refModal.classList.contains('hidden')) {
            window.toggleReferenceModal(false);
        }
    });

    if (refForm) {
        refForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const submitBtn = refForm.querySelector('button[type="submit"]');
            const typeValue = getEffectiveValue(typeSelect, typeNewInput);
            const sousTypeValue = getEffectiveValue(sousTypeSelect, sousTypeNewInput);
            const nomValue = document.getElementById('ref_nom').value.trim();

            if (!typeValue || !nomValue) {
                showMessage('Le type et le nom sont requis.', 'error');
                return;
            }

            try {
                const originalText = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="opacity-70">Enregistrement...</span>';
                submitBtn.classList.add('opacity-75', 'cursor-not-allowed');

                const editIdInput = document.getElementById('ref_edit_id');
                const isEdit = editIdInput && editIdInput.value;
                const endpoint = isEdit ? 'php/updateReference.php' : 'php/addReference.php';
                const payload = {
                    type: typeValue,
                    sous_type: sousTypeValue,
                    nom: nomValue
                };
                if (isEdit) {
                    payload.id = editIdInput.value;
                }

                const response = await fetch(endpoint, {
                    method: isEdit ? 'PUT' : 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();

                if (data.success) {
                    showMessage(data.message || 'Référence ajoutée avec succès !', 'success');

                    refForm.reset();

                    if (window.loadReferenceTree) {
                        await window.loadReferenceTree();
                    }

                    populateRefTypeSelect();

                    setTimeout(() => {
                        window.toggleReferenceModal(false);
                    }, 1500);

                } else {
                    showMessage(data.error || 'Une erreur est survenue lors de l\'ajout.', 'error');
                }

            } catch (error) {
                console.error('Erreur:', error);
                showMessage('Erreur de connexion avec le serveur.', 'error');
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Enregistrer';
                submitBtn.classList.remove('opacity-75', 'cursor-not-allowed');
            }
        });
    }

    /**
     * Affiche un message coloré dans la modale d'ajout/édition.
     *
     * @param {string} msg - Texte du message.
     * @param {"success"|"error"} type - Catégorie qui définit la couleur.
     * @returns {void}
     */
    function showMessage(msg, type) {
        if (!refMessage) return;

        refMessage.textContent = msg;
        refMessage.classList.remove('hidden', 'text-green-600', 'text-red-500');

        if (type === 'success') {
            refMessage.classList.add('text-green-600');
        } else {
            refMessage.classList.add('text-red-500');
        }
    }

    const viewAdd = document.getElementById('ref-view-add');
    const viewList = document.getElementById('ref-view-list');
    const tabAdd = document.getElementById('tab-ref-add');
    const tabList = document.getElementById('tab-ref-list');
    const tableBody = document.getElementById('ref-table-body');
    const checkAll = document.getElementById('ref-check-all');
    const btnDelete = document.getElementById('btn-delete-refs');
    const selectedCountSpan = document.getElementById('ref-selected-count');
    const listMessage = document.getElementById('ref-list-message');

    /**
     * Bascule entre les onglets "Ajouter" et "Liste" de la modale.
     *
     * @param {"add"|"list"} tabName - Onglet cible.
     * @returns {void}
     */
    window.switchReferenceTab = function(tabName) {
        if (tabName === 'add') {
            viewAdd.classList.remove('hidden');
            viewList.classList.add('hidden');

            tabAdd.classList.add('border-b-2', 'border-custom-brandLight', 'text-custom-brandLight');
            tabAdd.classList.remove('text-gray-500', 'hover:text-gray-800');

            tabList.classList.remove('border-b-2', 'border-custom-brandLight', 'text-custom-brandLight');
            tabList.classList.add('text-gray-500', 'hover:text-gray-800');

            const editId = document.getElementById('ref_edit_id');
            if (editId && editId.value && event && event.target && event.target.id === 'tab-ref-add') {
                window.resetReferenceForm();
            }
        } else {
            viewAdd.classList.add('hidden');
            viewList.classList.remove('hidden');

            tabList.classList.add('border-b-2', 'border-custom-brandLight', 'text-custom-brandLight');
            tabList.classList.remove('text-gray-500', 'hover:text-gray-800');

            tabAdd.classList.remove('border-b-2', 'border-custom-brandLight', 'text-custom-brandLight');
            tabAdd.classList.add('text-gray-500', 'hover:text-gray-800');

            window.loadReferencesList();
        }
    };

    /**
     * Charge la liste plate des références depuis le serveur et la rend.
     *
     * @returns {Promise<void>}
     */
    window.loadReferencesList = async function() {
        if (!tableBody) return;
        tableBody.innerHTML = '<tr><td colspan="4" class="p-8 text-center text-gray-500">Chargement des références...</td></tr>';
        checkAll.checked = false;
        updateDeleteButtonState();
        hideListMessage();

        try {
            const response = await fetch('php/getReferencesList.php');
            const data = await response.json();

            if (data.success && data.references) {
                renderReferencesTable(data.references);
            } else {
                tableBody.innerHTML = `<tr><td colspan="4" class="p-8 text-center text-red-500">${data.error || 'Erreur lors du chargement.'}</td></tr>`;
            }
        } catch (error) {
            console.error('Erreur:', error);
            tableBody.innerHTML = '<tr><td colspan="4" class="p-8 text-center text-red-500">Erreur de connexion serveur.</td></tr>';
        }
    };

    /**
     * Construit le tableau HTML des références.
     *
     * @param {Array<{id:number, Type:string, Sous_type:string, Nom:string}>} refs - Lignes à afficher.
     * @returns {void}
     */
    function renderReferencesTable(refs) {
        if (refs.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="4" class="p-8 text-center text-gray-500">Aucune référence trouvée.</td></tr>';
            return;
        }

        tableBody.innerHTML = refs.map(ref => `
            <tr class="group">
                <td class="p-3 text-center border-b border-gray-100"><input type="checkbox" value="${ref.id}" class="ref-checkbox rounded border-gray-300 text-custom-brandLight focus:ring-custom-brandLight/20 cursor-pointer w-4 h-4" onchange="updateDeleteButtonState()"></td>
                <td class="p-3 border-b border-gray-100 text-gray-800">${escapeHtml(ref.Type)}</td>
                <td class="p-3 border-b border-gray-100 text-gray-600">${escapeHtml(ref.Sous_type && ref.Sous_type !== 'Non défini' ? ref.Sous_type : '-')}</td>
                <td class="p-3 border-b border-gray-100 font-medium text-gray-900">${escapeHtml(ref.Nom)}</td>
            </tr>
        `).join('');
    }

    /**
     * Échappe les caractères HTML pour insertion sécurisée via innerHTML.
     *
     * @param {*} unsafe - Valeur à échapper.
     * @returns {string}
     */
    function escapeHtml(unsafe) {
        return (unsafe || '').toString()
             .replace(/&/g, "&amp;")
             .replace(/</g, "&lt;")
             .replace(/>/g, "&gt;")
             .replace(/"/g, "&quot;")
             .replace(/'/g, "&#039;");
    }

    /**
     * Coche/décoche toutes les références listées en suivant l'état d'une checkbox source.
     *
     * @param {HTMLInputElement} source - Checkbox "tout sélectionner".
     * @returns {void}
     */
    window.toggleAllReferences = function(source) {
        const checkboxes = document.querySelectorAll('.ref-checkbox');
        checkboxes.forEach(cb => cb.checked = source.checked);
        updateDeleteButtonState();
    };

    /**
     * Met à jour le compteur de sélection et l'état des boutons d'action
     * (Supprimer toujours, Modifier seulement si une seule ligne est cochée).
     *
     * @returns {void}
     */
    window.updateDeleteButtonState = function() {
        if (!selectedCountSpan || !btnDelete) return;
        const checkboxes = document.querySelectorAll('.ref-checkbox:checked');
        const count = checkboxes.length;
        selectedCountSpan.textContent = count;

        if (count > 0) {
            btnDelete.disabled = false;
            btnDelete.classList.remove('cursor-not-allowed');
            btnDelete.classList.add('cursor-pointer');
            btnDelete.style.backgroundColor = '#ef4444';
        } else {
            btnDelete.disabled = true;
            btnDelete.classList.remove('bg-red-500', 'hover:bg-red-600', 'cursor-pointer');
            btnDelete.classList.add('cursor-not-allowed');
            btnDelete.style.backgroundColor = '#6b7280';
            if (checkAll) checkAll.checked = false;
        }

        const btnEdit = document.getElementById('btn-edit-ref');
        if (btnEdit) {
            if (count === 1) {
                btnEdit.disabled = false;
                btnEdit.classList.remove('hidden', 'cursor-not-allowed');
                btnEdit.classList.add('cursor-pointer');
                btnEdit.style.backgroundColor = '#fbbf24';
            } else {
                btnEdit.disabled = true;
                btnEdit.classList.add('hidden', 'cursor-not-allowed');
                btnEdit.classList.remove('cursor-pointer');
                btnEdit.style.backgroundColor = '#6b7280';
            }
        }
    };

    /**
     * Supprime les références cochées (avec confirmation).
     * Recharge la liste et l'arbre des références en cas de succès partiel ou total.
     *
     * @returns {Promise<void>}
     */
    window.deleteSelectedReferences = async function() {
        const checkboxes = document.querySelectorAll('.ref-checkbox:checked');
        const ids = Array.from(checkboxes).map(cb => cb.value);

        if (ids.length === 0) return;

        if (!(await showConfirm(`Êtes-vous sûr de vouloir supprimer ${ids.length} référence(s) ?`, { confirmText: "Supprimer", type: "error" }))) return;

        btnDelete.disabled = true;
        const originalBtnText = btnDelete.innerHTML;
        btnDelete.innerHTML = 'Suppression...';

        try {
            const response = await fetch('php/deleteReferences.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ids })
            });
            const data = await response.json();

            if (data.success) {
                showListMessage(data.message || 'Références supprimées !', 'success');
                window.loadReferencesList();
                if (window.loadReferenceTree) window.loadReferenceTree();
            } else {
                let errorMsg = data.error || 'Erreur lors de la suppression.';
                if (data.errors && data.errors.length > 0) {
                    errorMsg += '\n' + data.errors.join('\n');
                }
                showListMessage(errorMsg, 'error');

                if (data.deleted_count > 0) {
                    window.loadReferencesList();
                    if (window.loadReferenceTree) window.loadReferenceTree();
                }
            }
        } catch (error) {
            console.error('Erreur:', error);
            showListMessage('Erreur de connexion serveur.', 'error');
        } finally {
            if (btnDelete) {
                btnDelete.disabled = false;
                btnDelete.innerHTML = originalBtnText;
                updateDeleteButtonState();
            }
        }
    };

    /**
     * Affiche un message coloré sous le tableau de la liste des références.
     *
     * @param {string} msg - Texte du message.
     * @param {"success"|"error"} type - Catégorie qui définit la couleur.
     * @returns {void}
     */
    function showListMessage(msg, type) {
        if (!listMessage) return;
        listMessage.textContent = msg;
        listMessage.classList.remove('hidden', 'text-green-600', 'text-red-500');

        if (type === 'success') {
            listMessage.classList.add('text-green-600');
        } else {
            listMessage.classList.add('text-red-500');
        }
    }

    /**
     * Masque et vide le message contextuel sous le tableau.
     *
     * @returns {void}
     */
    function hideListMessage() {
        if (listMessage) {
            listMessage.classList.add('hidden');
            listMessage.textContent = '';
        }
    }

    /**
     * Bascule sur l'onglet "Ajouter" en mode édition pour la référence cochée.
     * Pré-remplit les selects (en bascule "+ Nouveau..." si la valeur n'existe plus).
     *
     * @returns {void}
     */
    window.editSelectedReference = function() {
        const checkboxes = document.querySelectorAll('.ref-checkbox:checked');
        if (checkboxes.length !== 1) return;

        const activeRefId = checkboxes[0].value;
        const tr = checkboxes[0].closest('tr');
        const type = tr.children[1].textContent.trim();
        const sousType = tr.children[2].textContent.trim() === '-' ? '' : tr.children[2].textContent.trim();
        const nom = tr.children[3].textContent.trim();

        window.switchReferenceTab('add');

        document.getElementById('ref_edit_id').value = activeRefId;

        const tabAdd = document.getElementById('tab-ref-add');
        if (tabAdd) tabAdd.textContent = 'Modifier la référence';

        const descAdd = document.getElementById('ref-view-add-desc');
        if (descAdd) descAdd.textContent = "Modifiez les informations de cette référence. Tous les objets matériels associés seront mis à jour avec le nouveau nom.";

        // Léger délai : laisse l'UI se stabiliser après le switch d'onglet avant de remplir les selects
        setTimeout(() => {
            if (typeSelect) {
                let typeExists = false;
                Array.from(typeSelect.options).forEach(opt => {
                    if (opt.value === type) typeExists = true;
                });

                if (typeExists) {
                    typeSelect.value = type;
                    typeNewInput.classList.add('hidden');
                    typeNewInput.value = '';
                } else {
                    typeSelect.value = NEW_VALUE;
                    typeNewInput.classList.remove('hidden');
                    typeNewInput.value = type;
                }

                if (typeSelect.value !== NEW_VALUE) {
                    populateRefSousTypeSelect(typeSelect.value);
                } else {
                    if (sousTypeSelect) {
                        sousTypeSelect.disabled = false;
                        sousTypeSelect.innerHTML = '';
                        sousTypeSelect.appendChild(createOption('', '(Aucun sous-type)'));
                        sousTypeSelect.appendChild(createOption(NEW_VALUE, '+ Nouveau sous-type...'));
                    }
                }
            }

            if (sousTypeSelect && sousType) {
                let stExists = false;
                Array.from(sousTypeSelect.options).forEach(opt => {
                    if (opt.value === sousType) stExists = true;
                });

                if (stExists) {
                    sousTypeSelect.value = sousType;
                    sousTypeNewInput.classList.add('hidden');
                    sousTypeNewInput.value = '';
                } else {
                    sousTypeSelect.value = NEW_VALUE;
                    sousTypeNewInput.classList.remove('hidden');
                    sousTypeNewInput.value = sousType;
                }
            } else if (sousTypeSelect) {
                sousTypeSelect.value = '';
                if (sousTypeNewInput) {
                    sousTypeNewInput.classList.add('hidden');
                    sousTypeNewInput.value = '';
                }
            }

            const refNom = document.getElementById('ref_nom');
            if (refNom) refNom.value = nom;
        }, 50);
    };
});
