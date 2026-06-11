# CRUD Ujian Akhir Semester

Dokumentasi singkat tentang struktur website, instalasi, cara menjalankan, dan penggunaan aplikasi.

## 1. Gambaran Umum

Website ini adalah aplikasi CRUD sederhana untuk toko online yang dilengkapi dengan:
- Halaman depan produk
- Detail produk
- Autentikasi pengguna
- Panel admin untuk manajemen produk, kategori, brand, transaksi, dan pengguna
- Halaman profil dan riwayat pesanan untuk pengguna

## 2. Struktur Folder

- `index.php`
  - Halaman depan website yang menampilkan daftar produk.
- `detail.php`
  - Halaman detail produk.
- `db_init.php`
  - File inisialisasi database / contoh script untuk setup data awal.
- `database.sql`
  - File SQL untuk membuat tabel dan data awal.

- `auth/`
  - `login.php` - halaman login.
  - `logout.php` - proses logout.
  - `register.php` - halaman pendaftaran pengguna.
  - `loading.php` - halaman loading/redirect setelah login.

- `admin/`
  - `dashboard.php` - dashboard admin.
  - `produk.php` - halaman manajemen produk.
  - `kategori.php` - manajemen kategori.
  - `brand.php` - manajemen brand.
  - `transaksi.php` - manajemen transaksi.
  - `user.php` - manajemen pengguna.
  - `profil.php` - profil admin.

- `components/`
  - `header.php` - header umum.
  - `footer.php` - footer umum.
  - `admin_sidebar.php` - sidebar untuk halaman admin.
  - `toast.php` - tampilan notifikasi toast.

- `config/`
  - `db.php` - konfigurasi koneksi database.

- `assets/`
  - `css/style.css` - file stylesheet.
  - `js/app.js` - file JavaScript untuk interaksi frontend.

- `uploads/`
  - `produk/` - folder untuk menyimpan gambar produk.

- `user/`
  - `profil.php` - halaman profil pengguna.
  - `detail_pesanan.php` - detail pesanan pengguna.
  - `riwayat.php` - riwayat pembelian.
  - `beli.php` - halaman proses pembelian.

## 3. Persyaratan

Sebelum menjalankan aplikasi, pastikan Anda memiliki:
- PHP (versi 7.4+ atau lebih baru)
- Web server lokal atau built-in server PHP
- Database MySQL/MariaDB

## 4. Setup Database

1. Buat database baru di MySQL/MariaDB.
2. Impor file `database.sql` ke database tersebut.
3. Buka `config/db.php` dan sesuaikan pengaturan koneksi:
   - `DB_HOST`
   - `DB_NAME`
   - `DB_USER`
   - `DB_PASS`

## 5. Menjalankan Website

Dari folder root project, jalankan perintah berikut pada terminal:

```bash
php -S localhost:8000
```

Kemudian buka browser dan akses:

- `http://localhost:8000/` untuk halaman utama
- `http://localhost:8000/auth/login.php` untuk login
- `http://localhost:8000/auth/register.php` untuk registrasi

## 6. Cara Menggunakan

### Pengguna Biasa
- Daftar akun di `auth/register.php`
- Login di `auth/login.php`
- Jelajahi produk di `index.php`
- Buka detail produk via `detail.php`
- Proses pembelian melalui `user/beli.php`
- Lihat profil di `user/profil.php`
- Lihat riwayat pesanan di `user/riwayat.php`

### Admin
- Login sebagai admin via `auth/login.php`
- Akses panel admin di `admin/dashboard.php`
- Kelola produk di `admin/produk.php`
- Kelola kategori di `admin/kategori.php`
- Kelola brand di `admin/brand.php`
- Kelola transaksi di `admin/transaksi.php`
- Kelola pengguna di `admin/user.php`
- Ubah profil admin di `admin/profil.php`

## 7. Catatan Tambahan

- Pastikan folder `uploads/produk/` dapat ditulis oleh server untuk menyimpan gambar produk.
- Jika terjadi error koneksi database, periksa kembali konfigurasi di `config/db.php`.
- Jika Anda menggunakan environment lain (misalnya XAMPP), tempatkan seluruh folder project di `htdocs` dan akses melalui `http://localhost/<nama-folder>/`.

