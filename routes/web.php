<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\PlanController;
use App\Http\Controllers\Admin\RegistroController;
use App\Http\Controllers\Admin\ReporteController;
use Illuminate\Support\Facades\Route;

// Ruta raíz redirige al login
Route::get('/', function () {
    return redirect()->route('login');
});

// Rutas de autenticación (Breeze)
require __DIR__.'/auth.php';

// Rutas protegidas por autenticación
Route::middleware('auth')->group(function () {
    
    // Dashboard principal (redirige según rol)
    Route::get('/dashboard', function () {
        if (auth()->user()->role === 'admin') {
            return redirect()->route('admin.diario', ['fecha' => date('Y-m-d')]);
        }
        return redirect()->route('regular.registro.index');
    })->name('dashboard');

    // Rutas de perfil (Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
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
        Route::post('/clientes/quick', [ClientController::class, 'storeQuick'])->name('clientes.storeQuick'); // ✅ NUEVA
        Route::get('/clientes/{client}/edit', [ClientController::class, 'edit'])->name('clientes.edit');
        Route::put('/clientes/{client}', [ClientController::class, 'update'])->name('clientes.update');
        Route::delete('/clientes/{client}', [ClientController::class, 'destroy'])->name('clientes.destroy');
        Route::get('/clientes/{client}/plan', [ClientController::class, 'plan'])->name('clientes.plan');
        Route::post('/clientes/link-guest', [ClientController::class, 'linkGuest'])->name('clientes.linkGuest'); // ✅ NUEVA
        // Planes
        Route::post('/clientes/unlink-guest', [ClientController::class, 'unlinkGuest'])->name('clientes.unlinkGuest'); // ✅ NUEVA
        Route::get('/planes', [PlanController::class, 'index'])->name('planes.index');
        Route::get('/planes/crear', [PlanController::class, 'create'])->name('planes.create');
        Route::post('/planes', [PlanController::class, 'store'])->name('planes.store');
        Route::get('/planes/{plan}/editar', [PlanController::class, 'edit'])->name('planes.edit');
        Route::put('/planes/{plan}', [PlanController::class, 'update'])->name('planes.update');
        Route::delete('/planes/{plan}', [PlanController::class, 'destroy'])->name('planes.destroy');
        
        // Registro de uso
        Route::get('/registro', [RegistroController::class, 'index'])->name('registro.index');
        Route::get('/registro/buscar', [RegistroController::class, 'buscar'])->name('registro.buscar');
        Route::post('/registro/cowork', [RegistroController::class, 'cowork'])->name('registro.cowork');
        Route::post('/registro/sala', [RegistroController::class, 'sala'])->name('registro.sala');
        Route::post('/registro/impresion', [RegistroController::class, 'impresion'])->name('registro.impresion');
        Route::post('/registro/{id}/finalizar', [RegistroController::class, 'finalizar'])->name('registro.finalizar');
        
        // Reportes
        Route::get('/reportes', [ReporteController::class, 'index'])->name('reportes.index');
        Route::get('/reportes/fecha', [ReporteController::class, 'porFecha'])->name('reportes.fecha');
        Route::get('/reportes/cliente', [ReporteController::class, 'porCliente'])->name('reportes.cliente');
        Route::get('/reportes/periodo', [ReporteController::class, 'porPeriodo'])->name('reportes.periodo');
        
        // Calculadora
        Route::get('/calculadora', function () {
            return view('admin.calculadora');
        })->name('calculadora');
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
        Route::get('/calculadora', function () {
            return view('regular.calculadora');
        })->name('calculadora');
    });
});