<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    public function index()
    {
        $plans = Plan::withCount('subscriptions')
            ->orderBy('price')
            ->get();

        return view('admin.planes.index', compact('plans'));
    }

    public function create()
    {
        return view('admin.planes.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'cowork_hours' => 'required|integer|min:0',
            'meeting_room_hours' => 'required|integer|min:0',
            'prints_included' => 'required|integer|min:0',
            'events_included' => 'required|integer|min:0',
            'price' => 'required|numeric|min:0',
            'setup_fee' => 'nullable|numeric|min:0',
            'deposit_required' => 'nullable|numeric|min:0',
            'operational_cost' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        $validated['is_active'] = true;

        Plan::create($validated);

        return redirect()->route('admin.planes.index')
            ->with('success', 'Plan creado exitosamente.');
    }

    public function edit(Plan $plan)
    {
        return view('admin.planes.edit', compact('plan'));
    }

    public function update(Request $request, Plan $plan)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'cowork_hours' => 'required|integer|min:0',
            'meeting_room_hours' => 'required|integer|min:0',
            'prints_included' => 'required|integer|min:0',
            'events_included' => 'required|integer|min:0',
            'price' => 'required|numeric|min:0',
            'setup_fee' => 'nullable|numeric|min:0',
            'deposit_required' => 'nullable|numeric|min:0',
            'operational_cost' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        $plan->update($validated);

        return redirect()->route('admin.planes.index')
            ->with('success', 'Plan actualizado exitosamente.');
    }

    public function destroy(Plan $plan)
    {
        $plan->update(['is_active' => false]);

        return redirect()->route('admin.planes.index')
            ->with('success', 'Plan desactivado exitosamente.');
    }
}