<?php
require 'includes/koneksi.php';
require 'includes/auth_check.php';

$user_id = $_SESSION['user_id'];
$pesan_sukses = '';
$pesan_error = '';

// Tentukan periode (bulan & tahun)
$bulan_sekarang = isset($_GET['month']) ? (int)$_GET['month'] : date('m');
$tahun_sekarang = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

// Logika untuk menyimpan atau update budget
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['simpan_budget'])) {
    $budgets = $_POST['budget'];
    $month = $_POST['month'];
    $year = $_POST['year'];

    mysqli_begin_transaction($koneksi);
    try {
        foreach ($budgets as $category_id => $amount) {
            $amount = (float)str_replace('.', '', $amount ?? 0);
            $category_id = (int)$category_id;

            // Gunakan INSERT ... ON DUPLICATE KEY UPDATE untuk efisiensi
            $stmt = mysqli_prepare($koneksi, "
                INSERT INTO budgets (user_id, category_id, amount, `month`, `year`) 
                VALUES (?, ?, ?, ?, ?) 
                ON DUPLICATE KEY UPDATE amount = VALUES(amount)
            ");
            mysqli_stmt_bind_param($stmt, "iidis", $user_id, $category_id, $amount, $month, $year);
            mysqli_stmt_execute($stmt);
        }
        mysqli_commit($koneksi);
        $pesan_sukses = "Anggaran untuk " . date("F Y", mktime(0, 0, 0, $month, 1, $year)) . " berhasil disimpan.";
    } catch (Exception $e) {
        mysqli_rollback($koneksi);
        $pesan_error = "Terjadi kesalahan: " . $e->getMessage();
    }
}

// === PERUBAHAN QUERY KATEGORI UNTUK IKON ===
$query_kategori = mysqli_prepare($koneksi, "
    SELECT id, category_name, category_icon 
    FROM categories 
    WHERE user_id = ? AND category_type = 'Pengeluaran' 
    ORDER BY category_name ASC
");
mysqli_stmt_bind_param($query_kategori, "i", $user_id);
mysqli_stmt_execute($query_kategori);
$result_kategori = mysqli_stmt_get_result($query_kategori);

$kategori_list = [];
while($row = mysqli_fetch_assoc($result_kategori)){
    $kategori_list[$row['id']] = [
        'name' => $row['category_name'],
        'icon' => $row['category_icon'], // Menyimpan ikon
        'budget' => 0,
        'actual' => 0,
    ];
}

// Mengambil data budget yang sudah ada untuk periode ini
$stmt_budget = mysqli_prepare($koneksi, "SELECT category_id, amount FROM budgets WHERE user_id = ? AND `month` = ? AND `year` = ?");
mysqli_stmt_bind_param($stmt_budget, "iii", $user_id, $bulan_sekarang, $tahun_sekarang);
mysqli_stmt_execute($stmt_budget);
$result_budget = mysqli_stmt_get_result($stmt_budget);
while($row = mysqli_fetch_assoc($result_budget)){
    if(isset($kategori_list[$row['category_id']])){
        $kategori_list[$row['category_id']]['budget'] = $row['amount'];
    }
}

// Mengambil data pengeluaran aktual untuk periode ini
$stmt_actual = mysqli_prepare($koneksi, "
    SELECT category_id, SUM(amount) as total_pengeluaran 
    FROM transactions 
    WHERE user_id = ? AND transaction_type = 'Pengeluaran' AND MONTH(transaction_date) = ? AND YEAR(transaction_date) = ? AND category_id IS NOT NULL
    GROUP BY category_id
");
mysqli_stmt_bind_param($stmt_actual, "iii", $user_id, $bulan_sekarang, $tahun_sekarang);
mysqli_stmt_execute($stmt_actual);
$result_actual = mysqli_stmt_get_result($stmt_actual);
while($row = mysqli_fetch_assoc($result_actual)){
     if(isset($kategori_list[$row['category_id']])){
        $kategori_list[$row['category_id']]['actual'] = $row['total_pengeluaran'];
    }
}

$page_title = "Anggaran Bulanan - Uangmu App";
$active_page = 'budgeting';
include 'includes/header.php';
?>

<main>
    <div class="container-fluid px-4">
        <h1 class="mt-4">Anggaran Bulanan</h1>
        <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Anggaran</li>
        </ol>

        <?php if ($pesan_sukses) echo "<div class='alert alert-success'>$pesan_sukses</div>"; ?>
        <?php if ($pesan_error) echo "<div class='alert alert-danger'>$pesan_error</div>"; ?>

        <div class="card mb-4">
            <div class="card-header"><i class="fas fa-filter me-1"></i>Pilih Periode Anggaran</div>
            <div class="card-body">
                <form method="GET" action="budgeting.php" class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label for="month" class="form-label">Bulan</label>
                        <select name="month" id="month" class="form-select">
                            <?php for ($i = 1; $i <= 12; $i++): ?>
                                <option value="<?= $i; ?>" <?= $i == $bulan_sekarang ? 'selected' : ''; ?>>
                                    <?= date('F', mktime(0, 0, 0, $i, 10)); ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label for="year" class="form-label">Tahun</label>
                        <select name="year" id="year" class="form-select">
                            <?php for ($i = date('Y') + 1; $i >= date('Y') - 5; $i--): ?>
                                <option value="<?= $i; ?>" <?= $i == $tahun_sekarang ? 'selected' : ''; ?>><?= $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Tampilkan</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header"><i class="fas fa-tasks me-1"></i>Atur Anggaran untuk <?= date("F Y", mktime(0, 0, 0, $bulan_sekarang, 1, $tahun_sekarang)); ?></div>
            <div class="card-body">
                <form action="budgeting.php?month=<?= $bulan_sekarang ?>&year=<?= $tahun_sekarang ?>" method="POST">
                    <input type="hidden" name="month" value="<?= $bulan_sekarang; ?>">
                    <input type="hidden" name="year" value="<?= $tahun_sekarang; ?>">
                    <div class="table-responsive">
                         <table class="table table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Kategori</th>
                                    <th style="width: 25%;">Anggaran (Rp)</th>
                                    <th style="width: 40%;">Pengeluaran Aktual</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($kategori_list)): ?>
                                    <tr>
                                        <td colspan="3" class="text-center">Anda belum memiliki kategori pengeluaran. Silakan <a href="categories.php">tambahkan kategori</a> terlebih dahulu.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach($kategori_list as $id => $data): 
                                        $sisa = $data['budget'] - $data['actual'];
                                        $persentase = ($data['budget'] > 0) ? ($data['actual'] / $data['budget']) * 100 : 0;
                                        $persentase_tampil = min($persentase, 100);
                                        
                                        $progress_color = 'bg-success';
                                        if ($persentase > 75) $progress_color = 'bg-warning';
                                        if ($persentase >= 100) $progress_color = 'bg-danger';
                                    ?>
                                    <tr>
                                        <td>
                                            <strong>
                                                <i class="<?= htmlspecialchars($data['icon']); ?> fa-lg me-2 text-muted"></i>
                                                <?= htmlspecialchars($data['name']); ?>
                                            </strong>
                                        </td>
                                        <td>
                                            <input type="text" class="form-control price-format" name="budget[<?= $id ?>]" value="<?= number_format($data['budget'], 0, ',', ''); ?>">
                                        </td>
                                        <td>
                                            <div>Rp <?= number_format($data['actual'], 0, ',', '.'); ?> dari Rp <?= number_format($data['budget'], 0, ',', '.'); ?></div>
                                            <div class="progress mt-2" style="height: 20px;">
                                                <div class="progress-bar <?= $progress_color ?>" role="progressbar" style="width: <?= $persentase_tampil ?>%;" aria-valuenow="<?= $persentase_tampil ?>"><?= number_format($persentase, 1) ?>%</div>
                                            </div>
                                            <small class="text-muted">
                                                Sisa: 
                                                <span class="fw-bold <?= $sisa < 0 ? 'text-danger' : 'text-success' ?>">
                                                    Rp <?= number_format($sisa, 0, ',', '.'); ?>
                                                </span>
                                            </small>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="d-grid mt-3">
                        <button type="submit" name="simpan_budget" class="btn btn-primary btn-lg">Simpan Anggaran</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<?php
$additional_scripts = '
<script>
    window.addEventListener(\'DOMContentLoaded\', event => {
        function formatRupiah(angkaStr) {
            let number_string = String(angkaStr).replace(/[^0-9]/g, \'\');
            return number_string.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        }

        document.querySelectorAll(\'.price-format\').forEach(input => {
            input.value = formatRupiah(input.value);
            input.addEventListener(\'keyup\', function(e){
                this.value = formatRupiah(this.value);
            });
        });
    });
</script>';

include 'includes/footer.php';
?>