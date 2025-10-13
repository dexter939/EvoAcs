@extends('layouts.app')

@section('breadcrumb', 'Storage Configuration')
@section('page-title', 'Storage - ' . $device->serial_number)

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <a href="{{ route('acs.storage') }}" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left me-2"></i>Back to Storage Devices
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card mb-4">
            <div class="card-header pb-0">
                <h6>Storage Service Parameters</h6>
                <p class="text-sm">NAS, RAID, Filesystem Configuration</p>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm align-items-center mb-0">
                        <thead>
                            <tr>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder">Parameter Path</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder">Value</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder">Type</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder">Writable</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($storageParams as $param)
                            <tr>
                                <td class="text-xs">{{ $param->parameter_path }}</td>
                                <td class="text-xs font-weight-bold">{{ $param->parameter_value ?? '-' }}</td>
                                <td class="text-xs">{{ $param->parameter_type }}</td>
                                <td>
                                    <span class="badge badge-xs bg-gradient-{{ $param->writable ? 'success' : 'secondary' }}">
                                        {{ $param->writable ? 'Yes' : 'No' }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3">No storage parameters found</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                @if($storageParams->count() > 0)
                <div class="mt-4">
                    <h6 class="mb-3">Quick Actions</h6>
                    <form method="POST" action="{{ route('acs.storage.configure', $device->id) }}">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-sync me-2"></i>Refresh Storage Configuration
                        </button>
                    </form>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
