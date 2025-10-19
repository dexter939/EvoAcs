@extends('layouts.app')

@section('breadcrumb', 'Profilo Utente')
@section('page-title', 'Il Mio Profilo')

@section('content')
<div class="row">
    <div class="col-md-8">
        <!-- Profile Information Card -->
        <div class="card mb-4">
            <div class="card-header pb-0">
                <h6><i class="fas fa-user me-2"></i>Informazioni Profilo</h6>
            </div>
            <div class="card-body">
                <form id="profileInfoForm">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Nome Completo</label>
                            <input type="text" class="form-control" id="name" value="{{ $user->name }}" required>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" value="{{ $user->email }}" required>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn bg-gradient-primary">
                            <i class="fas fa-save me-1"></i>Salva Modifiche
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Change Password Card -->
        <div class="card mb-4">
            <div class="card-header pb-0">
                <h6><i class="fas fa-lock me-2"></i>Modifica Password</h6>
            </div>
            <div class="card-body">
                <form id="changePasswordForm">
                    <div class="mb-3">
                        <label class="form-label">Password Attuale</label>
                        <input type="password" class="form-control" id="current_password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nuova Password</label>
                        <input type="password" class="form-control" id="password" required>
                        <small class="text-muted">Minimo 8 caratteri</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Conferma Nuova Password</label>
                        <input type="password" class="form-control" id="password_confirmation" required>
                    </div>
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn bg-gradient-warning">
                            <i class="fas fa-key me-1"></i>Aggiorna Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <!-- User Info Card -->
        <div class="card">
            <div class="card-header pb-0 p-3">
                <h6 class="mb-0">Informazioni Account</h6>
            </div>
            <div class="card-body p-3">
                <ul class="list-group">
                    <li class="list-group-item border-0 ps-0 pt-0 text-sm">
                        <strong class="text-dark">Ruolo:</strong> &nbsp;
                        @foreach($user->roles as $role)
                            <span class="badge bg-gradient-info">{{ $role->name }}</span>
                        @endforeach
                    </li>
                    <li class="list-group-item border-0 ps-0 text-sm">
                        <strong class="text-dark">Email:</strong> &nbsp; {{ $user->email }}
                    </li>
                    <li class="list-group-item border-0 ps-0 text-sm">
                        <strong class="text-dark">Registrato il:</strong> &nbsp; {{ $user->created_at->format('d/m/Y H:i') }}
                    </li>
                    <li class="list-group-item border-0 ps-0 text-sm">
                        <strong class="text-dark">Ultimo accesso:</strong> &nbsp; {{ $user->updated_at->diffForHumans() }}
                    </li>
                </ul>
            </div>
        </div>

        <!-- Permissions Card -->
        @if($user->roles->first())
        <div class="card mt-4">
            <div class="card-header pb-0 p-3">
                <h6 class="mb-0">I Miei Permessi</h6>
            </div>
            <div class="card-body p-3">
                <div style="max-height: 300px; overflow-y: auto;">
                    @foreach($user->roles->first()->permissions->groupBy('category') as $category => $permissions)
                        <div class="mb-3">
                            <h6 class="text-xs text-uppercase font-weight-bolder opacity-6">{{ ucfirst($category) }}</h6>
                            @foreach($permissions as $perm)
                                <div class="text-xs mb-1">
                                    <i class="fas fa-check text-success me-1"></i>{{ $perm->name }}
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script src="/assets/js/vendor/jquery-3.7.1.min.js"></script>
<script>
// Update Profile Information
$('#profileInfoForm').on('submit', function(e) {
    e.preventDefault();
    
    $.ajax({
        url: '{{ route("acs.profile.update-info") }}',
        method: 'PUT',
        data: {
            name: $('#name').val(),
            email: $('#email').val(),
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            alert('✓ ' + response.message);
        },
        error: function(xhr) {
            alert('Errore: ' + (xhr.responseJSON?.message || 'Operazione fallita'));
        }
    });
});

// Change Password
$('#changePasswordForm').on('submit', function(e) {
    e.preventDefault();
    
    if ($('#password').val() !== $('#password_confirmation').val()) {
        alert('Le password non corrispondono');
        return;
    }
    
    $.ajax({
        url: '{{ route("acs.profile.update-password") }}',
        method: 'PUT',
        data: {
            current_password: $('#current_password').val(),
            password: $('#password').val(),
            password_confirmation: $('#password_confirmation').val(),
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            alert('✓ ' + response.message);
            $('#changePasswordForm')[0].reset();
        },
        error: function(xhr) {
            const errors = xhr.responseJSON?.errors;
            if (errors?.current_password) {
                alert('Errore: Password attuale non corretta');
            } else {
                alert('Errore: ' + (xhr.responseJSON?.message || 'Operazione fallita'));
            }
        }
    });
});
</script>
@endpush
