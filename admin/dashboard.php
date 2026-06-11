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

// ── KPI ──────────────────────────────────────────────────────────────────────
$total_users         = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_products      = $pdo->query("SELECT COUNT(*) FROM produk")->fetchColumn();
$low_stock           = $pdo->query("SELECT COUNT(*) FROM produk WHERE stok < 5")->fetchColumn();
$pending_transactions= $pdo->query("SELECT COUNT(*) FROM pembelian WHERE status = 'pending'")->fetchColumn();
$revenue             = $pdo->query("SELECT COALESCE(SUM(total_bayar),0) FROM pembelian WHERE status = 'confirmed'")->fetchColumn();

// ── 5 Latest Transactions ────────────────────────────────────────────────────
$latest_orders = $pdo->query(
    "SELECT p.*, u.username, pr.nama_produk
     FROM pembelian p
     JOIN users u  ON p.user_id = u.id
     JOIN produk pr ON p.kode_produk = pr.kode_produk
     ORDER BY p.tanggal_transaksi DESC LIMIT 5"
)->fetchAll();

// ── 5 Newest Users ────────────────────────────────────────────────────────────
$newest_users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5")->fetchAll();

// ── Chart Data: transaksi 30 hari terakhir ────────────────────────────────────
$chart_raw = $pdo->query(
    "SELECT DATE(tanggal_transaksi) as tgl, COUNT(*) as jumlah, SUM(total_bayar) as total
     FROM pembelian
     WHERE tanggal_transaksi >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
     GROUP BY DATE(tanggal_transaksi)
     ORDER BY tgl ASC"
)->fetchAll(PDO::FETCH_ASSOC);

// Build 30-day array
$chart_labels  = [];
$chart_count   = [];
$chart_revenue = [];
for ($i = 29; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("-{$i} days"));
    $chart_labels[] = date('d/m', strtotime($day));
    $chart_count[$day]   = 0;
    $chart_revenue[$day] = 0;
}
foreach ($chart_raw as $row) {
    if (isset($chart_count[$row['tgl']])) {
        $chart_count[$row['tgl']]   = (int)$row['jumlah'];
        $chart_revenue[$row['tgl']] = (float)$row['total'];
    }
}
$js_labels   = json_encode(array_values($chart_labels));
$js_count    = json_encode(array_values($chart_count));
$js_revenue  = json_encode(array_values($chart_revenue));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin | ZETA Motors</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config={theme:{extend:{colors:{navy:{50:'#f0f4f8',500:'#003087',900:'#0b1b3d'},zeta:{500:'#CC0000'}}}}}</script>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen flex">

    <?php require_once '../components/admin_sidebar.php'; ?>

    <main class="flex-grow p-6 md:p-10 space-y-8 overflow-y-auto max-h-screen">
        <header class="flex justify-between items-center border-b border-slate-200 pb-5">
            <div>
                <h1 class="text-3xl font-black text-slate-900 uppercase tracking-tight">DASHBOARD ANALYTICS</h1>
                <p class="text-xs text-slate-500 font-medium">Pemantauan data operasional Zeta Motors secara real-time</p>
            </div>
            <div class="text-xs font-semibold bg-white border border-slate-200 rounded px-4 py-2 text-slate-600 shadow-sm">
                Hari ini: <strong class="text-slate-800"><?= date('d F Y') ?></strong>
            </div>
        </header>

        <?php show_toast(); ?>

        <!-- KPI Cards -->
        <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6">
            <article class="bg-white p-5 rounded border border-slate-200/80 shadow-sm flex flex-col justify-between">
                <div>
                    <span class="text-[10px] font-mono tracking-widest text-slate-400 uppercase block mb-1">TOTAL PENDAPATAN</span>
                    <span class="text-xl font-black text-emerald-600 leading-none">Rp <?= number_format($revenue, 0, ',', '.') ?></span>
                </div>
                <p class="text-[10px] text-slate-400 mt-4 font-semibold">Berdasarkan transaksi 'confirmed'</p>
            </article>

            <article class="bg-white p-5 rounded border border-slate-200/80 shadow-sm flex flex-col justify-between">
                <div>
                    <span class="text-[10px] font-mono tracking-widest text-slate-400 uppercase block mb-1">TOTAL PENGGUNA</span>
                    <span class="text-3xl font-black text-slate-800 leading-none"><?= $total_users ?> <span class="text-xs font-medium text-slate-400">Akun</span></span>
                </div>
                <p class="text-[10px] text-slate-400 mt-4 font-semibold">User & admin terdaftar</p>
            </article>

            <article class="bg-white p-5 rounded border border-slate-200/80 shadow-sm flex flex-col justify-between">
                <div>
                    <span class="text-[10px] font-mono tracking-widest text-slate-400 uppercase block mb-1">TOTAL PRODUK</span>
                    <span class="text-3xl font-black text-slate-800 leading-none"><?= $total_products ?> <span class="text-xs font-medium text-slate-400">Barang</span></span>
                </div>
                <p class="text-[10px] text-slate-400 mt-4 font-semibold">Lini kendaraan di database</p>
            </article>

            <article class="bg-white p-5 rounded border border-slate-200/80 shadow-sm flex flex-col justify-between">
                <div>
                    <span class="text-[10px] font-mono tracking-widest text-slate-400 uppercase block mb-1">STOK MENIPIS</span>
                    <span class="text-3xl font-black <?= $low_stock > 0 ? 'text-amber-600' : 'text-slate-800' ?> leading-none"><?= $low_stock ?> <span class="text-xs font-medium text-slate-400">Produk</span></span>
                </div>
                <p class="text-[10px] text-slate-400 mt-4 font-semibold">Produk dengan stok &lt; 5 unit</p>
            </article>

            <article class="bg-white p-5 rounded border border-slate-200/80 shadow-sm flex flex-col justify-between">
                <div>
                    <span class="text-[10px] font-mono tracking-widest text-slate-400 uppercase block mb-1">PENDING TRANSAKSI</span>
                    <span class="text-3xl font-black <?= $pending_transactions > 0 ? 'text-rose-600' : 'text-slate-800' ?> leading-none"><?= $pending_transactions ?> <span class="text-xs font-medium text-slate-400">Menunggu</span></span>
                </div>
                <p class="text-[10px] text-slate-400 mt-4 font-semibold">Perlu tindakan verifikasi</p>
            </article>
        </section>

        <!-- Chart: Transaksi 30 Hari -->
        <section class="bg-white rounded border border-slate-200/80 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                <h3 class="text-sm font-extrabold text-slate-800 uppercase tracking-wider">Grafik Transaksi — 30 Hari Terakhir</h3>
                <div class="flex gap-4 text-[10px] font-semibold text-slate-500">
                    <span class="flex items-center gap-1.5"><span class="w-3 h-1 rounded bg-navy-500 inline-block"></span> Jumlah Order</span>
                    <span class="flex items-center gap-1.5"><span class="w-3 h-1 rounded bg-emerald-500 inline-block"></span> Revenue (Rp)</span>
                </div>
            </div>
            <div class="p-5">
                <canvas id="txChart" height="90"></canvas>
            </div>
        </section>

        <!-- Grid: Transaksi terbaru + User terbaru -->
        <section class="grid grid-cols-1 lg:grid-cols-3 gap-8">
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
                                <tr><td colspan="5" class="p-4 text-center text-slate-400">Belum ada transaksi saat ini.</td></tr>
                            <?php else: ?>
                                <?php foreach ($latest_orders as $o):
                                    $s = $o['status'];
                                    $b = $s==='pending'?'bg-amber-100 text-amber-800':($s==='paid'?'bg-blue-100 text-blue-800':($s==='confirmed'?'bg-emerald-100 text-emerald-800':'bg-rose-100 text-rose-800'));
                                ?>
                                <tr class="hover:bg-slate-50/50 transition">
                                    <td class="p-3 font-mono font-bold text-slate-800">#ZTA-<?= $o['id_pembelian'] ?></td>
                                    <td class="p-3 font-medium text-slate-700"><?= htmlspecialchars($o['username']) ?></td>
                                    <td class="p-3 font-semibold text-slate-800 uppercase"><?= htmlspecialchars($o['nama_produk']) ?></td>
                                    <td class="p-3 text-right font-bold">Rp <?= number_format($o['total_bayar'],0,',','.') ?></td>
                                    <td class="p-3 text-center"><span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase <?= $b ?>"><?= $s ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white rounded border border-slate-200/80 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                    <h3 class="text-sm font-extrabold text-slate-800 uppercase tracking-wider">5 User Terbaru</h3>
                    <a href="user.php" class="text-xs font-semibold text-navy-500 hover:underline">Lihat Semua</a>
                </div>
                <div class="divide-y divide-slate-100">
                    <?php if (empty($newest_users)): ?>
                        <div class="p-4 text-center text-xs text-slate-400">Belum ada pengguna.</div>
                    <?php else: ?>
                        <?php foreach ($newest_users as $u): ?>
                        <div class="p-4 flex items-center justify-between hover:bg-slate-50/50 transition">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-slate-100 rounded-full flex items-center justify-center font-bold text-slate-700 text-xs">
                                    <?= strtoupper(substr($u['username'],0,2)) ?>
                                </div>
                                <div>
                                    <h5 class="text-xs font-bold text-slate-800"><?= htmlspecialchars($u['username']) ?></h5>
                                    <p class="text-[10px] text-slate-400 font-mono"><?= htmlspecialchars($u['email']) ?></p>
                                </div>
                            </div>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase <?= $u['role']==='admin'?'bg-navy-500 text-white':'bg-slate-100 text-slate-600' ?>">
                                <?= $u['role'] ?>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Chart.js — Transaksi 30 hari
        const labels   = <?= $js_labels ?>;
        const counts   = <?= $js_count ?>;
        const revenues = <?= $js_revenue ?>;

        const ctx = document.getElementById('txChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Jumlah Order',
                        data: counts,
                        borderColor: '#003087',
                        backgroundColor: 'rgba(0,48,135,0.07)',
                        borderWidth: 2,
                        pointRadius: 3,
                        pointBackgroundColor: '#003087',
                        tension: 0.4,
                        fill: true,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Revenue (Rp)',
                        data: revenues,
                        borderColor: '#059669',
                        backgroundColor: 'rgba(5,150,105,0.06)',
                        borderWidth: 2,
                        pointRadius: 3,
                        pointBackgroundColor: '#059669',
                        tension: 0.4,
                        fill: true,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#0b1b3d',
                        titleFont: { family: 'Outfit', weight: 'bold', size: 12 },
                        bodyFont: { family: 'Inter', size: 11 },
                        padding: 12,
                        callbacks: {
                            label: ctx => {
                                if (ctx.datasetIndex === 1) {
                                    return ' Revenue: Rp ' + ctx.parsed.y.toLocaleString('id-ID');
                                }
                                return ' Order: ' + ctx.parsed.y + ' transaksi';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { color: 'rgba(0,0,0,0.04)' },
                        ticks: { font: { size: 9 }, maxRotation: 0 }
                    },
                    y: {
                        type: 'linear', position: 'left',
                        grid: { color: 'rgba(0,0,0,0.04)' },
                        ticks: { font: { size: 9 }, stepSize: 1 },
                        title: { display: true, text: 'Order', font: { size: 9 }, color: '#003087' }
                    },
                    y1: {
                        type: 'linear', position: 'right',
                        grid: { drawOnChartArea: false },
                        ticks: {
                            font: { size: 9 },
                            callback: v => 'Rp ' + (v/1000000).toFixed(1) + 'jt'
                        },
                        title: { display: true, text: 'Revenue', font: { size: 9 }, color: '#059669' }
                    }
                }
            }
        });
    </script>
</body>
</html>
