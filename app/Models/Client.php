<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
        protected $fillable = [
        'document_number',
        'first_name',
        'last_name',
        'email',
        'phone',
        'address',
        'city',
        'country',
        'birth_date',
        'client_status',
        'subscription_status',
        'current_subscription_id',
        'invited_by_client_id', // ✅ Agregar esta línea
        'notes',
        'invitation_link',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relaciones
    public function currentSubscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class, 'current_subscription_id');
    }
    

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function subscriptionMemberships(): HasMany
    {
        return $this->hasMany(SubscriptionMember::class);
    }

    public function usageRecords(): HasMany
    {
        return $this->hasMany(UsageRecord::class);
    }

    public function hoursTracking(): HasMany
    {
        return $this->hasMany(HoursTracking::class);
    }

    public function accessMethods(): HasMany
    {
        return $this->hasMany(AccessMethod::class);
    }

    public function accessLogs(): HasMany
    {
        return $this->hasMany(AccessLog::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    // Relación con el cliente que lo invitó (master)
    public function invitedBy()
    {
        return $this->belongsTo(Client::class, 'invited_by_client_id');
    }

    // Relación con los clientes que ha invitado
    public function guests()
    {
        return $this->hasMany(Client::class, 'invited_by_client_id');
    }

    // Accessors
    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->client_status === 'active';
    }

    public function getHasActiveSubscriptionAttribute(): bool
    {
        return $this->subscription_status === 'active';
    }

    public function getWhatsappInvitationUrlAttribute(): string
    {
        $invitationLink = $this->invitation_link
            ?: ('https://conectacowork.com/bienvenida?cliente=' . urlencode($this->full_name));

        $message = 'Hola ' . $this->first_name . ', gracias por ser parte de Conecta Coworking. '
            . 'Para mejorar nuestra convivencia te invitamos a revisar nuestras reglas de convivencia e informacion importante para ti. '
            . 'Sigue este link: ' . $invitationLink;

        return 'https://wa.me/?text=' . urlencode($message);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('client_status', 'active');
    }

    public function scopeWithActiveSubscription($query)
    {
        return $query->where('subscription_status', 'active');
    }
    
}
