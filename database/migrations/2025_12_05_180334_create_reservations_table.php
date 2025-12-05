<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            
            $table->enum('reservation_type', ['cowork', 'meeting_room', 'corporate_event']);
            
            // Detalles del evento (solo para corporate_event)
            $table->string('event_title', 255)->nullable();
            $table->text('event_description')->nullable();
            $table->enum('event_mode', ['presencial', 'virtual', 'hibrido'])->nullable();
            $table->integer('attendees_count')->nullable();
            
            // Fechas y horarios
            $table->date('reservation_date');
            $table->time('start_time');
            $table->time('end_time');
            
            $table->enum('status', ['confirmed', 'cancelled', 'completed'])->default('confirmed');
            
            $table->text('notes')->nullable();
            
            // Integración Google Calendar
            $table->string('google_calendar_id', 255)->nullable();
            $table->boolean('google_event_synced')->default(false);
            
            $table->timestamps();
            
            $table->index('reservation_date');
            $table->index(['client_id', 'reservation_date']);
            $table->index('reservation_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};