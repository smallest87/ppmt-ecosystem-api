<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('code', 15)->unique(); // Contoh: R1, A-1, B-12A, FASUM-IBADAH
            $table->string('block', 10)->index(); // R, A, B, C, D, FASUM
            $table->string('number', 10); // 1, 12A, 12B, 14, IBADAH
            $table->unsignedSmallInteger('sort_order')->index(); // Urutan fisik kavling
            $table->enum('unit_type', ['ruko', 'rumah', 'fasum']);
            $table->string('zone', 20)->index(); // ZONA-1 s/d ZONA-4
            $table->enum('occupancy_status', ['unclaimed', 'pending', 'occupied', 'rented', 'empty'])
                  ->default('unclaimed');
            $table->json('map_coordinates')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};