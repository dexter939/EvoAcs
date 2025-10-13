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
                            <li class="breadcrumb-item text-sm"><a class="opacity-5 text-dark" href="{{ route('acs.customers.detail', $service->customer->id) }}">{{ $service->customer->name }}</a></li>
                            <li class="breadcrumb-item text-sm text-dark active" aria-current="page">{{ $service->name }}</li>
                        </ol>
                    </nav>
                    <h6 class="mt-3">Dettaglio Servizio</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-uppercase text-xs font-weight-bolder opacity-7">Informazioni Servizio</h6>
                            <ul class="list-group">
                                <li class="list-group-item border-0 ps-0 pt-0 text-sm"><strong>Nome:</strong> {{ $service->name }}</li>
                                <li class="list-group-item border-0 ps-0 text-sm"><strong>Cliente:</strong> <a href="{{ route('acs.customers.detail', $service->customer->id) }}">{{ $service->customer->name }}</a></li>
                                <li class="list-group-item border-0 ps-0 text-sm"><strong>Tipo Servizio:</strong> <span class="badge badge-sm bg-gradient-primary">{{ $service->service_type }}</span></li>
                                <li class="list-group-item border-0 ps-0 text-sm"><strong>Numero Contratto:</strong> {{ $service->contract_number ?? '-' }}</li>
                                <li class="list-group-item border-0 ps-0 text-sm"><strong>SLA Tier:</strong> {{ $service->sla_tier ?? '-' }}</li>
                                <li class="list-group-item border-0 ps-0 text-sm">
                                    <strong>Stato:</strong>
                                    @if($service->status == 'active')
                                        <span class="badge badge-sm bg-gradient-success">Attivo</span>
                                    @elseif($service->status == 'provisioned')
                                        <span class="badge badge-sm bg-gradient-info">Provisioned</span>
                                    @elseif($service->status == 'suspended')
                                        <span class="badge badge-sm bg-gradient-warning">Sospeso</span>
                                    @else
                                        <span class="badge badge-sm bg-gradient-danger">Terminato</span>
                                    @endif
                                </li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-uppercase text-xs font-weight-bolder opacity-7">Date e Statistiche</h6>
                            <ul class="list-group">
                                <li class="list-group-item border-0 ps-0 pt-0 text-sm"><strong>Data Attivazione:</strong> {{ $service->activation_at ? $service->activation_at->format('d/m/Y H:i') : '-' }}</li>
                                <li class="list-group-item border-0 ps-0 text-sm"><strong>Data Terminazione:</strong> {{ $service->termination_at ? $service->termination_at->format('d/m/Y H:i') : '-' }}</li>
                                <li class="list-group-item border-0 ps-0 text-sm"><strong>Dispositivi Associati:</strong> {{ $service->cpeDevices->count() }}</li>
                                <li class="list-group-item border-0 ps-0 text-sm"><strong>Creato il:</strong> {{ $service->created_at->format('d/m/Y H:i') }}</li>
                                <li class="list-group-item border-0 ps-0 text-sm"><strong>Ultimo Aggiornamento:</strong> {{ $service->updated_at->format('d/m/Y H:i') }}</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header pb-0">
                    <h6>Dispositivi del Servizio</h6>
                </div>
                <div class="card-body px-0 pt-0 pb-2">
                    <div class="table-responsive p-0">
                        <table class="table align-items-center mb-0">
                            <thead>
                                <tr>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Dispositivo</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Modello</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Protocollo</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">IP Address</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Stato</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Ultimo Inform</th>
                                    <th class="text-secondary opacity-7"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($service->cpeDevices as $device)
                                <tr>
                                    <td>
                                        <div class="d-flex px-2 py-1">
                                            <div class="d-flex flex-column justify-content-center">
                                                <h6 class="mb-0 text-sm">{{ $device->serial_number }}</h6>
                                                <p class="text-xs text-secondary mb-0">{{ $device->manufacturer }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <p class="text-xs font-weight-bold mb-0">{{ $device->model_name ?? '-' }}</p>
                                        <p class="text-xs text-secondary mb-0">{{ $device->software_version ?? '-' }}</p>
                                    </td>
                                    <td>
                                        <span class="badge badge-sm bg-gradient-{{ $device->protocol_type == 'tr069' ? 'primary' : 'info' }}">
                                            {{ strtoupper($device->protocol_type) }}
                                        </span>
                                    </td>
                                    <td>
                                        <p class="text-xs font-weight-bold mb-0">{{ $device->ip_address ?? '-' }}</p>
                                    </td>
                                    <td class="align-middle text-center text-sm">
                                        @if($device->status == 'online')
                                            <span class="badge badge-sm bg-gradient-success">Online</span>
                                        @elseif($device->status == 'offline')
                                            <span class="badge badge-sm bg-gradient-secondary">Offline</span>
                                        @elseif($device->status == 'provisioning')
                                            <span class="badge badge-sm bg-gradient-warning">Provisioning</span>
                                        @else
                                            <span class="badge badge-sm bg-gradient-danger">Error</span>
                                        @endif
                                    </td>
                                    <td class="align-middle text-center">
                                        <span class="text-secondary text-xs font-weight-bold">
                                            {{ $device->last_inform ? $device->last_inform->diffForHumans() : 'Mai' }}
                                        </span>
                                    </td>
                                    <td class="align-middle">
                                        <a href="{{ route('devices.show', $device->id) }}" 
                                           class="btn btn-link text-secondary mb-0" data-toggle="tooltip" data-original-title="Vedi dettagli">
                                            <i class="fa fa-eye text-xs"></i> Dettagli
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <p class="text-sm text-secondary mb-0">Nessun dispositivo associato a questo servizio</p>
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
