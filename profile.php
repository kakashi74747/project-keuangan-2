<?php
require 'includes/koneksi.php';
require 'includes/auth_check.php';

$user_id = $_SESSION['user_id'];
$pesan_sukses_profil = '';
$pesan_error_profil = '';
$pesan_sukses_password = '';
$pesan_error_password = '';

// --- LOGIKA FORM ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Memproses form pembaruan profil
    if (isset($_POST['update_profile'])) {
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $username = trim($_POST['username']);

        if (empty($first_name) || empty($username)) {
            $pesan_error_profil = "Nama Awal dan Username tidak boleh kosong.";
        } else {
            // Cek apakah username baru sudah digunakan oleh user lain
            $stmt_check = mysqli_prepare($koneksi, "SELECT id FROM users WHERE username = ? AND id != ?");
            mysqli_stmt_bind_param($stmt_check, "si", $username, $user_id);
            mysqli_stmt_execute($stmt_check);
            mysqli_stmt_store_result($stmt_check);

            if (mysqli_stmt_num_rows($stmt_check) > 0) {
                $pesan_error_profil = "Username sudah digunakan. Silakan pilih yang lain.";
            } else {
                $stmt_update = mysqli_prepare($koneksi, "UPDATE users SET first_name=?, last_name=?, username=? WHERE id=?");
                mysqli_stmt_bind_param($stmt_update, "sssi", $first_name, $last_name, $username, $user_id);
                if (mysqli_stmt_execute($stmt_update)) {
                    $_SESSION['full_name'] = trim($first_name . ' ' . $last_name);
                    $_SESSION['username'] = $username;
                    $pesan_sukses_profil = "Profil berhasil diperbarui.";
                } else {
                    $pesan_error_profil = "Gagal memperbarui profil.";
                }
            }
        }
    }

    // Memproses form ganti password
    if (isset($_POST['change_password'])) {
        $password_lama = $_POST['password_lama'];
        $password_baru = $_POST['password_baru'];
        $konfirmasi_password = $_POST['konfirmasi_password'];

        $stmt_get_pass = mysqli_prepare($koneksi, "SELECT password FROM users WHERE id = ?");
        mysqli_stmt_bind_param($stmt_get_pass, "i", $user_id);
        mysqli_stmt_execute($stmt_get_pass);
        $result = mysqli_stmt_get_result($stmt_get_pass);
        $user = mysqli_fetch_assoc($result);

        if ($user['password'] !== $password_lama) {
            $pesan_error_password = "Password lama salah.";
        } elseif ($password_baru !== $konfirmasi_password) {
            $pesan_error_password = "Konfirmasi password baru tidak cocok.";
        } elseif (empty($password_baru)) {
            $pesan_error_password = "Password baru tidak boleh kosong.";
        } else {
            $stmt_update_pass = mysqli_prepare($koneksi, "UPDATE users SET password = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt_update_pass, "si", $password_baru, $user_id);
            if (mysqli_stmt_execute($stmt_update_pass)) {
                $pesan_sukses_password = "Password berhasil diubah.";
            } else {
                $pesan_error_password = "Gagal mengubah password.";
            }
        }
    }
}

// Mengambil data pengguna terkini untuk ditampilkan di form
$stmt_user = mysqli_prepare($koneksi, "SELECT first_name, last_name, username FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt_user, "i", $user_id);
mysqli_stmt_execute($stmt_user);
$current_user_data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_user));

$page_title = "Profil Saya - Uangmu App";
$active_page = 'profile'; // Untuk menandai menu aktif jika ada
include 'includes/header.php';
// include 'includes/sidebar.php'; // Sidebar belum ada
?>

<main>
    <div class="container-fluid px-4">
        <h1 class="mt-4">Profil Saya</h1>
        <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Profil</li>
        </ol>

        <div class="row">
            <div class="col-lg-6">
                <div class="card mb-4">
                    <div class="card-header"><i class="fas fa-user-edit me-1"></i>Edit Informasi Profil</div>
                    <div class="card-body">
                        <?php if ($pesan_sukses_profil) echo "<div class='alert alert-success'>$pesan_sukses_profil</div>"; ?>
                        <?php if ($pesan_error_profil) echo "<div class='alert alert-danger'>$pesan_error_profil</div>"; ?>
                        <form action="profile.php" method="POST">
                            <input type="hidden" name="update_profile" value="1">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="form-floating mb-3 mb-md-0">
                                        <input class="form-control" id="inputFirstName" name="first_name" type="text" value="<?= htmlspecialchars($current_user_data['first_name']); ?>" required />
                                        <label for="inputFirstName">Nama Awal</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input class="form-control" id="inputLastName" name="last_name" type="text" value="<?= htmlspecialchars($current_user_data['last_name']); ?>" />
                                        <label for="inputLastName">Nama Akhir</label>
                                    </div>
                                </div>
                            </div>
                            <div class="form-floating mb-3">
                                <input class="form-control" id="inputUsername" name="username" type="text" value="<?= htmlspecialchars($current_user_data['username']); ?>" required />
                                <label for="inputUsername">Username</label>
                            </div>
                            <div class="mt-4 mb-0">
                                <div class="d-grid"><button type="submit" class="btn btn-primary">Simpan Perubahan Profil</button></div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card mb-4">
                    <div class="card-header"><i class="fas fa-key me-1"></i>Ganti Password</div>
                    <div class="card-body">
                        <?php if ($pesan_sukses_password) echo "<div class='alert alert-success'>$pesan_sukses_password</div>"; ?>
                        <?php if ($pesan_error_password) echo "<div class='alert alert-danger'>$pesan_error_password</div>"; ?>
                        <form action="profile.php" method="POST">
                            <input type="hidden" name="change_password" value="1">
                            <div class="form-floating mb-3">
                                <input class="form-control" id="inputPasswordLama" name="password_lama" type="password" required />
                                <label for="inputPasswordLama">Password Lama</label>
                            </div>
                            <div class="form-floating mb-3">
                                <input class="form-control" id="inputPasswordBaru" name="password_baru" type="password" required />
                                <label for="inputPasswordBaru">Password Baru</label>
                            </div>
                            <div class="form-floating mb-3">
                                <input class="form-control" id="inputKonfirmasiPassword" name="konfirmasi_password" type="password" required />
                                <label for="inputKonfirmasiPassword">Konfirmasi Password Baru</label>
                            </div>
                            <div class="mt-4 mb-0">
                                <div class="d-grid"><button type="submit" class="btn btn-danger">Ubah Password</button></div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php
include 'includes/footer.php';
?>