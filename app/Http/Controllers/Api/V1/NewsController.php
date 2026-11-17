<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\NewsArticle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class NewsController extends Controller
{
    private const AUTHOR_RELATIONS = [
        'author:id,name',
        'author.governanceRoles',
        'unit:id,code',
    ];

    /**
     * Daftar berita terbit untuk Beranda Warga (Wajib terverifikasi).
     */
    public function index(Request $request)
    {
        $user = $request->user();
        if (! $user || ! $user->profile?->is_verified) {
            return response()->json(['message' => 'Akses khusus warga terverifikasi.'], 403);
        }

        $articles = NewsArticle::with(self::AUTHOR_RELATIONS)
            ->where('status', 'published')
            ->orderByDesc('published_at')
            ->limit(25)
            ->get()
            ->map(fn (NewsArticle $item) => $this->transformArticle($item));

        return response()->json(['data' => $articles]);
    }

    /**
     * Detail satu berita berdasarkan permalink /YYYY/MM/DD/slug (Wajib terverifikasi).
     */
    public function show(Request $request, string $year, string $month, string $day, string $slug)
    {
        $user = $request->user();
        if (! $user || ! $user->profile?->is_verified) {
            return response()->json(['message' => 'Akses khusus warga terverifikasi.'], 403);
        }

        $dateString = "{$year}-{$month}-{$day}";

        $article = NewsArticle::with(self::AUTHOR_RELATIONS)
            ->where('status', 'published')
            ->whereDate('published_date', $dateString)
            ->where('slug', $slug)
            ->first();

        if (! $article) {
            return response()->json(['message' => 'Artikel berita tidak ditemukan.'], 404);
        }

        $related = NewsArticle::with(self::AUTHOR_RELATIONS)
            ->where('status', 'published')
            ->where('id', '!=', $article->id)
            ->orderByDesc('published_at')
            ->limit(4)
            ->get()
            ->map(fn (NewsArticle $item) => $this->transformArticle($item));

        return response()->json([
            'data' => [
                'article' => $this->transformArticle($article, true),
                'related' => $related,
            ],
        ]);
    }

    /**
     * Daftar seluruh berita untuk manajemen di Panel Pengurus.
     */
    public function adminIndex(Request $request)
    {
        $user = $request->user();
        if (! $this->canPublishNews($user)) {
            return response()->json(['message' => 'Hanya pengurus yang dapat mengelola berita warga.'], 403);
        }

        $articles = NewsArticle::with(self::AUTHOR_RELATIONS)
            ->orderByDesc('published_at')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(fn (NewsArticle $item) => $this->transformArticle($item, true));

        return response()->json(['data' => $articles]);
    }

    /**
     * Terbitkan berita baru (Khusus Pengurus — langsung tayang).
     */
    public function store(Request $request)
    {
        $user = $request->user();
        if (! $this->canPublishNews($user)) {
            return response()->json(['message' => 'Hanya pengurus yang dapat menerbitkan berita warga.'], 403);
        }

        $validated = $request->validate([
            'title'    => ['required', 'string', 'min:8', 'max:180'],
            'category' => ['required', 'string', 'max:60'],
            'layout'   => ['nullable', 'in:hero,thumbnail,grid,text'],
            'body'     => ['required', 'string', 'min:20'],
            'unit_id'  => ['nullable', 'integer', 'exists:units,id'],
            'photo'    => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
        ]);

        $unitId = $validated['unit_id'] ?? $user->unitMemberships()
            ->where('is_approved', true)
            ->value('unit_id');

        $thumbnailPath = null;
        if ($request->hasFile('photo')) {
            $thumbnailPath = $request->file('photo')->store('news-thumbnails', 'public');
        }

        $now = now();
        $dateString = $now->toDateString();
        $layout = $validated['layout'] ?? ($thumbnailPath ? 'thumbnail' : 'text');

        // Pastikan hanya ada 1 berita Hero Utama yang aktif
        if ($layout === 'hero') {
            NewsArticle::where('status', 'published')
                ->where('layout', 'hero')
                ->update(['layout' => 'thumbnail']);
        }

        $article = NewsArticle::create([
            'user_id'        => $user->id,
            'unit_id'        => $unitId,
            'reviewed_by'    => $user->id,
            'title'          => trim($validated['title']),
            'slug'           => NewsArticle::generateUniqueSlug($validated['title'], $dateString),
            'category'       => $validated['category'],
            'layout'         => $layout,
            'excerpt'        => Str::limit(strip_tags(trim($validated['body'])), 150),
            'body'           => trim($validated['body']),
            'thumbnail_path' => $thumbnailPath,
            'status'         => 'published',
            'published_at'   => $now,
            'published_date' => $dateString,
        ]);

        return response()->json([
            'message' => 'Berita berhasil diterbitkan ke Beranda Warga.',
            'data'    => $this->transformArticle($article->load(self::AUTHOR_RELATIONS), true),
        ], 201);
    }

    /**
     * Sunting isi, kategori, atau posisi tata letak berita (Khusus Pengurus).
     */
    public function update(Request $request, NewsArticle $newsArticle)
    {
        $user = $request->user();
        if (! $this->canPublishNews($user)) {
            return response()->json(['message' => 'Hanya pengurus yang dapat menyunting berita.'], 403);
        }

        $validated = $request->validate([
            'title'    => ['sometimes', 'string', 'min:8', 'max:180'],
            'category' => ['sometimes', 'string', 'max:60'],
            'layout'   => ['sometimes', 'in:hero,thumbnail,grid,text'],
            'body'     => ['sometimes', 'string', 'min:20'],
        ]);

        if (isset($validated['title'])) {
            $newsArticle->title = trim($validated['title']);
            $dateString = ($newsArticle->published_at ?? now())->toDateString();
            $newsArticle->slug = NewsArticle::generateUniqueSlug($newsArticle->title, $dateString, $newsArticle->id);
        }
        if (isset($validated['category'])) {
            $newsArticle->category = $validated['category'];
        }
        if (isset($validated['layout'])) {
            $newsArticle->layout = $validated['layout'];
            if ($newsArticle->layout === 'hero') {
                NewsArticle::where('status', 'published')
                    ->where('id', '!=', $newsArticle->id)
                    ->where('layout', 'hero')
                    ->update(['layout' => 'thumbnail']);
            }
        }
        if (isset($validated['body'])) {
            $newsArticle->body = trim($validated['body']);
            $newsArticle->excerpt = Str::limit(strip_tags($newsArticle->body), 150);
        }

        $newsArticle->status = 'published';
        $newsArticle->published_at = $newsArticle->published_at ?? now();
        $newsArticle->published_date = $newsArticle->published_at->toDateString();
        $newsArticle->reviewed_by = $user->id;
        $newsArticle->save();

        return response()->json([
            'message' => 'Perubahan berita berhasil disimpan.',
            'data'    => $this->transformArticle($newsArticle->load(self::AUTHOR_RELATIONS), true),
        ]);
    }

    /**
     * Hapus berita (Khusus Pengurus).
     */
    public function destroy(Request $request, NewsArticle $newsArticle)
    {
        $user = $request->user();
        if (! $this->canPublishNews($user)) {
            return response()->json(['message' => 'Hanya pengurus yang dapat menghapus berita.'], 403);
        }

        if ($newsArticle->thumbnail_path) {
            Storage::disk('public')->delete($newsArticle->thumbnail_path);
        }

        $newsArticle->delete();

        return response()->json(['message' => 'Berita berhasil dihapus.']);
    }

    private function canPublishNews($user): bool
    {
        if (! $user || ! $user->profile?->is_verified) {
            return false;
        }

        return $user->canManageAnnouncements() || $user->isGlobalGovernor();
    }

    private function resolveAuthorRole(?NewsArticle $item): string
    {
        $rolePriority = [
            'ketua_paguyuban'      => 'Ketua Paguyuban',
            'sekretaris_paguyuban' => 'Sekretaris Paguyuban',
            'bendahara_paguyuban'  => 'Bendahara Paguyuban',
            'operator_aplikasi'    => 'Operator Aplikasi',
            'admin'                => 'Admin Sistem',
            'koordinator_zona'     => 'Koordinator Zona',
            'koordinator_blok'     => 'Koordinator Blok',
            'keamanan_lingkungan'  => 'Keamanan Lingkungan',
        ];

        $roles = $item?->author?->governanceRoles ?? collect();

        foreach ($rolePriority as $code => $label) {
            if ($roles->contains('role_code', $code)) {
                return $label;
            }
        }

        return 'Pengurus Paguyuban';
    }

    private function transformArticle(NewsArticle $item, bool $withFullBody = false): array
    {
        $pubDate = $item->published_at ?? $item->created_at;

        return [
            'id'                => $item->id,
            'layout'            => $item->layout,
            'date_path'         => $item->date_path,
            'slug'              => $item->slug,
            'category'          => $item->category,
            'title'             => $item->title,
            'excerpt'           => $item->excerpt,
            'body'              => $withFullBody ? $item->body : null,
            'thumbnail'         => $item->thumbnail_url,
            'author'            => $item->author?->name ?? 'Pengurus PPMT',
            'author_role'       => $this->resolveAuthorRole($item),
            'unit'              => $item->unit?->code ?? '-',
            'status'            => $item->status,
            'published_at'      => $pubDate?->diffForHumans() ?? 'Baru saja',
            'published_date_id' => $pubDate?->translatedFormat('d F Y'),
        ];
    }
}