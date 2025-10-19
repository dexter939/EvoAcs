<div class="modal fade" id="analyzeDiagnosticModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-gradient-info">
                <h5 class="modal-title text-white">
                    <i class="fas fa-stethoscope me-2"></i>
                    AI Diagnostic Analysis
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="analyzeDiagnosticForm">
                    <div class="mb-3">
                        <label class="form-label">Select Diagnostic Test <span class="text-danger">*</span></label>
                        <select class="form-select" name="diagnostic_id" id="diagnosticSelect" required>
                            <option value="">Loading diagnostics...</option>
                        </select>
                    </div>

                    <div class="alert alert-info d-flex align-items-center mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        <small>AI will analyze the test results and provide root cause analysis, troubleshooting steps, and recommended solutions.</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-info" onclick="analyzeDiagnostic()">
                    <i class="fas fa-microscope me-1"></i> Analyze with AI
                </button>
            </div>
        </div>
    </div>
</div>

<script>
$('#analyzeDiagnosticModal').on('show.bs.modal', function() {
    loadDiagnostics();
});

function loadDiagnostics() {
    $.ajax({
        url: '{{ route("diagnostics.index") }}',
        method: 'GET',
        success: function(response) {
            const select = $('#diagnosticSelect');
            select.empty().append('<option value="">Select a diagnostic test...</option>');
            
            if (response && Array.isArray(response)) {
                response.forEach(diag => {
                    const deviceInfo = diag.cpe_device ? `${diag.cpe_device.manufacturer} ${diag.cpe_device.model}` : 'Unknown Device';
                    select.append(`<option value="${diag.id}">${diag.diagnostic_type} - ${deviceInfo} (${diag.status})</option>`);
                });
            }
        },
        error: function() {
            $('#diagnosticSelect').html('<option value="">Error loading diagnostics</option>');
        }
    });
}

function analyzeDiagnostic() {
    const diagnosticId = $('#diagnosticSelect').val();
    
    if (!diagnosticId) {
        Swal.fire({
            icon: 'warning',
            title: 'Selection Required',
            text: 'Please select a diagnostic test'
        });
        return;
    }
    
    $('#analyzeDiagnosticModal').modal('hide');
    showLoading('AI is analyzing diagnostic results...');
    
    $.ajax({
        url: `/acs/diagnostics/${diagnosticId}/ai-analyze`,
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            Swal.close();
            
            const analysis = response.analysis || 'No analysis available';
            const issues = response.issues || [];
            const solutions = response.solutions || [];
            const severity = response.severity || 'info';
            const rootCause = response.root_cause;
            
            const severityConfig = {
                'critical': { color: 'danger', icon: 'exclamation-triangle' },
                'high': { color: 'warning', icon: 'exclamation-circle' },
                'medium': { color: 'info', icon: 'info-circle' },
                'low': { color: 'success', icon: 'check-circle' },
                'info': { color: 'secondary', icon: 'info' }
            };
            
            const config = severityConfig[severity] || severityConfig['info'];
            
            let html = `
                <div class="alert alert-${config.color} mb-3">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-${config.icon} fa-2x me-3"></i>
                        <div>
                            <strong class="text-uppercase">${severity} Severity</strong>
                            <p class="mb-0 text-sm mt-1">${analysis}</p>
                        </div>
                    </div>
                </div>
            `;
            
            if (rootCause) {
                html += `<div class="card mb-3 bg-light">
                    <div class="card-body">
                        <h6 class="mb-2"><i class="fas fa-search me-2"></i>Root Cause Analysis</h6>
                        <p class="mb-0 text-sm">${rootCause}</p>
                    </div>
                </div>`;
            }
            
            if (issues.length > 0) {
                html += `<div class="mb-3">
                    <h6 class="mb-2"><i class="fas fa-bug me-2"></i>Identified Issues:</h6>
                    <div class="list-group">`;
                
                issues.forEach(issue => {
                    html += `
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <span class="badge bg-secondary me-2">${issue.category || 'General'}</span>
                                    <strong>${issue.metric || 'Issue'}</strong>
                                    <p class="mb-0 mt-1 text-sm">${issue.description}</p>
                                    ${issue.threshold_exceeded ? `<small class="text-muted"><i class="fas fa-chart-line me-1"></i>${issue.threshold_exceeded}</small>` : ''}
                                </div>
                            </div>
                        </div>
                    `;
                });
                
                html += `</div></div>`;
            }
            
            if (solutions.length > 0) {
                html += `<div class="mb-3">
                    <h6 class="mb-2"><i class="fas fa-tools me-2"></i>Recommended Solutions:</h6>`;
                
                solutions.forEach((solution, index) => {
                    const priorityColor = {
                        'high': 'danger',
                        'medium': 'warning',
                        'low': 'info'
                    }[solution.priority] || 'secondary';
                    
                    html += `
                        <div class="card mb-2">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="mb-0">Solution ${index + 1}</h6>
                                    <span class="badge bg-${priorityColor}">${solution.priority} priority</span>
                                </div>
                                <p class="text-sm mb-2"><strong>${solution.action}</strong></p>
                                ${solution.technical_detail ? `<p class="text-sm mb-1 text-muted"><i class="fas fa-code me-1"></i>${solution.technical_detail}</p>` : ''}
                                ${solution.expected_result ? `<p class="text-sm mb-0 text-success"><i class="fas fa-check me-1"></i>Expected: ${solution.expected_result}</p>` : ''}
                            </div>
                        </div>
                    `;
                });
                
                html += `</div>`;
            }
            
            showResults('AI Diagnostic Analysis Results', html);
        },
        error: function(xhr) {
            Swal.close();
            Swal.fire({
                icon: 'error',
                title: 'Analysis Error',
                text: xhr.responseJSON?.error || 'Failed to analyze diagnostic'
            });
        }
    });
}
</script>
