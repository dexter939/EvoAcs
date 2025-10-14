@extends('layouts.app')

@section('breadcrumb', 'TR-069/369 Data Models')
@section('page-title', 'Data Models TR-069/369')

@section('content')
<div class="row mb-4">
    <div class="col-xl-3 col-sm-6 mb-xl-0 mb-4">
        <div class="card">
            <div class="card-body p-3">
                <div class="row">
                    <div class="col-8">
                        <div class="numbers">
                            <p class="text-sm mb-0 text-capitalize font-weight-bold">Data Models</p>
                            <h5 class="font-weight-bolder mb-0">{{ $dataModels->count() }}</h5>
                        </div>
                    </div>
                    <div class="col-4 text-end">
                        <div class="icon icon-shape bg-gradient-primary shadow text-center border-radius-md">
                            <i class="fas fa-database text-lg opacity-10"></i>
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
                            <p class="text-sm mb-0 text-capitalize font-weight-bold">Parametri Totali</p>
                            <h5 class="font-weight-bolder mb-0">{{ $dataModels->sum('total_count') }}</h5>
                        </div>
                    </div>
                    <div class="col-4 text-end">
                        <div class="icon icon-shape bg-gradient-info shadow text-center border-radius-md">
                            <i class="fas fa-list text-lg opacity-10"></i>
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
                            <p class="text-sm mb-0 text-capitalize font-weight-bold">Oggetti</p>
                            <h5 class="font-weight-bolder mb-0">{{ $dataModels->sum('objects_count') }}</h5>
                        </div>
                    </div>
                    <div class="col-4 text-end">
                        <div class="icon icon-shape bg-gradient-success shadow text-center border-radius-md">
                            <i class="fas fa-folder text-lg opacity-10"></i>
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
                            <p class="text-sm mb-0 text-capitalize font-weight-bold">Parametri</p>
                            <h5 class="font-weight-bolder mb-0">{{ $dataModels->sum('parameters_count') }}</h5>
                        </div>
                    </div>
                    <div class="col-4 text-end">
                        <div class="icon icon-shape bg-gradient-warning shadow text-center border-radius-md">
                            <i class="fas fa-sliders-h text-lg opacity-10"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header pb-2">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="mb-0">Data Models Importati</h6>
                    </div>
                    <div class="col-md-6 text-end">
                        <form method="GET" action="{{ route('acs.data-models') }}" class="d-inline-flex gap-2">
                            <select name="protocol" class="form-select form-select-sm" onchange="this.form.submit()" style="width: auto;">
                                <option value="all" {{ $protocolFilter == 'all' ? 'selected' : '' }}>Tutti i Protocolli</option>
                                @foreach($protocols as $protocol)
                                    <option value="{{ $protocol }}" {{ $protocolFilter == $protocol ? 'selected' : '' }}>{{ $protocol }}</option>
                                @endforeach
                            </select>
                            <select name="vendor" class="form-select form-select-sm" onchange="this.form.submit()" style="width: auto;">
                                <option value="all" {{ $vendorFilter == 'all' ? 'selected' : '' }}>Tutti i Vendor</option>
                                @foreach($vendors as $vendor)
                                    <option value="{{ $vendor }}" {{ $vendorFilter == $vendor ? 'selected' : '' }}>{{ $vendor }}</option>
                                @endforeach
                            </select>
                        </form>
                    </div>
                </div>
            </div>
            <div class="card-body px-0 pt-0 pb-2">
                <div class="table-responsive p-0">
                    <table class="table align-items-center mb-0">
                        <thead>
                            <tr>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Vendor</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Modello</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Firmware</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Protocollo</th>
                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Oggetti</th>
                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Parametri</th>
                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Totale</th>
                                <th class="text-secondary opacity-7"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($dataModels as $dm)
                            <tr>
                                <td>
                                    <div class="d-flex px-2 py-1">
                                        <div class="d-flex flex-column justify-content-center">
                                            <h6 class="mb-0 text-sm">{{ $dm->vendor }}</h6>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <p class="text-xs font-weight-bold mb-0">{{ $dm->model_name }}</p>
                                </td>
                                <td>
                                    <p class="text-xs font-weight-bold mb-0">{{ $dm->firmware_version }}</p>
                                </td>
                                <td>
                                    <span class="badge badge-sm 
                                        @if($dm->protocol_version == 'TR-098') bg-gradient-primary
                                        @elseif($dm->protocol_version == 'TR-104') bg-gradient-success
                                        @elseif($dm->protocol_version == 'TR-140') bg-gradient-warning
                                        @elseif($dm->protocol_version == 'TR-181') bg-gradient-info
                                        @else bg-gradient-secondary
                                        @endif
                                    ">{{ $dm->protocol_version }}</span>
                                </td>
                                <td class="align-middle text-center">
                                    <span class="text-secondary text-xs font-weight-bold">{{ $dm->objects_count }}</span>
                                </td>
                                <td class="align-middle text-center">
                                    <span class="text-secondary text-xs font-weight-bold">{{ $dm->parameters_count }}</span>
                                </td>
                                <td class="align-middle text-center">
                                    <span class="text-secondary text-xs font-weight-bold">{{ $dm->total_count }}</span>
                                </td>
                                <td class="align-middle">
                                    <button class="btn btn-link text-secondary mb-0 btn-sm" onclick="viewParameters({{ $dm->id }}, '{{ $dm->vendor }} {{ $dm->model_name }} {{ $dm->protocol_version }}')">
                                        <i class="fas fa-eye text-xs"></i> Visualizza
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <p class="text-sm text-secondary mb-0">Nessun data model trovato</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="parametersModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="parametersModalTitle">Parametri Data Model</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="parametersModalBody">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Caricamento...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
function viewParameters(dataModelId, title) {
    document.getElementById('parametersModalTitle').textContent = 'Parametri: ' + title;
    const modal = new bootstrap.Modal(document.getElementById('parametersModal'));
    modal.show();
    
    fetch(`/acs/data-models/${dataModelId}/parameters`)
        .then(response => response.json())
        .then(data => {
            let html = '<div class="table-responsive"><table class="table table-sm">';
            html += '<thead><tr>';
            html += '<th>Path Parametro</th>';
            html += '<th>Tipo</th>';
            html += '<th>Accesso</th>';
            html += '<th>Descrizione</th>';
            html += '</tr></thead><tbody>';
            
            data.parameters.data.forEach(param => {
                const accessBadge = param.access_type == 'RW' ? 'bg-success' : (param.access_type == 'W' ? 'bg-warning' : 'bg-secondary');
                const typeBadge = param.is_object ? 'bg-primary' : 'bg-info';
                
                html += '<tr>';
                html += `<td><code class="text-xs">${param.parameter_path}</code></td>`;
                html += `<td><span class="badge ${typeBadge} badge-sm">${param.is_object ? 'Object' : param.parameter_type || 'N/A'}</span></td>`;
                html += `<td><span class="badge ${accessBadge} badge-sm">${param.access_type}</span></td>`;
                html += `<td class="text-xs">${param.description || '-'}</td>`;
                html += '</tr>';
            });
            
            html += '</tbody></table></div>';
            
            if (data.parameters.last_page > 1) {
                html += `<div class="text-center mt-3"><p class="text-sm">Pagina ${data.parameters.current_page} di ${data.parameters.last_page} (${data.parameters.total} totali)</p></div>`;
            }
            
            document.getElementById('parametersModalBody').innerHTML = html;
        })
        .catch(error => {
            document.getElementById('parametersModalBody').innerHTML = '<div class="alert alert-danger">Errore nel caricamento parametri</div>';
        });
}
</script>
@endsection
