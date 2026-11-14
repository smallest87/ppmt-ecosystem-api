<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_governance_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role_code', 50)->index(); // operator_aplikasi, ketua_paguyuban, koordinator_zona, koordinator_blok, dll.
            $table->enum('scope_type', ['global', 'zone', 'block'])->default('global');
            $table->string('scope_value', 30)->nullable(); // Contoh: 'ZONA-4' atau 'C', null jika global
            $table->foreignId('assigned_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->unique(['user_id', 'role_code', 'scope_type', 'scope_value'], 'user_gov_role_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_governance_roles');
    }
};