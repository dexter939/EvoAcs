@extends('layouts.app')

@section('breadcrumb', 'Dashboard')
@section('page-title', 'Dashboard ACS')

@section('content')
<!-- Statistics Cards -->
<div class="row">
    <div class="col-xl-3 col-sm-6 mb-xl-0 mb-4">
        <div class="card">
            <div class="card-body p-3">
                <div class="row">
                    <div class="col-8">
                        <div class="numbers">
                            <p class="text-sm mb-0 text-capitalize font-weight-bold">Dispositivi Online</p>
                            <h5 class="font-weight-bolder mb-0">
                                {{ $stats['devices']['online'] ?? 0 }}
                                <span class="text-sm text-muted">/ {{ $stats['devices']['total'] ?? 0 }}</span>
                            </h5>
                        </div>
                    </div>
                    <div class="col-4 text-end">
                        <div class="icon icon-shape bg-gradient-success shadow text-center border-radius-md">
                            <i class="fas fa-router text-lg opacity-10" aria-hidden="true"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-sm-6 mb-xl-0 mb-4">
        <div class="card">
            <div class="card-body p-3">
                <div class="row">
                    <div class="col-8">
                        <div class="numbers">
                            <p class="text-sm mb-0 text-capitalize font-weight-bold">Task Pending</p>
                            <h5 class="font-weight-bolder mb-0">
                                {{ $stats['tasks']['pending'] ?? 0 }}
                                <span class="text-sm text-warning">in coda</span>
                            </h5>
                        </div>
                    </div>
                    <div class="col-4 text-end">
                        <div class="icon icon-shape bg-gradient-warning shadow text-center border-radius-md">
                            <i class="fas fa-tasks text-lg opacity-10" aria-hidden="true"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-sm-6 mb-xl-0 mb-4">
        <div class="card">
            <div class="card-body p-3">
                <div class="row">
                    <div class="col-8">
                        <div class="numbers">
                            <p class="text-sm mb-0 text-capitalize font-weight-bold">Firmware Deploy</p>
                            <h5 class="font-weight-bolder mb-0">
                                {{ $stats['firmware']['total_deployments'] ?? 0 }}
                                <span class="text-sm text-info">totali</span>
                            </h5>
                        </div>
                    </div>
                    <div class="col-4 text-end">
                        <div class="icon icon-shape bg-gradient-info shadow text-center border-radius-md">
                            <i class="fas fa-microchip text-lg opacity-10" aria-hidden="true"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-sm-6">
        <div class="card">
            <div class="card-body p-3">
                <div class="row">
                    <div class="col-8">
                        <div class="numbers">
                            <p class="text-sm mb-0 text-capitalize font-weight-bold">Task Completati</p>
                            <h5 class="font-weight-bolder mb-0">
                                {{ $stats['tasks']['completed'] ?? 0 }}
                                <span class="text-success text-sm font-weight-bolder">
                                    @if(($stats['tasks']['total'] ?? 0) > 0)
                                        {{ round(($stats['tasks']['completed'] / $stats['tasks']['total']) * 100) }}%
                                    @else
                                        0%
                                    @endif
                                </span>
                            </h5>
                        </div>
                    </div>
                    <div class="col-4 text-end">
                        <div class="icon icon-shape bg-gradient-primary shadow text-center border-radius-md">
                            <i class="fas fa-check-circle text-lg opacity-10" aria-hidden="true"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Devices & Tasks -->
<div class="row mt-4">
    <!-- Recent Devices -->
    <div class="col-lg-7 mb-lg-0 mb-4">
        <div class="card h-100">
            <div class="card-header pb-0">
                <h6>Ultimi Dispositivi Attivi</h6>
                <p class="text-sm">
                    <i class="fa fa-check text-success" aria-hidden="true"></i>
                    <span class="font-weight-bold ms-1">{{ count($stats['recent_devices'] ?? []) }}</span> dispositivi nelle ultime ore
                </p>
            </div>
            <div class="card-body p-3">
                <div class="table-responsive">
                    <table class="table align-items-center mb-0">
                        <thead>
                            <tr>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Dispositivo</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Stato</th>
                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Ultimo Inform</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($stats['recent_devices'] ?? [] as $device)
                            <tr>
                                <td>
                                    <div class="d-flex px-2 py-1">
                                        <div>
                                            <i class="fas fa-router text-primary me-2"></i>
                                        </div>
                                        <div class="d-flex flex-column justify-content-center">
                                            <h6 class="mb-0 text-sm">{{ $device->serial_number }}</h6>
                                            <p class="text-xs text-secondary mb-0">{{ $device->manufacturer }} {{ $device->model_name }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-sm bg-gradient-{{ $device->status == 'online' ? 'success' : 'secondary' }}">
                                        {{ ucfirst($device->status) }}
                                    </span>
                                </td>
                                <td class="align-middle text-center text-sm">
                                    <span class="text-xs font-weight-bold">{{ $device->last_inform ? $device->last_inform->diffForHumans() : 'Mai' }}</span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center text-sm text-muted py-4">
                                    Nessun dispositivo registrato
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Tasks -->
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header pb-0 p-3">
                <h6 class="mb-0">Task Recenti</h6>
            </div>
            <div class="card-body p-3">
                <ul class="list-group">
                    @forelse($stats['recent_tasks'] ?? [] as $task)
                    <li class="list-group-item border-0 d-flex justify-content-between ps-0 mb-2 border-radius-lg">
                        <div class="d-flex align-items-center">
                            <div class="icon icon-shape icon-sm me-3 bg-gradient-{{ $task->status == 'completed' ? 'success' : ($task->status == 'failed' ? 'danger' : 'warning') }} shadow text-center">
                                <i class="fas fa-{{ $task->status == 'completed' ? 'check' : ($task->status == 'failed' ? 'times' : 'clock') }} opacity-10"></i>
                            </div>
                            <div class="d-flex flex-column">
                                <h6 class="mb-1 text-dark text-sm">{{ ucfirst(str_replace('_', ' ', $task->task_type)) }}</h6>
                                <span class="text-xs">{{ $task->cpeDevice->serial_number ?? 'N/A' }}</span>
                            </div>
                        </div>
                        <div class="d-flex align-items-center text-{{ $task->status == 'completed' ? 'success' : ($task->status == 'failed' ? 'danger' : 'warning') }} text-gradient text-sm font-weight-bold">
                            {{ ucfirst($task->status) }}
                        </div>
                    </li>
                    @empty
                    <li class="list-group-item border-0 text-center text-sm text-muted py-4">
                        Nessun task recente
                    </li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Status Distribution -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header pb-0">
                <h6>Distribuzione Stati Dispositivi</h6>
            </div>
            <div class="card-body p-3">
                <div class="row">
                    <div class="col-md-3 col-6 text-center">
                        <div class="mb-3">
                            <h2 class="text-success">{{ $stats['devices']['online'] ?? 0 }}</h2>
                            <span class="text-sm">Online</span>
                        </div>
                    </div>
                    <div class="col-md-3 col-6 text-center">
                        <div class="mb-3">
                            <h2 class="text-secondary">{{ $stats['devices']['offline'] ?? 0 }}</h2>
                            <span class="text-sm">Offline</span>
                        </div>
                    </div>
                    <div class="col-md-3 col-6 text-center">
                        <div class="mb-3">
                            <h2 class="text-warning">{{ $stats['devices']['provisioning'] ?? 0 }}</h2>
                            <span class="text-sm">Provisioning</span>
                        </div>
                    </div>
                    <div class="col-md-3 col-6 text-center">
                        <div class="mb-3">
                            <h2 class="text-danger">{{ $stats['devices']['error'] ?? 0 }}</h2>
                            <span class="text-sm">Errore</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Auto-refresh dashboard every 30 seconds
setInterval(function() {
    location.reload();
}, 30000);
</script>
@endpush

@endsection
