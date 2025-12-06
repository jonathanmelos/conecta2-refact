<!DOCTYPE html>
<html lang="es" data-bs-theme="auto">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="author" content="Jonathan Melo">
    <title>Gestión Conecta Coworking</title>

    <!-- Bootstrap 4.5.2 -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    
    <!-- Bootstrap 5 (local) -->
    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
    
    <!-- FullCalendar -->
    <link href='https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.10.2/fullcalendar.min.css' rel='stylesheet'/>
    
    <!-- Estilos personalizados -->
    <style>
        :root {
            --bd-violet-bg: #712cf9;
            --bd-violet-rgb: 112, 44, 249;
        }

        .btn-bd-primary {
            --bs-btn-font-weight: 600;
            --bs-btn-color: var(--bs-white);
            --bs-btn-bg: var(--bd-violet-bg);
            --bs-btn-border-color: var(--bd-violet-bg);
            --bs-btn-hover-color: var(--bs-white);
            --bs-btn-hover-bg: #6528e0;
            --bs-btn-hover-border-color: #6528e0;
            --bs-btn-focus-shadow-rgb: var(--bd-violet-rgb);
            --bs-btn-active-color: var(--bs-btn-hover-color);
            --bs-btn-active-bg: #5a23c8;
            --bs-btn-active-border-color: #5a23c8;
        }

        /* Alerta lateral animada */
        .custom-alert {
            position: absolute;
            top: 0;
            left: -300px;
            width: 300px;
            min-height: 100px;
            background-color: #f8f9fa;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: left 0.3s ease-in-out;
            z-index: 1000;
            padding: 20px;
        }

        .custom-alert.show {
            left: 0;
        }

        .btn-close {
            position: absolute;
            top: 10px;
            right: 10px;
        }

        /* Tablas compactas en alertas */
        .custom-alert table {
            font-size: 0.8em;
        }

        /* Filas clicables */
        tr[onclick] {
            cursor: pointer;
        }

        /* Links del menú */
        nav a {
            margin-right: 1rem;
            padding: 0.5rem 0;
            color: var(--bs-body-color);
            text-decoration: none;
        }

        nav a:hover {
            text-decoration: underline;
        }
        /* Resaltar clientes sin plan */
        .table-warning {
            background-color: #fff3cd !important;
        }

        .table-warning:hover {
            background-color: #ffe69c !important;
        }
    </style>

    @stack('styles')
</head>
<body>
    {{-- Alerta lateral --}}
    @if(isset($showAlert) && $showAlert)
    <div id="customAlert" class="custom-alert">
        <button type="button" class="btn-close" aria-label="Close"></button>
        <h3 class="alert-heading">¡No lo olvides!</h3>
        @yield('alert-content')
    </div>
    @endif

    {{-- Header con logo y menú --}}
    <div class="container py-3">
        <div class="d-flex flex-column flex-md-row align-items-center pb-3 mb-4 border-bottom">
            {{-- Logo --}}
            <a href="{{ route('dashboard') }}" class="d-flex align-items-center link-body-emphasis text-decoration-none">
                <img src="{{ asset('images/logo.jpg') }}" width="150" height="65" alt="Conecta Coworking">
            </a>

            {{-- Usuario y Logout --}}
            <span class="fs-6 ms-3">Bienvenido, {{ auth()->user()->name }}</span>
            <a href="{{ route('logout') }}" class="ms-3 d-flex align-items-center link-body-emphasis text-decoration-none"
               onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                <img src="{{ asset('images/salida.png') }}" width="20" height="20" alt="Salir">
                <span class="ms-1">Cerrar Sesión</span>
            </a>

            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                @csrf
            </form>

            {{-- Menú de navegación según rol --}}
            <nav class='d-inline-flex mt-2 mt-md-0 ms-md-auto'>
                @if(auth()->user()->role === 'admin')
                    <a href="{{ route('admin.diario', ['fecha' => date('Y-m-d')]) }}" class="link-body-emphasis">Diario</a>
                    <a href="{{ route('admin.registro.index') }}" class="link-body-emphasis">Registro</a>
                    <a href="{{ route('admin.clientes.index') }}" class="link-body-emphasis">Cliente</a>
                    <a href="{{ route('admin.calculadora') }}" class="link-body-emphasis">Calculadora</a>
                    <a href="{{ route('admin.planes.index') }}" class="link-body-emphasis">Planes</a>
                    <a href="{{ route('admin.reportes.index') }}" class="link-body-emphasis">Reportes</a>
                @else
                    <a href="{{ route('regular.registro.index') }}" class="link-body-emphasis">Registro</a>
                    <a href="{{ route('regular.clientes.index') }}" class="link-body-emphasis">Cliente</a>
                    <a href="{{ route('regular.calculadora') }}" class="link-body-emphasis">Calculadora</a>
                    <a href="{{ route('regular.reportes.index') }}" class="link-body-emphasis">Reportes</a>
                @endif
            </nav>
        </div>
    </div>

    {{-- Contenido principal --}}
<div class="container">
    {{-- Mensajes flash --}}
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <strong>¡Éxito!</strong> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong>¡Error!</strong> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if(session('warning'))
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <strong>¡Atención!</strong> {{ session('warning') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @yield('content')
</div>

    {{-- Scripts --}}
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.4.3/dist/js/bootstrap.bundle.min.js"></script>
    
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
                // Mostrar alerta automáticamente
                setTimeout(() => {
                    alert.classList.add('show');
                }, 300);

                // Cerrar alerta
                var btnClose = alert.querySelector('.btn-close');
                if (btnClose) {
                    btnClose.addEventListener('click', function() {
                        alert.classList.remove('show');
                    });
                }
            }
        });
    </script>

    @stack('scripts')
</body>
</html>