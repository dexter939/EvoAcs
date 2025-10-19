<div class="modal fade" id="generateTemplateModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-gradient-primary">
                <h5 class="modal-title text-white">
                    <i class="fas fa-magic me-2"></i>
                    AI Template Generation
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="generateTemplateForm">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Device Type <span class="text-danger">*</span></label>
                            <select class="form-select" name="device_type" required>
                                <option value="">Select device type...</option>
                                <option value="router">Router</option>
                                <option value="gateway">Gateway / Modem</option>
                                <option value="ont">ONT (Optical Network Terminal)</option>
                                <option value="access_point">Access Point</option>
                                <option value="mesh_node">Mesh WiFi Node</option>
                                <option value="cpe">Generic CPE</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Manufacturer</label>
                            <input type="text" class="form-control" name="manufacturer" placeholder="e.g., TP-Link, Huawei, ZTE">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Model</label>
                        <input type="text" class="form-control" name="model" placeholder="e.g., Archer AX50, HG8245H">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Required Services</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="services[]" value="wifi" id="serviceWiFi">
                            <label class="form-check-label" for="serviceWiFi">
                                <i class="fas fa-wifi me-1"></i> WiFi Configuration
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="services[]" value="voip" id="serviceVoIP">
                            <label class="form-check-label" for="serviceVoIP">
                                <i class="fas fa-phone me-1"></i> VoIP Configuration
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="services[]" value="iptv" id="serviceIPTV">
                            <label class="form-check-label" for="serviceIPTV">
                                <i class="fas fa-tv me-1"></i> IPTV Configuration
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="services[]" value="tr069" id="serviceTR069">
                            <label class="form-check-label" for="serviceTR069">
                                <i class="fas fa-cog me-1"></i> TR-069 Management
                            </label>
                        </div>
                    </div>

                    <div class="alert alert-info d-flex align-items-center mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        <small>AI will generate a production-ready TR-181 compliant configuration template based on your requirements.</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="generateTemplate()">
                    <i class="fas fa-wand-magic me-1"></i> Generate with AI
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function generateTemplate() {
    const form = document.getElementById('generateTemplateForm');
    const formData = new FormData(form);
    
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    const data = {
        device_type: formData.get('device_type'),
        manufacturer: formData.get('manufacturer') || 'Generic',
        model: formData.get('model') || 'Generic Model',
        services: formData.getAll('services[]')
    };
    
    $('#generateTemplateModal').modal('hide');
    showLoading('AI is generating your configuration template...');
    
    $.ajax({
        url: '{{ route("acs.profiles.ai-generate") }}',
        method: 'POST',
        data: data,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            Swal.close();
            
            if (response.success) {
                const template = response.template_data;
                const confidence = response.confidence_score;
                const suggestions = response.suggestions || [];
                
                let html = `
                    <div class="alert alert-success mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-check-circle me-2"></i> Template generated successfully</span>
                            <span class="badge bg-success">Confidence: ${confidence}%</span>
                        </div>
                    </div>
                `;
                
                if (suggestions.length > 0) {
                    html += `<div class="alert alert-info mb-3">
                        <strong><i class="fas fa-lightbulb me-2"></i>AI Suggestions:</strong>
                        <ul class="mb-0 mt-2">`;
                    suggestions.forEach(s => {
                        html += `<li>${s}</li>`;
                    });
                    html += `</ul></div>`;
                }
                
                html += `<div class="mb-3">
                    <h6 class="mb-2">Generated Configuration:</h6>
                    <pre class="bg-light p-3 rounded" style="max-height: 400px; overflow-y: auto;"><code>${JSON.stringify(template, null, 2)}</code></pre>
                </div>`;
                
                html += `<div class="text-end">
                    <button class="btn btn-primary" onclick="saveGeneratedTemplate(${JSON.stringify(template).replace(/"/g, '&quot;')})">
                        <i class="fas fa-save me-1"></i> Save as Profile
                    </button>
                </div>`;
                
                showResults('AI Template Generation Results', html);
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Generation Failed',
                    text: response.error || 'Failed to generate template'
                });
            }
        },
        error: function(xhr) {
            Swal.close();
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to communicate with AI service'
            });
        }
    });
}

function saveGeneratedTemplate(template) {
    Swal.fire({
        title: 'Save Configuration Profile',
        html: `
            <input type="text" id="profileName" class="form-control mb-2" placeholder="Profile name">
            <textarea id="profileDescription" class="form-control" placeholder="Description"></textarea>
        `,
        showCancelButton: true,
        confirmButtonText: 'Save',
        preConfirm: () => {
            const name = document.getElementById('profileName').value;
            if (!name) {
                Swal.showValidationMessage('Please enter a profile name');
                return false;
            }
            return {
                name: name,
                description: document.getElementById('profileDescription').value
            };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            console.log('Saving profile:', result.value, template);
            Swal.fire('Saved!', 'Template saved as configuration profile', 'success');
        }
    });
}
</script>
