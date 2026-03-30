document.addEventListener('DOMContentLoaded', () => {
    const refModal = document.getElementById('reference-modal');
    const refForm = document.getElementById('form_create_reference');
    const refMessage = document.getElementById('ref_message');

    // Sélecteurs Type/Sous-type avec option "+ Nouveau..."
    const typeSelect = document.getElementById('ref_type_select');
    const typeNewInput = document.getElementById('ref_type_new');
    const sousTypeSelect = document.getElementById('ref_sous_type_select');
    const sousTypeNewInput = document.getElementById('ref_sous_type_new');

    const NEW_VALUE = '__new__';

    /**
     * Peuple le select Type depuis referenceTree (global, chargé par dynamicSelects.js)
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

        // Reset sous-type
        resetSousTypeSelect();
        typeNewInput.classList.add('hidden');
        typeNewInput.value = '';
    }

    /**
     * Peuple le select Sous-type en cascade depuis le type sélectionné
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
            // Pas de sous-types pour ce type
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

    function resetSousTypeSelect() {
        if (!sousTypeSelect) return;
        sousTypeSelect.innerHTML = '';
        sousTypeSelect.appendChild(createOption('', 'Sélectionner un sous-type'));
        sousTypeSelect.disabled = true;
        sousTypeNewInput.classList.add('hidden');
        sousTypeNewInput.value = '';
    }

    function createOption(value, text) {
        const opt = document.createElement('option');
        opt.value = value;
        opt.textContent = text;
        return opt;
    }

    // --- Listeners sur les selects ---
    if (typeSelect) {
        typeSelect.addEventListener('change', () => {
            const val = typeSelect.value;
            if (val === NEW_VALUE) {
                typeNewInput.classList.remove('hidden');
                typeNewInput.focus();
                // Quand on crée un nouveau type, le sous-type est libre aussi
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
     * Retourne la valeur effective d'un champ select+input new
     */
    function getEffectiveValue(select, newInput) {
        if (select.value === NEW_VALUE) {
            return newInput.value.trim();
        }
        return select.value;
    }

    // Make reset function available globally
    window.resetReferenceForm = function() {
        if (refForm) refForm.reset();

        populateRefTypeSelect();

        const nomInput = document.getElementById('ref_nom');
        if (nomInput) nomInput.value = '';

        if (refMessage) {
            refMessage.classList.add('hidden');
            refMessage.textContent = '';
        }
    };

    // Make toggle function available globally
    window.toggleReferenceModal = function(show) {
        if (!refModal) return;

        if (show) {
            refModal.classList.remove('hidden');
            refModal.style.display = 'flex';
            document.body.style.overflow = 'hidden';

            // Peupler les selects avec les données à jour
            populateRefTypeSelect();

            // Clear previous messages
            if (refMessage) {
                refMessage.classList.add('hidden');
                refMessage.className = 'text-sm text-center mt-2 hidden font-medium';
                refMessage.textContent = '';
            }

            // Focus first select
            setTimeout(() => {
                if (typeSelect) typeSelect.focus();
            }, 100);

        } else {
            refModal.classList.add('hidden');
            refModal.style.display = 'none';
            document.body.style.overflow = '';
            if (window.resetReferenceForm) window.resetReferenceForm();
        }
    };

    // Close on escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && refModal && !refModal.classList.contains('hidden')) {
            window.toggleReferenceModal(false);
        }
    });

    // Handle form submission
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
                // Disable button and show loading state
                const originalText = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="opacity-70">Enregistrement...</span>';
                submitBtn.classList.add('opacity-75', 'cursor-not-allowed');

                const response = await fetch('php/addReference.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        type: typeValue,
                        sous_type: sousTypeValue,
                        nom: nomValue
                    })
                });

                const data = await response.json();

                if (data.success) {
                    showMessage(data.message || 'Référence ajoutée avec succès !', 'success');

                    // Reset form
                    refForm.reset();

                    // Force refresh of selects by pulling the updated tree
                    if (window.loadReferenceTree) {
                        await window.loadReferenceTree();
                    }

                    // Re-peupler les selects du modal avec l'arbre mis à jour
                    populateRefTypeSelect();

                    // Close modal after short delay
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
                // Re-enable button
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Enregistrer';
                submitBtn.classList.remove('opacity-75', 'cursor-not-allowed');
            }
        });
    }

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

    // --- LOGIQUE ONGLETS & LISTE ---
    const viewAdd = document.getElementById('ref-view-add');
    const viewList = document.getElementById('ref-view-list');
    const tabAdd = document.getElementById('tab-ref-add');
    const tabList = document.getElementById('tab-ref-list');
    const tableBody = document.getElementById('ref-table-body');
    const checkAll = document.getElementById('ref-check-all');
    const btnDelete = document.getElementById('btn-delete-refs');
    const selectedCountSpan = document.getElementById('ref-selected-count');
    const listMessage = document.getElementById('ref-list-message');

    window.switchReferenceTab = function(tabName) {
        if (tabName === 'add') {
            viewAdd.classList.remove('hidden');
            viewList.classList.add('hidden');

            tabAdd.classList.add('border-b-2', 'border-custom-brandLight', 'text-custom-brandLight');
            tabAdd.classList.remove('text-gray-500', 'hover:text-gray-800');

            tabList.classList.remove('border-b-2', 'border-custom-brandLight', 'text-custom-brandLight');
            tabList.classList.add('text-gray-500', 'hover:text-gray-800');
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

    function escapeHtml(unsafe) {
        return (unsafe || '').toString()
             .replace(/&/g, "&amp;")
             .replace(/</g, "&lt;")
             .replace(/>/g, "&gt;")
             .replace(/"/g, "&quot;")
             .replace(/'/g, "&#039;");
    }

    window.toggleAllReferences = function(source) {
        const checkboxes = document.querySelectorAll('.ref-checkbox');
        checkboxes.forEach(cb => cb.checked = source.checked);
        updateDeleteButtonState();
    };

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
    };

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

    function hideListMessage() {
        if (listMessage) {
            listMessage.classList.add('hidden');
            listMessage.textContent = '';
        }
    }
});
