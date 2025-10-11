@extends('layouts.app')

@section('breadcrumb', 'Sottoscrizioni Eventi')
@section('page-title', 'Sottoscrizioni Eventi USP - ' . $device->serial_number)

@section('content')
<div class="row">
    <div class="col-12">
        <!-- Device Info Card -->
        <div class="card mb-3">
            <div class="card-body p-3">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h6 class="mb-0">
                            <i class="fas fa-satellite-dish me-2 text-info"></i>
                            Dispositivo: {{ $device->serial_number }}
                        </h6>
                        <p class="text-sm text-muted mb-0">
                            {{ $device->product_class }} | 
                            Endpoint ID: {{ $device->usp_endpoint_id }} | 
                            MTP: <span class="badge badge-sm bg-gradient-{{ $device->mtp_type === 'mqtt' ? 'warning' : 'info' }}">
                                {{ strtoupper($device->mtp_type) }}
                            </span>
                        </p>
                    </div>
                    <div class="col-md-4 text-end">
                        <a href="{{ route('acs.devices.show', $device->id) }}" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Torna al Dispositivo
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Subscriptions Table Card -->
        <div class="card mb-4">
            <div class="card-header pb-0 d-flex justify-content-between align-items-center">
                <div>
                    <h6>Sottoscrizioni Eventi TR-369</h6>
                    <p class="text-sm mb-0">Gestisci le sottoscrizioni evento per ricevere notifiche dal dispositivo</p>
                </div>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createSubscriptionModal">
                    <i class="fas fa-plus me-2"></i>Nuova Sottoscrizione
                </button>
            </div>
            <div class="card-body px-0 pt-0 pb-2">
                @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show mx-4 mt-3" role="alert">
                    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                @endif
                @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show mx-4 mt-3" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                @endif

                <div class="table-responsive p-0">
                    <table class="table align-items-center mb-0">
                        <thead>
                            <tr>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Subscription ID</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Event Path</th>
                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Stato</th>
                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Notifiche</th>
                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Ultima Notifica</th>
                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($subscriptions as $subscription)
                            <tr>
                                <td>
                                    <div class="d-flex px-2 py-1">
                                        <div class="d-flex flex-column justify-content-center">
                                            <h6 class="mb-0 text-xs font-monospace">{{ Str::limit($subscription->subscription_id, 25) }}</h6>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <p class="text-xs text-secondary mb-0 font-monospace">{{ $subscription->event_path }}</p>
                                    @if(!empty($subscription->reference_list) && count($subscription->reference_list) > 0)
                                    <small class="text-xxs text-muted">
                                        <i class="fas fa-list me-1"></i>{{ count($subscription->reference_list) }} reference path(s)
                                    </small>
                                    @endif
                                </td>
                                <td class="align-middle text-center text-sm">
                                    <span class="badge badge-sm bg-gradient-{{ $subscription->is_active ? 'success' : 'secondary' }}">
                                        {{ $subscription->is_active ? 'Attiva' : 'Inattiva' }}
                                    </span>
                                </td>
                                <td class="align-middle text-center">
                                    <span class="text-xs font-weight-bold">
                                        <i class="fas fa-bell me-1"></i>{{ $subscription->notification_count }}
                                    </span>
                                </td>
                                <td class="align-middle text-center">
                                    <span class="text-xs text-secondary">
                                        {{ $subscription->last_notification_at ? $subscription->last_notification_at->diffForHumans() : 'Mai' }}
                                    </span>
                                </td>
                                <td class="align-middle text-center">
                                    @if($subscription->is_active)
                                    <button class="btn btn-link text-danger px-2 mb-0" onclick="deleteSubscription({{ $subscription->id }}, '{{ Str::limit($subscription->event_path, 30) }}')">
                                        <i class="fas fa-trash text-xs"></i>
                                    </button>
                                    @else
                                    <span class="text-xs text-muted">-</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center text-sm text-muted py-4">
                                    <i class="fas fa-bell-slash fa-2x mb-2 opacity-5"></i>
                                    <p class="mb-0">Nessuna sottoscrizione evento attiva.</p>
                                    <p class="mb-0 text-xs">Clicca "Nuova Sottoscrizione" per crearne una.</p>
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

<!-- Modal Crea Sottoscrizione -->
<div class="modal fade" id="createSubscriptionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Crea Nuova Sottoscrizione Evento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('acs.devices.subscriptions.store', $device->id) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Event Path *</label>
                        <input type="text" class="form-control font-monospace" name="event_path" placeholder="Device.WiFi.Radio.*.ChannelChange!" required>
                        <small class="text-muted">Percorso evento TR-181/TR-369 (es: Device.WiFi.Radio.*.ChannelChange!)</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reference List (opzionale)</label>
                        <textarea class="form-control font-monospace" name="reference_list" rows="4" placeholder="Device.WiFi.Radio.1.Channel&#10;Device.WiFi.Radio.1.OperatingFrequencyBand"></textarea>
                        <small class="text-muted">Lista parametri da includere nelle notifiche (uno per riga)</small>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="notification_retry" value="1" checked>
                        <label class="form-check-label">Notification Retry</label>
                        <small class="d-block text-muted">Riprova l'invio notifica in caso di errore</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-primary">Crea Sottoscrizione</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Form (hidden) -->
<form id="deleteSubscriptionForm" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>

@endsection

@push('scripts')
<script>
function deleteSubscription(subscriptionId, eventPath) {
    if (confirm(`Sei sicuro di voler eliminare la sottoscrizione per:\n\n${eventPath}\n\nQuesta azione invierà un messaggio DELETE al dispositivo.`)) {
        const form = document.getElementById('deleteSubscriptionForm');
        form.action = `{{ route('acs.devices.subscriptions.destroy', ['id' => $device->id, 'subscriptionId' => '__ID__']) }}`.replace('__ID__', subscriptionId);
        form.submit();
    }
}
</script>
@endpush
