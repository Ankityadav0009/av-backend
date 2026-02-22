<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_number')->unique();
            $table->string('guest_name');
            $table->string('guest_phone', 20);
            $table->string('guest_email')->nullable();
            $table->text('guest_address')->nullable();
            $table->string('id_type')->nullable(); // Aadhar, Passport, etc.
            $table->string('id_number')->nullable();
            $table->json('id_proof_paths')->nullable(); // Multiple image paths
            $table->foreignId('room_id')->constrained('rooms')->onDelete('restrict');
            $table->date('check_in_date');
            $table->date('check_out_date');
            $table->unsignedTinyInteger('adults')->default(1);
            $table->unsignedTinyInteger('children')->default(0);
            $table->decimal('room_rate', 12, 2); // Per night
            $table->decimal('total_amount', 12, 2);
            $table->decimal('advance_paid', 12, 2)->default(0);
            $table->enum('status', ['pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('checked_out_at')->nullable();
            $table->timestamps();

            $table->index(['check_in_date', 'check_out_date']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
