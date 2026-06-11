<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Calculate base url dynamically based on path depth
$script_path = $_SERVER['SCRIPT_NAME'];
if (strpos($script_path, '/user/') !== false || strpos($script_path, '/auth/') !== false || strpos($script_path, '/admin/') !== false) {
    $base_url = '../';
} else {
    $base_url = './';
}

require_once $base_url . 'components/toast.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? $page_title . " | ZETA Motors" : "ZETA Motors — Premium Automotive E-Commerce" ?></title>
    <!-- Tailwind CSS v3 via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        navy: {
                            50: '#f0f4f8',
                            100: '#d9e2ec',
                            500: '#003087', /* Yamaha Navy */
                            600: '#002569',
                            900: '#0b1b3d',
                        },
                        zeta: {
                            500: '#CC0000', /* ZETA Red */
                            600: '#a30000',
                        }
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="<?= $base_url ?>assets/css/style.css">
</head>
<body class="bg-slate-50 text-slate-900 min-h-screen flex flex-col">

    <!-- Sticky Navigation Bar -->
    <header class="sticky top-0 z-40 bg-navy-900/95 backdrop-blur-md border-b border-navy-900/10 text-white shadow-md">
        <nav class="container mx-auto px-4 py-4 flex items-center justify-between">
            <!-- Logo Section -->
            <a href="<?= $base_url ?>index.php" class="flex items-center gap-2 group">
                <span class="text-2xl font-extrabold tracking-wider text-white brand-title">
                    ZETA<span class="text-zeta-500 group-hover:text-white transition-colors duration-200">MOTORS</span>
                </span>
            </a>

            <!-- Navigation Links -->
            <div class="hidden md:flex items-center gap-8 text-sm font-semibold tracking-wide">
                <a href="<?= $base_url ?>index.php" class="hover:text-zeta-500 transition-colors duration-200">BERANDA</a>
                <a href="<?= $base_url ?>index.php#katalog" class="hover:text-zeta-500 transition-colors duration-200">PRODUK</a>
                <a href="<?= $base_url ?>index.php#tentang" class="hover:text-zeta-500 transition-colors duration-200">TENTANG KAMI</a>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center gap-4">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <!-- Logged In State -->
                    <div class="flex items-center gap-3">
                        <span class="text-sm text-slate-300 hidden sm:inline">Halo, <strong class="text-white"><?= htmlspecialchars($_SESSION['username']) ?></strong></span>
                        
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                            <a href="<?= $base_url ?>admin/dashboard.php" class="px-4 py-2 bg-navy-500 hover:bg-navy-600 rounded text-xs font-bold tracking-wider transition duration-200">DASHBOARD ADMIN</a>
                        <?php else: ?>
                            <a href="<?= $base_url ?>user/riwayat.php" class="px-4 py-2 bg-navy-500 hover:bg-navy-600 rounded text-xs font-bold tracking-wider transition duration-200">PESANAN SAYA</a>
                        <?php endif; ?>

                        <a href="<?= $base_url ?>auth/logout.php" class="px-3 py-2 bg-transparent hover:bg-white/10 text-slate-300 hover:text-white border border-white/20 rounded text-xs font-bold transition duration-200">LOGOUT</a>
                    </div>
                <?php else: ?>
                    <!-- Logged Out State -->
                    <a href="<?= $base_url ?>auth/login.php" class="px-5 py-2 bg-zeta-500 hover:bg-zeta-600 rounded text-xs font-bold tracking-wider transition duration-200">MASUK / LOGIN</a>
                <?php endif; ?>
            </div>
        </nav>
    </header>

    <?php show_toast(); ?>
    <main class="flex-grow">
