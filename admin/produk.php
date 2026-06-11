<?php
require_once '../config/db.php';
require_once '../components/toast.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Access Control
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

$error_msg = '';

// Ensure upload directory exists
$upload_dir = '../uploads/produk/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $kode_produk = trim($_POST['kode_produk']);
        $nama_produk = trim($_POST['nama_produk']);
        $tipe_barang = trim($_POST['tipe_barang']);
        $kategori_id = (int)$_POST['kategori_id'];
        $brand_id = (int)$_POST['brand_id'];
        $harga = (float)$_POST['harga'];
        $stok = (int)$_POST['stok'];
        
        $gambar_file = $_FILES['gambar'] ?? null;
        $gambar_name = $_POST['old_gambar'] ?? null; // For updates

        // Image upload handling (equivalent to Multer constraints)
        if ($gambar_file && $gambar_file['error'] === UPLOAD_ERR_OK) {
            $max_size = 2 * 1024 * 1024; // 2MB
            $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
            
            $file_info = getimagesize($gambar_file['tmp_name']);
            $mime = $file_info['mime'] ?? '';

            if ($gambar_file['size'] > $max_size) {
                $error_msg = 'Ukuran gambar melebihi batas 2MB.';
            } elseif (!in_array($mime, $allowed_types)) {
                $error_msg = 'Format gambar tidak didukung (Hanya JPG, PNG, WEBP).';
            } else {
                // Generate unique filename: [kode_produk]_[timestamp].[ext]
                $ext = pathinfo($gambar_file['name'], PATHINFO_EXTENSION);
                $new_filename = $kode_produk . '_' . time() . '.' . $ext;
                $dest_path = $upload_dir . $new_filename;

                if (move_uploaded_file($gambar_file['tmp_name'], $dest_path)) {
                    // Cleanup old image if updating
                    if ($action === 'update' && !empty($gambar_name) && file_exists($upload_dir . $gambar_name)) {
                        unlink($upload_dir . $gambar_name);
                    }
                    $gambar_name = $new_filename;
                } else {
                    $error_msg = 'Gagal menyimpan gambar di server.';
                }
            }
        }

        // If no errors, proceed to database query
        if (empty($error_msg)) {
            if ($action === 'create') {
                try {
                    // Check duplicate kode_produk
                    $chk = $pdo->prepare("SELECT kode_produk FROM produk WHERE kode_produk = ?");
                    $chk->execute([$kode_produk]);
                    if ($chk->fetch()) {
                        $error_msg = 'Kode produk sudah digunakan.';
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO produk (kode_produk, nama_produk, tipe_barang, kategori_id, harga, stok, gambar, brand_id) 
                                                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$kode_produk, $nama_produk, $tipe_barang, $kategori_id, $harga, $stok, $gambar_name, $brand_id]);
                        set_toast('success', 'Produk berhasil ditambahkan.');
                        header('Location: produk.php');
                        exit;
                    }
                } catch (PDOException $e) {
                    $error_msg = 'Gagal menyimpan produk: ' . $e->getMessage();
                }
            } else {
                // Update
                try {
                    $stmt = $pdo->prepare("UPDATE produk SET nama_produk = ?, tipe_barang = ?, kategori_id = ?, harga = ?, stok = ?, gambar = ?, brand_id = ? 
                                            WHERE kode_produk = ?");
                    $stmt->execute([$nama_produk, $tipe_barang, $kategori_id, $harga, $stok, $gambar_name, $brand_id, $kode_produk]);
                    set_toast('success', 'Produk berhasil diperbarui.');
                    header('Location: produk.php');
                    exit;
                } catch (PDOException $e) {
                    $error_msg = 'Gagal memperbarui produk: ' . $e->getMessage();
                }
            }
        }
    }

    if ($action === 'delete') {
        $kode_produk = trim($_POST['kode_produk']);
        if (!empty($kode_produk)) {
            try {
                // Fetch image to delete
                $stmt = $pdo->prepare("SELECT gambar FROM produk WHERE kode_produk = ?");
                $stmt->execute([$kode_produk]);
                $prod = $stmt->fetch();

                $stmt = $pdo->prepare("DELETE FROM produk WHERE kode_produk = ?");
                $stmt->execute([$kode_produk]);

                // Cleanup file
                if ($prod && !empty($prod['gambar']) && file_exists($upload_dir . $prod['gambar'])) {
                    unlink($upload_dir . $prod['gambar']);
                }

                set_toast('success', 'Produk berhasil dihapus.');
                header('Location: produk.php');
                exit;
            } catch (PDOException $e) {
                $error_msg = 'Gagal menghapus produk. Produk mungkin telah digunakan dalam transaksi.';
            }
        }
    }
}

// Fetch lists for dropdowns
$categories = $pdo->query("SELECT * FROM kategori ORDER BY nama_kategori ASC")->fetchAll();
$brands = $pdo->query("SELECT * FROM brand ORDER BY nama_brand ASC")->fetchAll();

// Fetch products list
$products = $pdo->query("SELECT p.*, k.nama_kategori, b.nama_brand 
                         FROM produk p 
                         JOIN kategori k ON p.kategori_id = k.id 
                         JOIN brand b ON p.brand_id = b.id 
                         ORDER BY p.kode_produk ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Produk | ZETA Motors</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        navy: {
                            50: '#f0f4f8',
                            500: '#003087',
                            900: '#0b1b3d',
                        },
                        zeta: {
                            500: '#CC0000',
                        }
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="../assets/css/style.css">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen flex">

    <?php require_once '../components/admin_sidebar.php'; ?>

    <main class="flex-grow p-6 md:p-10 space-y-8 overflow-y-auto max-h-screen">
        <header class="flex justify-between items-center border-b border-slate-200 pb-5">
            <div>
                <h1 class="text-3xl font-black text-slate-900 uppercase tracking-tight">KELOLA PRODUK</h1>
                <p class="text-xs text-slate-500 font-medium">Tambah, ubah, dan hapus unit armada motor</p>
            </div>
            <button onclick="openCreateModal()" class="px-5 py-2.5 bg-navy-500 hover:bg-navy-600 text-white font-bold text-xs tracking-wider uppercase rounded shadow transition btn-premium">
                TAMBAH PRODUK BARU
            </button>
        </header>

        <?php show_toast(); ?>

        <?php if (!empty($error_msg)): ?>
            <div class="p-4 bg-rose-50 border-l-4 border-rose-600 rounded text-rose-800 text-sm flex items-center gap-2">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                <span><?= htmlspecialchars($error_msg) ?></span>
            </div>
        <?php endif; ?>

        <!-- Products Table -->
        <div class="bg-white rounded border border-slate-200/80 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-400 font-semibold uppercase tracking-wider">
                            <th class="p-4">Gambar</th>
                            <th class="p-4">Kode</th>
                            <th class="p-4">Nama Produk</th>
                            <th class="p-4">Kategori</th>
                            <th class="p-4">Brand</th>
                            <th class="p-4">Harga</th>
                            <th class="p-4">Stok</th>
                            <th class="p-4">Tipe Kendaraan</th>
                            <th class="p-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                        <?php if (empty($products)): ?>
                            <tr>
                                <td colspan="9" class="p-4 text-center text-slate-400">Belum ada data produk motor.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($products as $prod): ?>
                                <tr class="hover:bg-slate-50/50 transition">
                                    <td class="p-4">
                                        <?php 
                                        $img = $prod['gambar'];
                                        $src = (!empty($img) && file_exists('../uploads/produk/' . $img)) ? '../uploads/produk/' . $img : 'https://images.unsplash.com/photo-1568772585407-9361f9bf3a87?q=80&w=600&auto=format&fit=crop';
                                        ?>
                                        <img src="<?= $src ?>" alt="" class="w-16 h-10 object-cover rounded border border-slate-200">
                                    </td>
                                    <td class="p-4 font-mono font-bold text-slate-800"><?= htmlspecialchars($prod['kode_produk']) ?></td>
                                    <td class="p-4 font-bold text-slate-950 uppercase"><?= htmlspecialchars($prod['nama_produk']) ?></td>
                                    <td class="p-4 font-medium text-slate-600"><?= htmlspecialchars($prod['nama_kategori']) ?></td>
                                    <td class="p-4 font-medium text-slate-600"><?= htmlspecialchars($prod['nama_brand']) ?></td>
                                    <td class="p-4 font-bold text-slate-900">Rp <?= number_format($prod['harga'], 0, ',', '.') ?></td>
                                    <td class="p-4">
                                        <span class="px-2 py-0.5 rounded text-xs font-bold <?= $prod['stok'] > 0 ? 'bg-emerald-50 text-emerald-800' : 'bg-rose-50 text-rose-800' ?>">
                                            <?= $prod['stok'] ?> Unit
                                        </span>
                                    </td>
                                    <td class="p-4 text-slate-500 text-xs"><?= htmlspecialchars($prod['tipe_barang']) ?></td>
                                    <td class="p-4 text-center">
                                        <div class="flex justify-center gap-2">
                                            <button onclick="openEditModal(<?= htmlspecialchars(json_encode($prod), ENT_QUOTES, 'UTF-8') ?>)"
                                                class="px-2.5 py-1 bg-slate-100 hover:bg-navy-500 hover:text-white rounded text-[10px] font-bold tracking-wider uppercase transition">
                                                EDIT
                                            </button>
                                            <form action="produk.php" method="POST" id="form-del-<?= htmlspecialchars($prod['kode_produk']) ?>" class="inline">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="kode_produk" value="<?= htmlspecialchars($prod['kode_produk']) ?>">
                                                <button type="button"
                                                    onclick="confirmDelete('<?= htmlspecialchars($prod['kode_produk'], ENT_QUOTES) ?>', '<?= htmlspecialchars($prod['nama_produk'], ENT_QUOTES) ?>')"
                                                    class="px-2.5 py-1 bg-slate-100 hover:bg-rose-600 hover:text-white rounded text-[10px] font-bold tracking-wider uppercase transition">
                                                    HAPUS
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- CRUD Modal Dialog -->
    <div id="product-modal" class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-lg w-full max-w-xl overflow-hidden shadow-2xl border border-slate-200 animate-slideup">
            <div class="bg-navy-500 p-5 text-white flex justify-between items-center">
                <h3 id="modal-title" class="font-bold uppercase tracking-wider">Form Tambah Produk</h3>
                <button onclick="closeModal()" class="text-white/80 hover:text-white">&times;</button>
            </div>
            
            <form action="produk.php" method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
                <input type="hidden" name="action" id="modal-action" value="create">
                <input type="hidden" name="old_gambar" id="modal-old-gambar" value="">

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="kode_produk" class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Kode Produk</label>
                        <input type="text" name="kode_produk" id="modal-kode" required placeholder="Contoh: ZTA-R25"
                            class="w-full px-3 py-2 border border-slate-300 rounded text-sm focus:outline-none focus:ring-1 focus:ring-navy-500 bg-white">
                    </div>
                    <div>
                        <label for="nama_produk" class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Nama Motor</label>
                        <input type="text" name="nama_produk" id="modal-nama" required placeholder="Contoh: Zeta R25 V4"
                            class="w-full px-3 py-2 border border-slate-300 rounded text-sm focus:outline-none focus:ring-1 focus:ring-navy-500 bg-white">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="kategori_id" class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Kategori</label>
                        <select name="kategori_id" id="modal-kategori" required
                            class="w-full px-3 py-2 border border-slate-300 rounded text-sm focus:outline-none focus:ring-1 focus:ring-navy-500 bg-white">
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nama_kategori']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="brand_id" class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Brand</label>
                        <select name="brand_id" id="modal-brand" required
                            class="w-full px-3 py-2 border border-slate-300 rounded text-sm focus:outline-none focus:ring-1 focus:ring-navy-500 bg-white">
                            <?php foreach ($brands as $br): ?>
                                <option value="<?= $br['id'] ?>"><?= htmlspecialchars($br['nama_brand']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="harga" class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Harga (Rupiah)</label>
                        <input type="number" step="0.01" name="harga" id="modal-harga" required placeholder="Contoh: 35000000"
                            class="w-full px-3 py-2 border border-slate-300 rounded text-sm focus:outline-none focus:ring-1 focus:ring-navy-500 bg-white">
                    </div>
                    <div>
                        <label for="stok" class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Stok Unit</label>
                        <input type="number" name="stok" id="modal-stok" required value="5"
                            class="w-full px-3 py-2 border border-slate-300 rounded text-sm focus:outline-none focus:ring-1 focus:ring-navy-500 bg-white">
                    </div>
                </div>

                <div>
                    <label for="tipe_barang" class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Tipe Kendaraan</label>
                    <input type="text" name="tipe_barang" id="modal-tipe" required placeholder="Contoh: Sport Fairing 250cc"
                        class="w-full px-3 py-2 border border-slate-300 rounded text-sm focus:outline-none focus:ring-1 focus:ring-navy-500 bg-white">
                </div>

                <div>
                    <label for="gambar" class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Upload Gambar (JPG/PNG/WEBP, Maks. 2MB)</label>
                    <input type="file" name="gambar" id="modal-gambar"
                        class="w-full text-sm file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-navy-50 file:text-navy-500 hover:file:bg-navy-100">
                </div>

                <div class="flex gap-4 pt-4 border-t border-slate-100">
                    <button type="button" onclick="closeModal()" class="flex-1 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs tracking-wider uppercase rounded transition">
                        BATAL
                    </button>
                    <button type="submit" class="flex-1 py-2.5 bg-navy-500 hover:bg-navy-600 text-white font-bold text-xs tracking-wider uppercase rounded transition btn-premium">
                        SIMPAN DATA
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // ── SweetAlert2 Delete Confirmation ──
        function confirmDelete(kode, nama) {
            Swal.fire({
                title: 'Hapus Produk?',
                html: `Anda akan menghapus produk:<br><strong style="color:#CC0000;font-size:1.05em;letter-spacing:.04em">${nama}</strong><br><code style="background:#f1f5f9;padding:2px 8px;border-radius:4px;font-size:.8em">${kode}</code><br><br><span style="color:#64748b;font-size:.8em">Aksi ini tidak dapat dibatalkan dan gambar produk akan ikut terhapus.</span>`,
                icon: 'warning',
                iconColor: '#CC0000',
                showCancelButton: true,
                confirmButtonText: '🗑️ Ya, Hapus Sekarang',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#CC0000',
                cancelButtonColor: '#64748b',
                focusCancel: true,
                reverseButtons: true,
                backdrop: 'rgba(3,8,20,0.7)',
                customClass: {
                    popup:          'swal-zeta-popup',
                    title:          'swal-zeta-title',
                    htmlContainer:  'swal-zeta-html',
                    confirmButton:  'swal-zeta-confirm',
                    cancelButton:   'swal-zeta-cancel',
                },
                showClass: {
                    popup: 'animate__animated animate__fadeInDown animate__faster'
                },
                hideClass: {
                    popup: 'animate__animated animate__fadeOutUp animate__faster'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    // Tampilkan loading saat memproses
                    Swal.fire({
                        title: 'Menghapus...',
                        text: 'Mohon tunggu sebentar.',
                        icon: 'info',
                        iconColor: '#003087',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showConfirmButton: false,
                        backdrop: 'rgba(3,8,20,0.7)',
                        didOpen: () => { Swal.showLoading(); }
                    });
                    document.getElementById('form-del-' + kode).submit();
                }
            });
        }

        // ── Inline custom style untuk popup Zeta ──
        const swalStyle = document.createElement('style');
        swalStyle.textContent = `
            .swal-zeta-popup {
                border-radius: 14px !important;
                font-family: 'Plus Jakarta Sans', sans-serif !important;
                border: 1px solid #e2e8f0 !important;
                box-shadow: 0 25px 60px rgba(0,0,0,.15) !important;
            }
            .swal-zeta-title {
                font-family: 'Outfit', sans-serif !important;
                font-weight: 900 !important;
                font-size: 1.4rem !important;
                letter-spacing: .05em !important;
                text-transform: uppercase !important;
            }
            .swal-zeta-html {
                font-size: .85rem !important;
                line-height: 1.8 !important;
            }
            .swal-zeta-confirm, .swal-zeta-cancel {
                font-family: 'Outfit', sans-serif !important;
                font-weight: 700 !important;
                letter-spacing: .08em !important;
                text-transform: uppercase !important;
                font-size: .72rem !important;
                border-radius: 8px !important;
                padding: 10px 22px !important;
            }
            .swal-zeta-confirm:focus, .swal-zeta-cancel:focus {
                box-shadow: none !important;
            }
        `;
        document.head.appendChild(swalStyle);

        const modal = document.getElementById('product-modal');

        function openCreateModal() {
            document.getElementById('modal-title').textContent = 'Tambah Produk Baru';
            document.getElementById('modal-action').value = 'create';
            document.getElementById('modal-kode').readOnly = false;
            document.getElementById('modal-kode').value = '';
            document.getElementById('modal-nama').value = '';
            document.getElementById('modal-harga').value = '';
            document.getElementById('modal-stok').value = '5';
            document.getElementById('modal-tipe').value = '';
            document.getElementById('modal-old-gambar').value = '';
            modal.classList.remove('hidden');
        }

        function openEditModal(prod) {
            document.getElementById('modal-title').textContent = 'Ubah Detail Produk';
            document.getElementById('modal-action').value = 'update';
            document.getElementById('modal-kode').readOnly = true;
            document.getElementById('modal-kode').value = prod.kode_produk;
            document.getElementById('modal-nama').value = prod.nama_produk;
            document.getElementById('modal-kategori').value = prod.kategori_id;
            document.getElementById('modal-brand').value = prod.brand_id;
            document.getElementById('modal-harga').value = prod.harga;
            document.getElementById('modal-stok').value = prod.stok;
            document.getElementById('modal-tipe').value = prod.tipe_barang;
            document.getElementById('modal-old-gambar').value = prod.gambar;
            modal.classList.remove('hidden');
        }

        function closeModal() {
            modal.classList.add('hidden');
        }
    </script>
</body>
</html>
