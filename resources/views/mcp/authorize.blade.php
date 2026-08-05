<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Autorizar conexión MCP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f0f2f5; }
        .consent-box { max-width: 440px; margin: 90px auto; }
        .consent-box .card { border: none; box-shadow: 0 2px 12px rgba(0,0,0,.08); }
        .scope-note { background: #f6f7f8; border-radius: .5rem; padding: .85rem 1rem; font-size: .875rem; color: #555; }
    </style>
</head>
<body>
    <div class="consent-box">
        <div class="card">
            <div class="card-body p-4">
                <h4 class="mb-3">Autorizar acceso MCP</h4>
                <p>
                    <strong>{{ $client->client_name }}</strong> quiere conectarse a
                    <strong>Sistema Conecta Coworking</strong> en tu nombre para <strong>consultar datos</strong> (sin crear, editar ni eliminar nada).
                </p>
                <div class="scope-note mb-4">
                    El acceso concedido está limitado por los recursos activados en Admin → MCP Connector → Permisos.
                </div>
                <form method="POST" action="{{ route('mcp.oauth.authorize.submit') }}">
                    @csrf
                    @foreach ($params as $key => $value)
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endforeach
                    <div class="d-flex gap-2">
                        <button type="submit" name="decision" value="allow" class="btn btn-primary flex-fill">Autorizar</button>
                        <button type="submit" name="decision" value="deny" class="btn btn-outline-secondary flex-fill">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
