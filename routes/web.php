<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\PlanController;
use App\Http\Controllers\Admin\RegistroController;
use App\Http\Controllers\Admin\ReporteController;
use App\Http\Controllers\Admin\CalendarioController;
use App\Http\Controllers\SelfServiceController;
use App\Http\Controllers\Mcp\OAuthController;
use App\Http\Controllers\Admin\McpController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

// Ruta raíz redirige a registro (requiere login)
Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('admin.registro.index');
    }
    return redirect()->route('login');
});

// Autoservicio publico (sin login)
Route::get('/autoservicio', [SelfServiceController::class, 'index'])->name('autoservicio.index');
Route::post('/autoservicio/reservar', [SelfServiceController::class, 'reserve'])->name('autoservicio.reserve');

// Rutas de autenticación (Breeze)
require __DIR__.'/auth.php';

// ============================================
// MCP OAuth (servidor MCP de solo lectura)
// ============================================
Route::get('/.well-known/oauth-authorization-server', [OAuthController::class, 'authorizationServerMetadata']);
Route::get('/.well-known/oauth-protected-resource', [OAuthController::class, 'protectedResourceMetadata']);
Route::get('/.well-known/oauth-protected-resource/{any}', [OAuthController::class, 'protectedResourceMetadata'])->where('any', '.*');

Route::prefix('oauth/mcp')->name('mcp.oauth.')->group(function () {
    Route::post('/register', [OAuthController::class, 'register'])->name('register');
    Route::get('/authorize', [OAuthController::class, 'showAuthorize'])->name('authorize');
    Route::post('/authorize', [OAuthController::class, 'submitAuthorize'])->name('authorize.submit');
    Route::post('/token', [OAuthController::class, 'token'])->name('token');
    Route::post('/revoke', [OAuthController::class, 'revoke'])->name('revoke');
});

// Rutas protegidas por autenticación
Route::middleware('auth')->group(function () {
    
    // Dashboard principal (redirige según rol)
    Route::get('/dashboard', function () {
        if (Auth::user()->role === 'admin') {
            return redirect()->route('admin.registro.index');
        }
        return redirect()->route('regular.registro.index');
    })->name('dashboard');

    // Rutas de perfil (Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/api-token', [ProfileController::class, 'generateApiToken'])->name('profile.api-token');
    Route::delete('/profile/api-token', [ProfileController::class, 'revokeApiToken'])->name('profile.api-token.revoke');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // ============================================
    // RUTAS ADMIN
    // ============================================
    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        
        // Dashboard / Diario
        Route::get('/diario', [DashboardController::class, 'diario'])->name('diario');
        
        // Clientes
        Route::get('/clientes', [ClientController::class, 'index'])->name('clientes.index');
        Route::get('/clientes/create', [ClientController::class, 'create'])->name('clientes.create');
        Route::post('/clientes', [ClientController::class, 'store'])->name('clientes.store');
        Route::post('/clientes/quick', [ClientController::class, 'storeQuick'])->name('clientes.storeQuick');
        Route::get('/clientes/{client}/edit', [ClientController::class, 'edit'])->name('clientes.edit');
        Route::put('/clientes/{client}', [ClientController::class, 'update'])->name('clientes.update');
        Route::delete('/clientes/{client}', [ClientController::class, 'destroy'])->name('clientes.destroy');
        Route::get('/clientes/{client}/plan', [ClientController::class, 'plan'])->name('clientes.plan');
        Route::post('/clientes/link-guest', [ClientController::class, 'linkGuest'])->name('clientes.linkGuest');
        Route::post('/clientes/unlink-guest/{id}', [ClientController::class, 'unlinkGuest'])->name('clientes.unlinkGuest');
        // Rutas de suscripción y gestión de planes
        Route::get('/clientes/{client}/suscribir', [ClientController::class, 'suscribirForm'])->name('clientes.suscribirForm');
        Route::post('/clientes/{client}/suscribir', [ClientController::class, 'suscribir'])->name('clientes.suscribir');
        Route::post('/clientes/{client}/renovar', [ClientController::class, 'renovar'])->name('clientes.renovar');
        Route::post('/clientes/{client}/modificar-plan', [ClientController::class, 'modificarPlan'])->name('clientes.modificarPlan');
        Route::post('/clientes/{client}/actualizar-cupos-ultra', [ClientController::class, 'actualizarCuposUltra'])->name('clientes.actualizarCuposUltra');
        Route::post('/clientes/{client}/iniciar-plan', [ClientController::class, 'iniciarPlan'])->name('clientes.iniciarPlan');
        Route::post('/clientes/{client}/recalcular-horas', [ClientController::class, 'recalcularHorasTracking'])->name('clientes.recalcularHorasTracking');
        Route::post('/clientes/{client}/planes/{subscription}/miembros', [ClientController::class, 'storeSubscriptionMember'])->name('clientes.subscriptionMembers.store');
        Route::delete('/clientes/{client}/planes/{subscription}/miembros/{member}', [ClientController::class, 'destroySubscriptionMember'])->name('clientes.subscriptionMembers.destroy');
        Route::get('/clientes/{client}/registro/{subscription}', [ClientController::class, 'detalleRegistro'])->name('clientes.detalleRegistro');
        Route::put('/clientes/{client}/registro/{subscription}/fechas', [ClientController::class, 'actualizarFechasPlan'])->name('clientes.actualizarFechasPlan');
        Route::get('/clientes/{client}/registro/{subscription}/excel', [ClientController::class, 'exportDetalleRegistroExcel'])->name('clientes.detalleRegistroExcel');
        Route::get('/clientes/{client}/registro/{subscription}/pdf', [ClientController::class, 'exportDetalleRegistroPdf'])->name('clientes.detalleRegistroPdf');
        Route::delete('/clientes/registro/{usageRecord}', [ClientController::class, 'eliminarRegistro'])->name('clientes.eliminarRegistro');
        Route::put('/clientes/registro/{usageRecord}', [ClientController::class, 'actualizarRegistro'])->name('clientes.actualizarRegistro');

        // Planes
        Route::get('/planes', [PlanController::class, 'index'])->name('planes.index');
        Route::get('/planes/crear', [PlanController::class, 'create'])->name('planes.create');
        Route::post('/planes', [PlanController::class, 'store'])->name('planes.store');
        Route::get('/planes/{plan}/editar', [PlanController::class, 'edit'])->name('planes.edit');
        Route::put('/planes/{plan}', [PlanController::class, 'update'])->name('planes.update');
        Route::delete('/planes/{plan}', [PlanController::class, 'destroy'])->name('planes.destroy');
        
        // Registro de uso
        Route::get('/registro', [RegistroController::class, 'index'])->name('registro.index');
        Route::get('/registro/pendientes', [RegistroController::class, 'pendientes'])->name('registro.pendientes');
        Route::delete('/registro/pendientes', [RegistroController::class, 'eliminarPendientesGrupo'])->name('registro.pendientes.delete');
        Route::get('/registro/buscar', [RegistroController::class, 'buscar'])->name('registro.buscar');
        Route::post('/registro/cowork', [RegistroController::class, 'cowork'])->name('registro.cowork');
        Route::post('/registro/sala', [RegistroController::class, 'sala'])->name('registro.sala');
        Route::post('/registro/impresion', [RegistroController::class, 'impresion'])->name('registro.impresion');
        Route::post('/registro/{id}/finalizar', [RegistroController::class, 'finalizar'])->name('registro.finalizar');
        Route::post('/registro/finalizar-con-fecha/{id}', [RegistroController::class, 'finalizarConFecha'])->name('registro.finalizarConFecha');
        Route::post('/registro/update-record', [RegistroController::class, 'updateRecord'])->name('registro.updateRecord');
        Route::post('/registro/actualizar', [RegistroController::class, 'actualizarRegistro'])->name('registro.actualizar'); // ⭐ NUEVA
        
        // Reportes
        Route::get('/reportes', [ReporteController::class, 'index'])->name('reportes.index');
        Route::get('/reportes/fecha', [ReporteController::class, 'porFecha'])->name('reportes.fecha');
        Route::get('/reportes/cliente', [ReporteController::class, 'porCliente'])->name('reportes.cliente');
        Route::get('/reportes/periodo', [ReporteController::class, 'porPeriodo'])->name('reportes.periodo');
        
        // Calculadora
        Route::get('/calculadora', [ReporteController::class, 'calculadora'])->name('calculadora');

        // Calendario de Eventos
        Route::get('/calendario', [CalendarioController::class, 'index'])->name('calendario.index');
        Route::get('/calendario/crear', [CalendarioController::class, 'create'])->name('calendario.create');
        Route::get('/calendario/clientes/buscar', [CalendarioController::class, 'buscarClientes'])->name('calendario.buscarClientes');
        Route::post('/calendario/whatsapp-contacto', [CalendarioController::class, 'guardarWhatsappContacto'])->name('calendario.guardarWhatsappContacto');
        Route::post('/calendario', [CalendarioController::class, 'store'])->name('calendario.store');
        Route::get('/calendario/{evento}', [CalendarioController::class, 'show'])->name('calendario.show');
        Route::get('/calendario/{evento}/editar', [CalendarioController::class, 'edit'])->name('calendario.edit');
        Route::put('/calendario/{evento}', [CalendarioController::class, 'update'])->name('calendario.update');
        Route::delete('/calendario/{evento}', [CalendarioController::class, 'destroy'])->name('calendario.destroy');
        Route::get('/api/eventos', [CalendarioController::class, 'apiEvents'])->name('calendario.api');

        // MCP Connector (panel de administración)
        Route::prefix('mcp')->name('mcp.')->group(function () {
            Route::get('/permisos', [McpController::class, 'permissions'])->name('permissions');
            Route::post('/permisos', [McpController::class, 'updatePermissions'])->name('permissions.update');
            Route::get('/conexiones', [McpController::class, 'connections'])->name('connections');
            Route::post('/conexiones/{token}/revocar', [McpController::class, 'revokeConnection'])->name('connections.revoke');
            Route::get('/auditoria', [McpController::class, 'auditLog'])->name('audit');
        });
    });

    // ============================================
    // RUTAS USUARIO REGULAR
    // ============================================
    Route::middleware('regular')->prefix('regular')->name('regular.')->group(function () {
        
        // Registro
        Route::get('/registro', [RegistroController::class, 'index'])->name('registro.index');
        
        // Clientes (vista limitada)
        Route::get('/clientes', [ClientController::class, 'indexRegular'])->name('clientes.index');
        
        // Reportes
        Route::get('/reportes', [ReporteController::class, 'index'])->name('reportes.index');
        
        // Calculadora
        Route::get('/calculadora', [ReporteController::class, 'calculadora'])->name('calculadora');
    });
});
