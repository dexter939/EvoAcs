@extends('layouts.app')

@section('breadcrumb', 'Diagnostic Details')
@section('page-title', 'Test #' . $test->id)

@section('content')
<div class="row">
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header pb-0">
                <h6>Test Information</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-6">
                        <p class="text-xs font-weight-bold mb-1">Device</p>
                        <p class="text-sm">{{ $test->cpeDevice->serial_number ?? 'N/A' }}</p>
                    </div>
                    <div class="col-6">
                        <p class="text-xs font-weight-bold mb-1">Test Type</p>
                        <p class="text-sm">{{ strtoupper($test->test_type) }}</p>
                    </div>
                    <div class="col-6 mt-3">
                        <p class="text-xs font-weight-bold mb-1">State</p>
                        <span class="badge bg-gradient-{{ $test->test_state == 'Completed' ? 'success' : ($test->test_state == 'Error' ? 'danger' : 'warning') }}">
                            {{ $test->test_state }}
                        </span>
                    </div>
                    <div class="col-6 mt-3">
                        <p class="text-xs font-weight-bold mb-1">Created At</p>
                        <p class="text-sm">{{ $test->created_at->format('d/m/Y H:i:s') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header pb-0">
                <h6>Test Results</h6>
            </div>
            <div class="card-body">
                @if($test->test_type == 'ping')
                    <div class="row">
                        <div class="col-6">
                            <p class="text-xs font-weight-bold mb-1">Target Host</p>
                            <p class="text-sm">{{ $test->host ?? '-' }}</p>
                        </div>
                        <div class="col-6">
                            <p class="text-xs font-weight-bold mb-1">Success/Total</p>
                            <p class="text-sm">{{ $test->success_count }}/{{ $test->number_of_repetitions }}</p>
                        </div>
                        <div class="col-6 mt-3">
                            <p class="text-xs font-weight-bold mb-1">Average RTT</p>
                            <p class="text-sm">{{ $test->average_response_time ?? 0 }} ms</p>
                        </div>
                        <div class="col-6 mt-3">
                            <p class="text-xs font-weight-bold mb-1">Min/Max RTT</p>
                            <p class="text-sm">{{ $test->minimum_response_time ?? 0 }} / {{ $test->maximum_response_time ?? 0 }} ms</p>
                        </div>
                    </div>
                @elseif($test->test_type == 'traceroute')
                    <div class="row">
                        <div class="col-6">
                            <p class="text-xs font-weight-bold mb-1">Target Host</p>
                            <p class="text-sm">{{ $test->host ?? '-' }}</p>
                        </div>
                        <div class="col-6">
                            <p class="text-xs font-weight-bold mb-1">Hops Count</p>
                            <p class="text-sm">{{ $test->number_of_hops ?? 0 }}</p>
                        </div>
                        <div class="col-12 mt-3">
                            <p class="text-xs font-weight-bold mb-1">Route Data</p>
                            <pre class="text-xs" style="max-height: 200px; overflow-y: auto;">{{ $test->route_hops_data ?? 'No route data' }}</pre>
                        </div>
                    </div>
                @elseif($test->test_type == 'download')
                    <div class="row">
                        <div class="col-6">
                            <p class="text-xs font-weight-bold mb-1">Download Speed</p>
                            <p class="text-sm">{{ number_format($test->download_speed ?? 0, 2) }} Mbps</p>
                        </div>
                        <div class="col-6">
                            <p class="text-xs font-weight-bold mb-1">Total Bytes</p>
                            <p class="text-sm">{{ number_format($test->total_bytes_received ?? 0) }} bytes</p>
                        </div>
                        <div class="col-6 mt-3">
                            <p class="text-xs font-weight-bold mb-1">Test Duration</p>
                            <p class="text-sm">{{ $test->test_duration ?? 0 }} sec</p>
                        </div>
                        <div class="col-6 mt-3">
                            <p class="text-xs font-weight-bold mb-1">Download URL</p>
                            <p class="text-sm text-truncate">{{ $test->download_url ?? '-' }}</p>
                        </div>
                    </div>
                @elseif($test->test_type == 'upload')
                    <div class="row">
                        <div class="col-6">
                            <p class="text-xs font-weight-bold mb-1">Upload Speed</p>
                            <p class="text-sm">{{ number_format($test->upload_speed ?? 0, 2) }} Mbps</p>
                        </div>
                        <div class="col-6">
                            <p class="text-xs font-weight-bold mb-1">Total Bytes</p>
                            <p class="text-sm">{{ number_format($test->total_bytes_sent ?? 0) }} bytes</p>
                        </div>
                        <div class="col-6 mt-3">
                            <p class="text-xs font-weight-bold mb-1">Test Duration</p>
                            <p class="text-sm">{{ $test->test_duration ?? 0 }} sec</p>
                        </div>
                        <div class="col-6 mt-3">
                            <p class="text-xs font-weight-bold mb-1">Upload URL</p>
                            <p class="text-sm text-truncate">{{ $test->upload_url ?? '-' }}</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <a href="{{ route('acs.diagnostics') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to List
        </a>
    </div>
</div>
@endsection
