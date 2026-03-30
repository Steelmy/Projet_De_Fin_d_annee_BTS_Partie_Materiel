document.addEventListener('DOMContentLoaded', () => {
    const refModal = document.getElementById('reference-modal');
    const refForm = document.getElementById('form_create_reference');
    const refMessage = document.getElementById('ref_message');

    // Make reset function available globally
    window.resetReferenceForm = function() {
        if (refForm) refForm.reset();
        
        // Explicitly clear inputs
        const typeInput = document.getElementById('ref_type');
        const sousTypeInput = document.getElementById('ref_sous_type');
        const nomInput = document.getElementById('ref_nom');
        if (typeInput) typeInput.value = '';
        if (sousTypeInput) sousTypeInput.value = '';
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
            
            // Optionally, we could pre-fill the form with whatever was typed in the Ajout form
            const typeInputAjout = document.getElementById('type_materiel_ajout');
            const nomInputAjout = document.getElementById('nom_materiel_ajout');
            const refTypeInput = document.getElementById('ref_type');
            const refNomInput = document.getElementById('ref_nom');
            
            if (refTypeInput && typeInputAjout && typeInputAjout.value) {
                refTypeInput.value = typeInputAjout.value;
            }
            if (refNomInput && nomInputAjout && nomInputAjout.value) {
                refNomInput.value = nomInputAjout.value;
            }

            // Clear previous messages
            if (refMessage) {
                refMessage.classList.add('hidden');
                refMessage.className = 'text-sm text-center mt-2 hidden font-medium';
                refMessage.textContent = '';
            }

            // Focus first input
            setTimeout(() => {
                if (refTypeInput) refTypeInput.focus();
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
            const typeValue = document.getElementById('ref_type').value.trim();
            const sousTypeValue = document.getElementById('ref_sous_type').value.trim();
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
                        window.loadReferenceTree();
                    }
                    
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

    // --- NOUVELLE LOGIQUE : ONGLETS & LISTE ---
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
            <tr class="hover:bg-gray-50 transition-colors group">
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
            btnDelete.style.backgroundColor = '#ef4444'; // Force red color (bg-red-500)
        } else {
            btnDelete.disabled = true;
            btnDelete.classList.remove('bg-red-500', 'hover:bg-red-600', 'cursor-pointer');
            btnDelete.classList.add('cursor-not-allowed');
            btnDelete.style.backgroundColor = '#6b7280'; // Force gray color
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
                
                // Reload list to update checking state if some were deleted
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
