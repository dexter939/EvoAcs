@extends('layouts.app')

@section('breadcrumb', 'Dispositivi CPE')
@section('page-title', 'Gestione Dispositivi')

@section('content')
<!-- Filters Row -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form method="GET" action="{{ route('acs.devices') }}" class="row align-items-end">
                    <div class="col-md-3">
                        <label class="form-label text-xs">Protocollo</label>
                        <select name="protocol" class="form-select form-select-sm">
                            <option value="all" {{ request('protocol', 'all') == 'all' ? 'selected' : '' }}>Tutti</option>
                            <option value="tr069" {{ request('protocol') == 'tr069' ? 'selected' : '' }}>TR-069 (CWMP)</option>
                            <option value="tr369" {{ request('protocol') == 'tr369' ? 'selected' : '' }}>TR-369 (USP)</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-xs">MTP Type (TR-369)</label>
                        <select name="mtp_type" class="form-select form-select-sm">
                            <option value="all" {{ request('mtp_type', 'all') == 'all' ? 'selected' : '' }}>Tutti</option>
                            <option value="mqtt" {{ request('mtp_type') == 'mqtt' ? 'selected' : '' }}>MQTT</option>
                            <option value="http" {{ request('mtp_type') == 'http' ? 'selected' : '' }}>HTTP</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-xs">Stato</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="all" {{ request('status', 'all') == 'all' ? 'selected' : '' }}>Tutti</option>
                            <option value="online" {{ request('status') == 'online' ? 'selected' : '' }}>Online</option>
                            <option value="offline" {{ request('status') == 'offline' ? 'selected' : '' }}>Offline</option>
                            <option value="provisioning" {{ request('status') == 'provisioning' ? 'selected' : '' }}>Provisioning</option>
                            <option value="error" {{ request('status') == 'error' ? 'selected' : '' }}>Error</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-sm btn-primary w-100">
                            <i class="fas fa-filter me-2"></i>Filtra
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card mb-4">
            <div class="card-header pb-0 d-flex justify-content-between align-items-center">
                <h6>Dispositivi CPE Registrati</h6>
                @if(request()->hasAny(['protocol', 'mtp_type', 'status']))
                <a href="{{ route('acs.devices') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-times me-1"></i>Reset Filtri
                </a>
                @endif
            </div>
            <div class="card-body px-0 pt-0 pb-2">
                <div class="table-responsive p-0">
                    <table class="table align-items-center mb-0">
                        <thead>
                            <tr>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Dispositivo</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Protocollo</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Stato</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Servizio</th>
                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">IP Address</th>
                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Ultimo Contatto</th>
                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($devices as $device)
                            <tr>
                                <td>
                                    <div class="d-flex px-2 py-1">
                                        <div>
                                            <i class="fas fa-{{ $device->protocol_type === 'tr369' ? 'satellite-dish' : 'router' }} text-{{ $device->protocol_type === 'tr369' ? 'success' : 'primary' }} me-3"></i>
                                        </div>
                                        <div class="d-flex flex-column justify-content-center">
                                            <h6 class="mb-0 text-sm">{{ $device->serial_number }}</h6>
                                            <p class="text-xs text-secondary mb-0">{{ $device->manufacturer }} - {{ $device->model_name }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if($device->protocol_type === 'tr369')
                                        <span class="badge badge-sm bg-gradient-success">TR-369</span>
                                        @if($device->mtp_type)
                                            <span class="badge badge-sm bg-gradient-{{ $device->mtp_type === 'mqtt' ? 'warning' : 'info' }}">
                                                {{ strtoupper($device->mtp_type) }}
                                            </span>
                                        @endif
                                    @else
                                        <span class="badge badge-sm bg-gradient-primary">TR-069</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge badge-sm bg-gradient-{{ $device->status == 'online' ? 'success' : ($device->status == 'offline' ? 'secondary' : 'warning') }}">
                                        {{ ucfirst($device->status) }}
                                    </span>
                                </td>
                                <td>
                                    @if($device->service)
                                        <a href="{{ route('acs.services.detail', $device->service_id) }}" class="text-xs text-primary font-weight-bold">
                                            {{ $device->service->name }}
                                        </a>
                                        <p class="text-xxs text-secondary mb-0">{{ $device->service->customer->name }}</p>
                                    @else
                                        <span class="text-xs text-secondary">Non assegnato</span>
                                    @endif
                                    <button class="btn btn-link text-success px-1 mb-0" onclick="assignService({{ $device->id }}, '{{ $device->serial_number }}')" title="Assegna a Servizio">
                                        <i class="fas fa-link text-xs"></i>
                                    </button>
                                </td>
                                <td class="align-middle text-center text-sm">
                                    <span class="text-secondary text-xs font-weight-bold">{{ $device->ip_address ?? 'N/A' }}</span>
                                </td>
                                <td class="align-middle text-center">
                                    <span class="text-secondary text-xs font-weight-bold">
                                        {{ ($device->last_contact ?? $device->last_inform) ? ($device->last_contact ?? $device->last_inform)->format('d/m/Y H:i') : 'Mai' }}
                                    </span>
                                </td>
                                <td class="align-middle text-center">
                                    <button class="btn btn-link text-info px-2 mb-0" onclick="viewDevice({{ $device->id }})">
                                        <i class="fas fa-eye text-xs"></i>
                                    </button>
                                    <button class="btn btn-link text-success px-2 mb-0" onclick="provisionDevice({{ $device->id }}, '{{ $device->serial_number }}')">
                                        <i class="fas fa-cog text-xs"></i>
                                    </button>
                                    <button class="btn btn-link text-primary px-2 mb-0" onclick="connectionRequest({{ $device->id }}, '{{ $device->serial_number }}', {{ $device->connection_request_url ? 'true' : 'false' }})" title="Connection Request">
                                        <i class="fas fa-bell text-xs"></i>
                                    </button>
                                    <button class="btn btn-link text-warning px-2 mb-0" onclick="rebootDevice({{ $device->id }}, '{{ $device->serial_number }}')">
                                        <i class="fas fa-sync text-xs"></i>
                                    </button>
                                    <button class="btn btn-link text-danger px-2 mb-0" onclick="diagnosticDevice({{ $device->id }}, '{{ $device->serial_number }}')" title="Diagnostica TR-143">
                                        <i class="fas fa-stethoscope text-xs"></i>
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center text-sm text-muted py-4">
                                    Nessun dispositivo registrato. I dispositivi si registreranno automaticamente al primo Inform TR-069 o USP Record TR-369.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        @if($devices->hasPages())
        <div class="d-flex justify-content-center">
            {{ $devices->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Modal Provisioning Dispositivo -->

<!-- Modal Provisioning Dispositivo -->
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
                            @foreach(App\Models\ConfigurationProfile::where('is_active', true)->get() as $profile)
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

<!-- Modal Reboot Dispositivo -->
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

<!-- Modal Connection Request -->
<div class="modal fade" id="connectionRequestModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Connection Request TR-069</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="fas fa-router me-2"></i>Dispositivo: <strong id="connreq_device_sn"></strong>
                </div>
                <p>Invia richiesta HTTP al dispositivo per iniziare una nuova sessione TR-069.</p>
                <p class="text-sm text-secondary">Il dispositivo riceverà la richiesta e risponderà con un nuovo Inform all'ACS.</p>
                <div id="connreq_result" class="mt-3" style="display: none;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Chiudi</button>
                <button type="button" class="btn btn-primary" id="sendConnectionRequestBtn" onclick="sendConnectionRequest()">
                    <i class="fas fa-bell me-2"></i>Invia Connection Request
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Diagnostica TR-143 -->
<div class="modal fade" id="diagnosticModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Test Diagnostici TR-143</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="fas fa-router me-2"></i>Dispositivo: <strong id="diag_device_sn"></strong>
                </div>
                
                <ul class="nav nav-pills mb-3" id="diagnosticTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="ping-tab" data-bs-toggle="pill" data-bs-target="#ping" type="button">Ping</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="traceroute-tab" data-bs-toggle="pill" data-bs-target="#traceroute" type="button">Traceroute</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="download-tab" data-bs-toggle="pill" data-bs-target="#download" type="button">Download Test</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="upload-tab" data-bs-toggle="pill" data-bs-target="#upload" type="button">Upload Test</button>
                    </li>
                </ul>
                
                <div class="tab-content" id="diagnosticTabContent">
                    <!-- Ping Tab -->
                    <div class="tab-pane fade show active" id="ping" role="tabpanel">
                        <form id="pingForm">
                            <div class="mb-3">
                                <label class="form-label">Host/IP *</label>
                                <input type="text" class="form-control" name="host" value="8.8.8.8" required>
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Pacchetti</label>
                                    <input type="number" class="form-control" name="packets" value="4" min="1" max="100">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Timeout (ms)</label>
                                    <input type="number" class="form-control" name="timeout" value="1000" min="100" max="10000">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Packet Size (bytes)</label>
                                    <input type="number" class="form-control" name="size" value="64" min="32" max="1500">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">Avvia Ping Test</button>
                        </form>
                    </div>
                    
                    <!-- Traceroute Tab -->
                    <div class="tab-pane fade" id="traceroute" role="tabpanel">
                        <form id="tracerouteForm">
                            <div class="mb-3">
                                <label class="form-label">Host/IP *</label>
                                <input type="text" class="form-control" name="host" value="google.com" required>
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Tentativi</label>
                                    <input type="number" class="form-control" name="tries" value="3" min="1" max="10">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Timeout (ms)</label>
                                    <input type="number" class="form-control" name="timeout" value="5000" min="100" max="30000">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Max Hops</label>
                                    <input type="number" class="form-control" name="max_hops" value="30" min="1" max="64">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">Avvia Traceroute</button>
                        </form>
                    </div>
                    
                    <!-- Download Test Tab -->
                    <div class="tab-pane fade" id="download" role="tabpanel">
                        <form id="downloadForm">
                            <div class="mb-3">
                                <label class="form-label">URL File Download *</label>
                                <input type="url" class="form-control" name="url" value="http://speedtest.ftp.otenet.gr/files/test1Mb.db" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">File Size (bytes, opzionale)</label>
                                <input type="number" class="form-control" name="file_size" value="1048576">
                            </div>
                            <button type="submit" class="btn btn-primary">Avvia Download Test</button>
                        </form>
                    </div>
                    
                    <!-- Upload Test Tab -->
                    <div class="tab-pane fade" id="upload" role="tabpanel">
                        <form id="uploadForm">
                            <div class="mb-3">
                                <label class="form-label">URL Server Upload *</label>
                                <input type="url" class="form-control" name="url" value="http://httpbin.org/post" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">File Size (bytes)</label>
                                <input type="number" class="form-control" name="file_size" value="1048576" min="0" max="104857600">
                            </div>
                            <button type="submit" class="btn btn-primary">Avvia Upload Test</button>
                        </form>
                    </div>
                </div>
                
                <div id="diagnostic_result" class="mt-3" style="display: none;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Chiudi</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Assegna a Servizio -->
<div class="modal fade" id="assignServiceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Assegna Dispositivo a Servizio</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="assignServiceForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-router me-2"></i>Dispositivo: <strong id="assign_device_sn"></strong>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Cliente *</label>
                        <select class="form-select" id="assign_customer_id" required>
                            <option value="">Seleziona cliente...</option>
                            @foreach(\App\Models\Customer::where('status', 'active')->orderBy('name')->get() as $customer)
                            <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Servizio *</label>
                        <select class="form-select" id="assign_service_id" name="service_id" required disabled>
                            <option value="">Seleziona prima un cliente...</option>
                        </select>
                        <small class="text-muted">Il servizio determina il cliente e le configurazioni associate al dispositivo</small>
                    </div>
                    
                    <div id="assign_loading" class="text-center" style="display: none;">
                        <div class="spinner-border spinner-border-sm text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <span class="text-sm ms-2">Caricamento servizi...</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-success">Assegna</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function viewDevice(id) {
    window.location.href = '/acs/devices/' + id;
}

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

let currentDeviceId = null;

function connectionRequest(id, sn, hasUrl) {
    currentDeviceId = id;
    document.getElementById('connreq_device_sn').textContent = sn;
    document.getElementById('connreq_result').style.display = 'none';
    document.getElementById('sendConnectionRequestBtn').disabled = false;
    
    if (!hasUrl) {
        document.getElementById('connreq_result').innerHTML = '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle me-2"></i>Dispositivo non ha ConnectionRequestURL configurata</div>';
        document.getElementById('connreq_result').style.display = 'block';
        document.getElementById('sendConnectionRequestBtn').disabled = true;
    }
    
    new bootstrap.Modal(document.getElementById('connectionRequestModal')).show();
}

function sendConnectionRequest() {
    const btn = document.getElementById('sendConnectionRequestBtn');
    const resultDiv = document.getElementById('connreq_result');
    
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Invio in corso...';
    
    fetch('/acs/devices/' + currentDeviceId + '/connection-request', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        btn.innerHTML = '<i class="fas fa-bell me-2"></i>Invia Connection Request';
        
        if (data.success) {
            resultDiv.innerHTML = '<div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>' + data.message + '<br><small class="text-muted">Metodo: ' + (data.auth_method || 'N/A') + ' | HTTP: ' + (data.http_status || 'N/A') + '</small></div>';
        } else {
            resultDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-times-circle me-2"></i>' + data.message + '<br><small class="text-muted">Errore: ' + (data.error_code || 'N/A') + '</small></div>';
        }
        
        resultDiv.style.display = 'block';
        
        setTimeout(() => {
            btn.disabled = false;
        }, 2000);
    })
    .catch(error => {
        btn.innerHTML = '<i class="fas fa-bell me-2"></i>Invia Connection Request';
        btn.disabled = false;
        resultDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-times-circle me-2"></i>Errore di rete: ' + error.message + '</div>';
        resultDiv.style.display = 'block';
    });
}

let currentDiagDeviceId = null;

function diagnosticDevice(id, sn) {
    currentDiagDeviceId = id;
    document.getElementById('diag_device_sn').textContent = sn;
    document.getElementById('diagnostic_result').style.display = 'none';
    new bootstrap.Modal(document.getElementById('diagnosticModal')).show();
}

function handleDiagnosticForm(formId, testType) {
    const form = document.getElementById(formId);
    const resultDiv = document.getElementById('diagnostic_result');
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());
        
        resultDiv.innerHTML = '<div class="alert alert-info"><span class="spinner-border spinner-border-sm me-2"></span>Test in corso...</div>';
        resultDiv.style.display = 'block';
        
        fetch(`/acs/devices/${currentDiagDeviceId}/diagnostics/${testType}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                resultDiv.innerHTML = '<div class="alert alert-info"><i class="fas fa-spinner fa-spin me-2"></i>Test avviato. Attendi risultati...<br><small class="text-muted">Test ID: ' + data.diagnostic.id + '</small></div>';
                pollDiagnosticResults(data.diagnostic.id);
            } else {
                resultDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-times-circle me-2"></i>' + data.message + '</div>';
            }
        })
        .catch(error => {
            resultDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-times-circle me-2"></i>Errore: ' + error.message + '</div>';
        });
    });
}

handleDiagnosticForm('pingForm', 'ping');
handleDiagnosticForm('tracerouteForm', 'traceroute');
handleDiagnosticForm('downloadForm', 'download');
handleDiagnosticForm('uploadForm', 'upload');

function pollDiagnosticResults(diagnosticId) {
    const resultDiv = document.getElementById('diagnostic_result');
    let pollInterval = setInterval(() => {
        fetch(`/acs/diagnostics/${diagnosticId}/results?device_id=${currentDiagDeviceId}`, {
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        })
        .then(response => response.json())
        .then(data => {
            if (data.diagnostic.status === 'completed') {
                clearInterval(pollInterval);
                let resultsHtml = '<div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>Test completato!<br>';
                if (data.summary) {
                    Object.keys(data.summary).forEach(key => {
                        const value = typeof data.summary[key] === 'object' ? JSON.stringify(data.summary[key], null, 2) : data.summary[key];
                        resultsHtml += `<small><strong>${key}:</strong> ${value}</small><br>`;
                    });
                }
                resultsHtml += `<small class="text-muted">Durata: ${data.duration_seconds || 0}s</small></div>`;
                resultDiv.innerHTML = resultsHtml;
            } else if (data.diagnostic.status === 'failed') {
                clearInterval(pollInterval);
                resultDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-times-circle me-2"></i>Test fallito: ' + (data.diagnostic.error_message || 'Errore sconosciuto') + '</div>';
            }
        })
        .catch(() => clearInterval(pollInterval));
    }, 2000);
}

// Assign Service Modal
let currentAssignDeviceId = null;

function assignService(id, sn) {
    currentAssignDeviceId = id;
    document.getElementById('assign_device_sn').textContent = sn;
    document.getElementById('assign_customer_id').value = '';
    document.getElementById('assign_service_id').innerHTML = '<option value="">Seleziona prima un cliente...</option>';
    document.getElementById('assign_service_id').disabled = true;
    document.getElementById('assignServiceForm').action = '/acs/devices/' + id + '/assign-service';
    new bootstrap.Modal(document.getElementById('assignServiceModal')).show();
}

// Load services when customer is selected
document.getElementById('assign_customer_id').addEventListener('change', function() {
    const customerId = this.value;
    const serviceSelect = document.getElementById('assign_service_id');
    const loadingDiv = document.getElementById('assign_loading');
    
    if (!customerId) {
        serviceSelect.innerHTML = '<option value="">Seleziona prima un cliente...</option>';
        serviceSelect.disabled = true;
        return;
    }
    
    serviceSelect.disabled = true;
    loadingDiv.style.display = 'block';
    
    fetch(`/acs/customers/${customerId}/services-list`)
        .then(response => response.json())
        .then(data => {
            serviceSelect.innerHTML = '<option value="">Seleziona servizio...</option>';
            
            if (data.services && data.services.length > 0) {
                data.services.forEach(service => {
                    const option = document.createElement('option');
                    option.value = service.id;
                    option.textContent = `${service.name} (${service.service_type})`;
                    serviceSelect.appendChild(option);
                });
                serviceSelect.disabled = false;
            } else {
                serviceSelect.innerHTML = '<option value="">Nessun servizio disponibile per questo cliente</option>';
            }
            
            loadingDiv.style.display = 'none';
        })
        .catch(error => {
            serviceSelect.innerHTML = '<option value="">Errore caricamento servizi</option>';
            loadingDiv.style.display = 'none';
            console.error('Error loading services:', error);
        });
});

// Handle form submission
document.getElementById('assignServiceForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const serviceId = document.getElementById('assign_service_id').value;
    
    if (!serviceId) {
        alert('Seleziona un servizio');
        return;
    }
    
    fetch(this.action, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ service_id: serviceId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('assignServiceModal')).hide();
            window.location.reload();
        } else {
            alert('Errore: ' + (data.message || 'Impossibile assegnare servizio'));
        }
    })
    .catch(error => {
        alert('Errore di rete: ' + error.message);
    });
});
</script>
@endpush
