<?php
$page_title = "Detail Pesanan";
require_once '../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: ../auth/login.php');
    exit;
}

require_once '../components/toast.php';

$order_id = (int)($_GET['id'] ?? 0);
if ($order_id <= 0) {
    header('Location: riwayat.php');
    exit;
}

$stmt = $pdo->prepare(
    "SELECT p.*, pr.nama_produk, pr.harga, pr.gambar, pr.kode_produk, pr.tipe_barang,
            pr.stok, k.nama_kategori, b.nama_brand, u.username, u.email
     FROM pembelian p
     JOIN produk pr  ON p.kode_produk = pr.kode_produk
     JOIN kategori k ON pr.kategori_id = k.id
     JOIN brand b    ON pr.brand_id = b.id
     JOIN users u    ON p.user_id = u.id
     WHERE p.id_pembelian = ? AND p.user_id = ?"
);
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: riwayat.php');
    exit;
}

$status = $order['status'];
$badgeMap = [
    'pending'   => ['bg-amber-100 text-amber-800',   'Menunggu Pembayaran'],
    'paid'      => ['bg-blue-100 text-blue-800',     'Sudah Dibayar'],
    'confirmed' => ['bg-emerald-100 text-emerald-800','Selesai / Confirmed'],
    'cancelled' => ['bg-rose-100 text-rose-800',     'Dibatalkan'],
];
[$badgeClass, $badgeLabel] = $badgeMap[$status] ?? ['bg-slate-100 text-slate-600', ucfirst($status)];

// Status steps
$steps = ['pending','paid','confirmed'];
$currentStep = array_search($status, $steps);

require_once '../components/header.php';
?>

<!-- Breadcrumb -->
<div class="bg-slate-100 py-3 border-b border-slate-200">
    <div class="container mx-auto px-4 text-xs font-semibold text-slate-500 tracking-wider">
        <a href="../index.php" class="hover:text-navy-500 transition">BERANDA</a>
        <span class="mx-2">&gt;</span>
        <a href="riwayat.php" class="hover:text-navy-500 transition">PESANAN SAYA</a>
        <span class="mx-2">&gt;</span>
        <span class="text-slate-800">#ZTA-<?= $order['id_pembelian'] ?></span>
    </div>
</div>

<div class="container mx-auto px-4 py-10 max-w-4xl animate-slideup">
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <span class="w-1.5 h-8 bg-zeta-500"></span>
            <div>
                <h1 class="text-2xl font-black text-slate-900 uppercase tracking-tight">Detail Pesanan</h1>
                <p class="text-xs text-slate-500 font-mono">#ZTA-<?= $order['id_pembelian'] ?> · <?= date('d M Y, H:i', strtotime($order['tanggal_transaksi'])) ?></p>
            </div>
        </div>
        <span class="px-3 py-1.5 rounded-full text-xs font-bold tracking-wide uppercase <?= $badgeClass ?>">
            <?= $badgeLabel ?>
        </span>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Kiri: Produk + Status tracker -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Produk -->
            <div class="bg-white rounded-lg border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/50">
                    <h3 class="text-sm font-extrabold text-slate-800 uppercase tracking-wider">Kendaraan Dipesan</h3>
                </div>
                <div class="p-5 flex gap-5 items-center">
                    <?php
                    $img_path = $order['gambar'];
                    $img_src  = file_exists('../uploads/produk/'.$img_path) ? '../uploads/produk/'.$img_path
                                : 'https://images.unsplash.com/photo-1568772585407-9361f9bf3a87?q=80&w=600&auto=format&fit=crop';
                    ?>
                    <img src="<?= $img_src ?>" alt="" class="w-28 h-20 object-cover rounded-lg border border-slate-200 flex-shrink-0">
                    <div class="flex-grow">
                        <span class="px-2 py-0.5 bg-navy-500 text-white text-[10px] font-bold uppercase rounded mb-2 inline-block"><?= htmlspecialchars($order['nama_brand']) ?></span>
                        <h4 class="font-black text-slate-900 text-base uppercase"><?= htmlspecialchars($order['nama_produk']) ?></h4>
                        <p class="text-xs text-slate-500 font-mono mt-0.5"><?= htmlspecialchars($order['kode_produk']) ?> · <?= htmlspecialchars($order['tipe_barang']) ?></p>
                        <p class="text-xs text-slate-500 mt-1">Kategori: <strong class="text-slate-700"><?= htmlspecialchars($order['nama_kategori']) ?></strong></p>
                    </div>
                </div>
            </div>

            <!-- Status Tracker (hanya untuk non-cancelled) -->
            <?php if ($status !== 'cancelled'): ?>
            <div class="bg-white rounded-lg border border-slate-200 shadow-sm p-5">
                <h3 class="text-sm font-extrabold text-slate-800 uppercase tracking-wider mb-5">Status Pesanan</h3>
                <div class="flex items-center gap-0">
                    <?php
                    $stepInfo = [
                        ['label' => 'Pesanan Dibuat', 'sub' => 'Status: Pending'],
                        ['label' => 'Pembayaran',     'sub' => 'Menunggu konfirmasi'],
                        ['label' => 'Selesai',        'sub' => 'Dikonfirmasi admin'],
                    ];
                    foreach ($stepInfo as $i => $step):
                        $done    = ($currentStep !== false && $i <= $currentStep);
                        $current = ($currentStep !== false && $i === $currentStep);
                    ?>
                    <div class="flex-1 flex flex-col items-center relative">
                        <?php if ($i < count($stepInfo)-1): ?>
                        <div class="absolute top-4 left-1/2 w-full h-0.5 <?= ($currentStep !== false && $i < $currentStep) ? 'bg-emerald-400' : 'bg-slate-200' ?>"></div>
                        <?php endif; ?>
                        <div class="w-8 h-8 rounded-full flex items-center justify-center z-10 text-xs font-black
                            <?= $done ? 'bg-emerald-500 text-white' : 'bg-slate-100 text-slate-400' ?>
                            <?= $current ? 'ring-2 ring-emerald-300 ring-offset-2' : '' ?>">
                            <?php if ($done && !$current): ?>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                            <?php else: ?>
                                <?= $i+1 ?>
                            <?php endif; ?>
                        </div>
                        <p class="text-[10px] font-bold text-center mt-2 <?= $done ? 'text-emerald-600' : 'text-slate-400' ?>"><?= $step['label'] ?></p>
                        <p class="text-[9px] text-slate-400 text-center"><?= $step['sub'] ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php else: ?>
            <div class="p-4 bg-rose-50 border border-rose-200 rounded-lg flex items-center gap-3 text-rose-700">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <div>
                    <p class="text-sm font-bold">Transaksi Dibatalkan</p>
                    <p class="text-xs">Pesanan ini telah dibatalkan oleh admin atau sistem.</p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Instruksi pembayaran jika pending -->
            <?php if ($status === 'pending'): ?>
            <div class="bg-white rounded-lg border border-amber-200 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-amber-100 bg-amber-50">
                    <h3 class="text-sm font-extrabold text-amber-800 uppercase tracking-wider flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Instruksi Pembayaran
                    </h3>
                </div>
                <div class="p-5 text-xs text-slate-600 space-y-2 leading-relaxed">
                    <?php if ($order['metode_pembayaran'] === 'GoPay'): ?>
                        <p>1. Buka aplikasi Gojek / GoPay di smartphone Anda.</p>
                        <p>2. Pilih menu <strong>Kirim / Transfer</strong>.</p>
                        <p>3. Transfer ke: <strong class="text-slate-800">0812-3456-7890</strong> (A.N. ZETA MOTORS INDONESIA)</p>
                        <p>4. Nominal: <strong class="text-zeta-500 text-base">Rp <?= number_format($order['total_bayar'],0,',','.') ?></strong></p>
                        <?php if ($order['kode_unik'] > 0): ?>
                            <p class="text-amber-600">⚠ Sudah termasuk kode unik <strong>+Rp <?= $order['kode_unik'] ?></strong>. Transfer nominal PERSIS.</p>
                        <?php endif; ?>
                    <?php elseif ($order['metode_pembayaran'] === 'DANA'): ?>
                        <p>1. Buka aplikasi DANA.</p>
                        <p>2. Pilih menu <strong>Kirim ke Nomor Telepon</strong>.</p>
                        <p>3. Kirim ke: <strong class="text-slate-800">0812-3456-7890</strong> (A.N. ZETA MOTORS INDONESIA)</p>
                        <p>4. Nominal: <strong class="text-zeta-500 text-base">Rp <?= number_format($order['total_bayar'],0,',','.') ?></strong></p>
                        <?php if ($order['kode_unik'] > 0): ?>
                            <p class="text-amber-600">⚠ Sudah termasuk kode unik <strong>+Rp <?= $order['kode_unik'] ?></strong>. Transfer nominal PERSIS.</p>
                        <?php endif; ?>
                    <?php else: ?>
                        <p>1. Buka m-Banking atau ATM pilihan Anda.</p>
                        <p>2. Pilih <strong>Transfer ke Virtual Account</strong>.</p>
                        <p>3. Kode VA: <strong class="text-slate-800 text-sm">88019 <?= str_pad($order['id_pembelian'],5,'0',STR_PAD_LEFT) ?></strong></p>
                        <p>4. Nominal: <strong class="text-zeta-500 text-base">Rp <?= number_format($order['total_bayar'],0,',','.') ?></strong></p>
                    <?php endif; ?>
                    <p class="pt-2 text-slate-400">Setelah transfer, tunggu konfirmasi manual dari admin (1×24 jam).</p>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Kanan: Ringkasan biaya -->
        <div class="space-y-4">
            <div class="bg-white rounded-lg border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/50">
                    <h3 class="text-sm font-extrabold text-slate-800 uppercase tracking-wider">Ringkasan Biaya</h3>
                </div>
                <div class="p-5 space-y-3 text-sm">
                    <div class="flex justify-between text-slate-600">
                        <span>Harga Satuan</span>
                        <span>Rp <?= number_format($order['harga'],0,',','.') ?></span>
                    </div>
                    <div class="flex justify-between text-slate-600">
                        <span>Jumlah Unit</span>
                        <span><?= $order['jumlah'] ?> Unit</span>
                    </div>
                    <?php if ($order['kode_unik'] > 0): ?>
                    <div class="flex justify-between text-slate-500 text-xs">
                        <span>Kode Unik</span>
                        <span>+Rp <?= number_format($order['kode_unik'],0,',','.') ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="flex justify-between font-black text-slate-900 text-base border-t border-slate-100 pt-3">
                        <span>Total Bayar</span>
                        <span class="text-zeta-500">Rp <?= number_format($order['total_bayar'],0,',','.') ?></span>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/50">
                    <h3 class="text-sm font-extrabold text-slate-800 uppercase tracking-wider">Info Pembayaran</h3>
                </div>
                <div class="p-5 space-y-2 text-xs text-slate-600">
                    <div class="flex justify-between">
                        <span class="text-slate-500">Metode</span>
                        <span class="font-semibold px-2 py-0.5 bg-slate-100 rounded"><?= htmlspecialchars($order['metode_pembayaran']) ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Tanggal Order</span>
                        <span class="font-semibold"><?= date('d/m/Y H:i', strtotime($order['tanggal_transaksi'])) ?></span>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="space-y-2">
                <a href="riwayat.php" class="block w-full py-2.5 border border-slate-300 hover:bg-slate-50 text-slate-700 font-bold text-xs tracking-wider uppercase rounded text-center transition cursor-pointer">
                    ← Kembali ke Riwayat
                </a>
                <a href="../index.php" class="block w-full py-2.5 bg-navy-500 hover:bg-navy-600 text-white font-bold text-xs tracking-wider uppercase rounded text-center transition btn-premium cursor-pointer">
                    Katalog Motor
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once '../components/footer.php'; ?>
