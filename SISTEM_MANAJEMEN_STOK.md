# SISTEM MANAJEMEN STOK DEPOT GAS

## 📋 ALUR BISNIS

```
[Master Data Barang]
         |
         v
[Barang Masuk] --> Stok Bertambah
         |
         v
[Barang Keluar] --> Stok Berkurang
         |
         v
[Transaksi Tukar Tabung]
         |
         v
[Stok Opname & Penyesuaian]
         |
         v
[Laporan Stok & Transaksi]
```

## 🗄️ STRUKTUR DATABASE

### 1. **dbo_master_barang** (Master Data)

-   `stok_tabung_isi` - Stok tabung yang sudah terisi gas
-   `stok_tabung_kosong` - Stok tabung kosong
-   `stok_minimum` - Batas minimum stok untuk alert
-   `harga_jual` - Harga jual per unit

### 2. **dbo_barang_masuk** (Transaksi Masuk)

-   Mencatat barang yang masuk dari supplier
-   **EFEK**: `stok_tabung_isi += jumlah_isi`, `stok_tabung_kosong += jumlah_kosong`

### 3. **dbo_barang_keluar** (Transaksi Keluar)

-   Mencatat penjualan ke customer
-   **EFEK**: `stok_tabung_isi -= (jumlah_isi + pinjam_tabung)`, `stok_tabung_kosong += jumlah_kosong`

### 4. **dbo_riwayat_stok** (Audit Trail)

-   `tipe_transaksi`: MASUK, KELUAR, KOREKSI
-   `perubahan_isi` & `perubahan_kosong`: Delta perubahan stok
-   `stok_akhir_isi` & `stok_akhir_kosong`: Stok setelah transaksi

## 🔗 API ENDPOINTS

### 🔐 Authentication

```
POST /api/auth/login
POST /api/auth/register
POST /api/auth/logout
```

### 📊 Dashboard

```
GET /api/total-customer
GET /api/total-barang
GET /api/total-stok-akhir-isi
GET /api/total-stok-akhir-kosong
GET /api/distribusi-jenis-barang
```

### 📦 MASTER BARANG

```
GET    /api/master-barang           # List semua master barang
POST   /api/master-barang           # Tambah master barang baru
GET    /api/master-barang/{id}      # Detail master barang
PUT    /api/master-barang/{id}      # Update master barang
DELETE /api/master-barang/{id}      # Hapus master barang
```

**Request Body (POST/PUT):**

```json
{
    "kode_barang": "LPG12",
    "nama_barang": "Tabung Gas LPG 12kg",
    "jenis_barang": "LPG",
    "kapasitas": "12kg",
    "satuan": "Tabung",
    "stok_tabung_isi": 100,
    "stok_tabung_kosong": 50,
    "stok_minimum": 10,
    "harga_beli": 80000,
    "harga_jual": 100000,
    "supplier_utama": "PT Pertamina",
    "status": "aktif",
    "keterangan": "Tabung gas rumah tangga"
}
```

### 📦 MASTER CUSTOMER

```
GET    /api/master-customer           # List semua master customer
POST   /api/master-customer           # Tambah master customer baru
GET    /api/master-customer/{id}      # Detail master customer
PUT    /api/master-customer/{id}      # Update master customer
DELETE /api/master-customer/{id}      # Hapus master customer
```

**Request Body (POST/PUT):**

```json
{
    "nama_customer": "PT. Gas Makmur",
    "alamat": "Jl. Raya Utama No. 123, Jakarta Selatan",
    "telepon": "021-12345678"
}
```

### 📥 BARANG MASUK

```
GET    /api/barang-masuk           # List semua barang masuk
POST   /api/barang-masuk           # Tambah barang masuk baru
GET    /api/barang-masuk/{id}      # Detail barang masuk
PUT    /api/barang-masuk/{id}      # Update barang masuk
DELETE /api/barang-masuk/{id}      # Hapus barang masuk
```

**Request Body (POST/PUT):**

```json
{
    "id_barang": 1,
    "jumlah_isi": 50,
    "jumlah_kosong": 20,
    "supplier": "PT Supplier Gas",
    "keterangan": "Pengadaan rutin",
    "tanggal_masuk": "2025-11-15"
}
```

### 📤 BARANG KELUAR

```
GET    /api/barang-keluar          # List semua barang keluar
POST   /api/barang-keluar          # Tambah barang keluar baru
GET    /api/barang-keluar/{id}     # Detail barang keluar
PUT    /api/barang-keluar/{id}     # Update barang keluar
DELETE /api/barang-keluar/{id}     # Hapus barang keluar
```

**Request Body (POST/PUT):**

```json
{
    "id_barang": 1,
    "id_customer": 1,
    "jumlah_isi": 10,
    "jumlah_kosong": 8,
    "pinjam_tabung": 2,
    "harga_satuan": 25000,
    "status": "completed",
    "keterangan": "Pengiriman reguler",
    "tanggal_keluar": "2025-11-15"
}
```

### 📋 Dashboard

```
GET {{API_URL}}/api/total-customer
GET {{API_URL}}/api/total-barang
GET {{API_URL}}/api/total-stok-akhir-isi
GET {{API_URL}}/api/total-stok-akhir-kosong
GET {{API_URL}}/api/distribusi-jenis-barang
GET {{API_URL}}/api/pendapatan-per-tanggal
```

### 📋 STOK OPNAME

```
GET  {{API_URL}}/api/stok-opname                    # List riwayat opname
GET  {{API_URL}}/api/stok-opname/current-stok       # Stok saat ini untuk opname
POST {{API_URL}}/api/stok-opname/koreksi            # Lakukan koreksi stok
GET  {{API_URL}}/api/stok-opname/laporan-selisih    # Laporan selisih
GET  {{API_URL}}/api/stok-opname/stok-minimum       # Alert stok minimum
```

**Request Body (Koreksi Stok):**

```json
{
    "corrections": [
        {
            "id_barang": 1,
            "stok_isi_fisik": 45,
            "stok_kosong_fisik": 30,
            "keterangan": "Hasil opname fisik"
        }
    ],
    "tanggal_opname": "2025-11-15"
}
```

### 📈 LAPORAN

```
GET {{API_URL}}/api/laporan/mutasi-stok-harian      # Mutasi stok per hari
GET {{API_URL}}/api/laporan/mutasi-stok-bulanan     # Mutasi stok per bulan
GET {{API_URL}}/api/laporan/barang-masuk            # Laporan barang masuk
GET {{API_URL}}/api/laporan/barang-keluar           # Laporan penjualan
GET {{API_URL}}/api/laporan/cashflow                # Laporan keuangan
GET {{API_URL}}/api/laporan/dashboard-summary       # Summary dashboard
```

## 💡 CONTOH PENGGUNAAN

### 1. **Tambah Master Barang**

```bash
curl -X POST /api/master-barang \
-H "Authorization: Bearer TOKEN" \
-H "Content-Type: application/json" \
-d '{
    "kode_barang": "LPG12",
    "nama_barang": "Tabung Gas LPG 12kg",
    "jenis_barang": "LPG",
    "kapasitas": "12kg",
    "satuan": "Tabung",
    "stok_tabung_isi": 100,
    "stok_tabung_kosong": 50,
    "stok_minimum": 10,
    "harga_beli": 80000,
    "harga_jual": 100000,
    "supplier_utama": "PT Pertamina",
    "status": "aktif"
}'
```

**Response:**

```json
{
    "status": true,
    "message": "Master barang berhasil ditambahkan",
    "data": {
        "id_barang": 1,
        "kode_barang": "LPG12",
        "nama_barang": "Tabung Gas LPG 12kg",
        "jenis_barang": "LPG",
        "kapasitas": "12kg",
        "stok_tabung_isi": 100,
        "stok_tabung_kosong": 50,
        "stok_minimum": 10,
        "harga_jual": 100000,
        "status": "aktif",
        "created_at": "2025-11-15T10:00:00.000000Z"
    }
}
```

### 2. **Cek Stok Rendah**

```bash
curl -X GET /api/master-barang/stok-rendah \
-H "Authorization: Bearer TOKEN"
```

**Response:**

```json
{
    "status": true,
    "message": "Daftar barang dengan stok rendah",
    "data": [
        {
            "id_barang": 2,
            "nama_barang": "Tabung Gas LPG 3kg",
            "stok_tabung_isi": 5,
            "stok_minimum": 10,
            "selisih": -5,
            "status_alert": "KRITIS"
        }
    ],
    "total_barang_kritis": 1
}
```

### 3. **Input Barang Masuk**

```bash
curl -X POST /api/barang-masuk \
-H "Authorization: Bearer TOKEN" \
-H "Content-Type: application/json" \
-d '{
    "id_barang": 1,
    "jumlah_isi": 100,
    "jumlah_kosong": 50,
    "supplier": "PT Gas Supplier",
    "tanggal_masuk": "2025-11-15"
}'
```

**Response:**

```json
{
    "status": true,
    "message": "Barang masuk berhasil dicatat",
    "data": {
        "transaksi": {...},
        "stok_info": {
            "stok_sebelum": {
                "tabung_isi": 50,
                "tabung_kosong": 20
            },
            "stok_sesudah": {
                "tabung_isi": 150,
                "tabung_kosong": 70
            },
            "perubahan": {
                "masuk_isi": 100,
                "masuk_kosong": 50
            }
        }
    }
}
```

### 4. **Input Barang Keluar**

```bash
curl -X POST /api/barang-keluar \
-H "Authorization: Bearer TOKEN" \
-H "Content-Type: application/json" \
-d '{
    "id_barang": 1,
    "id_customer": 1,
    "jumlah_isi": 10,
    "jumlah_kosong": 8,
    "pinjam_tabung": 2,
    "tanggal_keluar": "2025-11-15"
}'
```

### 5. **Stok Opname**

```bash
curl -X POST /api/stok-opname/koreksi \
-H "Authorization: Bearer TOKEN" \
-H "Content-Type: application/json" \
-d '{
    "corrections": [
        {
            "id_barang": 1,
            "stok_isi_fisik": 138,
            "stok_kosong_fisik": 78,
            "keterangan": "Selisih -2 isi karena kebocoran"
        }
    ],
    "tanggal_opname": "2025-11-15"
}'
```

## ⚡ LOGIKA BISNIS

### **Master Barang:**

-   🗂️ **CRUD Operations** untuk data master
-   📊 **Stok Tracking** real-time
-   🚨 **Alert Stok Minimum** otomatis
-   💰 **Price Management** (harga beli & jual)
-   📝 Log: Semua perubahan tercatat

### **Barang Masuk:**

-   ✅ Stok Isi **BERTAMBAH** (+)
-   ✅ Stok Kosong **BERTAMBAH** (+)
-   📝 Log: `tipe_transaksi = "MASUK"`

### **Barang Keluar:**

-   ❌ Stok Isi **BERKURANG** (-)
-   ✅ Stok Kosong **BERTAMBAH** (+) _dari customer_
-   📝 Log: `tipe_transaksi = "KELUAR"`
-   💰 **Auto-create** record di `dbo_transaksi` untuk laporan

### **Stok Opname:**

-   🔄 Stok **DISESUAIKAN** dengan fisik
-   📝 Log: `tipe_transaksi = "KOREKSI"`

## 🛡️ VALIDASI & KEAMANAN

1. **JWT Authentication** untuk semua endpoint
2. **Stock Validation** sebelum transaksi keluar
3. **Database Transaction** untuk konsistensi data
4. **Audit Trail** lengkap di riwayat_stok
5. **Error Handling** dengan rollback otomatis

## 📋 MIGRATION COMMANDS

```bash
# Jika perlu tambah kolom baru
php artisan make:migration add_tipe_transaksi_to_riwayat_stok
php artisan make:migration add_perubahan_columns_to_riwayat_stok
```

## 🎯 FITUR UTAMA

✅ **Master Data Management** (Barang, Customer, Supplier)  
✅ **Multi-type Stock Management** (Isi & Kosong)  
✅ **Real-time Stock Updates**  
✅ **Stock Alert System** (stok minimum)  
✅ **Comprehensive Audit Trail**  
✅ **Stock Opname & Adjustments**  
✅ **Financial Reports & Analytics**  
✅ **Auto Transaction Recording** (dbo_transaksi)  
✅ **Transaction Rollback Support**  
✅ **API-First Architecture**  
✅ **Price Management** (harga beli & jual)

---

**Sistem Depot Gas** - Manajemen stok terintegrasi untuk efisiensi maksimal! 🚀
