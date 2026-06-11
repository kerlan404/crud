<?php
require_once '../config/db.php';
require_once '../components/toast.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: ../admin/dashboard.php');
    } else {
        header('Location: ../index.php');
    }
    exit;
}

$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (!empty($email) && !empty($password)) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                if ($user['status'] === 'banned') {
                    $error_msg = 'Akun Anda diblokir oleh Admin.';
                } else {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = $user['role'];

                    set_toast('success', 'Selamat datang kembali, ' . $user['username'] . '!');
                    
                    if ($user['role'] === 'admin') {
                        header('Location: ../admin/dashboard.php');
                    } else {
                        header('Location: ../index.php');
                    }
                    exit;
                }
            } else {
                $error_msg = 'Email atau password salah.';
            }
        } catch (PDOException $e) {
            $error_msg = 'Terjadi kesalahan sistem. Coba lagi nanti.';
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
                        navy: {
                            50: '#f0f4f8',
                            400: '#334e68',
                            500: '#003087',
                            900: '#060f24',
                            950: '#030814',
                        },
                        zeta: {
                            500: '#CC0000',
                            600: '#990000',
                            glow: 'rgba(204, 0, 0, 0.4)',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap');
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #030814;
        }
        h1, h2, .brand-title {
            font-family: 'Outfit', sans-serif;
        }
        .glass-container {
            background: rgba(6, 15, 36, 0.7);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5), 
                        inset 0 1px 0 rgba(255, 255, 255, 0.1);
        }
        .glow-red {
            box-shadow: 0 0 30px var(--tw-color-zeta-glow);
        }
        .glow-input:focus {
            box-shadow: 0 0 15px rgba(0, 48, 135, 0.3);
            border-color: #003087;
        }
        .motor-bg {
            background-image: linear-gradient(to bottom, rgba(3, 8, 20, 0.8), rgba(3, 8, 20, 0.95)), 
                              url('https://images.unsplash.com/photo-1615887023516-9b6bcd559e87?q=80&w=1920&auto=format&fit=crop');
            background-size: cover;
            background-position: center;
        }
    </style>
</head>
<body class="motor-bg min-h-screen flex items-center justify-center p-4 relative overflow-hidden">
    
    <!-- Dynamic Glowing Ambient Lights -->
    <div class="absolute w-[600px] h-[600px] rounded-full bg-navy-500/10 -top-60 -left-60 blur-3xl pointer-events-none"></div>
    <div class="absolute w-[600px] h-[600px] rounded-full bg-zeta-500/10 -bottom-60 -right-60 blur-3xl pointer-events-none"></div>

    <div class="w-full max-w-[440px] glass-container rounded-2xl p-8 relative z-10 transition-all duration-300">
        <!-- Logo and Heading -->
        <div class="text-center mb-8">
            <a href="../index.php" class="inline-block group mb-3">
                <span class="text-3xl font-black tracking-widest text-white brand-title">
                    ZETA<span class="text-zeta-500 transition-colors duration-300 group-hover:text-white">MOTORS</span>
                </span>
            </a>
            <p class="text-slate-400 text-xs uppercase tracking-widest">Akses Area Keanggotaan</p>
        </div>

        <?php if (!empty($error_msg)): ?>
            <div class="mb-6 p-4 bg-rose-500/10 border-l-4 border-zeta-500 rounded text-rose-300 text-xs flex items-center gap-3">
                <svg class="w-5 h-5 flex-shrink-0 text-zeta-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                <span><?= htmlspecialchars($error_msg) ?></span>
            </div>
        <?php endif; ?>

        <!-- Form -->
        <form action="login.php" method="POST" class="space-y-6">
            <div class="space-y-2">
                <label for="email" class="block text-[10px] font-bold tracking-widest text-slate-400 uppercase">Alamat Email</label>
                <input type="email" name="email" id="email" required placeholder="name@domain.com"
                    class="w-full px-4 py-3 bg-slate-900/60 border border-slate-700/60 rounded-lg text-white placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-navy-500 focus:border-navy-500 glow-input transition duration-200 text-sm">
            </div>

            <div class="space-y-2">
                <label for="password" class="block text-[10px] font-bold tracking-widest text-slate-400 uppercase">Kata Sandi</label>
                <input type="password" name="password" id="password" required placeholder="••••••••"
                    class="w-full px-4 py-3 bg-slate-900/60 border border-slate-700/60 rounded-lg text-white placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-navy-500 focus:border-navy-500 glow-input transition duration-200 text-sm">
            </div>

            <button type="submit" 
                class="w-full py-3.5 bg-zeta-500 hover:bg-zeta-600 text-white font-bold tracking-widest text-xs uppercase rounded-lg shadow-lg hover:shadow-zeta-500/20 active:translate-y-px transition-all duration-200">
                Otorisasi Masuk
            </button>
        </form>

        <!-- Redirect options -->
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
</body>
</html>
