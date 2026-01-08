<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\UsageRecord;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function clients(Request $request)
    {
        $query = Client::query()
            ->withCount(['subscriptions', 'usageRecords']);

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('document_number', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('client_status', $request->get('status'));
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->get('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->get('to'));
        }

        $limit = min((int) $request->get('limit', 200), 1000);
        $clients = $query->orderBy('created_at', 'desc')->limit($limit)->get();

        $data = $clients->map(function ($client) {
            return [
                'id' => $client->id,
                'document_number' => $client->document_number,
                'first_name' => $client->first_name,
                'last_name' => $client->last_name,
                'full_name' => $client->full_name,
                'email' => $client->email,
                'phone' => $client->phone,
                'client_status' => $client->client_status,
                'subscription_status' => $client->subscription_status,
                'subscriptions_count' => $client->subscriptions_count,
                'usage_records_count' => $client->usage_records_count,
                'created_at' => $client->created_at?->toDateTimeString(),
            ];
        });

        return response()->json(['data' => $data]);
    }

    public function plans(Request $request)
    {
        $query = Plan::query()->withCount('subscriptions');

        if ($request->filled('active')) {
            $query->where('is_active', filter_var($request->get('active'), FILTER_VALIDATE_BOOLEAN));
        }

        $limit = min((int) $request->get('limit', 200), 1000);
        $plans = $query->orderBy('price')->limit($limit)->get();

        $data = $plans->map(function ($plan) {
            return [
                'id' => $plan->id,
                'name' => $plan->name,
                'cowork_hours' => $plan->cowork_hours,
                'meeting_room_hours' => $plan->meeting_room_hours,
                'prints_included' => $plan->prints_included,
                'events_included' => $plan->events_included,
                'price' => (float) $plan->price,
                'is_active' => (bool) $plan->is_active,
                'subscriptions_count' => $plan->subscriptions_count,
            ];
        });

        return response()->json(['data' => $data]);
    }

    public function subscriptions(Request $request)
    {
        $query = Subscription::query()->with(['client', 'plan']);

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->filled('from')) {
            $query->whereDate('start_date', '>=', $request->get('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('start_date', '<=', $request->get('to'));
        }

        $limit = min((int) $request->get('limit', 200), 1000);
        $subscriptions = $query->orderBy('start_date', 'desc')->limit($limit)->get();

        $data = $subscriptions->map(function ($subscription) {
            return [
                'id' => $subscription->id,
                'client_id' => $subscription->client_id,
                'client_name' => $subscription->client?->full_name,
                'plan_id' => $subscription->plan_id,
                'plan_name' => $subscription->plan?->name,
                'status' => $subscription->status,
                'start_date' => $subscription->start_date?->format('Y-m-d'),
                'end_date' => $subscription->end_date?->format('Y-m-d'),
                'monthly_price' => (float) ($subscription->monthly_price ?? $subscription->plan?->price ?? 0),
            ];
        });

        return response()->json(['data' => $data]);
    }

    public function usageRecords(Request $request)
    {
        $query = UsageRecord::query()->with(['client', 'subscription.plan']);

        if ($request->filled('service_type')) {
            $query->where('service_type', $request->get('service_type'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->filled('from')) {
            $query->whereDate('check_in', '>=', $request->get('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('check_in', '<=', $request->get('to'));
        }

        $limit = min((int) $request->get('limit', 200), 1000);
        $records = $query->orderBy('check_in', 'desc')->limit($limit)->get();

        $data = $records->map(function ($record) {
            return [
                'id' => $record->id,
                'client_id' => $record->client_id,
                'client_name' => $record->client?->full_name,
                'subscription_id' => $record->subscription_id,
                'plan_name' => $record->subscription?->plan?->name,
                'service_type' => $record->service_type,
                'status' => $record->status,
                'check_in' => $record->check_in?->toDateTimeString(),
                'check_out' => $record->check_out?->toDateTimeString(),
                'duration_minutes' => $record->duration_in_minutes,
                'quantity' => $record->quantity ?? 0,
                'is_billable' => (bool) $record->is_billable,
            ];
        });

        return response()->json(['data' => $data]);
    }
}
