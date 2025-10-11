@extends('layouts.app')

@section('breadcrumb', 'Firmware')
@section('page-title', 'Gestione Firmware')

@section('content')
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header pb-0">
                <h6>Upload Nuovo Firmware</h6>
            </div>
            <div class="card-body">
                <form action="{{ route('acs.firmware.upload') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Manufacturer *</label>
                        <input type="text" class="form-control" name="manufacturer" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Model *</label>
                        <input type="text" class="form-control" name="model" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Versione *</label>
                        <input type="text" class="form-control" name="version" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">File Firmware</label>
                        <input type="file" class="form-control @error('firmware_file') is-invalid @enderror" name="firmware_file">
                        @error('firmware_file')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Obbligatorio se non fornisci URL Download</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">URL Download</label>
                        <input type="url" class="form-control @error('download_url') is-invalid @enderror" name="download_url" placeholder="http://example.com/firmware.bin">
                        @error('download_url')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Obbligatorio se non carichi un file</small>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="is_stable" value="1">
                        <label class="form-check-label">Versione Stabile</label>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-upload me-2"></i>Upload Firmware
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card">
            <div class="card-header pb-0 d-flex justify-content-between align-items-center">
                <div>
                    <h6>Versioni Firmware Disponibili</h6>
                    <p class="text-sm mb-0">Gestione versioni firmware</p>
                </div>
            </div>
            <div class="card-body px-0 pt-0 pb-2">
                <div class="table-responsive p-0">
                    <table class="table align-items-center mb-0">
                        <thead>
                            <tr>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Firmware</th>
                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Versione</th>
                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Stato</th>
                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($firmwareVersions as $fw)
                            <tr>
                                <td>
                                    <div class="d-flex px-2 py-1">
                                        <div class="d-flex flex-column justify-content-center">
                                            <h6 class="mb-0 text-sm">{{ $fw->manufacturer }}</h6>
                                            <p class="text-xs text-secondary mb-0">{{ $fw->model }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="align-middle text-center">
                                    <span class="text-xs font-weight-bold">{{ $fw->version }}</span>
                                </td>
                                <td class="align-middle text-center">
                                    <span class="badge badge-sm bg-gradient-{{ $fw->is_stable ? 'success' : 'info' }}">
                                        {{ $fw->is_stable ? 'Stabile' : 'Beta' }}
                                    </span>
                                </td>
                                <td class="align-middle text-center">
                                    <button class="btn btn-sm btn-primary" onclick="deployFirmware({{ $fw->id }}, '{{ $fw->manufacturer }} {{ $fw->model }} v{{ $fw->version }}')">
                                        <i class="fas fa-rocket me-1"></i>Deploy
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center text-sm text-muted py-4">
                                    Nessuna versione firmware disponibile
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

<div class="row">
    <div class="col-12">
        <div class="card mb-4">
            <div class="card-header pb-0">
                <h6>Deployment Firmware in Corso</h6>
                <p class="text-sm">Aggiornamenti firmware via TR-069 Download</p>
            </div>
            <div class="card-body px-0 pt-0 pb-2">
                <div class="table-responsive p-0">
                    <table class="table align-items-center mb-0">
                        <thead>
                            <tr>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Dispositivo</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Firmware</th>
                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Stato</th>
                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Progresso</th>
                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Data</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($deployments as $deployment)
                            <tr>
                                <td>
                                    <div class="d-flex px-2 py-1">
                                        <div class="d-flex flex-column justify-content-center">
                                            <h6 class="mb-0 text-sm">{{ $deployment->cpeDevice->serial_number ?? 'N/A' }}</h6>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <p class="text-xs font-weight-bold mb-0">{{ $deployment->firmwareVersion->version ?? 'N/A' }}</p>
                                    <p class="text-xs text-secondary mb-0">{{ $deployment->firmwareVersion->manufacturer ?? '' }}</p>
                                </td>
                                <td class="align-middle text-center text-sm">
                                    <span class="badge badge-sm bg-gradient-{{ $deployment->status == 'completed' ? 'success' : ($deployment->status == 'failed' ? 'danger' : 'info') }}">
                                        {{ ucfirst($deployment->status) }}
                                    </span>
                                </td>
                                <td class="align-middle text-center">
                                    <span class="text-xs font-weight-bold">{{ $deployment->download_progress ?? 0 }}%</span>
                                </td>
                                <td class="align-middle text-center">
                                    <span class="text-secondary text-xs font-weight-bold">{{ $deployment->created_at->format('d/m/Y H:i') }}</span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center text-sm text-muted py-4">
                                    Nessun deployment firmware
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        @if($deployments->hasPages())
        <div class="d-flex justify-content-center">
            {{ $deployments->links() }}
        </div>
        @endif
    </div>
</div>
</div>
</div>
</div>

<!-- Modal Deploy Firmware -->
<div class="modal fade" id="deployFirmwareModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Deploy Firmware</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="deployFirmwareForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>Firmware: <strong id="deploy_firmware_name"></strong>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Seleziona Dispositivi *</label>
                        <select class="form-select" name="device_ids[]" multiple size="8" required>
                            @foreach($devices as $device)
                            <option value="{{ $device->id }}">{{ $device->serial_number }} - {{ $device->manufacturer }} {{ $device->model_name }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Tieni premuto Ctrl/Cmd per selezione multipla</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Schedulazione</label>
                        <input type="datetime-local" class="form-control" name="scheduled_at">
                        <small class="text-muted">Lascia vuoto per deploy immediato</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-primary">Avvia Deploy</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function deployFirmware(id, name) {
    document.getElementById('deployFirmwareForm').action = '/acs/firmware/' + id + '/deploy';
    document.getElementById('deploy_firmware_name').textContent = name;
    new bootstrap.Modal(document.getElementById('deployFirmwareModal')).show();
}
</script>
@endpush
