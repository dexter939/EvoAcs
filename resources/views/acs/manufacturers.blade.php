@extends('layouts.app')

@section('breadcrumb', 'Router Manufacturers')
@section('page-title', 'Database Produttori Router')

@section('content')
<div class="row mb-4">
    <div class="col-xl-3 col-sm-6 mb-xl-0 mb-4">
        <div class="card">
            <div class="card-body p-3">
                <div class="row">
                    <div class="col-8">
                        <div class="numbers">
                            <p class="text-sm mb-0 text-capitalize font-weight-bold">Totale Produttori</p>
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
    <div class="col-xl-3 col-sm-6 mb-xl-0 mb-4">
        <div class="card">
            <div class="card-body p-3">
                <div class="row">
                    <div class="col-8">
                        <div class="numbers">
                            <p class="text-sm mb-0 text-capitalize font-weight-bold">TR-069 Support</p>
                            <h5 class="font-weight-bolder mb-0">{{ $stats['tr069'] }}</h5>
                        </div>
                    </div>
                    <div class="col-4 text-end">
                        <div class="icon icon-shape bg-gradient-info shadow text-center border-radius-md">
                            <i class="fas fa-network-wired text-lg opacity-10"></i>
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
                            <p class="text-sm mb-0 text-capitalize font-weight-bold">TR-369 Support</p>
                            <h5 class="font-weight-bolder mb-0">{{ $stats['tr369'] }}</h5>
                        </div>
                    </div>
                    <div class="col-4 text-end">
                        <div class="icon icon-shape bg-gradient-success shadow text-center border-radius-md">
                            <i class="fas fa-signal text-lg opacity-10"></i>
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
                            <p class="text-sm mb-0 text-capitalize font-weight-bold">Paesi</p>
                            <h5 class="font-weight-bolder mb-0">{{ $stats['countries'] }}</h5>
                        </div>
                    </div>
                    <div class="col-4 text-end">
                        <div class="icon icon-shape bg-gradient-warning shadow text-center border-radius-md">
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
                <div class="row">
                    <div class="col-md-6">
                        <h6>Lista Produttori Router Domestici</h6>
                    </div>
                </div>
            </div>
            
            <div class="card-body px-3 pt-3 pb-2">
                <form method="GET" class="row mb-3">
                    <div class="col-md-3">
                        <input type="text" name="search" class="form-control form-control-sm" placeholder="Cerca produttore..." value="{{ request('search') }}">
                    </div>
                    <div class="col-md-2">
                        <select name="category" class="form-control form-control-sm">
                            <option value="">Tutte le categorie</option>
                            @foreach($categories as $cat)
                            <option value="{{ $cat }}" {{ request('category') == $cat ? 'selected' : '' }}>{{ ucfirst($cat) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="tr069" value="1" {{ request('tr069') ? 'checked' : '' }}>
                            <label class="form-check-label">Solo TR-069</label>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="tr369" value="1" {{ request('tr369') ? 'checked' : '' }}>
                            <label class="form-check-label">Solo TR-369</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-sm btn-primary mb-0">
                            <i class="fas fa-search me-1"></i>Filtra
                        </button>
                        <a href="{{ route('acs.manufacturers') }}" class="btn btn-sm btn-outline-secondary mb-0">Reset</a>
                    </div>
                </form>

                <div class="table-responsive p-0">
                    <table class="table align-items-center mb-0">
                        <thead>
                            <tr>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Produttore</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Categoria</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Paese</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Linee Prodotto</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">TR-069</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">TR-369</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">OUI Prefix</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Note</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($manufacturers as $manufacturer)
                            <tr>
                                <td>
                                    <div class="d-flex px-2 py-1">
                                        <div class="d-flex flex-column justify-content-center">
                                            <h6 class="mb-0 text-sm">{{ $manufacturer->name }}</h6>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-sm {{ $manufacturer->getCategoryBadgeClass() }}">{{ ucfirst($manufacturer->category) }}</span>
                                </td>
                                <td>
                                    <p class="text-xs font-weight-bold mb-0">{{ $manufacturer->country }}</p>
                                </td>
                                <td>
                                    <p class="text-xs mb-0" style="max-width: 200px;">{{ $manufacturer->product_lines }}</p>
                                </td>
                                <td class="text-center">
                                    @if($manufacturer->tr069_support)
                                    <span class="badge badge-sm bg-gradient-success">✓</span>
                                    @else
                                    <span class="badge badge-sm bg-gradient-secondary">✗</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($manufacturer->tr369_support)
                                    <span class="badge badge-sm bg-gradient-success">✓</span>
                                    @else
                                    <span class="badge badge-sm bg-gradient-secondary">✗</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="text-xs">
                                        @foreach($manufacturer->getOuiPrefixesArray() as $index => $oui)
                                            @if($index < 2)
                                            <code class="text-xxs">{{ $oui }}</code>@if(!$loop->last && $index < 1),@endif
                                            @elseif($index == 2)
                                            <span class="text-xxs">+{{ count($manufacturer->getOuiPrefixesArray()) - 2 }}</span>
                                            @break
                                            @endif
                                        @endforeach
                                    </div>
                                </td>
                                <td>
                                    <p class="text-xs mb-0" style="max-width: 250px;">{{ Str::limit($manufacturer->notes, 60) }}</p>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <i class="fas fa-inbox fa-3x text-secondary opacity-3 mb-3"></i>
                                    <p class="text-sm mb-0">Nessun produttore trovato</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
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
