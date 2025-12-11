# 📝 Panduan Form Stok Opname (UI Implementation)

## 🎯 Flow untuk Form Seperti Gambar Anda

### **Tampilan Form:**

```
┌─────────────────────────────────────────┐
│   Form Stok Opname                  [X] │
├─────────────────────────────────────────┤
│ Pilih Barang *                          │
│ [▼ -- Pilih Barang --            ]      │
│                                          │
│ Kode Barang    Nama Barang    Kapasitas │
│ [BRG-001    ] [Oxigen     ]  [6m3    ]  │
│                                          │
│ Tanggal Opname *                        │
│ [12/10/2025                      ]      │
│                                          │
│ ┌─ Stok Sistem ────────────────┐        │
│ │ Stok Sistem (Isi)  │ (Kosong)│        │
│ │ [120           ]   │ [50    ] │        │
│ └──────────────────────────────┘        │
│                                          │
│ ┌─ Stok Fisik (Input Hasil Opname) ─┐  │
│ │ Stok Fisik (Isi) * │ (Kosong) *   │  │
│ │ [115            ]  │ [55      ]    │  │
│ └──────────────────────────────────┘   │
│                                          │
│ Keterangan                              │
│ [Catatan hasil opname (opsional)...]   │
│                                          │
│         [Close]  [Simpan Koreksi Stok] │
└─────────────────────────────────────────┘
```

---

## 🔄 Step-by-Step Implementation

### **Step 1: Load Dropdown Barang**

Saat form dibuka, load semua barang untuk dropdown:

```javascript
// API Call
GET /api/stok-opname/current-stok

// Response
{
  "status": true,
  "data": [
    {
      "id_barang": 1,
      "kode_barang": "BRG-2025-0001",
      "nama_barang": "Oxigen",
      "kapasitas": "6m3",
      "stok_tabung_isi": 120,
      "stok_tabung_kosong": 50
    },
    {
      "id_barang": 2,
      "kode_barang": "BRG-2025-0002",
      "nama_barang": "Nitrogen",
      "kapasitas": "3m3",
      "stok_tabung_isi": 80,
      "stok_tabung_kosong": 20
    }
  ]
}

// Tampilkan di dropdown
<select id="pilih-barang" onchange="loadDetailBarang(this.value)">
  <option value="">-- Pilih Barang --</option>
  <option value="1">Oxigen - 6m3</option>
  <option value="2">Nitrogen - 3m3</option>
</select>
```

---

### **Step 2: User Pilih Barang → Auto Fill Form**

Ketika user pilih barang dari dropdown:

```javascript
async function loadDetailBarang(idBarang) {
  if (!idBarang) return;

  // API Call untuk get detail
  const response = await fetch(`/api/stok-opname/detail-barang/${idBarang}`);
  const result = await response.json();

  if (result.status) {
    const data = result.data;

    // Auto-fill form (fields ini READ-ONLY/DISABLED)
    document.getElementById('kode-barang').value = data.kode_barang;
    document.getElementById('nama-barang').value = data.nama_barang;
    document.getElementById('kapasitas').value = data.kapasitas;
    document.getElementById('stok-sistem-isi').value = data.stok_sistem_isi;
    document.getElementById('stok-sistem-kosong').value = data.stok_sistem_kosong;

    // Reset input fisik
    document.getElementById('stok-fisik-isi').value = '';
    document.getElementById('stok-fisik-kosong').value = '';
    document.getElementById('keterangan').value = '';

    // Show info opname terakhir (optional)
    if (data.last_opname) {
      showInfo(`Terakhir opname: ${data.last_opname.tanggal} oleh ${data.last_opname.created_by}`);
    }
  }
}

// API Response
{
  "status": true,
  "message": "Detail barang berhasil diambil",
  "data": {
    "id_barang": 1,
    "kode_barang": "BRG-2025-0001",
    "nama_barang": "Oxigen",
    "kapasitas": "6m3",
    "stok_sistem_isi": 120,        // ← Auto-fill ke form
    "stok_sistem_kosong": 50,      // ← Auto-fill ke form
    "last_opname": {
      "tanggal": "2025-12-09",
      "created_by": "Admin"
    }
  }
}
```

---

### **Step 3: User Input Stok Fisik → Hitung Selisih Real-time**

Saat user ketik di field "Stok Fisik", hitung selisih otomatis:

```javascript
function hitungSelisih() {
    // Ambil nilai dari form
    const stokSistemIsi =
        parseInt(document.getElementById("stok-sistem-isi").value) || 0;
    const stokSistemKosong =
        parseInt(document.getElementById("stok-sistem-kosong").value) || 0;
    const stokFisikIsi =
        parseInt(document.getElementById("stok-fisik-isi").value) || 0;
    const stokFisikKosong =
        parseInt(document.getElementById("stok-fisik-kosong").value) || 0;

    // Hitung selisih
    const selisihIsi = stokFisikIsi - stokSistemIsi;
    const selisihKosong = stokFisikKosong - stokSistemKosong;

    // Tampilkan di UI (optional - untuk preview)
    document.getElementById("preview-selisih-isi").textContent = selisihIsi;
    document.getElementById("preview-selisih-kosong").textContent =
        selisihKosong;

    // Beri warna indicator
    if (selisihIsi < 0) {
        document.getElementById("preview-selisih-isi").className =
            "text-danger"; // Merah (kurang)
    } else if (selisihIsi > 0) {
        document.getElementById("preview-selisih-isi").className =
            "text-success"; // Hijau (lebih)
    }
}

// Attach event listener
document
    .getElementById("stok-fisik-isi")
    .addEventListener("input", hitungSelisih);
document
    .getElementById("stok-fisik-kosong")
    .addEventListener("input", hitungSelisih);
```

---

### **Step 4: Submit Form**

Saat user klik "Simpan Koreksi Stok":

```javascript
async function submitKoreksiStok(event) {
    event.preventDefault();

    // Validasi
    const idBarang = document.getElementById("pilih-barang").value;
    const tanggalOpname = document.getElementById("tanggal-opname").value;
    const stokFisikIsi = parseInt(
        document.getElementById("stok-fisik-isi").value
    );
    const stokFisikKosong = parseInt(
        document.getElementById("stok-fisik-kosong").value
    );
    const keterangan = document.getElementById("keterangan").value;

    if (!idBarang) {
        alert("Pilih barang terlebih dahulu!");
        return;
    }

    if (!tanggalOpname) {
        alert("Tanggal opname wajib diisi!");
        return;
    }

    if (stokFisikIsi === "" || stokFisikIsi < 0) {
        alert("Stok fisik isi wajib diisi!");
        return;
    }

    if (stokFisikKosong === "" || stokFisikKosong < 0) {
        alert("Stok fisik kosong wajib diisi!");
        return;
    }

    // Prepare data
    const data = {
        tanggal_opname: tanggalOpname,
        corrections: [
            {
                id_barang: parseInt(idBarang),
                stok_isi_fisik: stokFisikIsi,
                stok_kosong_fisik: stokFisikKosong,
                keterangan: keterangan || null,
            },
        ],
    };

    // API Call
    try {
        const response = await fetch("/api/stok-opname/koreksi", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                Authorization: "Bearer " + getToken(),
            },
            body: JSON.stringify(data),
        });

        const result = await response.json();

        if (result.status) {
            alert("Koreksi stok berhasil disimpan!");

            // Reset form
            document.getElementById("form-opname").reset();

            // Refresh tabel laporan atau redirect
            window.location.href = "/mutasi-stok"; // atau refresh table
        } else {
            alert("Error: " + result.message);
        }
    } catch (error) {
        alert("Error: " + error.message);
    }
}
```

**Request Body:**

```json
POST /api/stok-opname/koreksi
Content-Type: application/json

{
  "tanggal_opname": "2025-12-10",
  "corrections": [
    {
      "id_barang": 1,
      "stok_isi_fisik": 115,
      "stok_kosong_fisik": 55,
      "keterangan": "Koreksi hasil stock opname fisik"
    }
  ]
}
```

**Response:**

```json
{
    "status": true,
    "message": "Stok opname berhasil dilakukan",
    "data": {
        "tanggal_opname": "2025-12-10",
        "total_koreksi": 1,
        "detail_koreksi": [
            {
                "id_opname": 5,
                "id_barang": 1,
                "nama_barang": "Oxigen",
                "stok_sistem": {
                    "isi": 120,
                    "kosong": 50
                },
                "stok_fisik": {
                    "isi": 115,
                    "kosong": 55
                },
                "selisih": {
                    "isi": -5,
                    "kosong": 5
                }
            }
        ]
    }
}
```

---

## 📊 Tampilan Tabel Laporan

Setelah submit, tampilkan di tabel:

```html
<table>
    <thead>
        <tr>
            <th>Aksi</th>
            <th>Nama Barang</th>
            <th>Kapasitas</th>
            <th>Isi Sistem</th>
            <th>Kosong Sistem</th>
            <th>Isi Fisik</th>
            <th>Kosong Fisik</th>
            <th>Selisih Isi</th>
            <th>Selisih Kosong</th>
            <th>Total Selisih</th>
            <th>Tanggal</th>
            <th>Keterangan</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>
                <button onclick="editOpname(5)">Edit</button>
                <button onclick="deleteOpname(5)">Hapus</button>
            </td>
            <td>Oxigen</td>
            <td>6m3</td>
            <td>120</td>
            <td>50</td>
            <td>115</td>
            <td>55</td>
            <td class="text-danger">-5</td>
            <td class="text-success">+5</td>
            <td>0</td>
            <td>10/12/2025</td>
            <td>Koreksi hasil stock opname fisik</td>
        </tr>
    </tbody>
</table>
```

Load data dari:

```javascript
GET /api/stok-opname/laporanstok?per_page=15&page=1
```

---

## 🎨 UI Tips

### **1. Indicator Warna Selisih**

```css
.selisih-kurang {
    color: #dc3545;
    font-weight: bold;
} /* Merah */
.selisih-lebih {
    color: #28a745;
    font-weight: bold;
} /* Hijau */
.selisih-sama {
    color: #6c757d;
} /* Abu-abu */
```

### **2. Preview Selisih (Optional)**

Tampilkan preview selisih sebelum submit:

```html
<div class="alert alert-info">
    <strong>Preview Selisih:</strong>
    <p>Tabung Isi: <span id="preview-selisih-isi">-</span></p>
    <p>Tabung Kosong: <span id="preview-selisih-kosong">-</span></p>
</div>
```

### **3. Konfirmasi Submit**

```javascript
if (
    !confirm(
        `Yakin simpan koreksi?\nSelisih Isi: ${selisihIsi}\nSelisih Kosong: ${selisihKosong}`
    )
) {
    return;
}
```

---

## ❓ FAQ

### **Q: Bagaimana jika user salah input dan ingin koreksi ulang?**

**A:** Ada 2 cara:

1. **Hapus opname terakhir** (DELETE `/api/stok-opname/hapus/{id_opname}`) → stok rollback → input ulang
2. **Input koreksi baru** → Sistem akan ambil stok terbaru dari master barang (yang sudah diupdate)

### **Q: Kenapa ada 2 record untuk barang yang sama?**

**A:** Ini NORMAL! Setiap koreksi = 1 record history. Berguna untuk audit trail. Laporan akan tampilkan semua koreksi.

### **Q: Bagaimana filter hanya lihat opname barang tertentu?**

**A:**

```javascript
GET /api/stok-opname/laporanstok?id_barang=1
```

### **Q: Bagaimana lihat history lengkap 1 barang?**

**A:**

```javascript
GET / api / stok - opname / history / 1;
```

---

## 📝 Checklist Implementation

-   [ ] Dropdown list barang dari `/current-stok`
-   [ ] Event `onChange` dropdown → call `/detail-barang/{id}`
-   [ ] Auto-fill form fields (disabled)
-   [ ] Input stok fisik dengan validasi
-   [ ] Hitung selisih real-time saat user ketik
-   [ ] Submit ke `/koreksi` dengan format yang benar
-   [ ] Tampilkan tabel laporan dari `/laporanstok`
-   [ ] Tombol hapus opname (jika salah input)
-   [ ] Filter by barang/tanggal di tabel
-   [ ] Indicator warna untuk selisih (merah/hijau)

---

## 🚀 Complete Example (Vue.js/React Style)

```javascript
// State
const formData = {
    pilihBarang: null,
    kodeBarang: "",
    namaBarang: "",
    kapasitas: "",
    tanggalOpname: getTodayDate(),
    stokSistemIsi: 0,
    stokSistemKosong: 0,
    stokFisikIsi: "",
    stokFisikKosong: "",
    keterangan: "",
};

// Computed
const selisihIsi = () => {
    return (formData.stokFisikIsi || 0) - formData.stokSistemIsi;
};

const selisihKosong = () => {
    return (formData.stokFisikKosong || 0) - formData.stokSistemKosong;
};

// Methods
async function onBarangChange(idBarang) {
    const response = await api.get(`/stok-opname/detail-barang/${idBarang}`);
    const data = response.data.data;

    formData.kodeBarang = data.kode_barang;
    formData.namaBarang = data.nama_barang;
    formData.kapasitas = data.kapasitas;
    formData.stokSistemIsi = data.stok_sistem_isi;
    formData.stokSistemKosong = data.stok_sistem_kosong;

    // Reset input
    formData.stokFisikIsi = "";
    formData.stokFisikKosong = "";
}

async function submitKoreksi() {
    const payload = {
        tanggal_opname: formData.tanggalOpname,
        corrections: [
            {
                id_barang: formData.pilihBarang,
                stok_isi_fisik: parseInt(formData.stokFisikIsi),
                stok_kosong_fisik: parseInt(formData.stokFisikKosong),
                keterangan: formData.keterangan,
            },
        ],
    };

    const response = await api.post("/stok-opname/koreksi", payload);

    if (response.data.status) {
        alert("Berhasil!");
        resetForm();
        refreshTable();
    }
}
```

---

**Selamat mengimplementasikan! Jika ada kendala, silakan tanya lagi.** 🚀
