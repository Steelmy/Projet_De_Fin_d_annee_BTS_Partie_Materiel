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
});
