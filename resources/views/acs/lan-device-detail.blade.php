@extends('layouts.app')

@section('breadcrumb', 'LAN Device Details')
@section('page-title', 'LAN - ' . $device->serial_number)

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <a href="{{ route('acs.lan-devices') }}" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left me-2"></i>Back to LAN Devices
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card mb-4">
            <div class="card-header pb-0">
                <h6>LAN Device Parameters</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm align-items-center mb-0">
                        <thead>
                            <tr>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder">Parameter Path</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder">Value</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder">Type</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($lanParams as $param)
                            <tr>
                                <td class="text-xs">{{ $param->parameter_path }}</td>
                                <td class="text-xs font-weight-bold">{{ $param->parameter_value ?? '-' }}</td>
                                <td class="text-xs">{{ $param->parameter_type }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted py-3">No LAN parameters found</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
