<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resident_profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('resident_profiles', 'gender')) {
                $table->string('gender', 16)->nullable()->after('username');
            }
        });

        Schema::table('unit_memberships', function (Blueprint $table) {
            if (! Schema::hasColumn('unit_memberships', 'guarantor_user_id')) {
                $table->foreignId('guarantor_user_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('unit_memberships', function (Blueprint $table) {
            if (Schema::hasColumn('unit_memberships', 'guarantor_user_id')) {
                $table->dropConstrainedForeignId('guarantor_user_id');
            }
        });

        Schema::table('resident_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('resident_profiles', 'gender')) {
                $table->dropColumn('gender');
            }
        });
    }
};