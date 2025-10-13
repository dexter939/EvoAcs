@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header pb-0 d-flex justify-content-between align-items-center">
                    <h6>Clienti</h6>
                    <div class="d-flex gap-2">
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
                                           class="btn btn-link text-secondary mb-0" data-toggle="tooltip" data-original-title="Vedi dettagli">
                                            <i class="fa fa-eye text-xs"></i> Dettagli
                                        </a>
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
@endsection
