<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('umkm_stores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('owner_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('store_name', 120);
            $table->enum('category', ['kuliner', 'sembako', 'jasa', 'pinjam_alat', 'lainnya']);
            $table->text('description')->nullable();
            $table->enum('status', ['open', 'open_po', 'closed'])->default('open')->index();
            $table->string('whatsapp_number', 20);
            $table->json('catalog_items')->nullable(); // Daftar ringkas produk/jasa & harga
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('umkm_stores');
    }
};