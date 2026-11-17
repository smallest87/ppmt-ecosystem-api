<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_group_contacts', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 20)->unique();
            $table->string('wa_display_name', 180)->nullable();
            $table->json('groups')->nullable();           // ["Grup Warga PPMT", "Grup Pesona Mandiri"]
            $table->json('auto_labels')->nullable();      // Label otomatis (grup, status akun, saran kavling, non-penghuni)
            $table->json('manual_labels')->nullable();    // Label tambahan manual oleh Admin Sistem
            $table->string('source_note', 100)->default('data dari grup whatsapp');
            $table->string('detected_unit_code', 15)->nullable()->index();
            $table->foreignId('matched_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('matched_unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->string('reconciliation_status', 30)->default('unmatched')->index(); // unmatched | matched_auto | matched_manual | ignored
            $table->text('admin_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_group_contacts');
    }
};