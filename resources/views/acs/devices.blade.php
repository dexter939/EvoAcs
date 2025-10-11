@extends('layouts.app')

@section('breadcrumb', 'Dispositivi CPE')
@section('page-title', 'Gestione Dispositivi')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card mb-4">
            <div class="card-header pb-0">
                <h6>Dispositivi CPE Registrati</h6>
            </div>
            <div class="card-body px-0 pt-0 pb-2">
                <div class="table-responsive p-0">
                    <table class="table align-items-center mb-0">
                        <thead>
                            <tr>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Dispositivo</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Stato</th>
                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">IP Address</th>
                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Ultimo Inform</th>
                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($devices as $device)
                            <tr>
                                <td>
                                    <div class="d-flex px-2 py-1">
                                        <div>
                                            <i class="fas fa-router text-primary me-3"></i>
                                        </div>
                                        <div class="d-flex flex-column justify-content-center">
                                            <h6 class="mb-0 text-sm">{{ $device->serial_number }}</h6>
                                            <p class="text-xs text-secondary mb-0">{{ $device->manufacturer }} - {{ $device->model_name }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-sm bg-gradient-{{ $device->status == 'online' ? 'success' : ($device->status == 'offline' ? 'secondary' : 'warning') }}">
                                        {{ ucfirst($device->status) }}
                                    </span>
                                </td>
                                <td class="align-middle text-center text-sm">
                                    <span class="text-secondary text-xs font-weight-bold">{{ $device->ip_address ?? 'N/A' }}</span>
                                </td>
                                <td class="align-middle text-center">
                                    <span class="text-secondary text-xs font-weight-bold">{{ $device->last_inform ? $device->last_inform->format('d/m/Y H:i') : 'Mai' }}</span>
                                </td>
                                <td class="align-middle text-center">
                                    <button class="btn btn-link text-info px-2 mb-0" onclick="viewDevice({{ $device->id }})">
                                        <i class="fas fa-eye text-xs"></i>
                                    </button>
                                    <button class="btn btn-link text-success px-2 mb-0" onclick="provisionDevice({{ $device->id }}, '{{ $device->serial_number }}')">
                                        <i class="fas fa-cog text-xs"></i>
                                    </button>
                                    <button class="btn btn-link text-warning px-2 mb-0" onclick="rebootDevice({{ $device->id }}, '{{ $device->serial_number }}')">
                                        <i class="fas fa-sync text-xs"></i>
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center text-sm text-muted py-4">
                                    Nessun dispositivo registrato. I dispositivi si registreranno automaticamente al primo Inform TR-069.
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
</script>
@endpush
