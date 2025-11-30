# UPDATE DATABASE STRUKTUR - SISTEM MANAJEMEN STOK

## 🗄️ STRUKTUR DATABASE YANG DISESUAIKAN

Berdasarkan diagram database yang tersedia, berikut struktur tabel yang sebenarnya:

### **dbo_riwayat_stok** (Tabel Audit Stok)

```sql
CREATE TABLE dbo_riwayat_stok (
    id_riwayat INT PRIMARY KEY AUTO_INCREMENT,
    id_barang INT,
    id_transaksi INT,
    tipe_transaksi ENUM('MASUK','KELUAR','KOREKSI'),
    perubahan_isi INT,
    perubahan_kosong INT,
    stok_isi_setelah INT,
    stok_kosong_setelah INT,
    tanggal_transaksi DATETIME,
    FOREIGN KEY (id_barang) REFERENCES dbo_master_barang(id_barang)
);
```

### **dbo_barang_masuk** (Transaksi Masuk)

```sql
CREATE TABLE dbo_barang_masuk (
    id_masuk INT PRIMARY KEY AUTO_INCREMENT,
    id_barang INT,
    jumlah_isi INT,
    jumlah_kosong INT,
    supplier VARCHAR(100),
    keterangan VARCHAR(200),
    tanggal_masuk DATETIME,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (id_barang) REFERENCES dbo_master_barang(id_barang)
);
```

### **dbo_barang_keluar** (Transaksi Keluar)

```sql
CREATE TABLE dbo_barang_keluar (
    id_keluar INT PRIMARY KEY AUTO_INCREMENT,
    id_barang INT,
    id_customer INT,
    jumlah_isi INT,
    jumlah_kosong INT,
    pinjam_tabung INT,
    harga_satuan INT,
    total_harga INT,
    status VARCHAR(100),
    keterangan VARCHAR(255),
    tanggal_keluar DATETIME,
    created_at TIMESTAMP,
    FOREIGN KEY (id_barang) REFERENCES dbo_master_barang(id_barang),
    FOREIGN KEY (id_customer) REFERENCES dbo_customer(id_customer)
);
```

## 🔧 PERUBAHAN YANG DILAKUKAN

### 1. **RiwayatStokModel.php**

-   ❌ Hapus kolom: `stok_awal_isi`, `stok_awal_kosong`, `stok_akhir_isi`, `stok_akhir_kosong`, `pinjam_tabung`
-   ✅ Gunakan kolom: `perubahan_isi`, `perubahan_kosong`, `stok_isi_setelah`, `stok_kosong_setelah`

### 2. **BarangMasukController.php**

-   ✅ Riwayat stok hanya menyimpan: perubahan dan stok setelah transaksi
-   ✅ Validasi otomatis untuk stok positif

### 3. **BarangKeluarController.php**

-   ✅ Perubahan isi selalu negatif untuk keluar
-   ✅ Perubahan kosong positif jika customer bawa tabung kosong

### 4. **StokOpnameController.php**

-   ✅ Koreksi langsung ke stok akhir
-   ✅ Perhitungan selisih otomatis

### 5. **Database Triggers**

-   ✅ Update trigger untuk struktur tabel yang benar
-   ✅ Hapus kolom yang tidak ada

## 📊 LOGIKA BISNIS

### **Barang Masuk:**

```json
{
    "tipe_transaksi": "MASUK",
    "perubahan_isi": 50, // +50 tabung isi masuk
    "perubahan_kosong": 20, // +20 tabung kosong masuk
    "stok_isi_setelah": 150, // Total stok isi sekarang
    "stok_kosong_setelah": 70 // Total stok kosong sekarang
}
```

### **Barang Keluar:**

```json
{
    "tipe_transaksi": "KELUAR",
    "perubahan_isi": -12, // -10 isi + -2 pinjam = -12
    "perubahan_kosong": 8, // +8 tabung kosong dari customer
    "stok_isi_setelah": 138, // Stok isi berkurang
    "stok_kosong_setelah": 78 // Stok kosong bertambah
}
```

### **Stok Opname:**

```json
{
    "tipe_transaksi": "KOREKSI",
    "perubahan_isi": -2, // Selisih: fisik 136 vs sistem 138
    "perubahan_kosong": 2, // Selisih: fisik 80 vs sistem 78
    "stok_isi_setelah": 136, // Sesuai hasil fisik
    "stok_kosong_setelah": 80 // Sesuai hasil fisik
}
```

## 🚀 TESTING

### **Test Barang Masuk:**

```bash
curl -X POST /api/barang-masuk \
-H "Authorization: Bearer TOKEN" \
-d '{
    "id_barang": 1,
    "jumlah_isi": 50,
    "jumlah_kosong": 20,
    "supplier": "PT Supplier",
    "tanggal_masuk": "2025-11-15"
}'
```

### **Test Barang Keluar:**

```bash
curl -X POST /api/barang-keluar \
-H "Authorization: Bearer TOKEN" \
-d '{
    "id_barang": 1,
    "id_customer": 1,
    "jumlah_isi": 10,
    "jumlah_kosong": 8,
    "pinjam_tabung": 2,
    "tanggal_keluar": "2025-11-15"
}'
```

### **Test Stok Opname:**

```bash
curl -X POST /api/stok-opname/koreksi \
-H "Authorization: Bearer TOKEN" \
-d '{
    "corrections": [{
        "id_barang": 1,
        "stok_isi_fisik": 136,
        "stok_kosong_fisik": 80,
        "keterangan": "Hasil opname fisik"
    }],
    "tanggal_opname": "2025-11-15"
}'
```

## ✅ STATUS UPDATE

-   ✅ **RiwayatStokModel** - Disesuaikan dengan struktur database
-   ✅ **BarangMasukController** - Menggunakan kolom yang benar
-   ✅ **BarangKeluarController** - Menggunakan kolom yang benar
-   ✅ **StokOpnameController** - Menggunakan kolom yang benar
-   ✅ **LaporanController** - Menggunakan kolom yang benar
-   ✅ **TransaksiController** - Validasi stok menggunakan kolom yang benar
-   ✅ **Database Triggers** - Disesuaikan dengan struktur tabel

Sistem sekarang fully compatible dengan struktur database yang ada! 🎯
