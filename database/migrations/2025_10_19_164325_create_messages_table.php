<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->text('content');
            $table->timestamp('date_sent')->nullable();
            $table->foreignId('service_id')->nullable()->constrained('services')->cascadeOnUpdate()->nullOnDelete();
            $table->string('status');
            $table->json('provider_response')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('date_sent');
            $table->index('created_at');
            $table->index('service_id');
        });
    }

    public function down(): void {
        Schema::dropIfExists('messages');
    }
};
