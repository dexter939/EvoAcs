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
                <div class="card-header pb-0">
                    <h6>Servizi del Cliente</h6>
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
                                           class="btn btn-link text-secondary mb-0" data-toggle="tooltip" data-original-title="Vedi dettagli">
                                            <i class="fa fa-eye text-xs"></i> Dettagli
                                        </a>
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
@endsection
