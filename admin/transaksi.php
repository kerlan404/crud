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

// Handle Status Changes (Confirm or Cancel)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = (int)$_POST['order_id'];
    $action = $_POST['action'] ?? '';

    if ($order_id > 0 && !empty($action)) {
        try {
            // Start SQL transaction
            $pdo->beginTransaction();

            // Fetch current order status, product, quantity
            $stmt = $pdo->prepare("SELECT p.*, pr.stok FROM pembelian p 
                                    JOIN produk pr ON p.kode_produk = pr.kode_produk 
                                    WHERE p.id_pembelian = ? FOR UPDATE");
            $stmt->execute([$order_id]);
            $order = $stmt->fetch();

            if (!$order) {
                throw new Exception("Transaksi tidak ditemukan.");
            }

            if ($action === 'confirm') {
                if ($order['status'] === 'confirmed') {
                    throw new Exception("Transaksi ini sudah dikonfirmasi.");
                }

                // Check stock availability
                if ($order['stok'] < $order['jumlah']) {
                    throw new Exception("Gagal Konfirmasi: Stok tidak mencukupi (Stok saat ini: {$order['stok']} unit).");
                }

                // Deduct stock
                $upd_stock = $pdo->prepare("UPDATE produk SET stok = stok - ? WHERE kode_produk = ?");
                $upd_stock->execute([$order['jumlah'], $order['kode_produk']]);

                // Update status to confirmed
                $upd_status = $pdo->prepare("UPDATE pembelian SET status = 'confirmed' WHERE id_pembelian = ?");
                $upd_status->execute([$order_id]);

                set_toast('success', "Transaksi #ZTA-{$order_id} berhasil dikonfirmasi. Stok kendaraan telah dikurangi.");
            } 
            
            elseif ($action === 'cancel') {
                if ($order['status'] === 'cancelled') {
                    throw new Exception("Transaksi ini sudah dibatalkan.");
                }

                // If previously confirmed, return the stock
                if ($order['status'] === 'confirmed') {
                    $upd_stock = $pdo->prepare("UPDATE produk SET stok = stok + ? WHERE kode_produk = ?");
                    $upd_stock->execute([$order['jumlah'], $order['kode_produk']]);
                }

                // Update status to cancelled
                $upd_status = $pdo->prepare("UPDATE pembelian SET status = 'cancelled' WHERE id_pembelian = ?");
                $upd_status->execute([$order_id]);

                set_toast('success', "Transaksi #ZTA-{$order_id} berhasil dibatalkan.");
            }

            $pdo->commit();
            header('Location: transaksi.php');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error_msg = $e->getMessage();
        }
    }
}

// Filters
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';
$query = "SELECT p.*, u.username, pr.nama_produk, pr.harga, b.nama_brand
          FROM pembelian p 
          JOIN users u ON p.user_id = u.id 
          JOIN produk pr ON p.kode_produk = pr.kode_produk
          JOIN brand b ON pr.brand_id = b.id";
$params = [];

if (!empty($status_filter)) {
    $query .= " WHERE p.status = ?";
    $params[] = $status_filter;
}

$query .= " ORDER BY p.tanggal_transaksi DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$orders = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Transaksi | ZETA Motors</title>
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
                <h1 class="text-3xl font-black text-slate-900 uppercase tracking-tight">KELOLA TRANSAKSI</h1>
                <p class="text-xs text-slate-500 font-medium">Verifikasi pembayaran masuk dan kelola status pembelian</p>
            </div>
        </header>

        <?php show_toast(); ?>

        <?php if (!empty($error_msg)): ?>
            <div class="p-4 bg-rose-50 border-l-4 border-rose-600 rounded text-rose-800 text-sm flex items-center gap-2">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                <span><?= htmlspecialchars($error_msg) ?></span>
            </div>
        <?php endif; ?>

        <!-- Filter tabs -->
        <div class="flex gap-2 border-b border-slate-200 pb-px">
            <a href="transaksi.php" class="px-4 py-2 text-xs font-bold tracking-wider uppercase border-b-2 transition <?= empty($status_filter) ? 'border-navy-500 text-navy-500' : 'border-transparent text-slate-400 hover:text-slate-600' ?>">
                Semua Transaksi
            </a>
            <a href="transaksi.php?status=pending" class="px-4 py-2 text-xs font-bold tracking-wider uppercase border-b-2 transition <?= $status_filter === 'pending' ? 'border-amber-500 text-amber-500' : 'border-transparent text-slate-400 hover:text-slate-600' ?>">
                Pending
            </a>
            <a href="transaksi.php?status=confirmed" class="px-4 py-2 text-xs font-bold tracking-wider uppercase border-b-2 transition <?= $status_filter === 'confirmed' ? 'border-emerald-500 text-emerald-500' : 'border-transparent text-slate-400 hover:text-slate-600' ?>">
                Confirmed
            </a>
            <a href="transaksi.php?status=cancelled" class="px-4 py-2 text-xs font-bold tracking-wider uppercase border-b-2 transition <?= $status_filter === 'cancelled' ? 'border-rose-500 text-rose-500' : 'border-transparent text-slate-400 hover:text-slate-600' ?>">
                Cancelled
            </a>
        </div>

        <!-- Orders Table -->
        <div class="bg-white rounded border border-slate-200/80 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-400 font-semibold uppercase tracking-wider">
                            <th class="p-4">No. Pesanan</th>
                            <th class="p-4">Pembeli</th>
                            <th class="p-4">Kendaraan</th>
                            <th class="p-4">Jumlah</th>
                            <th class="p-4">Total Tagihan</th>
                            <th class="p-4">Metode Pembayaran</th>
                            <th class="p-4">Tanggal Order</th>
                            <th class="p-4">Status</th>
                            <th class="p-4 text-center">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                        <?php if (empty($orders)): ?>
                            <tr>
                                <td colspan="9" class="p-4 text-center text-slate-400">Belum ada transaksi pembelian.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($orders as $order): ?>
                                <tr class="hover:bg-slate-50/50 transition">
                                    <td class="p-4 font-mono font-bold text-slate-800">#ZTA-<?= $order['id_pembelian'] ?></td>
                                    <td class="p-4 font-semibold text-slate-700"><?= htmlspecialchars($order['username']) ?></td>
                                    <td class="p-4">
                                        <span class="font-bold text-slate-900 uppercase"><?= htmlspecialchars($order['nama_produk']) ?></span>
                                        <span class="block text-[10px] text-slate-400 font-mono"><?= htmlspecialchars($order['kode_produk']) ?> | <?= htmlspecialchars($order['nama_brand']) ?></span>
                                    </td>
                                    <td class="p-4 font-semibold text-slate-700"><?= $order['jumlah'] ?> Unit</td>
                                    <td class="p-4 font-bold text-slate-900">Rp <?= number_format($order['total_bayar'], 0, ',', '.') ?></td>
                                    <td class="p-4">
                                        <span class="px-2 py-0.5 rounded text-xs font-semibold bg-slate-100 text-slate-700">
                                            <?= htmlspecialchars($order['metode_pembayaran']) ?>
                                        </span>
                                    </td>
                                    <td class="p-4 text-xs text-slate-500 font-medium"><?= date('d M Y, H:i', strtotime($order['tanggal_transaksi'])) ?></td>
                                    <td class="p-4">
                                        <?php 
                                        $status = $order['status'];
                                        if ($status === 'pending') $badge = 'bg-amber-100 text-amber-800';
                                        elseif ($status === 'paid') $badge = 'bg-blue-100 text-blue-800';
                                        elseif ($status === 'confirmed') $badge = 'bg-emerald-100 text-emerald-800';
                                        else $badge = 'bg-rose-100 text-rose-800';
                                        ?>
                                        <span class="px-2.5 py-1 rounded-full text-xs font-bold tracking-wide uppercase <?= $badge ?>">
                                            <?= $status ?>
                                        </span>
                                    </td>
                                    <td class="p-4 text-center">
                                        <div class="flex justify-center gap-2">
                                            <?php if ($status === 'pending' || $status === 'paid'): ?>
                                                <form action="transaksi.php" method="POST" onsubmit="return confirm('Konfirmasi pembayaran transaksi ini?');" class="inline">
                                                    <input type="hidden" name="order_id" value="<?= $order['id_pembelian'] ?>">
                                                    <input type="hidden" name="action" value="confirm">
                                                    <button type="submit" class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded text-[10px] font-bold tracking-wider uppercase transition">
                                                        KONFIRMASI
                                                    </button>
                                                </form>
                                                <form action="transaksi.php" method="POST" onsubmit="return confirm('Batalkan transaksi ini?');" class="inline">
                                                    <input type="hidden" name="order_id" value="<?= $order['id_pembelian'] ?>">
                                                    <input type="hidden" name="action" value="cancel">
                                                    <button type="submit" class="px-2.5 py-1 bg-rose-600 hover:bg-rose-700 text-white rounded text-[10px] font-bold tracking-wider uppercase transition">
                                                        BATALKAN
                                                    </button>
                                                </form>
                                            <?php elseif ($status === 'confirmed'): ?>
                                                <!-- If already confirmed, admin can still cancel/void -->
                                                <form action="transaksi.php" method="POST" onsubmit="return confirm('Batalkan & kembalikan stok kendaraan?');" class="inline">
                                                    <input type="hidden" name="order_id" value="<?= $order['id_pembelian'] ?>">
                                                    <input type="hidden" name="action" value="cancel">
                                                    <button type="submit" class="px-2.5 py-1 bg-slate-100 hover:bg-rose-600 hover:text-white rounded text-[10px] font-bold tracking-wider uppercase transition">
                                                        VOID / CANCEL
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="text-xs text-slate-400">Tidak ada aksi</span>
                                            <?php endif; ?>
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

</body>
</html>
