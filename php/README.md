# Klinik Pratama — Backend PHP + MySQL

## Struktur File

```
klinik_pratama/
├── klinik_pratama.sql     ← Import ke MySQL (struktur + data)
├── config.php             ← Konfigurasi koneksi database
├── api.php                ← REST API JSON
└── index.php              ← Halaman admin (server-side PHP)
```

---

## Instalasi (XAMPP / Laragon / Hosting)

### 1. Import Database
- Buka **phpMyAdmin**
- Buat database baru: `klinik_pratama`
- Klik tab **Import** → pilih file `klinik_pratama.sql` → klik **Go**

### 2. Setting Koneksi
Edit file `config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'klinik_pratama');
define('DB_USER', 'root');   // sesuaikan
define('DB_PASS', '');       // sesuaikan
```

### 3. Letakkan File
Salin semua file ke folder:
- XAMPP: `C:/xampp/htdocs/klinik_pratama/`
- Laragon: `C:/laragon/www/klinik_pratama/`

### 4. Akses
| URL | Keterangan |
|-----|-----------|
| `http://localhost/klinik_pratama/index.php` | Halaman Admin |
| `http://localhost/klinik_pratama/api.php?resource=pasien&action=list` | REST API |

---

## Akun Login Default

| Username | Password | Role |
|----------|----------|------|
| admin | admin123 | Superadmin |
| staf | staf123 | Administrasi |
| perawat | perawat123 | Perawat |
| dokter1 | dokter123 | Dokter |
| kasir | kasir123 | Kasir |

> ⚠️ **Ganti semua password** sebelum digunakan di produksi!

---

## REST API

### Format Response
```json
{ "ok": true, "data": [...], "msg": "" }
```

### Endpoint Lengkap

| Method | URL | Keterangan |
|--------|-----|-----------|
| GET | `api.php?resource=dashboard` | Statistik dashboard |
| GET | `api.php?resource=pasien&action=list` | Daftar semua pasien |
| GET | `api.php?resource=pasien&action=list&q=budi` | Cari pasien |
| GET | `api.php?resource=pasien&action=get&id=p1` | Detail pasien |
| POST | `api.php?resource=pasien&action=create` | Tambah pasien |
| POST | `api.php?resource=pasien&action=update&id=p1` | Update pasien |
| POST | `api.php?resource=pasien&action=delete&id=p1` | Hapus pasien |
| POST | `api.php?resource=auth&action=login` | Login admin |

**Resource yang tersedia:**
`layanan`, `dokter`, `jadwal`, `pasien`, `pendaftaran`, `rekam_medis`, `obat`, `billing`, `pesan`, `admin_users`

### Contoh: Tambah Pasien (POST)
```json
POST api.php?resource=pasien&action=create
Body:
{
  "nama": "Andi Wijaya",
  "nik": "3271019876543210",
  "ttl": "1993-04-10",
  "jk": "Laki-laki",
  "telepon": "0812-9999-1111",
  "email": "andi@email.com",
  "alamat": "Jl. Sudirman No. 5, Jakarta"
}
```

### Contoh: Filter Pendaftaran by Tanggal
```
GET api.php?resource=pendaftaran&action=list&tanggal=2025-06-28
```

### Contoh: Login
```json
POST api.php?resource=auth&action=login
Body:
{ "username": "admin", "password": "admin123" }
```

---

## Struktur Database

| Tabel | Keterangan |
|-------|-----------|
| `layanan` | Poli / unit layanan klinik |
| `dokter` | Data dokter & spesialisasi |
| `jadwal` | Jadwal praktik dokter |
| `pasien` | Data pasien terdaftar |
| `pendaftaran` | Antrian & pendaftaran kunjungan |
| `rekam_medis` | Catatan medis per kunjungan |
| `obat` | Stok & harga obat |
| `billing` | Tagihan pasien |
| `billing_item` | Detail item tagihan |
| `pesan` | Pesan dari pengunjung website |
| `admin_users` | Akun admin & hak akses |

### Views Tersedia
| View | Keterangan |
|------|-----------|
| `v_pendapatan_harian` | Rekap pendapatan per hari |
| `v_kunjungan_dokter` | Total kunjungan per dokter |
| `v_rekam_medis_lengkap` | Rekam medis + data pasien & dokter |
| `v_obat_menipis` | Obat dengan stok < 50 |

---

## Menghubungkan Frontend HTML ke API

Di file HTML klinik, ganti fungsi `dbGetAll` agar fetch dari API:

```javascript
const API = 'http://localhost/klinik_pratama/api.php';

async function apiGet(resource, params = {}) {
  const qs = new URLSearchParams({ resource, action: 'list', ...params });
  const res = await fetch(`${API}?${qs}`);
  const json = await res.json();
  return json.data ?? [];
}

async function apiPost(resource, action, id, body) {
  const qs = new URLSearchParams({ resource, action });
  if (id) qs.set('id', id);
  const res = await fetch(`${API}?${qs}`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(body)
  });
  return res.json();
}

// Contoh pemakaian:
const pasien = await apiGet('pasien');
const cariPasien = await apiGet('pasien', { q: 'budi' });
await apiPost('pasien', 'create', null, { nama: 'Test', nik: '...' });
await apiPost('pasien', 'update', 'p1', { telepon: '0812-xxx' });
await apiPost('pasien', 'delete', 'p1', {});
```

---

## Keamanan Produksi

Sebelum deploy ke server publik, lakukan hal berikut:

1. **Hash password** — ganti plaintext dengan bcrypt di PHP:
   ```php
   // Saat simpan:
   $hash = password_hash($password, PASSWORD_BCRYPT);
   // Saat cek login:
   password_verify($inputPassword, $storedHash);
   ```

2. **Gunakan HTTPS** di server produksi

3. **Tambahkan autentikasi token** (JWT/session) untuk REST API

4. **Batasi akses** `api.php` dengan middleware login

5. **Nonaktifkan** tampilan error PHP di produksi:
   ```php
   ini_set('display_errors', 0);
   ```
