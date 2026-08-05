<?php

namespace App\Services;

use App\Models\Client;
use App\Models\HoursTracking;
use App\Models\Subscription;
use App\Models\SubscriptionMember;
use App\Models\UsageRecord;
use Illuminate\Support\Collection;

class UsagePlanResolver
{
    public function resolve(Client $client, string $serviceType): array
    {
        if ($serviceType === 'print') {
            return $this->resolvePrint($client);
        }

        $serviceColumn = $serviceType === 'meeting_room' ? 'can_use_meeting_room' : 'can_use_cowork';
        $defaultColumn = $serviceType === 'meeting_room' ? 'is_default_meeting_room' : 'is_default_cowork';

        $assignments = SubscriptionMember::with('subscription.plan')
            ->where('client_id', $client->id)
            ->where($serviceColumn, true)
            ->whereHas('subscription', function ($query) {
                $this->activeSubscriptionQuery($query);
            })
            ->orderByDesc($defaultColumn)
            ->orderByDesc('updated_at')
            ->get();

        if ($assignments->count() > 1 && !$assignments->contains($defaultColumn, true)) {
            return $this->ambiguous($assignments, $serviceType);
        }

        $assignment = $assignments->first();
        if ($assignment && $assignment->subscription) {
            return $this->resolved($assignment->subscription, false, 'member_assignment', $serviceType);
        }

        $client->loadMissing(['currentSubscription.plan', 'invitedBy.currentSubscription.plan', 'subscriptions.plan']);

        if ($client->invitedBy) {
            $subscription = $this->activeCurrentOrLatest($client->invitedBy);
            if ($subscription) {
                return $this->resolved($subscription, false, 'invited_by_current', $serviceType);
            }
        }

        $subscription = $this->activeCurrentOrLatest($client);
        if ($subscription) {
            return $this->resolved($subscription, false, 'client_current', $serviceType);
        }

        return [
            'status' => 'billable',
            'subscription_id' => null,
            'subscription' => null,
            'plan' => null,
            'is_billable' => true,
            'source' => 'billable',
            'service_type' => $serviceType,
            'hours_used' => 0.0,
            'hours_total' => 0.0,
            'hours_available' => 0.0,
            'is_pilot' => false,
            'message' => null,
            'options' => [],
        ];
    }

    private function resolvePrint(Client $client): array
    {
        $assignments = SubscriptionMember::with('subscription.plan')
            ->where('client_id', $client->id)
            ->whereHas('subscription', function ($query) {
                $this->activeSubscriptionQuery($query);
            })
            ->orderByDesc('is_default')
            ->orderByDesc('is_default_cowork')
            ->orderByDesc('is_default_meeting_room')
            ->orderByDesc('updated_at')
            ->get();

        if (
            $assignments->count() > 1
            && !$assignments->contains('is_default', true)
            && !$assignments->contains('is_default_cowork', true)
            && !$assignments->contains('is_default_meeting_room', true)
        ) {
            return $this->ambiguous($assignments, 'print');
        }

        $assignment = $assignments->first();
        if ($assignment && $assignment->subscription) {
            return $this->resolved($assignment->subscription, false, 'member_assignment', 'print');
        }

        $client->loadMissing(['currentSubscription.plan', 'invitedBy.currentSubscription.plan', 'subscriptions.plan']);

        if ($client->invitedBy) {
            $subscription = $this->activeCurrentOrLatest($client->invitedBy);
            if ($subscription) {
                return $this->resolved($subscription, false, 'invited_by_current', 'print');
            }
        }

        $subscription = $this->activeCurrentOrLatest($client);
        if ($subscription) {
            return $this->resolved($subscription, false, 'client_current', 'print');
        }

        return [
            'status' => 'billable',
            'subscription_id' => null,
            'subscription' => null,
            'plan' => null,
            'is_billable' => true,
            'source' => 'billable',
            'service_type' => 'print',
            'hours_used' => 0.0,
            'hours_total' => 0.0,
            'hours_available' => 0.0,
            'is_pilot' => false,
            'message' => null,
            'options' => [],
        ];
    }

    public function payloadForClient(Client $client): array
    {
        return [
            'cowork' => $this->toPayload($this->resolve($client, 'cowork')),
            'meeting_room' => $this->toPayload($this->resolve($client, 'meeting_room')),
        ];
    }

    public function toPayload(array $resolved): array
    {
        $subscription = $resolved['subscription'];
        $plan = $resolved['plan'];

        return [
            'status' => $resolved['status'],
            'source' => $resolved['source'],
            'message' => $resolved['message'],
            'id' => $resolved['subscription_id'],
            'subscription_id' => $resolved['subscription_id'],
            'plan_id' => $subscription?->plan_id,
            'start_date' => $subscription?->start_date?->format('Y-m-d'),
            'end_date' => $subscription && $subscription->end_date ? $subscription->end_date->format('Y-m-d') : null,
            'days_remaining' => $subscription && $subscription->end_date ? max(0, now()->diffInDays($subscription->end_date, false)) : null,
            'dates_label' => $subscription
                ? $subscription->start_date->format('d/m/Y') . ' - ' . ($subscription->end_date ? $subscription->end_date->format('d/m/Y') : 'Sin vencimiento')
                : null,
            'plan' => $plan ? [
                'id' => $plan->id,
                'name' => $plan->name,
                'cowork_hours' => $subscription->effective_cowork_hours,
                'meeting_room_hours' => $subscription->effective_meeting_room_hours,
                'is_pilot' => (bool) $plan->is_pilot,
            ] : null,
            'hours_used' => $resolved['hours_used'],
            'hours_total' => $resolved['hours_total'],
            'hours_available' => $resolved['hours_available'],
            'is_billable' => $resolved['is_billable'],
            'is_pilot' => $resolved['is_pilot'],
            'options' => $resolved['options'],
        ];
    }

    private function activeCurrentOrLatest(Client $client): ?Subscription
    {
        $client->loadMissing(['currentSubscription.plan', 'subscriptions.plan']);

        if ($client->currentSubscription && $this->isActive($client->currentSubscription)) {
            return $client->currentSubscription;
        }

        return $client->subscriptions
            ->filter(fn ($subscription) => $this->isActive($subscription))
            ->sortByDesc('start_date')
            ->first();
    }

    private function activeSubscriptionQuery($query): void
    {
        $query->where('status', 'active')
            ->whereDate('start_date', '<=', now())
            ->where(function ($dateQuery) {
                $dateQuery->whereDate('end_date', '>=', now())
                    ->orWhereNull('end_date');
            });
    }

    private function isActive(Subscription $subscription): bool
    {
        return $subscription->status === 'active'
            && $subscription->start_date->lte(today())
            && (!$subscription->end_date || $subscription->end_date->gte(today()));
    }

    private function resolved(Subscription $subscription, bool $isBillable, string $source, string $serviceType): array
    {
        $isPilot = (bool) ($subscription->plan && $subscription->plan->is_pilot);

        if ($serviceType === 'print') {
            $total = (float) $subscription->effective_prints_included;
            $used = (float) UsageRecord::where('subscription_id', $subscription->id)
                ->where('service_type', 'print')
                ->sum('quantity');
        } else {
            $tracking = HoursTracking::where('subscription_id', $subscription->id)
                ->where('service_type', $serviceType)
                ->first();

            $total = $serviceType === 'meeting_room'
                ? (float) $subscription->effective_meeting_room_hours
                : (float) $subscription->effective_cowork_hours;
            $used = $tracking ? (float) $tracking->hours_used : 0.0;
        }

        return [
            'status' => 'ok',
            'subscription_id' => $subscription->id,
            'subscription' => $subscription->loadMissing('plan'),
            'plan' => $subscription->plan,
            'is_billable' => $isBillable,
            'source' => $source,
            'service_type' => $serviceType,
            'hours_used' => $used,
            'hours_total' => $isPilot ? null : $total,
            'hours_available' => $isPilot ? null : max(0, $total - $used),
            'is_pilot' => $isPilot,
            'message' => null,
            'options' => [],
        ];
    }

    private function ambiguous(Collection $assignments, string $serviceType): array
    {
        return [
            'status' => 'ambiguous',
            'subscription_id' => null,
            'subscription' => null,
            'plan' => null,
            'is_billable' => false,
            'source' => 'member_assignment_ambiguous',
            'service_type' => $serviceType,
            'hours_used' => 0.0,
            'hours_total' => 0.0,
            'hours_available' => 0.0,
            'is_pilot' => false,
            'message' => 'Esta persona tiene varios planes asignados para este servicio. Marque uno como preferido.',
            'options' => $assignments->map(function ($assignment) {
                $subscription = $assignment->subscription;

                return [
                    'subscription_id' => $subscription->id,
                    'plan_name' => $subscription->plan->name ?? 'Plan',
                    'start_date' => $subscription->start_date ? $subscription->start_date->format('d/m/Y') : '-',
                    'end_date' => $subscription->end_date ? $subscription->end_date->format('d/m/Y') : 'Sin vencimiento',
                ];
            })->values()->all(),
        ];
    }
}
