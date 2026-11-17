<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aspirations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained()->nullOnDelete();
            $table->string('category', 50); // Infrastruktur & Drainase, Kualitas Bangunan, Lingkungan & Kebersihan, Keamanan
            $table->string('title', 180);
            $table->text('description');
            $table->string('location_scope', 120)->nullable(); // Misal: Jalan Sisi Barat, Blok C & D, dll.
            $table->enum('status', ['pending', 'open', 'submitted', 'resolved', 'rejected'])->default('pending');
            $table->unsignedInteger('target_signatures')->default(30); // Target ambang dukungan kavling
            $table->text('developer_response')->nullable(); // Catatan tindak lanjut / hasil audiensi
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('aspiration_signatures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aspiration_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // Siapa perwakilan unit yang menandatangani
            $table->string('note', 255)->nullable(); // Kesaksian singkat warga (opsional, misal: "Kavling C-12 juga mengalami rembes di ruang tamu")
            $table->timestamps();

            // 1 kavling = maksimal 1 dukungan sah per aspirasi
            $table->unique(['aspiration_id', 'unit_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aspiration_signatures');
        Schema::dropIfExists('aspirations');
    }
};