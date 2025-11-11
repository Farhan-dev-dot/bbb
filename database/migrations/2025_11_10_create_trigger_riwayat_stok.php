<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create trigger untuk otomatis mengelola riwayat stok
        DB::unprepared('
            CREATE TRIGGER tr_update_riwayat_stok 
            AFTER INSERT ON dbo_transaksi
            FOR EACH ROW
            BEGIN
                DECLARE v_stok_awal_isi INT DEFAULT 0;
                DECLARE v_stok_awal_kosong INT DEFAULT 0;
                DECLARE v_stok_akhir_isi INT DEFAULT 0;
                DECLARE v_stok_akhir_kosong INT DEFAULT 0;

                -- Ambil stok terakhir dari riwayat untuk barang ini
                SELECT 
                    COALESCE(stok_akhir_isi, 0),
                    COALESCE(stok_akhir_kosong, 0)
                INTO v_stok_awal_isi, v_stok_awal_kosong
                FROM dbo_riwayat_stok 
                WHERE id_barang = NEW.id_barang 
                ORDER BY id_riwayat DESC 
                LIMIT 1;

                -- Jika tidak ada riwayat sebelumnya, ambil dari master barang
                IF v_stok_awal_isi = 0 AND v_stok_awal_kosong = 0 THEN
                    SELECT COALESCE(stok_tabung, 0) INTO v_stok_awal_isi
                    FROM dbo_master_barang 
                    WHERE id_barang = NEW.id_barang;
                    SET v_stok_awal_kosong = 0;
                END IF;

                -- Hitung stok akhir berdasarkan logika refill tabung
                SET v_stok_akhir_isi = v_stok_awal_isi - NEW.jumlah_isi - COALESCE(NEW.pinjam_tabung, 0);
                SET v_stok_akhir_kosong = v_stok_awal_kosong + NEW.jumlah_kosong;

                -- Insert ke riwayat stok
                INSERT INTO dbo_riwayat_stok (
                    id_barang,
                    id_transaksi,
                    jenis_transaksi,
                    stok_awal_isi,
                    stok_awal_kosong,
                    stok_akhir_isi,
                    stok_akhir_kosong,
                    pinjam_tabung,
                    tanggal_transaksi
                ) VALUES (
                    NEW.id_barang,
                    NEW.id_pengiriman,
                    "keluar",
                    v_stok_awal_isi,
                    v_stok_awal_kosong,
                    v_stok_akhir_isi,
                    v_stok_akhir_kosong,
                    COALESCE(NEW.pinjam_tabung, 0),
                    NOW()
                );
            END
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS tr_update_riwayat_stok');
    }
};
