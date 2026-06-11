<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Must be logged in to see this page
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$role = $_SESSION['role'];
$name = htmlspecialchars($_SESSION['username']);
$redirect = ($role === 'admin') ? '../admin/dashboard.php' : '../index.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Memuat... | ZETA Motors</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@700;900&family=Plus+Jakarta+Sans:wght@400;600&display=swap');

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #030814;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .brand-title { font-family: 'Outfit', sans-serif; }

        /* ─── SHARED ─── */
        .loading-wrap {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 28px;
            animation: fadeIn .4s ease forwards;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(14px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .greeting {
            font-family: 'Outfit', sans-serif;
            font-size: 1.5rem;
            font-weight: 900;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: #fff;
        }

        .greeting span { color: #CC0000; }

        .sub {
            font-size: .68rem;
            letter-spacing: .25em;
            text-transform: uppercase;
            color: #475569;
            margin-top: -20px;
        }

        /* ─── TIRE (user) ─── */
        .tire-scene {
            width: 120px;
            height: 120px;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        @keyframes roll { to { transform: rotate(360deg); } }
        @keyframes bounce-y {
            0%, 100% { transform: translateY(0); }
            50%       { transform: translateY(-8px); }
        }

        .tire-outer {
            position: absolute;
            inset: 0;
            border-radius: 50%;
            border: 12px solid #1e293b;
            animation: roll 1s linear infinite;
            /* tread marks */
            background: repeating-conic-gradient(#1e293b 0deg 8deg, #0f172a 8deg 16deg) border-box;
            border-color: transparent;
            -webkit-mask:
                linear-gradient(#fff 0 0) content-box,
                linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor;
                    mask-composite: exclude;
            padding: 12px;
        }

        /* Rim */
        .tire-rim {
            position: absolute;
            width: 70px;
            height: 70px;
            border-radius: 50%;
            border: 3px solid #334155;
            background: radial-gradient(circle at 35% 35%, #1e293b, #0f172a);
            animation: roll 1s linear infinite;
            display: flex; align-items: center; justify-content: center;
        }

        /* Spokes SVG inside rim */
        .tire-spokes {
            width: 50px;
            height: 50px;
            animation: roll 1s linear infinite reverse;
        }

        .tire-glow {
            position: absolute;
            inset: -8px;
            border-radius: 50%;
            background: transparent;
            box-shadow: 0 0 30px 6px rgba(204, 0, 0, .25);
            animation: pulse-glow 1.2s ease-in-out infinite;
        }

        @keyframes pulse-glow {
            0%, 100% { box-shadow: 0 0 20px 4px rgba(204,0,0,.2); }
            50%       { box-shadow: 0 0 45px 14px rgba(204,0,0,.4); }
        }

        .tire-wrap {
            animation: bounce-y 1.4s ease-in-out infinite;
        }

        /* road line under tire */
        .road {
            width: 140px;
            height: 3px;
            background: linear-gradient(90deg, transparent, #334155, #334155, transparent);
            border-radius: 2px;
            margin-top: -10px;
        }

        /* ─── SHIELD (admin) ─── */
        @keyframes shield-pulse {
            0%, 100% {
                filter: drop-shadow(0 0 8px rgba(0,48,135,.6));
                transform: scale(1);
            }
            50% {
                filter: drop-shadow(0 0 22px rgba(0,48,135,1));
                transform: scale(1.06);
            }
        }
        @keyframes shield-spin-in {
            0%   { opacity: 0; transform: rotateY(-90deg) scale(.6); }
            60%  { opacity: 1; transform: rotateY(8deg) scale(1.04); }
            100% { opacity: 1; transform: rotateY(0) scale(1); }
        }
        @keyframes scan-line {
            0%   { top: 10%; opacity: 0; }
            10%  { opacity: 1; }
            90%  { opacity: 1; }
            100% { top: 88%; opacity: 0; }
        }

        .shield-wrap {
            position: relative;
            width: 120px;
            height: 140px;
            animation: shield-spin-in .7s cubic-bezier(.22,1,.36,1) forwards,
                       shield-pulse 2.4s ease-in-out 0.7s infinite;
        }

        .shield-wrap svg {
            width: 100%;
            height: 100%;
        }

        .shield-scan {
            position: absolute;
            left: 18px;
            right: 18px;
            height: 2px;
            background: linear-gradient(90deg, transparent, rgba(99,179,237,.8), transparent);
            border-radius: 1px;
            animation: scan-line 2s linear 0.7s infinite;
        }

        /* ─── DOTS PROGRESS ─── */
        .dots {
            display: flex;
            gap: 8px;
        }

        .dots span {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #CC0000;
            animation: dot-bounce .9s ease-in-out infinite;
        }

        .dots span:nth-child(2) { animation-delay: .15s; background: #003087; }
        .dots span:nth-child(3) { animation-delay: .3s; }

        @keyframes dot-bounce {
            0%, 80%, 100% { transform: scale(0.6); opacity: .4; }
            40%            { transform: scale(1);   opacity: 1; }
        }

        /* ─── BACKGROUND BLOBS ─── */
        .blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            pointer-events: none;
        }
        .blob-1 { width: 500px; height: 500px; top: -200px; left: -200px;  background: rgba(0,48,135,.12); }
        .blob-2 { width: 500px; height: 500px; bottom: -200px; right: -200px; background: rgba(204,0,0,.08); }
    </style>
</head>
<body>

    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>

    <div class="loading-wrap">

        <!-- Brand -->
        <div style="text-align:center; line-height:1;">
            <div class="greeting">ZETA<span>MOTORS</span></div>
            <div class="sub"><?= $role === 'admin' ? 'Akses Administrator' : 'Portal Keanggotaan' ?></div>
        </div>

        <?php if ($role === 'user'): ?>
        <!-- ── USER: Spinning Motorcycle Tire ── -->
        <div class="tire-wrap">
            <div class="tire-scene">
                <div class="tire-glow"></div>
                <div class="tire-outer"></div>
                <div class="tire-rim">
                    <!-- 6-spoke wheel SVG -->
                    <svg class="tire-spokes" viewBox="0 0 50 50" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="25" cy="25" r="5" fill="#CC0000"/>
                        <!-- spokes -->
                        <line x1="25" y1="25" x2="25" y2="3"  stroke="#475569" stroke-width="2" stroke-linecap="round"/>
                        <line x1="25" y1="25" x2="25" y2="47" stroke="#475569" stroke-width="2" stroke-linecap="round"/>
                        <line x1="25" y1="25" x2="5"  y2="14" stroke="#475569" stroke-width="2" stroke-linecap="round"/>
                        <line x1="25" y1="25" x2="45" y2="36" stroke="#475569" stroke-width="2" stroke-linecap="round"/>
                        <line x1="25" y1="25" x2="5"  y2="36" stroke="#475569" stroke-width="2" stroke-linecap="round"/>
                        <line x1="25" y1="25" x2="45" y2="14" stroke="#475569" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </div>
            </div>
        </div>
        <div class="road"></div>
        <p style="color:#64748b; font-size:.65rem; letter-spacing:.2em; text-transform:uppercase;">
            Memasuki Katalog Motor...
        </p>

        <?php else: ?>
        <!-- ── ADMIN: Animated Shield ── -->
        <div class="shield-wrap">
            <div class="shield-scan"></div>
            <svg viewBox="0 0 100 120" fill="none" xmlns="http://www.w3.org/2000/svg">
                <!-- Shield body -->
                <path d="M50 5 L90 20 L90 60 C90 85 50 115 50 115 C50 115 10 85 10 60 L10 20 Z"
                      fill="url(#shield-grad)"
                      stroke="url(#border-grad)"
                      stroke-width="2.5"
                      stroke-linejoin="round"/>
                <!-- Inner line detail -->
                <path d="M50 14 L82 27 L82 60 C82 80 50 105 50 105 C50 105 18 80 18 60 L18 27 Z"
                      fill="none"
                      stroke="rgba(99,179,237,0.15)"
                      stroke-width="1"/>
                <!-- Checkmark / Lock icon -->
                <path d="M35 58 L46 70 L65 50"
                      stroke="white"
                      stroke-width="4"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      opacity=".9"/>
                <defs>
                    <linearGradient id="shield-grad" x1="50" y1="5" x2="50" y2="115" gradientUnits="userSpaceOnUse">
                        <stop offset="0%" stop-color="#003087"/>
                        <stop offset="100%" stop-color="#060f24"/>
                    </linearGradient>
                    <linearGradient id="border-grad" x1="0" y1="0" x2="100" y2="120" gradientUnits="userSpaceOnUse">
                        <stop offset="0%" stop-color="#3b82f6"/>
                        <stop offset="100%" stop-color="#1d4ed8"/>
                    </linearGradient>
                </defs>
            </svg>
        </div>
        <p style="color:#64748b; font-size:.65rem; letter-spacing:.2em; text-transform:uppercase;">
            Memverifikasi Otorisasi Admin...
        </p>
        <?php endif; ?>

        <!-- Dot progress -->
        <div class="dots">
            <span></span><span></span><span></span>
        </div>

        <p style="color:#1e3a5f; font-size:.6rem; letter-spacing:.15em; text-transform:uppercase;">
            Selamat Datang, <?= $name ?>
        </p>

    </div>

    <!-- Auto redirect after animation -->
    <script>
        setTimeout(function () {
            window.location.href = '<?= $redirect ?>';
        }, 2600);
    </script>
</body>
</html>
