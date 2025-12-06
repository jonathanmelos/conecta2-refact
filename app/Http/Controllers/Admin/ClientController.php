<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Plan;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index()
    {
        $clients = Client::with('currentSubscription.plan')
            ->where('client_status', 'active')
            ->orderBy('first_name')
            ->paginate(50);

        return view('admin.clientes.index', compact('clients'));
    }

    public function create()
    {
        return view('admin.clientes.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'document_number' => 'required|unique:clients,document_number',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'ruc' => 'nullable|string|max:20',
        ]);

        $validated['client_status'] = 'active';
        $validated['subscription_status'] = 'expired';

        Client::create($validated);

        return redirect()->route('admin.clientes.index')
            ->with('success', 'Cliente creado exitosamente.');
    }

    public function edit(Client $client)
    {
        return view('admin.clientes.edit', compact('client'));
    }

    public function update(Request $request, Client $client)
    {
        $validated = $request->validate([
            'document_number' => 'required|unique:clients,document_number,' . $client->id,
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'ruc' => 'nullable|string|max:20',
        ]);

        $client->update($validated);

        return redirect()->route('admin.clientes.index')
            ->with('success', 'Cliente actualizado exitosamente.');
    }

    public function destroy(Client $client)
    {
        // Soft delete - cambiar estado a 'deleted'
        $client->update(['client_status' => 'deleted']);

        return redirect()->route('admin.clientes.index')
            ->with('success', 'Cliente eliminado exitosamente.');
    }

    public function plan(Client $client)
    {
        $client->load('subscriptions.plan', 'hoursTracking');
        $plans = Plan::active()->get();

        return view('admin.clientes.plan', compact('client', 'plans'));
    }

    public function indexRegular()
    {
        // Vista limitada para usuarios regulares
        $clients = Client::with('currentSubscription.plan')
            ->where('client_status', 'active')
            ->orderBy('first_name')
            ->paginate(50);

        return view('regular.clientes.index', compact('clients'));
    }
/**
 * Crear cliente rápido (sin plan, para invitados/ocasionales)
 */
public function storeQuick(Request $request)
{
    $request->validate([
        'document_number' => 'required|string|max:20|unique:clients,document_number',
        'first_name' => 'required|string|max:100',
        'last_name' => 'required|string|max:100',
        'phone' => 'nullable|string|max:20',
        'email' => 'nullable|email|max:100',
    ]);
    
    $client = Client::create([
        'document_number' => $request->document_number,
        'first_name' => $request->first_name,
        'last_name' => $request->last_name,
        'phone' => $request->phone,
        'email' => $request->email,
        'client_status' => 'active',
        'subscription_status' => 'expired', // Sin plan activo = Cliente ocasional
    ]);
    
    return redirect()->route('admin.registro.index', ['doc' => $client->document_number])
        ->with('success', '✓ Cliente ocasional ' . $client->full_name . ' creado exitosamente. Puede registrar uso de servicios.');
}

/**
 * Vincular un cliente como invitado de otro (master)
 */
public function linkGuest(Request $request)
{
    $request->validate([
        'guest_id' => 'required|exists:clients,id',
        'master_id' => 'required|exists:clients,id',
    ]);
    
    $guest = Client::findOrFail($request->guest_id);
    $master = Client::with('currentSubscription.plan')->findOrFail($request->master_id);
    
    // Validar que el master tenga plan activo
    if (!$master->currentSubscription || !$master->currentSubscription->plan) {
        return redirect()->route('admin.registro.index')
            ->with('error', 'El cliente master debe tener un plan activo para poder invitar.');
    }
    
    // Validar que no se inviten a sí mismos
    if ($guest->id === $master->id) {
        return redirect()->route('admin.registro.index')
            ->with('error', 'Un cliente no puede ser invitado de sí mismo.');
    }
    
    // Vincular el invitado
    $guest->update([
        'invited_by_client_id' => $master->id,
        // El invitado hereda la suscripción del master
        'current_subscription_id' => $master->current_subscription_id,
        'subscription_status' => 'active',
    ]);
    
    return redirect()->route('admin.registro.index', ['doc' => $guest->document_number])
        ->with('success', '✓ ' . $guest->full_name . ' ha sido vinculado como invitado de ' . $master->full_name);
}
/**
 * Desvincular un cliente invitado de su master
 */
public function unlinkGuest(Request $request)
{
    $request->validate([
        'guest_id' => 'required|exists:clients,id',
    ]);
    
    $guest = Client::findOrFail($request->guest_id);
    
    // Verificar que el cliente esté vinculado a alguien
    if (!$guest->invited_by_client_id) {
        return redirect()->back()
            ->with('error', $guest->full_name . ' no está vinculado a ningún cliente.');
    }
    
    // Guardar el nombre del master para el mensaje
    $master = Client::find($guest->invited_by_client_id);
    $masterName = $master ? $master->full_name : 'cliente master';
    
    // Desvincular
    $guest->update([
        'invited_by_client_id' => null,
        'current_subscription_id' => null,
        'subscription_status' => 'expired',
    ]);
    
    return redirect()->back()
        ->with('success', $guest->full_name . ' fue desvinculado de ' . $masterName);
}
}