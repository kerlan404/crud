<?php
require_once '../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Access Control: Must be a logged-in 'user'
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: ../auth/login.php');
    exit;
}

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$kode = isset($_GET['kode']) ? trim($_GET['kode']) : '';

$success_order = false;
$order_details = null;
$product = null;

if ($order_id > 0) {
    // Fetch order details directly for receipt view
    $stmt = $pdo->prepare("SELECT p.*, pr.nama_produk, pr.harga, pr.gambar, pr.kode_produk, b.nama_brand 
                            FROM pembelian p 
                            JOIN produk pr ON p.kode_produk = pr.kode_produk 
                            JOIN brand b ON pr.brand_id = b.id
                            WHERE p.id_pembelian = ? AND p.user_id = ?");
    $stmt->execute([$order_id, $_SESSION['user_id']]);
    $order_details = $stmt->fetch();
    
    if ($order_details) {
        $success_order = true;
    } else {
        header('Location: riwayat.php');
        exit;
    }
} elseif (!empty($kode)) {
    // Fetch product details for new order
    $stmt = $pdo->prepare("SELECT p.*, k.nama_kategori, b.nama_brand 
                            FROM produk p 
                            JOIN kategori k ON p.kategori_id = k.id 
                            JOIN brand b ON p.brand_id = b.id 
                            WHERE p.kode_produk = ?");
    $stmt->execute([$kode]);
    $product = $stmt->fetch();

    if (!$product || $product['stok'] <= 0) {
        header('Location: ../index.php');
        exit;
    }
} else {
    header('Location: ../index.php');
    exit;
}

$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jumlah = (int)$_POST['jumlah'];
    $metode = trim($_POST['metode_pembayaran']);

    if ($jumlah > 0 && !empty($metode)) {
        if ($jumlah > $product['stok']) {
            $error_msg = 'Jumlah pembelian melebihi stok yang tersedia.';
        } else {
            try {
                // Generate unique code & calculate total
                $kode_unik = 0;
                $subtotal = $product['harga'] * $jumlah;
                
                if ($metode === 'GoPay' || $metode === 'DANA') {
                    $kode_unik = rand(100, 999);
                    $total_bayar = $subtotal + $kode_unik;
                } else {
                    // Bank Transfer uses Virtual Account - no random code needed on amount, but VA is generated
                    $total_bayar = $subtotal;
                }

                // Insert purchase record
                $stmt = $pdo->prepare("INSERT INTO pembelian (user_id, kode_produk, jumlah, total_bayar, metode_pembayaran, kode_unik, status) 
                                        VALUES (?, ?, ?, ?, ?, ?, 'pending')");
                $stmt->execute([
                    $_SESSION['user_id'],
                    $product['kode_produk'],
                    $jumlah,
                    $total_bayar,
                    $metode,
                    $kode_unik
                ]);
                
                $order_id = $pdo->lastInsertId();
                $success_order = true;
                
                // Fetch the newly created order details
                $stmt = $pdo->prepare("SELECT p.*, pr.nama_produk, pr.harga 
                                        FROM pembelian p 
                                        JOIN produk pr ON p.kode_produk = pr.kode_produk 
                                        WHERE p.id_pembelian = ?");
                $stmt->execute([$order_id]);
                $order_details = $stmt->fetch();
                
                require_once '../components/toast.php';
                set_toast('success', 'Transaksi berhasil dibuat! Silakan lakukan pembayaran.');
            } catch (PDOException $e) {
                $error_msg = 'Gagal memproses transaksi: ' . $e->getMessage();
            }
        }
    } else {
        $error_msg = 'Harap isi jumlah unit dan metode pembayaran dengan benar.';
    }
}

$page_title = $success_order ? "Instruksi Pembayaran" : "Form Pembelian";
require_once '../components/header.php';
?>

<div class="container mx-auto px-4 py-12 max-w-3xl animate-slideup">
    <?php if (!$success_order): ?>
        <!-- Checkout Form Page -->
        <div class="bg-white rounded-lg border border-slate-200 shadow-sm overflow-hidden">
            <div class="bg-navy-500 p-6 text-white">
                <h2 class="text-xl font-bold uppercase tracking-wide">FORM PEMBELIAN KENDARAAN</h2>
                <p class="text-xs text-navy-100">Lengkapi formulir di bawah untuk menyelesaikan pembelian Anda</p>
            </div>

            <div class="p-6 md:p-8 space-y-6">
                <!-- Vehicle Overview -->
                <div class="flex gap-4 p-4 bg-slate-50 border border-slate-200/80 rounded-md items-center">
                    <?php 
                    $img_path = $product['gambar'];
                    $img_src = file_exists('../uploads/produk/' . $img_path) ? '../uploads/produk/' . $img_path : 'https://images.unsplash.com/photo-1568772585407-9361f9bf3a87?q=80&w=600&auto=format&fit=crop';
                    ?>
                    <img src="<?= $img_src ?>" alt="<?= htmlspecialchars($product['nama_produk']) ?>" class="w-24 h-16 object-cover rounded border border-slate-200">
                    <div>
                        <h4 class="font-bold text-slate-800 text-sm uppercase"><?= htmlspecialchars($product['nama_produk']) ?></h4>
                        <p class="text-xs font-mono text-slate-400"><?= htmlspecialchars($product['kode_produk']) ?> | Brand: <?= htmlspecialchars($product['nama_brand']) ?></p>
                        <p class="text-xs text-slate-500 mt-1">Stok tersedia: <strong><?= $product['stok'] ?> Unit</strong></p>
                    </div>
                </div>

                <?php if (!empty($error_msg)): ?>
                    <div class="p-4 bg-rose-50 border-l-4 border-rose-600 rounded text-rose-800 text-sm flex items-center gap-2">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        <span><?= htmlspecialchars($error_msg) ?></span>
                    </div>
                <?php endif; ?>

                <form action="beli.php?kode=<?= urlencode($product['kode_produk']) ?>" method="POST" class="space-y-6">
                    <!-- Quantity Input -->
                    <div>
                        <label for="jumlah" class="block text-xs font-semibold tracking-wider text-slate-500 uppercase mb-2">Jumlah Unit (Maks. <?= $product['stok'] ?>)</label>
                        <input type="number" name="jumlah" id="jumlah" min="1" max="<?= $product['stok'] ?>" value="1" required
                            class="w-full px-4 py-2.5 rounded border border-slate-200 focus:outline-none focus:ring-2 focus:ring-navy-500 focus:border-navy-500 transition text-sm">
                    </div>

                    <!-- Payment Method Select -->
                    <div>
                        <label for="metode_pembayaran" class="block text-xs font-semibold tracking-wider text-slate-500 uppercase mb-2">Metode Pembayaran</label>
                        <select name="metode_pembayaran" id="metode_pembayaran" required
                            class="w-full px-4 py-2.5 rounded border border-slate-200 focus:outline-none focus:ring-2 focus:ring-navy-500 focus:border-navy-500 transition text-sm bg-white">
                            <option value="">-- Pilih Metode --</option>
                            <option value="GoPay">GoPay (Instan Transfer)</option>
                            <option value="DANA">DANA (Instan Transfer)</option>
                            <option value="Bank Transfer">Transfer Bank Virtual Account</option>
                        </select>
                    </div>

                    <!-- Dynamic pricing calculator card -->
                    <div class="p-4 border border-slate-200 rounded-md bg-slate-50/50 space-y-2 text-sm">
                        <div class="flex justify-between text-slate-500">
                            <span>Harga Satuan</span>
                            <span>Rp <span id="price-unit"><?= number_format($product['harga'], 0, ',', '.') ?></span></span>
                        </div>
                        <div class="flex justify-between text-slate-500">
                            <span>Jumlah Unit</span>
                            <span id="display-quantity">1</span>
                        </div>
                        <div class="flex justify-between font-black text-slate-900 border-t border-slate-200 pt-3 text-lg">
                            <span>Estimasi Total</span>
                            <span>Rp <span id="total-price"><?= number_format($product['harga'], 0, ',', '.') ?></span></span>
                        </div>
                    </div>

                    <div class="flex gap-4">
                        <a href="../detail.php?kode=<?= urlencode($product['kode_produk']) ?>" class="flex-1 py-3 border border-slate-300 hover:bg-slate-50 text-slate-700 font-bold text-center rounded transition text-xs tracking-wider uppercase">
                            KEMBALI
                        </a>
                        <button type="submit" class="flex-1 py-3 bg-zeta-500 hover:bg-zeta-600 text-white font-bold rounded shadow transition duration-200 btn-premium text-xs tracking-wider uppercase">
                            SUBMIT TRANSAKSI
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            const price = <?= $product['harga'] ?>;
            const qtyInput = document.getElementById('jumlah');
            const qtyDisplay = document.getElementById('display-quantity');
            const totalDisplay = document.getElementById('total-price');

            qtyInput.addEventListener('input', () => {
                let qty = parseInt(qtyInput.value) || 0;
                if(qty < 1) qty = 1;
                qtyDisplay.textContent = qty;
                
                const total = price * qty;
                totalDisplay.textContent = new Intl.NumberFormat('id-ID').format(total);
            });
        </script>

    <?php else: ?>
        <!-- Payment Instructions Page -->
        <div class="bg-white rounded-lg border border-slate-200 shadow-lg overflow-hidden">
            <div class="bg-emerald-600 p-6 text-white text-center">
                <span class="text-4xl block mb-2">🎉</span>
                <h2 class="text-xl font-bold uppercase tracking-wide">Pemesanan Anda Berhasil Dibuat</h2>
                <p class="text-xs text-emerald-100">Pesanan ID: #ZTA-<?= $order_details['id_pembelian'] ?> | Status: PENDING</p>
            </div>

            <div class="p-6 md:p-8 space-y-6">
                <!-- Payment Total Callout -->
                <div class="bg-slate-50 border border-slate-200 rounded p-6 text-center">
                    <p class="text-xs font-semibold uppercase text-slate-500 tracking-wider mb-2">Total yang Harus Dibayar</p>
                    <div class="text-3xl font-black text-zeta-500 font-mono">
                        Rp <?= number_format($order_details['total_bayar'], 0, ',', '.') ?>
                    </div>
                    <?php if ($order_details['kode_unik'] > 0): ?>
                        <p class="text-[11px] text-slate-400 mt-2">
                            *Sudah termasuk kode unik transfer: <strong>+Rp <?= $order_details['kode_unik'] ?></strong>. <br>
                            Pastikan Anda mentransfer nominal persis agar admin dapat memverifikasi lebih cepat.
                        </p>
                    <?php endif; ?>
                </div>

                <!-- Step-by-Step Payment Instructions -->
                <div class="space-y-4">
                    <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider">INSTRUKSI PEMBAYARAN</h3>
                    
                    <?php if ($order_details['metode_pembayaran'] === 'GoPay'): ?>
                        <div class="p-4 border border-slate-100 rounded-lg space-y-3 text-sm">
                            <div class="flex items-center gap-3">
                                <span class="px-2.5 py-1 bg-sky-100 text-sky-800 font-bold text-xs uppercase rounded">GoPay</span>
                                <span class="font-semibold text-slate-800">Transfer ke QR / Nomor GoPay</span>
                            </div>
                            <p class="text-slate-600 leading-relaxed text-xs">
                                1. Buka aplikasi Gojek / GoPay di smartphone Anda.<br>
                                2. Pilih menu **Kirim / Transfer**.<br>
                                3. Kirim ke nomor GoPay Resmi Zeta Motors: <strong>0812-3456-7890</strong> (A.N. ZETA MOTORS INDONESIA).<br>
                                4. Masukkan nominal transfer sebesar <strong>Rp <?= number_format($order_details['total_bayar'], 0, ',', '.') ?></strong>.<br>
                                5. Setelah berhasil, silakan simpan bukti pembayaran dan tunggu verifikasi Admin.
                            </p>
                        </div>
                    <?php elseif ($order_details['metode_pembayaran'] === 'DANA'): ?>
                        <div class="p-4 border border-slate-100 rounded-lg space-y-3 text-sm">
                            <div class="flex items-center gap-3">
                                <span class="px-2.5 py-1 bg-blue-100 text-blue-800 font-bold text-xs uppercase rounded">DANA</span>
                                <span class="font-semibold text-slate-800">Transfer ke Nomor DANA</span>
                            </div>
                            <p class="text-slate-600 leading-relaxed text-xs">
                                1. Buka aplikasi DANA di smartphone Anda.<br>
                                2. Pilih menu **Kirim ke Nomor Telepon**.<br>
                                3. Masukkan nomor DANA Resmi Zeta: <strong>0812-3456-7890</strong> (A.N. ZETA MOTORS INDONESIA).<br>
                                4. Masukkan nominal transfer sebesar <strong>Rp <?= number_format($order_details['total_bayar'], 0, ',', '.') ?></strong>.<br>
                                5. Selesaikan transaksi lalu simpan struk pembayaran Anda untuk pengecekan admin.
                            </p>
                        </div>
                    <?php else: ?>
                        <!-- Virtual Account Bank Transfer -->
                        <div class="p-4 border border-slate-100 rounded-lg space-y-3 text-sm">
                            <div class="flex items-center gap-3">
                                <span class="px-2.5 py-1 bg-indigo-100 text-indigo-800 font-bold text-xs uppercase rounded">Bank Transfer</span>
                                <span class="font-semibold text-slate-800">Virtual Account Mandiri / BCA</span>
                            </div>
                            <p class="text-slate-600 leading-relaxed text-xs">
                                1. Gunakan m-Banking atau ATM pilihan Anda.<br>
                                2. Pilih menu **Transfer ke Virtual Account**.<br>
                                3. Masukkan kode Virtual Account berikut: <strong>88019 <?= str_pad($order_details['id_pembelian'], 5, '0', STR_PAD_LEFT) ?></strong>.<br>
                                4. Konfirmasi nama tagihan: **ZETA MOTORS - #<?= $order_details['id_pembelian'] ?>**.<br>
                                5. Nominal otomatis terhitung <strong>Rp <?= number_format($order_details['total_bayar'], 0, ',', '.') ?></strong>. Lakukan pembayaran.<br>
                                6. Setelah transaksi sukses, status akan diperiksa manual oleh admin secara terjadwal.
                            </p>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="pt-6 border-t border-slate-100 flex gap-4">
                    <a href="../index.php" class="flex-1 py-3 border border-slate-300 hover:bg-slate-50 text-slate-700 font-bold text-center rounded transition text-xs tracking-wider uppercase">
                        KEMBALI KE KATALOG
                    </a>
                    <a href="riwayat.php" class="flex-1 py-3 bg-navy-500 hover:bg-navy-600 text-white font-bold text-center rounded shadow transition duration-200 btn-premium text-xs tracking-wider uppercase">
                        RIWAYAT PESANAN SAYA
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../components/footer.php'; ?>
