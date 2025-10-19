@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0"><i class="fas fa-bell text-danger"></i> Real-time Alarms & Monitoring</h2>
                <div>
                    <span class="badge bg-gradient-success me-2">
                        <i class="fas fa-circle" style="animation: pulse 2s ease-in-out infinite;"></i> SSE Connected
                    </span>
                    <button class="btn btn-sm btn-outline-primary" onclick="location.reload()">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                </div>
            </div>
            
            <!-- Stat Cards Row - Soft UI PRO Pattern -->
            <div class="row mb-4">
                <!-- Total Active Alarms -->
                <div class="col-xl-3 col-sm-6 mb-xl-0 mb-4">
                    <div class="card">
                        <div class="card-body p-3">
                            <div class="row">
                                <div class="col-8">
                                    <div class="numbers">
                                        <p class="text-sm mb-0 text-uppercase font-weight-bold">Total Active</p>
                                        <h5 class="font-weight-bolder mb-0">
                                            {{ $stats['total_active'] }}
                                            <span class="text-success text-sm font-weight-bolder">
                                                <i class="fas fa-exclamation-circle"></i>
                                            </span>
                                        </h5>
                                    </div>
                                </div>
                                <div class="col-4 text-end">
                                    <div class="icon icon-shape bg-gradient-primary shadow-primary text-center rounded-circle">
                                        <i class="ni ni-bell-55 text-lg opacity-10" aria-hidden="true"></i>
                                    </div>
                                </div>
                            </div>
                            <canvas id="sparkline-total" width="100" height="50" class="mt-2"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Critical Alarms -->
                <div class="col-xl-3 col-sm-6 mb-xl-0 mb-4">
                    <div class="card">
                        <div class="card-body p-3">
                            <div class="row">
                                <div class="col-8">
                                    <div class="numbers">
                                        <p class="text-sm mb-0 text-uppercase font-weight-bold">Critical</p>
                                        <h5 class="font-weight-bolder mb-0 text-danger">
                                            {{ $stats['critical'] }}
                                            @if($stats['critical'] > 0)
                                            <span class="text-danger text-sm font-weight-bolder">
                                                <i class="fas fa-exclamation-triangle"></i>
                                            </span>
                                            @endif
                                        </h5>
                                    </div>
                                </div>
                                <div class="col-4 text-end">
                                    <div class="icon icon-shape bg-gradient-danger shadow-danger text-center rounded-circle">
                                        <i class="ni ni-fat-remove text-lg opacity-10" aria-hidden="true"></i>
                                    </div>
                                </div>
                            </div>
                            <canvas id="sparkline-critical" width="100" height="50" class="mt-2"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Major Alarms -->
                <div class="col-xl-3 col-sm-6 mb-xl-0 mb-4">
                    <div class="card">
                        <div class="card-body p-3">
                            <div class="row">
                                <div class="col-8">
                                    <div class="numbers">
                                        <p class="text-sm mb-0 text-uppercase font-weight-bold">Major</p>
                                        <h5 class="font-weight-bolder mb-0 text-warning">
                                            {{ $stats['major'] }}
                                        </h5>
                                    </div>
                                </div>
                                <div class="col-4 text-end">
                                    <div class="icon icon-shape bg-gradient-warning shadow-warning text-center rounded-circle">
                                        <i class="ni ni-sound-wave text-lg opacity-10" aria-hidden="true"></i>
                                    </div>
                                </div>
                            </div>
                            <canvas id="sparkline-major" width="100" height="50" class="mt-2"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Minor/Warning Alarms -->
                <div class="col-xl-3 col-sm-6 mb-xl-0 mb-4">
                    <div class="card">
                        <div class="card-body p-3">
                            <div class="row">
                                <div class="col-8">
                                    <div class="numbers">
                                        <p class="text-sm mb-0 text-uppercase font-weight-bold">Minor/Info</p>
                                        <h5 class="font-weight-bolder mb-0 text-info">
                                            {{ $stats['minor'] + $stats['warning'] + $stats['info'] }}
                                        </h5>
                                    </div>
                                </div>
                                <div class="col-4 text-end">
                                    <div class="icon icon-shape bg-gradient-info shadow-info text-center rounded-circle">
                                        <i class="ni ni-bulb-61 text-lg opacity-10" aria-hidden="true"></i>
                                    </div>
                                </div>
                            </div>
                            <canvas id="sparkline-minor" width="100" height="50" class="mt-2"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content Row -->
            <div class="row">
                <!-- Alarms Table (Left col-lg-8) -->
                <div class="col-lg-8 mb-lg-0 mb-4">
                    <div class="card">
                        <div class="card-header pb-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">Alarms List</h6>
                                <div class="d-flex gap-2">
                                    <select class="form-select form-select-sm" id="filterStatus" style="width: auto;">
                                        <option value="all" {{ $status === 'all' ? 'selected' : '' }}>All Status</option>
                                        <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Active</option>
                                        <option value="acknowledged" {{ $status === 'acknowledged' ? 'selected' : '' }}>Acknowledged</option>
                                        <option value="cleared" {{ $status === 'cleared' ? 'selected' : '' }}>Cleared</option>
                                    </select>
                                    <select class="form-select form-select-sm" id="filterSeverity" style="width: auto;">
                                        <option value="all">All Severities</option>
                                        <option value="critical">Critical</option>
                                        <option value="major">Major</option>
                                        <option value="minor">Minor</option>
                                        <option value="warning">Warning</option>
                                        <option value="info">Info</option>
                                    </select>
                                    <button class="btn btn-sm btn-success" id="bulkAcknowledge" title="Acknowledge All Active">
                                        <i class="fas fa-check-double"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                <div class="card-body px-0 pt-0 pb-2">
                    <div class="table-responsive p-0">
                        <table class="table align-items-center mb-0">
                            <thead>
                                <tr>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Alarm</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Device</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Severity</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Status</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Raised</th>
                                    <th class="text-secondary opacity-7">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($alarms as $alarm)
                                <tr id="alarm-{{ $alarm->id }}">
                                    <td>
                                        <div class="d-flex px-2 py-1">
                                            <div class="d-flex flex-column justify-content-center">
                                                <h6 class="mb-0 text-sm">{{ $alarm->title }}</h6>
                                                <p class="text-xs text-secondary mb-0">{{ Str::limit($alarm->description, 60) }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @if($alarm->device)
                                        <a href="{{ route('acs.devices.show', $alarm->device_id) }}" class="text-xs">
                                            {{ $alarm->device->serial_number }}
                                        </a>
                                        @else
                                        <span class="text-xs text-secondary">System</span>
                                        @endif
                                    </td>
                                    <td class="align-middle text-center text-sm">
                                        <span class="badge bg-gradient-{{ $alarm->severity_color }}">{{ $alarm->severity }}</span>
                                    </td>
                                    <td class="align-middle text-center">
                                        <span class="badge bg-{{ $alarm->status === 'active' ? 'danger' : ($alarm->status === 'acknowledged' ? 'warning' : 'success') }}">{{ $alarm->status }}</span>
                                    </td>
                                    <td class="align-middle text-center">
                                        <span class="text-secondary text-xs">{{ $alarm->raised_at->diffForHumans() }}</span>
                                    </td>
                                    <td class="align-middle">
                                        @if($alarm->status === 'active')
                                        <button class="btn btn-sm btn-warning acknowledge-btn" data-id="{{ $alarm->id }}">
                                            <i class="fas fa-check"></i> Acknowledge
                                        </button>
                                        <button class="btn btn-sm btn-success clear-btn ms-1" data-id="{{ $alarm->id }}">
                                            <i class="fas fa-times"></i> Clear
                                        </button>
                                        @elseif($alarm->status === 'acknowledged')
                                        <button class="btn btn-sm btn-success clear-btn" data-id="{{ $alarm->id }}">
                                            <i class="fas fa-times"></i> Clear
                                        </button>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <p class="text-secondary mb-0">No alarms found</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer">
                        {{ $alarms->links() }}
                    </div>
                </div>
            </div>
            
            <!-- Activity Timeline (Right col-lg-4) -->
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header pb-0 p-3">
                        <h6 class="mb-0">Recent Activity</h6>
                    </div>
                    <div class="card-body p-3">
                        <div class="timeline timeline-one-side" data-timeline-axis-style="dotted" id="activity-timeline">
                            @foreach($alarms->take(10) as $alarm)
                            <div class="timeline-block mb-3">
                                <span class="timeline-step badge-{{ $alarm->severity_color }}">
                                    <i class="fas fa-{{ $alarm->severity === 'critical' ? 'exclamation-triangle' : ($alarm->severity === 'major' ? 'exclamation-circle' : 'bell') }}"></i>
                                </span>
                                <div class="timeline-content">
                                    <h6 class="text-dark text-sm font-weight-bold mb-0">
                                        <span class="badge badge-sm bg-gradient-{{ $alarm->severity_color }} me-1">{{ strtoupper($alarm->severity) }}</span>
                                        {{ Str::limit($alarm->title, 40) }}
                                    </h6>
                                    <p class="text-secondary font-weight-bold text-xs mt-1 mb-0">
                                        <i class="fas fa-clock"></i> {{ $alarm->raised_at->diffForHumans() }}
                                    </p>
                                    @if($alarm->device)
                                    <p class="text-xs text-secondary mb-0">
                                        <i class="fas fa-router"></i> {{ $alarm->device->serial_number }}
                                    </p>
                                    @endif
                                    <p class="text-sm mt-2 mb-0">{{ Str::limit($alarm->description, 80) }}</p>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.acknowledge-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const alarmId = this.dataset.id;
            fetch(`/acs/alarms/${alarmId}/acknowledge`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            });
        });
    });

    document.querySelectorAll('.clear-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const alarmId = this.dataset.id;
            const resolution = prompt('Enter resolution (optional):');
            fetch(`/acs/alarms/${alarmId}/clear`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ resolution })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            });
        });
    });

    document.getElementById('filterStatus').addEventListener('change', function() {
        window.location.href = `?status=${this.value}`;
    });

    document.getElementById('filterSeverity').addEventListener('change', function() {
        const params = new URLSearchParams(window.location.search);
        params.set('severity', this.value);
        window.location.href = `?${params.toString()}`;
    });
    
    // Bulk Acknowledge All Active Alarms
    document.getElementById('bulkAcknowledge').addEventListener('click', function() {
        if (!confirm('Acknowledge all active alarms?')) return;
        
        const activeAlarms = document.querySelectorAll('.acknowledge-btn');
        let promises = [];
        
        activeAlarms.forEach(btn => {
            const alarmId = btn.dataset.id;
            promises.push(
                fetch(`/acs/alarms/${alarmId}/acknowledge`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json'
                    }
                })
            );
        });
        
        Promise.all(promises).then(() => {
            location.reload();
        });
    });

    let lastAlarmId = {{ $alarms->max('id') ?? 0 }};
    let eventSource = null;
    let reconnectAttempts = 0;

    function connectSSE() {
        if (eventSource) {
            eventSource.close();
        }

        eventSource = new EventSource(`{{ route('acs.alarms.stream') }}?lastId=${lastAlarmId}`);

        eventSource.onopen = function() {
            console.log('✅ SSE connected');
            reconnectAttempts = 0;
        };

        eventSource.onmessage = function(event) {
            try {
                const alarm = JSON.parse(event.data);
                lastAlarmId = Math.max(lastAlarmId, alarm.id);
                
                showAlarmNotification(alarm);
            } catch (e) {
                console.error('SSE parse error:', e);
            }
        };

        eventSource.onerror = function(error) {
            console.error('SSE error:', error);
            eventSource.close();
            
            reconnectAttempts++;
            const delay = Math.min(1000 * Math.pow(2, reconnectAttempts), 30000);
            console.log(`🔄 Reconnecting SSE in ${delay}ms (attempt ${reconnectAttempts})`);
            setTimeout(connectSSE, delay);
        };
    }

    function showAlarmNotification(alarm) {
        const severityColors = {
            critical: '#dc3545',
            major: '#fd7e14',
            minor: '#ffc107',
            warning: '#0dcaf0',
            info: '#6c757d'
        };

        const severityIcons = {
            critical: 'fas fa-exclamation-triangle',
            major: 'fas fa-exclamation-circle',
            minor: 'fas fa-info-circle',
            warning: 'fas fa-bell',
            info: 'fas fa-info'
        };

        const notification = document.createElement('div');
        notification.className = 'toast align-items-center border-0';
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 350px;
            background: linear-gradient(135deg, ${severityColors[alarm.severity]} 0%, ${severityColors[alarm.severity]}dd 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        `;
        notification.setAttribute('role', 'alert');
        notification.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    <i class="${severityIcons[alarm.severity]} me-2"></i>
                    <strong>${alarm.severity.toUpperCase()} ALARM</strong><br>
                    <small>${alarm.title} - ${alarm.device_name}</small><br>
                    <small class="opacity-75">${alarm.raised_at}</small>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;

        document.body.appendChild(notification);
        const toast = new bootstrap.Toast(notification, { autohide: true, delay: 6000 });
        toast.show();

        notification.addEventListener('hidden.bs.toast', () => notification.remove());

        const alertSound = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBSyBzvLZiTYIGGS56+mjUBELTKXh8bllHQU2jdXu0n0pBSd+zPDajzsKFGO56OykUhELSKDe8bllHgY2jdTu0n0qBSd+zPDajzsKFGK46OykUhEMSp/f8LllHgY2jdXu0n0qBSZ9y/DajzsKFGK46OykUhEMSp/f8LllHgY2jdTu0n0qBSd+zPDajzsKFGK46OykUhELTKLg8bllHQU2jdXt0n0pBSd9y/DajzsKFGO56+mjUBELTKLg8rllHQU2jdXu0n0qBSd+zPDajzsKE2S66+mjUBELTKLg8rllHQU2jdXu0n0qBSd+zPDajzsKFGO56+mjUBEMSp/f8LllHgY2jdXu0n0qBSd+zPDajzsKFGK46OykUhEMSp/f8LllHgY2jdXu0n0qBSd+zPDajzsKFGK46OykUhEMSp/f8LllHgY2jdXu0n0qBSd+zPDajzsKFGK46OykUhEMSp/f8LllHgY2jdXu0n0qBSd+zPDajzsKFGO56+mjUBEMSp/f8LllHgY2jdXu0n0qBSd+zPDajzsKFGK46OykUhEMSp/f8LllHgY2jdXu0n0qBSd+zPDajzsKFGK46OykUhEMSp/f8LllHgY2jdXu0n0qBSd+zPDajzsKFGK46OykUhEMSp/f8LllHgY2jdXu0n0qBSd+zPDajzsKFGK46OykUhEMSp/f8LllHgY2jdXu0n0qBSd+zPDajzsKFGK46OykUhEMSp/f8LllHgY2jdXu0n0qBSd+zPDajzsKFGK46OykUhEMSp/f8LllHgY2jdXu0n0qBSd+zPDajzsKFGK46OykUhEMSp/f8LllHgY2jdXu0n0qBSd+zPDajzsKFGK46OykUhEMSp/f8LllHgY2jdXu0n0qBSd+zPDajzsKFGK46OykUhEMSp/f8LllHgY2jdXu0n0qBSd+zPDajzsKFGK46OykUhEMSp/f8LllHgY2jdXu0n0qBSd+zPDajzsKFGK46OykUhEMSp/f8LllHgY2jdXu0n0qBSd+zPDajzsKFGK46OykUhEMSp/f8LllHgY2jdXu0n0qBSd+zPDajzsKFGK46OykUhEMSp/f8LllHgY2jdXu0n0qBSd+zPDajzsKFGK46OykUhEMSp/f8LllHgY2jdXu0n0qBSd+zPDajzsKFGK46OykUhEMSp/f8LllHgY2jdXu0n0qBSd+zPDajzsKFGK46OykUhEMSp/f8A==');
        alertSound.volume = 0.3;
        alertSound.play().catch(e => console.log('Audio play failed:', e));

        setTimeout(() => location.reload(), 7000);
    }

    connectSSE();

    window.addEventListener('beforeunload', function() {
        if (eventSource) {
            eventSource.close();
        }
    });
    
    // Sparkline Charts - Soft UI PRO Pattern
    const sparklineConfig = {
        type: 'line',
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false }, tooltip: { enabled: false } },
            scales: {
                y: { display: false },
                x: { display: false }
            },
            elements: {
                point: { radius: 0 },
                line: { borderWidth: 2, tension: 0.4 }
            }
        }
    };
    
    // Real 24h trend data from backend
    const hours = @json($stats['trends_24h']['labels']);
    const totalData = @json($stats['trends_24h']['total']);
    const criticalData = @json($stats['trends_24h']['critical']);
    const majorData = @json($stats['trends_24h']['major']);
    const minorData = @json($stats['trends_24h']['minor']);
    
    new Chart(document.getElementById('sparkline-total').getContext('2d'), {
        ...sparklineConfig,
        data: {
            labels: hours,
            datasets: [{
                data: totalData,
                borderColor: '#5e72e4',
                backgroundColor: 'rgba(94, 114, 228, 0.1)',
                fill: true
            }]
        }
    });
    
    new Chart(document.getElementById('sparkline-critical').getContext('2d'), {
        ...sparklineConfig,
        data: {
            labels: hours,
            datasets: [{
                data: criticalData,
                borderColor: '#f5365c',
                backgroundColor: 'rgba(245, 54, 92, 0.1)',
                fill: true
            }]
        }
    });
    
    new Chart(document.getElementById('sparkline-major').getContext('2d'), {
        ...sparklineConfig,
        data: {
            labels: hours,
            datasets: [{
                data: majorData,
                borderColor: '#fb6340',
                backgroundColor: 'rgba(251, 99, 64, 0.1)',
                fill: true
            }]
        }
    });
    
    new Chart(document.getElementById('sparkline-minor').getContext('2d'), {
        ...sparklineConfig,
        data: {
            labels: hours,
            datasets: [{
                data: minorData,
                borderColor: '#11cdef',
                backgroundColor: 'rgba(17, 205, 239, 0.1)',
                fill: true
            }]
        }
    });
});
</script>

<style>
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.3; }
}
</style>

@endsection
