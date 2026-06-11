<?php
require_once '../config/db.php';
require_once '../components/toast.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Access Control: Must be admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

// Fetch KPIs
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_products = $pdo->query("SELECT COUNT(*) FROM produk")->fetchColumn();
$low_stock = $pdo->query("SELECT COUNT(*) FROM produk WHERE stok < 5")->fetchColumn();
$pending_transactions = $pdo->query("SELECT COUNT(*) FROM pembelian WHERE status = 'pending'")->fetchColumn();
$revenue = $pdo->query("SELECT SUM(total_bayar) FROM pembelian WHERE status = 'confirmed'")->fetchColumn() ?: 0;

// Fetch 5 Latest Transactions
$latest_orders = $pdo->query("SELECT p.*, u.username, pr.nama_produk 
                              FROM pembelian p 
                              JOIN users u ON p.user_id = u.id 
                              JOIN produk pr ON p.kode_produk = pr.kode_produk 
                              ORDER BY p.tanggal_transaksi DESC LIMIT 5")->fetchAll();

// Fetch 5 Newest Users
$newest_users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin | ZETA Motors</title>
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

    <!-- Sidebar Navigation -->
    <?php require_once '../components/admin_sidebar.php'; ?>

    <!-- Main Content Area -->
    <main class="flex-grow p-6 md:p-10 space-y-8 overflow-y-auto max-h-screen">
        <!-- Page Title & Header -->
        <header class="flex justify-between items-center border-b border-slate-200 pb-5">
            <div>
                <h1 class="text-3xl font-black text-slate-900 uppercase tracking-tight">DASHBOARD ANALITYCS</h1>
                <p class="text-xs text-slate-500 font-medium">Pemantauan data operasional Zeta Motors secara real-time</p>
            </div>
            <div class="text-xs font-semibold bg-white border border-slate-200 rounded px-4 py-2 text-slate-600 shadow-sm">
                Hari ini: <strong class="text-slate-800"><?= date('d F Y') ?></strong>
            </div>
        </header>

        <?php show_toast(); ?>

        <!-- KPI Cards Grid -->
        <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6">
            <!-- Revenue -->
            <article class="bg-white p-5 rounded border border-slate-200/80 shadow-sm flex flex-col justify-between">
                <div>
                    <span class="text-[10px] font-mono tracking-widest text-slate-400 uppercase block mb-1">TOTAL PENDAPATAN</span>
                    <span class="text-2xl font-black text-emerald-600 font-serif leading-none">
                        Rp <?= number_format($revenue, 0, ',', '.') ?>
                    </span>
                </div>
                <p class="text-[10px] text-slate-400 mt-4 font-semibold">Berdasarkan transaksi 'confirmed'</p>
            </article>

            <!-- Total Users -->
            <article class="bg-white p-5 rounded border border-slate-200/80 shadow-sm flex flex-col justify-between">
                <div>
                    <span class="text-[10px] font-mono tracking-widest text-slate-400 uppercase block mb-1">TOTAL PENGGUNA</span>
                    <span class="text-3xl font-black text-slate-800 leading-none">
                        <?= $total_users ?> <span class="text-xs font-medium text-slate-400">Akun</span>
                    </span>
                </div>
                <p class="text-[10px] text-slate-400 mt-4 font-semibold">User & admin terdaftar</p>
            </article>

            <!-- Total Products -->
            <article class="bg-white p-5 rounded border border-slate-200/80 shadow-sm flex flex-col justify-between">
                <div>
                    <span class="text-[10px] font-mono tracking-widest text-slate-400 uppercase block mb-1">TOTAL PRODUK</span>
                    <span class="text-3xl font-black text-slate-800 leading-none">
                        <?= $total_products ?> <span class="text-xs font-medium text-slate-400">Barang</span>
                    </span>
                </div>
                <p class="text-[10px] text-slate-400 mt-4 font-semibold">Lini kendaraan di database</p>
            </article>

            <!-- Stock warnings -->
            <article class="bg-white p-5 rounded border border-slate-200/80 shadow-sm flex flex-col justify-between">
                <div>
                    <span class="text-[10px] font-mono tracking-widest text-slate-400 uppercase block mb-1">STOK MENIPIS</span>
                    <span class="text-3xl font-black <?= $low_stock > 0 ? 'text-amber-600' : 'text-slate-800' ?> leading-none">
                        <?= $low_stock ?> <span class="text-xs font-medium text-slate-400">Produk</span>
                    </span>
                </div>
                <p class="text-[10px] text-slate-400 mt-4 font-semibold">Produk dengan stok &lt; 5 unit</p>
            </article>

            <!-- Pending orders -->
            <article class="bg-white p-5 rounded border border-slate-200/80 shadow-sm flex flex-col justify-between">
                <div>
                    <span class="text-[10px] font-mono tracking-widest text-slate-400 uppercase block mb-1">PENDING TRANSAKSI</span>
                    <span class="text-3xl font-black <?= $pending_transactions > 0 ? 'text-rose-600' : 'text-slate-800' ?> leading-none">
                        <?= $pending_transactions ?> <span class="text-xs font-medium text-slate-400">Menunggu</span>
                    </span>
                </div>
                <p class="text-[10px] text-slate-400 mt-4 font-semibold">Perlu tindakan verifikasi</p>
            </article>
        </section>

        <!-- Dynamic summaries grids -->
        <section class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- 5 Latest Transactions -->
            <div class="bg-white rounded border border-slate-200/80 shadow-sm overflow-hidden lg:col-span-2">
                <div class="px-5 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                    <h3 class="text-sm font-extrabold text-slate-800 uppercase tracking-wider">5 Transaksi Terbaru</h3>
                    <a href="transaksi.php" class="text-xs font-semibold text-navy-500 hover:underline">Lihat Semua</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-slate-50 text-slate-400 font-semibold uppercase tracking-wider border-b border-slate-100">
                                <th class="p-3">ID</th>
                                <th class="p-3">User</th>
                                <th class="p-3">Produk</th>
                                <th class="p-3 text-right">Total</th>
                                <th class="p-3 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (empty($latest_orders)): ?>
                                <tr>
                                    <td colspan="5" class="p-4 text-center text-slate-400">Belum ada transaksi saat ini.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($latest_orders as $order): ?>
                                    <tr class="hover:bg-slate-50/50 transition">
                                        <td class="p-3 font-mono font-bold text-slate-800">#ZTA-<?= $order['id_pembelian'] ?></td>
                                        <td class="p-3 font-medium text-slate-700"><?= htmlspecialchars($order['username']) ?></td>
                                        <td class="p-3 font-semibold text-slate-800 uppercase"><?= htmlspecialchars($order['nama_produk']) ?></td>
                                        <td class="p-3 text-right font-bold text-slate-950">Rp <?= number_format($order['total_bayar'], 0, ',', '.') ?></td>
                                        <td class="p-3 text-center">
                                            <?php 
                                            $status = $order['status'];
                                            if ($status === 'pending') $badge = 'bg-amber-100 text-amber-800';
                                            elseif ($status === 'paid') $badge = 'bg-blue-100 text-blue-800';
                                            elseif ($status === 'confirmed') $badge = 'bg-emerald-100 text-emerald-800';
                                            else $badge = 'bg-rose-100 text-rose-800';
                                            ?>
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold tracking-wide uppercase <?= $badge ?>">
                                                <?= $status ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 5 Newest Users -->
            <div class="bg-white rounded border border-slate-200/80 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                    <h3 class="text-sm font-extrabold text-slate-800 uppercase tracking-wider">5 User Terbaru</h3>
                    <a href="user.php" class="text-xs font-semibold text-navy-500 hover:underline">Lihat Semua</a>
                </div>
                <div class="divide-y divide-slate-100">
                    <?php if (empty($newest_users)): ?>
                        <div class="p-4 text-center text-xs text-slate-400">Belum ada pengguna terdaftar.</div>
                    <?php else: ?>
                        <?php foreach ($newest_users as $user): ?>
                            <div class="p-4 flex items-center justify-between hover:bg-slate-50/50 transition">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 bg-slate-100 rounded-full flex items-center justify-center font-bold text-slate-700 text-xs">
                                        <?= substr($user['username'], 0, 2) ?>
                                    </div>
                                    <div>
                                        <h5 class="text-xs font-bold text-slate-800"><?= htmlspecialchars($user['username']) ?></h5>
                                        <p class="text-[10px] text-slate-400 font-mono"><?= htmlspecialchars($user['email']) ?></p>
                                    </div>
                                </div>
                                <div>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase <?= $user['role'] === 'admin' ? 'bg-navy-500 text-white' : 'bg-slate-100 text-slate-600' ?>">
                                        <?= $user['role'] ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>

</body>
</html>
