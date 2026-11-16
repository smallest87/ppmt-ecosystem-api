<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meeting_minutes', function (Blueprint $table) {
            $table->id();
            $table->string('title', 180);
            $table->date('meeting_date');
            $table->string('location', 120)->nullable();
            $table->string('category', 30)->default('musyawarah_warga'); // musyawarah_warga, rapat_pengurus, tata_tertib
            $table->text('decisions');
            $table->string('visibility', 30)->default('public_residents'); // public_residents, internal_pengurus
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['visibility', 'meeting_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meeting_minutes');
    }
};