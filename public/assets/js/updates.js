document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    function showNotification(message, type = 'success') {
        const alertClass = type === 'success' ? 'alert-success' : type === 'error' ? 'alert-danger' : 'alert-warning';
        const iconClass = type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle';
        
        const alertHtml = `
            <div class="alert ${alertClass} alert-dismissible fade show position-fixed" 
                 role="alert" 
                 style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
                <i class="fas ${iconClass} me-2"></i>
                <strong>${message}</strong>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', alertHtml);
        
        setTimeout(() => {
            const alert = document.querySelector('.alert');
            if (alert) {
                alert.classList.remove('show');
                setTimeout(() => alert.remove(), 150);
            }
        }, 5000);
    }

    function confirmAction(message, callback) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Conferma Azione',
                text: message,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sì, Procedi',
                cancelButtonText: 'Annulla',
                customClass: {
                    confirmButton: 'btn btn-success',
                    cancelButton: 'btn btn-secondary'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    callback();
                }
            });
        } else {
            if (confirm(message)) {
                callback();
            }
        }
    }

    function makeRequest(url, method = 'POST', data = {}) {
        return fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: method !== 'GET' ? JSON.stringify(data) : undefined
        }).then(response => {
            if (!response.ok) {
                return response.json().then(err => Promise.reject(err));
            }
            return response.json();
        });
    }

    const checkUpdatesButtons = document.querySelectorAll('#btnCheckUpdates, #btnCheckUpdatesEmpty');
    checkUpdatesButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const btnIcon = this.querySelector('i');
            const originalIcon = btnIcon.className;
            btnIcon.className = 'fas fa-spinner fa-spin me-1';
            this.disabled = true;

            makeRequest('/acs/updates/check', 'POST', { auto_stage: true })
                .then(response => {
                    showNotification(response.message, 'success');
                    setTimeout(() => window.location.reload(), 1500);
                })
                .catch(error => {
                    showNotification(error.message || 'Errore durante la verifica degli aggiornamenti', 'error');
                })
                .finally(() => {
                    btnIcon.className = originalIcon;
                    this.disabled = false;
                });
        });
    });

    const approveButtons = document.querySelectorAll('.btn-approve, .btn-approve-detail');
    approveButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const updateId = this.getAttribute('data-update-id');
            
            confirmAction('Sei sicuro di voler approvare questo aggiornamento?', () => {
                const btnIcon = this.querySelector('i');
                const originalIcon = btnIcon.className;
                btnIcon.className = 'fas fa-spinner fa-spin';
                this.disabled = true;

                makeRequest(`/acs/updates/${updateId}/approve`, 'POST')
                    .then(response => {
                        showNotification(response.message, 'success');
                        setTimeout(() => window.location.reload(), 1500);
                    })
                    .catch(error => {
                        showNotification(error.message || 'Errore durante l\'approvazione', 'error');
                        btnIcon.className = originalIcon;
                        this.disabled = false;
                    });
            });
        });
    });

    const rejectButtons = document.querySelectorAll('.btn-reject, .btn-reject-detail');
    rejectButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const updateId = this.getAttribute('data-update-id');
            
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Rigetta Aggiornamento',
                    text: 'Sei sicuro di voler rigettare questo aggiornamento?',
                    input: 'textarea',
                    inputPlaceholder: 'Motivo del rigetto (opzionale)',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sì, Rigetta',
                    cancelButtonText: 'Annulla',
                    customClass: {
                        confirmButton: 'btn btn-danger',
                        cancelButton: 'btn btn-secondary'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        const btnIcon = this.querySelector('i');
                        const originalIcon = btnIcon.className;
                        btnIcon.className = 'fas fa-spinner fa-spin';
                        this.disabled = true;

                        makeRequest(`/acs/updates/${updateId}/reject`, 'POST', { reason: result.value })
                            .then(response => {
                                showNotification(response.message, 'success');
                                setTimeout(() => window.location.reload(), 1500);
                            })
                            .catch(error => {
                                showNotification(error.message || 'Errore durante il rigetto', 'error');
                                btnIcon.className = originalIcon;
                                this.disabled = false;
                            });
                    }
                });
            } else {
                const reason = prompt('Motivo del rigetto (opzionale):');
                if (reason !== null) {
                    makeRequest(`/acs/updates/${updateId}/reject`, 'POST', { reason })
                        .then(response => {
                            showNotification(response.message, 'success');
                            setTimeout(() => window.location.reload(), 1500);
                        })
                        .catch(error => {
                            showNotification(error.message || 'Errore durante il rigetto', 'error');
                        });
                }
            }
        });
    });

    const scheduleForm = document.getElementById('scheduleForm');
    if (scheduleForm) {
        scheduleForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const updateId = window.updateId;
            const scheduledAt = document.getElementById('scheduled_at').value;
            
            makeRequest(`/acs/updates/${updateId}/schedule`, 'POST', { scheduled_at: scheduledAt })
                .then(response => {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('scheduleModal'));
                    modal.hide();
                    showNotification(response.message, 'success');
                    setTimeout(() => window.location.reload(), 1500);
                })
                .catch(error => {
                    showNotification(error.message || 'Errore durante la pianificazione', 'error');
                });
        });
    }

    const applyButtons = document.querySelectorAll('.btn-apply, .btn-apply-detail');
    applyButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const updateId = this.getAttribute('data-update-id');
            
            confirmAction(
                'ATTENZIONE: Questa operazione applicherà l\'aggiornamento al sistema. Il sistema potrebbe essere riavviato. Procedere?', 
                () => {
                    const progressModal = new bootstrap.Modal(document.getElementById('progressModal'));
                    progressModal.show();
                    
                    const progressBar = document.getElementById('deployProgress');
                    const progressMessage = document.getElementById('progressMessage');
                    
                    progressBar.style.width = '10%';
                    progressBar.textContent = '10%';
                    progressMessage.textContent = 'Validazione package in corso...';
                    
                    setTimeout(() => {
                        progressBar.style.width = '30%';
                        progressBar.textContent = '30%';
                        progressMessage.textContent = 'Creazione backup sistema...';
                    }, 1000);
                    
                    setTimeout(() => {
                        progressBar.style.width = '50%';
                        progressBar.textContent = '50%';
                        progressMessage.textContent = 'Copia file aggiornati...';
                    }, 2000);
                    
                    setTimeout(() => {
                        progressBar.style.width = '70%';
                        progressBar.textContent = '70%';
                        progressMessage.textContent = 'Esecuzione migrations database...';
                    }, 3000);
                    
                    setTimeout(() => {
                        progressBar.style.width = '90%';
                        progressBar.textContent = '90%';
                        progressMessage.textContent = 'Health checks e validazione...';
                    }, 4000);
                    
                    makeRequest(`/acs/updates/${updateId}/apply`, 'POST')
                        .then(response => {
                            progressBar.style.width = '100%';
                            progressBar.textContent = '100%';
                            progressBar.classList.remove('progress-bar-animated');
                            progressBar.classList.add('bg-success');
                            progressMessage.textContent = 'Deployment completato con successo!';
                            
                            setTimeout(() => {
                                progressModal.hide();
                                showNotification(response.message, 'success');
                                setTimeout(() => window.location.reload(), 2000);
                            }, 2000);
                        })
                        .catch(error => {
                            progressBar.classList.remove('progress-bar-animated');
                            progressBar.classList.add('bg-danger');
                            progressMessage.textContent = 'Errore durante il deployment: ' + (error.message || 'Errore sconosciuto');
                            progressMessage.classList.add('text-danger');
                            
                            setTimeout(() => {
                                progressModal.hide();
                                showNotification(error.message || 'Errore durante il deployment', 'error');
                            }, 3000);
                        });
                }
            );
        });
    });

    const validateButtons = document.querySelectorAll('.btn-validate');
    validateButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const updateId = this.getAttribute('data-update-id');
            const btnIcon = this.querySelector('i');
            const originalIcon = btnIcon.className;
            btnIcon.className = 'fas fa-spinner fa-spin me-2';
            this.disabled = true;

            makeRequest(`/acs/updates/${updateId}/validate`, 'GET')
                .then(response => {
                    if (response.success) {
                        showNotification(response.message, 'success');
                        setTimeout(() => window.location.reload(), 1500);
                    } else {
                        showNotification(response.message, 'warning');
                    }
                })
                .catch(error => {
                    showNotification(error.message || 'Errore durante la validazione', 'error');
                })
                .finally(() => {
                    btnIcon.className = originalIcon;
                    this.disabled = false;
                });
        });
    });

    const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltips.forEach(el => new bootstrap.Tooltip(el));
});
