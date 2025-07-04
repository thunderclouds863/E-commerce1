<?php
// Mulai sesi
session_start();

// Hapus semua data sesi
session_unset();

// Hancurkan sesi
session_destroy();

// Alihkan pengguna kembali ke halaman utama
header("Location: index.php");
exit();
?>
