<?php
// Kode ditulis untuk PHP/XAMPP lama: matikan exception mysqli default PHP 8.1+
mysqli_report(MYSQLI_REPORT_OFF);

// DB eksternal (Vercel/TiDB) + session di database; tanpa env DB_HOST perilaku tetap seperti semula
if (is_file('/var/www/html/deploy/db_compat.php')) require_once '/var/www/html/deploy/db_compat.php';

// Demo portfolio: sisipkan kotak "Akun Demo" di halaman login (konfigurasi di deploy/demo_banner.php).
(function () {
    $cfgFile = '/var/www/html/deploy/demo_banner.php';
    if (getenv('DEMO_BANNER_CONFIG')) $cfgFile = getenv('DEMO_BANNER_CONFIG');
    if (!is_file($cfgFile)) return;
    $cfg = include $cfgFile;
    $page = basename($_SERVER['SCRIPT_NAME'] ?? '');
    if (!in_array($page, $cfg['pages'], true)) return;

    $rows = '';
    foreach ($cfg['accounts'] as [$role, $user, $pass]) {
        $rows .= '<tr><td>' . htmlspecialchars($role) . '</td><td><code>' . htmlspecialchars($user)
               . '</code></td><td><code>' . htmlspecialchars($pass) . '</code></td></tr>';
    }
    $note = isset($cfg['note']) ? '<p style="margin:8px 0 0;font-size:11.5px;color:#5C534C">' . $cfg['note'] . '</p>' : '';
    $banner = '<details id="demo-akun" style="position:fixed;right:16px;bottom:16px;z-index:9999;max-width:320px;'
        . 'background:#FFFFFF;border:1px solid #E8DFD3;border-radius:12px;box-shadow:0 10px 28px rgba(31,26,23,.12);'
        . 'padding:10px 14px;font:12.5px/1.45 \'Plus Jakarta Sans\',system-ui,-apple-system,sans-serif;color:#1F1A17;text-align:left">'
        . '<summary style="cursor:pointer;font-weight:600;color:#C8553D;list-style:none;display:flex;align-items:center;justify-content:space-between;gap:10px">'
        . '<span>Akun Demo</span><span style="font-size:11px;font-weight:500;color:#8A7F76">Klik untuk lihat</span></summary>'
        . '<table style="margin-top:8px;border-collapse:collapse;width:100%;font-size:12px">'
        . '<tr style="color:#8A7F76;border-bottom:1px solid #E8DFD3"><td style="padding:3px 0">Role</td><td style="padding:3px 6px">User</td><td style="padding:3px 0">Password</td></tr>' . $rows . '</table>' . $note . '</details>';

    ob_start(function ($html) use ($banner) {
        if (stripos($html, '</body>') !== false) {
            return preg_replace('~</body>~i', $banner . '</body>', $html, 1);
        }
        return $html;
    });
})();
