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
                            @forelse($device->deviceParameters as $param)
                            <tr>
                                <td class="text-xs px-3">{{ $param->parameter_name }}</td>
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
                            @forelse($device->provisioningTasks()->latest()->take(10)->get() as $task)
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
@endsection

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
</script>
@endpush
