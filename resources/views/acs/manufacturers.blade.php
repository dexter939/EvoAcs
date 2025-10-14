@extends('layouts.app')

@section('breadcrumb', 'Router Manufacturers & Products')
@section('page-title', 'Database Produttori e Modelli Router')

@section('content')
<div class="row mb-4">
    <div class="col-xl-2 col-sm-6 mb-xl-0 mb-4">
        <div class="card">
            <div class="card-body p-3">
                <div class="row">
                    <div class="col-8">
                        <div class="numbers">
                            <p class="text-sm mb-0 text-capitalize font-weight-bold">Produttori</p>
                            <h5 class="font-weight-bolder mb-0">{{ $stats['total'] }}</h5>
                        </div>
                    </div>
                    <div class="col-4 text-end">
                        <div class="icon icon-shape bg-gradient-primary shadow text-center border-radius-md">
                            <i class="fas fa-industry text-lg opacity-10"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-sm-6 mb-xl-0 mb-4">
        <div class="card">
            <div class="card-body p-3">
                <div class="row">
                    <div class="col-8">
                        <div class="numbers">
                            <p class="text-sm mb-0 text-capitalize font-weight-bold">Modelli</p>
                            <h5 class="font-weight-bolder mb-0">{{ $stats['total_products'] }}</h5>
                        </div>
                    </div>
                    <div class="col-4 text-end">
                        <div class="icon icon-shape bg-gradient-info shadow text-center border-radius-md">
                            <i class="fas fa-box text-lg opacity-10"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-sm-6 mb-xl-0 mb-4">
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
                        <div class="icon icon-shape bg-gradient-danger shadow text-center border-radius-md">
                            <i class="fas fa-wifi text-lg opacity-10"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-sm-6 mb-xl-0 mb-4">
        <div class="card">
            <div class="card-body p-3">
                <div class="row">
                    <div class="col-8">
                        <div class="numbers">
                            <p class="text-sm mb-0 text-capitalize font-weight-bold">TR-069</p>
                            <h5 class="font-weight-bolder mb-0">{{ $stats['tr069'] }}</h5>
                        </div>
                    </div>
                    <div class="col-4 text-end">
                        <div class="icon icon-shape bg-gradient-success shadow text-center border-radius-md">
                            <i class="fas fa-network-wired text-lg opacity-10"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-sm-6">
        <div class="card">
            <div class="card-body p-3">
                <div class="row">
                    <div class="col-8">
                        <div class="numbers">
                            <p class="text-sm mb-0 text-capitalize font-weight-bold">TR-369</p>
                            <h5 class="font-weight-bolder mb-0">{{ $stats['tr369'] }}</h5>
                        </div>
                    </div>
                    <div class="col-4 text-end">
                        <div class="icon icon-shape bg-gradient-warning shadow text-center border-radius-md">
                            <i class="fas fa-signal text-lg opacity-10"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-sm-6">
        <div class="card">
            <div class="card-body p-3">
                <div class="row">
                    <div class="col-8">
                        <div class="numbers">
                            <p class="text-sm mb-0 text-capitalize font-weight-bold">Paesi</p>
                            <h5 class="font-weight-bolder mb-0">{{ $stats['countries'] }}</h5>
                        </div>
                    </div>
                    <div class="col-4 text-end">
                        <div class="icon icon-shape bg-gradient-secondary shadow text-center border-radius-md">
                            <i class="fas fa-globe text-lg opacity-10"></i>
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
                <h6>Produttori Router e Modelli</h6>
            </div>
            
            <div class="card-body px-3 pt-3 pb-2">
                <form method="GET" class="row mb-4 align-items-end">
                    <div class="col-md-12 mb-2">
                        <label class="text-xs font-weight-bold mb-1">FILTRI PRODUTTORI</label>
                    </div>
                    <div class="col-md-2">
                        <input type="text" name="search" class="form-control form-control-sm" placeholder="Cerca produttore..." value="{{ request('search') }}">
                    </div>
                    <div class="col-md-2">
                        <select name="category" class="form-control form-control-sm">
                            <option value="">Tutte categorie</option>
                            @foreach($categories as $cat)
                            <option value="{{ $cat }}" {{ request('category') == $cat ? 'selected' : '' }}>{{ ucfirst($cat) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-1">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="tr069" value="1" {{ request('tr069') ? 'checked' : '' }}>
                            <label class="form-check-label text-xs">TR-069</label>
                        </div>
                    </div>
                    <div class="col-md-1">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="tr369" value="1" {{ request('tr369') ? 'checked' : '' }}>
                            <label class="form-check-label text-xs">TR-369</label>
                        </div>
                    </div>
                    
                    <div class="col-md-12 mb-2 mt-3">
                        <label class="text-xs font-weight-bold mb-1">FILTRI MODELLI</label>
                    </div>
                    <div class="col-md-2">
                        <select name="wifi" class="form-control form-control-sm">
                            <option value="">WiFi Standard</option>
                            @foreach($wifiStandards as $std)
                            <option value="{{ $std }}" {{ request('wifi') == $std ? 'selected' : '' }}>{{ $std }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-1">
                        <select name="year" class="form-control form-control-sm">
                            <option value="">Anno</option>
                            @foreach($years as $yr)
                            <option value="{{ $yr }}" {{ request('year') == $yr ? 'selected' : '' }}>{{ $yr }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-1">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="gaming" value="1" {{ request('gaming') ? 'checked' : '' }}>
                            <label class="form-check-label text-xs">Gaming</label>
                        </div>
                    </div>
                    <div class="col-md-1">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="mesh" value="1" {{ request('mesh') ? 'checked' : '' }}>
                            <label class="form-check-label text-xs">Mesh</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-sm btn-primary mb-0">
                            <i class="fas fa-search me-1"></i>Filtra
                        </button>
                        <a href="{{ route('acs.manufacturers') }}" class="btn btn-sm btn-outline-secondary mb-0">Reset</a>
                    </div>
                </form>

                <div class="accordion" id="manufacturersAccordion">
                    @forelse($manufacturers as $index => $manufacturer)
                    <div class="accordion-item border rounded mb-2">
                        <h2 class="accordion-header" id="heading{{ $manufacturer->id }}">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{ $manufacturer->id }}" aria-expanded="false" aria-controls="collapse{{ $manufacturer->id }}">
                                <div class="d-flex align-items-center w-100">
                                    <div class="me-3">
                                        <i class="fas fa-industry text-primary fa-lg"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0">{{ $manufacturer->name }}</h6>
                                        <div class="text-xs text-secondary mt-1">
                                            <span class="badge badge-sm {{ $manufacturer->getCategoryBadgeClass() }} me-1">{{ ucfirst($manufacturer->category) }}</span>
                                            <span class="me-2"><i class="fas fa-globe me-1"></i>{{ $manufacturer->country }}</span>
                                            @if($manufacturer->tr069_support)
                                            <span class="badge badge-sm bg-gradient-success me-1">TR-069</span>
                                            @endif
                                            @if($manufacturer->tr369_support)
                                            <span class="badge badge-sm bg-gradient-info">TR-369</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="text-end me-3">
                                        <span class="badge bg-gradient-dark">{{ $manufacturer->products_count }} modelli</span>
                                    </div>
                                </div>
                            </button>
                        </h2>
                        <div id="collapse{{ $manufacturer->id }}" class="accordion-collapse collapse" aria-labelledby="heading{{ $manufacturer->id }}" data-bs-parent="#manufacturersAccordion">
                            <div class="accordion-body p-3">
                                <div class="row mb-2">
                                    <div class="col-md-6">
                                        <p class="text-xs mb-1"><strong>Linee Prodotto:</strong> {{ $manufacturer->product_lines }}</p>
                                        <p class="text-xs mb-1"><strong>OUI Prefix:</strong> 
                                            @foreach($manufacturer->getOuiPrefixesArray() as $index => $oui)
                                                @if($index < 3)
                                                <code class="text-xxs">{{ $oui }}</code>@if(!$loop->last && $index < 2), @endif
                                                @elseif($index == 3)
                                                <span class="text-xxs">+{{ count($manufacturer->getOuiPrefixesArray()) - 3 }}</span>
                                                @break
                                                @endif
                                            @endforeach
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="text-xs mb-0">{{ $manufacturer->notes }}</p>
                                    </div>
                                </div>
                                
                                <hr class="horizontal dark mt-2 mb-3">
                                
                                @if($manufacturer->products_count > 0)
                                <div class="table-responsive">
                                    <table class="table table-sm align-items-center mb-0">
                                        <thead>
                                            <tr>
                                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder">Modello</th>
                                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder">WiFi</th>
                                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder">Anno</th>
                                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder">Velocità</th>
                                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder">Prezzo</th>
                                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder text-center">Features</th>
                                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder">Caratteristiche</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($manufacturer->products as $product)
                                            <tr>
                                                <td class="text-xs font-weight-bold">{{ $product->model_name }}</td>
                                                <td><span class="badge badge-sm {{ $product->getWifiStandardBadgeClass() }}">{{ $product->wifi_standard }}</span></td>
                                                <td class="text-xs">{{ $product->release_year }}</td>
                                                <td class="text-xs">{{ $product->max_speed }}</td>
                                                <td class="text-xs font-weight-bold">${{ number_format($product->price_usd) }}</td>
                                                <td class="text-center">
                                                    @if($product->gaming_features)
                                                    <span class="badge badge-sm bg-gradient-success me-1" title="Gaming"><i class="fas fa-gamepad"></i></span>
                                                    @endif
                                                    @if($product->mesh_support)
                                                    <span class="badge badge-sm bg-gradient-info" title="Mesh"><i class="fas fa-network-wired"></i></span>
                                                    @endif
                                                </td>
                                                <td class="text-xs" style="max-width: 300px;">{{ Str::limit($product->key_features, 80) }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                @else
                                <div class="text-center py-3">
                                    <i class="fas fa-box-open fa-2x text-secondary opacity-5"></i>
                                    <p class="text-xs text-secondary mb-0 mt-2">Nessun modello disponibile per questo produttore</p>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-5">
                        <i class="fas fa-inbox fa-3x text-secondary opacity-3 mb-3"></i>
                        <p class="text-sm mb-0">Nessun produttore trovato</p>
                    </div>
                    @endforelse
                </div>
            </div>
            
            @if($manufacturers->hasPages())
            <div class="card-footer">
                {{ $manufacturers->links() }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
