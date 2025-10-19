<div class="modal fade" id="analyzeHistoryModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-gradient-danger">
                <h5 class="modal-title text-white">
                    <i class="fas fa-history me-2"></i>
                    AI Historical Pattern Detection
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="analyzeHistoryForm">
                    <div class="mb-3">
                        <label class="form-label">Select Device <span class="text-danger">*</span></label>
                        <select class="form-select" name="device_id" id="historyDeviceSelect" required>
                            <option value="">Loading devices...</option>
                        </select>
                    </div>

                    <div class="alert alert-info d-flex align-items-center mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        <small>AI will analyze the device's diagnostic history to identify recurring issues, degradation patterns, and root causes.</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" onclick="analyzeHistory()">
                    <i class="fas fa-chart-area me-1"></i> Analyze with AI
                </button>
            </div>
        </div>
    </div>
</div>

<script>
$('#analyzeHistoryModal').on('show.bs.modal', function() {
    loadDevicesForHistory();
});

function loadDevicesForHistory() {
    $.ajax({
        url: '{{ route("acs.devices") }}',
        method: 'GET',
        success: function(response) {
            const select = $('#historyDeviceSelect');
            select.empty().append('<option value="">Select a device...</option>');
            
            if (response && Array.isArray(response)) {
                response.forEach(device => {
                    select.append(`<option value="${device.id}">${device.manufacturer} ${device.model} - ${device.serial_number}</option>`);
                });
            }
        },
        error: function() {
            $('#historyDeviceSelect').html('<option value="">Error loading devices</option>');
        }
    });
}

function analyzeHistory() {
    const deviceId = $('#historyDeviceSelect').val();
    
    if (!deviceId) {
        Swal.fire({
            icon: 'warning',
            title: 'Selection Required',
            text: 'Please select a device'
        });
        return;
    }
    
    $('#analyzeHistoryModal').modal('hide');
    showLoading('AI is analyzing diagnostic history patterns...');
    
    $.ajax({
        url: `/acs/devices/${deviceId}/ai-analyze-diagnostics`,
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            Swal.close();
            
            const patterns = response.patterns || [];
            const rootCause = response.root_cause;
            const recommendations = response.recommendations || [];
            const trend = response.trend || 'stable';
            const confidence = response.confidence || 0;
            
            const trendConfig = {
                'improving': { color: 'success', icon: 'arrow-up', text: 'Improving' },
                'stable': { color: 'info', icon: 'minus', text: 'Stable' },
                'degrading': { color: 'danger', icon: 'arrow-down', text: 'Degrading' }
            };
            
            const trendInfo = trendConfig[trend] || trendConfig['stable'];
            
            let html = `
                <div class="row mb-3">
                    <div class="col-6">
                        <div class="card bg-light">
                            <div class="card-body p-3 text-center">
                                <h6 class="mb-1">Overall Trend</h6>
                                <span class="badge bg-${trendInfo.color} badge-lg">
                                    <i class="fas fa-${trendInfo.icon} me-1"></i>${trendInfo.text}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="card bg-light">
                            <div class="card-body p-3 text-center">
                                <h6 class="mb-1">AI Confidence</h6>
                                <span class="badge bg-primary badge-lg">${confidence}%</span>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            if (rootCause) {
                html += `<div class="alert alert-warning mb-3">
                    <h6 class="mb-2"><i class="fas fa-search me-2"></i>Root Cause Hypothesis</h6>
                    <p class="mb-0">${rootCause}</p>
                </div>`;
            }
            
            if (patterns.length > 0) {
                html += `<div class="mb-3">
                    <h6 class="mb-2"><i class="fas fa-chart-line me-2"></i>Detected Patterns:</h6>`;
                
                patterns.forEach(pattern => {
                    const typeColor = {
                        'degradation': 'danger',
                        'intermittent': 'warning',
                        'recurring': 'info'
                    }[pattern.type] || 'secondary';
                    
                    html += `
                        <div class="card mb-2">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <span class="badge bg-${typeColor}">${pattern.type}</span>
                                    ${pattern.frequency ? `<small class="text-muted">${pattern.frequency}</small>` : ''}
                                </div>
                                <p class="text-sm mb-2">${pattern.description}</p>
                                ${pattern.affected_tests && pattern.affected_tests.length > 0 ? 
                                    `<small class="text-muted">
                                        <i class="fas fa-clipboard-list me-1"></i>
                                        Affects: ${pattern.affected_tests.join(', ')}
                                    </small>` : ''}
                            </div>
                        </div>
                    `;
                });
                
                html += `</div>`;
            } else {
                html += `<div class="alert alert-info mb-3">
                    <i class="fas fa-check-circle me-2"></i>
                    No significant patterns detected in diagnostic history
                </div>`;
            }
            
            if (recommendations.length > 0) {
                html += `<div class="mb-3">
                    <h6 class="mb-2"><i class="fas fa-shield-alt me-2"></i>Preventive Recommendations:</h6>`;
                
                recommendations.forEach((rec, index) => {
                    const priorityColor = {
                        'high': 'danger',
                        'medium': 'warning',
                        'low': 'info'
                    }[rec.priority] || 'secondary';
                    
                    html += `
                        <div class="card mb-2">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="mb-0">Recommendation ${index + 1}</h6>
                                    <span class="badge bg-${priorityColor}">${rec.priority}</span>
                                </div>
                                <p class="text-sm mb-1"><strong>${rec.action}</strong></p>
                                <p class="text-sm mb-0 text-muted">${rec.rationale}</p>
                            </div>
                        </div>
                    `;
                });
                
                html += `</div>`;
            }
            
            showResults('AI Historical Pattern Detection Results', html);
        },
        error: function(xhr) {
            Swal.close();
            Swal.fire({
                icon: 'error',
                title: 'Analysis Error',
                text: xhr.responseJSON?.error || 'Failed to analyze diagnostic history'
            });
        }
    });
}
</script>
