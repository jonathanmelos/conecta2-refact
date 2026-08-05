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
            <li class="nav-item"><a class="nav-link active" href="{{ route('admin.mcp.connections') }}">Conexiones</a></li>
            <li class="nav-item"><a class="nav-link" href="{{ route('admin.mcp.audit') }}">Auditoría</a></li>
        </ul>

        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        <div class="card shadow-sm border-0">
            <div class="card-body">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>Usuario</th>
                            <th>Creada</th>
                            <th>Último uso</th>
                            <th>Expira</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($tokens as $token)
                            <tr>
                                <td>{{ $token->client_name }}</td>
                                <td>{{ $token->user_name }}</td>
                                <td>{{ $token->created_at?->format('d/m/Y H:i') }}</td>
                                <td>{{ $token->last_used_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                <td>{{ $token->refresh_expires_at?->format('d/m/Y') }}</td>
                                <td>
                                    <form method="POST" action="{{ route('admin.mcp.connections.revoke', $token->id) }}"
                                        onsubmit="return confirm('¿Revocar esta conexión?');">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Revocar</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-muted">No hay conexiones activas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
