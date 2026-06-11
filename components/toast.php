<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * show_toast() — Renders a SweetAlert2 toast via PHP session flash
 * Dipanggil dari header.php setelah SweetAlert2 dimuat
 */
function show_toast() {
    if (isset($_SESSION['toast'])) {
        $type    = $_SESSION['toast']['type'];    // 'success' | 'error' | 'warning' | 'info'
        $msg     = addslashes($_SESSION['toast']['message']);
        unset($_SESSION['toast']);

        $icon = in_array($type, ['success','error','warning','info']) ? $type : 'info';

        echo "
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ZetaToast = Swal.mixin({
                toast: true,
                position: 'bottom-end',
                showConfirmButton: false,
                timer: 3500,
                timerProgressBar: true,
                customClass: {
                    popup: 'swal-zeta-popup',
                    title: 'swal-zeta-title'
                },
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer);
                    toast.addEventListener('mouseleave', Swal.resumeTimer);
                }
            });
            ZetaToast.fire({ icon: '{$icon}', title: '{$msg}' });
        });
        </script>
        ";
    }
}

function set_toast($type, $message) {
    $_SESSION['toast'] = [
        'type'    => $type,
        'message' => $message
    ];
}
?>
