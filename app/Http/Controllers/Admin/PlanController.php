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
            ->withCount([
                'subscriptions as ultra_children_count' => function ($query) {
                    $query->where('is_ultra_custom', true);
                },
            ])
            ->with([
                'subscriptions' => function ($query) {
                    $query->where('is_ultra_custom', true)
                        ->with('client')
                        ->latest();
                },
            ])
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
            'is_pilot' => 'nullable|boolean',
            'is_ultra_custom' => 'nullable|boolean',
            'price' => 'required|numeric|min:0',
            'setup_fee' => 'nullable|numeric|min:0',
            'deposit_required' => 'nullable|numeric|min:0',
            'operational_cost' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        $validated['is_pilot'] = $request->boolean('is_pilot');
        $validated['is_ultra_custom'] = $request->boolean('is_ultra_custom');
        if ($validated['is_pilot']) {
            $validated['cowork_hours'] = 0;
            $validated['meeting_room_hours'] = 0;
        }
        if ($validated['is_ultra_custom']) {
            $validated['cowork_hours'] = 0;
            $validated['meeting_room_hours'] = 0;
            $validated['prints_included'] = 0;
            $validated['events_included'] = 0;
        }
        $validated['setup_fee'] = $validated['setup_fee'] ?? 0;
        $validated['deposit_required'] = $validated['deposit_required'] ?? 0;
        $validated['operational_cost'] = $validated['operational_cost'] ?? 0;
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
            'is_pilot' => 'nullable|boolean',
            'is_ultra_custom' => 'nullable|boolean',
            'price' => 'required|numeric|min:0',
            'setup_fee' => 'nullable|numeric|min:0',
            'deposit_required' => 'nullable|numeric|min:0',
            'operational_cost' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        $validated['is_pilot'] = $request->boolean('is_pilot');
        $validated['is_ultra_custom'] = $request->boolean('is_ultra_custom');
        if ($validated['is_pilot']) {
            $validated['cowork_hours'] = 0;
            $validated['meeting_room_hours'] = 0;
        }
        if ($validated['is_ultra_custom']) {
            $validated['cowork_hours'] = 0;
            $validated['meeting_room_hours'] = 0;
            $validated['prints_included'] = 0;
            $validated['events_included'] = 0;
        }
        $validated['setup_fee'] = $validated['setup_fee'] ?? 0;
        $validated['deposit_required'] = $validated['deposit_required'] ?? 0;
        $validated['operational_cost'] = $validated['operational_cost'] ?? 0;
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
