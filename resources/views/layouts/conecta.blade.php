<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="author" content="Hermesseo">
    <title>Conecta Coworking</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- FullCalendar CSS -->
    <link href='https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.10.2/fullcalendar.min.css' rel='stylesheet'/>

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
        }

        main {
            flex: 1;
        }

        /* Navbar personalizado */
        .navbar-conecta {
            background-color: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .navbar-conecta .navbar-brand img {
            max-height: 50px;
            width: auto;
        }

        .navbar-conecta .nav-link {
            color: var(--conecta-primary);
            font-weight: 500;
            padding: 0.5rem 1rem;
            transition: color 0.2s;
        }

        .navbar-conecta .nav-link:hover,
        .navbar-conecta .nav-link.active {
            color: var(--conecta-secondary);
        }

        .navbar-conecta .nav-link.active {
            border-bottom: 2px solid var(--conecta-secondary);
        }

        /* Dropdown de usuario */
        .user-dropdown .dropdown-toggle {
            color: var(--conecta-primary);
            font-weight: 500;
        }

        .user-dropdown .dropdown-toggle::after {
            margin-left: 0.5rem;
        }

        /* Footer */
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

        /* Alerta lateral animada */
        .custom-alert {
            position: fixed;
            top: 80px;
            left: -350px;
            width: 320px;
            background-color: #fff;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transition: left 0.3s ease-in-out;
            z-index: 1050;
            padding: 1.5rem;
            border-radius: 0 8px 8px 0;
            border-left: 4px solid var(--conecta-secondary);
        }

        .custom-alert.show {
            left: 0;
        }

        /* Tablas compactas */
        .custom-alert table {
            font-size: 0.85em;
        }

        /* Filas clicables */
        tr[onclick] {
            cursor: pointer;
        }

        /* Resaltar clientes sin plan */
        .table-warning {
            background-color: #fff3cd !important;
        }

        .table-warning:hover {
            background-color: #ffe69c !important;
        }

        /* Responsive adjustments */
        @media (max-width: 991.98px) {
            .navbar-conecta .navbar-collapse {
                padding-top: 1rem;
                border-top: 1px solid #eee;
                margin-top: 0.5rem;
            }

            .navbar-conecta .nav-link {
                padding: 0.75rem 0;
                border-bottom: 1px solid #f0f0f0;
            }

            .navbar-conecta .nav-link.active {
                border-bottom: 1px solid var(--conecta-secondary);
            }

            .user-dropdown {
                padding-top: 0.5rem;
                border-top: 1px solid #eee;
                margin-top: 0.5rem;
            }
        }

        @media (max-width: 576px) {
            .footer-conecta {
                text-align: center;
            }

            .footer-conecta .d-flex {
                flex-direction: column;
                gap: 0.5rem;
            }
        }
    </style>

    @stack('styles')
</head>
<body>
    {{-- Alerta lateral --}}
    @if(isset($showAlert) && $showAlert)
    <div id="customAlert" class="custom-alert">
        <button type="button" class="btn-close position-absolute top-0 end-0 m-2" aria-label="Cerrar"></button>
        <h5 class="alert-heading mb-3"><i class="bi bi-bell-fill text-warning me-2"></i>No lo olvides!</h5>
        @yield('alert-content')
    </div>
    @endif

    {{-- Navbar --}}
    <nav class="navbar navbar-expand-lg navbar-conecta sticky-top">
        <div class="container">
            {{-- Logo a la izquierda --}}
            <a class="navbar-brand" href="{{ route('admin.registro.index') }}">
                <img src="{{ asset('images/logo.jpg') }}" alt="Conecta Coworking">
            </a>

            {{-- Boton hamburguesa para movil --}}
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            {{-- Menu colapsable --}}
            <div class="collapse navbar-collapse" id="navbarMain">
                {{-- Menu de navegacion principal --}}
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    @if(auth()->user()->role === 'admin')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.registro.*') ? 'active' : '' }}" href="{{ route('admin.registro.index') }}">
                                <i class="bi bi-clipboard-check d-lg-none me-2"></i>Registro
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.registro.pendientes') ? 'active' : '' }}" href="{{ route('admin.registro.pendientes') }}">
                                <i class="bi bi-exclamation-triangle d-lg-none me-2"></i>Pendientes
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.diario') ? 'active' : '' }}" href="{{ route('admin.diario', ['fecha' => date('Y-m-d')]) }}">
                                <i class="bi bi-calendar-day d-lg-none me-2"></i>Diario
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.clientes.*') ? 'active' : '' }}" href="{{ route('admin.clientes.index') }}">
                                <i class="bi bi-people d-lg-none me-2"></i>Clientes
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.planes.*') ? 'active' : '' }}" href="{{ route('admin.planes.index') }}">
                                <i class="bi bi-card-list d-lg-none me-2"></i>Planes
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.calculadora') ? 'active' : '' }}" href="{{ route('admin.calculadora') }}">
                                <i class="bi bi-calculator d-lg-none me-2"></i>Calculadora
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.reportes.*') ? 'active' : '' }}" href="{{ route('admin.reportes.index') }}">
                                <i class="bi bi-bar-chart d-lg-none me-2"></i>Reportes
                            </a>
                        </li>
                       <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.calendario.*') ? 'active' : '' }}" href="{{ route('admin.calendario.index') }}">
                                <i class="bi bi-calendar3 d-lg-none me-2"></i>Calendario
                            </a>
                        </li> 
                    @else
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('regular.registro.*') ? 'active' : '' }}" href="{{ route('regular.registro.index') }}">
                                <i class="bi bi-clipboard-check d-lg-none me-2"></i>Registro
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('regular.clientes.*') ? 'active' : '' }}" href="{{ route('regular.clientes.index') }}">
                                <i class="bi bi-people d-lg-none me-2"></i>Clientes
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('regular.calculadora') ? 'active' : '' }}" href="{{ route('regular.calculadora') }}">
                                <i class="bi bi-calculator d-lg-none me-2"></i>Calculadora
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('regular.reportes.*') ? 'active' : '' }}" href="{{ route('regular.reportes.index') }}">
                                <i class="bi bi-bar-chart d-lg-none me-2"></i>Reportes
                            </a>
                        </li>
                    @endif
                </ul>

                {{-- Menu de usuario a la derecha --}}
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item me-2">
                        <span class="nav-link d-flex align-items-center">
                            <i class="bi bi-person-circle me-2 fs-5"></i>
                            <span>{{ auth()->user()->name }}</span>
                        </span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('profile.edit') }}" title="Perfil">
                            <i class="bi bi-gear fs-5"></i>
                        </a>
                    </li>
                    <li class="nav-item">
                        <form method="POST" action="{{ route('logout') }}" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-link nav-link text-danger p-0 px-2" title="Cerrar Sesion">
                                <i class="bi bi-box-arrow-right fs-5"></i>
                            </button>
                        </form>
                    </li>
                </ul>

            </div>
        </div>
    </nav>

    {{-- Contenido principal --}}
    <main class="py-4">
        <div class="container">
            {{-- Mensajes flash --}}
            @if(session('success'))
                @if(session('success_client_name'))
                <div class="alert alert-success alert-dismissible fade show d-flex flex-column flex-md-row align-items-start align-items-md-center gap-2" role="alert">
                    <div>
                        <i class="bi bi-check-circle-fill me-2"></i>
                        <strong>Exito.</strong> Cliente {{ session('success_client_name') }} creado correctamente.
                    </div>
                    @if(session('success_client_doc') && auth()->check() && auth()->user()->role === 'admin')
                        <a href="{{ route('admin.registro.index', ['doc' => session('success_client_doc'), 'open_service' => 1]) }}" class="btn btn-success btn-sm">
                            <i class="bi bi-play-fill me-1"></i>Iniciar registro
                        </a>
                    @endif
                    <button type="button" class="btn-close ms-md-auto" data-bs-dismiss="alert"></button>
                </div>
                @else
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <strong>Exito.</strong> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                @endif
            @endif

            @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <strong>Error!</strong> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            @if(session('warning'))
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-circle-fill me-2"></i>
                <strong>Atencion!</strong> {{ session('warning') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            @yield('content')
        </div>
    </main>

    {{-- Footer --}}
    <footer class="footer-conecta">
        <div class="container">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center">
                <div class="mb-2 mb-md-0">
                    <span>Desarrollado por </span>
                    <a href="https://hermesseo.com" target="_blank" rel="noopener noreferrer">
                        <strong>Hermesseo</strong>
                    </a>
                </div>
                <div>
                    <a href="https://wa.me/593992902916" target="_blank" rel="noopener noreferrer" class="support-link">
                        <i class="bi bi-headset me-1"></i>
                        Soporte Tecnico: <strong>0992902916</strong>
                    </a>
                </div>
            </div>
        </div>
    </footer>

    {{-- Scripts --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Moment.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>

    <!-- FullCalendar -->
    <script src='https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.10.2/fullcalendar.min.js'></script>

    <!-- XLSX -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.0/xlsx.full.min.js"></script>

    {{-- Script para alerta lateral --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var alert = document.getElementById('customAlert');
            if (alert) {
                setTimeout(() => {
                    alert.classList.add('show');
                }, 300);

                var btnClose = alert.querySelector('.btn-close');
                if (btnClose) {
                    btnClose.addEventListener('click', function() {
                        alert.classList.remove('show');
                    });
                }
            }
        });
    </script>

    @if(session('whatsapp_open_url'))
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const whatsappUrl = @json(session('whatsapp_open_url'));
            if (whatsappUrl) {
                window.open(whatsappUrl, '_blank', 'noopener,noreferrer');
            }
        });
    </script>
    @endif

    @stack('scripts')
</body>
</html>
