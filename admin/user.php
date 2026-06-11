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

// Handle Ban/Unban requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $target_id = (int)$_POST['user_id'];
    $action = $_POST['action'] ?? '';

    if ($target_id > 0 && !empty($action)) {
        if ($target_id === (int)$_SESSION['user_id']) {
            $error_msg = 'Anda tidak dapat memblokir akun Anda sendiri.';
        } else {
            try {
                $status = ($action === 'ban') ? 'banned' : 'active';
                $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
                $stmt->execute([$status, $target_id]);
                
                $msg = ($action === 'ban') ? 'User berhasil diblokir / banned.' : 'Akses user berhasil dipulihkan.';
                set_toast('success', $msg);
                header('Location: user.php');
                exit;
            } catch (PDOException $e) {
                $error_msg = 'Gagal memperbarui status user.';
            }
        }
    }
}

// Fetch all users
$users = $pdo->query("SELECT * FROM users ORDER BY role ASC, id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola User | ZETA Motors</title>
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
                <h1 class="text-3xl font-black text-slate-900 uppercase tracking-tight">KELOLA USER</h1>
                <p class="text-xs text-slate-500 font-medium">Manajemen otorisasi dan kontrol status akun</p>
            </div>
        </header>

        <?php show_toast(); ?>

        <?php if (!empty($error_msg)): ?>
            <div class="p-4 bg-rose-50 border-l-4 border-rose-600 rounded text-rose-800 text-sm flex items-center gap-2">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                <span><?= htmlspecialchars($error_msg) ?></span>
            </div>
        <?php endif; ?>

        <!-- Users Table -->
        <div class="bg-white rounded border border-slate-200/80 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-400 font-semibold uppercase tracking-wider">
                            <th class="p-4 w-16">ID</th>
                            <th class="p-4">Username</th>
                            <th class="p-4">Alamat Email</th>
                            <th class="p-4">Peran (Role)</th>
                            <th class="p-4">Tanggal Daftar</th>
                            <th class="p-4 text-center">Status</th>
                            <th class="p-4 text-right">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                        <?php foreach ($users as $user): ?>
                            <tr class="hover:bg-slate-50/50 transition">
                                <td class="p-4 font-mono text-slate-500 font-semibold">#<?= $user['id'] ?></td>
                                <td class="p-4 font-bold text-slate-900"><?= htmlspecialchars($user['username']) ?></td>
                                <td class="p-4 font-mono text-slate-600"><?= htmlspecialchars($user['email']) ?></td>
                                <td class="p-4">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase <?= $user['role'] === 'admin' ? 'bg-navy-500 text-white' : 'bg-slate-100 text-slate-700' ?>">
                                        <?= $user['role'] ?>
                                    </span>
                                </td>
                                <td class="p-4 text-xs text-slate-500 font-medium"><?= date('d M Y, H:i', strtotime($user['created_at'])) ?></td>
                                <td class="p-4 text-center">
                                    <?php if ($user['status'] === 'active'): ?>
                                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">Aktif</span>
                                    <?php else: ?>
                                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-100 text-rose-800">Banned</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-4 text-right">
                                    <?php if ($user['id'] === (int)$_SESSION['user_id']): ?>
                                        <span class="text-xs text-slate-400 font-medium">Akun Anda</span>
                                    <?php else: ?>
                                        <form action="user.php" method="POST" class="inline">
                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                            <?php if ($user['status'] === 'active'): ?>
                                                <input type="hidden" name="action" value="ban">
                                                <button type="submit" onclick="return confirm('Apakah Anda yakin ingin mem-ban user ini?');"
                                                    class="px-3 py-1 bg-rose-50 hover:bg-rose-600 hover:text-white border border-rose-200 text-rose-700 rounded text-xs font-bold transition">
                                                    BAN USER
                                                </button>
                                            <?php else: ?>
                                                <input type="hidden" name="action" value="unban">
                                                <button type="submit" onclick="return confirm('Apakah Anda yakin ingin memulihkan user ini?');"
                                                    class="px-3 py-1 bg-emerald-50 hover:bg-emerald-600 hover:text-white border border-emerald-200 text-emerald-700 rounded text-xs font-bold transition">
                                                    UNBAN USER
                                                </button>
                                            <?php endif; ?>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

</body>
</html>
