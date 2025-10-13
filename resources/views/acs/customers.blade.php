@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header pb-0 d-flex justify-content-between align-items-center">
                    <h6>Clienti</h6>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addCustomerModal">
                            <i class="fas fa-plus"></i> Nuovo Cliente
                        </button>
                        <form method="GET" action="{{ route('acs.customers') }}" class="d-flex gap-2">
                            <input type="text" name="search" class="form-control form-control-sm" 
                                   placeholder="Cerca cliente..." value="{{ request('search') }}">
                            <select name="status" class="form-select form-select-sm">
                                <option value="">Tutti gli stati</option>
                                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Attivo</option>
                                <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inattivo</option>
                                <option value="suspended" {{ request('status') == 'suspended' ? 'selected' : '' }}>Sospeso</option>
                                <option value="terminated" {{ request('status') == 'terminated' ? 'selected' : '' }}>Terminato</option>
                            </select>
                            <button type="submit" class="btn btn-sm btn-primary">Filtra</button>
                            @if(request('search') || request('status'))
                                <a href="{{ route('acs.customers') }}" class="btn btn-sm btn-secondary">Reset</a>
                            @endif
                        </form>
                    </div>
                </div>
                <div class="card-body px-0 pt-0 pb-2">
                    <div class="table-responsive p-0">
                        <table class="table align-items-center mb-0">
                            <thead>
                                <tr>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Cliente</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">ID Esterno</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Email Contatto</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Servizi</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Stato</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Data Creazione</th>
                                    <th class="text-secondary opacity-7"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($customers as $customer)
                                <tr>
                                    <td>
                                        <div class="d-flex px-2 py-1">
                                            <div class="d-flex flex-column justify-content-center">
                                                <h6 class="mb-0 text-sm">{{ $customer->name }}</h6>
                                                <p class="text-xs text-secondary mb-0">ID: {{ $customer->id }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <p class="text-xs font-weight-bold mb-0">{{ $customer->external_id ?? '-' }}</p>
                                    </td>
                                    <td>
                                        <p class="text-xs font-weight-bold mb-0">{{ $customer->contact_email ?? '-' }}</p>
                                    </td>
                                    <td class="align-middle text-center">
                                        <span class="badge badge-sm bg-gradient-info">{{ $customer->services_count }} servizi</span>
                                    </td>
                                    <td class="align-middle text-center text-sm">
                                        @if($customer->status == 'active')
                                            <span class="badge badge-sm bg-gradient-success">Attivo</span>
                                        @elseif($customer->status == 'inactive')
                                            <span class="badge badge-sm bg-gradient-secondary">Inattivo</span>
                                        @elseif($customer->status == 'suspended')
                                            <span class="badge badge-sm bg-gradient-warning">Sospeso</span>
                                        @else
                                            <span class="badge badge-sm bg-gradient-danger">Terminato</span>
                                        @endif
                                    </td>
                                    <td class="align-middle text-center">
                                        <span class="text-secondary text-xs font-weight-bold">{{ $customer->created_at->format('d/m/Y H:i') }}</span>
                                    </td>
                                    <td class="align-middle">
                                        <a href="{{ route('acs.customers.detail', $customer->id) }}" 
                                           class="btn btn-link text-secondary mb-0 px-2" data-toggle="tooltip" data-original-title="Vedi dettagli">
                                            <i class="fa fa-eye text-xs"></i>
                                        </a>
                                        <button type="button" class="btn btn-link text-dark mb-0 px-2" 
                                                data-bs-toggle="modal" data-bs-target="#editCustomerModal"
                                                data-customer-id="{{ $customer->id }}"
                                                data-customer-name="{{ $customer->name }}"
                                                data-customer-external-id="{{ $customer->external_id }}"
                                                data-customer-email="{{ $customer->contact_email }}"
                                                data-customer-timezone="{{ $customer->timezone }}"
                                                data-customer-status="{{ $customer->status }}"
                                                data-toggle="tooltip" data-original-title="Modifica">
                                            <i class="fas fa-pencil-alt text-xs"></i>
                                        </button>
                                        <button type="button" class="btn btn-link text-danger mb-0 px-2" 
                                                data-bs-toggle="modal" data-bs-target="#deleteCustomerModal"
                                                data-customer-id="{{ $customer->id }}"
                                                data-customer-name="{{ $customer->name }}"
                                                data-toggle="tooltip" data-original-title="Elimina">
                                            <i class="fas fa-trash text-xs"></i>
                                        </button>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <p class="text-sm text-secondary mb-0">Nessun cliente trovato</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($customers->hasPages())
                    <div class="d-flex justify-content-center mt-3">
                        {{ $customers->links() }}
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Customer Modal -->
<div class="modal fade" id="addCustomerModal" tabindex="-1" aria-labelledby="addCustomerModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addCustomerModalLabel">Nuovo Cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('acs.customers.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="add_name" class="form-label">Nome Cliente *</label>
                        <input type="text" class="form-control" id="add_name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="add_external_id" class="form-label">ID Esterno</label>
                        <input type="text" class="form-control" id="add_external_id" name="external_id" placeholder="Es: CUST-12345">
                        <small class="text-muted">Identificatore univoco per sistemi esterni</small>
                    </div>
                    <div class="mb-3">
                        <label for="add_contact_email" class="form-label">Email Contatto *</label>
                        <input type="email" class="form-control" id="add_contact_email" name="contact_email" required>
                    </div>
                    <div class="mb-3">
                        <label for="add_timezone" class="form-label">Fuso Orario</label>
                        <select class="form-select" id="add_timezone" name="timezone">
                            <option value="Europe/Rome" selected>Europe/Rome</option>
                            <option value="Europe/London">Europe/London</option>
                            <option value="America/New_York">America/New_York</option>
                            <option value="Asia/Tokyo">Asia/Tokyo</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="add_status" class="form-label">Stato *</label>
                        <select class="form-select" id="add_status" name="status" required>
                            <option value="active" selected>Attivo</option>
                            <option value="inactive">Inattivo</option>
                            <option value="suspended">Sospeso</option>
                            <option value="terminated">Terminato</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-success">Crea Cliente</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Customer Modal -->
<div class="modal fade" id="editCustomerModal" tabindex="-1" aria-labelledby="editCustomerModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editCustomerModalLabel">Modifica Cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editCustomerForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Nome Cliente *</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_external_id" class="form-label">ID Esterno</label>
                        <input type="text" class="form-control" id="edit_external_id" name="external_id">
                    </div>
                    <div class="mb-3">
                        <label for="edit_contact_email" class="form-label">Email Contatto *</label>
                        <input type="email" class="form-control" id="edit_contact_email" name="contact_email" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_timezone" class="form-label">Fuso Orario</label>
                        <select class="form-select" id="edit_timezone" name="timezone">
                            <option value="Europe/Rome">Europe/Rome</option>
                            <option value="Europe/London">Europe/London</option>
                            <option value="America/New_York">America/New_York</option>
                            <option value="Asia/Tokyo">Asia/Tokyo</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_status" class="form-label">Stato *</label>
                        <select class="form-select" id="edit_status" name="status" required>
                            <option value="active">Attivo</option>
                            <option value="inactive">Inattivo</option>
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

<!-- Delete Customer Modal -->
<div class="modal fade" id="deleteCustomerModal" tabindex="-1" aria-labelledby="deleteCustomerModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteCustomerModalLabel">Elimina Cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="deleteCustomerForm" method="POST">
                @csrf
                @method('DELETE')
                <div class="modal-body">
                    <p>Sei sicuro di voler eliminare il cliente <strong id="delete_customer_name"></strong>?</p>
                    <div class="alert alert-warning" role="alert">
                        <i class="fas fa-exclamation-triangle"></i> Attenzione: Verranno eliminati anche tutti i servizi e le associazioni ai dispositivi di questo cliente.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-danger">Elimina Cliente</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Populate Edit Modal
document.getElementById('editCustomerModal').addEventListener('show.bs.modal', function (event) {
    var button = event.relatedTarget;
    var customerId = button.getAttribute('data-customer-id');
    var customerName = button.getAttribute('data-customer-name');
    var externalId = button.getAttribute('data-customer-external-id');
    var email = button.getAttribute('data-customer-email');
    var timezone = button.getAttribute('data-customer-timezone');
    var status = button.getAttribute('data-customer-status');
    
    document.getElementById('editCustomerForm').action = '/acs/customers/' + customerId;
    document.getElementById('edit_name').value = customerName;
    document.getElementById('edit_external_id').value = externalId || '';
    document.getElementById('edit_contact_email').value = email || '';
    document.getElementById('edit_timezone').value = timezone || 'Europe/Rome';
    document.getElementById('edit_status').value = status;
});

// Populate Delete Modal
document.getElementById('deleteCustomerModal').addEventListener('show.bs.modal', function (event) {
    var button = event.relatedTarget;
    var customerId = button.getAttribute('data-customer-id');
    var customerName = button.getAttribute('data-customer-name');
    
    document.getElementById('deleteCustomerForm').action = '/acs/customers/' + customerId;
    document.getElementById('delete_customer_name').textContent = customerName;
});
</script>
@endsection
