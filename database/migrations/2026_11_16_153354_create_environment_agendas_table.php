<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('environment_agendas', function (Blueprint $table) {
            $table->id();
            $table->string('title', 180);
            $table->string('category', 40)->default('kegiatan'); // kegiatan | kebersihan | keamanan | musyawarah | pemeliharaan
            $table->date('event_date')->index();
            $table->string('time_label', 80); // Contoh: "06.30 – 09.30 WIB"
            $table->string('location', 140);
            $table->text('notes')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('environment_agendas');
    }
};