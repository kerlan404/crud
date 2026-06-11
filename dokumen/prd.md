# ZETA MOTORS
## PRODUCT REQUIREMENTS DOCUMENT

> **Website E-Commerce Otomotif | Platform Manajemen Produk & Transaksi**

---

| Field | Detail |
|---|---|
| Nama Produk | ZETA Motors — Website Otomotif |
| Versi Dokumen | v1.0 |
| Status | Draft — Untuk Review Internal |
| Product Manager | Tim Product ZETA |
| Tanggal Dibuat | 11 Juni 2026 |
| Target Platform | Web Application (Responsive) |
| Klasifikasi | CONFIDENTIAL |

---

## Daftar Isi

1. [Ringkasan Eksekutif](#1-ringkasan-eksekutif)
2. [Stakeholder & User Persona](#2-stakeholder--user-persona)
3. [Struktur Database & Entitas](#3-struktur-database--entitas)
4. [Sistem Autentikasi & Privilege](#4-sistem-autentikasi--privilege)
5. [Fitur Interface User](#5-fitur-interface-user)
6. [Fitur Interface Admin](#6-fitur-interface-admin)
7. [Panduan Desain UI](#7-panduan-desain-ui--terinspirasi-yamaha)
8. [Spesifikasi Teknis](#8-spesifikasi-teknis)
9. [Acceptance Criteria](#9-acceptance-criteria)
10. [Kebutuhan Non-Fungsional](#10-kebutuhan-non-fungsional)
11. [Timeline & Milestone](#11-timeline--milestone)
12. [Analisis Risiko](#12-analisis-risiko)
- [Appendix](#appendix--referensi--catatan)

---

## 1. RINGKASAN EKSEKUTIF

ZETA Motors adalah platform e-commerce otomotif berbasis web yang menyediakan pengalaman berbelanja produk kendaraan bermotor secara digital. Terinspirasi dari desain UI Yamaha Motor Indonesia, ZETA mengedepankan estetika premium, navigasi intuitif, dan sistem transaksi yang aman.

### 1.1 Visi Produk

Menjadi platform digital otomotif terpercaya di Indonesia yang menghubungkan konsumen dengan produk kendaraan bermotor berkualitas melalui pengalaman belanja yang modern, aman, dan efisien.

### 1.2 Tujuan Bisnis

- Digitalisasi proses penjualan produk otomotif brand ZETA
- Menyediakan sistem manajemen inventori dan transaksi terpusat
- Meningkatkan kepercayaan konsumen melalui payment gateway yang terverifikasi
- Memberikan kontrol penuh kepada admin dalam mengelola produk, user, dan transaksi

### 1.3 Scope Proyek

- **Frontend:** Website responsif dengan UI terinspirasi Yamaha Motor
- **Backend:** Sistem manajemen database dengan logika CRUD kompleks
- **Payment:** Integrasi payment gateway (GoPay, DANA, Transfer Bank)
- **Auth:** Sistem autentikasi dua peran (User & Admin)

---

## 2. STAKEHOLDER & USER PERSONA

### 2.1 Stakeholder

| Stakeholder | Peran | Kepentingan |
|---|---|---|
| Product Manager | Pemilik visi produk | Pastikan PRD sesuai business goals |
| Developer | Implementasi teknis | Spesifikasi teknis yang jelas |
| UI/UX Designer | Desain antarmuka | Design system & komponen |
| Admin ZETA | Operator harian | Tools manajemen yang efisien |
| End User | Pembeli produk | Pengalaman belanja yang nyaman |
| Finance Team | Rekonsiliasi keuangan | Laporan transaksi akurat |

### 2.2 User Persona

#### Persona 1: Admin ZETA

- Email: `admin@gmail.com` | Password: `admin123`
- Role: ADMINISTRATOR dengan full privilege
- Goals: Mengelola produk, memantau transaksi, mengatur user
- Pain Points: Butuh dashboard komprehensif dengan data real-time

#### Persona 2: User / Konsumen

- Email: `user@example.com` | Password: `example`
- Role: USER dengan akses terbatas
- Goals: Browse produk, melakukan pembelian, melacak pesanan
- Pain Points: Proses checkout yang panjang, ketidakpastian status pembayaran

---

## 3. STRUKTUR DATABASE & ENTITAS

Sistem ZETA Motors menggunakan relational database dengan 5 tabel utama yang saling berelasi untuk mendukung seluruh operasional platform.

### 3.1 Tabel: `users`

| Kolom | Tipe Data | Constraint | Keterangan |
|---|---|---|---|
| `id` | INT (PK) | AUTO_INCREMENT, NOT NULL | Primary key unik setiap user |
| `username` | VARCHAR(100) | NOT NULL, UNIQUE | Nama tampil user di platform |
| `email` | VARCHAR(255) | NOT NULL, UNIQUE | Email untuk login & notifikasi |
| `password` | VARCHAR(255) | NOT NULL | Password ter-hash (bcrypt) |
| `role` | ENUM | `'user','admin'` DEFAULT `'user'` | Menentukan hak akses sistem |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Waktu registrasi akun |

### 3.2 Tabel: `produk`

| Kolom | Tipe Data | Constraint | Keterangan |
|---|---|---|---|
| `kode_produk` | VARCHAR(20) (PK) | NOT NULL, UNIQUE | Kode unik produk (e.g. ZTA-001) |
| `nama_produk` | VARCHAR(255) | NOT NULL | Nama lengkap produk |
| `tipe_barang` | VARCHAR(100) | NOT NULL | Motor Sport, Matic, Trail, dll |
| `kategori_id` | INT (FK) | REFERENCES kategori(id) | Relasi ke tabel kategori |
| `harga` | DECIMAL(15,2) | NOT NULL | Harga satuan dalam rupiah |
| `stok` | INT | DEFAULT 0, CHECK >= 0 | Jumlah stok tersedia |
| `gambar` | VARCHAR(500) | NULLABLE | Path file gambar produk |
| `brand_id` | INT (FK) | REFERENCES brand(id) | Relasi ke tabel brand |

### 3.3 Tabel: `pembelian`

| Kolom | Tipe Data | Constraint | Keterangan |
|---|---|---|---|
| `id_pembelian` | INT (PK) | AUTO_INCREMENT, NOT NULL | Primary key transaksi |
| `user_id` | INT (FK) | REFERENCES users(id) | Relasi ke user pembeli |
| `kode_produk` | VARCHAR(20) (FK) | REFERENCES produk(kode_produk) | Produk yang dibeli |
| `jumlah` | INT | NOT NULL, CHECK > 0 | Jumlah unit yang dibeli |
| `total_bayar` | DECIMAL(15,2) | NOT NULL | Total harga transaksi |
| `tanggal_transaksi` | TIMESTAMP | DEFAULT NOW() | Waktu transaksi dilakukan |
| `status` | ENUM | `'pending','paid','confirmed','cancelled'` | Status pembayaran & konfirmasi |

### 3.4 Tabel: `kategori`

| Kolom | Tipe Data | Constraint | Keterangan |
|---|---|---|---|
| `id` | INT (PK) | AUTO_INCREMENT, NOT NULL | Primary key kategori |
| `nama_kategori` | VARCHAR(100) | NOT NULL, UNIQUE | Nama kategori produk otomotif |

### 3.5 Tabel: `brand`

| Kolom | Tipe Data | Constraint | Keterangan |
|---|---|---|---|
| `id` | INT (PK) | AUTO_INCREMENT, NOT NULL | Primary key brand |
| `nama_brand` | VARCHAR(100) | NOT NULL, UNIQUE | Nama brand kendaraan |

### 3.6 Entity Relationship Diagram (Deskripsi)

```
users        (1) ──────< (N) pembelian
produk       (1) ──────< (N) pembelian
kategori     (1) ──────< (N) produk
brand        (1) ──────< (N) produk
```

- **users (1) ←→ (N) pembelian** — Satu user bisa memiliki banyak transaksi
- **produk (1) ←→ (N) pembelian** — Satu produk bisa muncul di banyak transaksi
- **kategori (1) ←→ (N) produk** — Satu kategori memiliki banyak produk
- **brand (1) ←→ (N) produk** — Satu brand memiliki banyak produk

---

## 4. SISTEM AUTENTIKASI & PRIVILEGE

### 4.1 Mekanisme Login Dua Arah

Platform ZETA mengimplementasikan sistem login bifurkasi dimana satu form login mengakomodasi dua jenis pengguna berdasarkan field `role` pada tabel `users`.

| Aspek | User | Admin |
|---|---|---|
| Kredensial Default | user@example.com / example | admin@gmail.com / admin123 |
| Role di Database | `role = 'user'` | `role = 'admin'` |
| Redirect Setelah Login | `/user/dashboard` | `/admin/dashboard` |
| Session Management | JWT Token (24 jam) | JWT Token (8 jam) |
| Password Hashing | bcrypt (salt rounds: 12) | bcrypt (salt rounds: 12) |
| Brute Force Protection | Lockout 5x gagal (15 menit) | Lockout 3x gagal (30 menit) |

### 4.2 Privilege Matrix

| Fitur / Aksi | User | Admin |
|---|:---:|:---:|
| Login / Logout | ✅ | ✅ |
| Lihat Katalog Produk | ✅ | ✅ |
| Detail Produk | ✅ | ✅ |
| Melakukan Pembelian | ✅ | ❌ |
| Lihat Riwayat Transaksi Sendiri | ✅ | ❌ |
| Melakukan Pembayaran | ✅ | ❌ |
| Edit Profile Sendiri | ✅ | ✅ |
| Dashboard Analytics | ❌ | ✅ |
| CRUD Produk | ❌ | ✅ |
| CRUD Kategori & Brand | ❌ | ✅ |
| Lihat Semua User | ❌ | ✅ |
| Ban / Unban User | ❌ | ✅ |
| Konfirmasi Pembayaran | ❌ | ✅ |
| Lihat Semua Transaksi | ❌ | ✅ |
| Upload Gambar Produk | ❌ | ✅ |

---

## 5. FITUR INTERFACE USER

### 5.1 Homepage / Katalog

- Hero section dengan banner produk unggulan ZETA (animasi slideshow)
- Grid produk dengan filter: kategori, brand, range harga, tipe barang
- Search bar dengan autocomplete
- Section 'Produk Terbaru' dan 'Produk Terlaris'
- Footer dengan informasi kontak, sosial media, dan navigasi cepat

### 5.2 Halaman Detail Produk

- Gambar produk dengan zoom-on-hover (seperti Yamaha Motor)
- Informasi lengkap: nama, kode, kategori, brand, tipe, harga, stok
- Tombol **'Beli Sekarang'** — hanya aktif jika stok > 0
- Badge **'Stok Habis'** jika stok = 0
- Breadcrumb navigasi: `Beranda > Kategori > Nama Produk`

### 5.3 Form Transaksi Pembelian

Alur pembelian user mengikuti flow berikut:

1. User klik 'Beli Sekarang' dari halaman detail produk
2. Sistem menampilkan form pembelian: jumlah unit, ringkasan produk, total harga kalkulasi otomatis
3. User memilih metode pembayaran: GoPay / DANA / Transfer Bank
4. User submit — sistem membuat record pembelian dengan status `'pending'`
5. Sistem redirect ke halaman instruksi pembayaran sesuai metode
6. User melakukan pembayaran di luar platform (GoPay app, DANA app, ATM/mBanking)
7. Admin menerima notifikasi pembayaran masuk dan melakukan konfirmasi
8. Status transaksi berubah menjadi `'paid'` → `'confirmed'`
9. User menerima notifikasi email konfirmasi pesanan

### 5.4 Halaman Riwayat & Detail Pesanan User

- List semua transaksi milik user yang login
- Kolom: ID Pesanan, Nama Produk, Jumlah, Total Bayar, Tanggal, Status
- Badge status berwarna: Pending (kuning), Paid (biru), Confirmed (hijau), Cancelled (merah)
- Klik per baris → halaman detail pesanan lengkap
- Detail pesanan: info produk, metode bayar, instruksi pembayaran, status tracking

### 5.5 Payment Gateway — Metode Pembayaran

| Metode | Provider | Instruksi | Kode Unik |
|---|---|---|---|
| GoPay | Gojek Ecosystem | Scan QR Code / Nomor GoPay tujuan | Ya — 3 digit akhir unik per transaksi |
| DANA | DANA Indonesia | Transfer ke nomor DANA tujuan | Ya — 3 digit akhir unik per transaksi |
| Bank Transfer | BCA / BNI / Mandiri | Transfer ke nomor rekening virtual | Virtual Account unik per transaksi |

> _Catatan: Sistem membuat kode unik per transaksi untuk memudahkan rekonsiliasi admin. Pembayaran tidak langsung ter-konfirmasi otomatis — wajib melalui konfirmasi manual admin sebagai kontrol keamanan._

---

## 6. FITUR INTERFACE ADMIN

### 6.1 Dashboard Admin

Halaman utama admin menampilkan ringkasan operasional platform secara real-time.

#### 6.1.1 Widget / KPI Cards

| Widget | Data Ditampilkan | Update |
|---|---|---|
| Total Pengguna | Jumlah akun terdaftar (aktif & banned) | Real-time |
| User Login Hari Ini | Jumlah unique login session hari ini | Real-time |
| Total Produk | Jumlah produk di database | Real-time |
| Stok Menipis | Produk dengan stok < 5 unit | Real-time |
| Transaksi Pending | Transaksi menunggu konfirmasi bayar | Real-time |
| Revenue Bulan Ini | Total transaksi 'confirmed' bulan berjalan | Harian |

#### 6.1.2 Tabel Data Terbaru

- Tabel '5 Transaksi Terbaru' dengan status dan aksi cepat konfirmasi
- Tabel '5 User Terbaru Registrasi'
- Grafik transaksi 30 hari terakhir (line chart)

### 6.2 Manajemen Produk

Admin memiliki full CRUD pada data produk dengan fitur upload gambar menggunakan logika Multer.

#### 6.2.1 Daftar Produk

- Tabel produk dengan kolom: Gambar Thumbnail, Kode, Nama, Kategori, Brand, Harga, Stok, Tipe, Aksi
- Filter: Kategori, Brand, Tipe Barang, Range Stok
- Search by nama produk atau kode
- Pagination: 10/25/50 item per halaman
- Aksi per baris: Edit (ikon pensil), Hapus (ikon tempat sampah + konfirmasi modal)

#### 6.2.2 Form Tambah / Edit Produk

- Field wajib: Kode Produk, Nama Produk, Tipe Barang, Kategori (dropdown), Harga, Stok, Brand (dropdown)
- **Upload Gambar** menggunakan Multer middleware:
  - Format diterima: JPG, PNG, WEBP (max 2MB)
  - Preview gambar sebelum disimpan
  - Thumbnail auto-generate di server
  - Jika edit produk, gambar lama tetap tampil; ganti opsional
- Validasi frontend & backend wajib

#### 6.2.3 Logika Multer Upload Gambar

Implementasi logika upload gambar menggunakan library Multer pada Node.js/Express:

- **Storage:** Disk storage ke folder `/public/uploads/produk/`
- **Filename:** `[kode_produk]_[timestamp].[ext]` — mencegah duplikasi
- **File filter:** validasi MIME type di server (`image/jpeg`, `image/png`, `image/webp`)
- **Size limit:** maxSize 2MB dikonfigurasi di Multer options
- **Error handling:** jika format/ukuran tidak sesuai, response error 400 dengan pesan deskriptif
- **Cleanup:** jika update produk, file gambar lama dihapus dari disk otomatis

```javascript
// Contoh konfigurasi Multer
const storage = multer.diskStorage({
  destination: (req, file, cb) => cb(null, '/public/uploads/produk/'),
  filename: (req, file, cb) => {
    const ext = path.extname(file.originalname);
    cb(null, `${req.body.kode_produk}_${Date.now()}${ext}`);
  }
});

const fileFilter = (req, file, cb) => {
  const allowed = ['image/jpeg', 'image/png', 'image/webp'];
  allowed.includes(file.mimetype) ? cb(null, true) : cb(new Error('Format file tidak didukung'), false);
};

const upload = multer({ storage, fileFilter, limits: { fileSize: 2 * 1024 * 1024 } });
```

### 6.3 Manajemen Bahan / Produk Pendukung

Admin dapat mengelola master data pendukung yang digunakan pada form produk:

| Master Data | Operasi | Validasi |
|---|---|---|
| Kategori Produk | Create, Read, Update, Delete | Nama kategori unik, max 100 karakter |
| Brand Kendaraan | Create, Read, Update, Delete | Nama brand unik, max 100 karakter |
| Tipe Barang | Managed via produk form | Konsistensi input, opsional enum |

### 6.4 Manajemen User

#### 6.4.1 Daftar User

- Tabel user: ID, Username, Email, Role, Status (Aktif/Banned), Tanggal Daftar
- Filter: Role (User/Admin), Status (Aktif/Banned)
- Search by username atau email
- Aksi per baris: Lihat Detail, Ban/Unban User

#### 6.4.2 Detail User

- Informasi akun: ID, Username, Email, Role, Status, Tanggal Registrasi
- Riwayat transaksi user tersebut (5 terbaru + link lihat semua)
- Tombol **Ban User:** mengubah status user + blokir login
- Tombol **Unban User:** memulihkan akses login

### 6.5 Manajemen Transaksi

- Tabel semua transaksi: ID, User, Produk, Jumlah, Total, Metode Bayar, Tanggal, Status
- Filter: Status, Metode Bayar, Range Tanggal
- Aksi: Konfirmasi Pembayaran (`pending` → `confirmed`), Batalkan Transaksi
- Modal konfirmasi sebelum aksi destruktif
- Export data transaksi ke CSV/Excel

### 6.6 Setting / Profil Admin

- Edit informasi profil: Username, Email
- Ganti password dengan verifikasi password lama
- Upload foto profil admin

---

## 7. PANDUAN DESAIN UI — TERINSPIRASI YAMAHA

### 7.1 Design Philosophy

UI ZETA Motors mengadopsi pendekatan desain **"Premium Automotive Digital"** yang terinspirasi dari website Yamaha Motor Indonesia, dengan karakteristik:

- **Bold typography** dengan kontras tinggi — kesan kuat dan bertenaga
- **Color palette** berbasis biru gelap navy + aksen merah — identitas otomotif premium
- **Gambar full-bleed** pada hero section — showcase produk yang impresif
- **Grid layout** terstruktur untuk katalog produk — browsing yang efisien
- **Navigasi sticky** dengan mega-menu — akses cepat ke semua kategori

### 7.2 Design Tokens

| Token | Nilai | Penggunaan |
|---|---|---|
| Primary Color | `#003087` (Navy Blue) | Header, CTA button, heading utama |
| Secondary Color | `#CC0000` (ZETA Red) | Aksen, badge status, hover state |
| Background | `#FFFFFF` / `#F8F9FA` | Background konten utama |
| Text Primary | `#1A1A1A` | Body text, judul konten |
| Text Secondary | `#6C757D` | Subtitle, label, placeholder |
| Border Color | `#D0D5DD` | Garis pemisah, border card |
| Font Family | Roboto / Inter (sans-serif) | Seluruh teks platform |
| Font Size Body | 14px / 16px | Konten umum |
| Font Size H1 | 36px – 48px | Hero section, page title |
| Border Radius | 4px / 8px | Card, button, input field |
| Shadow | `0 2px 8px rgba(0,0,0,0.12)` | Card hover, dropdown, modal |

### 7.3 Komponen UI Utama

#### 7.3.1 Navigation Bar (Desktop)

- Logo ZETA di kiri — nama brand bold
- Menu utama: Beranda | Produk | Kategori | Brand | Tentang | Kontak
- Kanan: ikon profil + tombol Login/Logout
- Sticky on scroll dengan background navy solid
- Mobile: hamburger menu dengan slide-out drawer

#### 7.3.2 Product Card

- Thumbnail gambar 300×220px dengan `object-fit: cover`
- Badge kategori di pojok kiri atas (pill style)
- Nama produk bold, max 2 baris dengan ellipsis
- Harga menonjol dalam ZETA Red
- Stok info: 'Tersedia X unit' atau badge 'Stok Habis'
- Tombol 'Lihat Detail' — border button, hover fill navy

#### 7.3.3 Form Elements

- Input field: border 1px, radius 4px, focus ring navy 2px
- Dropdown: styled custom select dengan chevron ikon
- Button Primary: background navy, text putih, hover darken 10%
- Button Danger: background merah untuk aksi destruktif
- Error state: border merah + pesan error di bawah field

#### 7.3.4 Status Badges

| Status | Warna Background | Warna Text | Konteks |
|---|---|---|---|
| Pending | `#FFF3CD` | `#856404` | Menunggu pembayaran |
| Paid | `#CCE5FF` | `#004085` | Pembayaran diterima, belum dikonfirmasi |
| Confirmed | `#D4EDDA` | `#155724` | Admin sudah konfirmasi |
| Cancelled | `#F8D7DA` | `#721C24` | Transaksi dibatalkan |
| Banned | `#F8D7DA` | `#721C24` | User diblokir admin |
| Active | `#D4EDDA` | `#155724` | User aktif normal |

---

## 8. SPESIFIKASI TEKNIS

### 8.1 Tech Stack Rekomendasi

| Layer | Teknologi | Keterangan |
|---|---|---|
| Frontend | HTML5, CSS3, JavaScript / React.js | Komponen reusable, responsive |
| Backend | Node.js + Express.js | REST API, middleware Multer |
| Database | MySQL / PostgreSQL | Relational, mendukung FK & constraint |
| Authentication | JWT (JSON Web Token) + bcrypt | Stateless auth, password hashing |
| File Upload | Multer (Node.js middleware) | Disk storage, validasi file |
| Payment | Payment Gateway Manual | GoPay, DANA, Virtual Account bank |
| Hosting | VPS / Cloud (AWS/GCP/Vercel) | Fleksibel sesuai budget |

### 8.2 API Endpoints Utama

| Method | Endpoint | Auth | Deskripsi |
|---|---|---|---|
| `POST` | `/api/auth/login` | Public | Login user/admin |
| `POST` | `/api/auth/logout` | User/Admin | Logout session |
| `GET` | `/api/produk` | Public | List semua produk (dengan filter) |
| `GET` | `/api/produk/:kode` | Public | Detail satu produk |
| `POST` | `/api/produk` | Admin | Tambah produk baru + upload gambar |
| `PUT` | `/api/produk/:kode` | Admin | Update produk + opsional gambar baru |
| `DELETE` | `/api/produk/:kode` | Admin | Hapus produk |
| `GET` | `/api/pembelian` | Admin | Semua transaksi (semua user) |
| `GET` | `/api/pembelian/me` | User | Transaksi milik user yang login |
| `POST` | `/api/pembelian` | User | Buat transaksi baru |
| `PUT` | `/api/pembelian/:id/confirm` | Admin | Konfirmasi pembayaran |
| `GET` | `/api/users` | Admin | Semua data user |
| `PUT` | `/api/users/:id/ban` | Admin | Ban user |
| `PUT` | `/api/users/:id/unban` | Admin | Unban user |
| `GET` | `/api/dashboard/stats` | Admin | Data statistik dashboard |

### 8.3 Logika Bisnis Kritis

#### 8.3.1 Kalkulasi Total Bayar

- `total_bayar = harga produk × jumlah yang dibeli`
- Kalkulasi dilakukan di **backend (server-side)** — tidak bergantung input client
- Validasi: jumlah tidak boleh melebihi stok tersedia

#### 8.3.2 Manajemen Stok

- Saat transaksi dibuat (status: `pending`): stok **BELUM** dikurangi
- Saat admin konfirmasi (status: `confirmed`): stok **dikurangi** sejumlah `jumlah` beli
- Saat transaksi dibatalkan: jika stok sudah dikurangi, kembalikan stok
- Race condition protection: gunakan database transaction / optimistic locking

#### 8.3.3 Status Flow Transaksi

```
pending ──► paid ──► confirmed
   │                    
   └──────────────────► cancelled
```

- `pending` → `paid`: update manual oleh user (upload bukti) ATAU otomatis via webhook
- `paid` → `confirmed`: konfirmasi admin setelah verifikasi pembayaran
- `pending`/`paid` → `cancelled`: admin dapat membatalkan; user hanya bisa cancel status `pending`

---

## 9. ACCEPTANCE CRITERIA

### 9.1 Authentication

- **AC-01:** User dengan role `'user'` dapat login dan diarahkan ke halaman user
- **AC-02:** User dengan role `'admin'` dapat login dan diarahkan ke dashboard admin
- **AC-03:** User yang di-ban tidak dapat login (pesan error: _"Akun Anda diblokir"_)
- **AC-04:** Login gagal 5x berturut (user) / 3x (admin) → akun terkunci sementara
- **AC-05:** Akses halaman admin tanpa login → redirect ke halaman login

### 9.2 Produk & Upload Gambar

- **AC-06:** Admin dapat menambah produk dengan gambar format JPG/PNG/WEBP max 2MB
- **AC-07:** Upload file non-gambar → error 400 dengan pesan jelas
- **AC-08:** Upload file > 2MB → error 413 dengan pesan jelas
- **AC-09:** Gambar tersimpan di server dengan nama unik (tidak bisa duplikat)
- **AC-10:** Edit produk tanpa ganti gambar → gambar lama tetap dipertahankan

### 9.3 Transaksi & Payment

- **AC-11:** User dapat melakukan pembelian dengan memilih metode: GoPay/DANA/Bank
- **AC-12:** Transaksi baru tersimpan dengan status `'pending'`
- **AC-13:** Admin dapat melihat semua transaksi dengan filter status
- **AC-14:** Admin dapat mengkonfirmasi pembayaran → status berubah menjadi `'confirmed'`
- **AC-15:** Stok produk berkurang hanya setelah admin konfirmasi
- **AC-16:** User dapat melihat detail dan status semua pesanannya sendiri

### 9.4 Manajemen User

- **AC-17:** Admin dapat melihat daftar semua user beserta status aktif/banned
- **AC-18:** Admin dapat ban user → user tidak bisa login
- **AC-19:** Admin dapat unban user → akses pulih
- **AC-20:** Admin dapat melihat riwayat transaksi per user

---

## 10. KEBUTUHAN NON-FUNGSIONAL

| Kategori | Requirement | Target |
|---|---|---|
| Performance | Waktu load halaman pertama (LCP) | < 3 detik |
| Performance | API response time | < 500ms untuk 95% request |
| Availability | Uptime sistem | >= 99% (bulanan) |
| Security | Password storage | bcrypt, salt rounds >= 10 |
| Security | SQL Injection Prevention | Parameterized queries / ORM |
| Security | XSS Prevention | Input sanitization, Content Security Policy |
| Security | CSRF Protection | CSRF token pada form |
| Scalability | Concurrent users | Support 100 concurrent users |
| Mobile | Responsive breakpoints | Mobile 320px, Tablet 768px, Desktop 1280px |
| Browser | Support browser | Chrome, Firefox, Safari, Edge (versi 2 terakhir) |
| File Storage | Max ukuran gambar produk | 2MB per file, total storage terkelola |

---

## 11. TIMELINE & MILESTONE

| Sprint | Durasi | Deliverable | Priority |
|---|---|---|---|
| Sprint 0 | 1 minggu | Setup project: DB schema, auth system, folder structure | 🔴 Critical |
| Sprint 1 | 2 minggu | Authentication (login/register/logout), session management | 🔴 Critical |
| Sprint 2 | 2 minggu | CRUD Produk + upload gambar Multer, CRUD Kategori & Brand | 🟠 High |
| Sprint 3 | 2 minggu | Katalog user, detail produk, form pembelian, payment flow | 🟠 High |
| Sprint 4 | 2 minggu | Dashboard admin, manajemen user (ban/unban), manajemen transaksi | 🟠 High |
| Sprint 5 | 1 minggu | Riwayat pesanan user, detail pesanan, konfirmasi pembayaran admin | 🟡 Medium |
| Sprint 6 | 1 minggu | UI polish (Yamaha-inspired), responsif, setting profil admin/user | 🟡 Medium |
| Sprint 7 | 1 minggu | QA testing, bug fixing, deployment, dokumentasi teknis final | 🟢 Low |

**Total estimasi development: 12–13 minggu (±3 bulan)**

---

## 12. ANALISIS RISIKO

| Risiko | Probabilitas | Dampak | Mitigasi |
|---|---|---|---|
| Pembayaran tidak bisa diverifikasi otomatis | Tinggi | Medium | Gunakan kode unik 3 digit + instruksi jelas untuk user |
| Gambar produk ukuran besar memperlambat halaman | Medium | Medium | Resize & compress gambar di server saat upload |
| Race condition pada pengurangan stok | Medium | Tinggi | Gunakan database transaction atomic |
| Admin lupa konfirmasi pembayaran | Tinggi | Tinggi | Notifikasi email + dashboard alert untuk pending payment |
| User mencoba akses halaman admin | Rendah | Tinggi | Middleware auth guard + role check di setiap route admin |
| Data user bocor (keamanan) | Rendah | Kritis | HTTPS, bcrypt, parameterized query, validasi input ketat |

---

## APPENDIX — REFERENSI & CATATAN

### A. Kredensial Demo

| Role | Email | Password | Akses |
|---|---|---|---|
| User | `user@example.com` | `example` | Katalog, pembelian, riwayat pesanan |
| Admin | `admin@gmail.com` | `admin123` | Full access — dashboard, CRUD, konfirmasi |

> ⚠️ **PERINGATAN:** Kredensial di atas hanya untuk lingkungan development/demo. **Wajib diganti** sebelum deployment ke production.

### B. Referensi Desain

- **Yamaha Motor Indonesia** (yamaha-motor.co.id) — inspirasi layout, navigasi, dan product showcase
- **Material Design 3** — komponen form dan interaksi
- **WCAG 2.1 AA** — standar aksesibilitas minimum

### C. Glossary

| Istilah | Definisi |
|---|---|
| PRD | Product Requirements Document — dokumen spesifikasi kebutuhan produk |
| CRUD | Create, Read, Update, Delete — operasi dasar pada database |
| Multer | Middleware Node.js untuk menangani multipart/form-data (upload file) |
| JWT | JSON Web Token — standar token untuk autentikasi stateless |
| Payment Gateway | Sistem perantara yang mengelola alur dan validasi pembayaran |
| Virtual Account | Nomor rekening sementara unik per transaksi untuk bank transfer |
| Privilege | Hak akses spesifik yang diberikan kepada role tertentu |
| bcrypt | Algoritma hashing password yang aman dengan salt otomatis |
| Stok | Jumlah unit produk yang tersedia untuk dijual |
| Status Transaksi | Tahapan kondisi transaksi: `pending` → `paid` → `confirmed` / `cancelled` |

---

<div align="center">

— END OF DOCUMENT —

*ZETA Motors PRD v1.0 | Dokumen ini bersifat CONFIDENTIAL*

</div>