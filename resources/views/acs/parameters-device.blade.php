@extends('layouts.app')

@section('breadcrumb', 'Device Parameters')
@section('page-title', 'Parameters - ' . $device->serial_number)

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <a href="{{ route('acs.parameters') }}" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left me-2"></i>Back to Devices
        </a>
        <form method="POST" action="{{ route('acs.parameters.discover', $device->id) }}" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="fas fa-search me-2"></i>Run Discovery
            </button>
        </form>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card mb-4">
            <div class="card-header pb-0">
                <h6>All Device Parameters ({{ $parameters->total() }} total)</h6>
                <p class="text-sm">Complete parameter tree from TR-111 discovery</p>
            </div>
            <div class="card-body px-0 pt-0 pb-2">
                <div class="table-responsive p-0">
                    <table class="table table-sm align-items-center mb-0">
                        <thead>
                            <tr>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7" style="width: 50%">Parameter Path</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7" style="width: 25%">Value</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Type</th>
                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Writable</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($parameters as $param)
                            <tr>
                                <td class="text-xs" style="font-family: monospace">{{ $param->parameter_path }}</td>
                                <td class="text-xs">
                                    @if(strlen($param->parameter_value ?? '') > 50)
                                        {{ substr($param->parameter_value, 0, 50) }}...
                                    @else
                                        {{ $param->parameter_value ?? '-' }}
                                    @endif
                                </td>
                                <td class="text-xs">{{ $param->parameter_type }}</td>
                                <td class="text-center">
                                    <span class="badge badge-xs bg-gradient-{{ $param->writable ? 'success' : 'secondary' }}">
                                        {{ $param->writable ? 'Yes' : 'No' }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3">No parameters discovered yet. Click "Run Discovery" to start.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        @if($parameters->hasPages())
        <div class="d-flex justify-content-center">
            {{ $parameters->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
