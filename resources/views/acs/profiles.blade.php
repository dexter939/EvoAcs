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
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createProfileModal">
                    <i class="fas fa-plus me-2"></i>Nuovo Profilo
                </button>
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
</script>
@endpush
