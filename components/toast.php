<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function show_toast() {
    if (isset($_SESSION['toast'])) {
        $type = $_SESSION['toast']['type']; // 'success' or 'error'
        $msg = $_SESSION['toast']['message'];
        unset($_SESSION['toast']);
        
        $bgColor = $type === 'success' ? 'bg-emerald-600' : 'bg-rose-600';
        $icon = $type === 'success' ? 
            '<svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>' : 
            '<svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>';
        
        echo "
        <div id='toast-notification' class='fixed bottom-5 right-5 z-50 transform translate-y-10 opacity-0 transition-all duration-300 ease-out'>
            <div class='flex items-center gap-3 px-5 py-3 rounded-lg shadow-xl {$bgColor} text-white font-medium'>
                {$icon}
                <span>" . htmlspecialchars($msg) . "</span>
                <button onclick='document.getElementById(\"toast-notification\").remove()' class='ml-4 hover:opacity-75 focus:outline-none'>
                    <svg class='w-4 h-4' fill='none" . "' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M6 18L18 6M6 6l12 12'></path></svg>
                </button>
            </div>
        </div>
        <script>
            setTimeout(() => {
                const toast = document.getElementById('toast-notification');
                if (toast) {
                    toast.classList.remove('translate-y-10', 'opacity-0');
                }
            }, 100);
            setTimeout(() => {
                const toast = document.getElementById('toast-notification');
                if (toast) {
                    toast.classList.add('translate-y-10', 'opacity-0');
                    setTimeout(() => toast.remove(), 300);
                }
            }, 4000);
        </script>
        ";
    }
}

function set_toast($type, $message) {
    $_SESSION['toast'] = [
        'type' => $type,
        'message' => $message
    ];
}
?>
