@extends('layouts.acs')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <h2 class="mb-3"><i class="fas fa-bell"></i> Real-time Alarms</h2>
            
            <div class="row mb-4">
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <div class="card">
                        <div class="card-body p-3 text-center">
                            <h6 class="text-muted mb-0">Total Active</h6>
                            <h3 class="mb-0">{{ $stats['total_active'] }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <div class="card bg-gradient-danger">
                        <div class="card-body p-3 text-center text-white">
                            <h6 class="text-white mb-0">Critical</h6>
                            <h3 class="mb-0 text-white">{{ $stats['critical'] }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <div class="card bg-gradient-warning">
                        <div class="card-body p-3 text-center text-white">
                            <h6 class="text-white mb-0">Major</h6>
                            <h3 class="mb-0 text-white">{{ $stats['major'] }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <div class="card bg-gradient-info">
                        <div class="card-body p-3 text-center text-white">
                            <h6 class="text-white mb-0">Minor</h6>
                            <h3 class="mb-0 text-white">{{ $stats['minor'] }}</h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header pb-0">
                    <div class="d-flex justify-content-between">
                        <h6>Alarms List</h6>
                        <div>
                            <select class="form-select form-select-sm d-inline-block w-auto" id="filterStatus">
                                <option value="all">All Status</option>
                                <option value="active" selected>Active</option>
                                <option value="acknowledged">Acknowledged</option>
                                <option value="cleared">Cleared</option>
                            </select>
                            <select class="form-select form-select-sm d-inline-block w-auto ms-2" id="filterSeverity">
                                <option value="all">All Severities</option>
                                <option value="critical">Critical</option>
                                <option value="major">Major</option>
                                <option value="minor">Minor</option>
                                <option value="warning">Warning</option>
                                <option value="info">Info</option>
                            </select>
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
                </div>
            </div>

            <div class="mt-3">
                {{ $alarms->links() }}
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
});
</script>
@endsection
