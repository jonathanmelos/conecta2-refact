<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="author" content="Hermesseo">
    <title>Conecta Coworking</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        :root {
            --conecta-primary: #2c3e50;
            --conecta-secondary: #3498db;
            --conecta-accent: #e74c3c;
        }

        html, body {
            height: 100%;
        }

        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            background: #f7f8fb;
        }

        main {
            flex: 1;
        }

        .public-header {
            background: #fff;
            border-bottom: 1px solid #e6e8ef;
        }

        .public-brand {
            color: var(--conecta-primary);
            font-weight: 700;
            letter-spacing: 0.3px;
        }

        .footer-conecta {
            background-color: var(--conecta-primary);
            color: #fff;
            padding: 1rem 0;
            margin-top: auto;
        }

        .footer-conecta a {
            color: #fff;
            text-decoration: none;
        }

        .footer-conecta a:hover {
            color: var(--conecta-secondary);
            text-decoration: underline;
        }

        .footer-conecta .support-link {
            color: #2ecc71;
        }

        .footer-conecta .support-link:hover {
            color: #27ae60;
        }
    </style>
</head>
<body>
    <header class="public-header py-3">
        <div class="container d-flex justify-content-between align-items-center">
            <div class="public-brand fs-5">
                <i class="bi bi-building me-2"></i>Conecta Coworking
            </div>
            <div class="text-muted small">Autoservicio</div>
        </div>
    </header>

    <main class="py-4">
        <div class="container">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @yield('content')
        </div>
    </main>

    <footer class="footer-conecta">
        <div class="container">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center">
                <div class="mb-2 mb-md-0">
                    <span>Desarrollado por </span>
                    <a href="https://hermesseo.com" target="_blank">
                        <strong>Hermesseo</strong>
                    </a>
                </div>
                <div>
                    <a href="https://wa.me/593992902916" target="_blank" class="support-link">
                        <i class="bi bi-headset me-1"></i>
                        Soporte Tecnico: <strong>0992902916</strong>
                    </a>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
