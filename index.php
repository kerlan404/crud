<?php
$page_title = "Katalog Otomotif Premium";
require_once 'config/db.php';
require_once 'components/header.php';

// Fetch categories & brands for filters
$categories = $pdo->query("SELECT * FROM kategori ORDER BY nama_kategori ASC")->fetchAll();
$brands = $pdo->query("SELECT * FROM brand ORDER BY nama_brand ASC")->fetchAll();

// Construct Query based on filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$cat_id = isset($_GET['kategori']) ? (int)$_GET['kategori'] : 0;
$brand_id = isset($_GET['brand']) ? (int)$_GET['brand'] : 0;

$query = "SELECT p.*, k.nama_kategori, b.nama_brand 
          FROM produk p 
          JOIN kategori k ON p.kategori_id = k.id 
          JOIN brand b ON p.brand_id = b.id 
          WHERE 1=1";
$params = [];

if ($search !== '') {
    $query .= " AND (p.nama_produk LIKE ? OR p.kode_produk LIKE ? OR p.tipe_barang LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($cat_id > 0) {
    $query .= " AND p.kategori_id = ?";
    $params[] = $cat_id;
}

if ($brand_id > 0) {
    $query .= " AND p.brand_id = ?";
    $params[] = $brand_id;
}

$query .= " ORDER BY p.nama_produk ASC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll();
?>

<!-- Hero / Slideshow Section -->
<section class="relative bg-navy-900 text-white min-h-[500px] flex items-center overflow-hidden">
    <!-- Overlay & Background -->
    <div class="absolute inset-0 bg-gradient-to-r from-navy-900 via-navy-900/80 to-transparent z-10"></div>
    <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('https://images.unsplash.com/photo-1558981806-ec527fa84c39?q=80&w=1920&auto=format&fit=crop');"></div>

    <div class="container mx-auto px-4 z-20 relative space-y-6 max-w-5xl">
        <span class="inline-block bg-zeta-500 text-white text-xs font-bold tracking-widest px-3 py-1.5 uppercase rounded-sm">
            ZETA PERFORMANCES
        </span>
        <h1 class="text-4xl md:text-6xl font-black tracking-tight leading-none uppercase">
            RASAKAN SENSASI<br>
            <span class="text-transparent bg-clip-text bg-gradient-to-r from-white to-slate-400">BERKENDARA PREMIUM</span>
        </h1>
        <p class="text-slate-300 max-w-xl text-base md:text-lg">
            Temukan jajaran lini motor sport, matic kelas atas, dan dual purpose terbaik yang dirancang untuk performa tanpa kompromi.
        </p>
        <div class="flex gap-4">
            <a href="#katalog" class="px-6 py-3 bg-zeta-500 hover:bg-zeta-600 text-white font-bold text-sm tracking-wider uppercase rounded transition btn-premium shadow-lg">
                JELAJAHI LINIEUP
            </a>
            <a href="#tentang" class="px-6 py-3 bg-white/10 hover:bg-white/20 border border-white/20 text-white font-bold text-sm tracking-wider uppercase rounded transition">
                TENTANG KAMI
            </a>
        </div>
    </div>
</section>

<!-- Filter & Search Bar Section -->
<section id="katalog" class="py-12 bg-white border-b border-slate-100">
    <div class="container mx-auto px-4">
        <h2 class="text-2xl font-extrabold tracking-tight text-navy-900 uppercase mb-8 flex items-center gap-3">
            <span class="w-1.5 h-8 bg-zeta-500"></span> LINIEUP KENDARAAN ZETA
        </h2>

        <!-- Filter Form -->
        <form action="index.php#katalog" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 p-5 bg-slate-50 border border-slate-200/60 rounded-lg">
            <!-- Search field -->
            <div class="space-y-1">
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider">Cari Motor</label>
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Nama, kode, atau tipe..."
                    class="w-full px-3 py-2 border border-slate-300 rounded text-sm focus:outline-none focus:ring-1 focus:ring-navy-500 focus:border-navy-500 bg-white">
            </div>

            <!-- Category Filter -->
            <div class="space-y-1">
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider">Kategori</label>
                <select name="kategori" class="w-full px-3 py-2 border border-slate-300 rounded text-sm focus:outline-none focus:ring-1 focus:ring-navy-500 focus:border-navy-500 bg-white">
                    <option value="0">Semua Kategori</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $cat_id == $cat['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['nama_kategori']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Brand Filter -->
            <div class="space-y-1">
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider">Brand</label>
                <select name="brand" class="w-full px-3 py-2 border border-slate-300 rounded text-sm focus:outline-none focus:ring-1 focus:ring-navy-500 focus:border-navy-500 bg-white">
                    <option value="0">Semua Brand</option>
                    <?php foreach ($brands as $brand): ?>
                        <option value="<?= $brand['id'] ?>" <?= $brand_id == $brand['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($brand['nama_brand']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Submit & Reset Buttons -->
            <div class="flex items-end gap-2">
                <button type="submit" class="flex-grow py-2 bg-navy-500 hover:bg-navy-600 text-white font-bold text-xs tracking-wider uppercase rounded transition btn-premium h-[38px]">
                    CARI
                </button>
                <?php if ($search !== '' || $cat_id > 0 || $brand_id > 0): ?>
                    <a href="index.php#katalog" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold text-xs tracking-wider uppercase rounded transition flex items-center justify-center h-[38px]">
                        RESET
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</section>

<!-- Product List Grid -->
<section class="py-12 bg-slate-50">
    <div class="container mx-auto px-4">
        <?php if (empty($products)): ?>
            <!-- Empty State -->
            <div class="text-center py-20 bg-white rounded-lg border border-dashed border-slate-300 max-w-lg mx-auto">
                <span class="text-5xl block mb-4">🏍️</span>
                <h3 class="text-lg font-bold text-slate-800">Motor Tidak Ditemukan</h3>
                <p class="text-slate-500 text-sm mt-1">Kami tidak menemukan motor yang sesuai dengan kriteria filter Anda.</p>
                <a href="index.php#katalog" class="mt-4 inline-block text-xs font-bold text-navy-500 hover:underline">Tampilkan semua produk</a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
                <?php foreach ($products as $prod): ?>
                    <article class="bg-white rounded overflow-hidden product-card flex flex-col">
                        <!-- Image Container with zoom effect -->
                        <div class="zoom-container h-[220px] bg-slate-100 relative">
                            <!-- Category Badge -->
                            <span class="absolute top-3 left-3 z-10 px-2 py-1 bg-navy-900/80 backdrop-blur text-white text-[10px] font-bold tracking-widest uppercase rounded">
                                <?= htmlspecialchars($prod['nama_kategori']) ?>
                            </span>

                            <?php 
                            $img_path = $prod['gambar'];
                            // Check if image is an uploaded file or a seeded image
                            $img_src = file_exists('uploads/produk/' . $img_path) ? 'uploads/produk/' . $img_path : 'https://images.unsplash.com/photo-1568772585407-9361f9bf3a87?q=80&w=600&auto=format&fit=crop';
                            ?>
                            <img src="<?= $img_src ?>" alt="<?= htmlspecialchars($prod['nama_produk']) ?>" class="w-full h-full object-cover zoom-image">
                            
                            <?php if ($prod['stok'] == 0): ?>
                                <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center">
                                    <span class="px-3 py-1.5 bg-zeta-500 text-white font-bold text-xs tracking-wider uppercase rounded-sm">
                                        STOK HABIS
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Card Content -->
                        <div class="p-5 flex-grow flex flex-col justify-between">
                            <div>
                                <span class="text-[10px] uppercase font-mono tracking-widest text-slate-400 block mb-1">
                                    <?= htmlspecialchars($prod['kode_produk']) ?> | <?= htmlspecialchars($prod['nama_brand']) ?>
                                </span>
                                <h3 class="font-bold text-lg text-slate-900 leading-tight mb-2 hover:text-navy-500 transition">
                                    <a href="detail.php?kode=<?= urlencode($prod['kode_produk']) ?>">
                                        <?= htmlspecialchars($prod['nama_produk']) ?>
                                    </a>
                                </h3>
                                <p class="text-xs text-slate-500"><?= htmlspecialchars($prod['tipe_barang']) ?></p>
                            </div>

                            <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between">
                                <span class="text-lg font-black text-zeta-500">
                                    Rp <?= number_format($prod['harga'], 0, ',', '.') ?>
                                </span>
                                <a href="detail.php?kode=<?= urlencode($prod['kode_produk']) ?>" 
                                    class="px-3.5 py-2 border border-navy-500 text-navy-500 hover:bg-navy-500 hover:text-white text-xs font-bold tracking-wider uppercase rounded transition">
                                    DETAIL
                                </a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- About Section -->
<section id="tentang" class="py-20 bg-navy-900 text-white">
    <div class="container mx-auto px-4 max-w-4xl text-center space-y-6">
        <h2 class="text-3xl md:text-4xl font-extrabold tracking-tight brand-title uppercase">
            ZETA<span class="text-zeta-500">MOTORS</span> INDONESIA
        </h2>
        <p class="text-slate-300 leading-relaxed max-w-2xl mx-auto text-sm md:text-base">
            Kami adalah penyedia sepeda motor premium berlisensi dengan kualitas global. Terinspirasi dari kecanggihan teknologi otomotif terkini, ZETA Motors berkomitmen memberikan kenyamanan berkendara serta proses kepemilikan kendaraan yang mudah melalui sistem digital yang terintegrasi penuh.
        </p>
        <div class="pt-6 grid grid-cols-1 sm:grid-cols-3 gap-6 text-center max-w-2xl mx-auto">
            <div class="p-4 border border-white/10 rounded bg-white/5">
                <span class="text-2xl font-black text-zeta-500 block mb-1">100%</span>
                <span class="text-xs uppercase tracking-wider text-slate-400">Produk Resmi</span>
            </div>
            <div class="p-4 border border-white/10 rounded bg-white/5">
                <span class="text-2xl font-black text-zeta-500 block mb-1">24 Jam</span>
                <span class="text-xs uppercase tracking-wider text-slate-400">Jaminan Transaksi</span>
            </div>
            <div class="p-4 border border-white/10 rounded bg-white/5">
                <span class="text-2xl font-black text-zeta-500 block mb-1">10k+</span>
                <span class="text-xs uppercase tracking-wider text-slate-400">Pengguna Aktif</span>
            </div>
        </div>
    </div>
</section>

<?php require_once 'components/footer.php'; ?>
