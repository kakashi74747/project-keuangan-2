<?php
require 'includes/koneksi.php';
require 'includes/auth_check.php';

$user_id = $_SESSION['user_id'];
$pesan_sukses = '';
$pesan_error = '';

$form_mode = 'tambah';
$edit_data = null;

// Daftar Ikon (Bisa ditambahkan sesuai kebutuhan)
$icons = [
    'fas fa-utensils', 'fas fa-shopping-cart', 'fas fa-gas-pump', 'fas fa-home', 'fas fa-bus',
    'fas fa-file-invoice-dollar', 'fas fa-heartbeat', 'fas fa-gift', 'fas fa-graduation-cap',
    'fas fa-plane', 'fas fa-film', 'fas fa-tshirt', 'fas fa-lightbulb', 'fas fa-briefcase', 
    'fas fa-dollar-sign', 'fas fa-wallet', 'fas fa-coins', 'fas fa-piggy-bank', 'fas fa-car',
    'fas fa-medkit', 'fas fa-paw', 'fas fa-gamepad', 'fas fa-book', 'fas fa-wrench'
];
sort($icons); // Urutkan ikon secara alfabetis

if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $form_mode = 'edit';
    $id_to_edit = (int)$_GET['id'];
    $stmt = mysqli_prepare($koneksi, "SELECT * FROM categories WHERE id = ? AND user_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $id_to_edit, $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $edit_data = mysqli_fetch_assoc($result);
    if (!$edit_data) {
        $pesan_error = "Data kategori tidak ditemukan.";
        $form_mode = 'tambah';
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    
    if ($_POST['action'] == 'tambah' || $_POST['action'] == 'edit') {
        $category_name = mysqli_real_escape_string($koneksi, $_POST['category_name']);
        $category_type = mysqli_real_escape_string($koneksi, $_POST['category_type']);
        $category_icon = mysqli_real_escape_string($koneksi, $_POST['category_icon']);
        
        if ($_POST['action'] == 'tambah') {
            $stmt = mysqli_prepare($koneksi, "INSERT INTO categories (user_id, category_name, category_icon, category_type) VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "isss", $user_id, $category_name, $category_icon, $category_type);
            $pesan_sukses = "Kategori berhasil ditambahkan.";
        } else {
            $id = (int)$_POST['id'];
            $stmt = mysqli_prepare($koneksi, "UPDATE categories SET category_name=?, category_icon=?, category_type=? WHERE id=? AND user_id=?");
            mysqli_stmt_bind_param($stmt, "sssii", $category_name, $category_icon, $category_type, $id, $user_id);
            $pesan_sukses = "Kategori berhasil diperbarui. Mengalihkan...";
            echo "<meta http-equiv='refresh' content='2;url=categories.php'>";
        }

        if(!mysqli_stmt_execute($stmt)) {
            $pesan_sukses = ''; // Reset pesan sukses jika gagal
            $pesan_error = "Gagal menyimpan kategori. Pastikan nama kategori belum ada.";
        }
    }
    
    elseif ($_POST['action'] == 'hapus') {
        $id = (int)$_POST['id'];
        $stmt = mysqli_prepare($koneksi, "DELETE FROM categories WHERE id=? AND user_id=?");
        mysqli_stmt_bind_param($stmt, "ii", $id, $user_id);
        if(mysqli_stmt_execute($stmt)) {
            $pesan_sukses = "Kategori berhasil dihapus.";
        } else {
            $pesan_error = "Gagal menghapus kategori. Kategori ini mungkin masih terhubung dengan data transaksi.";
        }
    }
}

$queryCategories = mysqli_prepare($koneksi, "SELECT * FROM categories WHERE user_id = ? ORDER BY category_type, category_name ASC");
mysqli_stmt_bind_param($queryCategories, "i", $user_id);
mysqli_stmt_execute($queryCategories);
$resultCategories = mysqli_stmt_get_result($queryCategories);

$page_title = "Kelola Kategori - Uangmu App";
$active_page = 'categories';
include 'includes/header.php';
?>

<main>
    <div class="container-fluid px-4">
        <h1 class="mt-4">Kelola Kategori</h1>
        <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Kategori</li>
        </ol>

        <?php if (!empty($pesan_sukses)) { echo '<div class="alert alert-success">'.htmlspecialchars($pesan_sukses).'</div>'; } ?>
        <?php if (!empty($pesan_error)) { echo '<div class="alert alert-danger">'.htmlspecialchars($pesan_error).'</div>'; } ?>
        
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-plus me-1"></i>
                <?= $form_mode == 'edit' ? 'Edit Kategori: ' . htmlspecialchars($edit_data['category_name']) : 'Tambah Kategori Baru'; ?>
            </div>
            <div class="card-body">
                <form method="POST" action="categories.php">
                    <input type="hidden" name="action" value="<?= $form_mode; ?>">
                    <?php if($form_mode == 'edit'): ?>
                        <input type="hidden" name="id" value="<?= htmlspecialchars($edit_data['id']); ?>">
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nama Kategori</label>
                            <input type="text" class="form-control" name="category_name" placeholder="Contoh: Gaji, Makanan" value="<?= htmlspecialchars($edit_data['category_name'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tipe Kategori</label>
                            <div>
                                <?php 
                                $tipe_kategori = ['Pengeluaran', 'Pemasukan'];
                                $selected_tipe = $edit_data['category_type'] ?? 'Pengeluaran';
                                foreach($tipe_kategori as $tipe):
                                    $checked = ($selected_tipe == $tipe) ? 'checked' : '';
                                    $btn_class = ($tipe == 'Pemasukan') ? 'btn-outline-success' : 'btn-outline-danger';
                                ?>
                                    <input type="radio" class="btn-check" name="category_type" id="tipe_<?= $tipe; ?>" value="<?= $tipe; ?>" autocomplete="off" <?= $checked; ?>>
                                    <label class="btn <?= $btn_class; ?> mb-1" for="tipe_<?= $tipe; ?>"><?= $tipe; ?></label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Pilih Ikon</label>
                        <input type="hidden" name="category_icon" id="selected_icon" value="<?= htmlspecialchars($edit_data['category_icon'] ?? 'fas fa-question-circle'); ?>">
                        <div id="icon-picker" class="d-flex flex-wrap gap-2 border p-2 rounded" style="max-height: 200px; overflow-y: auto;">
                            <?php 
                            // Pastikan ikon yang sedang diedit ada di dalam list
                            $current_icon = $edit_data['category_icon'] ?? 'fas fa-question-circle';
                            if (!in_array($current_icon, $icons)) {
                                array_unshift($icons, $current_icon);
                            }

                            foreach ($icons as $icon): ?>
                                <button type="button" class="btn btn-outline-secondary icon-btn <?= $current_icon == $icon ? 'active' : ''; ?>" data-icon="<?= $icon; ?>">
                                    <i class="<?= $icon; ?> fa-2x"></i>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <?php if($form_mode == 'edit'): ?>
                            <a href="categories.php" class="btn btn-secondary">Batal Edit</a>
                            <button type="submit" class="btn btn-warning">Simpan Perubahan</button>
                        <?php else: ?>
                            <button type="submit" class="btn btn-primary">Simpan Kategori</button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header"><i class="fas fa-table me-1"></i>Daftar Kategori Anda</div>
            <div class="card-body">
                <table id="datatablesSimple" class="table table-striped table-hover align-middle">
                    <thead>
                        <tr><th style="width: 10%;">Ikon</th><th>Nama Kategori</th><th>Tipe</th><th>Aksi</th></tr>
                    </thead>
                    <tbody>
                        <?php while ($cat = mysqli_fetch_assoc($resultCategories)) { ?>
                        <tr>
                            <td class="text-center"><i class="<?= htmlspecialchars($cat['category_icon']); ?> fa-2x text-muted"></i></td>
                            <td><?= htmlspecialchars($cat['category_name']); ?></td>
                            <td>
                                <?php if ($cat['category_type'] == 'Pemasukan'): ?>
                                    <span class="badge bg-success">Pemasukan</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Pengeluaran</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="categories.php?action=edit&id=<?= $cat['id']; ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
                                <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#hapusModal<?= $cat['id']; ?>"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<?php mysqli_data_seek($resultCategories, 0); while ($cat = mysqli_fetch_assoc($resultCategories)) { ?>
<div class="modal fade" id="hapusModal<?= $cat['id']; ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Konfirmasi Hapus</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST" action="categories.php">
                <input type="hidden" name="action" value="hapus"><input type="hidden" name="id" value="<?= $cat['id']; ?>">
                <div class="modal-body">
                    <p>Yakin ingin menghapus kategori <strong><?= htmlspecialchars($cat['category_name']); ?></strong>?</p>
                    <p class="text-danger small">Menghapus kategori akan membuat transaksi terkait menjadi "Tanpa Kategori".</p>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-danger">Ya, Hapus</button></div>
            </form>
        </div>
    </div>
</div>
<?php } ?>

<?php
$additional_scripts = '
<style>
    #icon-picker .icon-btn { width: 50px; height: 50px; display: inline-flex; align-items: center; justify-content: center; }
</style>
<script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js" crossorigin="anonymous"></script>
<script>
    window.addEventListener(\'DOMContentLoaded\', event => {
        const datatablesSimple = document.getElementById(\'datatablesSimple\');
        if (datatablesSimple) { new simpleDatatables.DataTable(datatablesSimple); }

        const iconPicker = document.getElementById(\'icon-picker\');
        const hiddenInput = document.getElementById(\'selected_icon\');
        
        iconPicker.addEventListener(\'click\', function(e) {
            const button = e.target.closest(\'.icon-btn\');
            if (!button) return;

            iconPicker.querySelectorAll(\'.icon-btn\').forEach(btn => btn.classList.remove(\'active\'));
            button.classList.add(\'active\');
            hiddenInput.value = button.dataset.icon;
        });
    });
</script>';

include 'includes/footer.php';
?>