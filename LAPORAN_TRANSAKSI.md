# 📊 LAPORAN TRANSAKSI - dbo_transaksi

Sistem laporan transaksi komprehensif untuk menganalisis data penjualan dan operasional depot gas.

## 🎯 ENDPOINT LAPORAN TRANSAKSI

### 📅 **1. Laporan Harian**

```
GET /api/laporan-transaksi/harian?tanggal=2025-11-15
```

**Response:**

```json
{
    "status": true,
    "message": "Laporan transaksi harian berhasil diambil",
    "summary": {
        "tanggal": "2025-11-15",
        "total_transaksi": 25,
        "total_tabung_isi": 150,
        "total_tabung_kosong": 120,
        "total_pinjam_tabung": 30,
        "total_pendapatan": 3750000,
        "rata_rata_harga": 25000,
        "transaksi_per_status": {
            "pending": 2,
            "completed": 22,
            "cancelled": 1
        }
    },
    "data": [...]
}
```

### 📊 **2. Laporan Bulanan**

```
GET /api/laporan-transaksi/bulanan?bulan=11&tahun=2025
```

**Response:**

```json
{
    "status": true,
    "message": "Laporan transaksi bulanan berhasil diambil",
    "summary": {
        "periode": {"bulan": "11", "tahun": "2025"},
        "total_transaksi": 450,
        "total_tabung_isi": 2700,
        "total_pendapatan": 67500000,
        "rata_rata_pendapatan_harian": 2250000,
        "hari_terbaik": {
            "tanggal": "2025-11-15",
            "total_pendapatan": 4200000
        }
    },
    "grafik_harian": [
        {
            "tanggal": "2025-11-01",
            "total_transaksi": 15,
            "total_pendapatan": 1875000,
            "total_tabung_isi": 75
        }
    ],
    "data": [...]
}
```

### 👥 **3. Laporan per Customer**

```
GET /api/laporan-transaksi/by-customer
GET /api/laporan-transaksi/by-customer?id_customer=1
GET /api/laporan-transaksi/by-customer?tanggal_dari=2025-11-01&tanggal_sampai=2025-11-30
```

**Response:**

```json
{
    "status": true,
    "message": "Laporan transaksi per customer berhasil diambil",
    "total_customers": 25,
    "data": [
        {
            "customer": {
                "id_customer": 1,
                "nama_customer": "Warung Bu Siti",
                "alamat": "Jl. Merdeka No. 123",
                "telepon": "081234567890"
            },
            "total_transaksi": 45,
            "total_tabung_isi": 270,
            "total_tabung_kosong": 200,
            "total_pinjam_tabung": 50,
            "total_pembelian": 6750000,
            "transaksi_terakhir": "2025-11-15",
            "status_transaksi": {
                "pending": 0,
                "completed": 44,
                "cancelled": 1
            }
        }
    ]
}
```

### 🏷️ **4. Laporan per Barang**

```
GET /api/laporan-transaksi/by-barang
GET /api/laporan-transaksi/by-barang?id_barang=1
GET /api/laporan-transaksi/by-barang?tanggal_dari=2025-11-01&tanggal_sampai=2025-11-30
```

**Response:**

```json
{
    "status": true,
    "message": "Laporan transaksi per barang berhasil diambil",
    "total_barang": 3,
    "data": [
        {
            "barang": {
                "id_barang": 1,
                "kode_barang": "TG-3KG",
                "nama_barang": "Tabung Gas 3 Kg",
                "kapasitas": "3 Kg"
            },
            "total_transaksi": 280,
            "total_tabung_isi_terjual": 1680,
            "total_tabung_kosong_diterima": 1200,
            "total_pinjam_tabung": 320,
            "total_pendapatan": 42000000,
            "rata_rata_harga": 25000,
            "customer_terbanyak": "Warung Bu Siti"
        }
    ]
}
```

### 🏠 **5. Dashboard Summary**

```
GET /api/laporan-transaksi/dashboard-summary
```

**Response:**

```json
{
    "status": true,
    "message": "Dashboard summary transaksi berhasil diambil",
    "data": {
        "hari_ini": {
            "total_transaksi": 18,
            "total_pendapatan": 2250000,
            "total_tabung_isi": 90,
            "pending_count": 2
        },
        "bulan_ini": {
            "total_transaksi": 450,
            "total_pendapatan": 11250000,
            "rata_rata_harian": 750000,
            "customer_unique": 25
        },
        "top_customer": [
            {
                "id_customer": 1,
                "total_pembelian": 6750000,
                "total_transaksi": 45,
                "customer": {...}
            }
        ],
        "transaksi_pending": [...]
    }
}
```

## 💡 CONTOH PENGGUNAAN

### **1. Dashboard Harian Manager**

```bash
curl -X GET "/api/laporan-transaksi/harian?tanggal=$(date +%Y-%m-%d)" \
-H "Authorization: Bearer TOKEN"
```

### **2. Analisis Customer Terbaik**

```bash
curl -X GET "/api/laporan-transaksi/by-customer?tanggal_dari=2025-11-01&tanggal_sampai=2025-11-30" \
-H "Authorization: Bearer TOKEN"
```

### **3. Performance Produk Bulanan**

```bash
curl -X GET "/api/laporan-transaksi/by-barang?tanggal_dari=2025-11-01&tanggal_sampai=2025-11-30" \
-H "Authorization: Bearer TOKEN"
```

### **4. Monitor Real-time**

```bash
curl -X GET "/api/laporan-transaksi/dashboard-summary" \
-H "Authorization: Bearer TOKEN"
```

## 📈 FITUR ANALYTICS

### **📊 Summary Metrics:**

-   Total transaksi & pendapatan
-   Rata-rata harga jual & volume penjualan
-   Distribusi status transaksi
-   Top customer & produk terlaris

### **📅 Time-based Analysis:**

-   Laporan harian dengan detail transaksi
-   Trend bulanan dengan grafik per hari
-   Perbandingan periode (YoY, MoM)

### **👥 Customer Intelligence:**

-   Customer terbaik berdasarkan pembelian
-   Frekuensi transaksi per customer
-   Analisis loyalitas customer

### **🏷️ Product Performance:**

-   Produk terlaris berdasarkan pendapatan
-   Volume penjualan per jenis tabung
-   Rata-rata harga per produk

### **⚠️ Operational Alerts:**

-   Transaksi pending yang perlu diproses
-   Customer dengan transaksi cancelled tinggi
-   Monitor daily/monthly targets

## 🔍 FILTER & PARAMETER

| Parameter        | Deskripsi                | Format     | Default       |
| ---------------- | ------------------------ | ---------- | ------------- |
| `tanggal`        | Filter tanggal harian    | YYYY-MM-DD | Today         |
| `bulan`          | Filter bulan             | 1-12       | Current month |
| `tahun`          | Filter tahun             | YYYY       | Current year  |
| `tanggal_dari`   | Range tanggal mulai      | YYYY-MM-DD | -             |
| `tanggal_sampai` | Range tanggal akhir      | YYYY-MM-DD | -             |
| `id_customer`    | Filter customer spesifik | integer    | All           |
| `id_barang`      | Filter barang spesifik   | integer    | All           |

## 🎯 USE CASES

### **🏢 Management Dashboard:**

-   Monitor daily revenue & transaction volume
-   Track monthly performance vs targets
-   Identify top-performing customers & products

### **📊 Sales Analysis:**

-   Analyze seasonal patterns
-   Compare product performance
-   Customer segmentation & loyalty analysis

### **🔍 Operations Monitoring:**

-   Track pending transactions
-   Monitor transaction status distribution
-   Identify operational bottlenecks

### **📈 Business Intelligence:**

-   Revenue trend analysis
-   Customer lifetime value calculation
-   Product profitability analysis

---

**Sistem Laporan Transaksi** - Analytics untuk optimasi bisnis depot gas! 📊
