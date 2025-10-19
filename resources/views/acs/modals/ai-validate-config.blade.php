<div class="modal fade" id="validateConfigModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-gradient-success">
                <h5 class="modal-title text-white">
                    <i class="fas fa-check-circle me-2"></i>
                    AI Configuration Validation
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="validateConfigForm">
                    <div class="mb-3">
                        <label class="form-label">Select Configuration Profile <span class="text-danger">*</span></label>
                        <select class="form-select" name="profile_id" id="validateProfileSelect" required>
                            <option value="">Loading profiles...</option>
                        </select>
                    </div>

                    <div class="alert alert-info d-flex align-items-center mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        <small>AI will check for TR-181 compliance, security issues, performance problems, and missing mandatory parameters.</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" onclick="validateConfig()">
                    <i class="fas fa-shield-alt me-1"></i> Validate with AI
                </button>
            </div>
        </div>
    </div>
</div>

<script>
$('#validateConfigModal').on('show.bs.modal', function() {
    loadProfiles('validateProfileSelect');
});

function loadProfiles(selectId) {
    $.ajax({
        url: '{{ route("acs.profiles") }}',
        method: 'GET',
        success: function(response) {
            const select = $('#' + selectId);
            select.empty().append('<option value="">Select a profile...</option>');
            
            if (response && Array.isArray(response)) {
                response.forEach(profile => {
                    select.append(`<option value="${profile.id}">${profile.name} (${profile.device_type})</option>`);
                });
            }
        },
        error: function() {
            $('#' + selectId).html('<option value="">Error loading profiles</option>');
        }
    });
}

function validateConfig() {
    const profileId = $('#validateProfileSelect').val();
    
    if (!profileId) {
        Swal.fire({
            icon: 'warning',
            title: 'Selection Required',
            text: 'Please select a configuration profile'
        });
        return;
    }
    
    $('#validateConfigModal').modal('hide');
    showLoading('AI is validating your configuration...');
    
    $.ajax({
        url: `/acs/profiles/${profileId}/ai-validate`,
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            Swal.close();
            
            const isValid = response.is_valid;
            const issues = response.issues || [];
            const recommendations = response.recommendations || [];
            
            let html = `
                <div class="alert alert-${isValid ? 'success' : 'warning'} mb-3">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-${isValid ? 'check-circle' : 'exclamation-triangle'} fa-2x me-3"></i>
                        <div>
                            <strong>${isValid ? 'Configuration Valid' : 'Issues Found'}</strong>
                            <p class="mb-0 text-sm">${isValid ? 'No issues detected' : issues.length + ' issue(s) detected'}</p>
                        </div>
                    </div>
                </div>
            `;
            
            if (issues.length > 0) {
                html += `<div class="mb-3">
                    <h6 class="mb-2"><i class="fas fa-bug me-2"></i>Issues Detected:</h6>
                    <div class="list-group">`;
                
                issues.forEach(issue => {
                    const severityColor = {
                        'critical': 'danger',
                        'warning': 'warning',
                        'info': 'info'
                    }[issue.severity] || 'secondary';
                    
                    html += `
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <span class="badge bg-${severityColor} me-2">${issue.severity}</span>
                                    <strong>${issue.parameter || 'General'}</strong>
                                    <p class="mb-0 mt-1 text-sm">${issue.message}</p>
                                </div>
                            </div>
                        </div>
                    `;
                });
                
                html += `</div></div>`;
            }
            
            if (recommendations.length > 0) {
                html += `<div class="mb-3">
                    <h6 class="mb-2"><i class="fas fa-lightbulb me-2"></i>Recommendations:</h6>
                    <ul class="list-unstyled">`;
                
                recommendations.forEach(rec => {
                    html += `<li class="mb-2"><i class="fas fa-arrow-right text-primary me-2"></i>${rec}</li>`;
                });
                
                html += `</ul></div>`;
            }
            
            showResults('AI Configuration Validation Results', html);
        },
        error: function(xhr) {
            Swal.close();
            Swal.fire({
                icon: 'error',
                title: 'Validation Error',
                text: xhr.responseJSON?.error || 'Failed to validate configuration'
            });
        }
    });
}
</script>
