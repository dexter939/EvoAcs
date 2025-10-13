@extends('layouts.app')

@section('breadcrumb', 'Dashboard')
@section('page-title', 'Dashboard ACS')

@push('styles')
<link href="/assets/css/dashboard-enhancements.css" rel="stylesheet" />
@endpush

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
                                <span class="stat-devices-online">{{ $stats['devices']['online'] ?? 0 }}</span>
                                <span class="text-sm text-muted">/ <span class="stat-devices-total">{{ $stats['devices']['total'] ?? 0 }}</span></span>
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
                                <span class="stat-tasks-pending">{{ $stats['tasks']['pending'] ?? 0 }}</span>
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
                                <span class="stat-firmware-total">{{ $stats['firmware']['total_deployments'] ?? 0 }}</span>
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
                                <span class="stat-tasks-completed">{{ $stats['tasks']['completed'] ?? 0 }}</span>
                                <span class="text-success text-sm font-weight-bolder stat-tasks-completion">
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
                                <span class="stat-diagnostics-completed">{{ $stats['diagnostics']['completed'] ?? 0 }}</span>
                                <span class="text-sm text-muted">/ <span class="stat-diagnostics-total">{{ $stats['diagnostics']['total'] ?? 0 }}</span></span>
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
                                <span class="stat-profiles-active">{{ $stats['profiles_active'] ?? 0 }}</span>
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
                                <span class="stat-firmware-versions">{{ $stats['firmware_versions'] ?? 0 }}</span>
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
                                <span class="stat-parameters-count">{{ $stats['unique_parameters'] ?? 0 }}</span>
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
                                <span class="stat-tr069-devices">{{ $stats['devices']['tr069'] ?? 0 }}</span>
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
                                <span class="stat-tr369-devices">{{ $stats['devices']['tr369'] ?? 0 }}</span>
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
                                <span class="stat-tr369-mqtt">{{ $stats['devices']['tr369_mqtt'] ?? 0 }}</span>
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
                                <span class="stat-tr369-http">{{ $stats['devices']['tr369_http'] ?? 0 }}</span>
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
            <div class="card-header pb-0 d-flex justify-content-between align-items-center">
                <div>
                    <h6><i class="fas fa-history me-2 text-success"></i>Ultimi Dispositivi Attivi</h6>
                    <p class="text-sm mb-0">
                        <i class="fa fa-check text-success" aria-hidden="true"></i>
                        <span class="font-weight-bold ms-1">{{ count($stats['recent_devices'] ?? []) }}</span> dispositivi nelle ultime ore
                    </p>
                </div>
                <button class="btn btn-sm btn-primary mb-0" data-bs-toggle="modal" data-bs-target="#addDeviceModal">
                    <i class="fas fa-plus me-1"></i> Aggiungi
                </button>
            </div>
            <div class="card-body p-3">
                <div class="table-responsive">
                    <table class="table align-items-center mb-0">
                        <thead>
                            <tr>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Dispositivo</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Stato</th>
                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Ultimo Inform</th>
                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Azioni</th>
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
                                <td class="align-middle text-center">
                                    <button class="btn btn-link text-info px-2 mb-0" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editDeviceModal"
                                            data-device-id="{{ $device->id }}"
                                            data-device-serial="{{ $device->serial_number }}"
                                            data-device-manufacturer="{{ $device->manufacturer }}"
                                            data-device-model="{{ $device->model_name }}"
                                            data-device-status="{{ $device->status }}"
                                            title="Modifica">
                                        <i class="fas fa-edit text-sm"></i>
                                    </button>
                                    <button class="btn btn-link text-danger px-2 mb-0" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#deleteDeviceModal"
                                            data-device-id="{{ $device->id }}"
                                            data-device-serial="{{ $device->serial_number }}"
                                            title="Elimina">
                                        <i class="fas fa-trash text-sm"></i>
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center text-sm text-muted py-4">
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

// Legacy auto-refresh removed - now using dashboard-realtime.js for smooth updates

// Modal handlers for CRUD operations
document.addEventListener('DOMContentLoaded', function() {
    // Edit Device Modal - populate with data and set form action
    const editModal = document.getElementById('editDeviceModal');
    if (editModal) {
        editModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const deviceId = button.getAttribute('data-device-id');
            const serial = button.getAttribute('data-device-serial');
            const manufacturer = button.getAttribute('data-device-manufacturer');
            const model = button.getAttribute('data-device-model');
            const status = button.getAttribute('data-device-status');
            
            // Set form action URL dynamically
            const form = editModal.querySelector('#editDeviceForm');
            form.action = `/acs/devices/${deviceId}`;
            
            // Populate form fields
            editModal.querySelector('#edit_device_id').value = deviceId;
            editModal.querySelector('#edit_serial_number').value = serial;
            editModal.querySelector('#edit_manufacturer').value = manufacturer;
            editModal.querySelector('#edit_model_name').value = model;
            editModal.querySelector('#edit_status').value = status;
        });
    }
    
    // Delete Device Modal - populate with data and set form action
    const deleteModal = document.getElementById('deleteDeviceModal');
    if (deleteModal) {
        deleteModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const deviceId = button.getAttribute('data-device-id');
            const serial = button.getAttribute('data-device-serial');
            
            // Set form action URL dynamically
            const form = deleteModal.querySelector('#deleteDeviceForm');
            form.action = `/acs/devices/${deviceId}`;
            
            // Populate modal content
            deleteModal.querySelector('#delete_device_id').value = deviceId;
            deleteModal.querySelector('#delete_device_serial').textContent = serial;
        });
    }
});
</script>

<!-- Real-time Dashboard Updates -->
<script src="/assets/js/dashboard-realtime.js"></script>
@endpush

<!-- CRUD Modals -->
<!-- Add Device Modal -->
<div class="modal fade" id="addDeviceModal" tabindex="-1" aria-labelledby="addDeviceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-gradient-primary">
                <h5 class="modal-title text-white" id="addDeviceModalLabel">
                    <i class="fas fa-plus-circle me-2"></i>Aggiungi Nuovo Dispositivo
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="/acs/devices" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="serial_number" class="form-label">Serial Number <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="serial_number" name="serial_number" required>
                    </div>
                    <div class="mb-3">
                        <label for="manufacturer" class="form-label">Produttore</label>
                        <input type="text" class="form-control" id="manufacturer" name="manufacturer" placeholder="es. TP-Link">
                    </div>
                    <div class="mb-3">
                        <label for="model_name" class="form-label">Modello</label>
                        <input type="text" class="form-control" id="model_name" name="model_name" placeholder="es. Archer C6">
                    </div>
                    <div class="mb-3">
                        <label for="oui" class="form-label">OUI</label>
                        <input type="text" class="form-control" id="oui" name="oui" placeholder="es. 001234">
                    </div>
                    <div class="mb-3">
                        <label for="product_class" class="form-label">Product Class</label>
                        <input type="text" class="form-control" id="product_class" name="product_class">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Salva Dispositivo
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Device Modal -->
<div class="modal fade" id="editDeviceModal" tabindex="-1" aria-labelledby="editDeviceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-gradient-info">
                <h5 class="modal-title text-white" id="editDeviceModalLabel">
                    <i class="fas fa-edit me-2"></i>Modifica Dispositivo
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editDeviceForm" method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" id="edit_device_id" name="device_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_serial_number" class="form-label">Serial Number <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_serial_number" name="serial_number" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_manufacturer" class="form-label">Produttore</label>
                        <input type="text" class="form-control" id="edit_manufacturer" name="manufacturer">
                    </div>
                    <div class="mb-3">
                        <label for="edit_model_name" class="form-label">Modello</label>
                        <input type="text" class="form-control" id="edit_model_name" name="model_name">
                    </div>
                    <div class="mb-3">
                        <label for="edit_status" class="form-label">Stato</label>
                        <select class="form-select" id="edit_status" name="status">
                            <option value="online">Online</option>
                            <option value="offline">Offline</option>
                            <option value="provisioning">Provisioning</option>
                            <option value="error">Error</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-info">
                        <i class="fas fa-save me-1"></i>Aggiorna
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Device Modal -->
<div class="modal fade" id="deleteDeviceModal" tabindex="-1" aria-labelledby="deleteDeviceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-gradient-danger">
                <h5 class="modal-title text-white" id="deleteDeviceModalLabel">
                    <i class="fas fa-exclamation-triangle me-2"></i>Conferma Eliminazione
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="deleteDeviceForm" method="POST">
                @csrf
                @method('DELETE')
                <input type="hidden" id="delete_device_id" name="device_id">
                <div class="modal-body">
                    <p class="text-center">
                        <i class="fas fa-trash fa-3x text-danger mb-3"></i>
                    </p>
                    <p class="text-center">Sei sicuro di voler eliminare il dispositivo <strong id="delete_device_serial"></strong>?</p>
                    <p class="text-center text-muted text-sm">Questa azione non può essere annullata.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-1"></i>Elimina
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
