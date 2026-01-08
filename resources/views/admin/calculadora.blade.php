@extends('layouts.conecta')

@section('content')
<div class="row">
    <div class="col-md-7 col-lg-6">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Calculadora de Plan</h5>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('admin.calculadora') }}">
                    <div class="mb-3 row">
                        <label for="personas" class="col-sm-4 col-form-label">Número de personas</label>
                        <div class="col-sm-8">
                            <input type="number" class="form-control" id="personas" name="personas" min="1" value="{{ $inputs['personas'] ?? 1 }}">
                            <small class="form-text text-muted">¿Cuántas personas acompañarían al titular?<br>Ingresar el número más el titular</small>
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <label for="horas" class="col-sm-4 col-form-label">Horas por visita</label>
                        <div class="col-sm-8">
                            <input type="number" class="form-control" id="horas" name="horas" min="0" value="{{ $inputs['horas'] ?? 0 }}">
                            <small class="form-text text-muted">Promedio de horas que utilizarían el espacio de Coworking</small>
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <label for="visita" class="col-sm-4 col-form-label">Cuantas veces por semana</label>
                        <div class="col-sm-8">
                            <input type="number" class="form-control" id="visita" name="visita" min="0" value="{{ $inputs['visita'] ?? 0 }}">
                            <small class="form-text text-muted">Días a la semana que visitarían Conecta Cowork</small>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary btn-lg w-100">Calcular</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-5 col-lg-6">
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">Plan sugerido</h5>
            </div>
            <div class="card-body">
                @if($suggestedPlan)
                    <h6 class="mb-3">Total horas al mes: <strong>{{ $horasEstimadas }}</strong></h6>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Plan</th>
                                    <th>Cowork</th>
                                    <th>Sala</th>
                                    <th>Impre</th>
                                    <th>Evento</th>
                                    <th>Precio</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="table-success">
                                    <th>{{ $suggestedPlan->name }}</th>
                                    <td>{{ $suggestedPlan->cowork_hours }}</td>
                                    <td>{{ $suggestedPlan->meeting_room_hours }}</td>
                                    <td>{{ $suggestedPlan->prints_included }}</td>
                                    <td>{{ $suggestedPlan->events_included }}</td>
                                    <td>{{ number_format($suggestedPlan->price, 2) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-muted">Ingresa los datos para sugerir un Plan</p>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-secondary text-white">
                <h6 class="mb-0">Otros planes</h6>
            </div>
            <div class="card-body">
                @if($otherPlans->isEmpty())
                    <p class="text-muted">No hay otros planes disponibles.</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Plan</th>
                                    <th>Cowork</th>
                                    <th>Sala</th>
                                    <th>Impre</th>
                                    <th>Evento</th>
                                    <th>Precio</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($otherPlans as $plan)
                                    <tr>
                                        <th>{{ $plan->name }}</th>
                                        <td>{{ $plan->cowork_hours }}</td>
                                        <td>{{ $plan->meeting_room_hours }}</td>
                                        <td>{{ $plan->prints_included }}</td>
                                        <td>{{ $plan->events_included }}</td>
                                        <td>{{ number_format($plan->price, 2) }}</td>
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
@endsection
