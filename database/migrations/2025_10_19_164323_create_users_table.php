<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique();
            $table->string('password');
            $table->foreignId('role_id')->constrained('user_roles')->cascadeOnUpdate()->restrictOnDelete();
            $table->rememberToken();
            $table->timestamps();

            $table->index('role_id');
        });
    }

    public function down(): void {
        Schema::dropIfExists('users');
    }
};
