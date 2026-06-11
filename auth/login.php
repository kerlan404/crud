<?php
require_once '../config/db.php';
require_once '../components/toast.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id'])) {
    header($_SESSION['role'] === 'admin' ? 'Location: ../admin/dashboard.php' : 'Location: ../index.php');
    exit;
}

// ── Brute Force Protection Helper ──────────────────────────────────────────
function get_failed_attempts(PDO $pdo, string $email, string $ip): int {
    $window = date('Y-m-d H:i:s', time() - 15 * 60); // 15 menit
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM login_attempts WHERE (email = ? OR ip_address = ?) AND attempted_at > ?");
    $stmt->execute([$email, $ip, $window]);
    return (int)$stmt->fetchColumn();
}

function record_failed_attempt(PDO $pdo, string $email, string $ip): void {
    $stmt = $pdo->prepare("INSERT INTO login_attempts (email, ip_address) VALUES (?, ?)");
    $stmt->execute([$email, $ip]);
}

function clear_attempts(PDO $pdo, string $email): void {
    $pdo->prepare("DELETE FROM login_attempts WHERE email = ?")->execute([$email]);
}

// Pastikan tabel ada (fallback jika db_init belum dijalankan)
$pdo->exec("CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(255) NOT NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `attempted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_email_time (`email`, `attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$error_msg   = '';
$lockout_msg = '';
$client_ip   = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($email) && !empty($password)) {
        // Cek lockout sebelum query user
        $attempts = get_failed_attempts($pdo, $email, $client_ip);
        $max_attempts = 5;

        if ($attempts >= $max_attempts) {
            $lockout_msg = 'Terlalu banyak percobaan login. Silakan coba lagi dalam 15 menit.';
        } else {
            try {
                $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password'])) {
                    if ($user['status'] === 'banned') {
                        $error_msg = 'Akun Anda diblokir oleh Admin.';
                        record_failed_attempt($pdo, $email, $client_ip);
                    } else {
                        // Login sukses — bersihkan attempts
                        clear_attempts($pdo, $email);

                        $_SESSION['user_id']  = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['email']    = $user['email'];
                        $_SESSION['role']     = $user['role'];

                        set_toast('success', 'Selamat datang kembali, ' . $user['username'] . '!');
                        header('Location: loading.php');
                        exit;
                    }
                } else {
                    record_failed_attempt($pdo, $email, $client_ip);
                    $remaining = $max_attempts - get_failed_attempts($pdo, $email, $client_ip);
                    if ($remaining <= 0) {
                        $lockout_msg = 'Terlalu banyak percobaan login. Silakan coba lagi dalam 15 menit.';
                    } else {
                        $error_msg = 'Email atau password salah. Sisa percobaan: ' . max(0, $remaining);
                    }
                }
            } catch (PDOException $e) {
                $error_msg = 'Terjadi kesalahan sistem. Coba lagi nanti.';
            }
        }
    } else {
        $error_msg = 'Silakan isi semua field.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk Ke Akun | ZETA Motors</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        navy:  { 50:'#f0f4f8',400:'#334e68',500:'#003087',900:'#060f24',950:'#030814' },
                        zeta:  { 500:'#CC0000',600:'#990000' }
                    }
                }
            }
        }
    </script>
    <!-- SweetAlert2 -->
    <link  rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #030814; }
        h1, h2, .brand-title { font-family: 'Outfit', sans-serif; }
        .glass-container {
            background: rgba(6, 15, 36, 0.7);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.08);
            box-shadow: 0 20px 50px rgba(0,0,0,0.5), inset 0 1px 0 rgba(255,255,255,0.1);
        }
        .glow-input:focus { box-shadow: 0 0 15px rgba(0,48,135,0.3); border-color: #003087; }
        .motor-bg {
            background-image: linear-gradient(to bottom, rgba(3,8,20,0.8), rgba(3,8,20,0.95)),
                              url('https://images.unsplash.com/photo-1615887023516-9b6bcd559e87?q=80&w=1920&auto=format&fit=crop');
            background-size: cover; background-position: center;
        }
    </style>
</head>
<body class="motor-bg min-h-screen flex items-center justify-center p-4 relative overflow-hidden">

    <div class="absolute w-[600px] h-[600px] rounded-full bg-navy-500/10 -top-60 -left-60 blur-3xl pointer-events-none"></div>
    <div class="absolute w-[600px] h-[600px] rounded-full bg-zeta-500/10 -bottom-60 -right-60 blur-3xl pointer-events-none"></div>

    <div class="w-full max-w-[440px] glass-container rounded-2xl p-8 relative z-10">
        <div class="text-center mb-8">
            <a href="../index.php" class="inline-block group mb-3">
                <span class="text-3xl font-black tracking-widest text-white brand-title">
                    ZETA<span class="text-zeta-500 transition-colors duration-300 group-hover:text-white">MOTORS</span>
                </span>
            </a>
            <p class="text-slate-400 text-xs uppercase tracking-widest">Akses Area Keanggotaan</p>
        </div>

        <?php if (!empty($lockout_msg)): ?>
            <div class="mb-6 p-4 bg-amber-500/10 border-l-4 border-amber-500 rounded text-amber-300 text-xs flex items-center gap-3">
                <svg class="w-5 h-5 flex-shrink-0 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m0 0v2m0-2h2m-2 0H10m2-8v4m0 0a9 9 0 110 0z"/></svg>
                <span><?= htmlspecialchars($lockout_msg) ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_msg)): ?>
            <div class="mb-6 p-4 bg-rose-500/10 border-l-4 border-zeta-500 rounded text-rose-300 text-xs flex items-center gap-3">
                <svg class="w-5 h-5 flex-shrink-0 text-zeta-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                <span><?= htmlspecialchars($error_msg) ?></span>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST" id="login-form" class="space-y-6">
            <div class="space-y-2">
                <label for="email" class="block text-[10px] font-bold tracking-widest text-slate-400 uppercase">Alamat Email</label>
                <input type="email" name="email" id="email" required placeholder="name@domain.com"
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                    class="w-full px-4 py-3 bg-slate-900/60 border border-slate-700/60 rounded-lg text-white placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-navy-500 focus:border-navy-500 glow-input transition duration-200 text-sm">
            </div>
            <div class="space-y-2">
                <label for="password" class="block text-[10px] font-bold tracking-widest text-slate-400 uppercase">Kata Sandi</label>
                <div class="relative">
                    <input type="password" name="password" id="password" required placeholder="••••••••"
                        class="w-full px-4 py-3 bg-slate-900/60 border border-slate-700/60 rounded-lg text-white placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-navy-500 focus:border-navy-500 glow-input transition duration-200 text-sm pr-10">
                    <button type="button" id="toggle-password" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-300 transition">
                        <svg id="eye-show" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        <svg id="eye-hide" class="w-4 h-4 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                    </button>
                </div>
            </div>

            <button type="submit" id="btn-login"
                class="w-full py-3.5 bg-zeta-500 hover:bg-zeta-600 text-white font-bold tracking-widest text-xs uppercase rounded-lg shadow-lg active:translate-y-px transition-all duration-200 flex items-center justify-center gap-2"
                <?= !empty($lockout_msg) ? 'disabled' : '' ?>>
                <span id="btn-text">Otorisasi Masuk</span>
                <svg id="btn-spinner" class="hidden w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                </svg>
            </button>
        </form>

        <div class="mt-8 pt-6 border-t border-slate-800 text-center text-xs text-slate-400">
            Belum terdaftar? <a href="register.php" class="text-zeta-500 font-bold hover:text-white transition duration-200">Daftar Akun Baru</a>
        </div>

        <div class="mt-6 p-4 bg-slate-950/80 border border-slate-800 rounded-lg text-[10px] text-slate-500 space-y-1.5 font-mono">
            <div class="font-bold text-slate-400 uppercase tracking-wider mb-1">Kredensial Demo:</div>
            <div class="flex justify-between">
                <span>Admin: <strong class="text-slate-300">admin@gmail.com</strong></span>
                <span>Pass: <strong class="text-slate-300">admin123</strong></span>
            </div>
            <div class="flex justify-between">
                <span>User: <strong class="text-slate-300">user@example.com</strong></span>
                <span>Pass: <strong class="text-slate-300">example</strong></span>
            </div>
        </div>
    </div>

    <script>
        // Toggle password visibility
        document.getElementById('toggle-password').addEventListener('click', function() {
            const pwd = document.getElementById('password');
            const show = document.getElementById('eye-show');
            const hide = document.getElementById('eye-hide');
            if (pwd.type === 'password') {
                pwd.type = 'text';
                show.classList.add('hidden');
                hide.classList.remove('hidden');
            } else {
                pwd.type = 'password';
                show.classList.remove('hidden');
                hide.classList.add('hidden');
            }
        });

        // Loading spinner on submit
        document.getElementById('login-form').addEventListener('submit', function() {
            document.getElementById('btn-text').textContent = 'Memverifikasi...';
            document.getElementById('btn-spinner').classList.remove('hidden');
            document.getElementById('btn-login').disabled = true;
            document.getElementById('btn-login').classList.add('opacity-70', 'cursor-not-allowed');
        });

        <?php if (!empty($lockout_msg)): ?>
        // SweetAlert lockout warning
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'warning',
                iconColor: '#f59e0b',
                title: 'AKUN DIKUNCI',
                text: '<?= addslashes($lockout_msg) ?>',
                confirmButtonText: 'Mengerti',
                confirmButtonColor: '#003087',
                customClass: { popup: 'swal-zeta-popup', title: 'swal-zeta-title', confirmButton: 'swal-zeta-confirm' }
            });
        });
        <?php endif; ?>
    </script>
</body>
</html>
