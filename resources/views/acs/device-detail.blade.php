@extends('layouts.app')

@section('breadcrumb', 'Dettaglio Dispositivo')
@section('page-title', $device->serial_number)

@section('content')
<div class="row">
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header pb-0">
                <h6>Informazioni Dispositivo</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr>
                        <th class="text-sm">Serial Number:</th>
                        <td class="text-sm">{{ $device->serial_number }}</td>
                    </tr>
                    <tr>
                        <th class="text-sm">Manufacturer:</th>
                        <td class="text-sm">{{ $device->manufacturer }}</td>
                    </tr>
                    <tr>
                        <th class="text-sm">Model:</th>
                        <td class="text-sm">{{ $device->model_name }}</td>
                    </tr>
                    <tr>
                        <th class="text-sm">IP Address:</th>
                        <td class="text-sm">{{ $device->ip_address ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th class="text-sm">Stato:</th>
                        <td><span class="badge bg-gradient-{{ $device->status == 'online' ? 'success' : 'secondary' }}">{{ ucfirst($device->status) }}</span></td>
                    </tr>
                    <tr>
                        <th class="text-sm">Ultimo Inform:</th>
                        <td class="text-sm">{{ $device->last_inform ? $device->last_inform->format('d/m/Y H:i:s') : 'Mai' }}</td>
                    </tr>
                    <tr>
                        <th class="text-sm">Profilo Attivo:</th>
                        <td class="text-sm">{{ $device->configurationProfile->name ?? 'Nessuno' }}</td>
                    </tr>
                </table>
                
                <div class="mt-3">
                    <button class="btn btn-success btn-sm w-100 mb-2" onclick="provisionDevice({{ $device->id }}, '{{ $device->serial_number }}')">
                        <i class="fas fa-cog me-2"></i>Provisioning
                    </button>
                    <button class="btn btn-warning btn-sm w-100 mb-2" onclick="rebootDevice({{ $device->id }}, '{{ $device->serial_number }}')">
                        <i class="fas fa-sync me-2"></i>Reboot
                    </button>
                    <button class="btn btn-primary btn-sm w-100 mb-2" onclick="aiAnalyzeDeviceHistory({{ $device->id }})">
                        <i class="fas fa-magic me-2"></i>AI Diagnostic Analysis
                    </button>
                    @if($device->protocol_type === 'tr369')
                    <a href="{{ route('acs.devices.subscriptions', $device->id) }}" class="btn btn-info btn-sm w-100 mb-2">
                        <i class="fas fa-bell me-2"></i>Sottoscrizioni Eventi
                    </a>
                    @endif
                    <a href="{{ route('acs.devices') }}" class="btn btn-secondary btn-sm w-100">
                        <i class="fas fa-arrow-left me-2"></i>Torna alla Lista
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header pb-0">
                <h6>Parametri TR-181</h6>
            </div>
            <div class="card-body px-0 pt-0 pb-2">
                <div class="table-responsive p-0">
                    <table class="table align-items-center mb-0">
                        <thead>
                            <tr>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Parametro</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Valore</th>
                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Tipo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($device->parameters as $param)
                            <tr>
                                <td class="text-xs px-3">{{ $param->parameter_path }}</td>
                                <td class="text-xs">{{ $param->parameter_value }}</td>
                                <td class="text-xs text-center">{{ $param->parameter_type }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center text-sm text-muted py-4">
                                    Nessun parametro disponibile
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header pb-0">
                <h6>Remote Diagnostics (TR-143)</h6>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-6">
                        <button class="btn btn-outline-primary btn-sm w-100" onclick="openDiagnosticModal('ping', {{ $device->id }})">
                            <i class="fas fa-network-wired me-2"></i>Ping Test
                        </button>
                    </div>
                    <div class="col-6">
                        <button class="btn btn-outline-primary btn-sm w-100" onclick="openDiagnosticModal('traceroute', {{ $device->id }})">
                            <i class="fas fa-route me-2"></i>Traceroute
                        </button>
                    </div>
                    <div class="col-6">
                        <button class="btn btn-outline-primary btn-sm w-100" onclick="openDiagnosticModal('download', {{ $device->id }})">
                            <i class="fas fa-download me-2"></i>Download Test
                        </button>
                    </div>
                    <div class="col-6">
                        <button class="btn btn-outline-primary btn-sm w-100" onclick="openDiagnosticModal('upload', {{ $device->id }})">
                            <i class="fas fa-upload me-2"></i>Upload Test
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header pb-0 d-flex justify-content-between align-items-center">
                <h6>Network Topology Map</h6>
                <button class="btn btn-sm btn-primary" onclick="triggerNetworkScan({{ $device->id }})">
                    <i class="fas fa-sync me-2"></i>Scan Network
                </button>
            </div>
            <div class="card-body">
                <div id="network-stats" class="row mb-3">
                    <div class="col-3 text-center">
                        <div class="text-sm text-muted">Total</div>
                        <div class="h5 mb-0" id="stats-total">0</div>
                    </div>
                    <div class="col-3 text-center">
                        <div class="text-sm text-muted">LAN</div>
                        <div class="h5 mb-0" id="stats-lan">0</div>
                    </div>
                    <div class="col-3 text-center">
                        <div class="text-sm text-muted">WiFi 2.4GHz</div>
                        <div class="h5 mb-0" id="stats-wifi24">0</div>
                    </div>
                    <div class="col-3 text-center">
                        <div class="text-sm text-muted">WiFi 5GHz</div>
                        <div class="h5 mb-0" id="stats-wifi5">0</div>
                    </div>
                </div>
                
                <div id="network-topology-container" style="position: relative; height: 400px; border: 1px solid #e9ecef; border-radius: 0.5rem; background: #f8f9fa;">
                    <div class="d-flex justify-content-center align-items-center h-100">
                        <div class="text-center text-muted">
                            <i class="fas fa-network-wired fa-3x mb-3"></i>
                            <p>Click "Scan Network" to visualize connected clients</p>
                        </div>
                    </div>
                </div>
                
                <div id="network-clients-list" class="mt-3" style="display: none;">
                    <h6 class="text-sm font-weight-bold mb-2">Connected Clients</h6>
                    <div class="table-responsive">
                        <table class="table table-sm align-items-center mb-0">
                            <thead>
                                <tr>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Device</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">IP Address</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Connection</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Signal</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Last Seen</th>
                                </tr>
                            </thead>
                            <tbody id="clients-table-body">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header pb-0">
                <h6>Task Recenti</h6>
            </div>
            <div class="card-body px-0 pt-0 pb-2">
                <div class="table-responsive p-0">
                    <table class="table align-items-center mb-0">
                        <thead>
                            <tr>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Tipo Task</th>
                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Stato</th>
                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Data</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentTasks as $task)
                            <tr>
                                <td class="text-xs px-3">{{ ucfirst(str_replace('_', ' ', $task->task_type)) }}</td>
                                <td class="text-center">
                                    <span class="badge badge-sm bg-gradient-{{ $task->status == 'completed' ? 'success' : ($task->status == 'failed' ? 'danger' : 'warning') }}">
                                        {{ ucfirst($task->status) }}
                                    </span>
                                </td>
                                <td class="text-xs text-center">{{ $task->created_at->format('d/m/Y H:i') }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center text-sm text-muted py-4">
                                    Nessun task
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

<!-- Modal Provisioning -->
<div class="modal fade" id="provisionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Provisioning Dispositivo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="provisionForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-router me-2"></i>Dispositivo: <strong id="provision_device_sn"></strong>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Profilo Configurazione *</label>
                        <select class="form-select" name="profile_id" required>
                            <option value="">Seleziona profilo...</option>
                            @foreach($activeProfiles as $profile)
                            <option value="{{ $profile->id }}">{{ $profile->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-success">Avvia Provisioning</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Reboot -->
<div class="modal fade" id="rebootModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reboot Dispositivo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="rebootForm" method="POST">
                @csrf
                <div class="modal-body">
                    <p>Sei sicuro di voler riavviare il dispositivo <strong id="reboot_device_sn"></strong>?</p>
                    <p class="text-warning text-sm"><i class="fas fa-exclamation-triangle me-2"></i>Il dispositivo si riavvierà immediatamente.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-warning">Riavvia</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Diagnostic Test -->
<div class="modal fade" id="diagnosticModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="diagnosticModalTitle">Diagnostic Test</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="diagnosticForm">
                @csrf
                <div class="modal-body">
                    <div id="diagnosticFormFields"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-primary">Avvia Test</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

<!-- Modal AI Historical Analysis -->
<div class="modal fade" id="aiHistoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-gradient-primary">
                <h5 class="modal-title text-white"><i class="fas fa-chart-line me-2"></i>AI Historical Diagnostic Analysis</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="aiHistoryContent"></div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function provisionDevice(id, sn) {
    document.getElementById('provisionForm').action = '/acs/devices/' + id + '/provision';
    document.getElementById('provision_device_sn').textContent = sn;
    new bootstrap.Modal(document.getElementById('provisionModal')).show();
}

function rebootDevice(id, sn) {
    document.getElementById('rebootForm').action = '/acs/devices/' + id + '/reboot';
    document.getElementById('reboot_device_sn').textContent = sn;
    new bootstrap.Modal(document.getElementById('rebootModal')).show();
}

async function aiAnalyzeDeviceHistory(deviceId) {
    const modal = new bootstrap.Modal(document.getElementById('aiHistoryModal'));
    const content = document.getElementById('aiHistoryContent');
    
    content.innerHTML = '<div class="text-center py-5"><i class="fas fa-spinner fa-spin fa-3x text-primary"></i><p class="mt-3">AI sta analizzando lo storico diagnostico...</p></div>';
    modal.show();
    
    try {
        const response = await fetch(`/acs/devices/${deviceId}/ai-analyze-diagnostics`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            let html = '';
            
            // Header with test count and confidence
            html += `<div class="alert alert-info mb-3">
                <div class="d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-database me-2"></i>Tests Analyzed: <strong>${result.tests_analyzed}</strong></span>
                    <span><i class="fas fa-percentage me-2"></i>Confidence: <strong>${result.confidence}%</strong></span>
                    <span class="badge bg-gradient-${result.trend === 'improving' ? 'success' : result.trend === 'degrading' ? 'danger' : 'secondary'}">
                        Trend: ${result.trend.toUpperCase()}
                    </span>
                </div>
            </div>`;
            
            // Root Cause
            if (result.root_cause) {
                html += `<div class="card mb-3">
                    <div class="card-header bg-gradient-dark">
                        <h6 class="text-white mb-0"><i class="fas fa-search me-2"></i>Root Cause Analysis</h6>
                    </div>
                    <div class="card-body">
                        <p class="mb-0">${result.root_cause}</p>
                    </div>
                </div>`;
            }
            
            // Patterns Detected
            if (result.patterns && result.patterns.length > 0) {
                html += `<div class="card mb-3">
                    <div class="card-header bg-gradient-warning">
                        <h6 class="text-white mb-0"><i class="fas fa-chart-area me-2"></i>Patterns Detected (${result.patterns.length})</h6>
                    </div>
                    <div class="card-body">`;
                
                result.patterns.forEach((pattern, index) => {
                    const typeClass = {
                        'degradation': 'danger',
                        'intermittent': 'warning',
                        'recurring': 'info'
                    }[pattern.type] || 'secondary';
                    
                    html += `<div class="border-bottom pb-3 mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0">${index + 1}. ${pattern.description}</h6>
                            <span class="badge bg-gradient-${typeClass}">${pattern.type}</span>
                        </div>
                        <p class="text-sm mb-1"><strong>Affected Tests:</strong> ${pattern.affected_tests.join(', ')}</p>
                        <p class="text-sm mb-0"><strong>Frequency:</strong> ${pattern.frequency}</p>
                    </div>`;
                });
                
                html += `</div></div>`;
            }
            
            // Recommendations
            if (result.recommendations && result.recommendations.length > 0) {
                html += `<div class="card mb-3">
                    <div class="card-header bg-gradient-success">
                        <h6 class="text-white mb-0"><i class="fas fa-lightbulb me-2"></i>Recommendations (${result.recommendations.length})</h6>
                    </div>
                    <div class="card-body">`;
                
                result.recommendations.forEach((rec, index) => {
                    const priorityClass = {
                        'high': 'danger',
                        'medium': 'warning',
                        'low': 'info'
                    }[rec.priority] || 'secondary';
                    
                    html += `<div class="border-bottom pb-3 mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0">${index + 1}. ${rec.action}</h6>
                            <span class="badge bg-gradient-${priorityClass}">${rec.priority} priority</span>
                        </div>
                        <p class="text-sm mb-0"><strong>Rationale:</strong> ${rec.rationale}</p>
                    </div>`;
                });
                
                html += `</div></div>`;
            }
            
            if (!result.patterns || result.patterns.length === 0) {
                html += `<div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>Nessun pattern critico rilevato. Il dispositivo sembra operare normalmente.
                </div>`;
            }
            
            content.innerHTML = html;
        } else {
            content.innerHTML = `<div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>${result.error}
            </div>`;
        }
    } catch (error) {
        content.innerHTML = `<div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle me-2"></i>Errore di connessione: ${error.message}
        </div>`;
    }
}

function openDiagnosticModal(type, deviceId) {
    const titles = {
        ping: 'Ping Test (IPPing)',
        traceroute: 'Traceroute Test',
        download: 'Download Speed Test',
        upload: 'Upload Speed Test'
    };
    
    const forms = {
        ping: `
            <div class="mb-3">
                <label class="form-label">Host / IP Address *</label>
                <input type="text" class="form-control" name="host" placeholder="8.8.8.8 or google.com" required>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Numero Pacchetti</label>
                    <input type="number" class="form-control" name="packets" value="4" min="1" max="100">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Timeout (ms)</label>
                    <input type="number" class="form-control" name="timeout" value="1000" min="100" max="10000">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Dimensione Pacchetto (bytes)</label>
                <input type="number" class="form-control" name="size" value="64" min="32" max="1500">
            </div>
        `,
        traceroute: `
            <div class="mb-3">
                <label class="form-label">Host / IP Address *</label>
                <input type="text" class="form-control" name="host" placeholder="8.8.8.8 or google.com" required>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Numero Tentativi</label>
                    <input type="number" class="form-control" name="tries" value="3" min="1" max="10">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Timeout (ms)</label>
                    <input type="number" class="form-control" name="timeout" value="5000" min="100" max="30000">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Max Hop Count</label>
                <input type="number" class="form-control" name="max_hops" value="30" min="1" max="64">
            </div>
        `,
        download: `
            <div class="mb-3">
                <label class="form-label">Download URL *</label>
                <input type="url" class="form-control" name="url" placeholder="http://example.com/test.bin" required>
            </div>
            <div class="mb-3">
                <label class="form-label">File Size (bytes, 0=auto)</label>
                <input type="number" class="form-control" name="file_size" value="0" min="0">
            </div>
        `,
        upload: `
            <div class="mb-3">
                <label class="form-label">Upload URL *</label>
                <input type="url" class="form-control" name="url" placeholder="http://example.com/upload" required>
            </div>
            <div class="mb-3">
                <label class="form-label">File Size (bytes)</label>
                <input type="number" class="form-control" name="file_size" value="1048576" min="0" max="104857600">
                <small class="text-muted">Max 100MB</small>
            </div>
        `
    };
    
    document.getElementById('diagnosticModalTitle').textContent = titles[type] || 'Diagnostic Test';
    document.getElementById('diagnosticFormFields').innerHTML = forms[type] || '';
    
    const form = document.getElementById('diagnosticForm');
    form.onsubmit = async (e) => {
        e.preventDefault();
        
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());
        
        try {
            const response = await fetch(`/acs/devices/${deviceId}/diagnostics/${type}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(data)
            });
            
            const result = await response.json();
            
            if (result.success) {
                bootstrap.Modal.getInstance(document.getElementById('diagnosticModal')).hide();
                alert(`✅ ${titles[type]} avviato con successo! ID: ${result.diagnostic.id}`);
                location.reload();
            } else {
                alert(`❌ Errore: ${result.message}`);
            }
        } catch (error) {
            alert(`❌ Errore connessione: ${error.message}`);
        }
    };
    
    new bootstrap.Modal(document.getElementById('diagnosticModal')).show();
}

// Network Topology Map Functions
async function triggerNetworkScan(deviceId) {
    try {
        const response = await fetch(`/acs/devices/${deviceId}/trigger-network-scan`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ data_model: 'tr098' })
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('✅ ' + result.message);
            // Wait 3 seconds then load network map
            setTimeout(() => loadNetworkMap(deviceId), 3000);
        } else {
            alert('❌ Errore: ' + result.message);
        }
    } catch (error) {
        alert('❌ Errore connessione: ' + error.message);
    }
}

async function loadNetworkMap(deviceId) {
    try {
        const response = await fetch(`/acs/devices/${deviceId}/network-map`);
        const result = await response.json();
        
        if (result.success && result.clients.length > 0) {
            updateNetworkStats(result.stats);
            renderNetworkTopology(result.device, result.clients);
            renderClientsList(result.clients);
        } else {
            document.getElementById('network-topology-container').innerHTML = 
                '<div class="d-flex justify-content-center align-items-center h-100"><div class="text-center text-muted"><i class="fas fa-exclamation-circle fa-3x mb-3"></i><p>No clients found. Try scanning again.</p></div></div>';
        }
    } catch (error) {
        console.error('Error loading network map:', error);
    }
}

function updateNetworkStats(stats) {
    document.getElementById('stats-total').textContent = stats.total;
    document.getElementById('stats-lan').textContent = stats.lan;
    document.getElementById('stats-wifi24').textContent = stats.wifi_2_4ghz;
    document.getElementById('stats-wifi5').textContent = stats.wifi_5ghz;
}

function renderNetworkTopology(device, clients) {
    const container = document.getElementById('network-topology-container');
    container.innerHTML = '';
    
    // Create SVG canvas
    const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    svg.setAttribute('width', '100%');
    svg.setAttribute('height', '100%');
    svg.style.position = 'absolute';
    svg.style.top = '0';
    svg.style.left = '0';
    
    // Draw router (center)
    const routerX = container.offsetWidth / 2;
    const routerY = 80;
    
    const routerCircle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
    routerCircle.setAttribute('cx', routerX);
    routerCircle.setAttribute('cy', routerY);
    routerCircle.setAttribute('r', '30');
    routerCircle.setAttribute('fill', '#5e72e4');
    svg.appendChild(routerCircle);
    
    // Router icon (text)
    const routerText = document.createElementNS('http://www.w3.org/2000/svg', 'text');
    routerText.setAttribute('x', routerX);
    routerText.setAttribute('y', routerY + 5);
    routerText.setAttribute('text-anchor', 'middle');
    routerText.setAttribute('fill', 'white');
    routerText.setAttribute('font-size', '14');
    routerText.textContent = '🛜';
    svg.appendChild(routerText);
    
    // Draw clients in circle around router
    const radius = 150;
    const angleStep = (2 * Math.PI) / clients.length;
    
    clients.forEach((client, index) => {
        const angle = angleStep * index - Math.PI / 2;
        const x = routerX + radius * Math.cos(angle);
        const y = routerY + radius * Math.sin(angle);
        
        // Draw connection line
        const line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
        line.setAttribute('x1', routerX);
        line.setAttribute('y1', routerY);
        line.setAttribute('x2', x);
        line.setAttribute('y2', y);
        line.setAttribute('stroke', client.connection_type === 'lan' ? '#2dce89' : '#11cdef');
        line.setAttribute('stroke-width', '2');
        line.setAttribute('stroke-dasharray', client.connection_type === 'lan' ? '0' : '5,5');
        svg.appendChild(line);
        
        // Draw client circle
        const clientCircle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
        clientCircle.setAttribute('cx', x);
        clientCircle.setAttribute('cy', y);
        clientCircle.setAttribute('r', '20');
        clientCircle.setAttribute('fill', client.connection_type === 'lan' ? '#2dce89' : '#11cdef');
        clientCircle.setAttribute('data-bs-toggle', 'tooltip');
        clientCircle.setAttribute('title', `${client.hostname}\\n${client.ip_address}\\nMAC: ${client.mac_address}${client.signal_strength ? '\\nSignal: ' + client.signal_strength + ' dBm' : ''}`);
        svg.appendChild(clientCircle);
        
        // Client icon
        const clientIcon = document.createElementNS('http://www.w3.org/2000/svg', 'text');
        clientIcon.setAttribute('x', x);
        clientIcon.setAttribute('y', y + 5);
        clientIcon.setAttribute('text-anchor', 'middle');
        clientIcon.setAttribute('fill', 'white');
        clientIcon.setAttribute('font-size', '12');
        clientIcon.textContent = client.connection_type === 'lan' ? '💻' : '📱';
        svg.appendChild(clientIcon);
    });
    
    container.appendChild(svg);
}

function renderClientsList(clients) {
    const tbody = document.getElementById('clients-table-body');
    tbody.innerHTML = '';
    
    clients.forEach(client => {
        const signalBadge = client.signal_strength ? 
            `<span class="badge badge-sm bg-gradient-${client.signal_quality === 'excellent' ? 'success' : client.signal_quality === 'good' ? 'info' : client.signal_quality === 'fair' ? 'warning' : 'danger'}">
                ${client.signal_strength} dBm
            </span>` : 
            '<span class="text-muted">-</span>';
            
        const connectionBadge = `<span class="badge badge-sm bg-gradient-${client.connection_type === 'lan' ? 'success' : 'info'}">
            <i class="fas ${client.connection_icon} me-1"></i>${client.connection_type.replace('_', ' ')}
        </span>`;
        
        const row = `
            <tr>
                <td class="text-xs px-3">
                    <strong>${client.hostname}</strong><br>
                    <small class="text-muted">${client.mac_address}</small>
                </td>
                <td class="text-xs">${client.ip_address}</td>
                <td class="text-xs">${connectionBadge}</td>
                <td class="text-xs">${signalBadge}</td>
                <td class="text-xs">${client.last_seen}</td>
            </tr>
        `;
        tbody.innerHTML += row;
    });
    
    document.getElementById('network-clients-list').style.display = 'block';
}

// Auto-load network map on page load if data exists
document.addEventListener('DOMContentLoaded', () => {
    const deviceId = {{ $device->id }};
    loadNetworkMap(deviceId);
});
</script>
@endpush
