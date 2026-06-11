<?php
$page_title = "Riwayat Pesanan Saya";
require_once '../config/db.php';
require_once '../components/header.php';

// Access Control: Must be a logged-in 'user'
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: ../auth/login.php');
    exit;
}

// Fetch all orders of this user
$stmt = $pdo->prepare("SELECT p.*, pr.nama_produk, pr.harga, pr.gambar, pr.kode_produk, b.nama_brand
                        FROM pembelian p 
                        JOIN produk pr ON p.kode_produk = pr.kode_produk
                        JOIN brand b ON pr.brand_id = b.id
                        WHERE p.user_id = ? 
                        ORDER BY p.tanggal_transaksi DESC");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();
?>

<div class="container mx-auto px-4 py-12 max-w-5xl animate-slideup">
    <div class="space-y-6">
        <div class="flex items-center gap-3">
            <span class="w-1.5 h-8 bg-zeta-500"></span>
            <h1 class="text-3xl font-black text-slate-900 uppercase tracking-tight">RIWAYAT PESANAN SAYA</h1>
        </div>

        <?php if (empty($orders)): ?>
            <!-- Empty State -->
            <div class="text-center py-20 bg-white rounded-lg border border-slate-200 max-w-lg mx-auto shadow-sm">
                <span class="text-5xl block mb-4">🛒</span>
                <h3 class="text-lg font-bold text-slate-800">Belum Ada Transaksi</h3>
                <p class="text-slate-500 text-sm mt-1">Anda belum melakukan pembelian motor apa pun saat ini.</p>
                <a href="../index.php" class="mt-6 inline-block py-2.5 px-5 bg-navy-500 hover:bg-navy-600 text-white font-bold text-xs tracking-wider uppercase rounded transition btn-premium shadow">
                    Mulai Belanja
                </a>
            </div>
        <?php else: ?>
            <!-- Orders List Table -->
            <div class="bg-white rounded-lg border border-slate-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                <th class="p-4">No. Pesanan</th>
                                <th class="p-4">Kendaraan</th>
                                <th class="p-4">Jumlah</th>
                                <th class="p-4">Total Bayar</th>
                                <th class="p-4">Metode Bayar</th>
                                <th class="p-4">Tanggal</th>
                                <th class="p-4">Status</th>
                                <th class="p-4 text-center whitespace-nowrap">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm">
                            <?php foreach ($orders as $order): ?>
                                <tr class="hover:bg-slate-50/50 transition duration-150">
                                    <td class="p-4 font-mono font-bold text-slate-800">
                                        #ZTA-<?= $order['id_pembelian'] ?>
                                    </td>
                                    <td class="p-4">
                                        <div class="flex items-center gap-3">
                                            <?php 
                                            $img_path = $order['gambar'];
                                            $img_src = file_exists('../uploads/produk/' . $img_path) ? '../uploads/produk/' . $img_path : 'https://images.unsplash.com/photo-1568772585407-9361f9bf3a87?q=80&w=600&auto=format&fit=crop';
                                            ?>
                                            <img src="<?= $img_src ?>" alt="" class="w-12 h-8 object-cover rounded border border-slate-200">
                                            <div>
                                                <div class="font-bold text-slate-950 uppercase"><?= htmlspecialchars($order['nama_produk']) ?></div>
                                                <div class="text-[10px] text-slate-400 font-mono"><?= htmlspecialchars($order['kode_produk']) ?> | <?= htmlspecialchars($order['nama_brand']) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="p-4 font-semibold text-slate-700">
                                        <?= $order['jumlah'] ?> Unit
                                    </td>
                                    <td class="p-4 font-bold text-slate-900">
                                        Rp <?= number_format($order['total_bayar'], 0, ',', '.') ?>
                                    </td>
                                    <td class="p-4">
                                        <span class="px-2 py-0.5 rounded text-xs font-semibold bg-slate-100 text-slate-700">
                                            <?= htmlspecialchars($order['metode_pembayaran']) ?>
                                        </span>
                                    </td>
                                    <td class="p-4 text-xs text-slate-500 font-medium">
                                        <?= date('d M Y, H:i', strtotime($order['tanggal_transaksi'])) ?>
                                    </td>
                                    <td class="p-4">
                                        <?php 
                                        $status = $order['status'];
                                        if ($status === 'pending') {
                                            $badgeClass = 'bg-amber-100 text-amber-800';
                                            $labelText = 'Pending / Menunggu';
                                        } elseif ($status === 'paid') {
                                            $badgeClass = 'bg-blue-100 text-blue-800';
                                            $labelText = 'Paid / Dibayar';
                                        } elseif ($status === 'confirmed') {
                                            $badgeClass = 'bg-emerald-100 text-emerald-800';
                                            $labelText = 'Selesai / Confirmed';
                                        } else {
                                            $badgeClass = 'bg-rose-100 text-rose-800';
                                            $labelText = 'Dibatalkan';
                                        }
                                        ?>
                                        <span class="px-2.5 py-1 rounded-full text-xs font-bold tracking-wide <?= $badgeClass ?>">
                                            <?= $labelText ?>
                                        </span>
                                    </td>
                                    <td class="p-4 text-center whitespace-nowrap">
                                        <?php if ($status === 'pending'): ?>
                                            <a href="beli.php?order_id=<?= $order['id_pembelian'] ?>" class="inline-block px-3 py-1.5 bg-zeta-500 hover:bg-zeta-600 text-white rounded text-xs font-bold tracking-wider uppercase transition shadow-sm hover:shadow btn-premium">
                                                Instruksi Bayar
                                            </a>
                                        <?php else: ?>
                                            <span class="text-xs text-slate-400">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../components/footer.php'; ?>
