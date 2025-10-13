@extends('layouts.app')

@section('breadcrumb', 'Profili')
@section('page-title', 'Profili Configurazione')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card mb-4">
            <div class="card-header pb-0 d-flex justify-content-between align-items-center">
                <div>
                    <h6>Profili di Configurazione TR-181</h6>
                    <p class="text-sm mb-0">Template parametri per provisioning zero-touch</p>
                </div>
                <div>
                    <button class="btn btn-success btn-sm me-2" data-bs-toggle="modal" data-bs-target="#aiAssistantModal">
                        <i class="fas fa-magic me-2"></i>AI Assistant
                    </button>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createProfileModal">
                        <i class="fas fa-plus me-2"></i>Nuovo Profilo
                    </button>
                </div>
            </div>
            <div class="card-body px-0 pt-0 pb-2">
                <div class="table-responsive p-0">
                    <table class="table align-items-center mb-0">
                        <thead>
                            <tr>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Nome Profilo</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Descrizione</th>
                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Stato</th>
                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Parametri</th>
                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($profiles as $profile)
                            <tr>
                                <td>
                                    <div class="d-flex px-2 py-1">
                                        <div class="d-flex flex-column justify-content-center">
                                            <h6 class="mb-0 text-sm">{{ $profile->name }}</h6>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <p class="text-xs text-secondary mb-0">{{ $profile->description ?? 'N/A' }}</p>
                                </td>
                                <td class="align-middle text-center text-sm">
                                    <span class="badge badge-sm bg-gradient-{{ $profile->is_active ? 'success' : 'secondary' }}">
                                        {{ $profile->is_active ? 'Attivo' : 'Disattivo' }}
                                    </span>
                                </td>
                                <td class="align-middle text-center">
                                    <span class="text-xs font-weight-bold">{{ is_array($profile->parameters) ? count($profile->parameters) : 0 }}</span>
                                </td>
                                <td class="align-middle text-center">
                                    <button class="btn btn-link text-success px-2 mb-0" onclick="aiValidateProfile({{ $profile->id }}, '{{ $profile->name }}')" title="AI Validate">
                                        <i class="fas fa-check-circle text-xs"></i>
                                    </button>
                                    <button class="btn btn-link text-warning px-2 mb-0" onclick="aiOptimizeProfile({{ $profile->id }}, '{{ $profile->name }}')" title="AI Optimize">
                                        <i class="fas fa-magic text-xs"></i>
                                    </button>
                                    <button class="btn btn-link text-info px-2 mb-0" onclick="editProfile({{ $profile->id }}, '{{ $profile->name }}', '{{ $profile->description }}', {{ $profile->is_active ? 'true' : 'false' }}, {{ json_encode($profile->parameters) }})">
                                        <i class="fas fa-edit text-xs"></i>
                                    </button>
                                    <button class="btn btn-link text-danger px-2 mb-0" onclick="deleteProfile({{ $profile->id }}, '{{ $profile->name }}')">
                                        <i class="fas fa-trash text-xs"></i>
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center text-sm text-muted py-4">
                                    Nessun profilo di configurazione. Clicca "Nuovo Profilo" per crearne uno.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Crea Profilo -->
<div class="modal fade" id="createProfileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Crea Nuovo Profilo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('acs.profiles.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nome Profilo *</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descrizione</label>
                        <textarea class="form-control" name="description" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Parametri TR-181 (JSON) *</label>
                        <textarea class="form-control font-monospace" name="parameters" rows="8" placeholder='{"InternetGatewayDevice.WiFi.SSID.1.SSID": "MyNetwork", "InternetGatewayDevice.WiFi.SSID.1.Enable": "1"}' required></textarea>
                        <small class="text-muted">Formato JSON con parametri TR-181</small>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" checked>
                        <label class="form-check-label">Profilo Attivo</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-primary">Crea Profilo</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Modifica Profilo -->
<div class="modal fade" id="editProfileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Modifica Profilo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editProfileForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nome Profilo *</label>
                        <input type="text" class="form-control" name="name" id="edit_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descrizione</label>
                        <textarea class="form-control" name="description" id="edit_description" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Parametri TR-181 (JSON) *</label>
                        <textarea class="form-control font-monospace" name="parameters" id="edit_parameters" rows="8" required></textarea>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_active" id="edit_is_active" value="1">
                        <label class="form-check-label">Profilo Attivo</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-primary">Salva Modifiche</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Elimina Profilo -->
<div class="modal fade" id="deleteProfileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Conferma Eliminazione</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="deleteProfileForm" method="POST">
                @csrf
                @method('DELETE')
                <div class="modal-body">
                    <p>Sei sicuro di voler eliminare il profilo <strong id="delete_profile_name"></strong>?</p>
                    <p class="text-danger text-sm">Questa azione non può essere annullata.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-danger">Elimina</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal AI Assistant -->
<div class="modal fade" id="aiAssistantModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-gradient-success">
                <h5 class="modal-title text-white"><i class="fas fa-magic me-2"></i>AI Configuration Assistant</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>AI Assistant</strong> genera automaticamente configurazioni TR-069/TR-369 ottimizzate usando intelligenza artificiale.
                </div>
                
                <h6 class="mb-3">Genera Nuovo Template</h6>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Tipo Dispositivo *</label>
                        <select class="form-select" id="ai_device_type">
                            <option value="CPE">CPE Router</option>
                            <option value="ONT">ONT/ONU Fiber</option>
                            <option value="Gateway">Gateway</option>
                            <option value="Modem">Cable Modem</option>
                            <option value="Extender">WiFi Extender</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Manufacturer</label>
                        <input type="text" class="form-control" id="ai_manufacturer" placeholder="es. Huawei, ZTE, Nokia">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Model</label>
                        <input type="text" class="form-control" id="ai_model" placeholder="es. HG8245Q2">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Servizi Richiesti</label>
                        <div class="form-check">
                            <input class="form-check-input ai-service" type="checkbox" value="wifi" id="service_wifi" checked>
                            <label class="form-check-label" for="service_wifi">WiFi 2.4GHz/5GHz</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input ai-service" type="checkbox" value="voip" id="service_voip">
                            <label class="form-check-label" for="service_voip">VoIP (TR-104)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input ai-service" type="checkbox" value="iptv" id="service_iptv">
                            <label class="form-check-label" for="service_iptv">IPTV/STB (TR-135)</label>
                        </div>
                    </div>
                </div>
                
                <button type="button" class="btn btn-success w-100 mb-3" onclick="generateAITemplate()">
                    <i class="fas fa-magic me-2"></i>Genera Template con AI
                </button>
                
                <div id="aiResultContainer" style="display:none;">
                    <hr>
                    <h6>Risultato AI</h6>
                    <div id="aiConfidence" class="mb-2"></div>
                    <div id="aiSuggestions" class="mb-3"></div>
                    <div class="mb-3">
                        <label class="form-label">Template Generato (JSON)</label>
                        <textarea class="form-control font-monospace" id="aiGeneratedTemplate" rows="10" readonly></textarea>
                    </div>
                    <button type="button" class="btn btn-primary" onclick="useAITemplate()">
                        <i class="fas fa-check me-2"></i>Usa Questo Template
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal AI Validation Results -->
<div class="modal fade" id="aiValidationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-check-circle me-2"></i>AI Validation Results</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="validationResults"></div>
            </div>
        </div>
    </div>
</div>

<!-- Modal AI Optimization Results -->
<div class="modal fade" id="aiOptimizationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-gradient-warning">
                <h5 class="modal-title text-white"><i class="fas fa-magic me-2"></i>AI Optimization Suggestions</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="optimizationResults"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function editProfile(id, name, description, isActive, parameters) {
    document.getElementById('editProfileForm').action = '/acs/profiles/' + id;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_description').value = description || '';
    document.getElementById('edit_parameters').value = JSON.stringify(parameters, null, 2);
    document.getElementById('edit_is_active').checked = isActive;
    new bootstrap.Modal(document.getElementById('editProfileModal')).show();
}

function deleteProfile(id, name) {
    document.getElementById('deleteProfileForm').action = '/acs/profiles/' + id;
    document.getElementById('delete_profile_name').textContent = name;
    new bootstrap.Modal(document.getElementById('deleteProfileModal')).show();
}

async function generateAITemplate() {
    const deviceType = document.getElementById('ai_device_type').value;
    const manufacturer = document.getElementById('ai_manufacturer').value;
    const model = document.getElementById('ai_model').value;
    
    const services = Array.from(document.querySelectorAll('.ai-service:checked'))
        .map(cb => cb.value);
    
    const button = event.target;
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Generazione in corso...';
    
    try {
        const response = await fetch('/acs/profiles/ai-generate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                device_type: deviceType,
                manufacturer: manufacturer,
                model: model,
                services: services
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            document.getElementById('aiConfidence').innerHTML = 
                `<span class="badge bg-gradient-${result.confidence_score >= 80 ? 'success' : result.confidence_score >= 60 ? 'warning' : 'secondary'}">
                    Confidence: ${result.confidence_score}%
                </span>`;
            
            if (result.suggestions && result.suggestions.length > 0) {
                document.getElementById('aiSuggestions').innerHTML = 
                    '<strong>Suggerimenti AI:</strong><ul class="text-sm">' + 
                    result.suggestions.map(s => `<li>${s}</li>`).join('') + 
                    '</ul>';
            }
            
            document.getElementById('aiGeneratedTemplate').value = 
                JSON.stringify(result.template, null, 2);
            
            document.getElementById('aiResultContainer').style.display = 'block';
        } else {
            alert('Errore: ' + result.error);
        }
    } catch (error) {
        alert('Errore di connessione: ' + error.message);
    } finally {
        button.disabled = false;
        button.innerHTML = '<i class="fas fa-magic me-2"></i>Genera Template con AI';
    }
}

function useAITemplate() {
    const template = document.getElementById('aiGeneratedTemplate').value;
    document.querySelector('#createProfileModal [name="parameters"]').value = template;
    
    bootstrap.Modal.getInstance(document.getElementById('aiAssistantModal')).hide();
    new bootstrap.Modal(document.getElementById('createProfileModal')).show();
}

async function aiValidateProfile(id, name) {
    try {
        const response = await fetch(`/acs/profiles/${id}/ai-validate`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            let html = `<h6>Profilo: ${name}</h6>`;
            
            html += `<div class="alert alert-${result.is_valid ? 'success' : 'warning'}">
                <i class="fas fa-${result.is_valid ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
                ${result.is_valid ? 'Configurazione valida!' : 'Problemi rilevati'}
            </div>`;
            
            if (result.issues && result.issues.length > 0) {
                html += '<h6 class="mt-3">Problemi:</h6><ul class="text-sm">';
                result.issues.forEach(issue => {
                    html += `<li class="text-${issue.severity === 'critical' ? 'danger' : issue.severity === 'warning' ? 'warning' : 'info'}">
                        <strong>${issue.severity.toUpperCase()}:</strong> ${issue.message}
                        ${issue.parameter ? `<br><small>Parametro: ${issue.parameter}</small>` : ''}
                    </li>`;
                });
                html += '</ul>';
            }
            
            if (result.recommendations && result.recommendations.length > 0) {
                html += '<h6 class="mt-3">Raccomandazioni:</h6><ul class="text-sm">';
                result.recommendations.forEach(rec => {
                    html += `<li>${rec}</li>`;
                });
                html += '</ul>';
            }
            
            document.getElementById('validationResults').innerHTML = html;
            new bootstrap.Modal(document.getElementById('aiValidationModal')).show();
        } else {
            alert('Errore validazione: ' + result.error);
        }
    } catch (error) {
        alert('Errore di connessione: ' + error.message);
    }
}

async function aiOptimizeProfile(id, name) {
    try {
        const response = await fetch(`/acs/profiles/${id}/ai-optimize`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ focus: 'all' })
        });
        
        const result = await response.json();
        
        if (result.success) {
            let html = `<h6>Profilo: ${name}</h6>`;
            
            if (result.suggestions && result.suggestions.length > 0) {
                html += '<div class="table-responsive"><table class="table table-sm">';
                html += '<thead><tr><th>Categoria</th><th>Parametro</th><th>Suggerimento</th><th>Impatto</th></tr></thead><tbody>';
                
                result.suggestions.forEach(sug => {
                    html += `<tr>
                        <td><span class="badge bg-gradient-info">${sug.category}</span></td>
                        <td class="text-xs">${sug.parameter || 'N/A'}</td>
                        <td class="text-xs">
                            ${sug.current_value ? `Attuale: ${sug.current_value}<br>` : ''}
                            <strong>Suggerito: ${sug.suggested_value}</strong><br>
                            <small>${sug.rationale}</small>
                        </td>
                        <td><span class="badge bg-gradient-${sug.impact === 'high' ? 'danger' : sug.impact === 'medium' ? 'warning' : 'secondary'}">
                            ${sug.impact}
                        </span></td>
                    </tr>`;
                });
                
                html += '</tbody></table></div>';
            } else {
                html += '<p class="text-muted">Nessuna ottimizzazione suggerita. La configurazione è già ottimale!</p>';
            }
            
            document.getElementById('optimizationResults').innerHTML = html;
            new bootstrap.Modal(document.getElementById('aiOptimizationModal')).show();
        } else {
            alert('Errore ottimizzazione: ' + result.error);
        }
    } catch (error) {
        alert('Errore di connessione: ' + error.message);
    }
}
</script>
@endpush
