@extends('layouts.conecta')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="d-flex align-items-center mb-4">
            <i class="bi bi-diagram-3-fill fs-3 me-3 text-primary"></i>
            <div>
                <h4 class="mb-0 fw-bold">MCP Connector</h4>
                <small class="text-muted">Conexión de solo lectura para consultar datos vía Claude</small>
            </div>
        </div>

        <ul class="nav nav-tabs mb-4">
            <li class="nav-item"><a class="nav-link active" href="{{ route('admin.mcp.permissions') }}">Permisos</a></li>
            <li class="nav-item"><a class="nav-link" href="{{ route('admin.mcp.connections') }}">Conexiones</a></li>
            <li class="nav-item"><a class="nav-link" href="{{ route('admin.mcp.audit') }}">Auditoría</a></li>
        </ul>

        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        <div class="card shadow-sm border-0">
            <div class="card-body">
                <p class="text-muted">
                    Activa solo los recursos que quieres permitir consultar. Esta conexión es <strong>exclusivamente de lectura</strong> —
                    ninguna herramienta puede crear, editar ni eliminar datos, sin importar qué actives aquí.
                    Además, solo usuarios con rol <strong>admin</strong> pueden usar esta conexión.
                </p>

                <form method="POST" action="{{ route('admin.mcp.permissions.update') }}">
                    @csrf
                    <div class="row g-3 mt-2">
                        @foreach ($permissions as $permission)
                            <div class="col-md-6">
                                <div class="form-check form-switch p-3 border rounded">
                                    <input class="form-check-input" type="checkbox" role="switch"
                                        id="resource-{{ $permission->resource }}"
                                        name="resources[]" value="{{ $permission->resource }}"
                                        {{ $permission->read_enabled ? 'checked' : '' }}>
                                    <label class="form-check-label fw-semibold" for="resource-{{ $permission->resource }}">
                                        {{ ucfirst(str_replace('_', ' ', $permission->resource)) }}
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <button type="submit" class="btn btn-primary mt-4">Guardar permisos</button>
                </form>
            </div>
        </div>

        <div class="card shadow-sm border-0 mt-4">
            <div class="card-body">
                <h6 class="fw-bold">Conectar desde Claude.ai</h6>
                <ol class="mb-3">
                    <li>En Claude.ai ve a Configuración → Conectores → Agregar conector personalizado.</li>
                    <li>Pega esta URL como endpoint del servidor MCP: <code>{{ $mcpEndpoint }}</code></li>
                    <li>Claude descubrirá los endpoints OAuth automáticamente y te pedirá iniciar sesión.</li>
                </ol>
                <p class="text-muted mb-0"><small>Metadata OAuth (referencia): <code>{{ $metadataEndpoint }}</code></small></p>
            </div>
        </div>
    </div>
</div>
@endsection
