stateDiagram-v2
    direction LR

    state "1. Status Hunian Kavling (Unit.occupancy_status)" as UnitState {
        [*] --> unclaimed : Seed Awal (91 Kavling)
        unclaimed --> pending : Klaim Mandiri (Tanpa Undangan)
        unclaimed --> occupied : Daftar via Undangan / Assisted Register (Pemilik/Penghuni)
        unclaimed --> rented : Daftar via Undangan / Assisted Register (Penyewa)
        pending --> occupied : Disetujui Pengurus / Penjamin (Non-Penyewa)
        pending --> rented : Disetujui Pengurus / Penjamin (Penyewa)
        occupied --> rented : Pemilik Menyewakan Unit
        rented --> occupied : Masa Sewa Selesai / Dihuni Pemilik
    }

    state "2. Verifikasi Kepemilikan SHM (UnitMembership)" as ShmState {
        [*] --> none : Bukan Pemilik (is_owner = false)
        [*] --> unverified_claim : Klaim Pemilik Tanpa NIB / Diisi Warga
        none --> unverified_claim : Warga Centang Pemilik + Isi NIB SHM
        unverified_claim --> verified_shm : Disahkan Pengurus (OwnershipVerification >= 1)
        verified_shm --> unverified_claim : Semua Pengesah Menarik Pengesahan / Hapus NIB
    }

    state "3. Status Aspirasi Warga (Aspiration.status)" as AspState {
        [*] --> pending : Diajukan Warga Biasa
        [*] --> open : Diajukan Pengurus (Auto-Open)
        pending --> open : Lolos Kurasi Pengurus
        pending --> rejected : Ditolak Kurasi Pengurus
        open --> submitted : Target Tanda Tangan Tercapai -> Diajukan ke Developer
        submitted --> resolved : Ditindaklanjuti / Selesai
        submitted --> rejected : Ditolak Developer
    }

    state "4. Status Iuran Sampah Bulanan (DuesPayment.status)" as DuesState {
        [*] --> unpaid : Tagihan Bulan Berjalan (Unit Terhuni)
        [*] --> vacant : Unit Belum Terhuni (unclaimed / pending)
        unpaid --> paid : Bendahara Catat Lunas (+ Auto CashLedgerEntry)
        unpaid --> exempt : Dibebaskan Bendahara
        paid --> unpaid : Dibatalkan Bendahara (- Hapus CashLedgerEntry)
        exempt --> unpaid : Diaktifkan Kembali Tagihannya
    }

    state "5. Rekonsiliasi Kontak WA (WhatsappGroupContact)" as WaState {
        [*] --> unmatched : Hasil Impor CSV Google Contacts
        unmatched --> matched_auto : Nomor HP Cocok dengan ResidentProfile
        unmatched --> matched_manual : Dihubungkan Manual oleh Admin Sistem
        unmatched --> ignored : Ditandai Non-Penghuni / Vendor / Abaikan
    }