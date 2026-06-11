<?php
// Active page detection helper
$current_page = basename($_SERVER['PHP_SELF']);

function is_active($page, $current) {
    return $page === $current ? 'bg-navy-900 text-white border-l-4 border-zeta-500' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900';
}
?>
<aside class="w-64 bg-white border-r border-slate-200 min-h-screen flex flex-col justify-between shrink-0">
    <div class="py-6">
        <!-- Brand Title -->
        <div class="px-6 mb-8 text-center sm:text-left">
            <a href="../index.php" class="inline-block">
                <span class="text-xl font-extrabold tracking-wider text-slate-800 brand-title">
                    ZETA<span class="text-zeta-500">ADMIN</span>
                </span>
            </a>
        </div>

        <!-- Navigation Links -->
        <nav class="space-y-1">
            <a href="dashboard.php" class="flex items-center gap-3 px-6 py-3 text-sm font-semibold transition <?= is_active('dashboard.php', $current_page) ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2v-4zM14 16a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2v-4z"></path></svg>
                <span>Dashboard KPI</span>
            </a>
            
            <a href="produk.php" class="flex items-center gap-3 px-6 py-3 text-sm font-semibold transition <?= is_active('produk.php', $current_page) ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                <span>Kelola Produk</span>
            </a>

            <a href="kategori.php" class="flex items-center gap-3 px-6 py-3 text-sm font-semibold transition <?= is_active('kategori.php', $current_page) ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                <span>Kelola Kategori</span>
            </a>

            <a href="brand.php" class="flex items-center gap-3 px-6 py-3 text-sm font-semibold transition <?= is_active('brand.php', $current_page) ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                <span>Kelola Brand</span>
            </a>

            <a href="transaksi.php" class="flex items-center gap-3 px-6 py-3 text-sm font-semibold transition <?= is_active('transaksi.php', $current_page) ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                <span>Kelola Transaksi</span>
            </a>

            <a href="user.php" class="flex items-center gap-3 px-6 py-3 text-sm font-semibold transition <?= is_active('user.php', $current_page) ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                <span>Kelola User</span>
            </a>
        </nav>
    </div>

    <!-- Admin Profile Section at bottom -->
    <div class="border-t border-slate-100 p-4 space-y-3">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-navy-500 rounded-full flex items-center justify-center font-bold text-white text-sm uppercase">
                <?= substr($_SESSION['username'], 0, 2) ?>
            </div>
            <div>
                <h5 class="text-xs font-bold text-slate-800"><?= htmlspecialchars($_SESSION['username']) ?></h5>
                <p class="text-[10px] font-mono text-slate-400">ADMINISTRATOR</p>
            </div>
        </div>
        <a href="profil.php" class="block w-full py-2 bg-slate-100 hover:bg-navy-500 hover:text-white text-slate-600 text-center font-bold text-xs rounded transition uppercase">
            EDIT PROFIL
        </a>
        <a href="../auth/logout.php" class="block w-full py-2 bg-slate-100 hover:bg-rose-50 hover:text-rose-700 text-slate-500 text-center font-bold text-xs rounded transition uppercase">
            KELUAR / LOGOUT
        </a>
    </div>
</aside>
