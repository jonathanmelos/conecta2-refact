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
            <li class="nav-item"><a class="nav-link" href="{{ route('admin.mcp.permissions') }}">Permisos</a></li>
            <li class="nav-item"><a class="nav-link" href="{{ route('admin.mcp.connections') }}">Conexiones</a></li>
            <li class="nav-item"><a class="nav-link active" href="{{ route('admin.mcp.audit') }}">Auditoría</a></li>
        </ul>

        <div class="card shadow-sm border-0">
            <div class="card-body">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Usuario</th>
                            <th>Herramienta</th>
                            <th>Resultado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($logs as $log)
                            <tr>
                                <td>{{ $log->created_at?->format('d/m/Y H:i:s') }}</td>
                                <td>{{ $log->user?->name }}</td>
                                <td><code>{{ $log->tool_name }}</code></td>
                                <td>
                                    @if ($log->result_status === 'success')
                                        <span class="badge bg-success">success</span>
                                    @else
                                        <span class="badge bg-danger">{{ $log->result_status }}</span>
                                    @endif
                                    @if ($log->result_summary)
                                        <br><small class="text-muted">{{ $log->result_summary }}</small>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-muted">Sin actividad registrada todavía.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                {{ $logs->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
