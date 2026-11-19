<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('umkm_stores', function (Blueprint $table) {
            $table->boolean('is_featured')->default(false)->index()->after('status');
            $table->timestamp('featured_until')->nullable()->after('is_featured');
            $table->boolean('is_suspended')->default(false)->index()->after('featured_until');
            $table->string('moderation_note', 500)->nullable()->after('is_suspended');
            $table->foreignId('curated_by_user_id')->nullable()->after('moderation_note')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('umkm_stores', function (Blueprint $table) {
            $table->dropConstrainedForeignId('curated_by_user_id');
            $table->dropColumn([
                'is_featured',
                'featured_until',
                'is_suspended',
                'moderation_note',
            ]);
        });
    }
};