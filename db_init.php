<?php
/**
 * Database Auto-Initialization Script
 * Zeta Motors
 */

$host = 'localhost';
$user = 'root';
$pass = '';

try {
    // Connect without DB name to create it first if not exists
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // Read SQL file
    $sql_file = __DIR__ . '/database.sql';
    if (!file_exists($sql_file)) {
        throw new Exception("File database.sql tidak ditemukan.");
    }
    
    $sql = file_get_contents($sql_file);

    // Execute queries
    $pdo->exec($sql);

    echo "
    <!DOCTYPE html>
    <html lang='id'>
    <head>
        <meta charset='UTF-8'>
        <title>Database Setup | ZETA Motors</title>
        <script src='https://cdn.tailwindcss.com'></script>
    </head>
    <body class='bg-slate-900 text-white min-h-screen flex items-center justify-center p-4'>
        <div class='max-w-md w-full bg-slate-800 rounded-lg p-6 border border-emerald-500/30 text-center shadow-xl'>
            <span class='text-5xl block mb-4'>✅</span>
            <h1 class='text-2xl font-bold uppercase tracking-wide text-emerald-500 mb-2'>Setup Database Berhasil!</h1>
            <p class='text-slate-300 text-sm mb-6'>Database <strong>crud_ujian</strong> telah dibuat dan di-seed dengan kredensial terbaru.</p>
            <div class='bg-slate-950 p-4 rounded text-left text-xs font-mono text-slate-400 mb-6 space-y-1 border border-slate-700'>
                <div>Admin: admin@gmail.com / admin123</div>
                <div>User: user@example.com / example</div>
            </div>
            <a href='auth/login.php' class='block w-full py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded font-bold uppercase tracking-wider text-sm transition shadow'>
                MASUK KE HALAMAN LOGIN
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
        <div class='max-w-md w-full bg-slate-800 rounded-lg p-6 border border-rose-500/30 text-center shadow-xl'>
            <span class='text-5xl block mb-4'>❌</span>
            <h1 class='text-2xl font-bold uppercase tracking-wide text-rose-500 mb-2'>Setup Database Gagal!</h1>
            <p class='text-slate-300 text-sm mb-6'>Pastikan layanan MySQL (seperti XAMPP / Laragon) Anda sudah menyala.</p>
            <div class='bg-slate-950 p-4 rounded text-left text-xs font-mono text-rose-400 mb-6 overflow-x-auto border border-slate-700'>
                " . htmlspecialchars($e->getMessage()) . "
            </div>
            <a href='db_init.php' class='block w-full py-3 bg-rose-600 hover:bg-rose-700 text-white rounded font-bold uppercase tracking-wider text-sm transition shadow'>
                COBA LAGI
            </a>
        </div>
    </body>
    </html>
    ";
}
?>
