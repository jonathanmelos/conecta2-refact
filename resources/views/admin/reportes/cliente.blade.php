@extends('layouts.conecta')

@section('content')
<div class="row">
    <div class="col-12 col-lg-10 mx-auto">
        <div class="card mb-4">
            <div class="card-body">
                <h4 class="mb-3">Historia Cliente</h4>
                <form method="GET" action="{{ route('admin.reportes.cliente') }}" autocomplete="off">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-8 position-relative">
                            <label for="cliente_search" class="form-label">Cliente</label>
                            <input type="text" id="cliente_search" class="form-control" placeholder="Buscar cliente"
                                   value="{{ $client ? ($client->document_number . ' - ' . $client->full_name) : '' }}">
                            <ul id="cliente_results" class="list-group position-absolute w-100 d-none" style="z-index: 10;"></ul>
                            <input type="hidden" name="documento" id="cliente_documento" value="{{ $documento }}">
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-info w-100">Consultar registros</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        @if($documento && !$client)
            <div class="alert alert-warning">Cliente no encontrado.</div>
        @endif

        @if($client)
            <div class="row">
                <div class="col-lg-4">
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="mb-3">Datos del Cliente</h5>
                            <div class="mb-2"><strong>Nombre:</strong> {{ $client->full_name }}</div>
                            <div class="mb-2"><strong>Documento:</strong> {{ $client->document_number }}</div>
                            <div class="mb-2"><strong>Correo:</strong> {{ $client->email ?? '-' }}</div>
                            <div class="mb-2"><strong>Direccion:</strong> {{ $client->address ?? '-' }}</div>
                            <div class="mb-2"><strong>Telefono:</strong> {{ $client->phone ?? '-' }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="mb-3">Historial Planes</h5>
                            @if($subscriptions->isEmpty())
                                <div class="text-muted">Cliente sin plan.</div>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Codigo</th>
                                                <th>Plan</th>
                                                <th>Fecha Inicio</th>
                                                <th>Fecha Fin</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($subscriptions as $subscription)
                                                <tr onclick="window.location='{{ route('admin.clientes.detalleRegistro', [$client, $subscription]) }}'">
                                                    <td>{{ $subscription->id }}</td>
                                                    <td>{{ $subscription->plan->name ?? 'Plan' }}</td>
                                                    <td>{{ $subscription->start_date->format('Y-m-d') }}</td>
                                                    <td>{{ $subscription->end_date->format('Y-m-d') }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const input = document.getElementById('cliente_search');
    const list = document.getElementById('cliente_results');
    const hidden = document.getElementById('cliente_documento');

    let controller = null;

    function clearList() {
        list.innerHTML = '';
        list.classList.add('d-none');
    }

    input.addEventListener('input', function() {
        const term = input.value.trim();
        hidden.value = '';
        if (term.length < 3) {
            clearList();
            return;
        }

        if (controller) {
            controller.abort();
        }
        controller = new AbortController();

        fetch(`{{ route('admin.registro.buscar') }}?search=${encodeURIComponent(term)}`, { signal: controller.signal })
            .then((res) => res.json())
            .then((data) => {
                list.innerHTML = '';
                if (!data.length) {
                    clearList();
                    return;
                }

                data.forEach((client) => {
                    const item = document.createElement('li');
                    item.className = 'list-group-item list-group-item-action';
                    item.textContent = `${client.document_number} - ${client.first_name} ${client.last_name}`;
                    item.dataset.documento = client.document_number;
                    item.addEventListener('click', () => {
                        input.value = item.textContent;
                        hidden.value = item.dataset.documento;
                        clearList();
                    });
                    list.appendChild(item);
                });
                list.classList.remove('d-none');
            })
            .catch(() => {});
    });

    document.addEventListener('click', function(event) {
        if (!list.contains(event.target) && event.target !== input) {
            clearList();
        }
    });
});
</script>
@endpush
