<?php
// koneksi.php sudah memanggil config.php dan session_start()

// 1. Cek apakah session user_id ada
if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "login.php");
    exit();
}

// 2. Validasi user_id ke database (PENTING!)
$user_id_session = $_SESSION['user_id'];
$stmt_check_user = mysqli_prepare($koneksi, "SELECT id FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt_check_user, "i", $user_id_session);
mysqli_stmt_execute($stmt_check_user);
mysqli_stmt_store_result($stmt_check_user);

// 3. Jika user tidak ditemukan di database, hancurkan session dan redirect
if (mysqli_stmt_num_rows($stmt_check_user) == 0) {
    // Hapus semua variabel session
    $_SESSION = array();
    // Hancurkan session
    session_destroy();
    // Redirect ke halaman login dengan pesan error
    header("Location: " . BASE_URL . "login.php?error=Sesi+tidak+valid.+Silakan+login+kembali.");
    exit();
}

mysqli_stmt_close($stmt_check_user);
?>