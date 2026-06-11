<?php
$page_title = "Profil Saya";
require_once '../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: ../auth/login.php');
    exit;
}

require_once '../components/toast.php';

$user_id   = (int)$_SESSION['user_id'];
$error_msg = '';

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header('Location: ../auth/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profil') {
        $new_username = trim($_POST['username']);
        $new_email    = trim($_POST['email']);

        if (empty($new_username) || empty($new_email)) {
            $error_msg = 'Username dan email tidak boleh kosong.';
        } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
            $error_msg = 'Format email tidak valid.';
        } else {
            try {
                $chk = $pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
                $chk->execute([$new_username, $new_email, $user_id]);
                if ($chk->fetch()) {
                    $error_msg = 'Username atau email sudah digunakan akun lain.';
                } else {
                    $pdo->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?")
                        ->execute([$new_username, $new_email, $user_id]);
                    $_SESSION['username'] = $new_username;
                    $_SESSION['email']    = $new_email;
                    $user['username'] = $new_username;
                    $user['email']    = $new_email;
                    set_toast('success', 'Profil berhasil diperbarui.');
                    header('Location: profil.php');
                    exit;
                }
            } catch (PDOException $e) {
                $error_msg = 'Gagal memperbarui profil.';
            }
        }
    }

    if ($action === 'change_password') {
        $old_pass  = $_POST['old_password']  ?? '';
        $new_pass  = $_POST['new_password']  ?? '';
        $conf_pass = $_POST['confirm_password'] ?? '';

        if (empty($old_pass) || empty($new_pass) || empty($conf_pass)) {
            $error_msg = 'Semua field password wajib diisi.';
        } elseif (!password_verify($old_pass, $user['password'])) {
            $error_msg = 'Password lama tidak sesuai.';
        } elseif ($new_pass !== $conf_pass) {
            $error_msg = 'Konfirmasi password baru tidak cocok.';
        } elseif (strlen($new_pass) < 6) {
            $error_msg = 'Password baru minimal 6 karakter.';
        } else {
            $hashed = password_hash($new_pass, PASSWORD_BCRYPT);
            $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hashed, $user_id]);
            set_toast('success', 'Password berhasil diubah.');
            header('Location: profil.php');
            exit;
        }
    }
}

// Statistik singkat
$total_orders = $pdo->prepare("SELECT COUNT(*) FROM pembelian WHERE user_id = ?");
$total_orders->execute([$user_id]);
$total_orders = $total_orders->fetchColumn();

require_once '../components/header.php';
?>

<div class="container mx-auto px-4 py-12 max-w-4xl animate-slideup">
    <div class="flex items-center gap-3 mb-8">
        <span class="w-1.5 h-8 bg-zeta-500"></span>
        <h1 class="text-3xl font-black text-slate-900 uppercase tracking-tight">PROFIL SAYA</h1>
    </div>

    <?php if (!empty($error_msg)): ?>
        <div class="mb-6 p-4 bg-rose-50 border-l-4 border-rose-600 rounded text-rose-800 text-sm flex items-center gap-2">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            <span><?= htmlspecialchars($error_msg) ?></span>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Profil & Edit -->
        <div class="bg-white p-6 rounded-lg border border-slate-200 shadow-sm space-y-6">
            <div class="flex items-center gap-4 pb-4 border-b border-slate-100">
                <div class="w-14 h-14 bg-navy-500 rounded-full flex items-center justify-center font-black text-white text-xl uppercase">
                    <?= strtoupper(substr($user['username'],0,2)) ?>
                </div>
                <div>
                    <h3 class="text-base font-black text-slate-800"><?= htmlspecialchars($user['username']) ?></h3>
                    <p class="text-xs text-slate-500 font-mono"><?= htmlspecialchars($user['email']) ?></p>
                    <span class="text-[10px] text-slate-500">Bergabung: <?= date('d M Y', strtotime($user['created_at'])) ?></span>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-1 gap-3">
                <div class="p-3 bg-slate-50 rounded border border-slate-100 flex items-center justify-between">
                    <span class="text-xs text-slate-500 font-semibold">Total Pesanan</span>
                    <span class="text-lg font-black text-navy-500"><?= $total_orders ?> <span class="text-xs font-medium text-slate-400">Transaksi</span></span>
                </div>
            </div>

            <h4 class="text-sm font-extrabold text-slate-700 uppercase tracking-wider">Edit Informasi</h4>
            <form action="profil.php" method="POST" class="space-y-4">
                <input type="hidden" name="action" value="update_profil">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Username</label>
                    <input type="text" name="username" required value="<?= htmlspecialchars($user['username']) ?>"
                        class="w-full px-3 py-2.5 border border-slate-300 rounded text-sm focus:outline-none focus:ring-1 focus:ring-navy-500 bg-white">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Email</label>
                    <input type="email" name="email" required value="<?= htmlspecialchars($user['email']) ?>"
                        class="w-full px-3 py-2.5 border border-slate-300 rounded text-sm focus:outline-none focus:ring-1 focus:ring-navy-500 bg-white">
                </div>
                <button type="submit" class="w-full py-2.5 bg-navy-500 hover:bg-navy-600 text-white font-bold text-xs tracking-wider uppercase rounded transition btn-premium cursor-pointer">
                    SIMPAN PERUBAHAN
                </button>
            </form>
        </div>

        <!-- Ganti Password -->
        <div class="bg-white p-6 rounded-lg border border-slate-200 shadow-sm space-y-6">
            <h4 class="text-sm font-extrabold text-slate-700 uppercase tracking-wider flex items-center gap-2">
                <svg class="w-4 h-4 text-zeta-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                Ganti Password
            </h4>
            <form action="profil.php" method="POST" id="pw-form" class="space-y-4">
                <input type="hidden" name="action" value="change_password">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Password Lama</label>
                    <input type="password" name="old_password" required placeholder="••••••••"
                        class="w-full px-3 py-2.5 border border-slate-300 rounded text-sm focus:outline-none focus:ring-1 focus:ring-navy-500 bg-white">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Password Baru</label>
                    <input type="password" name="new_password" id="new_pw" required placeholder="Min. 6 karakter"
                        class="w-full px-3 py-2.5 border border-slate-300 rounded text-sm focus:outline-none focus:ring-1 focus:ring-navy-500 bg-white">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Konfirmasi Password Baru</label>
                    <input type="password" name="confirm_password" id="conf_pw" required placeholder="••••••••"
                        class="w-full px-3 py-2.5 border border-slate-300 rounded text-sm focus:outline-none focus:ring-1 focus:ring-navy-500 bg-white">
                </div>
                <button type="button" id="btn-ganti-pw"
                    class="w-full py-2.5 bg-zeta-500 hover:bg-zeta-600 text-white font-bold text-xs tracking-wider uppercase rounded transition btn-premium cursor-pointer">
                    GANTI PASSWORD
                </button>
            </form>

            <div class="p-3 bg-slate-50 border border-slate-200 rounded text-xs text-slate-500 space-y-1">
                <p class="font-semibold text-slate-600">Tips Keamanan:</p>
                <p>• Gunakan password minimal 8 karakter</p>
                <p>• Jangan bagikan password kepada siapapun</p>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('btn-ganti-pw').addEventListener('click', function() {
        const nPw = document.getElementById('new_pw').value;
        const cPw = document.getElementById('conf_pw').value;
        if (!nPw || !cPw) {
            Swal.fire({ icon: 'warning', title: 'FIELD KOSONG', text: 'Harap isi semua field password.',
                confirmButtonColor: '#003087', customClass: { popup: 'swal-zeta-popup', title: 'swal-zeta-title' } });
            return;
        }
        if (nPw !== cPw) {
            Swal.fire({ icon: 'error', title: 'TIDAK COCOK', text: 'Konfirmasi password tidak sesuai.',
                confirmButtonColor: '#CC0000', customClass: { popup: 'swal-zeta-popup', title: 'swal-zeta-title' } });
            return;
        }
        Swal.fire({
            title: 'GANTI PASSWORD?',
            text: 'Pastikan Anda ingat password baru sebelum menyimpan.',
            icon: 'warning', iconColor: '#CC0000',
            showCancelButton: true,
            confirmButtonText: 'Ya, Ganti',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#CC0000',
            cancelButtonColor: '#64748b',
            reverseButtons: true,
            customClass: { popup: 'swal-zeta-popup', title: 'swal-zeta-title', confirmButton: 'swal-zeta-confirm', cancelButton: 'swal-zeta-cancel' }
        }).then(r => { if (r.isConfirmed) document.getElementById('pw-form').submit(); });
    });
</script>

<?php require_once '../components/footer.php'; ?>
