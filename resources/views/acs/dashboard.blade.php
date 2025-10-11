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
                            <i class="fas fa-network-wired text-lg opacity-10" aria-hidden="true"></i>
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

<!-- Second Row Stats -->
<div class="row mt-4">
    <div class="col-xl-3 col-sm-6 mb-xl-0 mb-4">
        <div class="card">
            <div class="card-body p-3">
                <div class="row">
                    <div class="col-8">
                        <div class="numbers">
                            <p class="text-sm mb-0 text-capitalize font-weight-bold">Test Diagnostici</p>
                            <h5 class="font-weight-bolder mb-0">
                                {{ $stats['diagnostics']['completed'] ?? 0 }}
                                <span class="text-sm text-muted">/ {{ $stats['diagnostics']['total'] ?? 0 }}</span>
                            </h5>
                        </div>
                    </div>
                    <div class="col-4 text-end">
                        <div class="icon icon-shape bg-gradient-danger shadow text-center border-radius-md">
                            <i class="fas fa-stethoscope text-lg opacity-10" aria-hidden="true"></i>
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
                            <p class="text-sm mb-0 text-capitalize font-weight-bold">Profili Attivi</p>
                            <h5 class="font-weight-bolder mb-0">
                                {{ $stats['profiles_active'] ?? 0 }}
                            </h5>
                        </div>
                    </div>
                    <div class="col-4 text-end">
                        <div class="icon icon-shape bg-gradient-dark shadow text-center border-radius-md">
                            <i class="fas fa-cogs text-lg opacity-10" aria-hidden="true"></i>
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
                            <p class="text-sm mb-0 text-capitalize font-weight-bold">Versioni Firmware</p>
                            <h5 class="font-weight-bolder mb-0">
                                {{ $stats['firmware_versions'] ?? 0 }}
                            </h5>
                        </div>
                    </div>
                    <div class="col-4 text-end">
                        <div class="icon icon-shape bg-gradient-info shadow text-center border-radius-md">
                            <i class="fas fa-code-branch text-lg opacity-10" aria-hidden="true"></i>
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
                            <p class="text-sm mb-0 text-capitalize font-weight-bold">Parametri TR-181</p>
                            <h5 class="font-weight-bolder mb-0">
                                {{ $stats['unique_parameters'] ?? 0 }}
                            </h5>
                        </div>
                    </div>
                    <div class="col-4 text-end">
                        <div class="icon icon-shape bg-gradient-secondary shadow text-center border-radius-md">
                            <i class="fas fa-list-ul text-lg opacity-10" aria-hidden="true"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Protocol Statistics Row -->
<div class="row mt-4">
    <div class="col-xl-3 col-sm-6 mb-xl-0 mb-4">
        <div class="card">
            <div class="card-body p-3">
                <div class="row">
                    <div class="col-8">
                        <div class="numbers">
                            <p class="text-sm mb-0 text-capitalize font-weight-bold">Dispositivi TR-069</p>
                            <h5 class="font-weight-bolder mb-0">
                                {{ $stats['devices']['tr069'] ?? 0 }}
                                <span class="text-sm text-muted">CWMP</span>
                            </h5>
                        </div>
                    </div>
                    <div class="col-4 text-end">
                        <div class="icon icon-shape bg-gradient-primary shadow text-center border-radius-md">
                            <i class="fas fa-server text-lg opacity-10" aria-hidden="true"></i>
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
                            <p class="text-sm mb-0 text-capitalize font-weight-bold">Dispositivi TR-369</p>
                            <h5 class="font-weight-bolder mb-0">
                                {{ $stats['devices']['tr369'] ?? 0 }}
                                <span class="text-sm text-success">USP</span>
                            </h5>
                        </div>
                    </div>
                    <div class="col-4 text-end">
                        <div class="icon icon-shape bg-gradient-success shadow text-center border-radius-md">
                            <i class="fas fa-satellite-dish text-lg opacity-10" aria-hidden="true"></i>
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
                            <p class="text-sm mb-0 text-capitalize font-weight-bold">USP via MQTT</p>
                            <h5 class="font-weight-bolder mb-0">
                                {{ $stats['devices']['tr369_mqtt'] ?? 0 }}
                                <span class="text-sm text-warning">broker</span>
                            </h5>
                        </div>
                    </div>
                    <div class="col-4 text-end">
                        <div class="icon icon-shape bg-gradient-warning shadow text-center border-radius-md">
                            <i class="fas fa-exchange-alt text-lg opacity-10" aria-hidden="true"></i>
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
                            <p class="text-sm mb-0 text-capitalize font-weight-bold">USP via HTTP</p>
                            <h5 class="font-weight-bolder mb-0">
                                {{ $stats['devices']['tr369_http'] ?? 0 }}
                                <span class="text-sm text-info">diretto</span>
                            </h5>
                        </div>
                    </div>
                    <div class="col-4 text-end">
                        <div class="icon icon-shape bg-gradient-info shadow text-center border-radius-md">
                            <i class="fas fa-globe text-lg opacity-10" aria-hidden="true"></i>
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
                <h6><i class="fas fa-history me-2 text-success"></i>Ultimi Dispositivi Attivi</h6>
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
                <h6 class="mb-0"><i class="fas fa-clock me-2 text-warning"></i>Task Recenti</h6>
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

<!-- Charts Row -->
<div class="row mt-4">
    <!-- Devices Status Chart -->
    <div class="col-lg-4 mb-lg-0 mb-4">
        <div class="card h-100">
            <div class="card-header pb-0">
                <h6><i class="fas fa-chart-pie me-2 text-primary"></i>Distribuzione Dispositivi</h6>
            </div>
            <div class="card-body p-3">
                <canvas id="devicesChart" height="200"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Tasks Status Chart -->
    <div class="col-lg-4 mb-lg-0 mb-4">
        <div class="card h-100">
            <div class="card-header pb-0">
                <h6><i class="fas fa-chart-bar me-2 text-warning"></i>Stati Task Provisioning</h6>
            </div>
            <div class="card-body p-3">
                <canvas id="tasksChart" height="200"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Diagnostics Type Chart -->
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header pb-0">
                <h6><i class="fas fa-chart-area me-2 text-danger"></i>Test Diagnostici per Tipo</h6>
            </div>
            <div class="card-body p-3">
                <canvas id="diagnosticsChart" height="200"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Firmware Deployments Chart -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header pb-0">
                <h6><i class="fas fa-chart-line me-2 text-info"></i>Deployment Firmware - Panoramica Stati</h6>
            </div>
            <div class="card-body p-3">
                <canvas id="firmwareChart" height="80"></canvas>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Chart.js Configuration
const chartColors = {
    success: '#82d616',
    danger: '#ea0606',
    warning: '#fbcf33',
    info: '#17c1e8',
    primary: '#cb0c9f',
    secondary: '#8392ab',
    dark: '#344767'
};

// 1. Devices Status Doughnut Chart
const devicesCtx = document.getElementById('devicesChart').getContext('2d');
new Chart(devicesCtx, {
    type: 'doughnut',
    data: {
        labels: ['Online', 'Offline', 'Provisioning', 'Error'],
        datasets: [{
            data: [
                {{ $stats['devices']['online'] ?? 0 }},
                {{ $stats['devices']['offline'] ?? 0 }},
                {{ $stats['devices']['provisioning'] ?? 0 }},
                {{ $stats['devices']['error'] ?? 0 }}
            ],
            backgroundColor: [chartColors.success, chartColors.secondary, chartColors.warning, chartColors.danger],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom', labels: { padding: 15, font: { size: 11 } } },
            tooltip: { 
                callbacks: {
                    label: function(context) {
                        const total = {{ $stats['devices']['total'] ?? 0 }};
                        const value = context.parsed;
                        const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                        return context.label + ': ' + value + ' (' + percentage + '%)';
                    }
                }
            }
        }
    }
});

// 2. Tasks Status Bar Chart
const tasksCtx = document.getElementById('tasksChart').getContext('2d');
new Chart(tasksCtx, {
    type: 'bar',
    data: {
        labels: ['Pending', 'Processing', 'Completed', 'Failed'],
        datasets: [{
            label: 'Task',
            data: [
                {{ $stats['tasks']['pending'] ?? 0 }},
                {{ $stats['tasks']['processing'] ?? 0 }},
                {{ $stats['tasks']['completed'] ?? 0 }},
                {{ $stats['tasks']['failed'] ?? 0 }}
            ],
            backgroundColor: [chartColors.warning, chartColors.info, chartColors.success, chartColors.danger],
            borderRadius: 5
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, ticks: { precision: 0 } }
        }
    }
});

// 3. Diagnostics Type Radar Chart
const diagnosticsCtx = document.getElementById('diagnosticsChart').getContext('2d');
new Chart(diagnosticsCtx, {
    type: 'polarArea',
    data: {
        labels: ['Ping', 'Traceroute', 'Download', 'Upload'],
        datasets: [{
            data: [
                {{ $stats['diagnostics']['by_type']['ping'] ?? 0 }},
                {{ $stats['diagnostics']['by_type']['traceroute'] ?? 0 }},
                {{ $stats['diagnostics']['by_type']['download'] ?? 0 }},
                {{ $stats['diagnostics']['by_type']['upload'] ?? 0 }}
            ],
            backgroundColor: [chartColors.primary, chartColors.info, chartColors.success, chartColors.warning]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom', labels: { padding: 10, font: { size: 10 } } } },
        scales: { r: { ticks: { precision: 0, backdropPadding: 5 } } }
    }
});

// 4. Firmware Deployments Line Chart
const firmwareCtx = document.getElementById('firmwareChart').getContext('2d');
new Chart(firmwareCtx, {
    type: 'line',
    data: {
        labels: ['Scheduled', 'Downloading', 'Installing', 'Completed', 'Failed'],
        datasets: [{
            label: 'Firmware Deployments',
            data: [
                {{ $stats['firmware']['scheduled'] ?? 0 }},
                {{ $stats['firmware']['downloading'] ?? 0 }},
                {{ $stats['firmware']['installing'] ?? 0 }},
                {{ $stats['firmware']['completed'] ?? 0 }},
                {{ $stats['firmware']['failed'] ?? 0 }}
            ],
            borderColor: chartColors.info,
            backgroundColor: 'rgba(23, 193, 232, 0.1)',
            tension: 0.4,
            fill: true,
            pointRadius: 5,
            pointHoverRadius: 7
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, ticks: { precision: 0 } }
        }
    }
});

// Auto-refresh dashboard every 30 seconds
setInterval(function() {
    location.reload();
}, 30000);
</script>
@endpush

@endsection
