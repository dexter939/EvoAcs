@extends('layouts.app')

@section('breadcrumb', 'STB Configuration')
@section('page-title', 'STB - ' . $device->serial_number)

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <a href="{{ route('acs.stb') }}" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left me-2"></i>Back to STB Devices
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card mb-4">
            <div class="card-header pb-0">
                <h6>STB Service Parameters</h6>
                <p class="text-sm">IPTV Frontend, Streaming Protocols, QoS</p>
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
                            @forelse($stbParams as $param)
                            <tr>
                                <td class="text-xs">{{ $param->parameter_path }}</td>
                                <td class="text-xs font-weight-bold">{{ $param->parameter_value ?? '-' }}</td>
                                <td class="text-xs">{{ $param->parameter_type }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted py-3">No STB parameters found</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                @if($stbParams->count() > 0)
                <div class="mt-4">
                    <h6 class="mb-3">IPTV Actions</h6>
                    <form method="POST" action="{{ route('acs.stb.configure', $device->id) }}">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-tv me-2"></i>Update STB Configuration
                        </button>
                    </form>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
