<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();
            $table->string('type')->unique();
            $table->string('description')->nullable();
            $table->unsignedInteger('daily_msg_limit')->default(0);
            $table->json('permissions')->nullable();          // permisos como JSON (array de strings/flags)
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('user_roles');
    }
};
