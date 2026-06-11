<?php
/**
 * Database Auto-Initialization Script
 * Zeta Motors — v2
 */

$host = 'localhost';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    $sql_file = __DIR__ . '/database.sql';
    if (!file_exists($sql_file)) {
        throw new Exception("File database.sql tidak ditemukan.");
    }

    $sql = file_get_contents($sql_file);
    $pdo->exec($sql);

    echo "
    <!DOCTYPE html>
    <html lang='id'>
    <head>
        <meta charset='UTF-8'>
        <title>Database Setup | ZETA Motors</title>
        <script src='https://cdn.tailwindcss.com'></script>
        <link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css'>
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    </head>
    <body class='bg-slate-900 text-white min-h-screen flex items-center justify-center p-4'>
        <div class='max-w-md w-full bg-slate-800 rounded-xl p-8 border border-emerald-500/30 text-center shadow-2xl'>
            <div class='w-16 h-16 bg-emerald-600 rounded-full flex items-center justify-center mx-auto mb-4'>
                <svg class='w-8 h-8 text-white' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                    <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2.5' d='M5 13l4 4L19 7'/>
                </svg>
            </div>
            <h1 class='text-2xl font-black uppercase tracking-widest text-emerald-400 mb-2' style='font-family:Outfit,sans-serif'>SETUP BERHASIL</h1>
            <p class='text-slate-400 text-sm mb-6'>Database <strong class='text-white'>crud_ujian</strong> telah dibuat dan di-seed dengan data awal.</p>
            <div class='bg-slate-950 p-4 rounded-lg text-left text-xs font-mono text-slate-400 mb-6 border border-slate-700 space-y-1.5'>
                <p class='text-slate-500 uppercase tracking-widest text-[10px] mb-2'>Kredensial Demo</p>
                <p>Admin : <span class='text-emerald-400'>admin@gmail.com</span> / admin123</p>
                <p>User  : <span class='text-emerald-400'>user@example.com</span> / example</p>
            </div>
            <a href='auth/login.php' class='block w-full py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-bold uppercase tracking-wider text-sm transition shadow'>
                MASUK KE HALAMAN LOGIN
            </a>
            <a href='index.php' class='block w-full mt-2 py-3 bg-slate-700 hover:bg-slate-600 text-slate-300 rounded-lg font-bold uppercase tracking-wider text-sm transition'>
                LIHAT KATALOG
            </a>
        </div>
    </body>
    </html>
    ";

} catch (Exception $e) {
    echo "
    <!DOCTYPE html>
    <html lang='id'>
    <head>
        <meta charset='UTF-8'>
        <title>Database Setup Error | ZETA Motors</title>
        <script src='https://cdn.tailwindcss.com'></script>
    </head>
    <body class='bg-slate-900 text-white min-h-screen flex items-center justify-center p-4'>
        <div class='max-w-md w-full bg-slate-800 rounded-xl p-8 border border-rose-500/30 text-center shadow-2xl'>
            <div class='w-16 h-16 bg-rose-600 rounded-full flex items-center justify-center mx-auto mb-4'>
                <svg class='w-8 h-8 text-white' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                    <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2.5' d='M6 18L18 6M6 6l12 12'/>
                </svg>
            </div>
            <h1 class='text-2xl font-black uppercase tracking-widest text-rose-400 mb-2' style='font-family:Outfit,sans-serif'>SETUP GAGAL</h1>
            <p class='text-slate-400 text-sm mb-4'>Pastikan layanan MySQL (XAMPP / Laragon) sudah berjalan.</p>
            <div class='bg-slate-950 p-4 rounded-lg text-left text-xs font-mono text-rose-400 mb-6 overflow-x-auto border border-slate-700'>
                " . htmlspecialchars($e->getMessage()) . "
            </div>
            <a href='db_init.php' class='block w-full py-3 bg-rose-600 hover:bg-rose-700 text-white rounded-lg font-bold uppercase tracking-wider text-sm transition'>
                COBA LAGI
            </a>
        </div>
    </body>
    </html>
    ";
}
?>
