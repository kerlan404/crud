# 🛍️ CRUD Ujian Akhir Semester

<div align="center">

![IShowSpeed Yeah](https://media1.tenor.com/m/J9WzCvxHZEsAAAAd/ishowspeed-ishowspeed-yeah-right.gif)

**Aplikasi CRUD Toko Online yang Sederhana dan Powerful** ✨

Dokumentasi lengkap tentang struktur website, instalasi, cara menjalankan, dan penggunaan aplikasi.

</div>

---

## 📋 Daftar Isi

- [🎯 Gambaran Umum](#-gambaran-umum)
- [📁 Struktur Folder](#-struktur-folder)
- [⚙️ Persyaratan](#️-persyaratan)
- [🗄️ Setup Database](#️-setup-database)
- [🚀 Menjalankan Website](#-menjalankan-website)
- [💡 Cara Menggunakan](#-cara-menggunakan)
- [📝 Catatan Tambahan](#-catatan-tambahan)

---

## 🎯 Gambaran Umum

Website ini adalah aplikasi CRUD yang powerful untuk toko online dengan fitur lengkap:

| Fitur | Deskripsi |
|-------|-----------|
| 🏠 **Halaman Depan** | Menampilkan daftar produk dengan pagination |
| 🔍 **Detail Produk** | Informasi lengkap produk dengan gambar |
| 🔐 **Autentikasi** | Sistem login & register yang aman |
| 👨‍💼 **Admin Panel** | Manajemen lengkap untuk admin |
| 👤 **Profil Pengguna** | Profil & riwayat pesanan pengguna |

---

## 📁 Struktur Folder

```
📦 crud/
├── 📄 index.php                 # Halaman utama (daftar produk)
├── 📄 detail.php                # Halaman detail produk
├── 📄 db_init.php               # Script inisialisasi database
├── 📄 database.sql              # Database schema & initial data
│
├── 🔐 auth/
│   ├── login.php                # Halaman login
│   ├── logout.php               # Proses logout
│   ├── register.php             # Halaman pendaftaran
│   └── loading.php              # Halaman loading/redirect
│
├── 👨‍💼 admin/
│   ├── dashboard.php            # Dashboard admin
│   ├── produk.php               # Manajemen produk
│   ├── kategori.php             # Manajemen kategori
│   ├── brand.php                # Manajemen brand
│   ├── transaksi.php            # Manajemen transaksi
│   ├── user.php                 # Manajemen pengguna
│   └── profil.php               # Profil admin
│
├── 🎨 components/
│   ├── header.php               # Header umum
│   ├── footer.php               # Footer umum
│   ├── admin_sidebar.php        # Sidebar admin
│   └── toast.php                # Notifikasi toast
│
├── ⚙️ config/
│   └── db.php                   # Konfigurasi database
│
├── 📦 assets/
│   ├── 🎨 css/
│   │   └── style.css            # Stylesheet utama
│   └── 📜 js/
│       └── app.js               # JavaScript frontend
│
├── 👤 user/
│   ├── profil.php               # Halaman profil pengguna
│   ├── detail_pesanan.php       # Detail pesanan
│   ├── riwayat.php              # Riwayat pembelian
│   └── beli.php                 # Proses pembelian
│
└── 📤 uploads/
    └── produk/                  # Gambar produk

```

---

## ⚙️ Persyaratan

Pastikan sistem Anda memenuhi persyaratan berikut:

| Persyaratan | Versi | Status |
|-------------|-------|--------|
| 🐘 **PHP** | 7.4+ | ✅ |
| 🗄️ **MySQL/MariaDB** | 5.7+ | ✅ |
| 🌐 **Web Server** | Apache/Built-in | ✅ |

> 💡 Tip: Gunakan built-in PHP server untuk pengembangan lokal

---

## 🗄️ Setup Database

<div align="center">

![Loading](https://media1.tenor.com/m/d7jsZ0yCYkoAAAAd/bronya-think-loading-gif.gif)

</div>

### Langkah-langkah Setup:

**1️⃣ Buat Database Baru**
```sql
CREATE DATABASE nama_database;
USE nama_database;
```

**2️⃣ Impor File SQL**
```bash
mysql -u root -p nama_database < database.sql
```

**3️⃣ Konfigurasi Koneksi Database**

Edit file `config/db.php` dan sesuaikan:

```php
<?php
define('DB_HOST', 'localhost');      // Host database
define('DB_NAME', 'nama_database');  // Nama database
define('DB_USER', 'root');           // Username
define('DB_PASS', '');               // Password
?>
```

---

## 🚀 Menjalankan Website

### Opsi 1: Built-in PHP Server (Recommended)

```bash
# Dari folder root project
cd crud/
php -S localhost:8000
```

### Opsi 2: Web Server (Apache/Nginx)

Tempatkan folder project di:
- **XAMPP**: `htdocs/crud/`
- **Nginx**: `/var/www/html/crud/`

---

### 📍 Akses Website

| Halaman | URL |
|---------|-----|
| 🏠 **Halaman Utama** | `http://localhost:8000/` |
| 🔐 **Login** | `http://localhost:8000/auth/login.php` |
| 📝 **Register** | `http://localhost:8000/auth/register.php` |
| 👨‍💼 **Admin Panel** | `http://localhost:8000/admin/dashboard.php` |

---

## 💡 Cara Menggunakan

### 👥 Sebagai Pengguna Biasa

```
1. 📝 Daftar akun          → auth/register.php
2. 🔐 Login                 → auth/login.php
3. 🛍️ Jelajahi produk      → index.php
4. 🔍 Lihat detail produk   → detail.php (klik produk)
5. 🛒 Proses pembelian      → user/beli.php
6. 👤 Lihat profil          → user/profil.php
7. 📦 Riwayat pesanan       → user/riwayat.php
```

**Fitur Pengguna:**
- ✅ Membuat akun baru
- ✅ Memperbarui profil
- ✅ Melihat daftar produk
- ✅ Membeli produk
- ✅ Melihat riwayat pesanan
- ✅ Logout akun

---

### 👨‍💼 Sebagai Admin

```
1. 🔐 Login sebagai admin      → auth/login.php
2. 📊 Akses panel admin        → admin/dashboard.php
3. 📦 Kelola produk            → admin/produk.php
4. 📂 Kelola kategori          → admin/kategori.php
5. 🏷️ Kelola brand             → admin/brand.php
6. 💳 Kelola transaksi         → admin/transaksi.php
7. 👥 Kelola pengguna          → admin/user.php
8. ⚙️ Edit profil admin         → admin/profil.php
```

**Fitur Admin:**
- ✅ Create, Read, Update, Delete produk
- ✅ Manajemen kategori & brand
- ✅ Lihat & verifikasi transaksi
- ✅ Manajemen pengguna
- ✅ Update profil admin
- ✅ Dashboard analytics

---

## 📝 Catatan Tambahan

### ⚠️ Penting

- 📤 Pastikan folder `uploads/produk/` **dapat ditulis** oleh web server
- 🔒 Berikan permission: `chmod 755 uploads/produk/`
- 🔧 Jika error koneksi database, periksa kembali `config/db.php`
- 🌐 Untuk XAMPP: akses via `http://localhost/<nama-folder>/`

### 🐛 Troubleshooting

| Masalah | Solusi |
|---------|--------|
| ❌ Error koneksi database | Periksa `config/db.php` & pastikan MySQL running |
| ❌ Gambar tidak terupload | Ubah permission folder `uploads/produk/` menjadi 755 |
| ❌ Halaman blank | Cek PHP version, harus PHP 7.4+ |
| ❌ Session error | Pastikan `session_start()` di file pertama |

---

## 🎨 Tech Stack

```
Frontend: HTML5 | CSS3 | JavaScript
Backend:  PHP 7.4+
Database: MySQL/MariaDB
Server:   Apache / Built-in PHP Server
```

---

## 📊 Statistik Repository

![Code Composition](https://img.shields.io/badge/PHP-32.2%25-blue?style=flat-square)
![Code Composition](https://img.shields.io/badge/Python-52.9%25-green?style=flat-square)
![Code Composition](https://img.shields.io/badge/JavaScript-7.8%25-yellow?style=flat-square)
![Code Composition](https://img.shields.io/badge/TypeScript-4.5%25-lightblue?style=flat-square)
![Code Composition](https://img.shields.io/badge/HTML-2.3%25-orange?style=flat-square)

---

<div align="center">

### 🎉 Selamat Menggunakan Aplikasi CRUD Kami!

**Made with ❤️ for Ujian Akhir Semester**

![Loading](https://media1.tenor.com/m/d7jsZ0yCYkoAAAAd/bronya-think-loading-gif.gif)

⭐ Jika project ini membantu, beri star ya! ⭐

</div>
