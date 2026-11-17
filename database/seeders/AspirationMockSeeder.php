<?php

namespace Database\Seeders;

use App\Models\Aspiration;
use App\Models\AspirationSignature;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;

class AspirationMockSeeder extends Seeder
{
    public function run(): void
    {
        $firstUser = User::first();
        if (! $firstUser) {
            return;
        }

        $units = Unit::orderBy('code')->take(12)->get();
        $firstUnit = $units->first();

        $cases = [
            [
                'category' => 'Lingkungan & Kebersihan',
                'title' => 'Antisipasi Rontokan Daun Kering dari Tegalan Sebelah Perumahan Penyumbat Talang',
                'description' => "Rontokan daun kering dari lahan tegalan di sisi perbatasan perumahan cukup lebat terbawa angin dan menumpuk di atap serta talang air rumah warga. Jika tidak segera dibuatkan pembatas jaring (paranet) atau jadwal pembersihan batas lahan bersama pihak terkait, talang berisiko mampet dan meluap saat hujan deras.",
                'location_scope' => 'Kavling Perbatasan Tegalan',
                'status' => 'open',
                'target_signatures' => 25,
                'developer_response' => null,
                'sample_count' => 7,
                'sample_notes' => [
                    'Talang belakang rumah kami sudah dua kali penuh daun kering dalam sebulan terakhir.',
                    'Setuju, perlu ada pembatas atau koordinasi kebersihan batas perumahan sebelum puncak musim hujan.',
                ],
            ],
            [
                'category' => 'Infrastruktur & Drainase',
                'title' => 'Penanganan Genangan Air di Ruas Jalan Sisi Barat Saat Hujan',
                'description' => "Setiap kali turun hujan dengan intensitas sedang hingga deras, muncul genangan air yang cukup lama surut di salah satu ruas jalan sisi Barat. Kondisi ini mengganggu kenyamanan kendaraan melintas, berpotensi merusak lapisan jalan, dan menjadi sarang nyamuk apabila dibiarkan berlarut.",
                'location_scope' => 'Ruas Jalan Sisi Barat',
                'status' => 'open',
                'target_signatures' => 35,
                'developer_response' => null,
                'sample_count' => 11,
                'sample_notes' => [
                    'Kemiringan saluran air di titik tersebut memang terlihat kurang lancar pembuangannya.',
                    'Mohon segera dicek elevasi drainasenya oleh tim teknis pengembang.',
                ],
            ],
            [
                'category' => 'Kualitas Bangunan',
                'title' => 'Evaluasi & Perbaikan Tuntas Masalah Kebocoran Atap/Dinding Pasca-Komplain',
                'description' => "Beberapa warga sudah melaporkan keluhan kebocoran dan sudah sempat dikerjakan oleh tukang perumahan, namun hasilnya belum menyelesaikan akar masalah (masih rembes atau bocor kembali saat hujan deras). Warga mengusulkan pendataan kavling terdampak agar pengembang menurunkan tim teknis yang lebih kompeten dengan garansi tuntas.",
                'location_scope' => 'Beberapa Unit Kavling (Blok A–D)',
                'status' => 'submitted',
                'target_signatures' => 30,
                'developer_response' => 'Sudah masuk dalam daftar poin audiensi Tim Persiapan Paguyuban dengan manajemen pengembang untuk inventarisasi unit terdampak.',
                'sample_count' => 9,
                'sample_notes' => [
                    'Bagian sambungan atap dapur masih rembes meski sudah sempat ditambal.',
                    'Perlu pengecekan ulang nok atap dan waterproofing dinding samping.',
                ],
            ],
            [
                'category' => 'Kualitas Bangunan',
                'title' => 'Penanganan Meluasnya Retak Rambut pada Dinding di Beberapa Kavling',
                'description' => "Ditemukan retak rambut yang semakin bertambah panjang dan melebar pada bagian dinding plesteran di sejumlah unit rumah. Meski awalnya dianggap penyusutan wajar, sebarannya yang makin banyak perlu ditindaklanjuti secara kolektif agar pengembang melakukan perbaikan acian dan pengecatan ulang sesuai standar mutu serah terima.",
                'location_scope' => 'Beberapa Unit Kavling',
                'status' => 'open',
                'target_signatures' => 30,
                'developer_response' => null,
                'sample_count' => 6,
                'sample_notes' => [
                    'Di dinding ruang tamu dan kamar depan muncul garis retak memanjang.',
                    'Mendukung pendataan kolektif supaya perbaikannya serempak dan rapi.',
                ],
            ],
        ];

        foreach ($cases as $index => $item) {
            $aspiration = Aspiration::updateOrCreate(
                ['title' => $item['title']],
                [
                    'user_id' => $firstUser->id,
                    'unit_id' => $firstUnit?->id,
                    'category' => $item['category'],
                    'description' => $item['description'],
                    'location_scope' => $item['location_scope'],
                    'status' => $item['status'],
                    'target_signatures' => $item['target_signatures'],
                    'developer_response' => $item['developer_response'],
                    'reviewed_at' => now()->subDays(4 - $index),
                ]
            );

            $sliceUnits = $units->slice(0, min($item['sample_count'], $units->count()));
            foreach ($sliceUnits as $uIdx => $unit) {
                AspirationSignature::firstOrCreate(
                    [
                        'aspiration_id' => $aspiration->id,
                        'unit_id' => $unit->id,
                    ],
                    [
                        'user_id' => $firstUser->id,
                        'note' => $item['sample_notes'][$uIdx] ?? null,
                    ]
                );
            }
        }
    }
}