<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resident_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('phone', 20)->unique();
            $table->enum('global_role', ['warga_ppmt', 'koordinator_blok', 'admin'])
                  ->default('warga_ppmt');
            $table->boolean('is_verified')->default(false)->index();
            $table->text('nik_encrypted')->nullable();
            $table->text('kk_encrypted')->nullable();
            $table->boolean('is_ownership_doc_verified')->default(false);
            $table->json('vehicles')->nullable();
            $table->json('privacy_settings')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resident_profiles');
    }
};