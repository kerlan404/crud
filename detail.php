<?php
require_once 'config/db.php';

$kode = isset($_GET['kode']) ? trim($_GET['kode']) : '';

if (empty($kode)) {
    header('Location: index.php');
    exit;
}

// Fetch product details
$stmt = $pdo->prepare("SELECT p.*, k.nama_kategori, b.nama_brand 
                        FROM produk p 
                        JOIN kategori k ON p.kategori_id = k.id 
                        JOIN brand b ON p.brand_id = b.id 
                        WHERE p.kode_produk = ?");
$stmt->execute([$kode]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: index.php');
    exit;
}

$page_title = $product['nama_produk'];
require_once 'components/header.php';
?>

<!-- Breadcrumb -->
<div class="bg-slate-100 py-3 border-b border-slate-200">
    <div class="container mx-auto px-4 text-xs font-semibold text-slate-500 tracking-wider">
        <a href="index.php" class="hover:text-navy-500 transition">BERANDA</a> 
        <span class="mx-2">&gt;</span> 
        <a href="index.php?kategori=<?= $product['kategori_id'] ?>#katalog" class="hover:text-navy-500 transition uppercase"><?= htmlspecialchars($product['nama_kategori']) ?></a> 
        <span class="mx-2">&gt;</span> 
        <span class="text-slate-800 uppercase"><?= htmlspecialchars($product['nama_produk']) ?></span>
    </div>
</div>

<!-- Main Details Section -->
<section class="py-12 bg-white">
    <div class="container mx-auto px-4 max-w-5xl">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
            <!-- Product Image with Zoom-on-Hover -->
            <div class="space-y-4">
                <div class="zoom-container rounded-lg border border-slate-200 bg-slate-50 aspect-video overflow-hidden flex items-center justify-center relative shadow-sm">
                    <?php 
                    $img_path = $product['gambar'];
                    $img_src = file_exists('uploads/produk/' . $img_path) ? 'uploads/produk/' . $img_path : 'https://images.unsplash.com/photo-1568772585407-9361f9bf3a87?q=80&w=600&auto=format&fit=crop';
                    ?>
                    <img src="<?= $img_src ?>" alt="<?= htmlspecialchars($product['nama_produk']) ?>" class="w-full h-full object-cover zoom-image">
                    
                    <?php if ($product['stok'] == 0): ?>
                        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center">
                            <span class="px-4 py-2 bg-zeta-500 text-white font-bold text-sm tracking-wider uppercase rounded-sm">
                                STOK HABIS
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="text-center text-xs text-slate-400">
                    *Arahkan kursor di atas gambar untuk memperbesar gambar
                </div>
            </div>

            <!-- Product Specs & Action -->
            <div class="flex flex-col justify-between">
                <div class="space-y-6">
                    <div>
                        <span class="px-2.5 py-1 bg-navy-500 text-white text-[10px] font-bold tracking-widest uppercase rounded mb-3 inline-block">
                            <?= htmlspecialchars($product['nama_brand']) ?>
                        </span>
                        <h1 class="text-3xl font-black text-slate-900 uppercase tracking-tight mb-2">
                            <?= htmlspecialchars($product['nama_produk']) ?>
                        </h1>
                        <p class="text-sm text-slate-500 font-mono uppercase">KODE PRODUK: <?= htmlspecialchars($product['kode_produk']) ?></p>
                    </div>

                    <div class="text-3xl font-black text-zeta-500">
                        Rp <?= number_format($product['harga'], 0, ',', '.') ?>
                    </div>

                    <!-- Specifications Table -->
                    <div class="border-t border-b border-slate-100 py-4">
                        <h3 class="text-xs font-semibold tracking-wider text-slate-500 uppercase mb-3">Spesifikasi Detail</h3>
                        <table class="w-full text-sm">
                            <tr class="border-b border-slate-50">
                                <td class="py-2 text-slate-500 w-1/3">Tipe Kendaraan</td>
                                <td class="py-2 font-semibold text-slate-800"><?= htmlspecialchars($product['tipe_barang']) ?></td>
                            </tr>
                            <tr class="border-b border-slate-50">
                                <td class="py-2 text-slate-500">Kategori Lini</td>
                                <td class="py-2 font-semibold text-slate-800"><?= htmlspecialchars($product['nama_kategori']) ?></td>
                            </tr>
                            <tr>
                                <td class="py-2 text-slate-500">Stok Tersedia</td>
                                <td class="py-2 font-semibold <?= $product['stok'] > 0 ? 'text-emerald-600' : 'text-rose-600' ?>">
                                    <?= $product['stok'] > 0 ? $product['stok'] . ' Unit' : 'Stok Habis' ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Purchase Button Area -->
                <div class="mt-8 pt-6">
                    <?php if ($product['stok'] > 0): ?>
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <?php if ($_SESSION['role'] === 'admin'): ?>
                                <button disabled class="w-full py-4 bg-slate-300 text-slate-500 font-bold tracking-wider text-sm rounded cursor-not-allowed uppercase">
                                    ADMIN TIDAK DAPAT MEMBELI
                                </button>
                            <?php else: ?>
                                <a href="user/beli.php?kode=<?= urlencode($product['kode_produk']) ?>" 
                                    class="w-full py-4 bg-zeta-500 hover:bg-zeta-600 text-white font-bold tracking-wider text-sm rounded block text-center shadow-lg transition duration-200 btn-premium uppercase">
                                    BELI SEKARANG
                                </a>
                            <?php endif; ?>
                        <?php else: ?>
                            <a href="auth/login.php" 
                                class="w-full py-4 bg-navy-500 hover:bg-navy-600 text-white font-bold tracking-wider text-sm rounded block text-center shadow-lg transition duration-200 btn-premium uppercase">
                                LOGIN UNTUK MEMBELI
                            </a>
                        <?php endif; ?>
                    <?php else: ?>
                        <button disabled class="w-full py-4 bg-slate-200 text-slate-400 font-bold tracking-wider text-sm rounded cursor-not-allowed uppercase">
                            STOK HABIS
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once 'components/footer.php'; ?>
