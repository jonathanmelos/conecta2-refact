@extends('layouts.conecta')

@section('content')
@php
    $hoy = now()->format('Y-m-d');
    $fechaInicio = now()->startOfMonth()->format('Y-m-d');
@endphp
    <div class="row">
        <div class="col-12 col-lg-8 mx-auto">
            <div class="card">
                <div class="card-body text-center">
                    <h4 class="mb-4">Elige una opcion para visualizar reportes</h4>
                <div class="d-flex flex-column flex-md-row justify-content-center gap-3">
                    <a href="{{ route('admin.reportes.fecha', ['estado' => '%', 'fecha' => $hoy]) }}" class="btn btn-success">
                        Registros por Dia
                    </a>
                    <a href="{{ route('admin.reportes.cliente') }}" class="btn btn-primary">
                        Historia Cliente
                    </a>
                    <a href="{{ route('admin.reportes.periodo', ['estado' => '%', 'fecha_i' => $fechaInicio, 'busqueda' => 'a', 'fecha' => $hoy]) }}" class="btn btn-warning">
                        Por fecha
                    </a>
                </div>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-body d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-2">
                    <div>
                        <h5 class="mb-1">Conexion API</h5>
                        <p class="mb-0 text-muted">Genera un token para Power BI o IA desde el perfil.</p>
                    </div>
                    <a href="{{ route('profile.edit') }}" class="btn btn-outline-primary">
                        Configurar API
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
