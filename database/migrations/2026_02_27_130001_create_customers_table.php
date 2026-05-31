<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('mobile', 20)->unique();
            $table->string('email')->nullable();
            $table->string('otp_code', 10)->nullable();
            $table->timestamp('otp_expires_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->index('mobile');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
