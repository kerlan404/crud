<?php
require_once '../config/db.php';
require_once '../components/toast.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Delay kecil agar overlay loading di client tampak sebentar
    usleep(400000); // 0.4 detik

    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (!empty($username) && !empty($email) && !empty($password) && !empty($confirm_password)) {
        if ($password !== $confirm_password) {
            $error_msg = 'Konfirmasi sandi tidak sesuai.';
        } else {
            try {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
                $stmt->execute([$email, $username]);
                if ($stmt->fetch()) {
                    $error_msg = 'Email atau Username sudah terdaftar.';
                } else {
                    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role, status) VALUES (?, ?, ?, 'user', 'active')");
                    $stmt->execute([$username, $email, $hashed_password]);

                    $new_id = $pdo->lastInsertId();
                    
                    // AUTO LOGIN
                    $_SESSION['user_id'] = $new_id;
                    $_SESSION['username'] = $username;
                    $_SESSION['email'] = $email;
                    $_SESSION['role'] = 'user';

                    set_toast('success', 'Registrasi sukses! Selamat datang, ' . $username . '!');
                    header('Location: loading.php');
                    exit;
                }
            } catch (PDOException $e) {
                $error_msg = 'Terjadi kesalahan sistem: ' . $e->getMessage();
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
    <title>Daftar Akun Baru | ZETA Motors</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        navy: {
                            550: '#003087',
                            900: '#060f24',
                            950: '#030814',
                        },
                        zeta: {
                            500: '#CC0000',
                            600: '#990000',
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
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5), inset 0 1px 0 rgba(255, 255, 255, 0.1);
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
        @keyframes spin-slow {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .animate-spin-slow {
            animation: spin-slow 1.2s linear infinite;
        }
    </style>
</head>
<body class="motor-bg min-h-screen flex items-center justify-center p-4 relative overflow-hidden">
    
    <!-- Loading Screen (Tire Animation) -->
    <div id="loading-overlay" class="fixed inset-0 z-50 bg-navy-950/90 backdrop-blur-md hidden flex flex-col items-center justify-center space-y-4">
        <!-- Motorcycle Tire/Wheel Spinning SVG -->
        <div class="relative w-20 h-20 flex items-center justify-center">
            <!-- Tire Outer border -->
            <div class="absolute inset-0 rounded-full border-[6px] border-dashed border-slate-700 animate-spin-slow"></div>
            <!-- Tire Inner Wheel Rim -->
            <svg class="w-16 h-16 text-zeta-500 animate-spin-slow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <circle cx="12" cy="12" r="8"></circle>
                <circle cx="12" cy="12" r="2" fill="currentColor"></circle>
                <line x1="12" y1="4" x2="12" y2="20"></line>
                <line x1="4" y1="12" x2="20" y2="12"></line>
                <line x1="6.34" y1="6.34" x2="17.66" y2="17.66"></line>
                <line x1="6.34" y1="17.66" x2="17.66" y2="6.34"></line>
            </svg>
        </div>
        <p class="text-white text-xs font-bold tracking-widest uppercase animate-pulse">Membuat Akun Zeta Anda...</p>
    </div>

    <!-- Glowing Background Lights -->
    <div class="absolute w-[600px] h-[600px] rounded-full bg-navy-550/10 -top-60 -left-60 blur-3xl pointer-events-none"></div>
    <div class="absolute w-[600px] h-[600px] rounded-full bg-zeta-500/10 -bottom-60 -right-60 blur-3xl pointer-events-none"></div>

    <div class="w-full max-w-[440px] glass-container rounded-2xl p-8 relative z-10">
        <div class="text-center mb-8">
            <a href="../index.php" class="inline-block group mb-3">
                <span class="text-3xl font-black tracking-widest text-white brand-title">
                    ZETA<span class="text-zeta-500 transition-colors duration-300 group-hover:text-white">MOTORS</span>
                </span>
            </a>
            <p class="text-slate-400 text-xs uppercase tracking-widest">Registrasi Akun Baru</p>
        </div>

        <?php if (!empty($error_msg)): ?>
            <div class="mb-6 p-4 bg-rose-500/10 border-l-4 border-zeta-500 rounded text-rose-300 text-xs flex items-center gap-3">
                <svg class="w-5 h-5 flex-shrink-0 text-zeta-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                <span><?= htmlspecialchars($error_msg) ?></span>
            </div>
        <?php endif; ?>

        <form action="register.php" method="POST" id="register-form" class="space-y-4">
            <div class="space-y-1.5">
                <label for="username" class="block text-[10px] font-bold tracking-widest text-slate-400 uppercase">Nama Lengkap</label>
                <input type="text" name="username" id="username" required placeholder="Ahmad Zaki"
                    class="w-full px-4 py-2.5 bg-slate-900/60 border border-slate-700/60 rounded-lg text-white placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-navy-550 focus:border-navy-550 glow-input transition duration-200 text-sm">
            </div>

            <div class="space-y-1.5">
                <label for="email" class="block text-[10px] font-bold tracking-widest text-slate-400 uppercase">Alamat Email</label>
                <input type="email" name="email" id="email" required placeholder="name@domain.com"
                    class="w-full px-4 py-2.5 bg-slate-900/60 border border-slate-700/60 rounded-lg text-white placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-navy-550 focus:border-navy-550 glow-input transition duration-200 text-sm">
            </div>

            <div class="space-y-1.5">
                <label for="password" class="block text-[10px] font-bold tracking-widest text-slate-400 uppercase">Kata Sandi</label>
                <input type="password" name="password" id="password" required placeholder="••••••••"
                    class="w-full px-4 py-2.5 bg-slate-900/60 border border-slate-700/60 rounded-lg text-white placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-navy-550 focus:border-navy-550 glow-input transition duration-200 text-sm">
            </div>

            <div class="space-y-1.5">
                <label for="confirm_password" class="block text-[10px] font-bold tracking-widest text-slate-400 uppercase">Konfirmasi Kata Sandi</label>
                <input type="password" name="confirm_password" id="confirm_password" required placeholder="••••••••"
                    class="w-full px-4 py-2.5 bg-slate-900/60 border border-slate-700/60 rounded-lg text-white placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-navy-550 focus:border-navy-500 glow-input transition duration-200 text-sm">
            </div>

            <button type="submit" 
                class="w-full py-3 bg-zeta-500 hover:bg-zeta-600 text-white font-bold tracking-widest text-xs uppercase rounded-lg shadow-lg hover:shadow-zeta-500/20 active:translate-y-px transition-all duration-200 mt-4">
                Daftar Akun Baru
            </button>
        </form>

        <div class="mt-6 pt-4 border-t border-slate-800 text-center text-xs text-slate-400">
            Sudah terdaftar? <a href="login.php" class="text-zeta-500 font-bold hover:text-white transition duration-200">Masuk di Sini</a>
        </div>
    </div>

    <script>
        document.getElementById('register-form').addEventListener('submit', function() {
            document.getElementById('loading-overlay').classList.remove('hidden');
        });
    </script>
</body>
</html>
