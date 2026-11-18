<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('unit_memberships', function (Blueprint $table) {
            $table->boolean('is_owner')->default(false)->after('guarantor_user_id');
            $table->string('owner_role', 30)->nullable()->after('is_owner'); // primary_owner | co_owner
            $table->boolean('is_occupant')->default(true)->after('owner_role');
            $table->string('resident_role', 30)->nullable()->after('is_occupant'); // primary_resident | family_member | co_resident | tenant | caretaker
            $table->string('ownership_verification_status', 30)->default('none')->after('resident_role'); // none | unverified_claim | verified_shm
            $table->text('nib_shm_encrypted')->nullable()->after('ownership_verification_status');
            $table->timestamp('ownership_verified_at')->nullable()->after('nib_shm_encrypted');
            $table->foreignId('ownership_verified_by_user_id')->nullable()->after('ownership_verified_at')->constrained('users')->nullOnDelete();
        });

        // Sinkronisasi data lama agar kompatibel 100%
        DB::table('unit_memberships')->get()->each(function ($row) {
            $isOwner = $row->entity_type === 'owner';
            $ownerRole = $isOwner ? ($row->sub_role ?: 'primary_owner') : null;
            $residentRole = ! $isOwner ? ($row->sub_role ?: 'primary_resident') : 'primary_resident';

            // Cek apakah unit disewakan ke orang lain; jika tidak, pemilik lama dianggap menghuni
            $unitStatus = DB::table('units')->where('id', $row->unit_id)->value('occupancy_status');
            $isOccupant = $isOwner ? ($unitStatus !== 'rented') : true;

            $profileVerifiedDoc = DB::table('resident_profiles')
                ->where('user_id', $row->user_id)
                ->value('is_ownership_doc_verified');

            $ownershipStatus = 'none';
            if ($isOwner) {
                $ownershipStatus = $profileVerifiedDoc ? 'verified_shm' : 'unverified_claim';
            }

            DB::table('unit_memberships')
                ->where('id', $row->id)
                ->update([
                    'is_owner' => $isOwner,
                    'owner_role' => $ownerRole,
                    'is_occupant' => $isOccupant,
                    'resident_role' => $isOccupant ? $residentRole : null,
                    'ownership_verification_status' => $ownershipStatus,
                ]);
        });
    }

    public function down(): void
    {
        Schema::table('unit_memberships', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ownership_verified_by_user_id');
            $table->dropColumn([
                'is_owner',
                'owner_role',
                'is_occupant',
                'resident_role',
                'ownership_verification_status',
                'nib_shm_encrypted',
                'ownership_verified_at',
            ]);
        });
    }
};