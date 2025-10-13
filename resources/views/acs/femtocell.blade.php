@extends('layouts.app')

@section('breadcrumb', 'Femtocell')
@section('page-title', 'Femtocell RF Management (TR-196)')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card mb-4">
            <div class="card-header pb-0">
                <h6>Femtocell Devices</h6>
                <p class="text-sm">RF Configuration, GPS Location, UARFCN/EARFCN, TxPower</p>
            </div>
            <div class="card-body px-0 pt-0 pb-2">
                <div class="table-responsive p-0">
                    <table class="table align-items-center mb-0">
                        <thead>
                            <tr>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Device</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Model</th>
                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Status</th>
                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Last Contact</th>
                                <th class="text-secondary opacity-7"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($devices as $device)
                            <tr>
                                <td>
                                    <div class="d-flex px-2 py-1">
                                        <div class="d-flex flex-column justify-content-center">
                                            <h6 class="mb-0 text-sm">{{ $device->serial_number }}</h6>
                                            <p class="text-xs text-secondary mb-0">{{ $device->oui }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <p class="text-xs font-weight-bold mb-0">{{ $device->model ?? 'N/A' }}</p>
                                </td>
                                <td class="align-middle text-center">
                                    <span class="badge badge-sm bg-gradient-{{ $device->connection_status == 'online' ? 'success' : 'secondary' }}">
                                        {{ ucfirst($device->connection_status) }}
                                    </span>
                                </td>
                                <td class="align-middle text-center">
                                    <span class="text-secondary text-xs">{{ $device->last_contact_time ? $device->last_contact_time->format('d/m/Y H:i') : 'Never' }}</span>
                                </td>
                                <td class="align-middle">
                                    <a href="{{ route('acs.femtocell.device', $device->id) }}" class="text-secondary font-weight-bold text-xs">
                                        Configure
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center text-sm text-muted py-4">
                                    No femtocell devices found
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        @if($devices->hasPages())
        <div class="d-flex justify-content-center">
            {{ $devices->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
