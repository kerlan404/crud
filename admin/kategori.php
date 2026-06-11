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

// Handle CRUD submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $nama_kategori = trim($_POST['nama_kategori']);
        if (!empty($nama_kategori)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO kategori (nama_kategori) VALUES (?)");
                $stmt->execute([$nama_kategori]);
                set_toast('success', 'Kategori baru berhasil ditambahkan.');
                header('Location: kategori.php');
                exit;
            } catch (PDOException $e) {
                $error_msg = 'Gagal menambah kategori (Mungkin nama sudah terdaftar).';
            }
        }
    }
    
    if ($action === 'update') {
        $id = (int)$_POST['id'];
        $nama_kategori = trim($_POST['nama_kategori']);
        if ($id > 0 && !empty($nama_kategori)) {
            try {
                $stmt = $pdo->prepare("UPDATE kategori SET nama_kategori = ? WHERE id = ?");
                $stmt->execute([$nama_kategori, $id]);
                set_toast('success', 'Kategori berhasil diperbarui.');
                header('Location: kategori.php');
                exit;
            } catch (PDOException $e) {
                $error_msg = 'Gagal memperbarui kategori.';
            }
        }
    }

    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        if ($id > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM kategori WHERE id = ?");
                $stmt->execute([$id]);
                set_toast('success', 'Kategori berhasil dihapus.');
                header('Location: kategori.php');
                exit;
            } catch (PDOException $e) {
                $error_msg = 'Gagal menghapus kategori. Kategori ini sedang digunakan oleh produk aktif.';
            }
        }
    }
}

// Fetch all categories
$categories = $pdo->query("SELECT * FROM kategori ORDER BY id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kategori | ZETA Motors</title>
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
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen flex">

    <?php require_once '../components/admin_sidebar.php'; ?>

    <main class="flex-grow p-6 md:p-10 space-y-8 overflow-y-auto max-h-screen">
        <header class="flex justify-between items-center border-b border-slate-200 pb-5">
            <div>
                <h1 class="text-3xl font-black text-slate-900 uppercase tracking-tight">KELOLA KATEGORI</h1>
                <p class="text-xs text-slate-500 font-medium">Manajemen kategori klasifikasi produk motor</p>
            </div>
        </header>

        <?php show_toast(); ?>

        <?php if (!empty($error_msg)): ?>
            <div class="p-4 bg-rose-50 border-l-4 border-rose-600 rounded text-rose-800 text-sm flex items-center gap-2">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                <span><?= htmlspecialchars($error_msg) ?></span>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Form Create/Edit -->
            <div class="bg-white p-6 rounded border border-slate-200 shadow-sm space-y-4">
                <h3 class="text-sm font-extrabold text-slate-800 uppercase tracking-wider border-b border-slate-100 pb-3" id="form-title">
                    Tambah Kategori Baru
                </h3>
                
                <form action="kategori.php" method="POST" class="space-y-4" id="kategori-form">
                    <input type="hidden" name="action" id="form-action" value="create">
                    <input type="hidden" name="id" id="kategori-id" value="">
                    
                    <div>
                        <label for="nama_kategori" class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Nama Kategori</label>
                        <input type="text" name="nama_kategori" id="nama_kategori" required placeholder="Contoh: Scooter Matic"
                            class="w-full px-3 py-2 border border-slate-300 rounded text-sm focus:outline-none focus:ring-1 focus:ring-navy-500 focus:border-navy-500 bg-white">
                    </div>

                    <div class="flex gap-2 pt-2">
                        <button type="submit" class="flex-grow py-2 bg-navy-500 hover:bg-navy-600 text-white font-bold text-xs tracking-wider uppercase rounded transition btn-premium">
                            SIMPAN KATEGORI
                        </button>
                        <button type="button" id="btn-cancel" onclick="resetForm()" class="hidden px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold text-xs tracking-wider uppercase rounded transition">
                            BATAL
                        </button>
                    </div>
                </form>
            </div>

            <!-- List Categories -->
            <div class="bg-white rounded border border-slate-200/80 shadow-sm overflow-hidden lg:col-span-2">
                <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/50">
                    <h3 class="text-sm font-extrabold text-slate-800 uppercase tracking-wider">Daftar Kategori</h3>
                </div>
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-50 text-slate-400 font-semibold uppercase tracking-wider border-b border-slate-100">
                            <th class="p-4 w-20">ID</th>
                            <th class="p-4">Nama Kategori</th>
                            <th class="p-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if (empty($categories)): ?>
                            <tr>
                                <td colspan="3" class="p-4 text-center text-slate-400">Belum ada data kategori.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($categories as $cat): ?>
                                <tr class="hover:bg-slate-50/50 transition">
                                    <td class="p-4 font-mono font-bold text-slate-500"><?= $cat['id'] ?></td>
                                    <td class="p-4 font-semibold text-slate-800 text-sm"><?= htmlspecialchars($cat['nama_kategori']) ?></td>
                                    <td class="p-4 text-right flex justify-end gap-2">
                                        <button onclick="editKategori(<?= $cat['id'] ?>, '<?= htmlspecialchars($cat['nama_kategori'], ENT_QUOTES) ?>')"
                                            class="px-2.5 py-1 bg-slate-100 hover:bg-navy-500 hover:text-white rounded text-[10px] font-bold tracking-wider uppercase transition">
                                            EDIT
                                        </button>
                                        <form action="kategori.php" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus kategori ini?');" class="inline">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                                            <button type="submit" class="px-2.5 py-1 bg-slate-100 hover:bg-rose-600 hover:text-white rounded text-[10px] font-bold tracking-wider uppercase transition">
                                                HAPUS
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
        function editKategori(id, name) {
            document.getElementById('form-title').textContent = 'Edit Kategori';
            document.getElementById('form-action').value = 'update';
            document.getElementById('kategori-id').value = id;
            document.getElementById('nama_kategori').value = name;
            document.getElementById('btn-cancel').classList.remove('hidden');
        }

        function resetForm() {
            document.getElementById('form-title').textContent = 'Tambah Kategori Baru';
            document.getElementById('form-action').value = 'create';
            document.getElementById('kategori-id').value = '';
            document.getElementById('nama_kategori').value = '';
            document.getElementById('btn-cancel').classList.add('hidden');
        }
    </script>
</body>
</html>
