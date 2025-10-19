@extends('layouts.app')

@section('breadcrumb', 'Gestione Ruoli')
@section('page-title', 'Gestione Ruoli & Permessi')

@push('styles')
<link href="/assets/css/vendor/jquery.dataTables.min.css" rel="stylesheet" />
<style>
.permissions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 10px;
    max-height: 400px;
    overflow-y: auto;
}
.permission-category {
    font-weight: 600;
    color: #344767;
    margin-top: 15px;
    margin-bottom: 8px;
    font-size: 0.875rem;
}
.system-role-badge {
    background: linear-gradient(310deg, #ea0606 0%, #ea0606 100%);
    color: white;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 0.65rem;
    font-weight: 600;
}
</style>
@endpush

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card mb-4">
            <div class="card-header pb-0 d-flex justify-content-between align-items-center">
                <h6><i class="fas fa-user-shield me-2"></i>Ruoli Sistema</h6>
                <button class="btn bg-gradient-success btn-sm mb-0" onclick="showCreateModal()">
                    <i class="fas fa-plus me-1"></i>NUOVO RUOLO
                </button>
            </div>
            <div class="card-body px-0 pt-0 pb-2">
                <div class="table-responsive p-0">
                    <table id="rolesTable" class="table align-items-center mb-0">
                        <thead>
                            <tr>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Ruolo</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Slug</th>
                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Livello</th>
                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Permessi</th>
                                <th class="text-secondary opacity-7"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($roles as $role)
                            <tr>
                                <td>
                                    <div class="d-flex px-2 py-1 align-items-center">
                                        <h6 class="mb-0 text-sm">{{ $role->name }}</h6>
                                        @if(in_array($role->slug, ['super-admin', 'admin', 'operator', 'technician', 'viewer']))
                                            <span class="system-role-badge ms-2">SISTEMA</span>
                                        @endif
                                    </div>
                                </td>
                                <td><p class="text-xs text-secondary mb-0">{{ $role->slug }}</p></td>
                                <td class="align-middle text-center"><span class="badge badge-sm bg-gradient-info">{{ $role->level }}</span></td>
                                <td class="align-middle text-center"><span class="text-secondary text-xs font-weight-bold">{{ $role->permissions->count() }}</span></td>
                                <td class="align-middle">
                                    <button class="btn btn-link text-secondary mb-0" onclick='editRole(@json($role))'>
                                        <i class="fas fa-pencil-alt text-xs"></i> Modifica
                                    </button>
                                    <button class="btn btn-link text-primary mb-0" onclick='managePermissions(@json($role))'>
                                        <i class="fas fa-key text-xs"></i> Permessi
                                    </button>
                                    @if(!in_array($role->slug, ['super-admin', 'admin', 'operator', 'technician', 'viewer']))
                                        <button class="btn btn-link text-danger mb-0" onclick="deleteRole({{ $role->id }}, '{{ $role->name }}')">
                                            <i class="fas fa-trash text-xs"></i> Elimina
                                        </button>
                                    @endif
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

<!-- Create/Edit Role Modal -->
<div class="modal fade" id="roleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="roleModalTitle">Nuovo Ruolo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="roleForm">
                    <input type="hidden" id="roleId">
                    <div class="mb-3">
                        <label>Nome Ruolo</label>
                        <input type="text" class="form-control" id="roleName" required>
                    </div>
                    <div class="mb-3">
                        <label>Slug <small class="text-secondary">(es: custom-role)</small></label>
                        <input type="text" class="form-control" id="roleSlug" required>
                    </div>
                    <div class="mb-3">
                        <label>Livello <small class="text-secondary">(1-100, maggiore = più privilegi)</small></label>
                        <input type="number" class="form-control" id="roleLevel" min="1" max="100" required>
                    </div>
                    <div class="mb-3">
                        <label>Descrizione</label>
                        <textarea class="form-control" id="roleDescription" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                <button type="button" class="btn bg-gradient-success" onclick="saveRole()">Salva</button>
            </div>
        </div>
    </div>
</div>

<!-- Manage Permissions Modal -->
<div class="modal fade" id="permissionsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Gestione Permessi: <span id="permissionsRoleName"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="permissionsRoleId">
                <div id="permissionsContainer">
                    @php
                        $grouped = $permissions->groupBy('category');
                    @endphp
                    @foreach($grouped as $category => $perms)
                        <div class="permission-category">
                            <i class="fas fa-folder me-1"></i>{{ ucfirst($category) }}
                        </div>
                        <div class="permissions-grid">
                            @foreach($perms as $perm)
                                <div class="form-check">
                                    <input class="form-check-input permission-checkbox" type="checkbox" 
                                           value="{{ $perm->id }}" id="perm_{{ $perm->id }}">
                                    <label class="form-check-label text-sm" for="perm_{{ $perm->id }}">
                                        {{ $perm->name }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                <button type="button" class="btn bg-gradient-success" onclick="savePermissions()">Salva Permessi</button>
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
    $('#rolesTable').DataTable({
        language: { url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/it-IT.json' },
        order: [[2, 'desc']]
    });
});

function showCreateModal() {
    $('#roleModalTitle').text('Nuovo Ruolo');
    $('#roleForm')[0].reset();
    $('#roleId').val('');
    $('#roleModal').modal('show');
}

function editRole(role) {
    $('#roleModalTitle').text('Modifica Ruolo');
    $('#roleId').val(role.id);
    $('#roleName').val(role.name);
    $('#roleSlug').val(role.slug);
    $('#roleLevel').val(role.level);
    $('#roleDescription').val(role.description);
    $('#roleModal').modal('show');
}

function saveRole() {
    const roleId = $('#roleId').val();
    const isEdit = roleId !== '';
    const url = isEdit ? `/acs/roles/${roleId}` : '/acs/roles';
    const method = isEdit ? 'PUT' : 'POST';

    $.ajax({
        url: url,
        method: method,
        data: {
            name: $('#roleName').val(),
            slug: $('#roleSlug').val(),
            level: $('#roleLevel').val(),
            description: $('#roleDescription').val(),
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            $('#roleModal').modal('hide');
            location.reload();
        },
        error: function(xhr) {
            alert('Errore: ' + (xhr.responseJSON?.message || 'Operazione fallita'));
        }
    });
}

function deleteRole(id, name) {
    if (!confirm(`Sei sicuro di voler eliminare il ruolo "${name}"?`)) return;

    $.ajax({
        url: `/acs/roles/${id}`,
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

function managePermissions(role) {
    $('#permissionsRoleId').val(role.id);
    $('#permissionsRoleName').text(role.name);
    
    $('.permission-checkbox').prop('checked', false);
    role.permissions.forEach(function(perm) {
        $('#perm_' + perm.id).prop('checked', true);
    });
    
    $('#permissionsModal').modal('show');
}

function savePermissions() {
    const roleId = $('#permissionsRoleId').val();
    const selectedPermissions = $('.permission-checkbox:checked').map(function() {
        return $(this).val();
    }).get();

    $.ajax({
        url: `/acs/roles/${roleId}/assign-permissions`,
        method: 'POST',
        data: {
            permissions: selectedPermissions,
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            $('#permissionsModal').modal('hide');
            location.reload();
        },
        error: function(xhr) {
            alert('Errore: ' + (xhr.responseJSON?.message || 'Operazione fallita'));
        }
    });
}
</script>
@endpush
