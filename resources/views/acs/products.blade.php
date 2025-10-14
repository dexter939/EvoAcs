@extends('layouts.app')

@section('breadcrumb', 'Router Products')
@section('page-title', 'Database Modelli Router')

@section('content')
<div class="row mb-4">
    <div class="col-xl-3 col-sm-6 mb-xl-0 mb-4">
        <div class="card">
            <div class="card-body p-3">
                <div class="row">
                    <div class="col-8">
                        <div class="numbers">
                            <p class="text-sm mb-0 text-capitalize font-weight-bold">Totale Modelli</p>
                            <h5 class="font-weight-bolder mb-0">{{ $stats['total'] }}</h5>
                        </div>
                    </div>
                    <div class="col-4 text-end">
                        <div class="icon icon-shape bg-gradient-primary shadow text-center border-radius-md">
                            <i class="fas fa-box text-lg opacity-10"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6 mb-xl-0 mb-4">
        <div class="card">
            <div class="card-body p-3">
                <div class="row">
                    <div class="col-8">
                        <div class="numbers">
                            <p class="text-sm mb-0 text-capitalize font-weight-bold">WiFi 7</p>
                            <h5 class="font-weight-bolder mb-0">{{ $stats['wifi7'] }}</h5>
                        </div>
                    </div>
                    <div class="col-4 text-end">
                        <div class="icon icon-shape bg-gradient-info shadow text-center border-radius-md">
                            <i class="fas fa-wifi text-lg opacity-10"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6">
        <div class="card">
            <div class="card-body p-3">
                <div class="row">
                    <div class="col-8">
                        <div class="numbers">
                            <p class="text-sm mb-0 text-capitalize font-weight-bold">Gaming</p>
                            <h5 class="font-weight-bolder mb-0">{{ $stats['gaming'] }}</h5>
                        </div>
                    </div>
                    <div class="col-4 text-end">
                        <div class="icon icon-shape bg-gradient-success shadow text-center border-radius-md">
                            <i class="fas fa-gamepad text-lg opacity-10"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6">
        <div class="card">
            <div class="card-body p-3">
                <div class="row">
                    <div class="col-8">
                        <div class="numbers">
                            <p class="text-sm mb-0 text-capitalize font-weight-bold">Mesh Systems</p>
                            <h5 class="font-weight-bolder mb-0">{{ $stats['mesh'] }}</h5>
                        </div>
                    </div>
                    <div class="col-4 text-end">
                        <div class="icon icon-shape bg-gradient-warning shadow text-center border-radius-md">
                            <i class="fas fa-network-wired text-lg opacity-10"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card mb-4">
            <div class="card-header pb-0">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Catalogo Modelli Router (2023-2025)</h6>
                    </div>
                </div>
            </div>
            
            <div class="card-body px-3 pt-3 pb-2">
                <form method="GET" class="row mb-3">
                    <div class="col-md-2">
                        <input type="text" name="search" class="form-control form-control-sm" placeholder="Cerca modello..." value="{{ request('search') }}">
                    </div>
                    <div class="col-md-2">
                        <select name="manufacturer_id" class="form-control form-control-sm">
                            <option value="">Tutti i produttori</option>
                            @foreach($manufacturers as $mfr)
                            <option value="{{ $mfr->id }}" {{ request('manufacturer_id') == $mfr->id ? 'selected' : '' }}>{{ $mfr->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="wifi" class="form-control form-control-sm">
                            <option value="">Tutti WiFi</option>
                            @foreach($wifiStandards as $standard)
                            <option value="{{ $standard }}" {{ request('wifi') == $standard ? 'selected' : '' }}>{{ $standard }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-1">
                        <select name="year" class="form-control form-control-sm">
                            <option value="">Anno</option>
                            @foreach($years as $year)
                            <option value="{{ $year }}" {{ request('year') == $year ? 'selected' : '' }}>{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <div class="form-check form-switch mt-1">
                            <input class="form-check-input" type="checkbox" name="gaming" value="1" {{ request('gaming') ? 'checked' : '' }}>
                            <label class="form-check-label">Gaming</label>
                        </div>
                    </div>
                    <div class="col-md-1">
                        <div class="form-check form-switch mt-1">
                            <input class="form-check-input" type="checkbox" name="mesh" value="1" {{ request('mesh') ? 'checked' : '' }}>
                            <label class="form-check-label">Mesh</label>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-sm btn-primary mb-0">
                            <i class="fas fa-search me-1"></i>Filtra
                        </button>
                        <a href="{{ route('acs.products') }}" class="btn btn-sm btn-outline-secondary mb-0">Reset</a>
                    </div>
                </form>

                <div class="table-responsive p-0">
                    <table class="table align-items-center mb-0">
                        <thead>
                            <tr>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Modello</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Produttore</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">WiFi</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Anno</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Velocità</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Prezzo</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">Gaming</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">Mesh</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Caratteristiche</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($products as $product)
                            <tr>
                                <td>
                                    <div class="d-flex px-2 py-1">
                                        <div class="d-flex flex-column justify-content-center">
                                            <h6 class="mb-0 text-sm">{{ $product->model_name }}</h6>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <p class="text-xs font-weight-bold mb-0">{{ $product->manufacturer->name }}</p>
                                </td>
                                <td>
                                    <span class="badge badge-sm {{ $product->getWifiStandardBadgeClass() }}">{{ $product->wifi_standard }}</span>
                                </td>
                                <td>
                                    <p class="text-xs mb-0">{{ $product->release_year }}</p>
                                </td>
                                <td>
                                    <p class="text-xs mb-0">{{ $product->max_speed }}</p>
                                </td>
                                <td>
                                    <p class="text-xs font-weight-bold mb-0">${{ number_format($product->price_usd) }}</p>
                                </td>
                                <td class="text-center">
                                    @if($product->gaming_features)
                                    <span class="badge badge-sm bg-gradient-success"><i class="fas fa-gamepad"></i></span>
                                    @else
                                    <span class="badge badge-sm bg-gradient-secondary">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($product->mesh_support)
                                    <span class="badge badge-sm bg-gradient-info"><i class="fas fa-network-wired"></i></span>
                                    @else
                                    <span class="badge badge-sm bg-gradient-secondary">-</span>
                                    @endif
                                </td>
                                <td>
                                    <p class="text-xs mb-0" style="max-width: 300px;">{{ Str::limit($product->key_features, 80) }}</p>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="text-center py-4">
                                    <i class="fas fa-inbox fa-3x text-secondary opacity-3 mb-3"></i>
                                    <p class="text-sm mb-0">Nessun modello trovato</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            
            @if($products->hasPages())
            <div class="card-footer">
                {{ $products->links() }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
