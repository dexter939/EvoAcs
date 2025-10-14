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
        <button type="button" class="btn btn-success me-2" onclick="aiAnalyzeDiagnostic({{ $test->id }})">
            <i class="fas fa-magic me-2"></i>AI Troubleshooting
        </button>
        <a href="{{ route('acs.diagnostics') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to List
        </a>
    </div>
</div>

<!-- Modal AI Analysis Results -->
<div class="modal fade" id="aiAnalysisModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-gradient-success">
                <h5 class="modal-title text-white"><i class="fas fa-robot me-2"></i>AI Diagnostic Analysis</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="aiAnalysisContent"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
async function aiAnalyzeDiagnostic(diagnosticId) {
    const modal = new bootstrap.Modal(document.getElementById('aiAnalysisModal'));
    const content = document.getElementById('aiAnalysisContent');
    
    // Show loading
    content.innerHTML = '<div class="text-center py-5"><i class="fas fa-spinner fa-spin fa-3x text-success"></i><p class="mt-3">AI sta analizzando il test diagnostico...</p></div>';
    modal.show();
    
    try {
        const response = await fetch(`/acs/diagnostics/${diagnosticId}/ai-analyze`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            let html = '';
            
            // Severity Alert
            const severityClass = {
                'critical': 'danger',
                'high': 'warning',
                'medium': 'info',
                'low': 'secondary',
                'info': 'success'
            }[result.severity] || 'secondary';
            
            html += `<div class="alert alert-${severityClass} mb-3">
                <h6 class="text-white mb-2"><i class="fas fa-exclamation-triangle me-2"></i>Severity: ${result.severity.toUpperCase()}</h6>
                <p class="mb-0 text-white">${result.analysis}</p>
            </div>`;
            
            // Root Cause
            if (result.root_cause) {
                html += `<div class="card mb-3">
                    <div class="card-header bg-gradient-dark">
                        <h6 class="text-white mb-0"><i class="fas fa-search me-2"></i>Root Cause Analysis</h6>
                    </div>
                    <div class="card-body">
                        <p class="mb-0">${result.root_cause}</p>
                    </div>
                </div>`;
            }
            
            // Issues
            if (result.issues && result.issues.length > 0) {
                html += `<div class="card mb-3">
                    <div class="card-header bg-gradient-warning">
                        <h6 class="text-white mb-0"><i class="fas fa-exclamation-circle me-2"></i>Identified Issues (${result.issues.length})</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Category</th>
                                        <th>Description</th>
                                        <th>Metric</th>
                                        <th>Threshold</th>
                                    </tr>
                                </thead>
                                <tbody>`;
                
                result.issues.forEach(issue => {
                    html += `<tr>
                        <td><span class="badge bg-gradient-info">${issue.category}</span></td>
                        <td class="text-sm">${issue.description}</td>
                        <td class="text-sm">${issue.metric || '-'}</td>
                        <td class="text-sm">${issue.threshold_exceeded || '-'}</td>
                    </tr>`;
                });
                
                html += `</tbody></table></div></div></div>`;
            }
            
            // Solutions
            if (result.solutions && result.solutions.length > 0) {
                html += `<div class="card mb-3">
                    <div class="card-header bg-gradient-success">
                        <h6 class="text-white mb-0"><i class="fas fa-wrench me-2"></i>Recommended Solutions (${result.solutions.length})</h6>
                    </div>
                    <div class="card-body">`;
                
                result.solutions.forEach((solution, index) => {
                    const priorityClass = {
                        'high': 'danger',
                        'medium': 'warning',
                        'low': 'info'
                    }[solution.priority] || 'secondary';
                    
                    html += `<div class="border-bottom pb-3 mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0">${index + 1}. ${solution.action}</h6>
                            <span class="badge bg-gradient-${priorityClass}">${solution.priority} priority</span>
                        </div>
                        <p class="text-sm mb-2"><strong>Technical Detail:</strong> ${solution.technical_detail}</p>
                        <p class="text-sm text-success mb-0"><strong>Expected Result:</strong> ${solution.expected_result}</p>
                    </div>`;
                });
                
                html += `</div></div>`;
            }
            
            content.innerHTML = html;
        } else {
            content.innerHTML = `<div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>${result.error}
            </div>`;
        }
    } catch (error) {
        content.innerHTML = `<div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle me-2"></i>Errore di connessione: ${error.message}
        </div>`;
    }
}
</script>
@endpush
