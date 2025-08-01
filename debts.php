<?php
require 'includes/koneksi.php';
require 'includes/auth_check.php';

$user_id = $_SESSION['user_id'];
$pesan_sukses = '';
$pesan_error = '';

// Jika ada pesan dari proses sebelumnya, tampilkan
if (isset($_SESSION['pesan_sukses'])) {
    $pesan_sukses = $_SESSION['pesan_sukses'];
    unset($_SESSION['pesan_sukses']);
}
if (isset($_SESSION['pesan_error'])) {
    $pesan_error = $_SESSION['pesan_error'];
    unset($_SESSION['pesan_error']);
}

// --- LOGIKA FORM ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    mysqli_begin_transaction($koneksi);
    try {
        if ($_POST['action'] == 'tambah' || $_POST['action'] == 'edit') {
            $type = $_POST['type'];
            $person_name = mysqli_real_escape_string($koneksi, $_POST['person_name']);
            $description = mysqli_real_escape_string($koneksi, $_POST['description']);
            $total_amount = (float)str_replace('.', '', $_POST['total_amount'] ?? 0);
            $due_date = !empty($_POST['due_date']) ? $_POST['due_date'] : null;

            if ($_POST['action'] == 'tambah') {
                $stmt = mysqli_prepare($koneksi, "INSERT INTO debts (user_id, type, person_name, description, total_amount, remaining_amount, due_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt, "isssdds", $user_id, $type, $person_name, $description, $total_amount, $total_amount, $due_date);
                $_SESSION['pesan_sukses'] = "Catatan " . lcfirst($type) . " berhasil ditambahkan.";
            } else {
                $id = (int)$_POST['id'];
                $stmt = mysqli_prepare($koneksi, "UPDATE debts SET type=?, person_name=?, description=?, total_amount=?, due_date=? WHERE id=? AND user_id=?");
                mysqli_stmt_bind_param($stmt, "sssdsii", $type, $person_name, $description, $total_amount, $due_date, $id, $user_id);
                 $_SESSION['pesan_sukses'] = "Catatan " . lcfirst($type) . " berhasil diperbarui.";
            }
            if(!mysqli_stmt_execute($stmt)) { throw new Exception("Gagal menyimpan data."); }
        }
        
        elseif ($_POST['action'] == 'bayar') {
            $debt_id = (int)$_POST['debt_id'];
            $account_id = (int)$_POST['account_id'];
            $amount = (float)str_replace('.', '', $_POST['amount'] ?? 0);

            $stmt_debt = mysqli_prepare($koneksi, "SELECT * FROM debts WHERE id = ? AND user_id = ?");
            mysqli_stmt_bind_param($stmt_debt, "ii", $debt_id, $user_id);
            mysqli_stmt_execute($stmt_debt);
            $debt = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_debt));

            if (!$debt || $amount <= 0 || $amount > $debt['remaining_amount']) {
                throw new Exception("Jumlah pembayaran tidak valid.");
            }

            // Logika terbalik: Bayar Utang = Pengeluaran, Terima Piutang = Pemasukan
            if ($debt['type'] == 'Utang') { // Kita membayar utang, uang keluar
                $stmt_cek_saldo = mysqli_prepare($koneksi, "SELECT current_balance FROM accounts WHERE id = ?");
                mysqli_stmt_bind_param($stmt_cek_saldo, "i", $account_id);
                mysqli_stmt_execute($stmt_cek_saldo);
                $akun = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_cek_saldo));
                if ($akun['current_balance'] < $amount) { throw new Exception("Saldo akun tidak mencukupi."); }
                
                $stmt_update_akun = mysqli_prepare($koneksi, "UPDATE accounts SET current_balance = current_balance - ? WHERE id = ?");
                $transaction_type = 'Pengeluaran';
                $description_trx = "Pembayaran utang kepada " . $debt['person_name'];

            } else { // Kita menerima pembayaran piutang, uang masuk
                $stmt_update_akun = mysqli_prepare($koneksi, "UPDATE accounts SET current_balance = current_balance + ? WHERE id = ?");
                $transaction_type = 'Pemasukan';
                 $description_trx = "Penerimaan piutang dari " . $debt['person_name'];
            }
            mysqli_stmt_bind_param($stmt_update_akun, "di", $amount, $account_id);
            mysqli_stmt_execute($stmt_update_akun);

            // Kurangi sisa utang & update status jika lunas
            $new_remaining = $debt['remaining_amount'] - $amount;
            $new_status = ($new_remaining <= 0) ? 'Lunas' : 'Belum Lunas';
            $stmt_update_debt = mysqli_prepare($koneksi, "UPDATE debts SET remaining_amount = ?, status = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt_update_debt, "dsi", $new_remaining, $new_status, $debt_id);
            mysqli_stmt_execute($stmt_update_debt);
            
            // Catat di riwayat pembayaran utang
            $stmt_hist = mysqli_prepare($koneksi, "INSERT INTO debt_transactions (debt_id, account_id, amount) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($stmt_hist, "iid", $debt_id, $account_id, $amount);
            mysqli_stmt_execute($stmt_hist);

            // Catat di tabel transaksi utama
            $stmt_trx = mysqli_prepare($koneksi, "INSERT INTO transactions (user_id, account_id, transaction_type, amount, description, transaction_date) VALUES (?, ?, ?, ?, ?, NOW())");
            mysqli_stmt_bind_param($stmt_trx, "iisds", $user_id, $account_id, $transaction_type, $amount, $description_trx);
            mysqli_stmt_execute($stmt_trx);
            
            $_SESSION['pesan_sukses'] = "Pembayaran berhasil dicatat.";
        }

        elseif ($_POST['action'] == 'hapus') {
            $id = (int)$_POST['id'];
            $stmt = mysqli_prepare($koneksi, "DELETE FROM debts WHERE id=? AND user_id=?");
            mysqli_stmt_bind_param($stmt, "ii", $id, $user_id);
            if(mysqli_stmt_execute($stmt)) {
                 $_SESSION['pesan_sukses'] = "Catatan utang/piutang berhasil dihapus.";
            } else {
                throw new Exception("Gagal menghapus data.");
            }
        }
        mysqli_commit($koneksi);
    } catch (Exception $e) {
        mysqli_rollback($koneksi);
        $_SESSION['pesan_error'] = $e->getMessage();
    }
    header("Location: debts.php");
    exit();
}


// --- PENGAMBILAN DATA ---
$form_mode = 'tambah';
$edit_data = null;
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $form_mode = 'edit';
    $id_to_edit = (int)$_GET['id'];
    $stmt = mysqli_prepare($koneksi, "SELECT * FROM debts WHERE id = ? AND user_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $id_to_edit, $user_id);
    mysqli_stmt_execute($stmt);
    $edit_data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    if (!$edit_data) { $form_mode = 'tambah'; }
}

$queryDebts = mysqli_prepare($koneksi, "SELECT * FROM debts WHERE user_id = ? ORDER BY status ASC, due_date ASC");
mysqli_stmt_bind_param($queryDebts, "i", $user_id);
mysqli_stmt_execute($queryDebts);
$resultDebts = mysqli_stmt_get_result($queryDebts);
$queryAccounts = mysqli_query($koneksi, "SELECT * FROM accounts WHERE user_id = $user_id ORDER BY account_name ASC");

$page_title = "Utang & Piutang - Uangmu App";
// $active_page = 'debts'; // Uncomment jika sudah ditambahkan di header.php
include 'includes/header.php';
// include 'includes/sidebar.php'; // Sidebar belum ada, jadi dinonaktifkan
?>

<main>
    <div class="container-fluid px-4">
        <h1 class="mt-4">Utang & Piutang</h1>
        <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Utang & Piutang</li>
        </ol>

        <?php if (!empty($pesan_sukses)) { echo '<div class="alert alert-success">'.htmlspecialchars($pesan_sukses).'</div>'; } ?>
        <?php if (!empty($pesan_error)) { echo '<div class="alert alert-danger">'.htmlspecialchars($pesan_error).'</div>'; } ?>
        
        <div class="card mb-4">
            <div class="card-header"><i class="fas fa-plus me-1"></i><?= $form_mode == 'edit' ? 'Edit Catatan' : 'Tambah Catatan Baru'; ?></div>
            <div class="card-body">
                <form method="POST" action="debts.php">
                    <input type="hidden" name="action" value="<?= $form_mode; ?>">
                    <?php if($form_mode == 'edit'): ?><input type="hidden" name="id" value="<?= htmlspecialchars($edit_data['id']); ?>"><?php endif; ?>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tipe</label>
                            <select class="form-select" name="type" required>
                                <option value="Utang" <?= (($edit_data['type'] ?? '') == 'Utang') ? 'selected' : ''; ?>>Utang (Saya berutang)</option>
                                <option value="Piutang" <?= (($edit_data['type'] ?? '') == 'Piutang') ? 'selected' : ''; ?>>Piutang (Orang lain berutang ke saya)</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nama Orang</label>
                            <input type="text" class="form-control" name="person_name" placeholder="Nama pemberi/penerima utang" value="<?= htmlspecialchars($edit_data['person_name'] ?? ''); ?>" required>
                        </div>
                    </div>
                     <div class="mb-3">
                        <label class="form-label">Deskripsi</label>
                        <textarea class="form-control" name="description" rows="2"><?= htmlspecialchars($edit_data['description'] ?? ''); ?></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Jumlah Total</label>
                            <input type="text" class="form-control price-format" name="total_amount" value="<?= number_format($edit_data['total_amount'] ?? 0, 0, ',', ''); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tanggal Jatuh Tempo (Opsional)</label>
                            <input type="date" class="form-control" name="due_date" value="<?= htmlspecialchars($edit_data['due_date'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <?php if($form_mode == 'edit'): ?>
                            <a href="debts.php" class="btn btn-secondary">Batal</a>
                            <button type="submit" class="btn btn-warning">Simpan Perubahan</button>
                        <?php else: ?>
                            <button type="submit" class="btn btn-primary">Simpan Catatan</button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header"><i class="fas fa-table me-1"></i>Daftar Utang & Piutang</div>
            <div class="card-body">
                <table id="datatablesSimple" class="table table-striped table-hover">
                    <thead>
                        <tr><th>Tipe</th><th>Nama</th><th>Sisa</th><th>Jatuh Tempo</th><th>Status</th><th>Aksi</th></tr>
                    </thead>
                    <tbody>
                        <?php while ($debt = mysqli_fetch_assoc($resultDebts)) { ?>
                        <tr>
                            <td>
                                <?php if ($debt['type'] == 'Utang'): ?>
                                    <span class="badge bg-danger">Utang</span>
                                <?php else: ?>
                                    <span class="badge bg-success">Piutang</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($debt['person_name']); ?></td>
                            <td>Rp <?= number_format($debt['remaining_amount'], 0, ',', '.'); ?><br><small class="text-muted">dari Rp <?= number_format($debt['total_amount'], 0, ',', '.'); ?></small></td>
                            <td><?= !empty($debt['due_date']) ? date('d M Y', strtotime($debt['due_date'])) : 'N/A'; ?></td>
                            <td>
                                <?php if ($debt['status'] == 'Lunas'): ?>
                                    <span class="badge bg-primary">Lunas</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark">Belum Lunas</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($debt['status'] == 'Belum Lunas'): ?>
                                <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#bayarModal<?= $debt['id']; ?>"><i class="fas fa-money-bill-wave"></i></button>
                                <?php endif; ?>
                                <a href="debts.php?action=edit&id=<?= $debt['id']; ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
                                <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#hapusModal<?= $debt['id']; ?>"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>


<?php 
// Reset pointer result set untuk loop modal
mysqli_data_seek($resultDebts, 0); 
while ($debt = mysqli_fetch_assoc($resultDebts)) { ?>

<div class="modal fade" id="bayarModal<?= $debt['id']; ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?= $debt['type'] == 'Utang' ? 'Bayar Utang' : 'Terima Piutang'; ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="debts.php">
                <input type="hidden" name="action" value="bayar">
                <input type="hidden" name="debt_id" value="<?= $debt['id']; ?>">
                <div class="modal-body">
                    <p><strong>Kepada/Dari:</strong> <?= htmlspecialchars($debt['person_name']); ?></p>
                    <p><strong>Sisa:</strong> Rp <?= number_format($debt['remaining_amount'], 0, ',', '.'); ?></p>
                    <div class="mb-3">
                        <label class="form-label">Jumlah Pembayaran</label>
                        <input type="text" class="form-control price-format" name="amount" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Akun</label>
                        <select class="form-select" name="account_id" required>
                            <option value="">-- Pilih Akun --</option>
                            <?php 
                            mysqli_data_seek($queryAccounts, 0);
                            while($acc = mysqli_fetch_assoc($queryAccounts)) {
                                echo "<option value='{$acc['id']}'>" . htmlspecialchars($acc['account_name']) . " (Saldo: Rp " . number_format($acc['current_balance'], 0, ',', '.') . ")</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Pembayaran</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="hapusModal<?= $debt['id']; ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Konfirmasi Hapus</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST" action="debts.php">
                <input type="hidden" name="action" value="hapus"><input type="hidden" name="id" value="<?= $debt['id']; ?>">
                <div class="modal-body">
                    <p>Yakin ingin menghapus catatan ini?</p>
                    <p class="text-danger small">Tindakan ini juga akan menghapus riwayat pembayarannya.</p>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-danger">Ya, Hapus</button></div>
            </form>
        </div>
    </div>
</div>

<?php } ?>


<?php
$additional_scripts = '
<script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js" crossorigin="anonymous"></script>
<script>
    window.addEventListener(\'DOMContentLoaded\', event => {
        const datatablesSimple = document.getElementById(\'datatablesSimple\');
        if (datatablesSimple) { new simpleDatatables.DataTable(datatablesSimple); }

        function formatRupiah(angkaStr) {
            let number_string = angkaStr.replace(/[^0-9]/g, \'\');
            return number_string.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        }

        document.querySelectorAll(\'.price-format\').forEach(input => {
             input.value = formatRupiah(input.value);
            input.addEventListener(\'keyup\', function(e){
                this.value = formatRupiah(this.value.replace(/\./g, \'\'));
            });
        });
    });
</script>';

include 'includes/footer.php';
// echo $additional_scripts; // Aktifkan jika footer.php mendukung variabel ini
?>