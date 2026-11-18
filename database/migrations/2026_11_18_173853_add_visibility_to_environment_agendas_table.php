<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('environment_agendas', function (Blueprint $table) {
            $table->string('visibility', 20)->default('public')->after('notes')->index(); // public | block | invite_only
            $table->json('target_blocks')->nullable()->after('visibility'); // Contoh: ["A", "B"]
        });

        Schema::create('environment_agenda_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('environment_agenda_id')
                  ->constrained('environment_agendas')
                  ->cascadeOnDelete();
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['environment_agenda_id', 'user_id'], 'agenda_user_invite_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('environment_agenda_invitations');

        Schema::table('environment_agendas', function (Blueprint $table) {
            $table->dropColumn(['visibility', 'target_blocks']);
        });
    }
};