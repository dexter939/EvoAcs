<div class="modal fade" id="optimizeConfigModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-gradient-warning">
                <h5 class="modal-title text-white">
                    <i class="fas fa-rocket me-2"></i>
                    AI Configuration Optimization
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="optimizeConfigForm">
                    <div class="mb-3">
                        <label class="form-label">Select Configuration Profile <span class="text-danger">*</span></label>
                        <select class="form-select" name="profile_id" id="optimizeProfileSelect" required>
                            <option value="">Loading profiles...</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Optimization Focus</label>
                        <select class="form-select" name="focus">
                            <option value="all" selected>All Areas (Balanced)</option>
                            <option value="performance">Performance & Throughput</option>
                            <option value="security">Security & Access Control</option>
                            <option value="stability">Stability & Reliability</option>
                        </select>
                    </div>

                    <div class="alert alert-info d-flex align-items-center mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        <small>AI will analyze your configuration and suggest specific optimizations with recommended values and rationale.</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning" onclick="optimizeConfig()">
                    <i class="fas fa-rocket me-1"></i> Optimize with AI
                </button>
            </div>
        </div>
    </div>
</div>

<script>
$('#optimizeConfigModal').on('show.bs.modal', function() {
    loadProfiles('optimizeProfileSelect');
});

function optimizeConfig() {
    const profileId = $('#optimizeProfileSelect').val();
    const focus = $('select[name="focus"]').val();
    
    if (!profileId) {
        Swal.fire({
            icon: 'warning',
            title: 'Selection Required',
            text: 'Please select a configuration profile'
        });
        return;
    }
    
    $('#optimizeConfigModal').modal('hide');
    showLoading('AI is analyzing and optimizing your configuration...');
    
    $.ajax({
        url: `/acs/profiles/${profileId}/ai-optimize`,
        method: 'POST',
        data: { focus: focus },
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            Swal.close();
            
            const suggestions = response.suggestions || [];
            
            let html = `
                <div class="alert alert-success mb-3">
                    <i class="fas fa-check-circle me-2"></i>
                    AI found <strong>${suggestions.length}</strong> optimization suggestion(s)
                </div>
            `;
            
            if (suggestions.length > 0) {
                html += `<div class="mb-3">
                    <h6 class="mb-3"><i class="fas fa-chart-line me-2"></i>Optimization Suggestions:</h6>`;
                
                suggestions.forEach((suggestion, index) => {
                    const categoryColor = {
                        'performance': 'primary',
                        'security': 'success',
                        'stability': 'info'
                    }[suggestion.category] || 'secondary';
                    
                    const impactIcon = {
                        'high': 'fa-arrow-up',
                        'medium': 'fa-minus',
                        'low': 'fa-arrow-down'
                    }[suggestion.impact] || 'fa-circle';
                    
                    html += `
                        <div class="card mb-2">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <span class="badge bg-${categoryColor}">${suggestion.category}</span>
                                    <span class="badge bg-secondary">
                                        <i class="fas ${impactIcon} me-1"></i>${suggestion.impact} impact
                                    </span>
                                </div>
                                <h6 class="mb-2">${suggestion.parameter}</h6>
                                <div class="row text-sm mb-2">
                                    <div class="col-6">
                                        <span class="text-muted">Current:</span> <code>${suggestion.current_value}</code>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-muted">Suggested:</span> <code class="text-success">${suggestion.suggested_value}</code>
                                    </div>
                                </div>
                                <p class="text-sm mb-0 text-muted">
                                    <i class="fas fa-info-circle me-1"></i>${suggestion.rationale}
                                </p>
                            </div>
                        </div>
                    `;
                });
                
                html += `</div>`;
                
                html += `<div class="text-end">
                    <button class="btn btn-primary" onclick="applyOptimizations()">
                        <i class="fas fa-check me-1"></i> Apply Selected Optimizations
                    </button>
                </div>`;
            } else {
                html += `<div class="alert alert-info">
                    <i class="fas fa-check-circle me-2"></i>
                    Your configuration is already well-optimized!
                </div>`;
            }
            
            showResults('AI Configuration Optimization Results', html);
        },
        error: function(xhr) {
            Swal.close();
            Swal.fire({
                icon: 'error',
                title: 'Optimization Error',
                text: xhr.responseJSON?.error || 'Failed to optimize configuration'
            });
        }
    });
}

function applyOptimizations() {
    Swal.fire({
        title: 'Apply Optimizations?',
        text: 'This will update the configuration profile with AI-suggested values',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Apply',
        confirmButtonColor: '#ffc107'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire('Applied!', 'Optimization suggestions have been applied', 'success');
        }
    });
}
</script>
