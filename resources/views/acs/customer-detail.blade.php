@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header pb-0">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb bg-transparent mb-0 pb-0 pt-1 px-0 me-sm-6 me-5">
                            <li class="breadcrumb-item text-sm"><a class="opacity-5 text-dark" href="{{ route('acs.customers') }}">Clienti</a></li>
                            <li class="breadcrumb-item text-sm text-dark active" aria-current="page">{{ $customer->name }}</li>
                        </ol>
                    </nav>
                    <h6 class="mt-3">Dettaglio Cliente</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-uppercase text-xs font-weight-bolder opacity-7">Informazioni Cliente</h6>
                            <ul class="list-group">
                                <li class="list-group-item border-0 ps-0 pt-0 text-sm"><strong>Nome:</strong> {{ $customer->name }}</li>
                                <li class="list-group-item border-0 ps-0 text-sm"><strong>ID Esterno:</strong> {{ $customer->external_id ?? '-' }}</li>
                                <li class="list-group-item border-0 ps-0 text-sm"><strong>Email Contatto:</strong> {{ $customer->contact_email ?? '-' }}</li>
                                <li class="list-group-item border-0 ps-0 text-sm"><strong>Timezone:</strong> {{ $customer->timezone }}</li>
                                <li class="list-group-item border-0 ps-0 text-sm">
                                    <strong>Stato:</strong>
                                    @if($customer->status == 'active')
                                        <span class="badge badge-sm bg-gradient-success">Attivo</span>
                                    @elseif($customer->status == 'inactive')
                                        <span class="badge badge-sm bg-gradient-secondary">Inattivo</span>
                                    @elseif($customer->status == 'suspended')
                                        <span class="badge badge-sm bg-gradient-warning">Sospeso</span>
                                    @else
                                        <span class="badge badge-sm bg-gradient-danger">Terminato</span>
                                    @endif
                                </li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-uppercase text-xs font-weight-bolder opacity-7">Statistiche</h6>
                            <ul class="list-group">
                                <li class="list-group-item border-0 ps-0 pt-0 text-sm"><strong>Totale Servizi:</strong> {{ $customer->services->count() }}</li>
                                <li class="list-group-item border-0 ps-0 text-sm"><strong>Data Creazione:</strong> {{ $customer->created_at->format('d/m/Y H:i') }}</li>
                                <li class="list-group-item border-0 ps-0 text-sm"><strong>Ultimo Aggiornamento:</strong> {{ $customer->updated_at->format('d/m/Y H:i') }}</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header pb-0 d-flex justify-content-between align-items-center">
                    <h6>Servizi del Cliente</h6>
                    <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addServiceModal">
                        <i class="fas fa-plus"></i> Nuovo Servizio
                    </button>
                </div>
                <div class="card-body px-0 pt-0 pb-2">
                    <div class="table-responsive p-0">
                        <table class="table align-items-center mb-0">
                            <thead>
                                <tr>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Servizio</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Tipo</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Contratto</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Dispositivi</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Stato</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">SLA</th>
                                    <th class="text-secondary opacity-7"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($customer->services as $service)
                                <tr>
                                    <td>
                                        <div class="d-flex px-2 py-1">
                                            <div class="d-flex flex-column justify-content-center">
                                                <h6 class="mb-0 text-sm">{{ $service->name }}</h6>
                                                <p class="text-xs text-secondary mb-0">ID: {{ $service->id }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-sm bg-gradient-primary">{{ $service->service_type }}</span>
                                    </td>
                                    <td>
                                        <p class="text-xs font-weight-bold mb-0">{{ $service->contract_number ?? '-' }}</p>
                                    </td>
                                    <td class="align-middle text-center">
                                        <span class="badge badge-sm bg-gradient-info">{{ $service->cpe_devices_count }} dispositivi</span>
                                    </td>
                                    <td class="align-middle text-center text-sm">
                                        @if($service->status == 'active')
                                            <span class="badge badge-sm bg-gradient-success">Attivo</span>
                                        @elseif($service->status == 'provisioned')
                                            <span class="badge badge-sm bg-gradient-info">Provisioned</span>
                                        @elseif($service->status == 'suspended')
                                            <span class="badge badge-sm bg-gradient-warning">Sospeso</span>
                                        @else
                                            <span class="badge badge-sm bg-gradient-danger">Terminato</span>
                                        @endif
                                    </td>
                                    <td class="align-middle text-center">
                                        <span class="text-xs font-weight-bold">{{ $service->sla_tier ?? '-' }}</span>
                                    </td>
                                    <td class="align-middle">
                                        <a href="{{ route('acs.services.detail', $service->id) }}" 
                                           class="btn btn-link text-secondary mb-0 px-2" data-toggle="tooltip" data-original-title="Vedi dettagli">
                                            <i class="fa fa-eye text-xs"></i>
                                        </a>
                                        <button type="button" class="btn btn-link text-dark mb-0 px-2" 
                                                data-bs-toggle="modal" data-bs-target="#editServiceModal"
                                                data-service-id="{{ $service->id }}"
                                                data-service-name="{{ $service->name }}"
                                                data-service-type="{{ $service->service_type }}"
                                                data-service-contract="{{ $service->contract_number }}"
                                                data-service-sla="{{ $service->sla_tier }}"
                                                data-service-status="{{ $service->status }}"
                                                data-toggle="tooltip" data-original-title="Modifica">
                                            <i class="fas fa-pencil-alt text-xs"></i>
                                        </button>
                                        <button type="button" class="btn btn-link text-danger mb-0 px-2" 
                                                data-bs-toggle="modal" data-bs-target="#deleteServiceModal"
                                                data-service-id="{{ $service->id }}"
                                                data-service-name="{{ $service->name }}"
                                                data-toggle="tooltip" data-original-title="Elimina">
                                            <i class="fas fa-trash text-xs"></i>
                                        </button>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <p class="text-sm text-secondary mb-0">Nessun servizio associato</p>
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
</div>

<!-- Add Service Modal -->
<div class="modal fade" id="addServiceModal" tabindex="-1" aria-labelledby="addServiceModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addServiceModalLabel">Nuovo Servizio</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('acs.services.store') }}" method="POST">
                @csrf
                <input type="hidden" name="customer_id" value="{{ $customer->id }}">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="add_service_name" class="form-label">Nome Servizio *</label>
                        <input type="text" class="form-control" id="add_service_name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="add_service_type" class="form-label">Tipo Servizio *</label>
                        <select class="form-select" id="add_service_type" name="service_type" required>
                            <option value="FTTH">FTTH - Fiber to the Home</option>
                            <option value="VoIP">VoIP - Voice over IP</option>
                            <option value="IPTV">IPTV - Internet Protocol TV</option>
                            <option value="IoT">IoT - Internet of Things</option>
                            <option value="Femtocell">Femtocell - Mobile Network</option>
                            <option value="Other" selected>Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="add_contract_number" class="form-label">Numero Contratto</label>
                        <input type="text" class="form-control" id="add_contract_number" name="contract_number" placeholder="Es: CNTR-2025-001">
                        <small class="text-muted">Identificatore univoco del contratto</small>
                    </div>
                    <div class="mb-3">
                        <label for="add_sla_tier" class="form-label">SLA Tier</label>
                        <select class="form-select" id="add_sla_tier" name="sla_tier">
                            <option value="Standard" selected>Standard</option>
                            <option value="Premium">Premium</option>
                            <option value="Enterprise">Enterprise</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="add_service_status" class="form-label">Stato *</label>
                        <select class="form-select" id="add_service_status" name="status" required>
                            <option value="provisioned">Provisioned</option>
                            <option value="active" selected>Attivo</option>
                            <option value="suspended">Sospeso</option>
                            <option value="terminated">Terminato</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-success">Crea Servizio</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Service Modal -->
<div class="modal fade" id="editServiceModal" tabindex="-1" aria-labelledby="editServiceModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editServiceModalLabel">Modifica Servizio</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editServiceForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_service_name" class="form-label">Nome Servizio *</label>
                        <input type="text" class="form-control" id="edit_service_name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_service_type" class="form-label">Tipo Servizio *</label>
                        <select class="form-select" id="edit_service_type" name="service_type" required>
                            <option value="FTTH">FTTH - Fiber to the Home</option>
                            <option value="VoIP">VoIP - Voice over IP</option>
                            <option value="IPTV">IPTV - Internet Protocol TV</option>
                            <option value="IoT">IoT - Internet of Things</option>
                            <option value="Femtocell">Femtocell - Mobile Network</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_contract_number" class="form-label">Numero Contratto</label>
                        <input type="text" class="form-control" id="edit_contract_number" name="contract_number">
                    </div>
                    <div class="mb-3">
                        <label for="edit_sla_tier" class="form-label">SLA Tier</label>
                        <select class="form-select" id="edit_sla_tier" name="sla_tier">
                            <option value="Standard">Standard</option>
                            <option value="Premium">Premium</option>
                            <option value="Enterprise">Enterprise</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_service_status" class="form-label">Stato *</label>
                        <select class="form-select" id="edit_service_status" name="status" required>
                            <option value="provisioned">Provisioned</option>
                            <option value="active">Attivo</option>
                            <option value="suspended">Sospeso</option>
                            <option value="terminated">Terminato</option>
                        </select>
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

<!-- Delete Service Modal -->
<div class="modal fade" id="deleteServiceModal" tabindex="-1" aria-labelledby="deleteServiceModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteServiceModalLabel">Elimina Servizio</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="deleteServiceForm" method="POST">
                @csrf
                @method('DELETE')
                <div class="modal-body">
                    <p>Sei sicuro di voler eliminare il servizio <strong id="delete_service_name"></strong>?</p>
                    <div class="alert alert-warning" role="alert">
                        <i class="fas fa-exclamation-triangle"></i> Attenzione: Verranno rimosse le associazioni ai dispositivi di questo servizio (i dispositivi non verranno eliminati).
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-danger">Elimina Servizio</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Populate Edit Service Modal
document.getElementById('editServiceModal').addEventListener('show.bs.modal', function (event) {
    var button = event.relatedTarget;
    var serviceId = button.getAttribute('data-service-id');
    var serviceName = button.getAttribute('data-service-name');
    var serviceType = button.getAttribute('data-service-type');
    var contract = button.getAttribute('data-service-contract');
    var sla = button.getAttribute('data-service-sla');
    var status = button.getAttribute('data-service-status');
    
    document.getElementById('editServiceForm').action = '/acs/services/' + serviceId;
    document.getElementById('edit_service_name').value = serviceName;
    document.getElementById('edit_service_type').value = serviceType;
    document.getElementById('edit_contract_number').value = contract || '';
    document.getElementById('edit_sla_tier').value = sla || 'Standard';
    document.getElementById('edit_service_status').value = status;
});

// Populate Delete Service Modal
document.getElementById('deleteServiceModal').addEventListener('show.bs.modal', function (event) {
    var button = event.relatedTarget;
    var serviceId = button.getAttribute('data-service-id');
    var serviceName = button.getAttribute('data-service-name');
    
    document.getElementById('deleteServiceForm').action = '/acs/services/' + serviceId;
    document.getElementById('delete_service_name').textContent = serviceName;
});
</script>
@endsection
