<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('news_articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('title');
            $table->string('slug');
            $table->string('category', 60)->default('Kegiatan Warga');
            $table->enum('layout', ['hero', 'thumbnail', 'grid', 'text'])->default('thumbnail');

            $table->string('excerpt', 300)->nullable();
            $table->longText('body');
            $table->string('thumbnail_path')->nullable();

            $table->enum('status', ['pending', 'published', 'rejected'])->default('pending');
            $table->string('rejection_reason')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->date('published_date')->nullable()->index();

            $table->timestamps();

            // Menjamin permalink /berita/YYYY/MM/DD/slug selalu unik
            $table->unique(['published_date', 'slug'], 'news_unique_date_slug');
            $table->index(['status', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news_articles');
    }
};