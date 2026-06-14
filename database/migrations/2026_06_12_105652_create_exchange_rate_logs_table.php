<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exchange_rate_logs', function (Blueprint $table) {
            $table->id();
            $table->string('base_currency', 3);
            $table->string('target_currency', 3);
            $table->decimal('rate', 15, 6);
            $table->string('source');
            $table->timestamp('fetched_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exchange_rate_logs');
    }
};
