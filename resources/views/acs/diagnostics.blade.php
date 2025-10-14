@extends('layouts.app')

@section('breadcrumb', 'Diagnostics')
@section('page-title', 'Remote Diagnostics (TR-143)')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card mb-4">
            <div class="card-header pb-0 d-flex justify-content-between align-items-center">
                <div>
                    <h6>Remote Diagnostic Tests</h6>
                    <p class="text-sm">IPPing, TraceRoute, Download/Upload Diagnostics, UDPEcho</p>
                </div>
            </div>
            <div class="card-body px-0 pt-0 pb-2">
                <div class="table-responsive p-0">
                    <table class="table align-items-center mb-0">
                        <thead>
                            <tr>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">ID</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Device</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Test Type</th>
                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Status</th>
                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Result</th>
                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Date</th>
                                <th class="text-secondary opacity-7"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tests as $test)
                            <tr>
                                <td>
                                    <p class="text-xs text-secondary mb-0 ps-3">#{{ $test->id }}</p>
                                </td>
                                <td>
                                    <div class="d-flex px-2 py-1">
                                        <div class="d-flex flex-column justify-content-center">
                                            <h6 class="mb-0 text-sm">{{ $test->cpeDevice->serial_number ?? 'N/A' }}</h6>
                                            <p class="text-xs text-secondary mb-0">{{ $test->cpeDevice->model_name ?? '' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-sm bg-gradient-info">{{ strtoupper($test->diagnostic_type) }}</span>
                                </td>
                                <td class="align-middle text-center">
                                    <span class="badge badge-sm bg-gradient-{{ $test->status == 'completed' ? 'success' : ($test->status == 'failed' ? 'danger' : 'warning') }}">
                                        {{ ucfirst($test->status) }}
                                    </span>
                                </td>
                                <td class="align-middle text-center">
                                    @php
                                        $summary = $test->getResultsSummary();
                                    @endphp
                                    @if($test->diagnostic_type == 'IPPing' && isset($summary['success_count']))
                                        <span class="text-xs">{{ $summary['success_count'] }}/{{ $summary['success_count'] + $summary['failure_count'] }} pkts</span>
                                    @elseif($test->diagnostic_type == 'DownloadDiagnostics' && isset($summary['speed_mbps']))
                                        <span class="text-xs">{{ $summary['speed_mbps'] }} Mbps</span>
                                    @elseif($test->diagnostic_type == 'UploadDiagnostics' && isset($summary['speed_mbps']))
                                        <span class="text-xs">{{ $summary['speed_mbps'] }} Mbps</span>
                                    @else
                                        <span class="text-xs">-</span>
                                    @endif
                                </td>
                                <td class="align-middle text-center">
                                    <span class="text-secondary text-xs font-weight-bold">{{ $test->created_at->format('d/m/Y H:i') }}</span>
                                </td>
                                <td class="align-middle">
                                    <a href="{{ route('acs.diagnostics.details', $test->id) }}" class="text-secondary font-weight-bold text-xs" data-toggle="tooltip" data-original-title="View details">
                                        Details
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center text-sm text-muted py-4">
                                    No diagnostic tests found
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        @if($tests->hasPages())
        <div class="d-flex justify-content-center">
            {{ $tests->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
