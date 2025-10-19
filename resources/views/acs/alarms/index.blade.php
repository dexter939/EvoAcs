@extends('layouts.app')

@section('breadcrumb', 'Allarmi Sistema')
@section('page-title', 'Dashboard Allarmi & Monitoraggio Real-Time')

@push('styles')
<link href="/assets/css/vendor/jquery.dataTables.min.css" rel="stylesheet" />
<style>
.severity-badge {
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
}
.severity-critical { background: linear-gradient(310deg, #ea0606 0%, #ea0606 100%); color: white; }
.severity-major { background: linear-gradient(310deg, #f53939 0%, #f53939 100%); color: white; }
.severity-minor { background: linear-gradient(310deg, #fbcf33 0%, #fbcf33 100%); color: #344767; }
.severity-warning { background: linear-gradient(310deg, #82d616 0%, #82d616 100%); color: white; }
.severity-info { background: linear-gradient(310deg, #17c1e8 0%, #17c1e8 100%); color: white; }

.status-badge {
    padding: 3px 9px;
    border-radius: 5px;
    font-size: 0.65rem;
    font-weight: 600;
}
.status-active { background-color: #f53939; color: white; }
.status-acknowledged { background-color: #fbcf33; color: #344767; }
.status-cleared { background-color: #82d616; color: white; }

.alarm-notification {
    position: fixed;
    top: 80px;
    right: 20px;
    z-index: 9999;
    min-width: 350px;
}
</style>
@endpush

@section('content')
<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-xl-3 col-sm-6 mb-xl-0 mb-4">
        <div class="card">
            <div class="card-body p-3">
                <div class="row">
                    <div class="col-8">
                        <div class="numbers">
                            <p class="text-sm mb-0 text-uppercase font-weight-bold">Allarmi Attivi</p>
                            <h5 class="font-weight-bolder" id="stat-total">{{ $stats['total_active'] }}</h5>
                        </div>
                    </div>
                    <div class="col-4 text-end">
                        <div class="icon icon-shape bg-gradient-danger shadow-danger text-center rounded-circle">
                            <i class="ni ni-bell-55 text-lg opacity-10" aria-hidden="true"></i>
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
                            <p class="text-sm mb-0 text-uppercase font-weight-bold">Critici</p>
                            <h5 class="font-weight-bolder text-danger" id="stat-critical">{{ $stats['critical'] }}</h5>
                        </div>
                    </div>
                    <div class="col-4 text-end">
                        <div class="icon icon-shape bg-gradient-danger shadow-danger text-center rounded-circle">
                            <i class="fas fa-exclamation-triangle text-lg opacity-10"></i>
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
                            <p class="text-sm mb-0 text-uppercase font-weight-bold">Major</p>
                            <h5 class="font-weight-bolder text-warning" id="stat-major">{{ $stats['major'] }}</h5>
                        </div>
                    </div>
                    <div class="col-4 text-end">
                        <div class="icon icon-shape bg-gradient-warning shadow-warning text-center rounded-circle">
                            <i class="fas fa-exclamation-circle text-lg opacity-10"></i>
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
                            <p class="text-sm mb-0 text-uppercase font-weight-bold">Minor/Warning</p>
                            <h5 class="font-weight-bolder text-info" id="stat-minor">{{ $stats['minor'] + $stats['warning'] }}</h5>
                        </div>
                    </div>
                    <div class="col-4 text-end">
                        <div class="icon icon-shape bg-gradient-info shadow-info text-center rounded-circle">
                            <i class="fas fa-info-circle text-lg opacity-10"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Alarms Table -->
<div class="row">
    <div class="col-12">
        <div class="card mb-4">
            <div class="card-header pb-0 d-flex justify-content-between align-items-center">
                <h6><i class="fas fa-bell me-2"></i>Allarmi Sistema</h6>
                <div>
                    @if(auth()->user()->hasPermission('alarms.manage'))
                    <button class="btn bg-gradient-warning btn-sm mb-0 me-2" onclick="bulkAcknowledge()" id="btnBulkAck" disabled>
                        <i class="fas fa-check me-1"></i>PRENDI IN CARICO
                    </button>
                    <button class="btn bg-gradient-success btn-sm mb-0 me-2" onclick="bulkClear()" id="btnBulkClear" disabled>
                        <i class="fas fa-check-double me-1"></i>RISOLVI
                    </button>
                    @endif
                    <span class="badge bg-gradient-success" id="realtimeStatus">
                        <i class="fas fa-circle-notch fa-spin"></i> Connessione...
                    </span>
                </div>
            </div>
            <div class="card-body px-0 pt-0 pb-2">
                <!-- Filters -->
                <div class="px-3 mb-3">
                    <form method="GET" action="{{ route('acs.alarms') }}" class="row g-2">
                        <div class="col-md-3">
                            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="all" {{ $status === 'all' ? 'selected' : '' }}>Tutti gli stati</option>
                                <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Attivi</option>
                                <option value="acknowledged" {{ $status === 'acknowledged' ? 'selected' : '' }}>Presi in carico</option>
                                <option value="cleared" {{ $status === 'cleared' ? 'selected' : '' }}>Risolti</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select name="severity" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="all" {{ request('severity') === 'all' ? 'selected' : '' }}>Tutte le severity</option>
                                <option value="critical" {{ request('severity') === 'critical' ? 'selected' : '' }}>Critical</option>
                                <option value="major" {{ request('severity') === 'major' ? 'selected' : '' }}>Major</option>
                                <option value="minor" {{ request('severity') === 'minor' ? 'selected' : '' }}>Minor</option>
                                <option value="warning" {{ request('severity') === 'warning' ? 'selected' : '' }}>Warning</option>
                                <option value="info" {{ request('severity') === 'info' ? 'selected' : '' }}>Info</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select name="category" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="all" {{ request('category') === 'all' ? 'selected' : '' }}>Tutte le categorie</option>
                                <option value="connectivity" {{ request('category') === 'connectivity' ? 'selected' : '' }}>Connectivity</option>
                                <option value="performance" {{ request('category') === 'performance' ? 'selected' : '' }}>Performance</option>
                                <option value="security" {{ request('category') === 'security' ? 'selected' : '' }}>Security</option>
                                <option value="system" {{ request('category') === 'system' ? 'selected' : '' }}>System</option>
                            </select>
                        </div>
                    </form>
                </div>

                <div class="table-responsive p-0">
                    <table id="alarmsTable" class="table align-items-center mb-0">
                        <thead>
                            <tr>
                                @if(auth()->user()->hasPermission('alarms.manage'))
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                    <input type="checkbox" id="selectAll">
                                </th>
                                @endif
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Allarme</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Dispositivo</th>
                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Severity</th>
                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Stato</th>
                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Data</th>
                                <th class="text-secondary opacity-7"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($alarms as $alarm)
                            <tr data-alarm-id="{{ $alarm->id }}">
                                @if(auth()->user()->hasPermission('alarms.manage'))
                                <td><input type="checkbox" class="alarm-checkbox" value="{{ $alarm->id }}"></td>
                                @endif
                                <td>
                                    <div class="d-flex px-2 py-1">
                                        <div class="d-flex flex-column justify-content-center">
                                            <h6 class="mb-0 text-sm">{{ $alarm->title }}</h6>
                                            <p class="text-xs text-secondary mb-0">{{ Str::limit($alarm->description, 60) }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td><p class="text-xs text-secondary mb-0">{{ $alarm->device?->serial_number ?? 'System' }}</p></td>
                                <td class="align-middle text-center">
                                    <span class="severity-badge severity-{{ $alarm->severity }}">{{ $alarm->severity }}</span>
                                </td>
                                <td class="align-middle text-center">
                                    <span class="status-badge status-{{ $alarm->status }}">{{ ucfirst($alarm->status) }}</span>
                                </td>
                                <td class="align-middle text-center">
                                    <span class="text-secondary text-xs">{{ $alarm->raised_at->format('d/m/Y H:i') }}</span>
                                </td>
                                <td class="align-middle">
                                    @if(auth()->user()->hasPermission('alarms.manage'))
                                        @if($alarm->status === 'active')
                                            <button class="btn btn-link text-warning mb-0" onclick="acknowledgeAlarm({{ $alarm->id }})">
                                                <i class="fas fa-check text-xs"></i> Prendi in carico
                                            </button>
                                        @endif
                                        @if($alarm->status !== 'cleared')
                                            <button class="btn btn-link text-success mb-0" onclick="clearAlarm({{ $alarm->id }})">
                                                <i class="fas fa-check-double text-xs"></i> Risolvi
                                            </button>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <i class="fas fa-check-circle text-success fa-3x mb-3"></i>
                                    <p class="text-sm text-secondary">Nessun allarme trovato</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div class="px-3 mt-3">
                    {{ $alarms->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Clear Alarm Modal -->
<div class="modal fade" id="clearModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Risolvi Allarme</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="clearAlarmId">
                <div class="mb-3">
                    <label>Note Risoluzione <small class="text-secondary">(opzionale)</small></label>
                    <textarea class="form-control" id="clearResolution" rows="3" placeholder="Descrivi come è stato risolto l'allarme..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                <button type="button" class="btn bg-gradient-success" onclick="confirmClear()">Risolvi</button>
            </div>
        </div>
    </div>
</div>

<!-- Notification Container -->
<div id="notificationContainer" class="alarm-notification"></div>
@endsection

@push('scripts')
<script src="/assets/js/vendor/jquery-3.7.1.min.js"></script>
<script src="/assets/js/vendor/jquery.dataTables.min.js"></script>
<script>
let eventSource = null;
let lastAlarmId = {{ $alarms->max('id') ?? 0 }};
let selectedAlarms = [];

$(document).ready(function() {
    // Initialize DataTable (disabled pagination, using Laravel pagination)
    $('#alarmsTable').DataTable({
        paging: false,
        searching: false,
        info: false,
        order: []
    });
    
    // Connect SSE for real-time updates
    connectSSE();
    
    // Checkbox handlers
    $('#selectAll').on('change', function() {
        $('.alarm-checkbox').prop('checked', this.checked);
        updateBulkButtons();
    });
    
    $('.alarm-checkbox').on('change', updateBulkButtons);
});

function connectSSE() {
    if (eventSource) {
        eventSource.close();
    }
    
    $('#realtimeStatus').html('<i class="fas fa-circle-notch fa-spin"></i> Connessione...');
    
    eventSource = new EventSource('{{ route("acs.alarms.stream") }}?lastId=' + lastAlarmId);
    
    eventSource.onopen = function() {
        $('#realtimeStatus').html('<i class="fas fa-circle text-success"></i> Connesso');
    };
    
    eventSource.onmessage = function(event) {
        try {
            const alarm = JSON.parse(event.data);
            lastAlarmId = Math.max(lastAlarmId, alarm.id);
            showAlarmNotification(alarm);
            refreshStats();
        } catch (e) {
            console.error('Error parsing SSE data:', e);
        }
    };
    
    eventSource.onerror = function(error) {
        $('#realtimeStatus').html('<i class="fas fa-circle text-danger"></i> Disconnesso');
        eventSource.close();
        setTimeout(connectSSE, 5000); // Reconnect after 5s
    };
    
    // Handle reconnect event
    eventSource.addEventListener('reconnect', function(e) {
        eventSource.close();
        setTimeout(connectSSE, 1000);
    });
}

function showAlarmNotification(alarm) {
    const severityColors = {
        critical: 'danger',
        major: 'warning',
        minor: 'info',
        warning: 'secondary',
        info: 'primary'
    };
    
    const color = severityColors[alarm.severity] || 'dark';
    
    const notification = $(`
        <div class="alert alert-${color} alert-dismissible fade show" role="alert">
            <strong>${alarm.severity.toUpperCase()}:</strong> ${alarm.title}
            <br><small>${alarm.device_name} - ${alarm.raised_at}</small>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `);
    
    $('#notificationContainer').append(notification);
    
    // Auto dismiss after 10s
    setTimeout(() => {
        notification.alert('close');
    }, 10000);
}

function refreshStats() {
    $.get('{{ route("acs.alarms.stats") }}', function(response) {
        if (response.success) {
            $('#stat-total').text(response.data.total_active);
            $('#stat-critical').text(response.data.critical);
            $('#stat-major').text(response.data.major);
            $('#stat-minor').text(response.data.minor + response.data.warning);
        }
    });
}

function acknowledgeAlarm(id) {
    $.post(`/acs/alarms/${id}/acknowledge`, {
        _token: '{{ csrf_token() }}'
    }, function(response) {
        if (response.success) {
            location.reload();
        }
    }).fail(function(xhr) {
        alert('Errore: ' + (xhr.responseJSON?.message || 'Operazione fallita'));
    });
}

function clearAlarm(id) {
    $('#clearAlarmId').val(id);
    $('#clearResolution').val('');
    $('#clearModal').modal('show');
}

function confirmClear() {
    const id = $('#clearAlarmId').val();
    const resolution = $('#clearResolution').val();
    
    $.post(`/acs/alarms/${id}/clear`, {
        _token: '{{ csrf_token() }}',
        resolution: resolution
    }, function(response) {
        if (response.success) {
            $('#clearModal').modal('hide');
            location.reload();
        }
    }).fail(function(xhr) {
        alert('Errore: ' + (xhr.responseJSON?.message || 'Operazione fallita'));
    });
}

function updateBulkButtons() {
    selectedAlarms = $('.alarm-checkbox:checked').map(function() {
        return $(this).val();
    }).get();
    
    const hasSelection = selectedAlarms.length > 0;
    $('#btnBulkAck').prop('disabled', !hasSelection);
    $('#btnBulkClear').prop('disabled', !hasSelection);
}

function bulkAcknowledge() {
    if (selectedAlarms.length === 0) return;
    
    if (!confirm(`Prendere in carico ${selectedAlarms.length} allarmi?`)) return;
    
    $.post('{{ route("acs.alarms.bulk-acknowledge") }}', {
        _token: '{{ csrf_token() }}',
        alarm_ids: selectedAlarms
    }, function(response) {
        if (response.success) {
            location.reload();
        }
    }).fail(function(xhr) {
        alert('Errore: ' + (xhr.responseJSON?.message || 'Operazione fallita'));
    });
}

function bulkClear() {
    if (selectedAlarms.length === 0) return;
    
    const resolution = prompt(`Inserisci note risoluzione per ${selectedAlarms.length} allarmi (opzionale):`);
    if (resolution === null) return; // User cancelled
    
    $.post('{{ route("acs.alarms.bulk-clear") }}', {
        _token: '{{ csrf_token() }}',
        alarm_ids: selectedAlarms,
        resolution: resolution
    }, function(response) {
        if (response.success) {
            location.reload();
        }
    }).fail(function(xhr) {
        alert('Errore: ' + (xhr.responseJSON?.message || 'Operazione fallita'));
    });
}

// Cleanup on page unload
$(window).on('beforeunload', function() {
    if (eventSource) {
        eventSource.close();
    }
});
</script>
@endpush
