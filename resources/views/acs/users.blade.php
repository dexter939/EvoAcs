@extends('layouts.app')

@section('breadcrumb', 'Gestione Utenti')
@section('page-title', 'Gestione Utenti & RBAC')

@push('styles')
<link href="/assets/css/vendor/jquery.dataTables.min.css" rel="stylesheet" />
<style>
.role-badge {
    padding: 4px 12px;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 600;
}
.role-super-admin { background: linear-gradient(310deg, #cb0c9f 0%, #cb0c9f 100%); color: white; }
.role-admin { background: linear-gradient(310deg, #17c1e8 0%, #17c1e8 100%); color: white; }
.role-operator { background: linear-gradient(310deg, #82d616 0%, #82d616 100%); color: white; }
.role-technician { background: linear-gradient(310deg, #fbcf33 0%, #fbcf33 100%); color: #344767; }
.role-viewer { background: linear-gradient(310deg, #ea0606 0%, #ea0606 100%); color: white; }
</style>
@endpush

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card mb-4">
            <div class="card-header pb-0 d-flex justify-content-between align-items-center">
                <h6><i class="fas fa-users me-2"></i>Utenti Sistema</h6>
                <button class="btn bg-gradient-success btn-sm mb-0" onclick="showCreateModal()">
                    <i class="fas fa-plus me-1"></i>NUOVO UTENTE
                </button>
            </div>
            <div class="card-body px-0 pt-0 pb-2">
                <div class="table-responsive p-0">
                    <table id="usersTable" class="table align-items-center mb-0">
                        <thead>
                            <tr>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Utente</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Email</th>
                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Ruolo</th>
                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Creato</th>
                                <th class="text-secondary opacity-7"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($users as $user)
                            <tr>
                                <td>
                                    <div class="d-flex px-2 py-1">
                                        <div class="d-flex flex-column justify-content-center">
                                            <h6 class="mb-0 text-sm">{{ $user->name }}</h6>
                                        </div>
                                    </div>
                                </td>
                                <td><p class="text-xs text-secondary mb-0">{{ $user->email }}</p></td>
                                <td class="align-middle text-center text-sm">
                                    @foreach($user->roles as $role)
                                        <span class="role-badge role-{{ $role->slug }}">{{ $role->name }}</span>
                                    @endforeach
                                </td>
                                <td class="align-middle text-center"><span class="text-secondary text-xs font-weight-bold">{{ $user->created_at->format('d/m/Y') }}</span></td>
                                <td class="align-middle">
                                    <button class="btn btn-link text-secondary mb-0" onclick='editUser(@json($user))'>
                                        <i class="fas fa-pencil-alt text-xs"></i> Modifica
                                    </button>
                                    <button class="btn btn-link text-danger mb-0" onclick="deleteUser({{ $user->id }}, '{{ $user->name }}')">
                                        <i class="fas fa-trash text-xs"></i> Elimina
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create/Edit User Modal -->
<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Nuovo Utente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="userForm">
                    <input type="hidden" id="userId">
                    <div class="mb-3">
                        <label>Nome Completo</label>
                        <input type="text" class="form-control" id="userName" required>
                    </div>
                    <div class="mb-3">
                        <label>Email</label>
                        <input type="email" class="form-control" id="userEmail" required>
                    </div>
                    <div class="mb-3">
                        <label>Password <span class="text-xs text-secondary" id="passwordHint">(lascia vuoto per non modificare)</span></label>
                        <input type="password" class="form-control" id="userPassword">
                    </div>
                    <div class="mb-3">
                        <label>Conferma Password</label>
                        <input type="password" class="form-control" id="userPasswordConfirm">
                    </div>
                    <div class="mb-3">
                        <label>Ruolo</label>
                        <select class="form-select" id="userRole" required>
                            @foreach($roles as $role)
                                <option value="{{ $role->slug }}">{{ $role->name }} (Level {{ $role->level }})</option>
                            @endforeach
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                <button type="button" class="btn bg-gradient-success" onclick="saveUser()">Salva</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="/assets/js/vendor/jquery-3.7.1.min.js"></script>
<script src="/assets/js/vendor/jquery.dataTables.min.js"></script>
<script>
$(document).ready(function() {
    $('#usersTable').DataTable({
        language: { url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/it-IT.json' },
        order: [[3, 'desc']]
    });
});

function showCreateModal() {
    $('#modalTitle').text('Nuovo Utente');
    $('#userForm')[0].reset();
    $('#userId').val('');
    $('#passwordHint').hide();
    $('#userPassword').prop('required', true);
    $('#userModal').modal('show');
}

function editUser(user) {
    $('#modalTitle').text('Modifica Utente');
    $('#userId').val(user.id);
    $('#userName').val(user.name);
    $('#userEmail').val(user.email);
    $('#userRole').val(user.roles[0]?.slug || 'viewer');
    $('#userPassword').val('').prop('required', false);
    $('#userPasswordConfirm').val('');
    $('#passwordHint').show();
    $('#userModal').modal('show');
}

function saveUser() {
    const userId = $('#userId').val();
    const isEdit = userId !== '';
    const url = isEdit ? `/acs/users/${userId}` : '/acs/users';
    const method = isEdit ? 'PUT' : 'POST';

    const data = {
        name: $('#userName').val(),
        email: $('#userEmail').val(),
        role: $('#userRole').val(),
        _token: '{{ csrf_token() }}'
    };

    if ($('#userPassword').val()) {
        data.password = $('#userPassword').val();
        data.password_confirmation = $('#userPasswordConfirm').val();
    }

    $.ajax({
        url: url,
        method: method,
        data: data,
        success: function(response) {
            $('#userModal').modal('hide');
            location.reload();
        },
        error: function(xhr) {
            alert('Errore: ' + (xhr.responseJSON?.message || 'Operazione fallita'));
        }
    });
}

function deleteUser(id, name) {
    if (!confirm(`Sei sicuro di voler eliminare l'utente "${name}"?`)) return;

    $.ajax({
        url: `/acs/users/${id}`,
        method: 'DELETE',
        data: { _token: '{{ csrf_token() }}' },
        success: function() {
            location.reload();
        },
        error: function(xhr) {
            alert('Errore: ' + (xhr.responseJSON?.message || 'Operazione fallita'));
        }
    });
}
</script>
@endpush
