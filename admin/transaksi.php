<?php
require_once '../config/db.php';
require_once '../components/toast.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

$error_msg = '';

// ── Handle status change ─────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = (int)($_POST['order_id'] ?? 0);
    $action   = $_POST['action'] ?? '';

    if ($order_id > 0 && !empty($action)) {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("SELECT p.*, pr.stok FROM pembelian p 
                                   JOIN produk pr ON p.kode_produk = pr.kode_produk 
                                   WHERE p.id_pembelian = ? FOR UPDATE");
            $stmt->execute([$order_id]);
            $order = $stmt->fetch();

            if (!$order) throw new Exception("Transaksi tidak ditemukan.");

            if ($action === 'confirm') {
                if ($order['status'] === 'confirmed') throw new Exception("Transaksi ini sudah dikonfirmasi.");
                if ($order['stok'] < $order['jumlah']) throw new Exception("Gagal Konfirmasi: Stok tidak mencukupi ({$order['stok']} unit).");

                $pdo->prepare("UPDATE produk SET stok = stok - ? WHERE kode_produk = ?")->execute([$order['jumlah'], $order['kode_produk']]);
                $pdo->prepare("UPDATE pembelian SET status = 'confirmed' WHERE id_pembelian = ?")->execute([$order_id]);
                set_toast('success', "Transaksi #ZTA-{$order_id} dikonfirmasi. Stok telah dikurangi.");

            } elseif ($action === 'cancel') {
                if ($order['status'] === 'cancelled') throw new Exception("Transaksi ini sudah dibatalkan.");
                if ($order['status'] === 'confirmed') {
                    $pdo->prepare("UPDATE produk SET stok = stok + ? WHERE kode_produk = ?")->execute([$order['jumlah'], $order['kode_produk']]);
                }
                $pdo->prepare("UPDATE pembelian SET status = 'cancelled' WHERE id_pembelian = ?")->execute([$order_id]);
                set_toast('success', "Transaksi #ZTA-{$order_id} berhasil dibatalkan.");
            }

            $pdo->commit();
            header('Location: transaksi.php' . (!empty($_GET['status']) ? '?status=' . urlencode($_GET['status']) : ''));
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error_msg = $e->getMessage();
        }
    }
}

// ── Export CSV ───────────────────────────────────────────────────────────────
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $query = "SELECT p.id_pembelian, u.username, u.email, pr.nama_produk, pr.kode_produk,
                     p.jumlah, p.total_bayar, p.metode_pembayaran, p.kode_unik,
                     p.status, p.tanggal_transaksi
              FROM pembelian p
              JOIN users u ON p.user_id = u.id
              JOIN produk pr ON p.kode_produk = pr.kode_produk
              ORDER BY p.tanggal_transaksi DESC";
    $rows = $pdo->query($query)->fetchAll();

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="transaksi_zeta_' . date('Ymd_His') . '.csv"');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8
    fputcsv($out, ['ID Pesanan','Username','Email','Produk','Kode Produk','Jumlah','Total Bayar','Metode','Kode Unik','Status','Tanggal']);
    foreach ($rows as $r) {
        fputcsv($out, [
            '#ZTA-'.$r['id_pembelian'], $r['username'], $r['email'],
            $r['nama_produk'], $r['kode_produk'], $r['jumlah'],
            $r['total_bayar'], $r['metode_pembayaran'], $r['kode_unik'],
            $r['status'], $r['tanggal_transaksi']
        ]);
    }
    fclose($out);
    exit;
}

// ── Fetch orders ─────────────────────────────────────────────────────────────
$status_filter = trim($_GET['status'] ?? '');
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

// Count per status for badges
$counts = $pdo->query("SELECT status, COUNT(*) as cnt FROM pembelian GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Transaksi | ZETA Motors</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config={theme:{extend:{colors:{navy:{50:'#f0f4f8',500:'#003087',900:'#0b1b3d'},zeta:{500:'#CC0000'}}}}}</script>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen flex">

    <?php require_once '../components/admin_sidebar.php'; ?>

    <main class="flex-grow p-6 md:p-10 space-y-8 overflow-y-auto max-h-screen">
        <header class="flex justify-between items-center border-b border-slate-200 pb-5">
            <div>
                <h1 class="text-3xl font-black text-slate-900 uppercase tracking-tight">KELOLA TRANSAKSI</h1>
                <p class="text-xs text-slate-500 font-medium">Verifikasi pembayaran masuk dan kelola status pembelian</p>
            </div>
            <a href="transaksi.php?export=csv"
               class="flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs tracking-wider uppercase rounded shadow transition cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                EXPORT CSV
            </a>
        </header>

        <?php show_toast(); ?>

        <?php if (!empty($error_msg)): ?>
            <div class="p-4 bg-rose-50 border-l-4 border-rose-600 rounded text-rose-800 text-sm flex items-center gap-2">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                <span><?= htmlspecialchars($error_msg) ?></span>
            </div>
        <?php endif; ?>

        <!-- Filter tabs with count badges -->
        <div class="flex gap-1 border-b border-slate-200 pb-px flex-wrap">
            <?php
            $tabs = [
                '' => ['label' => 'Semua', 'color' => 'navy'],
                'pending'   => ['label' => 'Pending',   'color' => 'amber'],
                'paid'      => ['label' => 'Paid',      'color' => 'blue'],
                'confirmed' => ['label' => 'Confirmed', 'color' => 'emerald'],
                'cancelled' => ['label' => 'Cancelled', 'color' => 'rose'],
            ];
            foreach ($tabs as $key => $tab):
                $active = $status_filter === $key;
                $colorMap = [
                    'navy'    => ['border-navy-500 text-navy-500',   'bg-navy-500 text-white'],
                    'amber'   => ['border-amber-500 text-amber-500',   'bg-amber-100 text-amber-800'],
                    'blue'    => ['border-blue-500 text-blue-500',     'bg-blue-100 text-blue-800'],
                    'emerald' => ['border-emerald-500 text-emerald-500','bg-emerald-100 text-emerald-800'],
                    'rose'    => ['border-rose-500 text-rose-500',     'bg-rose-100 text-rose-800'],
                ];
                [$activeClass, $badgeClass] = $colorMap[$tab['color']];
                $cnt = $key ? ($counts[$key] ?? 0) : array_sum($counts ?? [0]);
            ?>
            <a href="transaksi.php<?= $key ? '?status='.$key : '' ?>"
               class="flex items-center gap-1.5 px-4 py-2 text-xs font-bold tracking-wider uppercase border-b-2 transition
                      <?= $active ? $activeClass : 'border-transparent text-slate-400 hover:text-slate-600' ?>">
                <?= $tab['label'] ?>
                <span class="px-1.5 py-0.5 rounded-full text-[9px] font-black <?= $active ? $badgeClass : 'bg-slate-100 text-slate-500' ?>">
                    <?= $cnt ?>
                </span>
            </a>
            <?php endforeach; ?>
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
                            <th class="p-4">Jml</th>
                            <th class="p-4">Total Tagihan</th>
                            <th class="p-4">Metode</th>
                            <th class="p-4">Tanggal</th>
                            <th class="p-4 text-center">Status</th>
                            <th class="p-4 text-center">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                        <?php if (empty($orders)): ?>
                            <tr><td colspan="9" class="p-8 text-center text-slate-400">Belum ada transaksi.</td></tr>
                        <?php else: ?>
                            <?php foreach ($orders as $order):
                                $status = $order['status'];
                                $badgeMap = [
                                    'pending'   => 'bg-amber-100 text-amber-800',
                                    'paid'      => 'bg-blue-100 text-blue-800',
                                    'confirmed' => 'bg-emerald-100 text-emerald-800',
                                    'cancelled' => 'bg-rose-100 text-rose-800',
                                ];
                                $badge = $badgeMap[$status] ?? 'bg-slate-100 text-slate-600';
                                $oid = $order['id_pembelian'];
                            ?>
                            <!-- Hidden action forms -->
                            <form id="f-confirm-<?= $oid ?>" action="transaksi.php" method="POST" style="display:none">
                                <input type="hidden" name="order_id" value="<?= $oid ?>">
                                <input type="hidden" name="action" value="confirm">
                            </form>
                            <form id="f-cancel-<?= $oid ?>" action="transaksi.php" method="POST" style="display:none">
                                <input type="hidden" name="order_id" value="<?= $oid ?>">
                                <input type="hidden" name="action" value="cancel">
                            </form>

                            <tr class="hover:bg-slate-50/50 transition">
                                <td class="p-4 font-mono font-bold text-slate-800">#ZTA-<?= $oid ?></td>
                                <td class="p-4 font-semibold text-slate-700"><?= htmlspecialchars($order['username']) ?></td>
                                <td class="p-4">
                                    <span class="font-bold text-slate-900 uppercase"><?= htmlspecialchars($order['nama_produk']) ?></span>
                                    <span class="block text-[10px] text-slate-400 font-mono"><?= htmlspecialchars($order['kode_produk']) ?> | <?= htmlspecialchars($order['nama_brand']) ?></span>
                                </td>
                                <td class="p-4 font-semibold"><?= $order['jumlah'] ?> Unit</td>
                                <td class="p-4 font-bold">Rp <?= number_format($order['total_bayar'], 0, ',', '.') ?>
                                    <?php if ($order['kode_unik'] > 0): ?>
                                        <span class="block text-[10px] text-slate-400 font-mono">+<?= $order['kode_unik'] ?> kode unik</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-4"><span class="px-2 py-0.5 rounded text-xs font-semibold bg-slate-100 text-slate-700"><?= htmlspecialchars($order['metode_pembayaran']) ?></span></td>
                                <td class="p-4 text-xs text-slate-500 font-medium"><?= date('d M Y, H:i', strtotime($order['tanggal_transaksi'])) ?></td>
                                <td class="p-4 text-center">
                                    <span class="px-2.5 py-1 rounded-full text-xs font-bold tracking-wide uppercase <?= $badge ?>">
                                        <?= $status ?>
                                    </span>
                                </td>
                                <td class="p-4 text-center">
                                    <div class="flex justify-center gap-2">
                                        <?php if ($status === 'pending' || $status === 'paid'): ?>
                                            <button type="button"
                                                onclick="confirmAction('confirm', <?= $oid ?>, '<?= htmlspecialchars($order['nama_produk'], ENT_QUOTES) ?>')"
                                                class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded text-[10px] font-bold uppercase transition cursor-pointer">
                                                KONFIRMASI
                                            </button>
                                            <button type="button"
                                                onclick="confirmAction('cancel', <?= $oid ?>, '<?= htmlspecialchars($order['nama_produk'], ENT_QUOTES) ?>')"
                                                class="px-2.5 py-1 bg-rose-600 hover:bg-rose-700 text-white rounded text-[10px] font-bold uppercase transition cursor-pointer">
                                                BATALKAN
                                            </button>
                                        <?php elseif ($status === 'confirmed'): ?>
                                            <button type="button"
                                                onclick="confirmAction('cancel', <?= $oid ?>, '<?= htmlspecialchars($order['nama_produk'], ENT_QUOTES) ?>')"
                                                class="px-2.5 py-1 bg-slate-100 hover:bg-rose-600 hover:text-white rounded text-[10px] font-bold uppercase transition cursor-pointer">
                                                VOID / CANCEL
                                            </button>
                                        <?php else: ?>
                                            <span class="text-xs text-slate-400">—</span>
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

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function confirmAction(type, id, produk) {
            if (type === 'confirm') {
                Swal.fire({
                    title: 'KONFIRMASI PEMBAYARAN?',
                    html: `Transaksi <strong>#ZTA-${id}</strong> untuk <strong>${produk}</strong> akan dikonfirmasi.<br>
                           <small style="color:#94a3b8">Stok kendaraan akan dikurangi secara otomatis.</small>`,
                    icon: 'question',
                    iconColor: '#059669',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Konfirmasi',
                    cancelButtonText: 'Batal',
                    confirmButtonColor: '#059669',
                    cancelButtonColor: '#64748b',
                    reverseButtons: true,
                    customClass: { popup: 'swal-zeta-popup', title: 'swal-zeta-title', confirmButton: 'swal-zeta-confirm', cancelButton: 'swal-zeta-cancel' }
                }).then(r => {
                    if (r.isConfirmed) {
                        Swal.fire({ title: 'Memproses...', allowOutsideClick: false, showConfirmButton: false, didOpen: () => Swal.showLoading() });
                        document.getElementById('f-confirm-' + id).submit();
                    }
                });
            } else {
                Swal.fire({
                    title: 'BATALKAN TRANSAKSI?',
                    html: `Transaksi <strong>#ZTA-${id}</strong> akan dibatalkan.<br>
                           <small style="color:#94a3b8">Jika sudah confirmed, stok akan dikembalikan.</small>`,
                    icon: 'warning',
                    iconColor: '#CC0000',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Batalkan',
                    cancelButtonText: 'Tidak',
                    confirmButtonColor: '#CC0000',
                    cancelButtonColor: '#64748b',
                    reverseButtons: true,
                    customClass: { popup: 'swal-zeta-popup', title: 'swal-zeta-title', confirmButton: 'swal-zeta-confirm', cancelButton: 'swal-zeta-cancel' }
                }).then(r => {
                    if (r.isConfirmed) {
                        Swal.fire({ title: 'Memproses...', allowOutsideClick: false, showConfirmButton: false, didOpen: () => Swal.showLoading() });
                        document.getElementById('f-cancel-' + id).submit();
                    }
                });
            }
        }
    </script>
</body>
</html>
