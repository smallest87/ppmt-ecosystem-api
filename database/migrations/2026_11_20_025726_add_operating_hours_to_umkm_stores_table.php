<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('umkm_stores', function (Blueprint $table) {
            $table->string('operating_hours', 80)->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('umkm_stores', function (Blueprint $table) {
            $table->dropColumn('operating_hours');
        });
    }
};