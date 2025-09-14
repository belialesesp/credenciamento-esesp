/**
 * File: /scripts/form-handler.js
 * 
 * This file handles form submission for cadastros.php
 * Updated to handle redirects after successful registration
 */

document.addEventListener('DOMContentLoaded', function () {
    // Get all registration forms
    const forms = document.querySelectorAll('.needs-validation');

    forms.forEach(form => {
        form.addEventListener('submit', async function (event) {
            event.preventDefault();
            event.stopPropagation();

            // Check form validity
            if (!form.checkValidity()) {
                form.classList.add('was-validated');
                return;
            }

            // Show loading overlay
            showLoading(true);

            try {
                // Create FormData object
                const formData = new FormData(form);

                // Add form type identifier
                const formId = form.id;
                formData.append('form_type', formId);

                // Determine the correct path based on form action or default
                let actionUrl = '../process/process_registration.php';
                if (form.action && form.action.includes('process_registration.php')) {
                    actionUrl = form.action;
                }

                // Send to server
                const response = await fetch(actionUrl, {
                    method: 'POST',
                    body: formData
                });

                // Parse JSON response
                let result;
                try {
                    const responseText = await response.text();
                    console.log('Response received:', responseText); // Debug log
                    result = JSON.parse(responseText);
                } catch (parseError) {
                    console.error('Failed to parse response:', parseError);
                    throw new Error('Invalid response from server');
                }

                if (result.success) {
                    // Show success message
                    Swal.fire({
                        icon: 'success',
                        title: 'Cadastro Realizado!',
                        text: result.message,
                        confirmButtonColor: '#1e4c82',
                        confirmButtonText: 'Continuar',
                        allowOutsideClick: false
                    }).then(() => {
                        // Check for redirect URL in response
                        if (result.redirect_url) {
                            window.location.href = result.redirect_url;
                        } else if (result.redirect) {
                            window.location.href = result.redirect;
                        } else {
                            window.location.href = '../pages/login.php';
                        }
                    });
                } else {
                    // Check if user is already registered
                    if (result.already_registered === true) {
                        Swal.fire({
                            icon: 'info',
                            title: 'Usuário já cadastrado',
                            text: `Olá ${result.user_name || 'usuário'}! Você já está cadastrado no sistema.`,
                            confirmButtonColor: '#1e4c82',
                            confirmButtonText: 'Ir para Login',
                            allowOutsideClick: false
                        }).then(() => {
                            window.location.href = result.redirect_url || '../pages/login.php';
                        });
                    } else {
                        // Show regular error message
                        Swal.fire({
                            icon: 'error',
                            title: 'Erro no Cadastro',
                            text: result.message,
                            confirmButtonColor: '#dc3545'
                        });
                    }
                }
            } catch (error) {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Erro de Conexão',
                    text: 'Não foi possível conectar ao servidor. Tente novamente.',
                    confirmButtonColor: '#dc3545'
                });
            } finally {
                showLoading(false);
            }
        });
        // Setup other handlers
        setupFileValidation();
        applyInputMasks();
        handleSpecialNeeds();

        // Handle role application form if it exists
        handleRoleApplication();
    });

    // Setup other handlers
    setupFileValidation();
    applyInputMasks();
    handleSpecialNeeds();
});

// Show/Hide loading overlay
function showLoading(show) {
    let overlay = document.getElementById('loadingOverlay');

    if (!overlay && show) {
        // Create overlay if it doesn't exist
        overlay = document.createElement('div');
        overlay.id = 'loadingOverlay';
        overlay.className = 'loading-overlay';
        overlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 9999;
            display: flex;
            justify-content: center;
            align-items: center;
        `;
        overlay.innerHTML = `
            <div class="loading-content" style="
                background: white;
                padding: 2rem;
                border-radius: 0.5rem;
                text-align: center;
            ">
                <div class="spinner" style="
                    border: 4px solid #dee2e6;
                    border-top: 4px solid #1e4c82;
                    border-radius: 50%;
                    width: 40px;
                    height: 40px;
                    animation: spin 1s linear infinite;
                    margin: 0 auto 1rem;
                "></div>
                <p>Enviando formulário...</p>
                <p class="upload-progress">Processando documentos...</p>
            </div>
        `;

        // Add spinner animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
        `;
        document.head.appendChild(style);

        document.body.appendChild(overlay);
    }

    if (overlay) {
        overlay.style.display = show ? 'flex' : 'none';
    }
}

// File validation
function setupFileValidation() {
    const fileInputs = document.querySelectorAll('input[type="file"]');

    fileInputs.forEach(input => {
        input.addEventListener('change', function () {
            const file = this.files[0];

            if (file) {
                // Check file type (PDF only)
                if (file.type !== 'application/pdf') {
                    this.value = '';
                    Swal.fire({
                        icon: 'warning',
                        title: 'Formato Inválido',
                        text: 'Por favor, envie apenas arquivos PDF.',
                        confirmButtonColor: '#fca934'
                    });
                    return;
                }

                // Check file size (max 10MB)
                const maxSize = 10 * 1024 * 1024; // 10MB in bytes
                if (file.size > maxSize) {
                    this.value = '';
                    Swal.fire({
                        icon: 'warning',
                        title: 'Arquivo muito grande',
                        text: 'O arquivo deve ter no máximo 10MB.',
                        confirmButtonColor: '#fca934'
                    });
                    return;
                }

                // Show file name as feedback
                const label = this.closest('.file-upload-group')?.querySelector('.file-label');
                if (label) {
                    let indicator = label.querySelector('.file-name-indicator');
                    if (!indicator) {
                        indicator = document.createElement('span');
                        indicator.className = 'file-name-indicator';
                        indicator.style.cssText = `
                            display: block;
                            font-size: 0.875rem;
                            color: #28a745;
                            margin-top: 0.5rem;
                        `;
                        label.appendChild(indicator);
                    }

                    const fileName = file.name.length > 30
                        ? file.name.substring(0, 27) + '...'
                        : file.name;

                    const fileSize = (file.size / 1024).toFixed(1);
                    indicator.innerHTML = `✔ ${fileName} (${fileSize} KB)`;
                }
            }
        });
    });
}

// Apply input masks
function applyInputMasks() {
    // CPF Mask
    document.querySelectorAll('input[name="cpf"]').forEach(input => {
        input.addEventListener('input', function () {
            let value = this.value.replace(/\D/g, '');
            if (value.length > 11) value = value.slice(0, 11);

            if (value.length > 9) {
                value = value.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
            } else if (value.length > 6) {
                value = value.replace(/(\d{3})(\d{3})(\d{3})/, '$1.$2.$3');
            } else if (value.length > 3) {
                value = value.replace(/(\d{3})(\d{3})/, '$1.$2');
            }

            this.value = value;
        });
    });

    // Phone Mask
    document.querySelectorAll('input[name="phone"]').forEach(input => {
        input.addEventListener('input', function () {
            let value = this.value.replace(/\D/g, '');
            if (value.length > 11) value = value.slice(0, 11);

            if (value.length > 6) {
                if (value.length === 11) {
                    value = value.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
                } else {
                    value = value.replace(/(\d{2})(\d{4})(\d{4})/, '($1) $2-$3');
                }
            } else if (value.length > 2) {
                value = value.replace(/(\d{2})(\d+)/, '($1) $2');
            }

            this.value = value;
        });
    });

    // CEP Mask
    document.querySelectorAll('input[name="zipCode"]').forEach(input => {
        input.addEventListener('input', function () {
            let value = this.value.replace(/\D/g, '');
            if (value.length > 8) value = value.slice(0, 8);

            if (value.length > 5) {
                value = value.replace(/(\d{5})(\d{3})/, '$1-$2');
            }

            this.value = value;
        });
    });
}

// Handle special needs radio buttons
function handleSpecialNeeds() {
    document.querySelectorAll('input[name="specialNeeds"], input[name="special_needs"]').forEach(radio => {
        radio.addEventListener('change', function () {
            const detailsContainer = document.getElementById('specialNeedsDetails');
            if (detailsContainer) {
                detailsContainer.style.display = this.value === 'yes' ? 'block' : 'none';
                const input = detailsContainer.querySelector('input');
                if (input) {
                    if (this.value === 'yes') {
                        input.setAttribute('required', '');
                    } else {
                        input.removeAttribute('required');
                        input.value = '';
                    }
                }
            }
        });
    });
}

// Add Education Section dynamically
window.addEducationSection = function () {
    const container = document.getElementById('education-sections');
    if (!container) return;

    const sections = container.querySelectorAll('.clone-section');
    const newSection = sections[0].cloneNode(true);

    // Clear input values
    newSection.querySelectorAll('input').forEach(input => {
        input.value = '';
    });

    // Add remove button if it's not the first section
    if (sections.length > 0) {
        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'remove-section';
        removeBtn.innerHTML = '<i class="fas fa-times"></i> Remover';
        removeBtn.onclick = function () {
            newSection.remove();
        };
        newSection.appendChild(removeBtn);
    }

    container.appendChild(newSection);
};

// Add Discipline Section dynamically
window.addDisciplineSection = function () {
    const container = document.getElementById('disciplines-sections');
    if (!container) return;

    const sections = container.querySelectorAll('.clone-section');
    const newSection = sections[0].cloneNode(true);

    // Clear input values
    newSection.querySelectorAll('input').forEach(input => {
        input.value = '';
    });

    // Add remove button
    if (sections.length > 0) {
        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'remove-section';
        removeBtn.innerHTML = '<i class="fas fa-times"></i> Remover';
        removeBtn.onclick = function () {
            newSection.remove();
        };
        newSection.appendChild(removeBtn);
    }

    container.appendChild(newSection);
};
// Add this function to handle role application form
window.handleRoleApplication = function () {
    const form = document.getElementById('applyRolesForm');
    if (!form) return;

    form.addEventListener('submit', async function (event) {
        event.preventDefault();

        // Show loading overlay
        showLoading(true);

        try {
            const formData = new FormData(form);

            // Send to server
            const response = await fetch('../process/apply_roles.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                // Show success message
                Swal.fire({
                    icon: 'success',
                    title: 'Sucesso!',
                    text: result.message,
                    confirmButtonColor: '#1e4c82',
                    confirmButtonText: 'Continuar'
                }).then(() => {
                    // Redirect to appropriate page
                    if (result.redirect_url) {
                        window.location.href = result.redirect_url;
                    } else {
                        window.location.reload();
                    }
                });
            } else {
                // Show error message
                Swal.fire({
                    icon: 'error',
                    title: 'Erro',
                    text: result.message,
                    confirmButtonColor: '#dc3545'
                });
            }
        } catch (error) {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Erro de Conexão',
                text: 'Não foi possível conectar ao servidor. Tente novamente.',
                confirmButtonColor: '#dc3545'
            });
        } finally {
            showLoading(false);
        }
    });
};
