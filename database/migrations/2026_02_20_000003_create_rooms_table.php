<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('room_number');
            $table->foreignId('room_type_id')->constrained('room_types')->onDelete('restrict');
            $table->foreignId('floor_id')->constrained('floors')->onDelete('restrict');
            $table->enum('status', ['available', 'occupied', 'maintenance', 'cleaning'])->default('available');
            $table->decimal('custom_price', 12, 2)->nullable();
            $table->string('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('room_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
